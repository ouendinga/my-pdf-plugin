<?php
/**
 * PDF Template for post content
 *
 * This template is used to format WordPress post content for PDF generation.
 *
 * @package MyPDFPlugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<title><?php the_title(); ?> - <?php bloginfo( 'name' ); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
		/* Basic PDF styling */
		body {
			font-family: 'Helvetica', 'Arial', sans-serif;
			line-height: 1.5;
			font-size: 12pt;
			color: #333;
			margin: 0;
			padding: 20px;
		}
		.pdf-header {
			margin-bottom: 30px;
			padding-bottom: 10px;
			border-bottom: 1px solid #ddd;
		}
		.pdf-header h1 {
			font-size: 24pt;
			margin: 0 0 10px 0;
			color: #222;
		}
		.pdf-content {
			margin-bottom: 40px;
		}
		.pdf-content img {
			max-width: 100%;
			height: auto;
		}
		.pdf-meta {
			font-size: 10pt;
			color: #666;
			margin-bottom: 20px;
		}
		.pdf-footer {
			position: fixed;
			bottom: 0;
			left: 0;
			right: 0;
			height: 50px;
			text-align: center;
			font-size: 9pt;
			color: #666;
			border-top: 1px solid #ddd;
			padding-top: 10px;
		}
		.pdf-footer .page-number:after {
			content: counter(page);
		}
		/* Add page breaks where needed */
		.page-break {
			page-break-after: always;
		}
		/* Ensure links are visible in PDF */
		a {
			color: #0073aa;
			text-decoration: underline;
		}
	</style>
</head>
<body>
	<div class="pdf-header">
		<h1><?php the_title(); ?></h1>
		<div class="pdf-meta">
			<?php esc_html_e( 'Published on', 'my-pdf-plugin' ); ?>: <?php the_date(); ?> 
			<?php esc_html_e( 'by', 'my-pdf-plugin' ); ?> <?php the_author(); ?>
		</div>
	</div>

	<div class="pdf-content">
		<?php the_content(); ?>
	</div>

	<div class="pdf-footer">
		<div class="site-info">
			<?php bloginfo( 'name' ); ?> | <?php bloginfo( 'url' ); ?>
		</div>
		<div class="page-number">
			<?php esc_html_e( 'Page', 'my-pdf-plugin' ); ?> <span class="page-number"></span>
		</div>
	</div>
</body>
</html>

