<?php
if (!defined('ABSPATH')) exit;

class AcervoX_Import_History {
    
    private static $table_name = 'acervox_import_history';
    
    public static function init() {
        self::create_table();
    }
    
    private static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            import_type varchar(50) NOT NULL,
            collection_id bigint(20) UNSIGNED,
            total_items int(11) DEFAULT 0,
            imported_items int(11) DEFAULT 0,
            failed_items int(11) DEFAULT 0,
            status varchar(20) DEFAULT 'completed',
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime NULL,
            error_message text,
            log_data longtext,
            user_id bigint(20) UNSIGNED,
            PRIMARY KEY (id),
            KEY collection_id (collection_id),
            KEY user_id (user_id),
            KEY started_at (started_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public static function add($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $defaults = [
            'import_type' => 'csv',
            'collection_id' => null,
            'total_items' => 0,
            'imported_items' => 0,
            'failed_items' => 0,
            'status' => 'completed',
            'error_message' => null,
            'log_data' => null,
            'user_id' => get_current_user_id()
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        if (isset($data['log_data']) && is_array($data['log_data'])) {
            $data['log_data'] = json_encode($data['log_data']);
        }
        
        $wpdb->insert($table_name, $data);
        
        return $wpdb->insert_id;
    }
    
    public static function update($id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        if (isset($data['log_data']) && is_array($data['log_data'])) {
            $data['log_data'] = json_encode($data['log_data']);
        }
        
        $wpdb->update(
            $table_name,
            $data,
            ['id' => $id]
        );
    }
    
    public static function get($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id),
            ARRAY_A
        );
        
        if ($result && !empty($result['log_data'])) {
            $result['log_data'] = json_decode($result['log_data'], true);
        }
        
        return $result;
    }
    
    public static function get_all($args = []) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $defaults = [
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'started_at',
            'order' => 'DESC',
            'import_type' => null,
            'collection_id' => null,
            'status' => null
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = ['1=1'];
        $values = [];
        
        if ($args['import_type']) {
            $where[] = 'import_type = %s';
            $values[] = $args['import_type'];
        }
        
        if ($args['collection_id']) {
            $where[] = 'collection_id = %d';
            $values[] = $args['collection_id'];
        }
        
        if ($args['status']) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY $orderby LIMIT %d OFFSET %d";
        $values[] = $args['per_page'];
        $values[] = $offset;
        
        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        foreach ($results as &$result) {
            if (!empty($result['log_data'])) {
                $result['log_data'] = json_decode($result['log_data'], true);
            }
        }
        
        // Contar total
        $count_query = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";
        if (!empty($values)) {
            $count_values = array_slice($values, 0, -2); // Remove per_page e offset
            if (!empty($count_values)) {
                $count_query = $wpdb->prepare($count_query, $count_values);
            }
        }
        $total = $wpdb->get_var($count_query);
        
        return [
            'items' => $results,
            'total' => (int) $total,
            'pages' => ceil($total / $args['per_page'])
        ];
    }
    
    public static function delete($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        return $wpdb->delete($table_name, ['id' => $id]);
    }
}
