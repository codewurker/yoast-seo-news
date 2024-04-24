<?php
/**
 * Yoast SEO: News plugin file.
 *
 * @package WPSEO_News\XML_Sitemaps
 */

use Yoast\WP\Lib\ORM;
use Yoast\WP\SEO\Models\Indexable;
use Yoast\WP\SEO\Repositories\Indexable_Repository;

/**
 * Handling the generation of the News Sitemap.
 */
class WPSEO_News_Sitemap {

	/**
	 * The date helper.
	 *
	 * @var WPSEO_Date_Helper
	 */
	protected $date;

	/**
	 * The sitemap basename.
	 *
	 * @var string
	 */
	private $basename;

	/**
	 * Constructor. Set options, basename and add actions.
	 */
	public function __construct() {
		$this->date = new WPSEO_Date_Helper();

		add_action( 'init', [ $this, 'init' ], 10 );

		add_action( 'save_post', [ $this, 'invalidate_sitemap' ] );

		add_action( 'wpseo_news_schedule_sitemap_clear', 'yoast_wpseo_news_clear_sitemap_cache' );
	}

	/**
	 * Add the XML News Sitemap to the Sitemap Index.
	 *
	 * @param string $str String with Index sitemap content.
	 *
	 * @return string
	 */
	public function add_to_index( $str ) {

		// Only add when we have items.
		$items = $this->get_items( 1 );
		if ( empty( $items ) ) {
			return $str;
		}

		$str .= '<sitemap>' . "\n";
		$str .= '<loc>' . self::get_sitemap_name() . '</loc>' . "\n";
		$str .= '<lastmod>' . htmlspecialchars( $this->date->format( get_lastpostdate( 'gmt' ) ), ENT_COMPAT, get_bloginfo( 'charset' ), false ) . '</lastmod>' . "\n";
		$str .= '</sitemap>' . "\n";

		return $str;
	}

	/**
	 * Register the XML News sitemap with the main sitemap class.
	 *
	 * @return void
	 */
	public function init() {

		$this->basename = self::get_sitemap_name( false );

		// Setting stylesheet for cached sitemap.
		add_action( 'wpseo_sitemap_stylesheet_cache_' . $this->basename, [ $this, 'set_stylesheet_cache' ] );

		if ( isset( $GLOBALS['wpseo_sitemaps'] ) ) {
			add_filter( 'wpseo_sitemap_index', [ $this, 'add_to_index' ] );

			$this->yoast_wpseo_news_schedule_clear();

			// We might consider deprecating/removing this, because we are using a static xsl file.
			$GLOBALS['wpseo_sitemaps']->register_sitemap( $this->basename, [ $this, 'build' ] );
			if ( method_exists( $GLOBALS['wpseo_sitemaps'], 'register_xsl' ) ) {
				$xsl_rewrite_rule = sprintf( '^%s-sitemap.xsl$', $this->basename );

				$GLOBALS['wpseo_sitemaps']->register_xsl(
					$this->basename,
					[
						$this,
						'build_news_sitemap_xsl',
					],
					$xsl_rewrite_rule
				);
			}
		}
	}

	/**
	 * Method to invalidate the sitemap.
	 *
	 * @param int $post_id Post ID to invalidate for.
	 *
	 * @return void
	 */
	public function invalidate_sitemap( $post_id ) {
		// If this is just a revision, don't invalidate the sitemap cache yet.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Bail if this is a multisite installation and the site has been switched.
		if ( is_multisite() && ms_is_switched() ) {
			return;
		}

		// Only invalidate when we are in a News Post Type object.
		if ( ! in_array( get_post_type( $post_id ), WPSEO_News::get_included_post_types(), true ) ) {
			return;
		}

		WPSEO_Sitemaps_Cache::invalidate( $this->basename );
	}

	/**
	 * When sitemap is coming out of the cache there is no stylesheet. Normally it will take the default stylesheet.
	 *
	 * This method is called by a filter that will set the video stylesheet.
	 *
	 * @param object $target_object Target Object to set cache from.
	 *
	 * @return object
	 */
	public function set_stylesheet_cache( $target_object ) {
		$target_object->renderer->set_stylesheet( $this->get_stylesheet_line() );

		return $target_object;
	}

	/**
	 * Build the sitemap and push it to the XML Sitemaps Class instance for display.
	 *
	 * @return void
	 */
	public function build() {
		$GLOBALS['wpseo_sitemaps']->set_sitemap( $this->build_sitemap() );
		$GLOBALS['wpseo_sitemaps']->renderer->set_stylesheet( $this->get_stylesheet_line() );
	}

	/**
	 * Building the XML for the sitemap.
	 *
	 * @return string
	 */
	public function build_sitemap() {
		$start_time = microtime( true );

		$output = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";

		$items = $this->get_items();

		// Loop through items.
		if ( ! empty( $items ) ) {
			$output .= $this->build_items( $items );
		}

		/**
		 * Filter to add extra entries to the news sitemap.
		 *
		 * @param string $content String content to add, defaults to empty.
		 */
		$output .= apply_filters( 'wpseo_news_sitemap_content', '' );

		$output .= '</urlset>';

		$total_time = ( microtime( true ) - $start_time );
		if ( WP_DEBUG ) {
			$output .= '<!-- ' . $total_time . 's / ' . number_format( ( memory_get_peak_usage() / 1024 / 1024 ), 2 ) . 'MB -->';
		}
		return $output;
	}

