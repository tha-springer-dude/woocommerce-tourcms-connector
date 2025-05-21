<?php
/**
 * Plugin Name: Tamino TourCMS Connector
 * Plugin URI: https://tamino.travel
 * Description: Integrates WooCommerce orders with TourCMS. After checkout, it sends booking data to TourCMS, retrieves QR codes, and appends them to customer communication.
 * Version: 1.0.0
 * Author: Tamino Travel Dev Team
 * Author URI: https://tamino.travel
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tamino-tourcms-sync
 * Domain Path: /languages
 */

// Silence is golden. Implementation starts here.
function tamino_load_env($file_path) {
    if (!file_exists($file_path)) return;

    $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue; // Skip comments
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }
    }
}

// Load env file
tamino_load_env(__DIR__ . '/.env');

//add_action('admin_menu', 'tamino_add_env_admin_menu');

function tamino_add_env_admin_menu() {
    add_menu_page(
        'Tamino TourCMS',                        // Page title
        'TourCMS Connector',                     // Menu label
        'manage_options',                        // Capability
        'tamino-tourcms',                        // Slug
        'tamino_render_env_admin_page',          // Callback
        'dashicons-admin-generic',               // Icon
        56                                       // Position
    );
}

function tamino_render_env_admin_page() {
    echo '<div class="wrap"><h1>Tamino TourCMS Plugin</h1>';
    echo '<h2>Loaded .env Variables</h2><ul>';

    $env_keys = ['TOURCMS_API_KEY', 'TOURCMS_MARKETPLACE_ID', 'TOURCMS_CHANNEL_ID', 'TOURCMS_TEST_MODE'];

    foreach ($env_keys as $key) {
        $value = isset($_ENV[$key]) ? esc_html($_ENV[$key]) : '<em>Not set</em>';
        echo "<li><strong>$key:</strong> $value</li>";
    }

    echo '</ul></div>';
}


