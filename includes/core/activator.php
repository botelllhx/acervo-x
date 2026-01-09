<?php
if (!defined('ABSPATH')) exit;

class AcervoX_Activator {

    public static function activate() {

        // Flush rewrite rules (CPTs)
        flush_rewrite_rules();

        // Versão do plugin
        add_option('acervox_version', '0.1.0');

        // Flag inicial
        add_option('acervox_installed', time());
    }
}
