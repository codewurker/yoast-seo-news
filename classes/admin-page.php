<?php
/**
 * Yoast SEO: News plugin file.
 *
 * @package WPSEO_News\Admin
 */

/**
 * Represents the admin page.
 */
class WPSEO_News_Admin_Page {

	/**
	 * Display admin page.
	 *
	 * @return void
	 */
	public function display() {
		// Admin header.
		Yoast_Form::get_instance()->admin_header( true, 'wpseo_news' );

		// Introduction.
		echo '<div id="wpseo-news-genre-removal-alert" style="max-width: 600px;"></div>';
		echo '<p>', esc_html__( 'You will generally only need a News Sitemap when your website is included in Google News.', 'wordpress-seo-news' ), '</p>';
		echo '<p>';
		printf(
			/* translators: %1$s opening tag of the link to the News Sitemap, %2$s closing tag for the link. */
			esc_html__( '%1$sView your News Sitemap%2$s.', 'wordpress-seo-news' ),
			'<a target="_blank" href="' . esc_url( WPSEO_News_Sitemap::get_sitemap_name() ) . '">',
			'</a>'
		);
		echo '</p>';

		echo '<h2>', esc_html__( 'General settings', 'wordpress-seo-news' ), '</h2>';

		/* translators: Hidden accessibility text. */
		echo '<fieldset><legend class="screen-reader-text">', esc_html__( 'News Sitemap settings', 'wordpress-seo-news' ), '</legend>';

		// Google News Publication Name.
		Yoast_Form::get_instance()->textinput( 'news_sitemap_name', __( 'Google News Publication Name', 'wordpress-seo-news' ) );

		echo '</fieldset>';

		// Post Types to include in News Sitemap.
		$this->include_post_types();

		// Post categories to exclude.
		$this->excluded_post_type_taxonomies();

		// Admin footer.
		Yoast_Form::get_instance()->admin_footer( true, false );
	}

	/**
	 * Generates the HTML for the post types which should be included in the sitemap.
	 *
	 * @return void
	 */
	private function include_post_types() {
		$post_type_helper = YoastSEO()->helpers->post_type;

		// Post Types to include in News Sitemap.
		echo '<h2>' . esc_html__( 'Post Types to include in News Sitemap', 'wordpress-seo-news' ) . '</h2>';
		/* translators: Hidden accessibility text. */
		echo '<fieldset><legend class="screen-reader-text">', esc_html__( 'Post Types to include:', 'wordpress-seo-news' ), '</legend>';

		$post_types      = get_post_types( [ 'public' => true ], 'objects' );
		$post_types_list = [];
		foreach ( $post_types as $post_type ) {
			if ( ! $post_type_helper->is_excluded( $post_type->name ) && $post_type->name !== 'attachment' ) {
				$post_types_list[ $post_type->name ] = $post_type->labels->name . ' (' . $post_type->name . ')';
			}
		}

		Yoast_Form::get_instance()->checkbox_list( 'news_sitemap_include_post_types', $post_types_list );

		echo '</fieldset><br>';
	}

	/**
	 * Generates the HTML for excluding post categories.
	 *
	 * @return void
	 */
	private function excluded_post_type_taxonomies() {
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		$post_types = array_filter( $post_types, [ $this, 'filter_included_post_type' ] );

		array_walk( $post_types, [ $this, 'excluded_post_type_taxonomies_output' ] );
	}

	/**
	 * Filter function used to determine what post times should be included in the new sitemap.
	 *
	 * @param WP_Post_Type $post_type The post type.
	 *
	 * @return bool Whether or not the post type should be included in the sitemap.
	 */
	protected function filter_included_post_type( $post_type ) {
		static $included_post_types;
		if ( ! $included_post_types ) {
			$included_post_types = (array) WPSEO_Options::get( 'news_sitemap_include_post_types', [] );
		}

		return array_key_exists( $post_type->name, $included_post_types );
	}

	/**
	 * Creates an array of objects containing taxonomies and the list of terms that are eligible for exclusion in the
	 * sitemap.
	 *
	 * @param WP_Post_Type $post_type Post type for which to exclude taxonomies.
	 *
	 * @return array Returns an array containing terms and taxonomies. Can be empty.
	 */
	private function get_excluded_post_type_taxonomies( $post_type ) {
		$excludable_taxonomies = new WPSEO_News_Excludable_Taxonomies( $post_type->name );

		return $excludable_taxonomies->get_terms();
	}

	/**
	 * Echoes the sub heading + checkboxes to exclude terms within each of the post type's taxonomies.
	 *
	 * @param WP_Post_Type $post_type The post type.
	 *
	 * @return void
	 */
	private function excluded_post_type_taxonomies_output( $post_type ) {
		$terms_per_taxonomy = $this->get_excluded_post_type_taxonomies( $post_type );

		if ( $terms_per_taxonomy === [] ) {
			return;
		}

		/* translators: %1%s expands to the post type name. */
		echo '<h2>' . esc_html( sprintf( __( 'Terms to exclude for %1$s', 'wordpress-seo-news' ), $post_type->labels->name ) ) . '</h2>';

		foreach ( $terms_per_taxonomy as $data ) {
			$taxonomy = $data['taxonomy'];
			$terms    = $data['terms'];

			/* translators: %1%s expands to the taxonomy name name. */
			echo '<h3>' . esc_html( sprintf( __( '%1$s to exclude', 'wordpress-seo-news' ), $taxonomy->labels->name ) ) . '</h3>';

			$taxonomies_list = [];
			foreach ( $terms as $term ) {
				$taxonomies_list[ $term->term_id . '_for_' . $post_type->name ] = $term->name;
			}

			Yoast_Form::get_instance()->checkbox_list( 'news_sitemap_exclude_terms', $taxonomies_list );
		}
	}

	/**
	 * Checks if the current page is a news seo plugin page.
	 *
	 * @param string $page The page to check.
	 *
	 * @return bool True when currently on a new page.
	 */
	protected function is_news_page( $page ) {
		$news_pages = [ 'wpseo_news' ];

		return in_array( $page, $news_pages, true );
	}
}
