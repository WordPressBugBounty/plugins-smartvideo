<?php

namespace Swarmify\Smartvideo;

/**
 * Collects video data during page render and outputs VideoObject JSON-LD.
 *
 * Each page builder calls SchemaCollector::add() in its render method.
 * The wp_footer hook outputs the collected schema as JSON-LD.
 *
 * @since 2.5.0
 */
class SchemaCollector {

	/** @var array[] Collected video entries. */
	private static $videos = [];

	/**
	 * Register a video for schema output.
	 *
	 * @param string $src       Video source URL.
	 * @param string $poster    Poster/thumbnail URL (optional).
	 * @param string $title     Video title (optional, falls back to page title).
	 * @param string $description Video description (optional).
	 */
	public static function add( $src, $poster = '', $title = '', $description = '' ) {
		if ( empty( $src ) ) {
			return;
		}
		self::$videos[] = [
			'src'         => $src,
			'poster'      => $poster,
			'title'       => $title,
			'description' => $description,
		];
	}

	/**
	 * Output JSON-LD VideoObject(s) in the page footer.
	 *
	 * Hooked to wp_footer.
	 */
	public static function output_schema() {
		if ( 'on' !== get_option( 'swarmify_toggle_schema', 'on' ) ) {
			return;
		}

		if ( empty( self::$videos ) ) {
			return;
		}

		$page_title = wp_get_document_title();
		$upload_date = get_the_date( 'c' ) ?: gmdate( 'Y-m-d' );

		$schema_items = [];
		foreach ( self::$videos as $video ) {
			$item = [
				'@type'      => 'VideoObject',
				'contentUrl' => $video['src'],
				'name'       => ! empty( $video['title'] ) ? $video['title'] : $page_title,
				'uploadDate' => $upload_date,
			];

			if ( ! empty( $video['description'] ) ) {
				$item['description'] = $video['description'];
			}

			if ( ! empty( $video['poster'] ) ) {
				$item['thumbnailUrl'] = $video['poster'];
			}

			$schema_items[] = $item;
		}

		$output = [
			'@context' => 'https://schema.org',
		];

		if ( count( $schema_items ) === 1 ) {
			$output = array_merge( $output, $schema_items[0] );
		} else {
			$output['@graph'] = $schema_items;
		}

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode( $output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG )
		);
	}
}
