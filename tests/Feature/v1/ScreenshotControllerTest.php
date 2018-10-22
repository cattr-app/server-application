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

        $screenPath = "../../pic/TestsSetup.png";

        $data = [
            "time_interval_id"  => 1,
            "screenshot"        => new UploadedFile($screenPath, basename($screenPath))
        ];


        $response = $this->postJson("/api/v1/screenshots/create", $data, $headers);

        $response->assertStatus(200);
    }

    public function test_Destroy_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "screenshot"        => "samplebinarydata",
            "time_interval_id"  => "1"
        ];

        /**
         * Upload screenshot and get ID
         */
        $id = $this->post("/api/v1/screenshots/create", $data, $headers);

        $deleteScreenshotData = [
            "id" => $id
        ];

        $response = $this->post("/api/v1/screenshots/remove", $deleteScreenshotData, $headers);

        // @todo: check is test right
        $this->markTestSkipped('Not finished yet.');

        $response->assertStatus(200);
    }
}
