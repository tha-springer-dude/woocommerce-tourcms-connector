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

function tamino_load_env($file_path) {
    if (!file_exists($file_path)) return;
    $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }
    }
}

tamino_load_env(__DIR__ . '/.env');

require_once plugin_dir_path(__FILE__) . 'includes/TourCMS.php';

use TourCMS\Utils\TourCMS;

add_action('admin_menu', 'tamino_add_env_admin_menu');

function tamino_add_env_admin_menu() {
    add_menu_page(
        'Tamino TourCMS',
        'TourCMS Connector',
        'manage_options',
        'tamino-tourcms',
        'tamino_render_env_admin_page',
        'dashicons-admin-generic',
        56
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
    echo '</ul>';

    echo '<h2>Connection Test</h2>';
    echo "<p>" . tamino_tourcms_ping() . "</p>";

    echo '<form method="post"><p><input type="submit" name="tamino_test_booking" class="button button-primary" value="📘 Simulate Test Booking"></p></form>';

    if (isset($_POST['tamino_test_booking'])) {
        echo '<h2>Test Booking Response</h2><pre>';
        echo esc_html(tamino_test_booking());
        echo '</pre>';
    }

    if (isset($_POST['tamino_inspect_tour'])) {
        echo '<h2>Tour Inspection (show_tour)</h2><pre>';
        $tourcms = new TourCMS($_ENV['TOURCMS_MARKETPLACE_ID'], $_ENV['TOURCMS_API_KEY'], 'simplexml');
        $response = $tourcms->show_tour(118, 3930);
        print_r($response);
        echo '</pre>';
    }

    echo '<form method="post"><p><input type="submit" name="tamino_inspect_tour" class="button" value="🔍 Inspect Tour 118 (show_tour)"></p></form>';

    echo '<form method="post"><p><input type="submit" name="tamino_test_voucher" class="button" value="🎫 Show Voucher (Tour 218)"></p></form>';

if (isset($_POST['tamino_test_voucher'])) {
    echo '<h2>Voucher Info</h2><div style="padding: 10px; background: #f9f9f9; border: 1px solid #ccc;">';
    echo tamino_test_voucher();

}

echo '<form method="post"><p><input type="submit" name="tamino_test_voucher_full" class="button" value="📩 Test Booking + Voucher"></p></form>';

if (isset($_POST['tamino_test_voucher_full'])) {
    echo '<h2>Full Flow Test Result</h2><div style="padding: 10px; background: #f9f9f9; border: 1px solid #ccc;">';
    echo tamino_test_vienna_pass_full_flow();

}

echo '<form method="post"><p><input type="submit" name="tamino_test_voucher_debug" class="button" value="🐞 Debug Full Flow"></p></form>';
if (isset($_POST['tamino_test_voucher_debug'])) {
    echo '<h2>Debug Output</h2>';
    echo tamino_test_vienna_pass_debug_components();
}

    echo '</div>';
}

function tamino_tourcms_ping() {
    $marketplace_id = $_ENV['TOURCMS_MARKETPLACE_ID'] ?? null;
    $api_key = $_ENV['TOURCMS_API_KEY'] ?? null;
    $channel_id = $_ENV['TOURCMS_CHANNEL_ID'] ?? null;

    if (!$marketplace_id || !$api_key || !$channel_id) {
        return '❌ Missing TourCMS credentials.';
    }

    $tourcms = new TourCMS($marketplace_id, $api_key, 'simplexml');
    $response = $tourcms->show_channel($channel_id);

    if ((string)$response->error === "OK") {
        return "✅ TourCMS connected! Channel: " . $response->channel->channel_name;
    } else {
        return "❌ Error from TourCMS: " . $response->error;
    }
}

function tamino_test_booking() {
    $marketplace_id = $_ENV['TOURCMS_MARKETPLACE_ID'];
    $api_key = $_ENV['TOURCMS_API_KEY'];
    $channel_id = $_ENV['TOURCMS_CHANNEL_ID'];

    $tourcms = new TourCMS($marketplace_id, $api_key, 'simplexml');

    $tour_id = 118;
    $booking_date = '2025-06-01'; // adjust to valid date
    $rate_id = '428503'; // replace with your known valid numeric rate_id

    // Step 1: Get availability and fetch component_key
    $qs = "date=$booking_date&$rate_id=2";
    $availability = $tourcms->check_tour_availability($qs, $tour_id, $channel_id);

    if (!isset($availability->available_components->component[0]->component_key)) {
        return "❌ No available components found for this date.";
    }

    $component_key = (string)$availability->available_components->component[0]->component_key;

    // Step 2: Build booking XML with this component
    $booking = new SimpleXMLElement('<booking />');
    $booking->addChild('total_customers', '2');
    $booking->addChild('booking_key', 'demo_' . uniqid());

    $components = $booking->addChild('components');
    $component = $components->addChild('component');
    $component->addChild('component_key', $component_key);

    $customers = $booking->addChild('customers');

    $customer1 = $customers->addChild('customer');
    $customer1->addChild('title', 'Mr');
    $customer1->addChild('firstname', 'Test');
    $customer1->addChild('surname', 'Person');
    $customer1->addChild('email', 'test@example.com');

    $customer2 = $customers->addChild('customer');
    $customer2->addChild('title', 'Mrs');
    $customer2->addChild('firstname', 'Testina');
    $customer2->addChild('surname', 'Person');

    // Step 3: Send booking
    $response = $tourcms->start_new_booking($booking, $channel_id);

    // Step 4: Inspect response
    $xml = $response->asXML();
    $error = (string)$response->error;
    $error_detail = isset($response->error_detail) ? (string)$response->error_detail : 'No detail';
    $log_id = (string)$response->log_id;

    return <<<EOT
Error: $error
Detail: $error_detail
Log ID: $log_id

Full XML:
$xml
EOT;
}


function tamino_test_voucher() {
    $marketplace_id = $_ENV['TOURCMS_MARKETPLACE_ID'];
    $api_key = $_ENV['TOURCMS_API_KEY'];

    // Hardcoded Vienna Pass values
    $channel_id = 16339;
    $tour_id = 218;

    $tourcms = new TourCMS($marketplace_id, $api_key, 'simplexml');

    // 1. Show tour details
    $response = $tourcms->show_tour($tour_id, $channel_id);

    if (!isset($response->tour)) {
        return "❌ Failed to retrieve Vienna PASS details.";
    }

    $tour = $response->tour;
    $name = (string)$tour->tour_name;
    $desc = (string)$tour->shortdesc;
    $price = (string)$tour->from_price_display;
    $booking_url = (string)$tour->book_url;

    // 2. Simulate post-booking QR code retrieval (replace with real booking ID)
    $fake_booking_id = 'DEMO123456'; // Replace this with a real booking reference
    $booking_response = $tourcms->show_booking($fake_booking_id, $channel_id);

    $voucher_section = '';
    if (isset($booking_response->booking->voucher_url)) {
        $voucher_url = (string)$booking_response->booking->voucher_url;
        $voucher_section = "<br>🎟️ <a href=\"$voucher_url\" target=\"_blank\">View Voucher with QR Code</a>";
    } else {
        $voucher_section = "<br>⚠️ No voucher QR code available (booking not found or not confirmed).";
    }

    return <<<EOT
✅ Voucher Product: <strong>$name</strong><br>
💬 Description: $desc<br>
💶 Price: $price<br>
🔗 <a href="$booking_url" target="_blank">Buy Vienna PASS</a>
$voucher_section
EOT;
}

function tamino_test_vienna_pass_full_flow() {
    $marketplace_id = $_ENV['TOURCMS_MARKETPLACE_ID'];
    $api_key = $_ENV['TOURCMS_API_KEY'];
    $channel_id = 16339;
    $tour_id = 218;
    $booking_date = '2025-06-01';

    $tourcms = new TourCMS($marketplace_id, $api_key, 'simplexml');

    // Step 1: Get availability
    $qs = "date=$booking_date&r1=1";
    $availability = $tourcms->check_tour_availability($qs, $tour_id, $channel_id);
    $availability_xml = $availability->asXML();

    $component_key = null;
    foreach ($availability->available_components->component as $comp) {
        $key = (string)$comp->component_key;
        if (strpos($key, 'O1_day') !== false) {
            $component_key = $key;
            break;
        }
    }

    if (!$component_key) {
        return "<pre>❌ No component key with 'O1_day' found.\n\n$availability_xml</pre>";
    }

    // Step 2: Build booking
    $booking = new SimpleXMLElement('<booking />');
    $booking->addChild('total_customers', '1');
    $booking->addChild('booking_key', 'demo_' . uniqid());

    $components = $booking->addChild('components');
    $component = $components->addChild('component');
    $component->addChild('component_key', $component_key);
    $component->addChild('supplier_note', '1_day');

    $customers = $booking->addChild('customers');
    $customer = $customers->addChild('customer');
    $customer->addChild('title', 'Mr');
    $customer->addChild('firstname', 'Test');
    $customer->addChild('surname', 'User');
    $customer->addChild('email', 'test@example.com');

    // Step 3: Book
    $response = $tourcms->start_new_booking($booking, $channel_id);
    $booking_xml = $response->asXML();
    $booking_id = (string)$response->booking_id;
    $error = (string)$response->error;
    $error_detail = (string)($response->error_detail ?? 'No detail');

    if (!$booking_id) {
        return <<<EOT
<pre>
❌ Booking failed
Error: $error
Detail: $error_detail

🧾 Booking Request XML:
$booking_xml

📦 Availability XML:
$availability_xml
</pre>
EOT;
    }

    // Step 4: Get voucher
    $voucher_response = $tourcms->show_booking($booking_id, $channel_id);
    $voucher_url = (string)($voucher_response->booking->voucher_url ?? '');

    return <<<EOT
✅ Booking successful!<br>
📄 Booking ID: <strong>$booking_id</strong><br>
🎟️ <a href="$voucher_url" target="_blank">View Voucher with QR Code</a>
EOT;
}



function tamino_test_vienna_pass_debug_components() {
    $marketplace_id = $_ENV['TOURCMS_MARKETPLACE_ID'];
    $api_key = $_ENV['TOURCMS_API_KEY'];
    $channel_id = 16339;
    $tour_id = 218;
    $booking_date = '2025-06-01';

    $tourcms = new TourCMS($marketplace_id, $api_key, 'simplexml');
    $qs = "date=$booking_date&r1=1";
    $availability = $tourcms->check_tour_availability($qs, $tour_id, $channel_id);

    $log = "<h3>🔍 Available Component Keys:</h3><pre>";
    foreach ($availability->available_components->component as $comp) {
        $log .= htmlspecialchars((string)$comp->component_key) . "\n";
    }
    $log .= "</pre>";

    return $log;
}
