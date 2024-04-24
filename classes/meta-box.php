<?php
/**
 * Yoast SEO: News plugin file.
 *
 * @package WPSEO_News
 */

use Yoast\WP\SEO\Presenters\Admin\Meta_Fields_Presenter;

/**
 * Represents the Yoast SEO: News metabox.
 */
class WPSEO_News_Meta_Box extends WPSEO_Metabox {

	/**
	 * Holds the flattened version to use with enqueueing the scripts.
	 *
	 * @var string
	 */
	protected $script_version;

	/**
	 * Constructs WPSEO_News_Meta_Box.
	 *
	 * @param string $script_version The version to use for the script.
	 *
	 * @noinspection PhpMissingParentConstructorInspection The parent constructor only has unwanted side-effects.
	 */
	public function __construct( $script_version ) {
		$this->script_version = $script_version;
	}

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		global $pagenow;

		// Register the fields as meta.
		add_filter( 'add_extra_wpseo_meta_fields', [ $this, 'add_meta_fields_to_wpseo_meta' ] );

		// Register the fields for saving.
		add_filter( 'wpseo_save_metaboxes', [ $this, 'save' ], 10, 1 );

		// Render the fields alongside other hidden inputs.
		add_filter( 'wpseo_content_meta_section_content', [ $this, 'add_news_fields_to_the_content' ] );
		add_filter( 'wpseo_elementor_hidden_fields', [ $this, 'add_news_fields_to_the_content' ] );

		// Register the meta box tab.
		add_filter( 'yoast_free_additional_metabox_sections', [ $this, 'add_metabox_section' ] );

		// Load the editor script when on an edit post or new post page.
		$is_post_edit_page = $pagenow === 'post.php' || $pagenow === 'post-new.php';
		if ( $is_post_edit_page ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		}

		// Load the editor script when on an elementor edit page.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Not form data.
		$get_action             = isset( $_GET['action'] ) && is_string( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : null;
		$is_elementor_edit_page = $pagenow === 'post.php' && $get_action === 'elementor';
		if ( $is_elementor_edit_page ) {
			add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		}

		// Register the dismissible alert.
		$editor_changes_alert = new WPSEO_News_Editor_Changes_Alert();
		$editor_changes_alert->register_hooks();
	}

	/**
	 * The metaboxes to display and save for the tab.
	 *
	 * @param string $post_type The post type to get metaboxes for. Unused in this implementation.
	 *
	 * @return array[] Multi-level array with information on each metabox to display.
	 */
	public function get_meta_boxes( $post_type = 'post' ) {
		return [
			'newssitemap-stocktickers' => [
				'name'        => 'newssitemap-stocktickers',
				'std'         => '',
				'type'        => 'hidden',
				'title'       => __( 'Stock Tickers', 'wordpress-seo-news' ),
				'description' => __( 'A comma-separated list of up to 5 stock tickers of the companies, mutual funds, or other financial entities that are the main subject of the article. Each ticker must be prefixed by the name of its stock exchange, and must match its entry in Google Finance. For example, "NASDAQ:AMAT" (but not "NASD:AMAT"), or "BOM:500325" (but not "BOM:RIL").', 'wordpress-seo-news' ),
			],
			'newssitemap-robots-index' => [
				'type'          => 'hidden',
				'default_value' => '0', // The default value will be 'index'; See the list of options.
				'std'           => '',
				'options'       => [
					'0' => 'index',
					'1' => 'noindex',
				],
				'title'         => __( 'Googlebot-News index', 'wordpress-seo-news' ),
				'description'   => __( 'Using noindex allows you to prevent articles from appearing in Google News.', 'wordpress-seo-news' ),
			],
		];
	}

	/**
	 * Add the meta boxes to meta box array so they get saved.
	 *
	 * @param array $meta_boxes The metaboxes to save.
	 *
	 * @return array
	 */
	public function save( $meta_boxes ) {
		return array_merge( $meta_boxes, $this->get_meta_boxes() );
	}

	/**
	 * Add WordPress SEO meta fields to WPSEO meta class.
	 *
	 * @param array $meta_fields The meta fields to extend.
	 *
	 * @return array
	 */
	public function add_meta_fields_to_wpseo_meta( $meta_fields ) {
		$meta_fields['news'] = $this->get_meta_boxes();

		return $meta_fields;
	}

	/**
	 * Adds a news section to the metabox sections array.
	 *
	 * @param array $sections The sections to add to.
	 *
	 * @return array
	 */
	public function add_metabox_section( $sections ) {
		if ( ! $this->is_post_type_supported() ) {
			return $sections;
		}

		$sections[] = [
			'name'         => 'news',
			'link_content' => '<span class="dashicons dashicons-admin-plugins"></span>' . esc_html__( 'News', 'wordpress-seo-news' ),
			'content'      => '<div id="wpseo-news-metabox-root" class="wpseo-meta-section-content"></div>',
		];

		return $sections;
	}

	/**
	 * Adds the News meta fields to the content.
	 *
	 * @param string $content The content.
	 *
	 * @return string The content with the rendered News meta fields.
	 */
	public function add_news_fields_to_the_content( $content ) {
		return $content . new Meta_Fields_Presenter( $this->get_metabox_post(), 'news' );
	}

	/**
	 * Enqueues the editor scripts when the post type is supported.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! $this->is_post_type_supported() ) {
			return;
		}

		$script_handle = 'wpseo-news-editor';
		$dependencies  = [
			'wp-components',
			'wp-compose',
			'wp-data',
			'wp-dom-ready',
			'wp-element',
			'wp-hooks',
			'wp-i18n',
			'wp-plugins',
			'yoast-seo-editor-modules',
		];

		wp_enqueue_script(
			$script_handle,
			plugins_url( 'js/dist/yoast-seo-news-editor-' . $this->script_version . '.js', WPSEO_NEWS_FILE ),
			$dependencies,
			WPSEO_News::VERSION,
			true
		);

		$javascript_strings = new WPSEO_News_Javascript_Strings();
		$javascript_strings->localize_script( $script_handle );

		wp_localize_script(
			$script_handle,
			'wpseoNewsScriptData',
			[
				'isBlockEditor'        => WP_Screen::get()->is_block_editor(),
				'newsChangesAlertLink' => WPSEO_Shortlinker::get( 'https://yoa.st/news-changes' ),
			]
		);
	}

	/**
	 * Check if current post_type is supported.
	 *
	 * @return bool
	 */
	protected function is_post_type_supported() {
		static $is_supported;

		if ( $is_supported === null ) {
			// Default is false.
			$is_supported = false;

			$post = $this->get_metabox_post();

			if ( is_a( $post, 'WP_Post' ) ) {
				// Get supported post types.
				$post_types = WPSEO_News::get_included_post_types();

				// Display content if post type is supported.
				if ( ! empty( $post_types ) && in_array( $post->post_type, $post_types, true ) ) {
					$is_supported = true;
				}
			}
		}

		return $is_supported;
	}
}
