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

    public static function debug($message, $tag = '') {
        self::common(self::SEVERITY_DEBUG, $message, $tag);
    }

    public static function info($message, $tag = '') {
        self::common(self::SEVERITY_INFO, $message, $tag);
    }

    public static function warning($message, $tag = '') {
        self::common(self::SEVERITY_WARNING, $message, $tag);
    }

    public static function error($message, $tag = '') {
        self::common(self::SEVERITY_ERROR, $message, $tag);
    }

    public static function fatal($message, $tag = '') {
        self::error($message, $tag);

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

    private static function common($level, $message, $tag = '') {
        if(is_cli()) {
            // In CLI mode, output all logs to stderr
            fwrite(STDERR, self::severity_to_char($level) . '/' . $message . PHP_EOL);
        }
        else if($level >= self::SEVERITY_WARNING) {
            // Otherwise, write warning and errors to the system log
            error_log(self::severity_to_char($level) . ':' . $tag . ':' . $message);
        }
    }

}
