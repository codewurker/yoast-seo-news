<?php
/**
 * Yoast SEO: News plugin file.
 *
 * @package WPSEO_News
 */

use Yoast\WP\SEO\Config\Schema_IDs;

/**
 * Makes the require Schema changes.
 */
class WPSEO_News_Schema {

	/**
	 * The date helper.
	 *
	 * @var WPSEO_Date_Helper
	 */
	protected $date;

	/**
	 * WPSEO_News_Schema Constructor.
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		$this->date = new WPSEO_Date_Helper();

		add_filter( 'wpseo_schema_article_types', [ $this, 'schema_add_news_types' ] );
		add_filter( 'wpseo_schema_article_types_labels', [ $this, 'schema_add_news_types_labels' ] );

		add_filter( 'wpseo_schema_article', [ $this, 'add_copyright_information' ] );
	}

	/**
	 * Adds copyright information.
	 *
	 * @param array $data Schema Article data.
	 *
	 * @return array Schema Article data.
	 */
	public function add_copyright_information( $data ) {
		$post = $this->get_post();
		if ( $this->is_post_type_included( $post ) ) {
			$data['copyrightYear']   = $this->date->format( $post->post_date_gmt, 'Y' );
			$data['copyrightHolder'] = [ '@id' => trailingslashit( WPSEO_Utils::get_home_url() ) . Schema_IDs::ORGANIZATION_HASH ];
		}

		return $data;
	}

	/**
	 * Checks if the given post should be included or not, based on the type.
	 *
	 * @param WP_Post|null $post The post to check for.
	 *
	 * @return bool True if the post should be included based on post type.
	 */
	protected function is_post_type_included( $post ) {
		return $post !== null && in_array( $post->post_type, WPSEO_News::get_included_post_types(), true );
	}

	/**
	 * Checks if the given post should be excluded or not.
	 *
	 * @codeCoverageIgnore It just wraps logic.
	 *
	 * @param WP_Post $post The post to check for.
	 *
	 * @return bool True if the post should be excluded.
	 */
	protected function is_post_excluded( $post ) {
		return (
			WPSEO_News::is_excluded_through_sitemap( $post->ID )
			|| WPSEO_News::is_excluded_through_terms( $post->ID, $post->post_type )
		);
	}

	/**
	 * Retrieves post data given a post ID or post object.
	 *
	 * This function exists to be able to mock the get_post call and should
	 * no longer be needed when moving the tests suite over to BrainMonkey.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param int|WP_Post|null $post Optional. Post ID or post object.
	 *
	 * @return WP_Post|null The post object or null if it cannot be found.
	 */
	protected function get_post( $post = null ) {
		return get_post( $post );
	}

	/**
	 * Add schema article types.
	 *
	 * @param array $schema_article_types Schema article types.
	 *
	 * @return array Schema article types.
	 */
	public function schema_add_news_types( $schema_article_types ) {
		return array_merge(
			$schema_article_types,
			[
				'AnalysisNewsArticle'   => '',
				'AskPublicNewsArticle'  => '',
				'BackgroundNewsArticle' => '',
				'OpinionNewsArticle'    => '',
				'ReportageNewsArticle'  => '',
				'ReviewNewsArticle'     => '',
			]
		);
	}

	/**
	 * Add schema article types with labels.
	 *
	 * @param array $schema_article_types_labels Schema article types with labels.
	 *
	 * @return array Schema article types with labels.
	 */
	public function schema_add_news_types_labels( $schema_article_types_labels ) {
		return array_merge(
			$schema_article_types_labels,
			[
				[
					'name'  => __( 'News: Analysis article', 'wordpress-seo-news' ),
					'value' => 'AnalysisNewsArticle',
				],
				[
					'name'  => __( 'News: Ask The Public article', 'wordpress-seo-news' ),
					'value' => 'AskPublicNewsArticle',
				],
				[
					'name'  => __( 'News: Background article', 'wordpress-seo-news' ),
					'value' => 'BackgroundNewsArticle',
				],
				[
					'name'  => __( 'News: Opinion article', 'wordpress-seo-news' ),
					'value' => 'OpinionNewsArticle',
				],
				[
					'name'  => __( 'News: Reportage article', 'wordpress-seo-news' ),
					'value' => 'ReportageNewsArticle',
				],
				[
					'name'  => __( 'News: Review article', 'wordpress-seo-news' ),
					'value' => 'ReviewNewsArticle',
				],
			]
		);
	}
}
