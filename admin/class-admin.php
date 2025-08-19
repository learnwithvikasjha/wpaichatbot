<?php
/**
 * Admin functionality class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIChatbot_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    public function admin_menu() {
        add_menu_page(
            __('AI Chatbot', 'aichatbot'),
            __('AI Chatbot', 'aichatbot'),
            'manage_options',
            'aichatbot-settings',
            array($this, 'settings_page'),
            'dashicons-format-chat',
            30
        );
        
        add_submenu_page(
            'aichatbot-settings',
            __('Chat History', 'aichatbot'),
            __('Chat History', 'aichatbot'),
            'manage_options',
            'aichatbot-chat-history',
            array($this, 'chat_history_page')
        );
        
        add_submenu_page(
            'aichatbot-settings',
            __('Settings', 'aichatbot'),
            __('Settings', 'aichatbot'),
            'manage_options',
            'aichatbot-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_init() {
        register_setting('aichatbot_settings', 'aichatbot_openai_key');
        register_setting('aichatbot_settings', 'aichatbot_openai_model');
        register_setting('aichatbot_settings', 'aichatbot_woocommerce_enabled');
        
        // Add AJAX handlers for chat history
        add_action('wp_ajax_aichatbot_get_chat_history', array($this, 'ajax_get_chat_history'));
        add_action('wp_ajax_aichatbot_get_conversation_pairs', array($this, 'ajax_get_conversation_pairs'));
        
        // Add AJAX handlers for testing
        add_action('wp_ajax_aichatbot_test_config', array($this, 'ajax_test_config'));
        add_action('wp_ajax_aichatbot_test_openai', array($this, 'ajax_test_openai'));
        add_action('wp_ajax_aichatbot_test_database', array($this, 'ajax_test_database'));
        add_action('wp_ajax_aichatbot_test_woocommerce', array($this, 'ajax_test_woocommerce'));
        add_action('wp_ajax_aichatbot_test_functions', array($this, 'ajax_test_functions'));
        add_action('wp_ajax_aichatbot_test_complete', array($this, 'ajax_test_complete'));
        add_action('wp_ajax_aichatbot_test_direct_api', array($this, 'ajax_test_direct_api'));
    }
    
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('AI Chatbot Settings', 'aichatbot'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('aichatbot_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('OpenAI API Key', 'aichatbot'); ?></th>
                        <td>
                            <input type="password" name="aichatbot_openai_key" value="<?php echo esc_attr(get_option('aichatbot_openai_key')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Enter your OpenAI API key to enable AI responses', 'aichatbot'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('OpenAI Model', 'aichatbot'); ?></th>
                        <td>
                            <select name="aichatbot_openai_model" class="regular-text">
                                <?php
                                $current_model = get_option('aichatbot_openai_model', 'gpt-3.5-turbo');
                                $available_models = array(
                                    'gpt-4o' => 'GPT-4o (Latest, most capable)',
                                    'gpt-4o-mini' => 'GPT-4o mini (Fast, efficient)',
                                    'gpt-4-turbo' => 'GPT-4 Turbo (Previous generation)',
                                    'gpt-4' => 'GPT-4 (Standard)',
                                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Fast, cost-effective)',
                                    'gpt-3.5-turbo-16k' => 'GPT-3.5 Turbo 16K (Longer context)'
                                );
                                
                                foreach ($available_models as $model_id => $model_description) {
                                    $selected = ($current_model === $model_id) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($model_id) . '" ' . $selected . '>' . esc_html($model_description) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">
                                <?php _e('Choose the OpenAI model to use. GPT-4o is the latest and most capable, while GPT-3.5 Turbo is faster and more cost-effective.', 'aichatbot'); ?>
                            </p>
                            <?php if (class_exists('AIChatbot_Model_Info')) : ?>
                                <details style="margin-top: 10px;">
                                    <summary style="cursor: pointer; font-weight: bold; color: #0073aa;">
                                        <?php _e('📊 View Detailed Model Comparison', 'aichatbot'); ?>
                                    </summary>
                                    <?php echo AIChatbot_Model_Info::get_model_comparison_table(); ?>
                                </details>
                            <?php endif; ?>
                            <p class="description">
                                <strong><?php _e('Quick Recommendations:', 'aichatbot'); ?></strong><br>
                                • <strong>Best Performance:</strong> GPT-4o<br>
                                • <strong>Best Value:</strong> GPT-4o mini<br>
                                • <strong>Most Cost-Effective:</strong> GPT-3.5 Turbo<br>
                                • <strong>Long Conversations:</strong> GPT-3.5 Turbo 16K
                            </p>
                        </td>
                    </tr>
                    <?php if (class_exists('WooCommerce')) : ?>
                    <tr>
                        <th scope="row"><?php _e('WooCommerce Integration', 'aichatbot'); ?></th>
                        <td>
                            <input type="checkbox" name="aichatbot_woocommerce_enabled" value="1" <?php checked(get_option('aichatbot_woocommerce_enabled'), 1); ?> />
                            <label><?php _e('Enable AI to answer product and order queries', 'aichatbot'); ?></label>
                            <p class="description"><?php _e('Allow the chatbot to access WooCommerce data for product info and order status', 'aichatbot'); ?></p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <!-- Test Section -->
            <div style="margin-top: 40px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">
                <h2><?php _e('🧪 Plugin Testing & Diagnostics', 'aichatbot'); ?></h2>
                <p><?php _e('Use these tests to verify that your AI Chatbot is working correctly:', 'aichatbot'); ?></p>
                
                <!-- Current Settings Display -->
                <div style="margin: 20px 0; padding: 15px; background: #fff; border: 1px solid #ccc; border-radius: 3px;">
                    <h3><?php _e('📋 Current Settings', 'aichatbot'); ?></h3>
                    <?php
                    $api_key = get_option('aichatbot_openai_key');
                    $model = get_option('aichatbot_openai_model', 'gpt-3.5-turbo');
                    $woo_enabled = get_option('aichatbot_woocommerce_enabled');
                    ?>
                    <p><strong>API Key:</strong> <?php echo empty($api_key) ? '❌ Not configured' : '✅ Configured (length: ' . strlen($api_key) . ')'; ?></p>
                    <p><strong>Model:</strong> <?php echo $model; ?></p>
                    <p><strong>WooCommerce Integration:</strong> <?php echo $woo_enabled ? '✅ Enabled' : '❌ Disabled'; ?></p>
                    <p><strong>Database Table:</strong> 
                        <?php 
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'aichatbot_messages';
                        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
                        echo $table_exists ? '✅ Exists' : '❌ Missing';
                        ?>
                    </p>
                </div>
                
                <div style="margin: 20px 0;">
                    <h3><?php _e('1. Basic Configuration Test', 'aichatbot'); ?></h3>
                    <button type="button" id="test-config" class="button button-secondary">
                        <?php _e('Test Configuration', 'aichatbot'); ?>
                    </button>
                    <div id="config-test-result" style="margin-top: 10px;"></div>
                </div>
                
                <div style="margin: 20px 0;">
                    <h3><?php _e('2. OpenAI API Test', 'aichatbot'); ?></h3>
                    <button type="button" id="test-openai" class="button button-secondary">
                        <?php _e('Test OpenAI API', 'aichatbot'); ?>
                    </button>
                    <div id="openai-test-result" style="margin-top: 10px;"></div>
                </div>
                
                <div style="margin: 20px 0;">
                    <h3><?php _e('3. Database Test', 'aichatbot'); ?></h3>
                    <button type="button" id="test-database" class="button button-secondary">
                        <?php _e('Test Database', 'aichatbot'); ?>
                    </button>
                    <div id="database-test-result" style="margin-top: 10px;"></div>
                </div>
                
                <?php if (class_exists('WooCommerce')) : ?>
                <div style="margin: 20px 0;">
                    <h3><?php _e('4. WooCommerce Integration Test', 'aichatbot'); ?></h3>
                    <button type="button" id="test-woocommerce" class="button button-secondary">
                        <?php _e('Test WooCommerce', 'aichatbot'); ?>
                    </button>
                    <div id="woocommerce-test-result" style="margin-top: 10px;"></div>
                </div>
                <?php endif; ?>
                
                <div style="margin: 20px 0;">
                    <h3><?php _e('5. Function Calling Test', 'aichatbot'); ?></h3>
                    <button type="button" id="test-functions" class="button button-secondary">
                        <?php _e('Test Function Calling', 'aichatbot'); ?>
                    </button>
                    <div id="functions-test-result" style="margin-top: 10px;"></div>
                </div>
                
                <div style="margin: 20px 0;">
                    <h3><?php _e('6. Complete Integration Test', 'aichatbot'); ?></h3>
                    <button type="button" id="test-complete" class="button button-primary">
                        <?php _e('Run Complete Test', 'aichatbot'); ?>
                    </button>
                    <div id="complete-test-result" style="margin-top: 10px;"></div>
                </div>
                
                <div style="margin: 20px 0;">
                    <h3><?php _e('7. Direct API Test (Simple)', 'aichatbot'); ?></h3>
                    <button type="button" id="test-direct-api" class="button button-secondary">
                        <?php _e('Test Direct API Call', 'aichatbot'); ?>
                    </button>
                    <div id="direct-api-test-result" style="margin-top: 10px;"></div>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // Test Configuration
                $('#test-config').click(function() {
                    var button = $(this);
                    var resultDiv = $('#config-test-result');
                    
                    button.prop('disabled', true).text('Testing...');
                    resultDiv.html('<p>Testing configuration...</p>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'aichatbot_test_config',
                            nonce: '<?php echo wp_create_nonce('aichatbot_test_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                resultDiv.html('<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 3px;"><strong>✅ Configuration Test Passed</strong><br>' + response.data.message + '</div>');
                            } else {
                                resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 3px;"><strong>❌ Configuration Test Failed</strong><br>' + response.data + '</div>');
                            }
                        },
                        error: function() {
                            resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 3px;"><strong>❌ Configuration Test Error</strong><br>AJAX request failed</div>');
                        },
                        complete: function() {
                            button.prop('disabled', false).text('Test Configuration');
                        }
                    });
                });
                
                // Test OpenAI API
                $('#test-openai').click(function() {
                    var button = $(this);
                    var resultDiv = $('#openai-test-result');
                    
                    button.prop('disabled', true).text('Testing...');
                    resultDiv.html('<p>Testing OpenAI API...</p>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'aichatbot_test_openai',
                            nonce: '<?php echo wp_create_nonce('aichatbot_test_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                resultDiv.html('<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 3px;"><strong>✅ OpenAI API Test Passed</strong><br>Response: ' + response.data.response.substring(0, 100) + '...</div>');
                            } else {
                                resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 3px;"><strong>❌ OpenAI API Test Failed</strong><br>' + response.data + '</div>');
                            }
                        },
                        error: function() {
                            resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 3px;"><strong>❌ OpenAI API Test Error</strong><br>AJAX request failed</div>');
                        },
                        complete: function() {
                            button.prop('disabled', false).text('Test OpenAI API');
                        }
                    });
                });
                
                // Test Database
                $('#test-database').click(function() {
                    var button = $(this);
                    var resultDiv = $('#database-test-result');
                    
                    button.prop('disabled', true).text('Testing...');
                    resultDiv.html('<p>Testing database...</p>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'aichatbot_test_database',
                            nonce: '<?php echo wp_create_nonce('aichatbot_test_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                resultDiv.html('<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 3px;"><strong>✅ Database Test Passed</strong><br>' + response.data.message + '</div>');
                            } else {
                                resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 3px;"><strong>❌ Database Test Failed</strong><br>' + response.data + '</div>');
                            }
                        },
                        error: function() {
                            resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 3px;"><strong>❌ Database Test Error</strong><br>AJAX request failed</div>');
                        },
                        complete: function() {
                            button.prop('disabled', false).text('Test Database');
                        }
                    });
                });
                
                <?php if (class_exists('WooCommerce')) : ?>
                // Test WooCommerce
                $('#test-woocommerce').click(function() {
                    var button = $(this);
                    var resultDiv = $('#woocommerce-test-result');
                    
                    button.prop('disabled', true).text('Testing...');
                    resultDiv.html('<p>Testing WooCommerce integration...</p>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'aichatbot_test_woocommerce',
                            nonce: '<?php echo wp_create_nonce('aichatbot_test_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                resultDiv.html('<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 3px;"><strong>✅ WooCommerce Test Passed</strong><br>' + response.data.message + '</div>');
                            } else {
                                resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 3px;"><strong>❌ WooCommerce Test Failed</strong><br>' + response.data + '</div>');
                            }
                        },
                        error: function() {
                            resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 3px;"><strong>❌ WooCommerce Test Error</strong><br>AJAX request failed</div>');
                        },
                        complete: function() {
                            button.prop('disabled', false).text('Test WooCommerce');
                        }
                    });
                });
                <?php endif; ?>
                
                // Test Function Calling
                $('#test-functions').click(function() {
                    var button = $(this);
                    var resultDiv = $('#functions-test-result');
                    
                    button.prop('disabled', true).text('Testing...');
                    resultDiv.html('<p>Testing function calling...</p>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'aichatbot_test_functions',
                            nonce: '<?php echo wp_create_nonce('aichatbot_test_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                resultDiv.html('<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 3px;"><strong>✅ Function Calling Test Passed</strong><br>' + response.data.message + '</div>');
                            } else {
                                resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 3px;"><strong>❌ Function Calling Test Failed</strong><br>' + response.data + '</div>');
                            }
                        },
                        error: function() {
                            resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 3px;"><strong>❌ Function Calling Test Error</strong><br>AJAX request failed</div>');
                        },
                        complete: function() {
                            button.prop('disabled', false).text('Test Function Calling');
                        }
                    });
                });
                
                // Complete Test
                $('#test-complete').click(function() {
                    var button = $(this);
                    var resultDiv = $('#complete-test-result');
                    
                    button.prop('disabled', true).text('Running Complete Test...');
                    resultDiv.html('<p>Running complete integration test...</p>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'aichatbot_test_complete',
                            nonce: '<?php echo wp_create_nonce('aichatbot_test_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                resultDiv.html('<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 3px;"><strong>✅ Complete Test Passed</strong><br>' + response.data.message + '</div>');
                            } else {
                                resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 3px;"><strong>❌ Complete Test Failed</strong><br>' + response.data + '</div>');
                            }
                        },
                        error: function() {
                            resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 3px;"><strong>❌ Complete Test Error</strong><br>AJAX request failed</div>');
                        },
                        complete: function() {
                            button.prop('disabled', false).text('Run Complete Test');
                        }
                    });
                });
                
                // Direct API Test
                $('#test-direct-api').click(function() {
                    var button = $(this);
                    var resultDiv = $('#direct-api-test-result');
                    
                    button.prop('disabled', true).text('Testing...');
                    resultDiv.html('<p>Testing direct API call...</p>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'aichatbot_test_direct_api',
                            nonce: '<?php echo wp_create_nonce('aichatbot_test_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                resultDiv.html('<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 3px;"><strong>✅ Direct API Test Passed</strong><br>' + response.data.message + '</div>');
                            } else {
                                resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 3px;"><strong>❌ Direct API Test Failed</strong><br>' + response.data + '</div>');
                            }
                        },
                        error: function() {
                            resultDiv.html('<div style="color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 3px;"><strong>❌ Direct API Test Error</strong><br>AJAX request failed</div>');
                        },
                        complete: function() {
                            button.prop('disabled', false).text('Test Direct API Call');
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }
    
    public function chat_history_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('AI Chatbot - Chat History', 'aichatbot'); ?></h1>
            
            <div class="aichatbot-tabs">
                <button class="tab-button active" data-tab="conversations"><?php _e('Conversations', 'aichatbot'); ?></button>
                <button class="tab-button" data-tab="detailed"><?php _e('Detailed Log', 'aichatbot'); ?></button>
            </div>
            
            <div id="conversations-tab" class="tab-content active">
                <h2><?php _e('Recent Conversations', 'aichatbot'); ?></h2>
                <div id="conversations-list">
                    <p><?php _e('Loading conversations...', 'aichatbot'); ?></p>
                </div>
            </div>
            
            <div id="detailed-tab" class="tab-content">
                <h2><?php _e('Detailed Chat History', 'aichatbot'); ?></h2>
                
                <div class="aichatbot-filters">
                    <input type="text" id="filter-user" placeholder="<?php _e('Filter by user name...', 'aichatbot'); ?>">
                    <select id="filter-type">
                        <option value=""><?php _e('All message types', 'aichatbot'); ?></option>
                        <option value="user_input"><?php _e('User messages', 'aichatbot'); ?></option>
                        <option value="ai_response"><?php _e('AI responses', 'aichatbot'); ?></option>
                    </select>
                    <input type="date" id="filter-date-from" placeholder="<?php _e('From date', 'aichatbot'); ?>">
                    <input type="date" id="filter-date-to" placeholder="<?php _e('To date', 'aichatbot'); ?>">
                    <button id="apply-filters"><?php _e('Apply Filters', 'aichatbot'); ?></button>
                    <button id="clear-filters"><?php _e('Clear Filters', 'aichatbot'); ?></button>
                </div>
                
                <div id="detailed-list">
                    <p><?php _e('Loading detailed history...', 'aichatbot'); ?></p>
                </div>
                
                <div id="pagination">
                    <button id="prev-page" disabled><?php _e('Previous', 'aichatbot'); ?></button>
                    <span id="page-info"><?php _e('Page 1', 'aichatbot'); ?></span>
                    <button id="next-page"><?php _e('Next', 'aichatbot'); ?></button>
                </div>
            </div>
        </div>
        
        <style>
        .aichatbot-tabs {
            margin: 20px 0;
            border-bottom: 1px solid #ccc;
        }
        .tab-button {
            padding: 10px 20px;
            margin-right: 5px;
            border: 1px solid #ccc;
            background: #f1f1f1;
            cursor: pointer;
        }
        .tab-button.active {
            background: #0073aa;
            color: white;
            border-color: #0073aa;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .aichatbot-filters {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        .aichatbot-filters input,
        .aichatbot-filters select {
            margin-right: 10px;
            padding: 5px;
        }
        .conversation-item {
            margin: 15px 0;
            padding: 15px;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        .conversation-item h3 {
            margin-top: 0;
            color: #0073aa;
        }
        .message-detail {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-left: 3px solid #0073aa;
        }
        .context-box {
            background: #f0f0f0;
            padding: 10px;
            margin: 5px 0;
            border-radius: 3px;
            font-family: monospace;
            font-size: 12px;
        }
        .user-message {
            border-left-color: #0073aa;
        }
        .ai-message {
            border-left-color: #46b450;
        }
        #pagination {
            margin: 20px 0;
            text-align: center;
        }
        #pagination button {
            margin: 0 5px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            let currentPage = 1;
            let currentFilters = {};
            
            // Tab switching
            $('.tab-button').click(function() {
                $('.tab-button').removeClass('active');
                $(this).addClass('active');
                $('.tab-content').removeClass('active');
                $('#' + $(this).data('tab') + '-tab').addClass('active');
                
                if ($(this).data('tab') === 'conversations') {
                    loadConversations();
                } else {
                    loadDetailedHistory();
                }
            });
            
            // Load conversations
            function loadConversations() {
                $('#conversations-list').html('<p>Loading conversations...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'aichatbot_get_conversation_pairs',
                        nonce: '<?php echo wp_create_nonce('aichatbot_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            displayConversations(response.data);
                        } else {
                            $('#conversations-list').html('<p>No conversations found.</p>');
                        }
                    },
                    error: function() {
                        $('#conversations-list').html('<p>Error loading conversations.</p>');
                    }
                });
            }
            
            function displayConversations(conversations) {
                let html = '';
                conversations.forEach(function(conv) {
                    html += '<div class="conversation-item">';
                    
                    // Show user info with ID and email if available
                    let userInfo = conv.user_name;
                    if (conv.user_id && conv.user_id !== '0') {
                        if (conv.user_id.toString().startsWith('guest_')) {
                            userInfo += ' (Guest ID: ' + conv.user_id + ')';
                        } else {
                            userInfo += ' (ID: ' + conv.user_id + ')';
                            if (conv.user_email) {
                                userInfo += ' - ' + conv.user_email;
                            }
                        }
                    } else {
                        userInfo += ' (Guest)';
                    }
                    
                    html += '<h3>' + userInfo + ' - ' + conv.user_timestamp + '</h3>';
                    html += '<div class="message-detail user-message">';
                    html += '<strong>User:</strong> ' + conv.user_message;
                    if (conv.context_sent) {
                        html += '<div class="context-box"><strong>Context sent to OpenAI:</strong><br>' + conv.context_sent + '</div>';
                    }
                    html += '</div>';
                    if (conv.ai_message) {
                        html += '<div class="message-detail ai-message">';
                        html += '<strong>AI Response:</strong> ' + conv.ai_message;
                        html += '</div>';
                    }
                    html += '</div>';
                });
                $('#conversations-list').html(html);
            }
            
            // Load detailed history
            function loadDetailedHistory() {
                $('#detailed-list').html('<p>Loading detailed history...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'aichatbot_get_chat_history',
                        page: currentPage,
                        filters: currentFilters,
                        nonce: '<?php echo wp_create_nonce('aichatbot_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            displayDetailedHistory(response.data.messages, response.data.total, response.data.pages);
                        } else {
                            $('#detailed-list').html('<p>No messages found.</p>');
                        }
                    },
                    error: function() {
                        $('#detailed-list').html('<p>Error loading detailed history.</p>');
                    }
                });
            }
            
            function displayDetailedHistory(messages, total, pages) {
                let html = '<p>Total messages: ' + total + '</p>';
                messages.forEach(function(msg) {
                    html += '<div class="message-detail ' + (msg.message_type === 'user_input' ? 'user-message' : 'ai-message') + '">';
                    
                    // Show user info with ID and email if available
                    let userInfo = msg.user_name;
                    if (msg.user_id && msg.user_id !== '0') {
                        if (msg.user_id.toString().startsWith('guest_')) {
                            userInfo += ' (Guest ID: ' + msg.user_id + ')';
                        } else {
                            userInfo += ' (ID: ' + msg.user_id + ')';
                            if (msg.user_email) {
                                userInfo += ' - ' + msg.user_email;
                            }
                        }
                    } else {
                        userInfo += ' (Guest)';
                    }
                    
                    html += '<strong>' + userInfo + ' (' + msg.message_type + '):</strong> ' + msg.message;
                    html += '<br><small>Time: ' + msg.timestamp + ' | IP: ' + msg.ip_address + '</small>';
                    if (msg.context_sent) {
                        html += '<div class="context-box"><strong>Context sent:</strong><br>' + msg.context_sent + '</div>';
                    }
                    html += '</div>';
                });
                $('#detailed-list').html(html);
                
                // Update pagination
                $('#page-info').text('Page ' + currentPage + ' of ' + pages);
                $('#prev-page').prop('disabled', currentPage <= 1);
                $('#next-page').prop('disabled', currentPage >= pages);
            }
            
            // Filter handling
            $('#apply-filters').click(function() {
                currentFilters = {
                    user_name: $('#filter-user').val(),
                    message_type: $('#filter-type').val(),
                    date_from: $('#filter-date-from').val(),
                    date_to: $('#filter-date-to').val()
                };
                currentPage = 1;
                loadDetailedHistory();
            });
            
            $('#clear-filters').click(function() {
                $('#filter-user').val('');
                $('#filter-type').val('');
                $('#filter-date-from').val('');
                $('#filter-date-to').val('');
                currentFilters = {};
                currentPage = 1;
                loadDetailedHistory();
            });
            
            // Pagination
            $('#prev-page').click(function() {
                if (currentPage > 1) {
                    currentPage--;
                    loadDetailedHistory();
                }
            });
            
            $('#next-page').click(function() {
                currentPage++;
                loadDetailedHistory();
            });
            
            // Initial load
            loadConversations();
        });
        </script>
        <?php
    }
    
    // Test Methods
    public function ajax_test_config() {
        check_ajax_referer('aichatbot_test_nonce', 'nonce');
        
        $api_key = get_option('aichatbot_openai_key');
        $model = get_option('aichatbot_openai_model', 'gpt-3.5-turbo');
        $woocommerce_enabled = get_option('aichatbot_woocommerce_enabled');
        
        $issues = array();
        
        if (empty($api_key)) {
            $issues[] = 'OpenAI API key is not configured';
        }
        
        if (empty($model)) {
            $issues[] = 'OpenAI model is not selected';
        }
        
        if (class_exists('WooCommerce') && !$woocommerce_enabled) {
            $issues[] = 'WooCommerce is installed but integration is disabled';
        }
        
        if (empty($issues)) {
            $message = 'All configuration settings are properly configured.';
            if (class_exists('WooCommerce')) {
                $message .= ' WooCommerce integration is ' . ($woocommerce_enabled ? 'enabled' : 'disabled') . '.';
            }
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(implode(', ', $issues));
        }
    }
    
    public function ajax_test_openai() {
        check_ajax_referer('aichatbot_test_nonce', 'nonce');
        
        if (!class_exists('AIChatbot_OpenAI')) {
            wp_send_json_error('OpenAI class not available');
            return;
        }
        
        // Check API key first
        $api_key = get_option('aichatbot_openai_key');
        if (empty($api_key)) {
            wp_send_json_error('OpenAI API key is not configured. Please set your API key in the settings above.');
            return;
        }
        
        // Check model
        $model = get_option('aichatbot_openai_model', 'gpt-3.5-turbo');
        if (empty($model)) {
            wp_send_json_error('OpenAI model is not selected. Please choose a model in the settings above.');
            return;
        }
        
        // Validate model
        $valid_models = array(
            'gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-4', 
            'gpt-3.5-turbo', 'gpt-3.5-turbo-16k'
        );
        
        if (!in_array($model, $valid_models)) {
            wp_send_json_error('Invalid model selected: ' . $model . '. Please select a valid model from the dropdown.');
            return;
        }
        
        error_log('AIChatbot: Admin test - API key length: ' . strlen($api_key));
        error_log('AIChatbot: Admin test - Selected model: ' . $model);
        
        // First try the simple API call method
        $simple_response = AIChatbot_OpenAI::test_simple_api_call('Hello, this is a simple test message.');
        
        if ($simple_response) {
            wp_send_json_success(array(
                'message' => 'OpenAI API is working correctly (simple test)',
                'response' => $simple_response
            ));
            return;
        }
        
        // If simple test fails, try with gpt-3.5-turbo as fallback
        if ($model !== 'gpt-3.5-turbo') {
            error_log('AIChatbot: Admin test - Trying fallback with gpt-3.5-turbo');
            
            // Temporarily set the model to gpt-3.5-turbo for testing
            $original_model = $model;
            update_option('aichatbot_openai_model', 'gpt-3.5-turbo');
            
            $fallback_response = AIChatbot_OpenAI::test_simple_api_call('Hello, this is a fallback test message.');
            
            // Restore original model
            update_option('aichatbot_openai_model', $original_model);
            
            if ($fallback_response) {
                wp_send_json_success(array(
                    'message' => 'OpenAI API works with gpt-3.5-turbo but failed with ' . $original_model . '. The selected model might not be available.',
                    'response' => $fallback_response
                ));
                return;
            }
        }
        
        // If simple test fails, try the full method
        $test_message = 'Hello, this is a test message. Please respond with a simple greeting.';
        $response = AIChatbot_OpenAI::get_response($test_message);
        
        if ($response) {
            wp_send_json_success(array(
                'message' => 'OpenAI API is working correctly (full test)',
                'response' => $response
            ));
        } else {
            // Get the last few error log entries to help debug
            $error_log_file = ini_get('error_log');
            if (empty($error_log_file)) {
                $error_log_file = WP_CONTENT_DIR . '/debug.log';
            }
            
            $recent_errors = '';
            if (file_exists($error_log_file)) {
                $log_content = file_get_contents($error_log_file);
                $lines = explode("\n", $log_content);
                $recent_lines = array_slice($lines, -20); // Last 20 lines
                $aichatbot_errors = array_filter($recent_lines, function($line) {
                    return strpos($line, 'AIChatbot:') !== false;
                });
                if (!empty($aichatbot_errors)) {
                    $recent_errors = 'Recent error logs: ' . implode(' | ', array_slice($aichatbot_errors, -5));
                }
            }
            
            $error_message = 'OpenAI API test failed - no response received.';
            if (!empty($recent_errors)) {
                $error_message .= ' ' . $recent_errors;
            }
            
            wp_send_json_error($error_message);
        }
    }
    
    public function ajax_test_database() {
        check_ajax_referer('aichatbot_test_nonce', 'nonce');
        
        if (!class_exists('AIChatbot_Database')) {
            wp_send_json_error('Database class not available');
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'aichatbot_messages';
        
        $results = array();
        $all_good = true;
        
        // Test 1: Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if ($table_exists) {
            $results[] = '✅ Table exists: ' . $table_name;
        } else {
            $results[] = '❌ Table does not exist: ' . $table_name;
            $all_good = false;
        }
        
        // Test 2: Check table structure
        if ($table_exists) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
            $column_names = array();
            foreach ($columns as $column) {
                $column_names[] = $column->Field;
            }
            
            $required_columns = array(
                'id', 'user_name', 'user_id', 'user_email', 'message', 
                'message_type', 'context_sent', 'openai_response', 
                'session_id', 'ip_address', 'user_agent', 'timestamp'
            );
            
            foreach ($required_columns as $required_column) {
                if (in_array($required_column, $column_names)) {
                    $results[] = '✅ Column exists: ' . $required_column;
                } else {
                    $results[] = '❌ Missing column: ' . $required_column;
                    $all_good = false;
                }
            }
            
            // Check indexes
            $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name");
            $index_names = array();
            foreach ($indexes as $index) {
                $index_names[] = $index->Key_name;
            }
            
            $required_indexes = array('PRIMARY', 'session_id', 'message_type', 'timestamp', 'user_id');
            foreach ($required_indexes as $required_index) {
                if (in_array($required_index, $index_names)) {
                    $results[] = '✅ Index exists: ' . $required_index;
                } else {
                    $results[] = '⚠️ Missing index: ' . $required_index;
                }
            }
        }
        
        // Test 3: Try to create/upgrade table
        try {
            AIChatbot_Database::check_and_upgrade_table();
            $results[] = '✅ Table upgrade check completed';
        } catch (Exception $e) {
            $results[] = '❌ Table upgrade failed: ' . $e->getMessage();
            $all_good = false;
        }
        
        // Test 4: Test message insertion
        try {
            $test_result = AIChatbot_Database::insert_message(
                'Database Test User',
                'This is a database test message',
                'user_input',
                null,
                null,
                'db_test_' . time(),
                0,
                'test@example.com'
            );
            
            if ($test_result) {
                $results[] = '✅ Message insertion test passed';
            } else {
                $results[] = '❌ Message insertion test failed';
                $all_good = false;
            }
        } catch (Exception $e) {
            $results[] = '❌ Message insertion error: ' . $e->getMessage();
            $all_good = false;
        }
        
        // Test 5: Test message retrieval
        try {
            $messages = AIChatbot_Database::get_messages(5);
            $message_count = count($messages);
            $results[] = "✅ Message retrieval test passed - Found {$message_count} recent messages";
        } catch (Exception $e) {
            $results[] = '❌ Message retrieval error: ' . $e->getMessage();
            $all_good = false;
        }
        
        // Test 6: Check WordPress options
        $options_to_check = array(
            'aichatbot_openai_key' => 'OpenAI API Key',
            'aichatbot_openai_model' => 'OpenAI Model',
            'aichatbot_woocommerce_enabled' => 'WooCommerce Integration'
        );
        
        foreach ($options_to_check as $option_name => $option_label) {
            $option_value = get_option($option_name);
            if ($option_value !== false) {
                if ($option_name === 'aichatbot_openai_key') {
                    $results[] = '✅ Option exists: ' . $option_label . ' (length: ' . strlen($option_value) . ')';
                } else {
                    $results[] = '✅ Option exists: ' . $option_label . ' = ' . $option_value;
                }
            } else {
                $results[] = '⚠️ Option missing: ' . $option_label;
            }
        }
        
        // Test 7: Check database connection
        $db_check = $wpdb->get_var("SELECT 1");
        if ($db_check === '1') {
            $results[] = '✅ Database connection working';
        } else {
            $results[] = '❌ Database connection failed';
            $all_good = false;
        }
        
        $message = implode('<br>', $results);
        
        if ($all_good) {
            $message .= '<br><br><strong>🎉 All database tests passed! Database is working correctly.</strong>';
            wp_send_json_success(array('message' => $message));
        } else {
            $message .= '<br><br><strong>⚠️ Some database tests failed. Please check the issues above.</strong>';
            wp_send_json_error($message);
        }
    }
    
    public function ajax_test_woocommerce() {
        check_ajax_referer('aichatbot_test_nonce', 'nonce');
        
        if (!class_exists('WooCommerce')) {
            wp_send_json_error('WooCommerce is not installed or activated');
            return;
        }
        
        if (!get_option('aichatbot_woocommerce_enabled')) {
            wp_send_json_error('WooCommerce integration is disabled in plugin settings');
            return;
        }
        
        // Test WooCommerce functions
        $products = wc_get_products(array('limit' => 1));
        $orders = wc_get_orders(array('limit' => 1));
        
        $message = 'WooCommerce integration is working correctly.';
        if (!empty($products)) {
            $message .= ' Found ' . count($products) . ' test product(s).';
        }
        if (!empty($orders)) {
            $message .= ' Found ' . count($orders) . ' test order(s).';
        }
        
        wp_send_json_success(array('message' => $message));
    }
    
    public function ajax_test_functions() {
        check_ajax_referer('aichatbot_test_nonce', 'nonce');
        
        if (!class_exists('AIChatbot_Tools')) {
            wp_send_json_error('Tools class not available');
            return;
        }
        
        // Test function availability
        $functions = AIChatbot_Tools::get_available_functions();
        $function_count = count($functions);
        
        if ($function_count > 0) {
            // Test a simple function
            $test_result = AIChatbot_Tools::execute_function('get_store_info', array());
            
            if (is_array($test_result) && !isset($test_result['error'])) {
                wp_send_json_success(array(
                    'message' => "Function calling is working correctly. {$function_count} functions available. Store info function executed successfully."
                ));
            } else {
                wp_send_json_error('Function calling test failed - store info function returned error');
            }
        } else {
            wp_send_json_error('No functions available for testing');
        }
    }
    
    public function ajax_test_complete() {
        check_ajax_referer('aichatbot_test_nonce', 'nonce');
        
        $results = array();
        $all_passed = true;
        
        // Test 1: Configuration
        $api_key = get_option('aichatbot_openai_key');
        if (empty($api_key)) {
            $results[] = '❌ API key not configured';
            $all_passed = false;
        } else {
            $results[] = '✅ API key configured';
        }
        
        // Test 2: Database
        if (class_exists('AIChatbot_Database')) {
            AIChatbot_Database::check_and_upgrade_table();
            $test_insert = AIChatbot_Database::insert_message(
                'Complete Test',
                'Complete test message',
                'user_input',
                null,
                null,
                'complete_test_' . time(),
                0,
                'test@example.com'
            );
            if ($test_insert) {
                $results[] = '✅ Database working';
            } else {
                $results[] = '❌ Database test failed';
                $all_passed = false;
            }
        } else {
            $results[] = '❌ Database class not available';
            $all_passed = false;
        }
        
        // Test 3: OpenAI
        if (class_exists('AIChatbot_OpenAI')) {
            $ai_response = AIChatbot_OpenAI::get_response('Complete test message');
            if ($ai_response) {
                $results[] = '✅ OpenAI API working';
            } else {
                $results[] = '❌ OpenAI API test failed';
                $all_passed = false;
            }
        } else {
            $results[] = '❌ OpenAI class not available';
            $all_passed = false;
        }
        
        // Test 4: WooCommerce (if available)
        if (class_exists('WooCommerce')) {
            if (get_option('aichatbot_woocommerce_enabled')) {
                $results[] = '✅ WooCommerce integration enabled';
            } else {
                $results[] = '⚠️ WooCommerce available but integration disabled';
            }
        } else {
            $results[] = 'ℹ️ WooCommerce not installed';
        }
        
        // Test 5: Function calling
        if (class_exists('AIChatbot_Tools')) {
            $functions = AIChatbot_Tools::get_available_functions();
            if (count($functions) > 0) {
                $results[] = '✅ Function calling available';
            } else {
                $results[] = '⚠️ No functions available';
            }
        } else {
            $results[] = '❌ Tools class not available';
            $all_passed = false;
        }
        
        $message = implode('<br>', $results);
        
        if ($all_passed) {
            $message .= '<br><br><strong>🎉 All critical tests passed! Your AI Chatbot is ready to use.</strong>';
            wp_send_json_success(array('message' => $message));
        } else {
            $message .= '<br><br><strong>⚠️ Some tests failed. Please check the configuration and try again.</strong>';
            wp_send_json_error($message);
        }
    }

    public function ajax_test_direct_api() {
        check_ajax_referer('aichatbot_test_nonce', 'nonce');

        if (!class_exists('AIChatbot_OpenAI')) {
            wp_send_json_error('OpenAI class not available');
            return;
        }

        $api_key = get_option('aichatbot_openai_key');
        if (empty($api_key)) {
            wp_send_json_error('OpenAI API key is not configured. Please set your API key in the settings above.');
            return;
        }

        $model = get_option('aichatbot_openai_model', 'gpt-3.5-turbo');
        if (empty($model)) {
            wp_send_json_error('OpenAI model is not selected. Please choose a model in the settings above.');
            return;
        }

        $valid_models = array(
            'gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-4', 
            'gpt-3.5-turbo', 'gpt-3.5-turbo-16k'
        );

        if (!in_array($model, $valid_models)) {
            wp_send_json_error('Invalid model selected: ' . $model . '. Please select a valid model from the dropdown.');
            return;
        }

        $test_message = 'Hello, this is a direct API test message.';
        $response = AIChatbot_OpenAI::get_response($test_message);

        if ($response) {
            wp_send_json_success(array(
                'message' => 'Direct OpenAI API test passed. Response: ' . $response
            ));
        } else {
            $error_log_file = ini_get('error_log');
            if (empty($error_log_file)) {
                $error_log_file = WP_CONTENT_DIR . '/debug.log';
            }
            $recent_errors = '';
            if (file_exists($error_log_file)) {
                $log_content = file_get_contents($error_log_file);
                $lines = explode("\n", $log_content);
                $recent_lines = array_slice($lines, -20); // Last 20 lines
                $aichatbot_errors = array_filter($recent_lines, function($line) {
                    return strpos($line, 'AIChatbot:') !== false;
                });
                if (!empty($aichatbot_errors)) {
                    $recent_errors = 'Recent error logs: ' . implode(' | ', array_slice($aichatbot_errors, -5));
                }
            }
            $error_message = 'Direct OpenAI API test failed - no response received.';
            if (!empty($recent_errors)) {
                $error_message .= ' ' . $recent_errors;
            }
            wp_send_json_error($error_message);
        }
    }

    public function ajax_get_chat_history() {
        check_ajax_referer('aichatbot_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $messages = AIChatbot_Database::get_chat_history($limit, $offset, $filters);
        $total = AIChatbot_Database::get_chat_history_count($filters);
        $pages = ceil($total / $limit);
        
        wp_send_json_success(array(
            'messages' => $messages,
            'total' => $total,
            'pages' => $pages,
            'current_page' => $page
        ));
    }
    
    public function ajax_get_conversation_pairs() {
        check_ajax_referer('aichatbot_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $conversations = AIChatbot_Database::get_conversation_pairs(20);
        wp_send_json_success($conversations);
    }
}