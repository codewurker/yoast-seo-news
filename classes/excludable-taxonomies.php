<?php
/**
 * Yoast SEO: News plugin file.
 *
 * @package WPSEO_News
 */

/**
 * Class representing the excludable taxonomies for a certain post type.
 */
class WPSEO_News_Excludable_Taxonomies {

	/**
	 * The post type.
	 *
	 * @var string
	 */
	protected $post_type;

	/**
	 * Setting properties.
	 *
	 * @param string $post_type The post type.
	 */
	public function __construct( $post_type ) {
		$this->post_type = $post_type;
	}

	/**
	 * Gets a list of taxonomies of which posts with terms of this taxonomy can be excluded from the sitemap.
	 *
	 * @return array Taxonomies of which posts with terms of this taxonomy can be excluded from the sitemap.
	 */
	public function get() {
		$taxonomies = get_object_taxonomies( $this->post_type, 'objects' );

		return array_filter( $taxonomies, [ $this, 'filter_taxonomies' ] );
	}

	/**
	 * Retrieves the terms that belong to the taxonomy.
	 *
	 * @return array
	 */
	public function get_terms() {
		$taxonomies     = $this->get();
		$taxonomy_terms = array_map( [ $this, 'get_terms_for_taxonomy' ], $taxonomies );

		return array_filter( $taxonomy_terms );
	}

	/**
	 * Filter to check whether a taxonomy is shown in the WordPress ui.
	 *
	 * @param WP_Taxonomy $taxonomy The taxonomy to filter.
	 *
	 * @return bool Whether or not the taxonomy is hidden in the WordPress ui.
	 */
	protected function filter_taxonomies( WP_Taxonomy $taxonomy ) {
		return $taxonomy->show_ui === true;
	}

	/**
	 * Gets a list of terms for the given taxonomy, and returns them along with the taxonomy in an array.
	 *
	 * @param WP_Taxonomy $taxonomy The taxonomy to get the terms for.
	 *
	 * @return array|null An array containing both the taxonomy and its terms or null
	 *                    if no terms are associated with the taxonomy.
	 */
	protected function get_terms_for_taxonomy( $taxonomy ) {
		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy->name,
				'hide_empty' => false,
				'show_ui'    => true,
			]
		);

		if ( count( $terms ) === 0 ) {
			return null;
		}

		return [
			'taxonomy' => $taxonomy,
			'terms'    => $terms,
		];
	}
}
