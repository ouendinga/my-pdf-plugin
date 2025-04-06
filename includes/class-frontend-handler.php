<?php

namespace MyPDFPlugin;

/**
 * Frontend Handler
 *
 * @package My_PDF_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Exception;
use WP_Post;

/**
 * Frontend_Handler Class
 *
 * Handles all frontend functionality for the PDF generator plugin.
 *
 * @since 1.0.0
 */
class Frontend_Handler {

	/**
	 * Constructor
	 *
	 * Initialize filters and actions.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		 // Remove the_content filter.
		
		// Register frontend scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// Add metabox for Generate PDF button in the post editor.
		add_action( 'add_meta_boxes', array( $this, 'add_pdf_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Register AJAX handlers.
		add_action( 'wp_ajax_generate_pdf', array( $this, 'handle_generate_pdf' ) );
		add_action( 'wp_ajax_nopriv_generate_pdf', array( $this, 'handle_public_generate_pdf' ) );
	}

	/**
	 * Add PDF Metabox
	 *
	 * Adds a metabox with the "Generate PDF" button to the post editor.
	 *
	 * @since 1.0.0
	 */
	public function add_pdf_metabox() {
		add_meta_box(
			'my_pdf_plugin_metabox',
			__( 'Generate PDF', 'my-pdf-plugin' ),
			array( $this, 'render_pdf_metabox' ),
			array( 'post', 'page' ),
			'side',
			'high'
		);
	}

	/**
	 * Render PDF Metabox
	 *
	 * Outputs the HTML for the "Generate PDF" button in the post editor.
	 *
	 * @since 1.0.0
	 */
	public function render_pdf_metabox( $post ) {
		$nonce = wp_create_nonce( 'my_pdf_plugin_nonce' );
		echo '<button class="generate-pdf-button" data-post-id="' . esc_attr( $post->ID ) . '" data-nonce="' . esc_attr( $nonce ) . '">';
		echo esc_html__( 'Generate PDF', 'my-pdf-plugin' );
		echo '</button>';
		echo '<div class="pdf-loading-indicator"><span class="pdf-spinner"></span> ' . esc_html__( 'Generating PDF...', 'my-pdf-plugin' ) . '</div>';
		echo '<div class="pdf-message"></div>';
	}

	/**
	 * Enqueue Admin Scripts
	 *
	 * Enqueues the necessary scripts and styles for the admin metabox.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'my-pdf-plugin-styles',
			plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/my-pdf-plugin.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'my-pdf-plugin-scripts',
			plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/my-pdf-plugin.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'my-pdf-plugin-scripts',
			'my_pdf_plugin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Enqueue Scripts and Styles
	 *
	 * Register and enqueue the necessary CSS and JavaScript files.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		// Only enqueue on single post/page views.
		if ( ! is_singular( array( 'post', 'page' ) ) ) {
			return;
		}

		// Enqueue the CSS file.
		wp_enqueue_style(
			'my-pdf-plugin-styles',
			plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/my-pdf-plugin.css',
			array(),
			'1.0.0',
			'all'
		);

		// Enqueue the dashicons for the PDF icon.
		wp_enqueue_style( 'dashicons' );

		// Enqueue the JavaScript file.
		wp_enqueue_script(
			'my-pdf-plugin-scripts',
			plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/my-pdf-plugin.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		// Localize script for AJAX and translations.
		wp_localize_script(
			'my-pdf-plugin-scripts',
			'myPdfPlugin',
			array(
				'ajaxurl'             => admin_url( 'admin-ajax.php' ),
				'nonce'               => wp_create_nonce( 'my_pdf_plugin_nonce' ),
				'generating_text'     => __( 'Generating PDF...', 'my-pdf-plugin' ),
				'generated_text'      => __( 'Download PDF', 'my-pdf-plugin' ),
				'error_text'          => __( 'Error generating PDF. Please try again.', 'my-pdf-plugin' ),
			)
		);
	}

	/**
	 * Handle Generate PDF (Authenticated Users)
	 *
	 * Process AJAX requests for generating PDFs for authenticated users.
	 *
	 * @since 1.0.0
	 */
	public function handle_generate_pdf() {
		// Check nonce for security.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'my_pdf_plugin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'my-pdf-plugin' ) ) );
		}

		// Get and validate post ID.
		if ( ! isset( $_POST['post_id'] ) || ! is_numeric( $_POST['post_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'my-pdf-plugin' ) ) );
		}

		$post_id = intval( $_POST['post_id'] );
		$post    = get_post( $post_id );

		// Check if post exists.
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'my-pdf-plugin' ) ) );
		}

		// Check if the post is published and publicly viewable.
		if ( 'publish' !== $post->post_status || ! is_post_publicly_viewable( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'This post is not available for PDF generation.', 'my-pdf-plugin' ) ) );
		}

		try {
			// Generate temporary file URL for the PDF.
			// In a real implementation, this would call the PDF_Generator class.
			$pdf_url = $this->generate_pdf_file( $post );
			
			wp_send_json_success( array(
				'pdf_url' => $pdf_url,
				'message' => __( 'PDF generated successfully.', 'my-pdf-plugin' ),
			) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Handle Generate PDF (Public Access)
	 *
	 * Process AJAX requests for generating PDFs for non-authenticated users.
	 * Only allows PDF generation for public posts.
	 *
	 * @since 1.0.0
	 */
	public function handle_public_generate_pdf() {
		// For non-authenticated users, we'll add the same security checks
		// but only allow PDFs for publicly viewable content.
		$this->handle_generate_pdf();
	}

	/**
	 * Generate PDF File
	 *
	 * Creates a PDF file from the post content.
	 * This is a placeholder that would call the PDF_Generator class in a real implementation.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The post object.
	 * @return string URL to the generated PDF file.
	 */
	private function generate_pdf_file( $post ) {
		// In a real implementation, this would:
		// 1. Create an instance of PDF_Generator
		// 2. Pass the post content to it
		// 3. Generate the PDF file
		// 4. Return the URL to the generated file
		
		// For now, this is just a placeholder that would be replaced by the actual implementation
		if ( ! class_exists( 'MyPDFPlugin\PDF_Generator' ) ) {
			throw new Exception( __( 'PDF Generator is not available.', 'my-pdf-plugin' ) );
		}
		
		// This is a placeholder - in real code, you would instantiate the PDF_Generator and use it
		// $pdf_generator = new PDF_Generator();
		// return $pdf_generator->generate_from_post( $post );
		
		// For demo purposes, return a mock URL
		return esc_url( home_url( '/wp-content/uploads/pdfs/' . $post->ID . '.pdf' ) );
	}
}

// End of file class-frontend-handler.php
