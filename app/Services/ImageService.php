<?php
namespace App\Services;

use App\Models\Screenshots;
use Intervention\Image\Facades\Image;
use Psr\Http\Message\ResponseInterface;

// Handles image actions
class ImageService {

  // not sure for these 2, but let em be for now

  // Set width 280 by default
  const DEF_WIDTH = 280;

  // Set height to null so to save original one by default
  const DEF_HEIGHT = null;
    
  public function makeThumb(string $imagePath, int $thumbWidth = self::DEF_WIDTH, int $thumbHeight = self::DEF_HEIGHT) {

    // Instantiate Image class
    $image = Image::make($imagePath);

    // Resize image
    $resizedImage = $image->resize($thumbWidth, $thumbHeight, function ($constraint) {
        $constraint->aspectRatio();
    });

    // Convert image to stream
    $imageStream = $resizedImage->stream('jpg', 100);

    // Return wrapped stream ready to be sent
    return \GuzzleHttp\Psr7\StreamWrapper::getResource($imageStream);

  }

  private static function parseResBody (ResponseInterface $res) : array {

    return json_decode((string) $res->getBody(), true);

  }

  public function pushScreenAndThumbToCarnival($screenshotStream, $thumbStream, int $userID) : string {
    
    // Get credentials
    $url = env('CARNIVAL_URL');
    $token = 'bearer '.env('CARNIVAL_TOKEN');

    // HTTP client instantiation
    $client = new \GuzzleHttp\Client();

    // Header for every part of mp request
    $contentTypeHeader = [ 'Content-Type' => 'image/jpeg' ];

    // Make multipart request
    $res = $client->request('PUT',$url, [
      'headers' => [
          'authorization' => $token,
          'at-user-id' => $userID
      ],
      'multipart' => [
          [
              'name'     => 'screenshot',
              'contents' => $screenshotStream,
              'headers'  => $contentTypeHeader
          ],
          [
              'name'     => 'thumb',
              'contents' => $thumbStream,
              'headers'  => $contentTypeHeader
          ]
      ]
    ]);
    
    // Return response parsed object
    return ImageService::parseResBody($res)['url'];

  }

  public function rmScreenAndThumbFromCarnival(int $userID, string $path) : array {

    $client = new \GuzzleHttp\Client();
    $url = env('CARNIVAL_URL');
    $token = 'bearer '.env('CARNIVAL_TOKEN');

    $res = $client->request('DELETE',$url, [
        'headers' => [
            'authorization' => $token,
            'at-user-id' => $userID
        ],
        'json' => [
            'url' => $path
        ]
    ]);

    return ImageService::parseResBody($res);

  }

}