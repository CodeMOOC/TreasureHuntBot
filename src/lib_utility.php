<?php
/**
 * CodeMOOC TreasureHuntBot
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

/**
 * Extracts a cleaned-up response from the user.
 */
function extract_response($text) {
    return mb_strtolower(trim($text, ' /,.-!?;:\'"'));
}

/**
 * Unite two arrays, even if they are null.
 * Always returns a valid array.
 */
function unite_arrays($a, $b) {
    if(!$a || !is_array($a)) {
        $a = array();
    }

    if($b && is_array($b)) {
        $a = array_merge($a, $b);
    }

    return $a;
}

?>
