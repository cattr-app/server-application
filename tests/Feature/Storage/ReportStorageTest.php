<?php

namespace Tests\Feature\Storage;

use App\Jobs\GenerateAndSendReport;
use App\Models\User;
use App\Notifications\ReportGenerated;
use App\Reports\DashboardExport;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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

    public function test_report_mail_attaches_file_from_the_reports_disk(): void
    {
        config(['filesystems.disks.reports.driver' => 's3']);
        Storage::fake('reports');

        $this->generateReport();
        $path = $this->reportPath();

        Notification::assertSentTo(
            $this->admin,
            ReportGenerated::class,
            function (ReportGenerated $notification) use ($path) {
                $attachments = $notification->toMail($this->admin)->rawAttachments;

                return count($attachments) === 1
                    && $attachments[0]['name'] === basename($path)
                    && $attachments[0]['data'] === Storage::disk('reports')->get($path);
            }
        );
    }
}
