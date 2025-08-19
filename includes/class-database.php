<?php
/**
 * Database operations class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIChatbot_Database {
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aichatbot_messages';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_name varchar(100) NOT NULL,
            user_id varchar(50) NOT NULL,
            user_email varchar(100) NOT NULL,
            message text NOT NULL,
            message_type varchar(20) NOT NULL DEFAULT 'user_input',
            context_sent text,
            openai_response text,
            response_id varchar(100),
            session_id varchar(100) NOT NULL,
            ip_address varchar(45),
            user_agent text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY timestamp (timestamp),
            KEY response_id (response_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('AIChatbot: Table created/updated: ' . $table_name);
    }
    
    public static function check_and_upgrade_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aichatbot_messages';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            // Table doesn't exist, create it
            self::create_tables();
            return;
        }
        
        // Get current table structure
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $column_names = array();
        foreach ($columns as $column) {
            $column_names[] = $column->Field;
        }
        
        $updates_made = false;
        
        // Check and add missing columns
        if (!in_array('message_type', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN message_type ENUM('user_input', 'ai_response') NOT NULL DEFAULT 'user_input'");
            $updates_made = true;
            error_log('AIChatbot: Added message_type column');
        }
        
        if (!in_array('context_sent', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN context_sent TEXT NULL");
            $updates_made = true;
            error_log('AIChatbot: Added context_sent column');
        }
        
        if (!in_array('openai_response', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN openai_response TEXT NULL");
            $updates_made = true;
            error_log('AIChatbot: Added openai_response column');
        }
        
        if (!in_array('session_id', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN session_id VARCHAR(50) NULL");
            $updates_made = true;
            error_log('AIChatbot: Added session_id column');
        }
        
        if (!in_array('ip_address', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN ip_address VARCHAR(45) NULL");
            $updates_made = true;
            error_log('AIChatbot: Added ip_address column');
        }
        
        if (!in_array('user_agent', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN user_agent TEXT NULL");
            $updates_made = true;
            error_log('AIChatbot: Added user_agent column');
        }
        
        if (!in_array('user_id', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN user_id VARCHAR(50) NULL DEFAULT '0'");
            $updates_made = true;
            error_log('AIChatbot: Added user_id column');
        } else {
            // Check if user_id column needs to be updated to VARCHAR
            $column_info = $wpdb->get_row("SHOW COLUMNS FROM $table_name LIKE 'user_id'");
            if ($column_info && $column_info->Type === 'int(11)') {
                $wpdb->query("ALTER TABLE $table_name MODIFY COLUMN user_id VARCHAR(50) NULL DEFAULT '0'");
                error_log('AIChatbot: Updated user_id column to VARCHAR for guest support');
            }
        }
        
        if (!in_array('user_email', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN user_email VARCHAR(100) NULL");
            $updates_made = true;
            error_log('AIChatbot: Added user_email column');
        }
        
        // Add indexes if they don't exist
        $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name");
        $index_names = array();
        foreach ($indexes as $index) {
            $index_names[] = $index->Key_name;
        }
        
        if (!in_array('session_id', $index_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX session_id (session_id)");
            error_log('AIChatbot: Added session_id index');
        }
        
        if (!in_array('message_type', $index_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX message_type (message_type)");
            error_log('AIChatbot: Added message_type index');
        }
        
        if (!in_array('timestamp', $index_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX timestamp (timestamp)");
            error_log('AIChatbot: Added timestamp index');
        }
        
        if (!in_array('user_id', $index_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX user_id (user_id)");
            error_log('AIChatbot: Added user_id index');
        }
        
        if ($updates_made) {
            error_log('AIChatbot: Database table upgraded automatically');
        }
    }
    
    /**
     * Insert a message into the database
     */
    public static function insert_message($user_name, $user_id, $user_email, $message, $message_type = 'user_input', $context_sent = null, $openai_response = null, $response_id = null, $session_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aichatbot_messages';
        
        // Generate session ID if not provided
        if (!$session_id) {
            $session_id = 'chat_' . uniqid() . '.' . microtime(true);
        }
        
        // Get client IP
        $ip_address = self::get_client_ip();
        
        // Get user agent
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_name' => $user_name,
                'user_id' => $user_id,
                'user_email' => $user_email,
                'message' => $message,
                'message_type' => $message_type,
                'context_sent' => $context_sent,
                'openai_response' => $openai_response,
                'response_id' => $response_id,
                'session_id' => $session_id,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('AIChatbot: Failed to insert message into database: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    public static function get_client_ip() {
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
    
    public static function get_messages($limit = 20) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aichatbot_messages';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$table_exists) {
            error_log('AIChatbot: Table does not exist for get_messages');
            return array();
        }
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d",
                $limit
            )
        );
        
        if ($wpdb->last_error) {
            error_log('AIChatbot: Database select error: ' . $wpdb->last_error);
        }
        
        return $results ? $results : array();
    }
    
    public static function get_chat_history($limit = 50, $offset = 0, $filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aichatbot_messages';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$table_exists) {
            return array();
        }
        
        $where_clauses = array();
        $where_values = array();
        
        // Apply filters
        if (!empty($filters['user_name'])) {
            $where_clauses[] = "user_name LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like($filters['user_name']) . '%';
        }
        
        if (!empty($filters['message_type'])) {
            $where_clauses[] = "message_type = %s";
            $where_values[] = $filters['message_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = "DATE(timestamp) >= %s";
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = "DATE(timestamp) <= %s";
            $where_values[] = $filters['date_to'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $query = "SELECT * FROM $table_name $where_sql ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        $where_values[] = $limit;
        $where_values[] = $offset;
        
        $results = $wpdb->get_results($wpdb->prepare($query, $where_values));
        
        return $results ? $results : array();
    }
    
    public static function get_chat_history_count($filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aichatbot_messages';
        
        $where_clauses = array();
        $where_values = array();
        
        // Apply filters
        if (!empty($filters['user_name'])) {
            $where_clauses[] = "user_name LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like($filters['user_name']) . '%';
        }
        
        if (!empty($filters['message_type'])) {
            $where_clauses[] = "message_type = %s";
            $where_values[] = $filters['message_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = "DATE(timestamp) >= %s";
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = "DATE(timestamp) <= %s";
            $where_values[] = $filters['date_to'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $query = "SELECT COUNT(*) FROM $table_name $where_sql";
        
        if (!empty($where_values)) {
            $count = $wpdb->get_var($wpdb->prepare($query, $where_values));
        } else {
            $count = $wpdb->get_var($query);
        }
        
        return intval($count);
    }
    
    public static function get_conversation_pairs($limit = 20) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aichatbot_messages';
        
        $query = "
            SELECT 
                u.id as user_message_id,
                u.user_name,
                u.message as user_message,
                u.timestamp as user_timestamp,
                u.context_sent,
                u.openai_response,
                a.id as ai_message_id,
                a.message as ai_message,
                a.timestamp as ai_timestamp
            FROM $table_name u
            LEFT JOIN $table_name a ON (
                a.message_type = 'ai_response' 
                AND a.timestamp > u.timestamp 
                AND a.timestamp <= DATE_ADD(u.timestamp, INTERVAL 5 MINUTE)
            )
            WHERE u.message_type = 'user_input'
            ORDER BY u.timestamp DESC
            LIMIT %d
        ";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $limit));
        
        return $results ? $results : array();
    }
    
    /**
     * Get recent messages for conversation summary
     */
    public static function get_recent_messages($limit = 10) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aichatbot_messages';
        
        $sql = $wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d",
            $limit
        );
        
        $messages = $wpdb->get_results($sql, ARRAY_A);
        
        return $messages ? $messages : array();
    }

    /**
     * Get messages by session ID
     */
    public static function get_messages_by_session($session_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aichatbot_messages';
        
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE session_id = %s 
             ORDER BY timestamp ASC",
            $session_id
        ), ARRAY_A);
        
        return $messages ? $messages : array();
    }
    
    /**
     * Get the last response ID for a session
     */
    public static function get_last_response_id($session_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aichatbot_messages';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT response_id FROM $table_name 
             WHERE session_id = %s AND response_id IS NOT NULL 
             ORDER BY timestamp DESC LIMIT 1",
            $session_id
        ));
        
        return $result;
    }
}