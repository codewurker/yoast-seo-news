<?php
/**
 * Yoast SEO: News plugin file.
 *
 * @package WPSEO_News
 */

/**
 * Represents the javascript strings.
 */
class WPSEO_News_Javascript_Strings {

	/**
	 * Localizes the given script with the JavaScript translations.
	 *
	 * @param string $script_handle The script handle to localize for.
	 *
	 * @return void
	 */
	public function localize_script( $script_handle ) {
		$translations = [
			'wordpress-seo-news' => $this->get_translations( 'wordpress-seo-newsjs' ),
		];

		wp_localize_script( $script_handle, 'wpseoNewsJSL10n', $translations );
	}

	/**
	 * Returns translations necessary for JS files.
	 *
	 * @param string $component The component to retrieve the translations for.
	 *
	 * @return object|null The translations in a Jed format for JS files.
	 */
	protected function get_translations( $component ) {
		$locale = get_user_locale();

		$file = plugin_dir_path( WPSEO_NEWS_FILE ) . 'languages/' . $component . '-' . $locale . '.json';
		if ( file_exists( $file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Retrieving a local file.
			$file = file_get_contents( $file );
			if ( is_string( $file ) && $file !== '' ) {
				return json_decode( $file, true );
			}
		}

		return null;
	}
}
