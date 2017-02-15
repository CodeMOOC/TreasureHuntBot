<?php
/**
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Support library. Don't change a thing here.
 */

// Map storing private data of open requests
$curl_requests_private_data = array();

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

    // Complex parameters (i.e., arrays) are encoded as JSON strings
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

    Logger::info("HTTP request to {$url}", __FILE__);

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
 * Prepares a simple raw download request using the GET method.
 *
 * @param $url string HTTP request URI.
 * @param $output_path string Relative path to the output file.
 * @return object | false cURL handle or false on failure.
 */
function prepare_curl_download_request($url, $output_path) {
    global $curl_requests_private_data;

    // Parameter checking
    if(!is_string($url)) {
        Logger::error('URL must be a string', __FILE__);
        return false;
    }
    $file_handle = fopen(dirname(__FILE__) . '/' . $output_path, 'wb');
    if($file_handle === false) {
        Logger::error("Cannot write to path {$output_path}", __FILE__);
        return false;
    }

    Logger::info("HTTP download request to {$url}", __FILE__);

    // Prepare cURL handle
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_FILE, $file_handle);
    curl_setopt($handle, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($handle, CURLOPT_AUTOREFERER, true);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($handle, CURLOPT_MAXREDIRS, 1);
    curl_setopt($handle, CURLOPT_USERAGENT, 'Telegram Bot client, UWiClab (https://github.com/UWiClab/TelegramBotSample)');

    // Store private data
    $uuid = uniqid();
    curl_setopt($handle, CURLOPT_PRIVATE, $uuid);
    $curl_requests_private_data[$uuid] = array(
        'file_handle' => $file_handle
    );

    return $handle;
}

/**
 * Performs a cURL request and returns the expected response as string.
 *
 * @param object Handle to cURL request.
 * @return string | false Response as text or false on failure.
 */
function perform_curl_request($handle) {
    global $curl_requests_private_data;

    $response = curl_exec($handle);

    if ($response === false) {
        $errno = curl_errno($handle);
        $error = curl_error($handle);
        Logger::error("Curl returned error $errno: $error", __FILE__);

        curl_close($handle);

        return false;
    }

    $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));

    // Handle private data associated to the request
    $private_uuid = curl_getinfo($handle, CURLINFO_PRIVATE);
    if($private_uuid !== false) {
        $private_data = $curl_requests_private_data[$private_uuid];
        if($private_data !== null) {
            // Close file handle
            if($private_data['file_handle']) {
                fclose($private_data['file_handle']);
            }

            unset($curl_requests_private_data[$private_uuid]);
        }
    }

    curl_close($handle);

    if ($http_code >= 500) {
        Logger::warning('Internal server error', __FILE__);
        return false;
    }
    else if($http_code == 401) {
        Logger::warning('Unauthorized request (check token)', __FILE__);
        return false;
    }
    else if ($http_code != 200) {
        Logger::warning("Request failure with code $http_code ($response)", __FILE__);
        return false;
    }
    else {
        return $response;
    }
}
