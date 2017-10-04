<?php
/**
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Code generation library.
 */

require_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/lib_database.php');

const CODE_PADDING_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
const CODE_PADDING_MAX_LENGTH = 18; // leaves space for duplicate numbering

/**
 * Generate a new unique code.
 * @param $type String identifying the kind of lookup code.
 * @return The unique code inserted.
 */
function code_lookup_generate($context, $type, $event_id, $game_id, $location_id) {
    $code = '';

    switch($type) {
        case 'creation':
            $code .= "create-{$event_id}";
            break;

        case 'registration':
            $code .= "reg-{$event_id}-{$game_id}";
            break;

        case 'location':
            $code .= "{$game_id}-{$location_id}";
            break;

        case 'victory':
            Logger::error("Cannot generate victory lookup codes", __FILE__, $context);
            return null;

        default:
            Logger::error("Cannot generate lookup code for type '{$type}'", __FILE__, $context);
            return null;
    }

    $code .= '-';

    $code_padding_chars_length = mb_strlen(CODE_PADDING_CHARS) - 1;
    while(mb_strlen($code) < CODE_PADDING_MAX_LENGTH) {
        $code .= mb_substr(CODE_PADDING_CHARS, rand(0, $code_padding_chars_length), 1);
    }

    // Try shortcut with direct insertion
    if(db_perform_action(sprintf(
        'INSERT INTO `code_lookup` (`code`, `type`, `event_id`, `game_id`, `location_id`) VALUES(\'%s\', \'%s\', %s, %s, %s)',
        db_escape($code),
        $type,
        i2db($event_id),
        i2db($game_id),
        i2db($location_id)
    )) === 1) {
        Logger::debug("Generated lookup code '{$code}'", __FILE__, $context);

        return $code;
    }

    Logger::error("Failed to insert lookup code '{$code}' in DB", __FILE__, $context);

    return null;
}
