<?php

/**
 * Plugin Name:      Crypto Price Ticker
 * Description:      Displays a cryptocurrency price ticker that updates with the Interactivity API.
 * Version:          1.0.0
 * Author:           Kirill G.
 * License:          GPL-2.0-or-later
 * License URI:      https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:      crypto-price-ticker
 */

defined('ABSPATH') || exit;

// Define the cryptocurrency ID to display
define('CPT_CRYPTO_COIN_ID', 'bitcoin'); // Change to the desired coin ID: 'bitcoin', 'ethereum', 'arbitrum', etc.

/**
 * Fetches cryptocurrency price data, utilizing transient caching
 *
 * @param string $coin_id The ID of the cryptocurrency
 * @return array|WP_Error array of cryptocurrency data on success, WP_Error on failure
 */
function cpt_get_crypto_price_data($coin_id) {

    $transient_key = 'cpt_crypto_price_' . sanitize_key($coin_id);
    $cached_data = get_transient($transient_key);

    // Check if cached data exists and is not expired
    if (false !== $cached_data) {
        error_log('Fetching data from cache for: ' . $coin_id);
        return $cached_data;
    }

    // Call API
    error_log('Fetching data from API for: ' . $coin_id);
    $api_url = 'http://localhost:3000/price/' . sanitize_title($coin_id);
    $response = wp_remote_get($api_url);

    // Handle WP_Error
    if (is_wp_error($response)) {
        error_log('WP_Error fetching data for ' . $coin_id . ': ' . $response->get_error_message());
        return $response; // Return the WP_Error object
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Handle JSON decode errors or invalid data format
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data) || !isset($data['name'], $data['symbol'], $data['price'])) {
        error_log('Invalid data format or JSON error for ' . $coin_id . '. Body: ' . $body);
        return new WP_Error('cpt_invalid_data', 'Invalid data');
    }

    // Format the data
    $formatted_data = [
        'name'   => sanitize_text_field($data['name']),
        'symbol' => sanitize_text_field($data['symbol']),
        'price'  => number_format((float) $data['price'], 2),
        'error'  => false,
    ];

    // Store data in transient for 60 seconds
    set_transient($transient_key, $formatted_data, 60);
    error_log('Data cached for: ' . $coin_id);

    return $formatted_data;
}


/**
 * Builds and returns the price ticker HTML
 *
 * @return string HTML output of the ticker
 */
function crypto_price_ticker_output() {

    // Initial data load
    $coin_id = defined('CPT_CRYPTO_COIN_ID') ? CPT_CRYPTO_COIN_ID : 'bitcoin'; // Use constant or default
    $initial_data = cpt_get_crypto_price_data($coin_id);

    // Handle potential WP_Error from the data fetcher
    if (is_wp_error($initial_data)) {
        $initial_data = [
            'name'          => sanitize_text_field($coin_id) . ': Error',
            'symbol'        => 'ERR',
            'price'         => 'N/A',
            'error'         => true,
            'error_message' => $initial_data->get_error_message(), // could be used in template
        ];
    }

    // Adding JS for Interactivity API: enqueue with proper dependencies
    wp_enqueue_script_module(
        'crypto-price-ticker',
        plugin_dir_url(__FILE__) . 'assets/index.js',
        ['wp-interactivity'],
        filemtime(plugin_dir_path(__FILE__) . 'assets/index.js')
    );

    // Adding basic CSS
    wp_enqueue_style(
        'crypto-price-ticker-style',
        plugin_dir_url(__FILE__) . 'assets/style.css',
        [],
        '1.0.0'
    );

    // Add initial data for SSR and pass coin ID to JS
    $context_data = [
        'cryptoPriceTicker' => $initial_data,
        'coinId' => $coin_id, // Pass the coin ID to the JS context
        'ajaxUrl' => admin_url('admin-ajax.php?action=get_crypto_price'), // Pass the AJAX URL to the JS context
    ];

    // Render with template and OB
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/ticker-view.php';
    return ob_get_clean();
}


/**
 * Wrapper function around crypto_price_ticker_output() that echoes the output directly
 */
function crypto_price_ticker_output_echo() {
    echo crypto_price_ticker_output();
}


/**
 * AJAX handler to fetch updated price data
 */
function cpt_ajax_get_crypto_price() {

    // Check for coin ID in the AJAX request
    $coin_id = isset($_POST['coinId']) ? sanitize_text_field($_POST['coinId']) : '';

    if (empty($coin_id)) {
        wp_send_json_error('Coin ID is missing.');
    }

    $data = cpt_get_crypto_price_data($coin_id);

    if (is_wp_error($data)) {
        wp_send_json_error($data->get_error_message());
    } else {
        wp_send_json_success($data);
    }

    wp_die();
}


// By default add it to theme wp_footer hook
add_action('wp_footer', 'crypto_price_ticker_output_echo');

// Add this as a shortcode (optional)
add_shortcode('crypto_price_ticker', 'crypto_price_ticker_output');

// Hook the AJAX handler for logged-in and logged-out users
add_action('wp_ajax_get_crypto_price', 'cpt_ajax_get_crypto_price');
add_action('wp_ajax_nopriv_get_crypto_price', 'cpt_ajax_get_crypto_price');
