<?php
/**
 * OpenAI API integration class with function calling support
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIChatbot_OpenAI {
    
    /**
     * Get response from OpenAI using the Responses API
     */
    public static function get_response($message, $previous_response_id = null) {
        error_log('AIChatbot: get_response() called with message: ' . $message . ', previous_response_id: ' . ($previous_response_id ?: 'none'));
        
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

        // Prepare input for Responses API
        $input = array(
            array(
                'role'    => 'user',
                'content' => $message
            )
        );

        // Prepare request body for /responses endpoint
        $request_body = array(
            'model'            => $selected_model,
            'input'            => $input,
            'temperature'     => $model_params['temperature'],
            'max_output_tokens' => $model_params['max_tokens']
        );

        if (empty($previous_response_id)) {
            $request_body['instructions'] = 'You are a helpful AI assistant for a WordPress website with WooCommerce integration. You can help users with their orders, products, and general questions. Be friendly and helpful.';
        } else {
            $request_body['previous_response_id'] = $previous_response_id;
        }

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

        // Make API call to /responses endpoint
        $response = wp_remote_post('https://api.openai.com/v1/responses', array(
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

        if (!isset($data['id'])) {
            error_log('AIChatbot: Failed to parse OpenAI response - missing id');
            return false;
        }

        $response_text = '';
        if (!empty($data['output_text'])) {
            $response_text = $data['output_text'];
        } elseif (isset($data['output'][0]['content'][0]['text'])) {
            $response_text = $data['output'][0]['content'][0]['text'];
        }

        $tool_calls = array();
        if (isset($data['output'][0]['content'])) {
            foreach ($data['output'][0]['content'] as $content_item) {
                if (isset($content_item['type']) && $content_item['type'] === 'tool_calls' && !empty($content_item['tool_calls'])) {
                    $tool_calls = $content_item['tool_calls'];
                    break;
                }
            }
        }

        $response_id = $data['id'];

        // Handle tool calls if present
        if (!empty($tool_calls)) {
            error_log('AIChatbot: Tool calls detected: ' . json_encode($tool_calls));

            $tool_inputs = array();
            foreach ($tool_calls as $tool_call) {
                $function_name = $tool_call['function']['name'];
                $arguments = json_decode($tool_call['function']['arguments'], true);
                $result = AIChatbot_Tools::execute_function($function_name, $arguments ?: array());

                $tool_inputs[] = array(
                    'role' => 'tool',
                    'tool_call_id' => $tool_call['id'],
                    'content' => json_encode($result)
                );
            }

            $follow_up_body = array(
                'model' => $selected_model,
                'input' => $tool_inputs,
                'previous_response_id' => $data['id'],
                'temperature' => $model_params['temperature'],
                'max_output_tokens' => $model_params['max_tokens']
            );

            if (!empty($functions) && get_option('aichatbot_woocommerce_enabled')) {
                $follow_up_body['tools'] = $functions;
                $follow_up_body['tool_choice'] = 'auto';
            }

            $second_response = wp_remote_post('https://api.openai.com/v1/responses', array(
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
                if (isset($second_data['output_text'])) {
                    $response_text = $second_data['output_text'];
                } elseif (isset($second_data['output'][0]['content'][0]['text'])) {
                    $response_text = $second_data['output'][0]['content'][0]['text'];
                }
                $response_id = $second_data['id'] ?? $response_id;
            }
        }

        $response_text = trim($response_text);

        error_log('AIChatbot: Response ID: ' . $response_id);
        error_log('AIChatbot: AI response text: ' . $response_text);

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