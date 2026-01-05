<?php
/**
 * Shared Security Guard for Admin Scripts
 * 
 * Provides centralized authentication, authorization, and CSRF protection
 * for all admin scripts in primetime-price-guide/admin/scripts/
 * 
 * Usage: require_once '_guard.php'; at the top of each admin script
 */

// Prevent direct access to this guard file
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    http_response_code(403);
    exit('Direct access not allowed');
}

// 1. Load WordPress Context - Walk up directories to find wp-load.php
if (!defined('ABSPATH')) {
    $wp_load_path = null;
    $current_dir = __DIR__;
    
    // Walk up maximum 10 levels to find WordPress root
    for ($i = 0; $i < 10; $i++) {
        $test_path = $current_dir . '/wp-load.php';
        if (file_exists($test_path)) {
            $wp_load_path = $test_path;
            define('ABSPATH', $current_dir . '/');
            break;
        }
        $current_dir = dirname($current_dir);
    }
    
    if (!$wp_load_path) {
        http_response_code(500);
        exit('WordPress installation not found. Cannot load wp-load.php');
    }
    
    require_once $wp_load_path;
}

// Helper function to detect AJAX requests
function ptp_is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Helper function to send JSON error response
function ptp_json_error($message, $code = 403) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(array(
        'success' => false,
        'error' => $message,
        'code' => $code
    ));
    exit;
}

// Helper function to send error response (JSON for AJAX, wp_die for regular)
function ptp_send_error($message, $code = 403) {
    if (ptp_is_ajax_request()) {
        ptp_json_error($message, $code);
    } else {
        wp_die($message, 'Access Denied', array('response' => $code));
    }
}

// 2. Authentication Check - Must be logged in
if (!is_user_logged_in()) {
    ptp_send_error('Access denied. Please log in to access this functionality.', 401);
}

// 3. Capability Check - Default: edit_posts, allow override per script
$required_capability = defined('PTP_REQUIRED_CAP') ? PTP_REQUIRED_CAP : 'edit_posts';

if (!current_user_can($required_capability)) {
    ptp_send_error('You do not have sufficient permissions to access this functionality.', 403);
}

// 4. CSRF Protection - Require nonce for ALL requests (GET + POST)
$nonce = isset($_REQUEST['ptp_nonce']) ? $_REQUEST['ptp_nonce'] : '';

if (!wp_verify_nonce($nonce, 'ptp_admin_scripts')) {
    ptp_send_error('Security check failed. Invalid or missing security token.', 403);
}

// 5. Security passed - Script can continue
// WordPress context is loaded, user is authenticated and authorized, nonce is valid
?>