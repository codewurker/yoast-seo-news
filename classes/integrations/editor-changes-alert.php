<?php
/**
 * Yoast SEO News plugin file.
 *
 * @package Yoast\NewsSEO
 */

use Yoast\WP\SEO\Integrations\Alerts\Abstract_Dismissable_Alert;

/**
 * Class WPSEO_News_Editor_Changes_Alert.
 */
class WPSEO_News_Editor_Changes_Alert extends Abstract_Dismissable_Alert {

	/**
	 * Holds the alert identifier.
	 *
	 * @var string
	 */
	public $alert_identifier = 'news-editor-changes-alert';
}
