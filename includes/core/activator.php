<?php
if (!defined('ABSPATH')) exit;

class AcervoX_Activator {

    public static function activate() {

        // Flush rewrite rules (CPTs)
        flush_rewrite_rules();

        // Versão do plugin
        add_option('acervox_version', ACERVOX_VERSION);

        // Flag inicial
        add_option('acervox_installed', time());
        
        // Criar tabela de histórico de importações
        if (class_exists('AcervoX_Import_History')) {
            AcervoX_Import_History::init();
        }
    }
}
