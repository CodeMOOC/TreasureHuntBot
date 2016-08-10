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
 *     @param client $client - Guzzle RESTful client
 *     @param client $file_id - Photo file id received by telegram bot
 * @return string file path
 */
function getFilePath($client, $file_id) {
    echo "picture file id: " . $file_id . PHP_EOL;
    $response = $client->request('GET', 'getFile', [
        'query' => ['file_id' => $file_id]
    ]);

    echo "getFilePath response: " . $response->getBody() . PHP_EOL;
    $body = json_decode($response->getBody(), true);
    return $body['result']['file_path'];
}

/**
 * Function getPicture
 * Get actual picture file
 * https://api.telegram.org/file/bot<token>/<file_path>
 *
 * Parameters:
 *     @param client $client - Guzzle RESTful client
 *     @param client $filePath - Picture file path, REMEMBER to put slash in front of it, i.e. '/<file_path>'
 * @return File picture
 */
function getPicture($client, $filePath) {
    echo "picture file path: " . $filePath . PHP_EOL;
    $uri = 'https://api.telegram.org/file/bot' . TELEGRAM_BOT_TOKEN . '/' . $filePath;
    $response = $client->request('GET', $uri);

    return $response->getBody();
}

/*
function saveIssuedBadgesToFile($data){
    try {
        $filename = "csv/generated/badge-list.csv";
        $file = fopen($filename, "w");

        // Insert data
        foreach ($data as $var) {
            $list = array($var['recipient_identifier'], $var['image']);
            fputcsv($file, $list, ";");
        }

        fclose($file);

        return "badge-list.csv";
    } catch (Exception $e) {
        return "";
    }
}
 *
 */