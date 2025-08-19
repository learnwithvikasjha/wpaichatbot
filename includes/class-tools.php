<?php
/**
 * Tools/Functions class for OpenAI function calling
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIChatbot_Tools {
    
    /**
     * Get available functions for OpenAI
     */
    public static function get_available_functions() {
        $functions = array();
        
        // Only add WooCommerce functions if WooCommerce is enabled and active
        if (get_option('aichatbot_woocommerce_enabled') && class_exists('WooCommerce')) {
            // Existing WooCommerce functions
            $functions[] = array(
                'type' => 'function',
                'function' => array(
                    'name' => 'get_user_orders',
                    'description' => 'Get orders for the current logged-in user. Use this when users ask about their orders, order history, order status, or order count.',
                    'parameters' => array(
                        'type' => 'object',
                        'properties' => array(
                            'limit' => array(
                                'type' => 'integer',
                                'description' => 'Maximum number of orders to return (default: 10)',
                                'default' => 10
                            ),
                            'status' => array(
                                'type' => 'string',
                                'description' => 'Filter by order status (e.g., "processing", "completed", "cancelled")',
                                'enum' => array('processing', 'completed', 'cancelled', 'refunded', 'failed', 'on-hold')
                            )
                        ),
                        'required' => array()
                    )
                )
            );
            
            $functions[] = array(
                'type' => 'function',
                'function' => array(
                    'name' => 'get_products',
                    'description' => 'Get products from the store. Use this when users ask about products, prices, availability, or product information.',
                    'parameters' => array(
                        'type' => 'object',
                        'properties' => array(
                            'limit' => array(
                                'type' => 'integer',
                                'description' => 'Maximum number of products to return (default: 10)',
                                'default' => 10
                            ),
                            'search' => array(
                                'type' => 'string',
                                'description' => 'Search term to filter products by name or description'
                            ),
                            'category' => array(
                                'type' => 'string',
                                'description' => 'Filter by product category'
                            ),
                            'in_stock' => array(
                                'type' => 'boolean',
                                'description' => 'Filter to show only in-stock products'
                            )
                        ),
                        'required' => array()
                    )
                )
            );
            
            $functions[] = array(
                'type' => 'function',
                'function' => array(
                    'name' => 'get_store_info',
                    'description' => 'Get basic store information like store name, URL, and contact details.',
                    'parameters' => array(
                        'type' => 'object',
                        'properties' => new stdClass(),
                        'required' => array()
                    )
                )
            );
            
            // User Empowerment Tools
            $functions[] = array(
                'type' => 'function',
                'function' => array(
                    'name' => 'get_available_actions',
                    'description' => 'Get list of available actions and commands that users can perform. Use this when users ask "what can you do?" or "help" or want to know their options.',
                    'parameters' => array(
                        'type' => 'object',
                        'properties' => array(
                            'category' => array(
                                'type' => 'string',
                                'description' => 'Filter actions by category (orders, products, account, support)',
                                'enum' => array('orders', 'products', 'account', 'support', 'all')
                            )
                        ),
                        'required' => array()
                    )
                )
            );
            
            $functions[] = array(
                'type' => 'function',
                'function' => array(
                    'name' => 'get_conversation_summary',
                    'description' => 'Get a summary of the current conversation and what has been discussed. Use this when users ask for a summary or want to recap.',
                    'parameters' => array(
                        'type' => 'object',
                        'properties' => new stdClass(),
                        'required' => array()
                    )
                )
            );
            
            $functions[] = array(
                'type' => 'function',
                'function' => array(
                    'name' => 'get_quick_actions',
                    'description' => 'Get quick action buttons or shortcuts for common tasks. Use this to provide users with easy-to-click options.',
                    'parameters' => array(
                        'type' => 'object',
                        'properties' => array(
                            'context' => array(
                                'type' => 'string',
                                'description' => 'Context for quick actions (shopping, support, account, general)',
                                'enum' => array('shopping', 'support', 'account', 'general')
                            )
                        ),
                        'required' => array()
                    )
                )
            );
            
            $functions[] = array(
                'type' => 'function',
                'function' => array(
                    'name' => 'search_help_topics',
                    'description' => 'Search help topics and FAQs. Use this when users need help or have questions about how to do something.',
                    'parameters' => array(
                        'type' => 'object',
                        'properties' => array(
                            'query' => array(
                                'type' => 'string',
                                'description' => 'Search query for help topics'
                            ),
                            'category' => array(
                                'type' => 'string',
                                'description' => 'Filter help topics by category',
                                'enum' => array('orders', 'products', 'account', 'shipping', 'payment', 'returns')
                            )
                        ),
                        'required' => array('query')
                    )
                )
            );
            
            $functions[] = array(
                'type' => 'function',
                'function' => array(
                    'name' => 'get_user_preferences',
                    'description' => 'Get user preferences and settings. Use this to personalize responses based on user preferences.',
                    'parameters' => array(
                        'type' => 'object',
                        'properties' => new stdClass(),
                        'required' => array()
                    )
                )
            );
            
            $functions[] = array(
                'type' => 'function',
                'function' => array(
                    'name' => 'set_user_preference',
                    'description' => 'Set user preference or setting. Use this when users want to change their preferences.',
                    'parameters' => array(
                        'type' => 'object',
                        'properties' => array(
                            'preference' => array(
                                'type' => 'string',
                                'description' => 'Preference to set',
                                'enum' => array('language', 'currency', 'notifications', 'theme')
                            ),
                            'value' => array(
                                'type' => 'string',
                                'description' => 'Value for the preference'
                            )
                        ),
                        'required' => array('preference', 'value')
                    )
                )
            );
            
            $functions[] = array(
                'type' => 'function',
                'function' => array(
                    'name' => 'get_conversation_suggestions',
                    'description' => 'Get conversation suggestions based on user behavior and context. Use this to help guide the conversation.',
                    'parameters' => array(
                        'type' => 'object',
                        'properties' => array(
                            'context' => array(
                                'type' => 'string',
                                'description' => 'Current conversation context',
                                'enum' => array('shopping', 'support', 'browsing', 'checkout')
                            )
                        ),
                        'required' => array()
                    )
                )
            );
        }
        
        return $functions;
    }
    
    /**
     * Execute a function call
     */
    public static function execute_function($function_name, $arguments = array()) {
        error_log('AIChatbot: Executing function: ' . $function_name . ' with args: ' . json_encode($arguments));
        
        switch ($function_name) {
            case 'get_user_orders':
                return self::get_user_orders($arguments);
                
            case 'get_products':
                return self::get_products($arguments);
                
            case 'get_store_info':
                return self::get_store_info($arguments);
                
            // User Empowerment Tools
            case 'get_available_actions':
                return self::get_available_actions($arguments);
                
            case 'get_conversation_summary':
                return self::get_conversation_summary($arguments);
                
            case 'get_quick_actions':
                return self::get_quick_actions($arguments);
                
            case 'search_help_topics':
                return self::search_help_topics($arguments);
                
            case 'get_user_preferences':
                return self::get_user_preferences($arguments);
                
            case 'set_user_preference':
                return self::set_user_preference($arguments);
                
            case 'get_conversation_suggestions':
                return self::get_conversation_suggestions($arguments);
                
            default:
                error_log('AIChatbot: Unknown function: ' . $function_name);
                return array('error' => 'Unknown function: ' . $function_name);
        }
    }
    
    /**
     * Get user orders
     */
    private static function get_user_orders($args = array()) {
        if (!is_user_logged_in()) {
            return array(
                'error' => 'User not logged in',
                'message' => 'You need to be logged in to view your orders.'
            );
        }
        
        $limit = isset($args['limit']) ? intval($args['limit']) : 10;
        $status = isset($args['status']) ? $args['status'] : null;
        
        $query_args = array(
            'customer' => get_current_user_id(),
            'limit' => $limit
        );
        
        if ($status) {
            $query_args['status'] = $status;
        }
        
        $orders = wc_get_orders($query_args);
        
        if (empty($orders)) {
            return array(
                'orders' => array(),
                'count' => 0,
                'message' => 'No orders found.'
            );
        }
        
        $order_data = array();
        foreach ($orders as $order) {
            $order_data[] = array(
                'order_number' => $order->get_order_number(),
                'status' => $order->get_status(),
                'total' => $order->get_total(),
                'currency' => $order->get_currency(),
                'date_created' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'item_count' => $order->get_item_count(),
                'payment_method' => $order->get_payment_method_title()
            );
        }
        
        return array(
            'orders' => $order_data,
            'count' => count($order_data),
            'total_orders' => wc_get_customer_order_count(get_current_user_id())
        );
    }
    
    /**
     * Get products
     */
    private static function get_products($args = array()) {
        $limit = isset($args['limit']) ? intval($args['limit']) : 10;
        $search = isset($args['search']) ? $args['search'] : '';
        $category = isset($args['category']) ? $args['category'] : '';
        $in_stock = isset($args['in_stock']) ? $args['in_stock'] : null;
        
        $query_args = array(
            'limit' => $limit,
            'status' => 'publish'
        );
        
        if ($search) {
            $query_args['s'] = $search;
        }
        
        if ($category) {
            $query_args['category'] = array($category);
        }
        
        if ($in_stock !== null) {
            $query_args['stock_status'] = $in_stock ? 'instock' : 'outofstock';
        }
        
        $products = wc_get_products($query_args);
        
        if (empty($products)) {
            return array(
                'products' => array(),
                'count' => 0,
                'message' => 'No products found.'
            );
        }
        
        $product_data = array();
        foreach ($products as $product) {
            $product_data[] = array(
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'price_html' => $product->get_price_html(),
                'stock_status' => $product->is_in_stock() ? 'In Stock' : 'Out of Stock',
                'stock_quantity' => $product->get_stock_quantity(),
                'description' => $product->get_short_description(),
                'categories' => $product->get_categories(),
                'permalink' => $product->get_permalink()
            );
        }
        
        return array(
            'products' => $product_data,
            'count' => count($product_data)
        );
    }
    
    /**
     * Get store information
     */
    private static function get_store_info($args = array()) {
        return array(
            'store_name' => get_bloginfo('name'),
            'store_url' => get_site_url(),
            'store_description' => get_bloginfo('description'),
            'admin_email' => get_option('admin_email'),
            'currency' => get_woocommerce_currency(),
            'currency_symbol' => get_woocommerce_currency_symbol()
        );
    }
    
    /**
     * Get available actions for users
     */
    private static function get_available_actions($args = array()) {
        $category = isset($args['category']) ? $args['category'] : 'all';
        
        $actions = array(
            'orders' => array(
                'Check order status',
                'View order history',
                'Track package',
                'Request refund',
                'Cancel order',
                'Download invoice'
            ),
            'products' => array(
                'Search products',
                'Check product availability',
                'Compare products',
                'Read product reviews',
                'Get product recommendations',
                'Check prices'
            ),
            'account' => array(
                'Update profile',
                'Change password',
                'View account settings',
                'Manage addresses',
                'View loyalty points',
                'Download order history'
            ),
            'support' => array(
                'Create support ticket',
                'Search FAQs',
                'Contact customer service',
                'Schedule callback',
                'Report an issue',
                'Request assistance'
            )
        );
        
        if ($category === 'all') {
            return array(
                'actions' => $actions,
                'message' => 'Here are all the things I can help you with:'
            );
        } else {
            return array(
                'actions' => isset($actions[$category]) ? $actions[$category] : array(),
                'category' => $category,
                'message' => "Here are the {$category} actions I can help you with:"
            );
        }
    }
    
    /**
     * Get conversation summary
     */
    private static function get_conversation_summary($args = array()) {
        // Get recent conversation from database
        if (class_exists('AIChatbot_Database')) {
            $recent_messages = AIChatbot_Database::get_recent_messages(10);
            
            $summary = array(
                'total_messages' => count($recent_messages),
                'topics_discussed' => array(),
                'actions_taken' => array(),
                'pending_items' => array()
            );
            
            foreach ($recent_messages as $message) {
                // Analyze message content for topics
                $content = strtolower($message['message']);
                
                if (strpos($content, 'order') !== false) {
                    $summary['topics_discussed'][] = 'Orders';
                }
                if (strpos($content, 'product') !== false) {
                    $summary['topics_discussed'][] = 'Products';
                }
                if (strpos($content, 'help') !== false || strpos($content, 'support') !== false) {
                    $summary['topics_discussed'][] = 'Support';
                }
                if (strpos($content, 'account') !== false) {
                    $summary['topics_discussed'][] = 'Account';
                }
            }
            
            $summary['topics_discussed'] = array_unique($summary['topics_discussed']);
            
            return array(
                'summary' => $summary,
                'message' => 'Here\'s a summary of our conversation:'
            );
        }
        
        return array(
            'summary' => array('total_messages' => 0, 'topics_discussed' => array()),
            'message' => 'I don\'t have access to conversation history at the moment.'
        );
    }
    
    /**
     * Get quick actions for users
     */
    private static function get_quick_actions($args = array()) {
        $context = isset($args['context']) ? $args['context'] : 'general';
        
        $quick_actions = array(
            'shopping' => array(
                array('text' => '🔍 Search Products', 'action' => 'search_products'),
                array('text' => '🛒 View Cart', 'action' => 'view_cart'),
                array('text' => '⭐ My Wishlist', 'action' => 'view_wishlist'),
                array('text' => '💰 Check Deals', 'action' => 'view_deals')
            ),
            'support' => array(
                array('text' => '❓ FAQ', 'action' => 'view_faq'),
                array('text' => '📞 Contact Support', 'action' => 'contact_support'),
                array('text' => '📋 Create Ticket', 'action' => 'create_ticket'),
                array('text' => '📞 Schedule Call', 'action' => 'schedule_call')
            ),
            'account' => array(
                array('text' => '👤 My Profile', 'action' => 'view_profile'),
                array('text' => '📦 My Orders', 'action' => 'view_orders'),
                array('text' => '⚙️ Settings', 'action' => 'view_settings'),
                array('text' => '💳 Payment Methods', 'action' => 'view_payments')
            ),
            'general' => array(
                array('text' => '🏠 Home', 'action' => 'go_home'),
                array('text' => '📞 Help', 'action' => 'get_help'),
                array('text' => '🔍 Search', 'action' => 'search'),
                array('text' => '📱 Mobile App', 'action' => 'mobile_app')
            )
        );
        
        return array(
            'quick_actions' => isset($quick_actions[$context]) ? $quick_actions[$context] : $quick_actions['general'],
            'context' => $context,
            'message' => 'Here are some quick actions you can take:'
        );
    }
    
    /**
     * Search help topics
     */
    private static function search_help_topics($args = array()) {
        $query = isset($args['query']) ? $args['query'] : '';
        $category = isset($args['category']) ? $args['category'] : '';
        
        // Define help topics
        $help_topics = array(
            'orders' => array(
                'How to track my order?' => 'You can track your order by going to My Account > Orders and clicking on the order number.',
                'How to cancel an order?' => 'To cancel an order, contact our support team within 24 hours of placing the order.',
                'How to request a refund?' => 'You can request a refund by going to My Account > Orders and clicking "Request Refund".',
                'How long does shipping take?' => 'Standard shipping takes 3-5 business days. Express shipping takes 1-2 business days.'
            ),
            'products' => array(
                'How to search for products?' => 'Use the search bar at the top of the page or ask me to help you find specific products.',
                'How to check product availability?' => 'Product availability is shown on each product page. You can also ask me to check for you.',
                'How to read product reviews?' => 'Product reviews are displayed on each product page below the product description.',
                'How to compare products?' => 'You can compare products by adding them to your wishlist and using the compare feature.'
            ),
            'account' => array(
                'How to update my profile?' => 'Go to My Account > Profile to update your personal information.',
                'How to change my password?' => 'Go to My Account > Settings > Change Password to update your password.',
                'How to add a new address?' => 'Go to My Account > Addresses to add or edit your shipping addresses.',
                'How to manage my preferences?' => 'Go to My Account > Preferences to manage your notification and privacy settings.'
            ),
            'shipping' => array(
                'What are the shipping options?' => 'We offer standard shipping (3-5 days) and express shipping (1-2 days).',
                'How much does shipping cost?' => 'Shipping costs vary based on your location and the shipping method chosen.',
                'Do you ship internationally?' => 'Yes, we ship to most countries. International shipping takes 7-14 business days.',
                'How to track my package?' => 'You can track your package using the tracking number provided in your order confirmation email.'
            ),
            'payment' => array(
                'What payment methods do you accept?' => 'We accept credit cards, PayPal, and bank transfers.',
                'Is my payment information secure?' => 'Yes, we use industry-standard SSL encryption to protect your payment information.',
                'How to save a payment method?' => 'You can save payment methods in My Account > Payment Methods for faster checkout.',
                'How to update payment information?' => 'Go to My Account > Payment Methods to update or remove saved payment methods.'
            ),
            'returns' => array(
                'What is your return policy?' => 'We offer a 30-day return policy for most items. Some items may have different return terms.',
                'How to return an item?' => 'Go to My Account > Orders and click "Return Item" to initiate a return.',
                'How long do refunds take?' => 'Refunds are processed within 5-7 business days after we receive your return.',
                'Do you offer exchanges?' => 'Yes, you can exchange items for a different size or color within 30 days of purchase.'
            )
        );
        
        $results = array();
        
        if ($category && isset($help_topics[$category])) {
            foreach ($help_topics[$category] as $question => $answer) {
                if (stripos($question, $query) !== false || stripos($answer, $query) !== false) {
                    $results[] = array(
                        'question' => $question,
                        'answer' => $answer,
                        'category' => $category
                    );
                }
            }
        } else {
            foreach ($help_topics as $cat => $topics) {
                foreach ($topics as $question => $answer) {
                    if (stripos($question, $query) !== false || stripos($answer, $query) !== false) {
                        $results[] = array(
                            'question' => $question,
                            'answer' => $answer,
                            'category' => $cat
                        );
                    }
                }
            }
        }
        
        return array(
            'results' => $results,
            'query' => $query,
            'count' => count($results),
            'message' => count($results) > 0 ? 'Here are some helpful topics:' : 'No help topics found for your query.'
        );
    }
    
    /**
     * Get user preferences
     */
    private static function get_user_preferences($args = array()) {
        if (!is_user_logged_in()) {
            return array(
                'error' => 'User not logged in',
                'message' => 'You need to be logged in to view preferences.'
            );
        }
        
        $user_id = get_current_user_id();
        
        // Get user preferences from WordPress user meta
        $preferences = array(
            'language' => get_user_meta($user_id, 'aichatbot_language', true) ?: 'English',
            'currency' => get_user_meta($user_id, 'aichatbot_currency', true) ?: get_woocommerce_currency(),
            'notifications' => get_user_meta($user_id, 'aichatbot_notifications', true) ?: 'enabled',
            'theme' => get_user_meta($user_id, 'aichatbot_theme', true) ?: 'light',
            'chat_speed' => get_user_meta($user_id, 'aichatbot_chat_speed', true) ?: 'normal'
        );
        
        return array(
            'preferences' => $preferences,
            'message' => 'Here are your current preferences:'
        );
    }
    
    /**
     * Set user preference
     */
    private static function set_user_preference($args = array()) {
        if (!is_user_logged_in()) {
            return array(
                'error' => 'User not logged in',
                'message' => 'You need to be logged in to set preferences.'
            );
        }
        
        $preference = isset($args['preference']) ? $args['preference'] : '';
        $value = isset($args['value']) ? $args['value'] : '';
        
        if (empty($preference) || empty($value)) {
            return array(
                'error' => 'Invalid parameters',
                'message' => 'Please provide both preference and value.'
            );
        }
        
        $user_id = get_current_user_id();
        $meta_key = 'aichatbot_' . $preference;
        
        $result = update_user_meta($user_id, $meta_key, $value);
        
        if ($result) {
            return array(
                'success' => true,
                'preference' => $preference,
                'value' => $value,
                'message' => "Your {$preference} preference has been updated successfully."
            );
        } else {
            return array(
                'error' => 'Update failed',
                'message' => 'Failed to update your preference. Please try again.'
            );
        }
    }
    
    /**
     * Get conversation suggestions
     */
    private static function get_conversation_suggestions($args = array()) {
        $context = isset($args['context']) ? $args['context'] : 'general';
        
        $suggestions = array(
            'shopping' => array(
                'What products are on sale today?',
                'Can you recommend products based on my previous purchases?',
                'What\'s the best seller in [category]?',
                'Do you have any deals or promotions?',
                'Can you help me find a gift for [occasion]?'
            ),
            'support' => array(
                'I need help with my recent order',
                'How do I track my package?',
                'I want to return an item',
                'Can you help me with payment issues?',
                'I have a question about shipping'
            ),
            'browsing' => array(
                'Show me new arrivals',
                'What\'s trending right now?',
                'Can you help me discover new products?',
                'What are customers saying about [product]?',
                'Show me products similar to [product]'
            ),
            'checkout' => array(
                'I\'m having trouble with checkout',
                'What payment methods do you accept?',
                'Can you help me apply a coupon?',
                'I need to update my shipping address',
                'What are the shipping options?'
            ),
            'general' => array(
                'What can you help me with?',
                'Show me my recent orders',
                'Help me find what I\'m looking for',
                'What\'s new in the store?',
                'How can I get better deals?'
            )
        );
        
        return array(
            'suggestions' => isset($suggestions[$context]) ? $suggestions[$context] : $suggestions['general'],
            'context' => $context,
            'message' => 'Here are some things you might want to ask:'
        );
    }
}
