<?php

namespace Swarmify\Smartvideo;

/**
 * Collects video data during page render and outputs VideoObject JSON-LD.
 *
 * Builders call add() during render; output_schema() emits on wp_footer.
 *
 * @since 2.5.0
 */
class SchemaCollector {

	/** @var array[] Collected video entries. */
	private static $videos = [];

	/**
	 * Sanitize text for JSON-LD output, trimming to ~30 words.
	 *
	 * @param string $text Raw text.
	 * @return string Cleaned text.
	 */
	private static function sanitize_text( $text ) {
		$text = strip_shortcodes( $text );
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$text = wp_trim_words( $text, 30, '' );
		return $text;
	}

	/**
	 * Register a video for schema output.
	 *
	 * @param string $src         Video source URL.
	 * @param string $poster      Poster/thumbnail URL (optional).
	 * @param string $title       Video title (optional, falls back to page title).
	 * @param string $description Video description (optional).
	 * @param array  $meta        Additional metadata (optional).
	 */
	public static function add( $src, $poster = '', $title = '', $description = '', $meta = [] ) {
		if ( empty( $src ) ) {
			return;
		}
		static $seen = [];
		if ( isset( $seen[ $src ] ) ) {
			return;
		}
		$seen[ $src ]   = true;
		self::$videos[] = array_merge( [
			'src'         => $src,
			'poster'      => $poster,
			'title'       => $title,
			'description' => $description,
		], $meta );
	}

	/**
	 * Resolve a description for schema output using a fallback chain.
	 *
	 * @param string $per_video Description passed by the builder.
	 * @param int    $post_id   Current post ID.
	 * @return string Sanitized description, or empty string.
	 */
	private static function get_description_fallback( $per_video, $post_id ) {
		if ( ! empty( $per_video ) ) {
			return self::sanitize_text( $per_video );
		}

		if ( ! $post_id ) {
			return '';
		}

		$excerpt = get_the_excerpt( $post_id );
		if ( ! empty( $excerpt ) ) {
			return self::sanitize_text( $excerpt );
		}

		// One get_post_meta() call fetches every SEO plugin key in a single DB
		// hit. Skip values still holding template placeholders (%%title%% etc.).
		$seo_keys = [
			'_yoast_wpseo_metadesc',
			'rank_math_description',
			'_aioseo_description',
			'_seopress_titles_desc',
			'_genesis_description',
			'_metaseo_metadesc',
		];
		$all_meta = get_post_meta( $post_id );
		foreach ( $seo_keys as $key ) {
			$val = $all_meta[ $key ][0] ?? '';
			if ( ! empty( $val ) && ! preg_match( '/%%|%[a-z_]+%|#[a-z_]+/i', $val ) ) {
				return self::sanitize_text( $val );
			}
		}

		$content = get_post_field( 'post_content', $post_id );
		if ( ! empty( $content ) ) {
			return self::sanitize_text( $content );
		}

		return '';
	}

	/**
	 * Extract a thumbnail URL from a YouTube or Vimeo video URL.
	 *
	 * @param string $src Video source URL.
	 * @return string Thumbnail URL, or empty string.
	 */
	private static function get_video_thumbnail( $src ) {
		if ( preg_match( VideoUrl::YT_REGEX, $src, $m ) ) {
			return 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg';
		}

		// Vimeo: fetch thumbnail via oEmbed (cached), shared with the facade.
		return VideoThumbnail::vimeo( $src );
	}

