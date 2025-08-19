<?php
/**
 * OpenAI API integration class with function calling support
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIChatbot_OpenAI {
    
    /**
     * Get response from OpenAI using the /responses endpoint
     */
    public static function get_response($message, $previous_response_id = null) {
        error_log('AIChatbot: get_response() function called with message: ' . $message . ', previous_response_id: ' . ($previous_response_id ?: 'null'));
        
        $api_key = get_option('aichatbot_openai_key');
        if (empty($api_key)) {
            error_log('AIChatbot: OpenAI API key not configured');
            return false;
        }
        
        $selected_model = get_option('aichatbot_openai_model', 'gpt-3.5-turbo');
        $model_params = AIChatbot_Model_Info::get_model_parameters($selected_model);
        
        error_log('AIChatbot: Sending message to OpenAI. Original message: ' . $message);
        error_log('AIChatbot: Using model: ' . $selected_model);
        error_log('AIChatbot: API key length: ' . strlen($api_key));
        error_log('AIChatbot: Model parameters - max_tokens: ' . $model_params['max_tokens'] . ', temperature: ' . $model_params['temperature'] . ', timeout: ' . $model_params['timeout']);
        
        // Get available functions
        $functions = AIChatbot_Tools::get_available_functions();
        error_log('AIChatbot: Available functions count: ' . count($functions));
        
        // Prepare request body for /chat/completions endpoint
        $request_body = array(
            'model' => $selected_model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a helpful AI assistant for a WordPress website with WooCommerce integration. You can help users with their orders, products, and general questions. Be friendly and helpful.'
                ),
                array(
                    'role' => 'user',
                    'content' => $message
                )
            ),
            'temperature' => $model_params['temperature'],
            'max_tokens' => $model_params['max_tokens']
        );
        
        // Add functions if available and WooCommerce is enabled
        if (!empty($functions) && get_option('aichatbot_woocommerce_enabled')) {
            $request_body['tools'] = $functions;
            $request_body['tool_choice'] = 'auto';
            error_log('AIChatbot: Function calling enabled with corrected format');
            error_log('AIChatbot: Tools count: ' . count($functions));
            error_log('AIChatbot: First tool structure: ' . json_encode($functions[0]));
        } else {
            error_log('AIChatbot: Function calling disabled - functions: ' . (empty($functions) ? 'none' : count($functions)) . ', WooCommerce: ' . (get_option('aichatbot_woocommerce_enabled') ? 'enabled' : 'disabled'));
        }
        
        error_log('AIChatbot: Request body prepared: ' . json_encode($request_body));
        
        // Make API call to /chat/completions endpoint
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($request_body),
            'timeout' => $model_params['timeout']
        ));
        
        if (is_wp_error($response)) {
            error_log('AIChatbot: OpenAI API error: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('AIChatbot: OpenAI API response code: ' . $response_code);
        error_log('AIChatbot: OpenAI API response body: ' . $body);
        
        if ($response_code !== 200) {
            error_log('AIChatbot: OpenAI API returned non-200 status code: ' . $response_code);
            return false;
        }
        
        $data = json_decode($body, true);
        
        if (!isset($data['choices']) || empty($data['choices'])) {
            error_log('AIChatbot: Failed to parse OpenAI response - no choices');
            return false;
        }
        
        $choice = $data['choices'][0];
        $response_text = '';
        
        // Extract text from the response
        if (isset($choice['message']['content'])) {
            $response_text = $choice['message']['content'];
        }
        
        // Handle tool calls if present
        if (isset($choice['message']['tool_calls']) && !empty($choice['message']['tool_calls'])) {
            error_log('AIChatbot: Tool calls detected: ' . json_encode($choice['message']['tool_calls']));

            // Build follow up messages including tool results
            $tool_calls = $choice['message']['tool_calls'];
            $follow_up_messages = $request_body['messages'];

            // Add assistant message with the tool calls so the model has context
            $follow_up_messages[] = array(
                'role' => 'assistant',
                'tool_calls' => $tool_calls
            );

            foreach ($tool_calls as $tool_call) {
                $function_name = $tool_call['function']['name'];
                $arguments = json_decode($tool_call['function']['arguments'], true);
                $result = AIChatbot_Tools::execute_function($function_name, $arguments ?: array());

                // Append tool response message
                $follow_up_messages[] = array(
                    'role' => 'tool',
                    'tool_call_id' => $tool_call['id'],
                    'content' => json_encode($result)
                );
            }

            // Prepare second request to get final response after tools executed
            $follow_up_body = array(
                'model' => $selected_model,
                'messages' => $follow_up_messages,
                'temperature' => $model_params['temperature'],
                'max_tokens' => $model_params['max_tokens']
            );

            if (!empty($functions) && get_option('aichatbot_woocommerce_enabled')) {
                $follow_up_body['tools'] = $functions;
                $follow_up_body['tool_choice'] = 'auto';
            }

            $second_response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode($follow_up_body),
                'timeout' => $model_params['timeout']
            ));

            if (!is_wp_error($second_response)) {
                $second_body = wp_remote_retrieve_body($second_response);
                $second_data = json_decode($second_body, true);
                if (isset($second_data['choices'][0]['message']['content'])) {
                    $response_text = $second_data['choices'][0]['message']['content'];
                }
            }
        }

        $response_text = trim($response_text);
        
        // Generate a response ID for consistency
        $response_id = 'chat_' . uniqid() . '.' . microtime(true);
        
        error_log('AIChatbot: Response ID: ' . $response_id);
        error_log('AIChatbot: AI response text: ' . $response_text);
        
        // Return both the response text and the response ID for next conversation
        return array(
            'response' => $response_text,
            'response_id' => $response_id
        );
    }
    
    /**
     * Simple test method that bypasses function calling
     */
    public static function test_simple_api_call($message = 'Hello, test message') {
        $api_key = get_option('aichatbot_openai_key');
        if (empty($api_key)) {
            error_log('AIChatbot: Test - API key not configured');
            return false;
        }
        
        $selected_model = get_option('aichatbot_openai_model', 'gpt-3.5-turbo');
        error_log('AIChatbot: Test - Simple API call with model: ' . $selected_model);
        
        // Simple request without function calling
        $request_body = array(
            'model' => $selected_model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a helpful assistant. Respond briefly.'
                ),
                array('role' => 'user', 'content' => $message)
            ),
            'max_tokens' => 100,
            'temperature' => 0.7
        );
        
        error_log('AIChatbot: Test - Request body: ' . json_encode($request_body));
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($request_body),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('AIChatbot: Test - API error: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('AIChatbot: Test - Response code: ' . $response_code);
        error_log('AIChatbot: Test - Response body: ' . $body);
        
        if ($response_code !== 200) {
            error_log('AIChatbot: Test - Non-200 status code: ' . $response_code);
            return false;
        }
        
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            $response_text = trim($data['choices'][0]['message']['content']);
            error_log('AIChatbot: Test - Success response: ' . $response_text);
            return $response_text;
        } else {
            error_log('AIChatbot: Test - Failed to parse response');
            return false;
        }
    }
}