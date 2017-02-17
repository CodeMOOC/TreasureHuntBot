<?php
/**
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Support library. Don't change a thing here.
 */

class Logger {

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
        if(is_cli()) {
            // In CLI mode, output all logs to stderr
            fwrite(STDERR, self::severity_to_char($level) . '/' . $message . PHP_EOL);
        }
        else {
            $base_tag = basename($tag, '.php');

            if($level >= self::SEVERITY_WARNING) {
                // Write warning and errors to the system log
                error_log(self::severity_to_char($level) . ':' . $base_tag . ':' . $message);
            }

            // Log to DB if needed
            if(DEBUG_TO_DB || $level > self::SEVERITY_DEBUG) {
                $identity = ($context != null && $context->get_user_id() != null) ? $context->get_user_id() : 'NULL';
                $game_id = ($context != null && $context->get_game_id() != null) ? $context->get_game_id() : 'NULL';

                db_perform_action("INSERT INTO `log` (`log_id`, `severity`, `tag`, `message`, `timestamp`, `identity_id`, `game_id`) VALUES(DEFAULT, {$level}, '" . db_escape($base_tag) . "', '" . db_escape($message) . "', NOW(), {$identity}, {$game_id})");
            }
        }
    }

}
