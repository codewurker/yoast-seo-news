<?php
/**
 * WPSEO plugin file.
 *
 * @package WPSEO\Internals\Options
 */

/**
 * Class representing the wpseo_news options.
 */
class WPSEO_News_Option extends WPSEO_Option {

	/**
	 * The option name.
	 *
	 * @var string
	 */
	protected $option_name = 'wpseo_news';

	/**
	 * The defaults.
	 *
	 * @var array
	 */
	protected $defaults = [
		'news_sitemap_name'               => '',
		'news_version'                    => '0',
		'news_sitemap_include_post_types' => [],
		'news_sitemap_exclude_terms'      => [],
	];

	/**
	 * Registers the option to the WPSEO Options framework.
	 *
	 * @return void
	 */
	public static function register_option() {
		WPSEO_Options::register_option( self::get_instance() );
	}

	/**
	 * Get the singleton instance of this class.
	 *
	 * @return WPSEO_Option
	 */
	public static function get_instance() {
		if ( ! ( self::$instance instanceof self ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * All concrete classes must contain a validate_option() method which validates all
	 * values within the option.
	 *
	 * @param array $dirty New value for the option.
	 * @param array $clean Clean value for the option, normally the defaults.
	 * @param array $old   Old value of the option.
	 *
	 * @return array The cleaned and validated option.
	 */
	protected function validate_option( $dirty, $clean, $old ) {

		foreach ( $clean as $key => $value ) {
			switch ( $key ) {
				case 'news_version':
					$clean[ $key ] = WPSEO_NEWS_VERSION;
					break;
				case 'news_sitemap_name':
					if ( isset( $dirty[ $key ] ) && $dirty[ $key ] !== '' ) {
						$clean[ $key ] = WPSEO_Utils::sanitize_text_field( $dirty[ $key ] );
					}
					break;

				case 'news_sitemap_include_post_types':
				case 'news_sitemap_exclude_terms':
					$clean[ $key ] = [];

					if ( isset( $dirty[ $key ] ) && ( is_array( $dirty[ $key ] ) && $dirty[ $key ] !== [] ) ) {
						foreach ( $dirty[ $key ] as $name => $posted_value ) {
							if ( is_string( $name ) ) {
								$clean[ $key ][ $name ] = 'on';
							}
						}
					}
					break;
			}
		}

		return $clean;
	}
}
