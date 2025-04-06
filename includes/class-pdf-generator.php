<?php

namespace MyPDFPlugin;

/**
 * PDF Generator Class
 *
 * @package My_PDF_Plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TCPDF;
use WP_Error;
use WP_Post;
use Exception;

/**
 * PDF Generator class.
 *
 * Handles the generation of PDF documents from WordPress post content.
 *
 * @since 1.0.0
 */
class PDF_Generator {

	/**
	 * TCPDF instance.
	 *
	 * @var TCPDF
	 */
	private $pdf;

	/**
	 * Default PDF options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Error messages.
	 *
	 * @var WP_Error
	 */
	private $errors;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->errors = new WP_Error();
		$this->set_default_options();
	}

	/**
	 * Set default PDF options.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function set_default_options() {
		$this->options = array(
			'title'            => get_bloginfo( 'name' ),
			'author'           => get_bloginfo( 'name' ),
			'creator'          => 'My PDF Plugin',
			'subject'          => '',
			'keywords'         => '',
			'page_orientation' => 'P', // P for Portrait, L for Landscape.
			'unit'             => 'mm',
			'page_format'      => 'A4',
			'unicode'          => true,
			'encoding'         => 'UTF-8',
			'font'             => 'dejavusans',
			'font_size'        => 10,
			'header_logo'      => '',
			'header_title'     => get_bloginfo( 'name' ),
			'footer_text'      => get_bloginfo( 'url' ),
		);
	}

	/**
	 * Initialize TCPDF library.
	 *
	 * @since 1.0.0
	 * @return boolean True on success, false on failure.
	 */
	public function init_tcpdf() {
		// Check if TCPDF is already available.
		if ( ! class_exists( 'TCPDF' ) ) {
			// Try to load TCPDF from our plugin directory.
			$tcpdf_path = plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/tecnickcom/tcpdf/tcpdf.php';
			
			if ( file_exists( $tcpdf_path ) ) {
				require_once $tcpdf_path;
			} else {
				$this->errors->add( 'tcpdf_missing', __( 'TCPDF library is not available.', 'my-pdf-plugin' ) );
				return false;
			}
		}

		try {
			// Create new TCPDF instance.
			$this->pdf = new TCPDF(
				$this->options['page_orientation'],
				$this->options['unit'],
				$this->options['page_format'],
				$this->options['unicode'],
				$this->options['encoding']
			);

			// Set document information.
			$this->pdf->SetCreator( $this->options['creator'] );
			$this->pdf->SetAuthor( $this->options['author'] );
			$this->pdf->SetTitle( $this->options['title'] );
			$this->pdf->SetSubject( $this->options['subject'] );
			$this->pdf->SetKeywords( $this->options['keywords'] );

			// Remove default header/footer.
			$this->pdf->setPrintHeader( false );
			$this->pdf->setPrintFooter( true );

			// Set default font.
			$this->pdf->SetFont( $this->options['font'], '', $this->options['font_size'] );

			// Set margins.
			$this->pdf->SetMargins( 15, 15, 15 );

			// Set auto page breaks.
			$this->pdf->SetAutoPageBreak( true, 15 );

			return true;
		} catch ( Exception $e ) {
			$this->errors->add( 'tcpdf_init_error', $e->getMessage() );
			return false;
		}
	}

	/**
	 * Set PDF options.
	 *
	 * @since 1.0.0
	 * @param array $options Array of options to override defaults.
	 * @return void
	 */
	public function set_options( $options = array() ) {
		$this->options = wp_parse_args( $options, $this->options );
	}

	/**
	 * Generate PDF from post content.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Post ID to generate PDF from.
	 * @param string $output  The output destination: 'D' for download, 'I' for inline, 'F' for file, 'S' for string.
	 * @param string $file    File path for saving when $output is 'F'.
	 * @return mixed PDF content as string or true on success, WP_Error on failure.
	 */
	public function generate_from_post( $post_id, $output = 'I', $file = '' ) {
		// Verify post ID.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'invalid_post', __( 'Invalid post ID.', 'my-pdf-plugin' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'read_post', $post_id ) ) {
			return new WP_Error( 'permission_denied', __( 'You do not have permission to view this post.', 'my-pdf-plugin' ) );
		}

		// Initialize TCPDF.
		if ( ! $this->init_tcpdf() ) {
			return $this->errors;
		}

		try {
			// Set PDF title to post title.
			$this->pdf->SetTitle( get_the_title( $post ) );
			
			// Add a page.
			$this->pdf->AddPage();

			// Set PDF content with post title and content.
			$title = '<h1 style="text-align: center;">' . esc_html( get_the_title( $post ) ) . '</h1>';
			
			// Apply filters to the content.
			$content = apply_filters( 'the_content', $post->post_content );
			$content = $this->prepare_content_for_pdf( $content );
			
			// Add post meta information.
			$post_date = get_the_date( '', $post );
			$author = get_the_author_meta( 'display_name', $post->post_author );
			
			$meta = '<div style="margin-bottom: 20px; font-style: italic; text-align: center;">';
			$meta .= sprintf( __( 'Published on %s by %s', 'my-pdf-plugin' ), $post_date, $author );
			$meta .= '</div>';
			
			// Set footer callback.
			$this->pdf->setFooterCallback( function( $pdf ) {
				$pdf->SetY( -15 );
				$pdf->SetFont( 'helvetica', 'I', 8 );
				$pdf->Cell( 0, 10, $this->options['footer_text'] . ' | ' . __( 'Page', 'my-pdf-plugin' ) . ' ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M' );
			} );
			
			// Write HTML content to PDF.
			$this->pdf->writeHTML( $title . $meta . $content, true, false, true, false, '' );
			
			// Close and output PDF document.
			switch ( $output ) {
				case 'D': // Download.
					$filename = sanitize_file_name( get_the_title( $post ) ) . '.pdf';
					return $this->pdf->Output( $filename, 'D' );
				
				case 'F': // File.
					if ( empty( $file ) ) {
						$file = WP_CONTENT_DIR . '/uploads/pdfs/' . sanitize_file_name( get_the_title( $post ) ) . '.pdf';
						
						// Create directory if it doesn't exist.
						$dir = dirname( $file );
						if ( ! file_exists( $dir ) ) {
							wp_mkdir_p( $dir );
						}
					}
					return $this->pdf->Output( $file, 'F' );
				
				case 'S': // String.
					return $this->pdf->Output( '', 'S' );
				
				case 'I': // Inline (default).
				default:
					$filename = sanitize_file_name( get_the_title( $post ) ) . '.pdf';
					return $this->pdf->Output( $filename, 'I' );
			}
		} catch ( Exception $e ) {
			return new WP_Error( 'pdf_generation_error', $e->getMessage() );
		}
	}

	/**
	 * Prepare HTML content for PDF generation.
	 *
	 * Clean up HTML to make it more compatible with TCPDF.
	 *
	 * @since 1.0.0
	 * @param string $content HTML content to prepare.
	 * @return string Prepared HTML content.
	 */
	private function prepare_content_for_pdf( $content ) {
		// Replace relative URLs with absolute URLs for images.
		$content = preg_replace_callback(
			'/<img([^>]+)src=(["\'])([^"\']+)(["\'])([^>]*)>/i',
			function( $matches ) {
				$src = $matches[3];
				if ( strpos( $src, 'http' ) !== 0 ) {
					$src = site_url( $src );
				}
				return '<img' . $matches[1] . 'src=' . $matches[2] . $src . $matches[4] . $matches[5] . '>';
			},
			$content
		);

		// Add some basic styling.
		$content = '<div style="font-family: ' . $this->options['font'] . '; font-size: ' . $this->options['font_size'] . 'pt;">' . $content . '</div>';

		return $content;
	}

	/**
	 * Get any error messages.
	 *
	 * @since 1.0.0
	 * @return WP_Error WP_Error object with any error messages.
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Check if there are any errors.
	 *
	 * @since 1.0.0
	 * @return boolean True if there are errors, false otherwise.
	 */
	public function has_errors() {
		return $this->errors->has_errors();
	}
}
