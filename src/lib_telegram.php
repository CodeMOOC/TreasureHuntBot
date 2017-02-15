<?php
/**
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Support library. Don't change a thing here.
 */

/**
 * Performs a cURL request to a Telegram API and returns the parsed results.
 *
 * @param object Handle to cURL request.
 * @return object | bool Parsed response object or false on failure.
 */
function perform_telegram_request($handle) {
    if($handle === false) {
        Logger::error('Failed to prepare cURL handle', __FILE__);
        return false;
    }

    $response = perform_curl_request($handle);
    if($response === false) {
        return false;
    }
    else if($response === true) {
        // Response does not contain response body
        // Fake a successful API call with an empty response
        return array();
    }

    // Everything fine, return the result as object
    $response = json_decode($response, true);
    return $response['result'];
}

/**
 * Retrieves information about the bot.
 * https://core.telegram.org/bots/api#getme
 *
 * @return object | false Parsed JSON object returned by the API or false on failure.
 */
function telegram_get_bot_info() {
    $handle = prepare_curl_api_request(TELEGRAM_API_URI_BASE . 'getMe', 'GET', null, null);
    return perform_telegram_request($handle);
}

/**
 * Sends a Telegram bot message.
 * https://core.telegram.org/bots/api#sendmessage
 *
 * @param int $chat_id Identifier of the Telegram chat session.
 * @param string $message Message to send.
 * @param array $parameters Additional parameters that match the API request.
 * @return object | false Parsed JSON object returned by the API or false on failure.
 */
function telegram_send_message($chat_id, $message, $parameters = null) {
    $parameters = prepare_parameters($parameters, array(
        'chat_id' => $chat_id,
        'text' => $message
    ));

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_BASE . 'sendMessage', 'POST', $parameters, null);

    return perform_telegram_request($handle);
}

/**
 * Sends a Telegram bot location message.
 * https://core.telegram.org/bots/api#sendlocation
 *
 * @param int $chat_id Identifier of the Telegram chat session.
 * @param float $latitude Coordinate latitude.
 * @param float $longitude Coordinate longitude.
 * @param array $parameters Additional parameters that match the API request.
 * @return object | false Parsed JSON object returned by the API or false on failure.
 */
function telegram_send_location($chat_id, $latitude, $longitude, $parameters = null) {
    if(!is_numeric($latitude) || !is_numeric($longitude)) {
        Logger:error('Latitude and longitude must be numbers', __FILE__);
        return false;
    }

    $parameters = prepare_parameters($parameters, array(
        'chat_id' => $chat_id,
        'latitude' => $latitude,
        'longitude' => $longitude
    ));

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_BASE . 'sendLocation', 'POST', $parameters, null);

    return perform_telegram_request($handle);
}

/**
 * Sends a Telegram bot photo message.
 * https://core.telegram.org/bots/api#sendphoto
 *
 * @param int $chat_id Identifier of the Telegram chat session.
 * @param string $photo_id Relative path to the photo file to attach or full URI.
 * @param array $parameters Additional parameters that match the API request.
 * @return object | false Parsed JSON object returned by the API or false on failure.
 */
function telegram_send_photo($chat_id, $photo_id, $caption, $parameters = null) {
    if(!$photo_id) {
        Logger::error('Path to attached photo must be set', __FILE__);
        return false;
    }
    // Photo is remote if URL or non-existing file identifier is used
    $is_remote = (stripos($photo_id, 'http') === 0) || !file_exists($photo_id);

    $parameters = prepare_parameters($parameters, array(
        'chat_id' => $chat_id,
        'caption' => $caption
    ));

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_BASE . 'sendPhoto', 'POST', $parameters, array(
        'photo' => ($is_remote) ? $photo_id : new CURLFile($photo_id)
    ));

    return perform_telegram_request($handle);
}

/**
 * Sends a Telegram chat action update.
 * https://core.telegram.org/bots/api#sendchataction
 *
 * @param int $chat_id Identifier of the Telegram chat session.
 * @param string $action Type of action. See online API documentation.
 * @return object | false Parsed JSON object returned by the API or false on failure.
 */
function telegram_send_chat_action($chat_id, $action = 'typing') {
    $parameters = array(
        'chat_id' => $chat_id,
        'action' => $action
    );

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_BASE . 'sendChatAction', 'POST', $parameters, null);

    return perform_telegram_request($handle);
}

/**
 * Requests message updates from the Telegram API.
 * https://core.telegram.org/bots/api#getupdates
 *
 * @param int $offset Identifier of the first update to be returned, or null.
 * @param int $limit Maximum count of updates to fetch, or null.
 * @param int | bool $long_poll Performs a long polling request (defaults to false).
 *                              If true is passed, defaults to 60 seconds.
 *                              Otherwise, takes the timeout in seconds to wait.
 * @return array | false Parsed array of updates or false on failure.
 */
function telegram_get_updates($offset = null, $limit = null, $long_poll = false) {
    $parameters = array();
    if(is_numeric($offset))
        $parameters['offset'] = $offset;
    if(is_numeric($limit) && $limit > 0)
        $parameters['limit'] = $limit;
    if($long_poll === true)
        $long_poll = 60;
    if(is_numeric($long_poll) && $long_poll > 0)
        $parameters['timeout'] = $long_poll;

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_BASE . 'getUpdates', 'GET', $parameters, null);

    return perform_telegram_request($handle);
}

/**
 * Request information about a file on Telegram servers.
 * https://core.telegram.org/bots/api#getfile
 *
 * @param string $file_id File Identifier.
 * @return array | bool Parsed JSON object with file information or false on failure.
 */
function telegram_get_file_info($file_id) {
    $parameters = array(
        'file_id' => $file_id
    );

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_BASE . 'getFile', 'POST', $parameters, null);

    return perform_telegram_request($handle);
}

/**
 * Download a file from Telegram.
 * https://core.telegram.org/bots/api#getfile
 *
 * @param $file_path string File path as returned by a getFile request.
 * @param $output_path string Relative path to the output file.
 * @return bool True if download successful.
 */
function telegram_download_file($file_path, $output_path) {
    $handle = prepare_curl_download_request(TELEGRAM_FILE_API_URI_BASE . $file_path, $output_path);
    return (perform_telegram_request($handle) !== false);
}

/**
 * Edits a past text message.
 * https://core.telegram.org/bots/api#updating-messages
 *
 * @param int $chat_id Identifier of the Telegram chat session.
 * @param int $message_id Identifier of the existing chat message.
 * @param string $message Replacement message.
 * @param array $parameters Additional parameters that match the API request.
 * @return object | false Parsed JSON object returned by the API or false on failure.
 */
function telegram_edit_message($chat_id, $message_id, $text, $parameters = null) {
    $parameters = prepare_parameters($parameters, array(
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text
    ));

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_BASE . 'editMessageText', 'POST', $parameters, null);

    return perform_telegram_request($handle);
}
