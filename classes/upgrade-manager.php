<?php
/**
 * Yoast SEO: News plugin file.
 *
 * @package WPSEO_News
 */

use Yoast\WP\Lib\Model;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Represents the update routine when a newer version has been installed.
 */
class WPSEO_News_Upgrade_Manager {

	/**
	 * Check if there's a plugin update.
	 *
	 * @return void
	 */
	public function check_update() {
		// Get options.
		$options = get_option( 'wpseo_news' );

		if ( ! isset( $options['news_version'] ) && isset( $options['version'] ) ) {
			$options['news_version'] = $options['version'];

			unset( $options['version'] );

			update_option( 'wpseo_news', $options );
		}

		// Check if update is required.
		if ( version_compare( WPSEO_News::VERSION, $options['news_version'], '>' ) ) {

			// Do update.
			$this->do_update( $options['news_version'] );

			// Update version code.
			$this->update_current_version_code();
		}
	}

	/**
	 * An update is required, do it.
	 *
	 * @param string $current_version The current version.
	 *
	 * @return void
	 */
	private function do_update( $current_version ) {
		// Update to version 2.0.
		if ( version_compare( $current_version, '2.0', '<' ) ) {
			$this->upgrade_20();
		}

		// Upgrade to version 2.0.4.
		if ( version_compare( $current_version, '2.0.4', '<' ) ) {
			$this->upgrade_204();
		}

		// Upgrade to version 7.8.
		if ( version_compare( $current_version, '7.8', '<' ) ) {
			$this->upgrade_78();
		}

		// Upgrade to version 8.3.
		if ( version_compare( $current_version, '8.3', '<' ) ) {
			$this->upgrade_83();
		}

		// Upgrade to version 12.4.
		if ( version_compare( $current_version, '12.4-RC0', '<=' ) ) {
			$this->upgrade_124();
		}

		// Upgrade to version 12.4.1.
		if ( version_compare( $current_version, '12.4.1-RC0', '<=' ) ) {
			$this->upgrade_1241();
		}

		// Upgrade to version 12.7.
		if ( version_compare( $current_version, '12.7', '<=' ) ) {
			$this->upgrade_127();
		}

		// Upgrade to version 13.1.
		if ( version_compare( $current_version, '13.1-RC0', '<=' ) ) {
			$this->upgrade_131();
		}

		// Upgrade to version 13.1.
		if ( version_compare( $current_version, '13.2-RC0', '<=' ) ) {
			$this->upgrade_132();
		}
	}

	/**
	 * Update the current version code.
	 *
	 * @return void
	 */
	private function update_current_version_code() {
		$options                 = get_option( 'wpseo_news' );
		$options['news_version'] = WPSEO_News::VERSION;

		update_option( 'wpseo_news', $options );
	}

	/**
	 * Perform the upgrade to 2.0.
	 *
	 * @return void
	 */
	private function upgrade_20() {
		// Get current options.
		$current_options = get_option( 'wpseo_news' );

		// Set new options.
		$new_options = [
			'news_sitemap_name'          => ( ( isset( $current_options['newssitemapname'] ) ) ? $current_options['newssitemapname'] : '' ),
			'news_sitemap_default_genre' => ( ( isset( $current_options['newssitemap_default_genre'] ) ) ? $current_options['newssitemap_default_genre'] : '' ),
		];

		// Save new options.
		update_option( 'wpseo_news', $new_options );
	}

	/**
	 * Perform the upgrade to 2.0.4.
	 *
	 * @return void
	 */
	private function upgrade_204() {
		// Remove unused option.
		$news_options = get_option( 'wpseo_news' );
		unset( $news_options['ep_image_title'] );

		// Update options.
		update_option( 'wpseo_news', $news_options );
	}

