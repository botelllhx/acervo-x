<?php
/**
 * Plugin Name: AcervoX
 * Description: Framework moderno de gestão de acervos digitais
 * Version: 0.1.0
 * Author: Mateus Botelho
 */

if (!defined('ABSPATH')) exit;

// Constantes do plugin
define('ACERVOX_PATH', plugin_dir_path(__FILE__));
define('ACERVOX_URL', plugin_dir_url(__FILE__));

// Carregar classes de ativação/desativação antes de registrar hooks
require_once ACERVOX_PATH . 'includes/core/activator.php';
require_once ACERVOX_PATH . 'includes/core/deactivator.php';

// Hooks de ativação / desativação
register_activation_hook(__FILE__, ['AcervoX_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['AcervoX_Deactivator', 'deactivate']);

// Loader
require_once ACERVOX_PATH . 'includes/core/loader.php';

// Inicialização segura do plugin
add_action('plugins_loaded', ['AcervoX_Loader', 'init']);
