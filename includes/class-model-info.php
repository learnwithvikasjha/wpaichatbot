<?php
/**
 * OpenAI Model Information Helper Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIChatbot_Model_Info {
    
    /**
     * Get all available models with detailed information
     */
    public static function get_available_models() {
        return array(
            'gpt-4o' => array(
                'name' => 'GPT-4o',
                'description' => 'Latest and most capable model',
                'capabilities' => array(
                    'Best reasoning and analysis',
                    'Handles complex queries',
                    'Most accurate responses',
                    'Supports vision and audio (if needed)'
                ),
                'max_tokens' => 128000,
                'cost_per_1k_tokens' => '$0.005 (input) / $0.015 (output)',
                'speed' => 'Fast',
                'best_for' => 'Complex customer service, detailed product analysis'
            ),
            'gpt-4o-mini' => array(
                'name' => 'GPT-4o mini',
                'description' => 'Fast and efficient model',
                'capabilities' => array(
                    'Good reasoning capabilities',
                    'Fast response times',
                    'Cost-effective for most use cases',
                    'Handles moderate complexity'
                ),
                'max_tokens' => 128000,
                'cost_per_1k_tokens' => '$0.00015 (input) / $0.0006 (output)',
                'speed' => 'Very Fast',
                'best_for' => 'General customer service, quick responses'
            ),
            'gpt-4-turbo' => array(
                'name' => 'GPT-4 Turbo',
                'description' => 'Previous generation, still powerful',
                'capabilities' => array(
                    'Strong reasoning abilities',
                    'Good for complex tasks',
                    'Reliable performance',
                    'Wide knowledge base'
                ),
                'max_tokens' => 128000,
                'cost_per_1k_tokens' => '$0.01 (input) / $0.03 (output)',
                'speed' => 'Fast',
                'best_for' => 'Detailed product recommendations, complex queries'
            ),
            'gpt-4' => array(
                'name' => 'GPT-4',
                'description' => 'Standard GPT-4 model',
                'capabilities' => array(
                    'Excellent reasoning',
                    'High accuracy',
                    'Good for complex analysis',
                    'Reliable performance'
                ),
                'max_tokens' => 8192,
                'cost_per_1k_tokens' => '$0.03 (input) / $0.06 (output)',
                'speed' => 'Moderate',
                'best_for' => 'Complex customer inquiries, detailed analysis'
            ),
            'gpt-3.5-turbo' => array(
                'name' => 'GPT-3.5 Turbo',
                'description' => 'Fast and cost-effective',
                'capabilities' => array(
                    'Fast response times',
                    'Good for simple queries',
                    'Cost-effective',
                    'Reliable for basic tasks'
                ),
                'max_tokens' => 16385,
                'cost_per_1k_tokens' => '$0.0005 (input) / $0.0015 (output)',
                'speed' => 'Very Fast',
                'best_for' => 'Basic customer service, simple product questions'
            ),
            'gpt-3.5-turbo-16k' => array(
                'name' => 'GPT-3.5 Turbo 16K',
                'description' => 'Extended context version',
                'capabilities' => array(
                    'Longer conversations',
                    'More context handling',
                    'Good for complex conversations',
                    'Cost-effective for long chats'
                ),
                'max_tokens' => 16385,
                'cost_per_1k_tokens' => '$0.003 (input) / $0.004 (output)',
                'speed' => 'Fast',
                'best_for' => 'Long customer conversations, detailed product discussions'
            )
        );
    }
    
    /**
     * Get recommended model based on use case
     */
    public static function get_recommended_model($use_case = 'general') {
        $recommendations = array(
            'general' => 'gpt-4o-mini',
            'cost_effective' => 'gpt-3.5-turbo',
            'best_performance' => 'gpt-4o',
            'long_conversations' => 'gpt-3.5-turbo-16k',
            'complex_analysis' => 'gpt-4o',
            'fast_responses' => 'gpt-4o-mini'
        );
        
        return isset($recommendations[$use_case]) ? $recommendations[$use_case] : 'gpt-4o-mini';
    }
    
    /**
     * Get model comparison table for admin
     */
    public static function get_model_comparison_table() {
        $models = self::get_available_models();
        $html = '<table class="widefat" style="margin-top: 10px;">';
        $html .= '<thead><tr><th>Model</th><th>Best For</th><th>Speed</th><th>Cost (per 1K tokens)</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($models as $model_id => $model_info) {
            $html .= '<tr>';
            $html .= '<td><strong>' . esc_html($model_info['name']) . '</strong><br><small>' . esc_html($model_info['description']) . '</small></td>';
            $html .= '<td>' . esc_html($model_info['best_for']) . '</td>';
            $html .= '<td>' . esc_html($model_info['speed']) . '</td>';
            $html .= '<td>' . esc_html($model_info['cost_per_1k_tokens']) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        return $html;
    }
    
    /**
     * Get optimal parameters for a model
     */
    public static function get_model_parameters($model_id) {
        $parameters = array(
            'gpt-4o' => array(
                'max_tokens' => 500,
                'temperature' => 0.7,
                'timeout' => 30
            ),
            'gpt-4o-mini' => array(
                'max_tokens' => 400,
                'temperature' => 0.7,
                'timeout' => 25
            ),
            'gpt-4-turbo' => array(
                'max_tokens' => 500,
                'temperature' => 0.7,
                'timeout' => 30
            ),
            'gpt-4' => array(
                'max_tokens' => 400,
                'temperature' => 0.7,
                'timeout' => 35
            ),
            'gpt-3.5-turbo' => array(
                'max_tokens' => 300,
                'temperature' => 0.7,
                'timeout' => 20
            ),
            'gpt-3.5-turbo-16k' => array(
                'max_tokens' => 400,
                'temperature' => 0.7,
                'timeout' => 25
            )
        );
        
        return isset($parameters[$model_id]) ? $parameters[$model_id] : $parameters['gpt-3.5-turbo'];
    }
}
