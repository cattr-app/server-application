<?php

namespace Tests\Feature\v1;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Image;
use Tests\TestCase;

class ScreenshotControllerTest extends TestCase
{
    public function test_Create_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $ds = DIRECTORY_SEPARATOR;
        $screenPath = __DIR__ . "{$ds}..{$ds}..{$ds}pic{$ds}TestsSetup.png";

        $data = [
            "time_interval_id"  => 1,
            "screenshot"        => new UploadedFile($screenPath, basename($screenPath))
        ];

        $expectedFields = [
            "res" => [
                "time_interval_id", "path", "thumbnail_path", "updated_at", "created_at", "id"
            ]
        ];

        $response = $this->postJson("/v1/screenshots/create", $data, $headers);
        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }

    public function test_Destroy_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $ds = DIRECTORY_SEPARATOR;
        $screenPath = __DIR__ . "{$ds}..{$ds}..{$ds}pic{$ds}TestsSetup.png";

        $data = [
            "time_interval_id"  => 1,
            "screenshot"        => new UploadedFile($screenPath, basename($screenPath))
        ];

        $expectedFields = [
            "message"
        ];

        /**
         * Upload screenshot and get ID
         */
        $id = $this->post("/v1/screenshots/create", $data, $headers)->json("res.id");

        $deleteScreenshotData = [
            "id" => $id
        ];

        $response = $this->post("/v1/screenshots/remove", $deleteScreenshotData, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
    }
}
