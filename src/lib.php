<?php
/**
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Support library. Don't change a thing here.
 */

require_once('config.php');
require_once('lib_bot.php');
require_once('lib_database.php');
require_once('lib_utility.php');

/**
 * Mixes together parameters for an HTTP request.
 *
 * @param array $orig_params Original parameters or null.
 * @param array $add_params Additional parameters or null.
 * @return array Final mixed parameters.
 */
function prepare_parameters($orig_params, $add_params) {
    return unite_arrays($orig_params, $add_params);
}

/**
 * Performs a cURL request and returns the expected response as string.
 *
 * @param object Handle to cURL request.
 * @return string | false Response as text or false on failure.
 */
function perform_curl_request($handle) {
    $response = curl_exec($handle);

    if ($response === false) {
        $errno = curl_errno($handle);
        $error = curl_error($handle);
        error_log("Curl returned error $errno: $error");

        curl_close($handle);

        return false;
    }

    $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));

    curl_close($handle);

    if ($http_code >= 500) {
        error_log('Internal server error');
        return false;
    }
    else if($http_code == 401) {
        error_log('Unauthorized request (check token)');
        return false;
    }
    else if ($http_code != 200) {
        error_log("Request failure with code $http_code ($response)");
        return false;
    }
    else {
        return $response;
    }
}

/**
 * Performs a cURL request to a Telegram API and returns the parsed results.
 *
 * @param object Handle to cURL request.
 * @return object | false Parsed response object or false on failure.
 */
function perform_telegram_request($handle) {
    $response = perform_curl_request($handle);
    if($response === false) {
        return false;
    }

    // Everything fine, return the result as object
    $response = json_decode($response, true);
    return $response['result'];
}

/**
 * Prepares an API request using cURL.
 * Returns a cURL handle, ready to perform the request, or false on failure.
 *
 * @param string $url HTTP request URI.
 * @param string $method HTTP method ('GET' or 'POST').
 * @param array $parameters Query string parameters.
 * @param mixed $body String or array of values to be passed as request payload.
 * @return object | false cURL handle or false on failure.
 */
function prepare_curl_api_request($url, $method, $parameters = null, $body = null, $headers = null) {
    // Parameter checking
    if(!is_string($url)) {
        error_log('URL must be a string');
        return false;
    }
    if($method !== 'GET' && $method !== 'POST') {
        error_log('Method must be either GET or POST');
        return false;
    }
    if($method !== 'POST' && $body) {
        error_log('Cannot send request body content without POST method');
        return false;
    }
    if(!$parameters) {
        $parameters = array();
    }
    if(!is_array($parameters)) {
        error_log('Parameters must be an array of values');
        return false;
    }

    // Non-simple parameters (i.e., arrays) are encoded as JSON strings
    foreach ($parameters as $key => &$val) {
        if (!is_numeric($val) && !is_string($val)) {
            $val = json_encode($val);
        }
    }

    // Prepare final request URL
    $query_string = http_build_query($parameters);
    if(!empty($query_string)) {
        $url .= '?' . $query_string;
    }

    echo $url . PHP_EOL;

    // Prepare cURL handle
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_USERAGENT, 'Telegram Bot client, UWiClab (https://github.com/UWiClab/TelegramBotSample)');
    if($method === 'POST') {
        curl_setopt($handle, CURLOPT_POST, true);
        if($body) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }
    }
    if(is_array($headers)) {
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
    }

    return $handle;
}

/**
 * Retrieves information about the bot.
 * https://core.telegram.org/bots/api#getme
 *
 * @return object | false Parsed JSON object returned by the API or false on failure.
 */
function telegram_get_bot_info() {
    $handle = prepare_curl_api_request(TELEGRAM_API_URI_ME, 'GET', null, null);
    if($handle === false) {
        error_log('Failed to prepare cURL handle');
        return false;
    }

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

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_MESSAGE, 'POST', $parameters, null);
    if($handle === false) {
        error_log('Failed to prepare cURL handle');
        return false;
    }

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
        error_log('Latitude and longitude must be numbers');
        return false;
    }

    $parameters = prepare_parameters($parameters, array(
        'chat_id' => $chat_id,
        'latitude' => $latitude,
        'longitude' => $longitude
    ));

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_LOCATION, 'POST', $parameters, null);
    if($handle === false) {
        error_log('Failed to prepare cURL handle');
        return false;
    }

    return perform_telegram_request($handle);
}

/**
 * Sends a Telegram bot photo message.
 * https://core.telegram.org/bots/api#sendphoto
 *
 * @param int $chat_id Identifier of the Telegram chat session.
 * @param string $photo_path Path to the photo to attach.
 * @param array $parameters Additional parameters that match the API request.
 * @return object | false Parsed JSON object returned by the API or false on failure.
 */
function telegram_send_photo($chat_id, $photo_path, $caption, $parameters = null) {
    if(!$photo_path) {
        error_log('Path to attached photo must be set');
        return false;
    }
    if(!file_exists($photo_path)) {
        error_log("Photo at path $photo_path does not exist");
        return false;
    }

    $parameters = prepare_parameters($parameters, array(
        'chat_id' => $chat_id,
        'caption' => $caption
    ));

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_PHOTO, 'POST', $parameters, array(
        'photo' => new CURLFile($photo_path)
    ));
    if($handle === false) {
        error_log('Failed to prepare cURL handle');
        return false;
    }

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

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_UPDATES, 'GET', $parameters, null);
    if($handle === false) {
        error_log('Failed to prepare cURL handle');
        return false;
    }

    return perform_telegram_request($handle);
}
?>