	/**
	 * Output JSON-LD VideoObject(s) in the page footer.
	 */
	public static function output_schema() {
		if ( 'on' !== get_option( 'swarmify_toggle_schema', 'on' ) ) {
			return;
		}

		if ( ! is_singular() ) {
			self::$videos = [];
			return;
		}

		if ( empty( self::$videos ) ) {
			return;
		}

		$post_id     = get_queried_object_id();
		$page_title  = wp_get_document_title();
		$upload_date = get_the_date( 'c', $post_id ) ?: gmdate( 'Y-m-d' );

		$schema_items           = [];
		static $url_to_id_cache = [];
		foreach ( self::$videos as $video ) {
			$description = self::get_description_fallback( $video['description'], $post_id );

			// swarmify:// resolves to a real URL only in the player — no
			// contentUrl to build here.
			$raw_src = $video['src'];
			if ( 0 === strpos( $raw_src, 'swarmify://' ) ) {
				continue;
			}
			$content_url = esc_url_raw( $raw_src );
			if ( empty( $content_url ) ) {
				continue;
			}

			$item = [
				'@type'      => 'VideoObject',
				'contentUrl' => $content_url,
				'name'       => self::sanitize_text( ! empty( $video['title'] ) ? $video['title'] : $page_title ),
				'uploadDate' => $upload_date,
			];

			// Google requires description for VideoObject rich results.
			if ( empty( $description ) ) {
				$description = self::sanitize_text(
					sprintf(
						/* translators: %s: site name. */
						esc_html__( 'Video on %s', 'swarmify' ),
						get_bloginfo( 'name' )
					)
				);
			}
			$item['description'] = $description;

			$thumbnail = ! empty( $video['poster'] ) ? esc_url_raw( $video['poster'] ) : '';
			if ( empty( $thumbnail ) && $post_id ) {
				$featured = get_the_post_thumbnail_url( $post_id, 'full' );
				if ( $featured ) {
					$thumbnail = esc_url_raw( $featured );
				}
			}
			if ( empty( $thumbnail ) ) {
				$thumb_candidate = self::get_video_thumbnail( $video['src'] );
				if ( ! empty( $thumb_candidate ) ) {
					$thumbnail = esc_url_raw( $thumb_candidate );
				}
			}
			if ( empty( $thumbnail ) ) {
				$site_icon = get_site_icon_url();
				if ( ! empty( $site_icon ) ) {
					$thumbnail = esc_url_raw( $site_icon );
				}
			}
			if ( ! empty( $thumbnail ) ) {
				$item['thumbnailUrl'] = $thumbnail;
			}

			$permalink = get_permalink( $post_id );
			if ( $permalink ) {
				$item['url'] = esc_url_raw( $permalink );
			}

			$src = $video['src'];
			if ( ! isset( $url_to_id_cache[ $src ] ) ) {
				// Known-external hosts can never be attachments — skip the
				// expensive DB lookup.
				$is_external = preg_match(
					'#^https?://(www\.)?(youtube\.com|youtu\.be|vimeo\.com|player\.vimeo\.com|dailymotion\.com|dai\.ly|twitch\.tv|facebook\.com|fb\.watch)/#i',
					$src
				) || preg_match( '#swarmcdn\.com#i', $src );
				if ( $is_external ) {
					$url_to_id_cache[ $src ] = 0;
				} else {
					$url_to_id_cache[ $src ] = attachment_url_to_postid( $src );
				}
			}
			$att_id = $url_to_id_cache[ $src ];
			if ( $att_id ) {
				$att_meta = wp_get_attachment_metadata( $att_id );
				if ( ! empty( $att_meta['length'] ) ) {
					$seconds          = (int) $att_meta['length'];
					$hours            = floor( $seconds / 3600 );
					$minutes          = floor( ( $seconds % 3600 ) / 60 );
					$secs             = $seconds % 60;
					$item['duration'] = 'PT'
						. ( $hours ? $hours . 'H' : '' )
						. ( $minutes ? $minutes . 'M' : '' )
						. $secs . 'S';
				}
			}

			$schema_items[] = $item;
		}

		if ( empty( $schema_items ) ) {
			return;
		}

		$publisher = [
			'@type' => 'Organization',
			'name'  => self::sanitize_text( get_bloginfo( 'name' ) ),
		];
		$logo_id   = get_theme_mod( 'custom_logo' );
		$logo_url  = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
		if ( empty( $logo_url ) ) {
			$logo_url = get_site_icon_url();
		}
		if ( ! empty( $logo_url ) ) {
			$publisher['logo'] = [
				'@type' => 'ImageObject',
				'url'   => esc_url_raw( $logo_url ),
			];
		}

		foreach ( $schema_items as &$item ) {
			$item['publisher'] = $publisher;
		}
		unset( $item );

		$output = [
			'@context' => 'https://schema.org',
		];

		if ( count( $schema_items ) === 1 ) {
			$output = array_merge( $output, $schema_items[0] );
		} else {
			$output['@graph'] = $schema_items;
		}

		$output = apply_filters( 'smartvideo_schema_data', $output );

		// The filter can return anything; a non-array would corrupt the
		// JSON-LD payload.
		if ( ! is_array( $output ) ) {
			return;
		}

		if ( empty( $output ) ) {
			return;
		}

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode( $output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG )
		);
	}
}
