<?php
/**
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Support library. Don't change a thing here.
 */

/**
 * Checks whether a text string starts with another.
 * Performs a case-insensitive check.
 * @param $text String to search in.
 * @param $substring String to search for.
 * @return bool True if $text starts with $substring.
 */
function starts_with($text = '', $substring = '') {
    return (strpos(mb_strtolower($text), $substring) === 0);
}

/**
 * Extracts the command payload from a string.
 * @param $text String to search in.
 * @param $command Command string to remove.
 * @return string Command payload, if any, or empty string.
 */
function extract_command_payload($text = '', $command = '') {
    if(strlen($command) >= strlen($text)) {
        return '';
    }

    return substr($text, strlen($command) + 1);
}

?>