	/**
	 * Outputs the XSL file.
	 *
	 * @return void
	 */
	public function build_news_sitemap_xsl() {
		$protocol = 'HTTP/1.1';

		if ( isset( $_SERVER['SERVER_PROTOCOL'] ) && is_string( $_SERVER['SERVER_PROTOCOL'] ) && $_SERVER['SERVER_PROTOCOL'] !== '' ) {
			$protocol = sanitize_text_field( wp_unslash( $_SERVER['SERVER_PROTOCOL'] ) );
		}
		// Force a 200 header and replace other status codes.
		header( $protocol . ' 200 OK', true, 200 );
		// Set the right content / mime type.
		header( 'Content-Type: text/xml' );
		// Prevent the search engines from indexing the XML Sitemap.
		header( 'X-Robots-Tag: noindex, follow', true );
		// Make the browser cache this file properly.
		header( 'Pragma: public' );
		header( 'Cache-Control: maxage=' . YEAR_IN_SECONDS );
		header( 'Expires: ' . $this->date->format_timestamp( ( time() + YEAR_IN_SECONDS ), 'D, d M Y H:i:s' ) . ' GMT' );

		/*
		 * Using `readfile()` rather than `include` to prevent issues with XSL being interpreted as PHP
		 * on systems where the PHP ini directived `short_open_tags` is turned on.
		 * phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		 */
		readfile( dirname( WPSEO_NEWS_FILE ) . '/assets/xml-news-sitemap.xsl' );
		// phpcs:enable

		die();
	}

	/**
	 * Clear the sitemap and sitemap index every hour to make sure the sitemap is hidden or shown when it needs to be.
	 *
	 * @return void
	 */
	private function yoast_wpseo_news_schedule_clear() {
		$schedule = wp_get_schedule( 'wpseo_news_schedule_sitemap_clear' );

		if ( empty( $schedule ) ) {
			wp_schedule_event( time(), 'hourly', 'wpseo_news_schedule_sitemap_clear' );
		}
	}

	/**
	 * Getter for stylesheet URL.
	 *
	 * @return string Stylesheet URL.
	 */
	private function get_stylesheet_line() {
		return "\n" . '<?xml-stylesheet type="text/xsl" href="' . esc_url( $this->get_xsl_url() ) . '"?>';
	}

	/**
	 * Getting all the items for the sitemap.
	 *
	 * @param int $limit The limit for the query, default is 1000 items.
	 *
	 * @return array
	 */
	private function get_items( $limit = 1000 ) {
		global $wpdb;

		// Get supported post types.
		$post_types = WPSEO_News::get_included_post_types();

		if ( empty( $post_types ) ) {
			return [];
		}

		/**
		 * The indexable repository.
		 *
		 * @var Indexable_Repository $repository
		 */
		$repository = YoastSEO()->classes->get( Indexable_Repository::class );

		$query = $repository
			->query()
			->distinct()
			->select_many( 'i.id', 'i.object_id', 'i.object_sub_type', 'i.permalink', 'i.object_published_at' )
			->select( 'breadcrumb_title', 'title' )
			->select( 'pm2.meta_value', 'stock_tickers' )
			->table_alias( 'i' )
			->left_outer_join( $wpdb->postmeta, 'pm.post_id = i.object_id AND pm.meta_key = \'_yoast_wpseo_newssitemap-robots-index\'', 'pm' )
			->left_outer_join( $wpdb->postmeta, 'pm2.post_id = i.object_id AND pm2.meta_key = \'_yoast_wpseo_newssitemap-stocktickers\'', 'pm2' )
			->where( 'i.post_status', 'publish' )
			->where( 'i.object_type', 'post' )
			->where_in( 'object_sub_type', $post_types )
			->where_raw( '( i.is_robots_noindex = 0 OR i.is_robots_noindex IS NULL )' )
			->where_raw( 'i.object_published_at >= UTC_TIMESTAMP() - INTERVAL 48 HOUR' )
			->where_raw( '( pm.meta_value = \'0\' OR pm.meta_value IS NULL )' )
			->order_by_desc( 'i.object_published_at' )
			->limit( $limit );

		$query = $this->maybe_add_terms_query( $query, $post_types );

		return $query->find_many();
	}

