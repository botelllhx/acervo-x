<?php
if (!defined('ABSPATH')) exit;

class AcervoX_Admin {

  public function __construct() {
    add_action('admin_menu', [$this, 'menu']);
    add_action('admin_enqueue_scripts', [$this, 'assets']);
  }

  public function menu() {
    // Ícone SVG customizado para o AcervoX
    $icon_svg = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/><path d="M2 4h20v16H2z" fill="none" stroke="currentColor" stroke-width="2"/></svg>');
    
    add_menu_page(
      'AcervoX',
      'AcervoX',
      'manage_options',
      'acervox',
      [$this, 'render'],
      $icon_svg,
      25
    );
  }

  public function render() {
    echo '<div id="acervox-admin"></div>';
  }

  public function assets($hook) {
    if ($hook !== 'toplevel_page_acervox') return;

    wp_enqueue_style(
      'acervox-admin',
      ACERVOX_URL . 'admin/build/style.css',
      [],
      '0.1.0'
    );

    // Definir process antes de carregar o script React
    wp_register_script(
      'acervox-process-polyfill',
      '',
      [],
      '0.1.0',
      false
    );
    wp_add_inline_script('acervox-process-polyfill', 'if (typeof process === "undefined") { window.process = { env: { NODE_ENV: "production" } }; }');
    wp_enqueue_script('acervox-process-polyfill');

    wp_enqueue_script(
      'acervox-admin',
      ACERVOX_URL . 'admin/build/main.js',
      ['acervox-process-polyfill', 'wp-api-fetch'],
      '0.1.0',
      true
    );

    wp_localize_script('acervox-admin', 'AcervoX', [
      'api' => rest_url('acervox/v1'),
      'nonce' => wp_create_nonce('wp_rest'),
      'restUrl' => rest_url('wp/v2')
    ]);
  }
}
