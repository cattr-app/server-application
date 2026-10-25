<?php

namespace Tests\Feature\Storage;

use App\Jobs\GenerateAndSendReport;
use App\Models\User;
use App\Notifications\ReportGenerated;
use App\Reports\DashboardExport;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\FilesystemOperator;
use RuntimeException;
use Symfony\Component\Mime\Email;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ReportStorageTest extends TestCase
{
    private User $admin;

    /** @var resource|null the last stream handed out by nonSeekableStream() */
    private $source = null;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->admin = UserFactory::refresh()->asAdmin()->create();
    }

    private function generateReport(): GenerateAndSendReport
    {
        $job = new GenerateAndSendReport(
            DashboardExport::init([$this->admin->id], [], now()->subDay(), now(), 'UTC', 'UTC'),
            $this->admin,
            'csv',
        );
        $job->handle();

        return $job;
    }

    private function reportPath(): string
    {
        $files = Storage::disk('reports')->allFiles('reports');
        $this->assertCount(1, $files);

        return $files[0];
    }

    public function test_local_report_url_is_served_from_public_storage(): void
    {
        Storage::fake('reports');

        $url = $this->generateReport()->getPublicPath();

        // the report path starts with a slash, hence "/storage//reports/..." (unchanged behaviour)
        $this->assertSame('/storage/' . $this->reportPath(), preg_replace('#/+#', '/', $url));
    }

    public function test_s3_report_url_is_presigned_for_ten_minutes(): void
    {
        config(['filesystems.disks.reports.driver' => 's3']);
        Storage::fake('reports');
        $this->freezeSecond();

        $url = $this->generateReport()->getPublicPath();

        $expiration = now()->addMinutes(GenerateAndSendReport::URL_TTL_MINUTES)->getTimestamp();
        $this->assertSame(10, GenerateAndSendReport::URL_TTL_MINUTES);
        // Storage::fake() builds presigned-like URLs as <path>?expiration=<timestamp>
        $this->assertStringEndsWith($this->reportPath() . "?expiration=$expiration", $url);
    }

    public static function reportDrivers(): array
    {
        return [
            'local' => ['local'],
            's3' => ['s3'],
        ];
    }

    /**
     * Replace the (fake) reports disk with one whose readStream() returns $readStream($path).
     */
    private function swapReportsReadStream(callable $readStream): void
    {
        $fake = Storage::disk('reports');
        Storage::set('reports', new class ($fake->getDriver(), $fake->getAdapter(), $fake->getConfig(), $readStream)
            extends FilesystemAdapter {
            /** @var callable */
            private $reader;

            public function __construct(
                FilesystemOperator $driver,
                FlysystemAdapter $adapter,
                array $config,
                callable $reader
            ) {
                parent::__construct($driver, $adapter, $config);
                $this->reader = $reader;
            }

            public function readStream($path)
            {
                return ($this->reader)($path);
            }
        });
    }

    /**
     * A non-seekable read stream, like the HTTP body stream an S3 disk returns from readStream():
     * the read end of a socket pair. Remembered in $this->source to check that it gets closed.
     *
     * @return resource
     */
    private function nonSeekableStream(string $content)
    {
        // the whole content has to fit into the socket buffer, the writer is closed before reading
        $this->assertLessThan(64 * 1024, strlen($content));
        [$reader, $writer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        fwrite($writer, $content);
        fclose($writer);

        $this->assertFalse(stream_get_meta_data($reader)['seekable']);
        $this->assertSame(-1, @fseek($reader, 0));

        return $this->source = $reader;
    }

    private function useNonSeekableReportStream(): void
    {
        $this->swapReportsReadStream(
            fn (string $path) => $this->nonSeekableStream(Storage::disk('reports')->get($path))
        );
    }

    private function sentNotification(): ReportGenerated
    {
        $sent = null;
        Notification::assertSentTo(
            $this->admin,
            ReportGenerated::class,
            function (ReportGenerated $notification) use (&$sent) {
                $sent = $notification;

                return true;
            }
        );

        return $sent;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('reportDrivers')]
    public function test_report_mail_attaches_file_from_the_reports_disk(string $driver): void
    {
        config(['filesystems.disks.reports.driver' => $driver]);
        Storage::fake('reports');

        $this->generateReport();
        $path = $this->reportPath();
        $stored = Storage::disk('reports')->get($path);
        $this->assertNotEmpty($stored);

        if ($driver === 's3') {
            // Storage::fake() is a local disk: hand bufferReport() a non-seekable stream like a real S3 disk does
            $this->useNonSeekableReportStream();
        }

        $mail = $this->sentNotification()->toMail($this->admin);
        $this->assertSame([], $mail->attachments);
        $this->assertCount(1, $mail->rawAttachments);
        $attachment = $mail->rawAttachments[0];
        $this->assertSame(basename($path), $attachment['name']);
        $this->assertSame(Storage::disk('reports')->mimeType($path), $attachment['options']['mime']);
        // the report is passed as a stream, not loaded into a string
        $this->assertIsResource($attachment['data']);

        // render it the way MailChannel does and compare the attachment with the stored file
        $email = (new Email())->from('report@example.com')->to('admin@example.com')->text('report');
        (new Message($email))->attachData($attachment['data'], $attachment['name'], $attachment['options']);
        $parts = $email->getAttachments();
        $this->assertCount(1, $parts);
        $this->assertSame(basename($path), $parts[0]->getFilename());
        $this->assertSame($stored, $parts[0]->getBody());
        $this->assertStringContainsString(
            chunk_split(base64_encode($stored), 76, "\r\n"),
            implode('', iterator_to_array($email->toIterable(), false)),
        );

        if ($driver === 's3') {
            // the non-seekable source was read and closed by bufferReport()
            $this->assertNotNull($this->source);
            $this->assertFalse(is_resource($this->source));
        }
    }

    public function test_truncated_report_stream_fails_instead_of_mailing_a_partial_report(): void
    {
        Storage::fake('reports');
        $this->generateReport();
        $stored = Storage::disk('reports')->get($this->reportPath());
        $this->swapReportsReadStream(fn () => $this->nonSeekableStream(substr($stored, 0, -1)));

        try {
            $this->sentNotification()->toMail($this->admin);
            $this->fail('A truncated report has been attached');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString(
                sprintf('copied %d of %d bytes', strlen($stored) - 1, strlen($stored)),
                $e->getMessage(),
            );
        }

        $this->assertFalse(is_resource($this->source));
    }

    public function test_report_fails_when_the_buffer_cannot_be_opened(): void
    {
        Storage::fake('reports');
        $this->generateReport();
        $path = $this->reportPath();
        $this->useNonSeekableReportStream();

        $notification = new class ($path, 'reports') extends ReportGenerated {
            protected function openBuffer()
            {
                return false;
            }
        };

        try {
            $notification->toMail($this->admin);
            $this->fail('The report has been attached without a buffer');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Unable to open a buffer', $e->getMessage());
        }

        $this->assertFalse(is_resource($this->source));
    }
}
