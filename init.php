<?php
/**
 * Registration logic for the Page Relationship field type.
 *
 * @package ACF Page Relationship Field
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'wr_include_acf_field_page_relationship' );

/**
 * Registers the ACF field type.
 */
function wr_include_acf_field_page_relationship() {
	if ( ! function_exists( 'acf_register_field_type' ) ) {
		return;
	}

	require_once __DIR__ . '/class-wr-acf-field-page-relationship.php';

	acf_register_field_type( 'WP_ACF_Field_Page_Relationship' );
}
