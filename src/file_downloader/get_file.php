<?php
use GuzzleHttp\Client;

/**
 * Get picture:
 * First use
 * https://api.telegram.org/bot<bot_token>/getFile
 * to get file path, then
 * https://api.telegram.org/file/bot<token>/<file_path>
 * to get actual picture
 */

function getClient()
{
     return new Client(['base_uri' => TELEGRAM_API_URI_BASE, 'verify' => false]);
}

/**
 * Function getFilePath
 * Get file path for picture
 * https://api.telegram.org/bot<bot_token>/getFile
 *
 * Parameters:
 *     @param Client $client - Guzzle RESTful client
 *     @param string $file_id - Photo file id received by telegram bot
 * @return string file path
 */
function getFilePath($client, $file_id) {
    //echo "picture file id: " . $file_id . PHP_EOL;
    $response = $client->request('GET', 'getFile', [
        'query' => ['file_id' => $file_id]
    ]);

    //echo "getFilePath response: " . $response->getBody() . PHP_EOL;
    $body = json_decode($response->getBody(), true);
    return $body['result']['file_path'];
}

/**
 * Function getPicture
 * Get actual picture file
 * https://api.telegram.org/file/bot<token>/<file_path>
 *
 * Parameters:
 *     @param Client $client - Guzzle RESTful client
 *     @param string $filePath - Picture file path, REMEMBER to put slash in front of it, i.e. '/<file_path>'
 *     @param string $fileId - Picture file id, used as file name
 * @return File picture
 */
function getPicture($client, $filePath, $fileId, $folderPath) {
    //echo "picture file path for telegram: " . $filePath . PHP_EOL;
    $uri = 'https://api.telegram.org/file/bot' . TELEGRAM_BOT_TOKEN . '/' . $filePath;
    $response = $client->request('GET', $uri);

    if($response->getStatusCode() != 200)
        return null;

    $filePath = savePhotoToDisk($response->getBody(), $fileId, $folderPath);
    //echo "saved photo to path: " . $filePath . PHP_EOL;
    return $filePath;
}

/**
 * Function savePhotoToDisk
 * Save photo to disc
 *
 * Parameters:
 *     @param File $data - picture file
 *     @param string $fileId - Picture file id, used as file name
 * @return string File Path
 */
function savePhotoToDisk($data, $fileId, $folderPath){
    try {
        switch($folderPath) {
            case PHOTO_AVATAR:
                $output = 'avatars/' . $fileId . ".jpg";
                break;
            case PHOTO_SELFIE:
                $output = 'selfies/' . $fileId . ".jpg";
                break;
            default:
                $output = 'images/' . $fileId . ".jpg";
        }
        file_put_contents($output, $data);
        return $output;
    } catch (Exception $e) {
        echo "couldn't save photo to disk: " . $e . PHP_EOL;
        return null;
    }
}