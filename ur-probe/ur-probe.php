<?php
/**
 * Plugin Name: UR-Probe
 * Plugin URI: https://example.com/ur-probe
 * Description: Health check endpoint that verifies MySQL connection and WordPress status. Outputs OK or ERR.
 * Version: 1.0.2
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ur-probe
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

class UR_Probe {
    
    private static $instance = null;
    private $option_name = 'ur_probe_settings';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('init', array($this, 'register_probe_endpoint'));
        add_action('template_redirect', array($this, 'handle_probe_request'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function activate() {
        $default_options = array(
            'probe_path' => 'ur-probe'
        );
        
        if (!get_option($this->option_name)) {
            add_option($this->option_name, $default_options);
        }
        
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function add_admin_menu() {
        add_options_page(
            __('UR-Probe Settings', 'ur-probe'),
            __('UR-Probe', 'ur-probe'),
            'manage_options',
            'ur-probe',
            array($this, 'render_settings_page')
        );
    }
    
    public function register_settings() {
        register_setting(
            'ur_probe_settings_group',
            $this->option_name,
            array($this, 'sanitize_settings')
        );
        
        add_settings_section(
            'ur_probe_main_section',
            __('Probe Configuration', 'ur-probe'),
            array($this, 'render_section_description'),
            'ur-probe'
        );
        
        add_settings_field(
            'probe_path',
            __('Probe Path', 'ur-probe'),
            array($this, 'render_path_field'),
            'ur-probe',
            'ur_probe_main_section'
        );
    }
    
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['probe_path'])) {
            $sanitized['probe_path'] = sanitize_title($input['probe_path']);
            if (empty($sanitized['probe_path'])) {
                $sanitized['probe_path'] = 'ur-probe';
            }
        }
        
        flush_rewrite_rules();
        
        return $sanitized;
    }
    
    public function render_section_description() {
        echo '<p>' . esc_html__('Configure the URL path where the health check probe will be accessible.', 'ur-probe') . '</p>';
    }
    
    public function render_path_field() {
        $options = get_option($this->option_name);
        $path = isset($options['probe_path']) ? $options['probe_path'] : 'ur-probe';
        $full_url = home_url('/' . $path . '/');
        
        echo '<input type="text" name="' . esc_attr($this->option_name) . '[probe_path]" value="' . esc_attr($path) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('The URL path for the probe endpoint (without slashes).', 'ur-probe') . '</p>';
        echo '<p class="description">' . esc_html__('Full URL:', 'ur-probe') . ' <code>' . esc_url($full_url) . '</code></p>';
    }
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_GET['settings-updated'])) {
            add_settings_error('ur_probe_messages', 'ur_probe_message', __('Settings Saved. Rewrite rules flushed.', 'ur-probe'), 'updated');
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php settings_errors('ur_probe_messages'); ?>
            <form action="options.php" method="post">
                <?php
                settings_fields('ur_probe_settings_group');
                do_settings_sections('ur-probe');
                submit_button(__('Save Settings', 'ur-probe'));
                ?>
            </form>
            
            <hr />
            <h2><?php esc_html_e('Test Probe', 'ur-probe'); ?></h2>
            <p>
                <?php
                $options = get_option($this->option_name);
                $path = isset($options['probe_path']) ? $options['probe_path'] : 'ur-probe';
                $probe_url = home_url('/' . $path . '/');
                ?>
                <a href="<?php echo esc_url($probe_url); ?>" target="_blank" class="button button-secondary">
                    <?php esc_html_e('Open Probe URL', 'ur-probe'); ?>
                </a>
            </p>
            
            <h2><?php esc_html_e('Current Status', 'ur-probe'); ?></h2>
            <p>
                <?php
                $status = $this->check_health();
                $status_text = $status ? 'OK' : 'ERR';
                $status_class = $status ? 'notice-success' : 'notice-error';
                ?>
                <span class="notice <?php echo esc_attr($status_class); ?>" style="display: inline-block; padding: 5px 10px;">
                    <strong><?php echo esc_html($status_text); ?></strong>
                </span>
            </p>
        </div>
        <?php
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'ur_probe';
        return $vars;
    }
    
    public function register_probe_endpoint() {
        $options = get_option($this->option_name);
        $path = isset($options['probe_path']) ? $options['probe_path'] : 'ur-probe';
        
        add_rewrite_rule(
            '^' . preg_quote($path, '/') . '/?$',
            'index.php?ur_probe=1',
            'top'
        );
    }
    
    public function handle_probe_request() {
        if (!get_query_var('ur_probe')) {
            return;
        }
        
        $status = $this->check_health();
        
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        if ($status) {
            echo 'OK';
        } else {
            http_response_code(503);
            echo 'ERR';
        }
        
        exit;
    }
    
    private function check_health() {
        global $wpdb;
        
        // Check MySQL connection
        if (!$wpdb || !$wpdb->check_connection(false)) {
            return false;
        }
        
        // Test a simple query
        $result = $wpdb->get_var("SELECT 1");
        if ($result !== '1') {
            return false;
        }
        
        // Check if WordPress is properly loaded
        if (!function_exists('get_bloginfo') || !get_bloginfo('name')) {
            return false;
        }
        
        // Check if we can access the options table
        $test_option = get_option('siteurl');
        if (empty($test_option)) {
            return false;
        }
        
        return true;
    }
}

// Initialize the plugin
UR_Probe::get_instance();
