<?php
/**
 * WooCommerce integration class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIChatbot_WooCommerce {
    
    public static function get_context($message) {
        error_log('AIChatbot: WooCommerce enabled: ' . (get_option('aichatbot_woocommerce_enabled') ? 'YES' : 'NO'));
        error_log('AIChatbot: WooCommerce class exists: ' . (class_exists('WooCommerce') ? 'YES' : 'NO'));
        
        if (!get_option('aichatbot_woocommerce_enabled') || !class_exists('WooCommerce')) {
            error_log('AIChatbot: WooCommerce integration disabled or WooCommerce not active');
            return '';
        }
        
        $context = "\nStore Information:\n";
        
        // Always include basic store info
        $context .= "Store Name: " . get_bloginfo('name') . "\n";
        $context .= "Store URL: " . get_site_url() . "\n";
        
        // Always include product information if WooCommerce is available
        if (function_exists('wc_get_products')) {
            $products = wc_get_products(array('limit' => 10, 'status' => 'publish'));
            
            if (!empty($products)) {
                $context .= "\nAvailable Products:\n";
                foreach ($products as $product) {
                    $price = $product->get_price() ? $product->get_price_html() : 'Price not set';
                    $stock_status = $product->is_in_stock() ? 'In Stock' : 'Out of Stock';
                    $context .= "- {$product->get_name()}: {$price} ({$stock_status})\n";
                    
                    // Add product description if available
                    $description = $product->get_short_description();
                    if (!empty($description)) {
                        $context .= "  Description: " . substr(strip_tags($description), 0, 100) . "...\n";
                    }
                }
                error_log('AIChatbot: Added ' . count($products) . ' products to context');
            } else {
                $context .= "No products found in the store.\n";
                error_log('AIChatbot: No products found');
            }
        } else {
            error_log('AIChatbot: wc_get_products function not available');
            $context .= "Product information temporarily unavailable.\n";
        }
        
        // Order queries for logged-in users
        if (is_user_logged_in() && preg_match('/\b(order|delivery|shipping|status|track|my|purchase|bought)\b/i', $message)) {
            error_log('AIChatbot: Order query detected for logged-in user');
            $orders = wc_get_orders(array('customer' => get_current_user_id(), 'limit' => 5));
            
            if (!empty($orders)) {
                $context .= "\nYour Recent Orders:\n";
                foreach ($orders as $order) {
                    $context .= "- Order #{$order->get_order_number()}: {$order->get_status()} (Total: {$order->get_total()})\n";
                }
                error_log('AIChatbot: Added ' . count($orders) . ' orders to context');
            } else {
                $context .= "\nYou have no recent orders.\n";
            }
        }
        
        error_log('AIChatbot: Final context length: ' . strlen($context));
        return $context;
    }
}