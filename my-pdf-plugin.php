<?php
/**
 * My PDF Plugin
 *
 * @package           MyPDFPlugin
 * @author            Álvaro Solís Pascual
 * @copyright         2023 Álvaro Solís Pascual
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       My PDF Plugin
 * Description:       A plugin to generate PDF from post content
 * Version:           1.0.0
 * Author:            Álvaro Solís Pascual
 * Text Domain:       my-pdf-plugin
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 5.2
 * Requires PHP:      7.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin activation hook.
 *
 * This function runs when the plugin is activated.
 */
function my_pdf_plugin_activate() {
    // Add activation tasks here (e.g., create database tables, add options).
    // Flush rewrite rules.
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'my_pdf_plugin_activate' );

/**
 * Plugin deactivation hook.
 *
 * This function runs when the plugin is deactivated.
 */
function my_pdf_plugin_deactivate() {
    // Add deactivation tasks here (e.g., remove temporary data).
    // Flush rewrite rules.
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'my_pdf_plugin_deactivate' );

/**
 * Main plugin class.
 */
class My_PDF_Plugin {
    /**
     * Constructor.
     */
    public function __construct() {
        // Define plugin constants.
        $this->define_constants();
        
        // Include required files.
        $this->includes();
        
        // Initialize hooks.
        $this->init_hooks();
    }

    /**
     * Define plugin constants.
     */
    private function define_constants() {
        define( 'MY_PDF_PLUGIN_VERSION', '1.0.0' );
        define( 'MY_PDF_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
        define( 'MY_PDF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        define( 'MY_PDF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
    }

    /**
     * Include required files.
     */
    private function includes() {
        // Include core classes
        require_once MY_PDF_PLUGIN_PATH . 'includes/class-pdf-generator.php';
        require_once MY_PDF_PLUGIN_PATH . 'includes/class-frontend-handler.php';

        // Check if TCPDF is available (to be added with Composer later)
        $tcpdf_available = false;
        if ( file_exists( MY_PDF_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
            require_once MY_PDF_PLUGIN_PATH . 'vendor/autoload.php';
            $tcpdf_available = class_exists( 'TCPDF' );
        }

        // Initialize components if dependencies are met
        if ( $tcpdf_available ) {
            // Initialize PDF Generator
            $pdf_generator = new \MyPDFPlugin\PDF_Generator();
            
            // Initialize Frontend Handler
            $frontend_handler = new \MyPDFPlugin\Frontend_Handler( $pdf_generator );
        } else {
            // Add admin notice if TCPDF is not available
            add_action( 'admin_notices', array( $this, 'tcpdf_missing_notice' ) );
        }
    }

    /**
     * Display admin notice for missing TCPDF dependency.
     */
    public function tcpdf_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'My PDF Plugin requires TCPDF library. Please install it using Composer.', 'my-pdf-plugin' ); ?></p>
        </div>
        <?php
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Add actions and filters here.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'init', array( $this, 'init' ) );
    }

    /**
     * Enqueue scripts and styles.
     */
    public function enqueue_scripts() {
        // Enqueue frontend scripts and styles here.
    }

    /**
     * Initialize plugin.
     */
    public function init() {
        // Load text domain for internationalization.
        load_plugin_textdomain( 'my-pdf-plugin', false, dirname( MY_PDF_PLUGIN_BASENAME ) . '/languages' );
        
        // Other initialization tasks.
    }
}

/**
 * Initialize the plugin.
 */
function my_pdf_plugin_init() {
    return new My_PDF_Plugin();
}

// Start the plugin.
my_pdf_plugin_init();

