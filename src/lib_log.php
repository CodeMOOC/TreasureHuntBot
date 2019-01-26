<?php
/**
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Support library. Don't change a thing here.
 */

require_once(dirname(__FILE__) . '/lib_database.php');
require_once(dirname(__FILE__) . '/lib_telegram.php');
require_once(dirname(__FILE__) . '/lib_utility.php');

class Logger {

    private static $is_suspended = false;

    /**
     * Suspend logging to database and bot.
     * Can be used temporarily when warnings and errors are expected.
     */
    public static function suspend($suspend = false) {
        self::$is_suspended = $suspend;
    }

    const SEVERITY_DEBUG = 1;
    const SEVERITY_INFO = 64;
    const SEVERITY_WARNING = 128;
    const SEVERITY_ERROR = 255;

    public static function debug($message, $tag = '', $context = null) {
        self::common(self::SEVERITY_DEBUG, $message, $tag, $context);
    }

    public static function info($message, $tag = '', $context = null) {
        self::common(self::SEVERITY_INFO, $message, $tag, $context);
    }

    public static function warning($message, $tag = '', $context = null) {
        self::common(self::SEVERITY_WARNING, $message, $tag, $context);
    }

    public static function error($message, $tag = '', $context = null) {
        self::common(self::SEVERITY_ERROR, $message, $tag, $context);
    }

    public static function fatal($message, $tag = '', $context = null) {
        self::error($message, $tag, $context);

        die();
    }

    private static function severity_to_char($level) {
        if($level >= self::SEVERITY_ERROR)
            return 'E';
        else if($level >= self::SEVERITY_WARNING)
            return 'W';
        else if($level >= self::SEVERITY_INFO)
            return 'I';
        else
            return 'D';
    }

    private static function common($level, $message, $tag = '', $context = null) {
        $base_tag = basename($tag, '.php');

        if(is_cli()) {
            // In CLI mode, output all logs to stderr
            fwrite(STDERR, self::severity_to_char($level) . '/' . $message . PHP_EOL);
        }
        else if(!self::$is_suspended) {
            if($level >= self::SEVERITY_WARNING) {
                // Write warning and errors to the system log
                error_log(self::severity_to_char($level) . ':' . $base_tag . ':' . $message);
            }

            // Log to DB if needed
            if(DEBUG_TO_DB || $level > self::SEVERITY_DEBUG) {
                $identity = ($context != null && $context->get_internal_id() != null) ? $context->get_internal_id() : 'NULL';
                $game_id = ($context != null && $context->game != null && $context->game->game_id) ? $context->game->game_id : 'NULL';

                db_perform_action("INSERT INTO `log` (`log_id`, `severity`, `tag`, `message`, `timestamp`, `identity_id`, `game_id`) VALUES(DEFAULT, {$level}, '" . db_escape($base_tag) . "', '" . db_escape($message) . "', NOW(), {$identity}, {$game_id})");
            }

            // Log to debug chat if enabled
            if(CHAT_GROUP_DEBUG && $level >= self::SEVERITY_WARNING) {
                telegram_send_message(
                    CHAT_GROUP_DEBUG,
                    sprintf(
                        "%s <b>%s</b>\n%s\nModule: <code>%s</code>\nGame ID: %s",
                        ($level === self::SEVERITY_ERROR) ? '🚨' : '⚠️',
                        ($level === self::SEVERITY_ERROR) ? 'Error' : 'Warning',
                        $message,
                        $base_tag,
                        ($context != null && $context->game != null) ? $context->game->game_id : 'none'
                    ),
                    array(
                        'parse_mode' => 'HTML'
                    )
                );
            }
        }
    }

}

function logger_fatal_handler() {
    $error = error_get_last();

    if($error != null && $error['type'] === E_ERROR) {
        // This is a fatal error, whoopsie
        Logger::suspend(false);
        Logger::error(sprintf(
            '%s (line %d)',
            $error['message'],
            $error['line']
        ), $error['file']);
    }
}

// Register final "fatal error" handler
register_shutdown_function("logger_fatal_handler");
