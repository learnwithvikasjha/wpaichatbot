<?php
/**
 * Uninstall script for AI Chatbot plugin
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove database table
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}aichatbot_messages");

        // Remove plugin options
        delete_option('aichatbot_openai_key');
        delete_option('aichatbot_openai_model');
        delete_option('aichatbot_woocommerce_enabled');

// Clear any cached data
wp_cache_flush();