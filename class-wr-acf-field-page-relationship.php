<?php
/**
 * Defines the custom field type class for the page template field type.
 *
 * @package ACF Page Relationship Field
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_ACF_Field_Page_Relationship class.
 */
class WP_ACF_Field_Page_Relationship extends \acf_field {
	/**
	 * Controls field type visibilty in REST requests.
	 *
	 * @var bool
	 */
	public $show_in_rest = true;

	/**
	 * Environment values relating to the theme or plugin.
	 *
	 * @var array $env Plugin or theme context such as 'url' and 'version'.
	 */
	private $env;

	/**
	 * Constructor.
	 */
	public function __construct() {
		/**
		 * Field type reference used in PHP and JS code.
		 *
		 * No spaces. Underscores allowed.
		 */
		$this->name = 'page_relationship';

		/**
		 * Field type label.
		 *
		 * For public-facing UI. May contain spaces.
		 */
		$this->label = __( 'Page relationship', 'acf' );

		/**
		 * The category the field appears within in the field type picker.
		 */
		$this->category = 'relational';

		/**
		 * Field type Description.
		 *
		 * For field descriptions. May contain spaces.
		 */
		$this->description = __( 'Page relationship field, filterable by page template', 'acf' );

		/**
		 * Field type Doc URL.
		 *
		 * For linking to a documentation page. Displayed in the field picker modal.
		 */
		$this->doc_url = '';

		/**
		 * Field type Tutorial URL.
		 *
		 * For linking to a tutorial resource. Displayed in the field picker modal.
		 */
		$this->tutorial_url = '';

		/**
		 * Defaults for your custom user-facing settings for this field type.
		 */
		$this->defaults = array();

		/**
		 * Strings used in JavaScript code.
		 *
		 * Allows JS strings to be translated in PHP and loaded in JS via:
		 *
		 * ```js
		 * const errorMessage = acf._e("page_relationship", "error");
		 * ```
		 */
		$this->l10n = array(
			'error' => __( 'Error! Please enter a higher value', 'acf' ),
		);

		$this->env = array(
			'url'     => site_url( str_replace( ABSPATH, '', __DIR__ ) ), // URL to the acf-page-relationship directory.
			'version' => '1.0', // Replace this with your theme or plugin version constant.
		);

		/**
		 * Field type preview image.
		 *
		 * A preview image for the field type in the picker modal.
		 */
		$this->preview_image = $this->env['url'] . '/assets/images/field-preview-custom.png';

		parent::__construct();
	}

	/**
	 * Settings to display when users configure a field of this type.
	 *
	 * These settings appear on the ACF “Edit Field Group” admin page when
	 * setting up the field.
	 *
	 * @param array $field field configuration array.
	 * @return void
	 */
	public function render_field_settings( $field ) {
		/*
		 * Repeat for each setting you wish to display for this field type.
		 */
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Filter by page template', 'acf' ),
				'instructions' => '',
				'type'         => 'select',
				'name'         => 'page_template',
				'choices'      => $this->get_page_templates(),
				'multiple'     => 1,
				'ui'           => 1,
				'allow_null'   => 1,
				'placeholder'  => __( 'All page templates', 'acf' ),
			)
		);

		// Render a field setting that will tell us if an empty field is allowed or not.
		acf_render_field_setting(
			$field,
			array(
				'label'   => __( 'Allow Null?', 'acf' ),
				'type'    => 'radio',
				'name'    => 'allow_null',
				'choices' => array(
					1 => __( 'Yes', 'acf' ),
					0 => __( 'No', 'acf' ),
				),
				'layout'  => 'horizontal',
			)
		);

		// To render field settings on other tabs in ACF 6.0+:
		// https://www.advancedcustomfields.com/resources/adding-custom-settings-fields/#moving-field-setting
	}

	/**
	 * HTML content to show when a publisher edits the field on the edit screen.
	 *
	 * @param array $field The field settings and values.
	 * @return void
	 */
	public function render_field( $field ) {
		$posts = $this->get_matching_pages( $field['page_template'] );

		$field['type']     = 'select';
		$field['ui']       = 1;
		$field['ajax']     = 1;
		$field['choices']  = array();
		$field['multiple'] = 0;

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				if ( is_object( $post ) ) {
					// append to choices .
					$field['choices'][ $post->ID ] = get_the_title( $post );
				}
			}
		}

		$field_options = '';
		if ( $field['allow_null'] ) {
			$field_options .= '<option value="0">' . __( '- Select a page -', 'acf' ) . '</option>';
		}

		foreach ( $field['choices'] as $post_id => $title ) {
			if ( empty( $title ) ) {
				continue;
			}

			$selected = '';

			if ( ( is_array( $field['value'] ) && in_array( $post_id, $field['value'], true ) ) || (int) $field['value'] === (int) $post_id ) {
				$selected = ' selected';
			}

			$field_options .= '<option value="' . $post_id . '"' . $selected . '>' . get_the_title( $post_id ) . '</option>';
		}

		$field_html  = '';
		$field_id    = str_replace( array( '[', ']' ), array( '-', '' ), $field['name'] );
		$field_html .= '<select id="' . $field_id . '" name="' . $field['name'] . '">';
		$field_html .= $field_options;
		$field_html .= '</select>';

		echo apply_filters( 'wr-acf-page-relationship/field_html', $field_html, $field, $field_options );
	}

	/**
	 * Enqueues CSS and JavaScript needed by HTML in the render_field() method.
	 *
	 * Callback for admin_enqueue_script.
	 *
	 * @return void
	 */
	public function input_admin_enqueue_scripts() {
	}

	/**
	 * Get page templates available to WordPress.
	 *
	 * @return mixed
	 */
	protected function get_page_templates() {

		$templates = get_page_templates();
		if ( is_array( $templates ) ) {
			return array_flip( $templates );
		}

		return false;
	}

	/**
	 * Get pages that match the page template(s) selected.
	 *
	 * @param array $page_templates the list of page template names to match against.
	 * @return mixed
	 */
	private function get_matching_pages( $page_templates ) {
		$args = array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);

		if ( ! empty( $page_templates ) ) {
			$meta_query_parts = array();
			foreach ( $page_templates as $page_template ) {
				$meta_query_parts[] = array(
					'key'   => '_wp_page_template',
					'value' => $page_template,
				);
			}
			if ( count( $meta_query_parts ) > 1 ) {
				$meta_query_parts['relation'] = 'OR';
			}

			$args['meta_query'] = $meta_query_parts;
		}

		$results = new WP_Query( $args );
		if ( is_array( $results->posts ) && count( $results->posts ) > 0 ) {
			return $results->posts;
		} else {
			return array();
		}
	}
}
