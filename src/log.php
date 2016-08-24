<?php
/**
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Logging library.
 */

require_once('lib.php');
require_once('model/context.php');

// Register teardown upfront
register_shutdown_function('Logger::logger_teardown');

class Logger {

    const SEVERITY_DEBUG = 1;
    const SEVERITY_INFO = 64;
    const SEVERITY_WARNING = 128;
    const SEVERITY_ERROR = 255;

    private static $max_level = self::SEVERITY_DEBUG;
    private static $messages = array();
    private static $last_user_id = null;
    private static $last_group_id = null;

    public static function debug($message, $tag = '', $context = null) {
        self::common(self::SEVERITY_DEBUG, $message, $tag, $context);
    }

    public static function info($message, $tag = '', $context = null, $transmit = false) {
        self::common(self::SEVERITY_INFO, $message, $tag, $context);

        if($transmit) {
            self::notification($message);
        }
    }

    public static function warning($message, $tag = '', $context = null) {
        // Forward to default error logging (system log by default)
        error_log('Warning: ' . $message);

        self::common(self::SEVERITY_WARNING, $message, $tag, $context);
    }

    public static function error($message, $tag = '', $context = null) {
        // Forward to default error logging (system log by default)
        error_log('Error: ' . $message);

        self::common(self::SEVERITY_ERROR, $message, $tag, $context);
    }

    public static function fatal($message, $tag = '', $context = null) {
        self::error($message, $tag, $context);

        die();
    }

    private static function severity_to_char($level) {
        switch($level) {
            case self::SEVERITY_DEBUG:
            default:
                return 'D';

            case self::SEVERITY_INFO:
                return 'I';

            case self::SEVERITY_WARNING:
                return 'W';

            case self::SEVERITY_ERROR:
                return 'E';
        }
    }

    private static function common($level, $message, $tag = '', $context = null) {
        echo '(' . self::severity_to_char($level) . ') ' . $message . PHP_EOL;

        if(DEBUG_TO_BOT || $level > self::SEVERITY_DEBUG) {
            self::$max_level = max(self::$max_level, $level);
            self::$messages[] = $message;
        }

        if($context !== null) {
            $group_id = $context->get_group_id();
            $from_id = $context->get_user_id();

            self::$last_group_id = $group_id;
            self::$last_user_id = $from_id;
        }
        else {
            $group_id = 'NULL';
            $from_id = 'NULL';
        }

        // Write to database, if severity high enough or debug logging enabled
        if(DEBUG_TO_DB || $level > self::SEVERITY_DEBUG) {
            db_perform_action("INSERT INTO `log` VALUES(NOW(), '" . basename(db_escape($tag), '.php') . "', '" . db_escape($message) . "', {$level}, {$group_id}, {$from_id})");
        }
    }

    /**
     * Sends out pending error (and warning) messages and resets the queue.
     */
    public static function notify() {
        if(self::$messages && sizeof(self::$messages) > 0 && self::$max_level >= self::SEVERITY_WARNING) {
            $report = (self::$max_level === self::SEVERITY_ERROR) ? '🚨 *Error report*' : '⚠️ *Warning report*';
            foreach(self::$messages as $m) {
                $report .= "\n· " . escape_markdown($m);
            }
            if(self::$last_group_id && self::$last_user_id) {
                $report .= "\n_Group #" . self::$last_group_id . " User #" . self::$last_user_id . "_";
            }

            telegram_send_message(CHAT_GROUP_DEBUG, $report, array(
                'parse_mode' => 'Markdown'
            ));
        }

        Logger::$messages = array();
    }

    /**
     * Sends an immediate informative notification.
     * Use sparingly.
     */
    public static function notification($message) {
        telegram_send_message(CHAT_GROUP_DEBUG, 'ℹ️ ' . $message);
    }

    /**
    * Helper function that is called on PHP teardown.
    * Sends pending messages.
    */
    public static function logger_teardown() {
        Logger::notify();
    }

}
