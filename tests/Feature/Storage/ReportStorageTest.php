<?php

namespace Tests\Feature\Storage;

use App\Jobs\GenerateAndSendReport;
use App\Models\User;
use App\Notifications\ReportGenerated;
use App\Reports\DashboardExport;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Email;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ReportStorageTest extends TestCase
{
    private User $admin;

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

    #[\PHPUnit\Framework\Attributes\DataProvider('reportDrivers')]
    public function test_report_mail_attaches_file_from_the_reports_disk(string $driver): void
    {
        config(['filesystems.disks.reports.driver' => $driver]);
        Storage::fake('reports');

        $this->generateReport();
        $path = $this->reportPath();
        $stored = Storage::disk('reports')->get($path);
        $this->assertNotEmpty($stored);

        $sent = null;
        Notification::assertSentTo(
            $this->admin,
            ReportGenerated::class,
            function (ReportGenerated $notification) use (&$sent) {
                $sent = $notification;

                return true;
            }
        );

        $mail = $sent->toMail($this->admin);
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
    }
}
