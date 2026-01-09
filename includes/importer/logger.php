<?php
if (!defined('ABSPATH')) exit;

class AcervoX_Import_Logger {

    private static $log = [];

    public static function add($message) {
        self::$log[] = $message;
    }

    public static function get() {
        return self::$log;
    }
}
