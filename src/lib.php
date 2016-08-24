<?php
/**
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Support library. Don't change a thing here.
 */

require_once('config.php');
require_once('log.php');
require_once('lib_bot.php');
require_once('lib_database.php');
require_once('lib_utility.php');

const STATE_NEW             = 0;  // newly registered
const STATE_REG_VERIFIED    = 1;  // verified as human user
const STATE_REG_NAME        = 2;  // name registered
const STATE_REG_CONFIRMED   = 10; // registration confirmed
const STATE_REG_NUMBER      = 12; // number of participants given
const STATE_REG_READY       = 20; // avatar given, ready to play
const STATE_GAME_LOCATION   = 30; // [puzzle solved], location assigned, waiting for qr code
const STATE_GAME_SELFIE     = 32; // location reached, qr code scanned, waiting for selfie
const STATE_GAME_PUZZLE     = 34; // selfie taken, puzzle assigned
const STATE_GAME_LAST_LOC   = 40; // last location assigned, waiting for qr code
const STATE_GAME_LAST_PUZ   = 50; // qr code scanned, last puzzle assigned
const STATE_GAME_WON        = 99; // final qrcode scanned, victory

const STATE_ALL             = array(
    STATE_NEW,
    STATE_REG_VERIFIED,
    STATE_REG_NAME,
    STATE_REG_CONFIRMED,
    STATE_REG_NUMBER,
    STATE_REG_READY,
    STATE_GAME_LOCATION,
    STATE_GAME_SELFIE,
    STATE_GAME_PUZZLE,
    STATE_GAME_LAST_LOC,
    STATE_GAME_LAST_PUZ,
    STATE_GAME_WON
);

const OP_LESSTHAN           = -2;
const OP_LESSTHANEQUALS     = -1;
const OP_EQUALS             =  0;
const OP_GREATERTHANEQUALS  =  1;
const OP_GREATERTHAN        =  2;

const PHOTO_AVATAR          = 100;
const PHOTO_SELFIE          = 101;

/**
 * Converts an integer comparison operator to a valid SQL
 * string operator.
 */
function op_to_sql($op) {
    $intop = intval($op);

    if($intop <= OP_LESSTHAN)
        return '<';
    else if($intop == OP_LESSTHANEQUALS)
        return '<=';
    else if($intop == OP_EQUALS)
        return '=';
    else if($intop == OP_GREATERTHANEQUALS)
        return '>=';
    else
        return '>';
}

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
        Logger::error("Curl returned error $errno: $error", __FILE__);

        curl_close($handle);

        return false;
    }

    $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));

    curl_close($handle);

    if ($http_code >= 500) {
        Logger::error('Internal server error', __FILE__);
        return false;
    }
    else if($http_code == 401) {
        Logger::error('Unauthorized request (check token)', __FILE__);
        return false;
    }
    else if ($http_code != 200) {
        Logger::error("Request failure with code $http_code ($response)", __FILE__);
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
        Logger::error('URL must be a string', __FILE__);
        return false;
    }
    if($method !== 'GET' && $method !== 'POST') {
        Logger::error('Method must be either GET or POST', __FILE__);
        return false;
    }
    if($method !== 'POST' && $body) {
        Logger::error('Cannot send request body content without POST method', __FILE__);
        return false;
    }
    if(!$parameters) {
        $parameters = array();
    }
    if(!is_array($parameters)) {
        Logger::error('Parameters must be an array of values', __FILE__);
        return false;
    }

    // Non-simple parameters (i.e., arrays) are encoded as JSON strings
    foreach ($parameters as $key => &$val) {
        if (!is_numeric($val) && !is_string($val)) {
            $val = json_encode($val);
        }
    }

    Logger::debug("HTTP request to {$url}");

    // Prepare final request URL
    $query_string = http_build_query($parameters);
    if(!empty($query_string)) {
        $url .= '?' . $query_string;
    }

    // Prepare cURL handle
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_USERAGENT, 'Treasure Hunt Bot');
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
        Logger::error('Failed to prepare cURL handle', __FILE__);
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
        Logger::error('Failed to prepare cURL handle', __FILE__);
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
        Logger::error('Latitude and longitude must be numbers', __FILE__);
        return false;
    }

    $parameters = prepare_parameters($parameters, array(
        'chat_id' => $chat_id,
        'latitude' => $latitude,
        'longitude' => $longitude
    ));

    $handle = prepare_curl_api_request(TELEGRAM_API_URI_LOCATION, 'POST', $parameters, null);
    if($handle === false) {
        Logger::error('Failed to prepare cURL handle', __FILE__);
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
        Logger::error('Path to attached photo must be set', __FILE__);
        return false;
    }
    if(!file_exists($photo_path)) {
        Logger::error("Photo at path $photo_path does not exist", __FILE__);
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
        Logger::error('Failed to prepare cURL handle', __FILE__);
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
        Logger::error('Failed to prepare cURL handle', __FILE__);
        return false;
    }

    return perform_telegram_request($handle);
}
?>