	/**
	 * Adds the term query to the sitemap query if required.
	 *
	 * @param ORM      $query      The sitemap query.
	 * @param string[] $post_types The post types.
	 *
	 * @return ORM The modified query.
	 */
	private function maybe_add_terms_query( ORM $query, $post_types ) {
		global $wpdb;

		$excluded_terms = (array) WPSEO_Options::get( 'news_sitemap_exclude_terms', [] );

		if ( empty( $excluded_terms ) ) {
			return $query;
		}

		$excluded_terms_by_post_type = [];
		foreach ( $excluded_terms as $excluded_term => $value ) {
			if ( $value !== 'on' ) {
				continue;
			}
			list( $term_id, $post_type ) = explode( '_for_', $excluded_term, 2 );
			if ( ! array_key_exists( $post_type, $excluded_terms_by_post_type ) ) {
				$excluded_terms_by_post_type[ $post_type ] = [];
			}
			$excluded_terms_by_post_type[ $post_type ][] = (int) $term_id;
		}

		$replacements = [];
		$term_query   = [];
		foreach ( $post_types as $post_type ) {
			if ( ! array_key_exists( $post_type, $excluded_terms_by_post_type ) ) {
				continue;
			}
			$term_ids     = $excluded_terms_by_post_type[ $post_type ];
			$replacements = array_merge( $replacements, [ $post_type ], $term_ids );
			$placeholders = implode( ', ', array_fill( 0, count( $term_ids ), '%d' ) );
			$term_query[] = "( object_sub_type = %s AND t.term_id IN ( $placeholders ) )";
		}
		$term_query = implode( ' OR ', $term_query );

		return $query
			->raw_join(
				"LEFT OUTER JOIN (
					SELECT tr.object_id, tt.term_id
					FROM $wpdb->term_relationships AS tr
					LEFT OUTER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				)",
				"( $term_query ) AND t.object_id = i.object_id",
				't',
				$replacements
			)
			->where_null( 't.object_id' );
	}

	/**
	 * Loop through all $items and build each one of it.
	 *
	 * @param Indexable[] $items Items to convert to sitemap output.
	 *
	 * @return string
	 */
	private function build_items( $items ) {
		$publication_tag = $this->build_publication_tag();
		$output          = '';
		foreach ( $items as $item ) {
			$output .= new WPSEO_News_Sitemap_Item( $item, $publication_tag );
		}

		return $output;
	}

	/**
	 * Builds the publication tag.
	 *
	 * @return string
	 */
	private function build_publication_tag() {
		$publication_name = WPSEO_Options::get( 'news_sitemap_name', get_bloginfo( 'name' ) );
		$publication_lang = $this->get_publication_lang();
		$charset          = get_bloginfo( 'charset' );

		$publication_tag  = "\t\t<news:publication>\n";
		$publication_tag .= "\t\t\t<news:name>" . htmlspecialchars( $publication_name, ENT_COMPAT, $charset, false ) . '</news:name>' . "\n";
		$publication_tag .= "\t\t\t<news:language>" . htmlspecialchars( $publication_lang, ENT_COMPAT, $charset, false ) . '</news:language>' . "\n";
		$publication_tag .= "\t\t</news:publication>\n";

		return $publication_tag;
	}

	/**
	 * Getting the name for the sitemap, if $full_path is true, it will return the full path.
	 *
	 * @param bool $full_path Generate a full path.
	 *
	 * @return string
	 */
	public static function get_sitemap_name( $full_path = true ) {
		/**
		 * Allows for filtering the News sitemap name.
		 *
		 * @param string $sitemap_name First portion of the news sitemap "file" name.
		 *
		 * @since 12.5.0
		 */
		$sitemap_name = apply_filters( 'Yoast\WP\News\sitemap_name', self::news_sitemap_basename() );

		// When $full_path is true, it will generate a full path.
		if ( $full_path ) {
			return WPSEO_Sitemaps_Router::get_base_url( $sitemap_name . '-sitemap.xml' );
		}

		return $sitemap_name;
	}

	/**
	 * Returns the basename of the news-sitemap, the first portion of the name of the sitemap "file".
	 *
	 * Defaults to news, but it's possible to override it by using the YOAST_NEWS_SITEMAP_BASENAME constant.
	 *
	 * @since 3.1
	 *
	 * @return string Basename for the news sitemap.
	 */
	public static function news_sitemap_basename() {
		$basename = 'news';

		if ( post_type_exists( 'news' ) ) {
			$basename = 'yoast-news';
		}

		if ( defined( 'YOAST_NEWS_SITEMAP_BASENAME' ) ) {
			$basename = YOAST_NEWS_SITEMAP_BASENAME;
		}

		return $basename;
	}

	/**
	 * Retrieves the XSL URL that should be used in the current environment.
	 *
	 * When home_url and site_url are not the same, the home_url should be used.
	 * This is because the XSL needs to be served from the same domain, protocol and port
	 * as the XML file that is loading it.
	 *
	 * @return string The XSL URL that needs to be used.
	 */
	protected function get_xsl_url() {
		if ( home_url() !== site_url() ) {
			return home_url( $this->basename . '-sitemap.xsl' );
		}

		return plugin_dir_url( WPSEO_NEWS_FILE ) . 'assets/xml-news-sitemap.xsl';
	}

	/**
	 * Getting the publication language.
	 *
	 * @return string Publication language.
	 */
	private function get_publication_lang() {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPSEO hook.
		$locale = apply_filters( 'wpseo_locale', get_locale() );

		// Fallback to 'en', if the length of the locale is less than 2 characters.
		if ( strlen( $locale ) < 2 ) {
			$locale = 'en';
		}

		return substr( $locale, 0, 2 );
	}
}
