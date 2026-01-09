<?php
if (!defined('ABSPATH')) exit;

class AcervoX_Deactivator {

    public static function deactivate() {

        // Não apagar dados!
        flush_rewrite_rules();
    }
}
