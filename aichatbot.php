<?php
/**
 * Plugin Name: AI Chatbot
 * Plugin URI: https://nexovious.com/ai-chatbot
 * Description: AI-powered chatbot with WooCommerce integration for WordPress
 * Version: 1.0.0
 * Author: Nexovious
 * License: GPL v2 or later
 * Text Domain: aichatbot
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AICHATBOT_VERSION', '1.0.0');
define('AICHATBOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AICHATBOT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Main plugin class
final class AIChatbot {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('AIChatbot', 'uninstall'));
        add_filter('pre_set_site_transient_update_plugins', array($this, 'disable_plugin_updates'));
    }
    
    public function init() {
        $this->load_textdomain();
        $this->includes();
        $this->init_classes();
        $this->check_database_upgrade();
    }
    
    private function check_database_upgrade() {
        // Only check if we're in admin or during AJAX requests
        if (!is_admin() && !wp_doing_ajax()) {
            return;
        }
        
        // Check if database needs upgrade
        if (class_exists('AIChatbot_Database')) {
            AIChatbot_Database::check_and_upgrade_table();
        }
    }
    
    private function load_textdomain() {
        load_plugin_textdomain('aichatbot', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
            private function includes() {
            $files = array(
                'includes/class-database.php',
                'includes/class-openai.php',
                'includes/class-woocommerce.php',
                'includes/class-model-info.php',
                'includes/class-tools.php',
                'admin/class-admin.php',
                'public/class-public.php'
            );
        
        foreach ($files as $file) {
            $file_path = AICHATBOT_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    private function init_classes() {
        if (class_exists('AIChatbot_Admin')) {
            new AIChatbot_Admin();
        }
        if (class_exists('AIChatbot_Public')) {
            new AIChatbot_Public();
        }
    }
    
    public function activate() {
        if (class_exists('AIChatbot_Database')) {
            AIChatbot_Database::create_tables();
        }
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Clear any cached data
        wp_cache_flush();
        flush_rewrite_rules();
    }
    
    public static function uninstall() {
        // Remove database table
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}aichatbot_messages");
        
        // Remove plugin options
        delete_option('aichatbot_openai_key');
        delete_option('aichatbot_openai_model');
        delete_option('aichatbot_woocommerce_enabled');
        
        // Clear any cached data
        wp_cache_flush();
    }
    
    public function disable_plugin_updates($value) {
        $plugin_file = plugin_basename(__FILE__);
        if (isset($value->response[$plugin_file])) {
            unset($value->response[$plugin_file]);
        }
        return $value;
    }
}

// Initialize the plugin
AIChatbot::instance();