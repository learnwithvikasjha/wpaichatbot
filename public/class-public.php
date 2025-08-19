<?php
/**
 * Public-facing functionality class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIChatbot_Public {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'chat_widget'));
        add_action('wp_ajax_aichatbot_send_message', array($this, 'handle_message'));
        add_action('wp_ajax_nopriv_aichatbot_send_message', array($this, 'handle_message'));
        add_action('wp_ajax_aichatbot_get_messages', array($this, 'get_messages'));
        add_action('wp_ajax_nopriv_aichatbot_get_messages', array($this, 'get_messages'));
        add_action('wp_ajax_aichatbot_test_ai', array($this, 'test_ai_response'));
        add_action('wp_ajax_nopriv_aichatbot_test_ai', array($this, 'test_ai_response'));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'aichatbot-js',
            AICHATBOT_PLUGIN_URL . 'assets/js/chat.js',
            array('jquery'),
            time(), // Force reload
            true
        );
        wp_enqueue_style(
            'aichatbot-css',
            AICHATBOT_PLUGIN_URL . 'assets/css/chat.css',
            array(),
            time() // Force reload
        );
        
        wp_localize_script('aichatbot-js', 'aichatbot_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aichatbot_nonce'),
            'plugin_url' => AICHATBOT_PLUGIN_URL
        ));
    }
    
    public function chat_widget() {
        ?>
        <!-- AI Chatbot Widget Start -->
        <div id="aichatbot-widget" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; background: red; padding: 10px; border: 2px solid black;">
            <div id="aichatbot-toggle" style="width: 60px; height: 60px; background: #007cba; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 24px; color: white;">💬</div>
            <div id="aichatbot-box" style="display:none; width: 300px; height: 400px; background: white; border: 1px solid #ddd; border-radius: 8px; position: absolute; bottom: 70px; right: 0;">
                <div id="aichatbot-header" style="background: #007cba; color: white; padding: 10px; border-radius: 8px 8px 0 0;">
                    <span><?php _e('Chat', 'aichatbot'); ?></span>
                    <span id="aichatbot-minimize" style="cursor: pointer;">−</span>
                </div>
                <div id="aichatbot-messages" style="height: 280px; overflow-y: auto; padding: 10px;"></div>
                <div id="aichatbot-input" style="padding: 10px;">
                    <input type="text" id="aichatbot-message-input" placeholder="<?php _e('Type message...', 'aichatbot'); ?>" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 5px; box-sizing: border-box;">
                    <?php if (!is_user_logged_in()) : ?>
                        <input type="text" id="aichatbot-user-name" placeholder="<?php _e('Your name (optional)', 'aichatbot'); ?>" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 5px; box-sizing: border-box;">
                    <?php endif; ?>
                    <button id="aichatbot-send-btn" style="width: 100%; padding: 8px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer;"><?php _e('Send', 'aichatbot'); ?></button>
                    <button id="aichatbot-test-btn" style="width: 100%; padding: 4px; background: #666; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 5px; font-size: 10px;">Test AI</button>
                </div>
            </div>
        </div>
        <!-- AI Chatbot Widget End -->
        <script>
        console.log('AIChatbot widget loaded');
        console.log('AJAX URL:', '<?php echo admin_url('admin-ajax.php'); ?>');
        console.log('Widget element:', document.getElementById('aichatbot-widget'));
        </script>
        <?php
    }
    
    /**
     * Handle incoming message
     */
    public function handle_message() {
        check_ajax_referer('aichatbot_nonce', 'nonce');
        
        $message = sanitize_textarea_field($_POST['message']);
        $display_name = sanitize_text_field($_POST['display_name'] ?? '');
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        
        error_log('AIChatbot: Processing message: ' . $message);
        error_log('AIChatbot: Session ID: ' . ($session_id ?: 'new session'));
        
        // Get user information
        $user_name = '';
        $user_id = '0';
        $user_email = '';
        
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $user_name = $current_user->display_name ?: $current_user->user_login;
            $user_id = $current_user->ID;
            $user_email = $current_user->user_email;
            error_log('AIChatbot: Logged-in user detected - ID: ' . $user_id . ', Name: ' . $user_name . ', Email: ' . $user_email);
        } else {
            $user_name = $display_name ?: 'Guest';
            $user_id = $this->get_guest_identifier();
            $user_email = 'guest@example.com';
            error_log('AIChatbot: Guest user detected - Name: ' . $user_name . ', ID: ' . $user_id);
        }
        
        // Generate new session ID if not provided
        if (empty($session_id)) {
            if (is_user_logged_in()) {
                $session_id = 'chat_' . uniqid() . '.' . microtime(true);
            } else {
                $session_id = 'guest_' . uniqid() . '.' . microtime(true);
            }
            error_log('AIChatbot: Generated new session ID: ' . $session_id);
        } else {
            error_log('AIChatbot: Using existing session ID: ' . $session_id);
        }
        
        // Save user message to database
        $message_id = AIChatbot_Database::insert_message(
            $user_name, 
            $user_id, 
            $user_email, 
            $message, 
            'user_input', 
            null, 
            null, 
            null, 
            $session_id
        );
        
        error_log('AIChatbot: User message saved: ' . ($message_id ? 'YES' : 'NO'));
        
        // Get previous response ID for conversation continuity
        $previous_response_id = AIChatbot_Database::get_last_response_id($session_id);
        error_log('AIChatbot: Previous response ID: ' . ($previous_response_id ?: 'none'));
        
        // Get AI response using /responses endpoint
        error_log('AIChatbot: Calling OpenAI /responses API...');
        error_log('AIChatbot: Message to send: ' . $message);
        error_log('AIChatbot: Previous response ID: ' . ($previous_response_id ?: 'none'));
        
        // Check configuration before API call
        $api_key = get_option('aichatbot_openai_key');
        $model = get_option('aichatbot_openai_model', 'gpt-3.5-turbo');
        $woocommerce_enabled = get_option('aichatbot_woocommerce_enabled');
        
        error_log('AIChatbot: Configuration check - API Key: ' . (empty($api_key) ? 'MISSING' : 'SET (' . strlen($api_key) . ' chars)'));
        error_log('AIChatbot: Configuration check - Model: ' . $model);
        error_log('AIChatbot: Configuration check - WooCommerce: ' . ($woocommerce_enabled ? 'enabled' : 'disabled'));
        
        $ai_response = AIChatbot_OpenAI::get_response($message, $previous_response_id);
        
        error_log('AIChatbot: OpenAI response received: ' . ($ai_response ? 'SUCCESS' : 'FAILED'));
        if ($ai_response) {
            error_log('AIChatbot: Response type: ' . gettype($ai_response));
            if (is_array($ai_response)) {
                error_log('AIChatbot: Response array keys: ' . implode(', ', array_keys($ai_response)));
            }
        }
        
        if ($ai_response === false) {
            error_log('AIChatbot: Failed to get AI response from OpenAI');
            $final_response = "I apologize, but I'm having trouble responding right now. Please try again in a moment or contact support if the issue persists.";
            $response_id = null;
        } else {
            // Handle new response format
            if (is_array($ai_response)) {
                $final_response = $ai_response['response'];
                $response_id = $ai_response['response_id'];
            } else {
                // Fallback for old format
                $final_response = $ai_response;
                $response_id = null;
            }
            error_log('AIChatbot: AI response received: ' . substr($final_response, 0, 100) . '...');
        }
        
        // Save AI response to database
        $ai_message_id = AIChatbot_Database::insert_message(
            'AI Assistant', 
            'ai', 
            'ai@assistant.com', 
            $final_response, 
            'ai_response', 
            null, 
            $final_response, 
            $response_id, 
            $session_id
        );
        
        error_log('AIChatbot: AI response saved: ' . ($ai_message_id ? 'YES' : 'NO'));
        
        wp_send_json_success(array(
            'message' => $final_response,
            'ai_response' => $final_response,
            'response_id' => $response_id,
            'session_id' => $session_id
        ));
    }
    
    /**
     * Generate unique guest identifier
     */
    private static function get_guest_identifier() {
        // Start session if not already started
        if (!session_id()) {
            session_start();
        }
        
        // Get client IP
        $ip = self::get_client_ip();
        
        // Get user agent
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        // Create unique identifier based on IP, user agent, and session
        $unique_string = $ip . $user_agent . session_id();
        $guest_id = 'guest_' . substr(md5($unique_string), 0, 12);
        
        // Store guest identifier in session for consistency
        if (!isset($_SESSION['aichatbot_guest_id'])) {
            $_SESSION['aichatbot_guest_id'] = $guest_id;
        } else {
            $guest_id = $_SESSION['aichatbot_guest_id'];
        }
        
        return $guest_id;
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    }
    
    /**
     * Get messages for display
     */
    public function get_messages() {
        check_ajax_referer('aichatbot_nonce', 'nonce');
        
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        
        error_log('AIChatbot: get_messages called for session: ' . ($session_id ?: 'all sessions'));
        
        // Ensure database table exists
        if (class_exists('AIChatbot_Database')) {
            AIChatbot_Database::create_tables();
        }
        
        $messages = array();
        
        if (class_exists('AIChatbot_Database')) {
            if (!empty($session_id)) {
                // Get messages for specific session
                $messages = AIChatbot_Database::get_messages_by_session($session_id);
                error_log('AIChatbot: Retrieved ' . count($messages) . ' messages for session: ' . $session_id);
            } else {
                // Get all recent messages (fallback)
                $messages = AIChatbot_Database::get_messages();
                error_log('AIChatbot: Retrieved ' . count($messages) . ' messages (all sessions)');
            }
        }
        
        error_log('AIChatbot: Sending messages response: ' . json_encode($messages));
        
        wp_send_json_success($messages);
    }
    
    public function test_ai_response() {
        check_ajax_referer('aichatbot_nonce', 'nonce');
        
        $test_message = 'Hello, can you help me?';
        
        // Check API key first
        $api_key = get_option('aichatbot_openai_key');
        if (empty($api_key)) {
            wp_send_json_error('API key not configured. Please set your OpenAI API key in the plugin settings.');
            return;
        }
        
        error_log('AIChatbot: Test function called with API key length: ' . strlen($api_key));
        
        if (class_exists('AIChatbot_OpenAI')) {
            $response = AIChatbot_OpenAI::get_response($test_message);
            if ($response) {
                wp_send_json_success(array(
                    'message' => 'AI test successful',
                    'response' => $response
                ));
            } else {
                wp_send_json_error('AI test failed - no response received. Check error logs for details.');
            }
        } else {
            wp_send_json_error('AI test failed - OpenAI class not available');
        }
    }
}