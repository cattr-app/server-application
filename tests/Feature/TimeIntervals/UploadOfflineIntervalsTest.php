<?php

namespace Tests\Feature\TimeIntervals;

use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use MessagePack\MessagePack;
use PHPUnit\Framework\Attributes\DataProvider;
use phpseclib4\Crypt\RSA;
use Settings;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;
use Tests\TestResponse;
use ZipArchive;

/**
 * The archive is what the desktop application builds: `Intervals` is a MessagePack list of intervals and
 * `EncryptedDigest` is the SHA-256 digest of `Intervals` encrypted with the public key of the instance.
 */
class UploadOfflineIntervalsTest extends TestCase
{
    private const URI = 'offline-sync/upload-intervals';

    private User $worker;
    private Task $task;

    /** @var list<string> Archives created by the test */
    private array $archives = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->worker = UserFactory::asUser()->withTokens()->create();
        $this->task = TaskFactory::forUser($this->worker)->create();
    }

    protected function tearDown(): void
    {
        foreach ($this->archives as $archive) {
            @unlink($archive);
        }

        parent::tearDown();
    }

    public static function privilegedRoles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
        ];
    }

    public function test_upload_own_interval(): void
    {
        $this->assertDatabaseMissing('time_intervals', ['user_id' => $this->worker->id]);

        $response = $this->upload($this->worker, [$this->interval()]);

        $response->assertOk();
        $response->assertJsonStructure(['data' => [['interval', 'message', 'success']]]);
        $response->assertJsonPath('data.0.success', true);
        $response->assertJsonPath('data.0.message', __('validation.offline-sync.time_interval_added'));
        $this->assertDatabaseHas('time_intervals', [
            'user_id' => $this->worker->id,
            'task_id' => $this->task->id,
            'start_at' => '2026-03-10 10:00:00',
            'end_at' => '2026-03-10 10:05:00',
            'activity_fill' => 80,
            'mouse_fill' => 40,
            'keyboard_fill' => 30,
        ]);
    }

    #[DataProvider('privilegedRoles')]
    public function test_upload_for_another_user_as_privileged_role(string $role): void
    {
        $uploader = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->upload($uploader, [$this->interval()]);

        $response->assertOk();
        $response->assertJsonPath('data.0.success', true);
        $this->assertDatabaseHas('time_intervals', [
            'user_id' => $this->worker->id,
            'task_id' => $this->task->id,
        ]);
    }

    public function test_upload_for_another_user_as_auditor(): void
    {
        $uploader = UserFactory::refresh()->asAuditor()->withTokens()->create();

        $response = $this->upload($uploader, [$this->interval()]);

        $response->assertOk();
        $response->assertJsonPath('data.0.success', false);
        $response->assertJsonPath('data.0.message', __('validation.offline-sync.cannot_create_interval'));
        $this->assertDatabaseMissing('time_intervals', ['user_id' => $this->worker->id]);
    }

    public function test_upload_for_another_user_as_user(): void
    {
        $uploader = UserFactory::refresh()->asUser()->withTokens()->create();

        // Users do not see other users, so the owner of the intervals is not found
        $response = $this->upload($uploader, [$this->interval()]);

        $response->assertError(400);
        $this->assertDatabaseMissing('time_intervals', ['user_id' => $this->worker->id]);
    }

    public function test_each_interval_is_reported_separately(): void
    {
        $response = $this->upload($this->worker, [
            $this->interval(),
            // Ends before it starts
            $this->interval(['start_at' => '2026-03-10T12:05:00+00:00', 'end_at' => '2026-03-10T12:00:00+00:00']),
            $this->interval(['start_at' => '2026-03-10T14:00:00+00:00', 'end_at' => '2026-03-10T14:05:00+00:00']),
        ]);

        $response->assertOk();
        $this->assertSame([true, false, true], array_column($response->json('data'), 'success'));
        $this->assertArrayHasKey('start_at', $response->json('data.1.message'));
        $this->assertSame(2, TimeInterval::where('user_id', $this->worker->id)->count());
    }

    public function test_unknown_task_is_reported_without_failing_the_upload(): void
    {
        $response = $this->upload($this->worker, [
            $this->interval(['task_id' => Task::withoutGlobalScopes()->max('id') + 1000]),
            $this->interval(['start_at' => '2026-03-10T14:00:00+00:00', 'end_at' => '2026-03-10T14:05:00+00:00']),
        ]);

        $response->assertOk();
        $this->assertSame([false, true], array_column($response->json('data'), 'success'));
        $this->assertSame(1, TimeInterval::where('user_id', $this->worker->id)->count());
    }

    public function test_overlapping_interval_is_not_added_again(): void
    {
        $this->upload($this->worker, [$this->interval()])->assertOk();

        $response = $this->upload($this->worker, [$this->interval()]);

        $response->assertOk();
        $response->assertJsonPath('data.0.success', false);
        $this->assertArrayHasKey('end_at', $response->json('data.0.message'));
        $this->assertSame(1, TimeInterval::where('user_id', $this->worker->id)->count());
    }

    public function test_interval_with_screenshot_keeps_its_id(): void
    {
        $screenshotId = (string)Str::uuid();

        $response = $this->upload($this->worker, [
            $this->interval(['has_screenshot' => true, 'screenshot_id' => $screenshotId]),
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.0.success', true);
        $this->assertDatabaseHas('time_intervals', [
            'user_id' => $this->worker->id,
            'screenshot_id' => $screenshotId,
        ]);
    }

    public function test_interval_with_screenshot_requires_its_id(): void
    {
        $response = $this->upload($this->worker, [$this->interval(['has_screenshot' => true])]);

        $response->assertOk();
        $response->assertJsonPath('data.0.success', false);
        $this->assertArrayHasKey('screenshot_id', $response->json('data.0.message'));
        $this->assertDatabaseMissing('time_intervals', ['user_id' => $this->worker->id]);
    }

    public function test_digest_mismatch(): void
    {
        $intervals = MessagePack::pack([$this->interval()]);
        $otherIntervals = MessagePack::pack([$this->interval(['task_id' => $this->task->id + 1])]);
        $archive = $this->archive($intervals, $this->encryptDigest($otherIntervals));

        $response = $this->uploadArchive($this->worker, $archive);

        $response->assertError(400);
        $this->assertDatabaseMissing('time_intervals', ['user_id' => $this->worker->id]);
    }

    public function test_digest_encrypted_with_another_key(): void
    {
        $content = MessagePack::pack([$this->interval()]);
        $anotherKey = RSA::createKey(2048)->getPublicKey();
        $digest = $anotherKey->withPadding(RSA::ENCRYPTION_OAEP)->withHash('sha256')
            ->encrypt(openssl_digest($content, 'sha256'));

        $response = $this->uploadArchive($this->worker, $this->archive($content, $digest));

        $response->assertError(400);
        $this->assertDatabaseMissing('time_intervals', ['user_id' => $this->worker->id]);
    }

    public function test_truncated_encrypted_digest(): void
    {
        $content = MessagePack::pack([$this->interval()]);
        $digest = substr($this->encryptDigest($content), 0, 16);

        $response = $this->uploadArchive($this->worker, $this->archive($content, $digest));

        $response->assertError(400);
        $this->assertDatabaseMissing('time_intervals', ['user_id' => $this->worker->id]);
    }

    public function test_empty_list_of_intervals(): void
    {
        $response = $this->upload($this->worker, []);

        $response->assertError(400);
    }

    public function test_unknown_user(): void
    {
        $unknownUserId = User::withoutGlobalScopes()->max('id') + 1;

        $response = $this->upload($this->worker, [$this->interval(['user_id' => $unknownUserId])]);

        $response->assertError(400);
    }

    public function test_file_is_required(): void
    {
        $response = $this->actingAs($this->worker)->postJson(self::URI);

        $response->assertValidationError();
    }

    public function test_file_must_be_a_zip_archive(): void
    {
        $path = $this->temporaryFile('not an archive');

        $response = $this->uploadArchive($this->worker, $path);

        $response->assertValidationError();
    }

    public function test_file_must_have_cattr_extension(): void
    {
        $response = $this->uploadArchive($this->worker, $this->archiveOf([$this->interval()]), 'intervals.zip');

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_public_key_of_the_instance_encrypts_the_digest(): void
    {
        $response = $this->actingAs($this->worker)->getJson('offline-sync/public-key');

        $publicKey = RSA::load($response->json('data.key'));
        $encrypted = $publicKey->withPadding(RSA::ENCRYPTION_OAEP)->withHash('sha256')->encrypt('digest');
        $privateKey = RSA::load(Settings::scope('core.offline-sync')->get('private_key'));

        $this->assertSame(
            'digest',
            $privateKey->withPadding(RSA::ENCRYPTION_OAEP)->withHash('sha256')->decrypt($encrypted)
        );
    }

    private function interval(array $overrides = []): array
    {
        return $overrides + [
            'user_id' => $this->worker->id,
            'task_id' => $this->task->id,
            'start_at' => '2026-03-10T10:00:00+00:00',
            'end_at' => '2026-03-10T10:05:00+00:00',
            'activity_fill' => 80,
            'mouse_fill' => 40,
            'keyboard_fill' => 30,
            'has_screenshot' => false,
        ];
    }

    private function upload(User $uploader, array $intervals): TestResponse
    {
        return $this->uploadArchive($uploader, $this->archiveOf($intervals));
    }

    private function uploadArchive(User $uploader, string $path, string $name = 'intervals.cattr'): TestResponse
    {
        return $this->actingAs($uploader)->post(
            self::URI,
            ['file' => new UploadedFile($path, $name, 'application/zip', null, true)],
            ['Accept' => 'application/json'],
        );
    }

    private function archiveOf(array $intervals): string
    {
        $content = MessagePack::pack($intervals);

        return $this->archive($content, $this->encryptDigest($content));
    }

    private function encryptDigest(string $content): string
    {
        $publicKey = RSA::load(Settings::scope('core.offline-sync')->get('public_key'));

        return $publicKey->withPadding(RSA::ENCRYPTION_OAEP)->withHash('sha256')
            ->encrypt(openssl_digest($content, 'sha256'));
    }

    private function archive(string $intervals, string $encryptedDigest): string
    {
        $path = $this->temporaryFile('');

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('Intervals', $intervals);
        $zip->addFromString('EncryptedDigest', $encryptedDigest);
        $zip->close();

        return $path;
    }

    private function temporaryFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'offline');
        file_put_contents($path, $content);
        $this->archives[] = $path;

        return $path;
    }
}
