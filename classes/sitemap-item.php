<?php
/**
 * Yoast SEO: News plugin file.
 *
 * @package WPSEO_News\XML_Sitemaps
 */

use Yoast\WP\SEO\Models\Indexable;

/**
 * The News Sitemap entry.
 */
class WPSEO_News_Sitemap_Item {

	/**
	 * The date helper.
	 *
	 * @var WPSEO_Date_Helper
	 */
	protected $date;

	/**
	 * The output which will be returned.
	 *
	 * @var string
	 */
	private $output = '';

	/**
	 * The current item.
	 *
	 * @var Indexable
	 */
	private $item;

	/**
	 * The publication tag
	 *
	 * @var string
	 */
	private $publication_tag;

	/**
	 * Setting properties and build the item.
	 *
	 * @param Indexable $item            The post.
	 * @param string    $publication_tag The publication tag.
	 */
	public function __construct( $item, $publication_tag ) {
		$this->item            = $item;
		$this->publication_tag = $publication_tag;

		$this->date = new WPSEO_Date_Helper();

		// Check if item should be skipped.
		if ( ! $this->skip_build_item() ) {
			$this->build_item();
		}
	}

	/**
	 * Return the output, because the object is converted to a string.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->output;
	}

	/**
	 * Determines if the item has to be skipped or not.
	 *
	 * @return bool True if the item has to be skipped.
	 */
	private function skip_build_item() {
		$skip_build_item = false;

		/**
		 * Filter: 'Yoast\WP\News\skip_build_item' - Allow override of decision to skip adding this item to the news sitemap.
		 *
		 * @param bool $skip_build_item Whether this item should be built for the sitemap.
		 * @param int  $item_id         ID of the current item to be skipped or not.
		 *
		 * @since 12.8.0
		 */
		$skip_build_item = apply_filters( 'Yoast\WP\News\skip_build_item', $skip_build_item, $this->item->object_id );

		return is_bool( $skip_build_item ) && $skip_build_item;
	}

	/**
	 * Building each sitemap item.
	 *
	 * @return void
	 */
	private function build_item() {
		$this->output .= '<url>' . "\n";
		$this->output .= "\t<loc>" . $this->item->permalink . '</loc>' . "\n";

		// Building the news_tag.
		$this->build_news_tag();

		$this->output .= '</url>' . "\n";
	}

	/**
	 * Building the news tag.
	 *
	 * @return void
	 */
	private function build_news_tag() {
		$this->output .= "\t<news:news>\n";
		$this->output .= $this->publication_tag;
		$this->output .= "\t\t<news:publication_date>" . $this->date->format( $this->item->object_published_at ) . '</news:publication_date>' . "\n";
		$this->output .= "\t\t<news:title><![CDATA[" . $this->item->title . ']]></news:title>' . "\n";

		if ( ! empty( $this->item->stock_tickers ) ) {
			$this->output .= "\t\t<news:stock_tickers><![CDATA[" . $this->item->stock_tickers . ']]></news:stock_tickers>' . "\n";
		}

		$this->output .= "\t</news:news>\n";
	}
}