	/**
	 * Perform the upgrade to 7.8.
	 *
	 * @return void
	 */
	private function upgrade_78() {
		// Delete all standout tags. Functionality was deleted in 7.7, data only deleted in 7.8.
		$this->delete_meta_by_key( '_yoast_wpseo_newssitemap-standout' );

		// Delete all editors picks settings.
		$this->delete_meta_by_key( '_yoast_wpseo_newssitemap-editors-pick' );

		// Delete all original source references.
		$this->delete_meta_by_key( '_yoast_wpseo_newssitemap-original' );
	}

	/**
	 * Performs the upgrade to 8.3.
	 *
	 * @return void
	 */
	private function upgrade_83() {
		// Get current options.
		$options = get_option( 'wpseo_news' );

		foreach ( $options as $key => $value ) {

			if ( strpos( $key, 'catexclude_' ) !== 0 ) {
				continue;
			}

			$slug = str_replace( 'catexclude_', '', $key );
			$options[ 'news_sitemap_exclude_term_category_' . $slug ] = $value;
			unset( $options[ $key ] );
		}

		// Update options.
		update_option( 'wpseo_news', $options );
	}

	/**
	 * Removes the timezone notice when set.
	 *
	 * @return void
	 */
	private function upgrade_124() {
		Yoast_Notification_Center::get()->remove_notification_by_id( 'wpseo-news_timezone_format_empty' );

		$options = get_option( 'wpseo_news' );

		if ( isset( $options['name'] ) && ! isset( $options['news_sitemap_name'] ) ) {
			$options['news_sitemap_name'] = $options['name'];

			unset( $options['name'] );
		}

		if ( isset( $options['default_genre'] ) && ! isset( $options['news_sitemap_default_genre'] ) ) {
			$options['news_sitemap_default_genre'] = $options['default_genre'];

			unset( $options['default_genre'] );
		}

		foreach ( $options as $option_name => $option_value ) {
			if ( strpos( $option_name, 'newssitemap_include_' ) === 0 ) {
				$options[ str_replace( 'newssitemap_include_', 'news_sitemap_include_post_type_', $option_name ) ] = $option_value;

				unset( $options[ $option_name ] );

				continue;
			}

			if ( strpos( $option_name, 'term_exclude_' ) === 0 ) {
				$options[ str_replace( 'term_exclude_', 'news_sitemap_exclude_term_', $option_name ) ] = $option_value;

				unset( $options[ $option_name ] );

				continue;
			}
		}

		update_option( 'wpseo_news', $options );
	}

	/**
	 * Makes the options converted in 12.4 an array.
	 *
	 * @return void
	 */
	private function upgrade_1241() {
		$options = get_option( 'wpseo_news' );

		$included_post_types = [];
		$excluded_terms      = [];

		if ( isset( $options['news_sitemap_include_post_types'] ) && is_array( $options['news_sitemap_include_post_types'] ) ) {
			$included_post_types = $options['news_sitemap_include_post_types'];
		}

		if ( isset( $options['news_sitemap_exclude_terms'] ) && is_array( $options['news_sitemap_exclude_terms'] ) ) {
			$excluded_terms = $options['news_sitemap_exclude_terms'];
		}

		foreach ( $options as $option_name => $option_value ) {
			if ( strpos( $option_name, 'news_sitemap_include_post_type_' ) === 0 ) {
				$post_type_to_include                         = str_replace( 'news_sitemap_include_post_type_', '', $option_name );
				$included_post_types[ $post_type_to_include ] = 'on';

				unset( $options[ $option_name ] );

				continue;
			}

			if ( strpos( $option_name, 'news_sitemap_exclude_term_' ) === 0 ) {
				$term_to_exclude                    = str_replace( 'news_sitemap_exclude_term_', '', $option_name );
				$excluded_terms[ $term_to_exclude ] = 'on';

				unset( $options[ $option_name ] );

				continue;
			}
		}

		$options['news_sitemap_include_post_types'] = $included_post_types;
		$options['news_sitemap_exclude_terms']      = $excluded_terms;

		update_option( 'wpseo_news', $options );
	}

	/**
	 * Performs the upgrade routine for Yoast SEO News 12.7.
	 *
	 * @return void
	 */
	private function upgrade_127() {
		// Remove the default genre setting from the database.
		$options = get_option( 'wpseo_news' );
		unset( $options['news_sitemap_default_genre'] );
		update_option( 'wpseo_news', $options );

		// Remove the genre settings from the database.
		$this->delete_meta_by_key( '_yoast_wpseo_newssitemap-genre' );

		// Remove the News sitemap exclude settings from the database.
		$this->delete_meta_by_key( '_yoast_wpseo_newssitemap-exclude' );
	}

	/**
	 * Performs the upgrade routine for Yoast SEO News 13.1.
	 *
	 * @return void
	 */
	private function upgrade_131() {
		/*
		 * The updating of the excluded terms options needs to run later
		 * so we can assume that all custom post types have been registered.
		 */
		add_action( 'init', [ $this, 'update_excluded_terms_options' ], 100 );

		global $wpdb;

		$indexable_table = Model::get_table_name( 'Indexable' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"
			UPDATE %i AS i
			JOIN %i AS p ON i.%i = p.ID AND i.%i = 'post'
			SET i.%i = p.%i, i.%i = p.%i
			WHERE p.%i >= NOW() - INTERVAL 48 HOUR
			",
				$indexable_table,
				$wpdb->posts,
				'object_id',
				'object_type',
				'object_published_at',
				'post_date_gmt',
				'object_last_modified',
				'post_modified_gmt',
				'post_date_gmt'
			)
		);
		// phpcs:enable
	}

	/**
	 * Performs the upgrade routine for Yoast SEO News 13.2.
	 *
	 * @return void
	 */
	private function upgrade_132() {
		require_once ABSPATH . 'wp-admin/includes/admin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		$upgrader       = new WP_Upgrader();
		$upgrader->skin = new Automatic_Upgrader_Skin();
		Language_Pack_Upgrader::async_upgrade( $upgrader );
	}

	/**
	 * Deletes post meta fields by key.
	 *
	 * @link https://codex.wordpress.org/Class_Reference/wpdb#DELETE_Rows
	 *
	 * @param string $key The key to delete post meta fields for.
	 *
	 * @return void
	 */
	private function delete_meta_by_key( $key ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.SlowDBQuery -- Upgrade routines are only used intermittently.
		$wpdb->delete(
			$wpdb->postmeta,
			[
				'meta_key' => $key,
			],
			[ '%s' ]
		);
		// phpcs:enable
	}

	/**
	 * Updates the options about excluded terms to the new 13.1 format.
	 *
	 * @return void
	 */
	public function update_excluded_terms_options() {
		$options    = get_option( 'wpseo_news' );
		$post_types = [];
		foreach ( get_post_types( [ 'public' => true ], 'names' ) as $post_type ) {
			if ( array_key_exists( $post_type, $options['news_sitemap_include_post_types'] ) && $options['news_sitemap_include_post_types'][ $post_type ] === 'on' ) {
				$post_types[] = $post_type;
			}
		}

		// Support post if no post types are supported.
		if ( empty( $post_types ) ) {
			$post_types[] = 'post';
		}

		$updated_option = [];

		foreach ( $post_types as $post_type ) {
			$excludable_taxonomies = new WPSEO_News_Excludable_Taxonomies( $post_type );

			foreach ( $excludable_taxonomies->get_terms() as $data ) {
				$terms = $data['terms'];

				foreach ( $terms as $term ) {
					$option_key = $term->taxonomy . '_' . $term->slug . '_for_' . $post_type;
					if ( array_key_exists( $option_key, $options['news_sitemap_exclude_terms'] ) && $options['news_sitemap_exclude_terms'][ $option_key ] === 'on' ) {
						$updated_option[ $term->term_id . '_for_' . $post_type ] = 'on';
					}
				}
			}
		}

		$options['news_sitemap_exclude_terms'] = $updated_option;
		update_option( 'wpseo_news', $options );
	}
}
