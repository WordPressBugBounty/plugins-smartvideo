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
	 * Sanitize text for JSON-LD output.
	 *
	 * Strips shortcodes, HTML, block markup, and entities.
	 * Trims to ~30 words at the nearest word boundary.
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
		// 1. Per-video description from builder.
		if ( ! empty( $per_video ) ) {
			return self::sanitize_text( $per_video );
		}

		if ( ! $post_id ) {
			return '';
		}

		// 2. Post excerpt.
		$excerpt = get_the_excerpt( $post_id );
		if ( ! empty( $excerpt ) ) {
			return self::sanitize_text( $excerpt );
		}

		// 3. SEO plugin meta description (raw post_meta, skip template variables).
		// Single get_post_meta() call returns all meta in one DB hit; we then
		// extract each key from the array (values are wrapped in [0 => ...]).
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

		// 4. First ~30 words of post content.
		$content = get_post_field( 'post_content', $post_id );
		if ( ! empty( $content ) ) {
			return self::sanitize_text( $content );
		}

		return '';
	}

	/**
	 * Extract a thumbnail URL from a YouTube or Vimeo video URL.
	 *
	 * YouTube thumbnails are deterministic. Vimeo thumbnails are fetched
	 * via oEmbed and cached in a transient for one week.
	 *
	 * @param string $src Video source URL.
	 * @return string Thumbnail URL, or empty string.
	 */
	private static function get_video_thumbnail( $src ) {
		// YouTube: extract video ID, use standard thumbnail.
		if ( preg_match( VideoUrl::YT_REGEX, $src, $m ) ) {
			return 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg';
		}

		// Vimeo: extract video ID, fetch thumbnail via oEmbed (cached).
		if ( preg_match( '/(?:vimeo\.com\/(?:video\/)?)(\d+)/', $src, $m ) ) {
			$cache_key = 'sv_vimeo_thumb_' . $m[1];
			$cached    = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}

			// wp_safe_remote_get blocks SSRF to private IPs / non-allowed hosts.
			$response = wp_safe_remote_get(
				'https://vimeo.com/api/oembed.json?url=' . rawurlencode( 'https://vimeo.com/' . $m[1] ),
				[ 'timeout' => 3 ]
			);

			// Network/transport failure. Cached briefly rather than not at all:
			// on a host that blocks outbound HTTP every frontend request would
			// otherwise pay the full timeout, forever. Short TTL so a transient
			// outage still recovers on its own.
			if ( is_wp_error( $response ) ) {
				set_transient( $cache_key, '', 15 * MINUTE_IN_SECONDS );
				return '';
			}

			$code = (int) wp_remote_retrieve_response_code( $response );

			// 4xx/5xx — Vimeo signalled a real error (private video, removed, rate-
			// limited, server fault). Cache empty short-TTL so we don't hammer it.
			if ( $code >= 400 ) {
				set_transient( $cache_key, '', DAY_IN_SECONDS );
				return '';
			}

			if ( 200 === $code ) {
				$data = json_decode( wp_remote_retrieve_body( $response ) );
				if ( is_object( $data ) && ! empty( $data->thumbnail_url ) ) {
					$thumb_url = (string) $data->thumbnail_url;
					$parts     = wp_parse_url( $thumb_url );
					// Reject javascript:, data:, http:, malformed URLs, and
					// authority-credentials (user[:pass]@host) before persisting
					// to the week-long transient. The cached value flows into
					// JSON-LD output only — SSRF on the outbound fetch is
					// handled by wp_safe_remote_get above.
					if ( is_array( $parts )
						&& isset( $parts['scheme'], $parts['host'] )
						&& 'https' === $parts['scheme']
						&& '' !== $parts['host']
						&& ! isset( $parts['user'] )
					) {
						set_transient( $cache_key, $thumb_url, WEEK_IN_SECONDS );
						return $thumb_url;
					}
				}
			}

			// Reached on unexpected status, empty/non-object body, OR a 200
			// whose thumbnail_url failed scheme/host/credentials validation.
			// Cache empty for a day to avoid hammering Vimeo on the next
			// pageview.
			set_transient( $cache_key, '', DAY_IN_SECONDS );
			return '';
		}

		return '';
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

			// swarmify:// is resolved to a media URL only at runtime (player-side),
			// so no valid contentUrl can be built here.
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

			// thumbnailUrl: poster → featured image → YouTube/Vimeo thumbnail.
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
			// Site icon as last-resort fallback.
			if ( empty( $thumbnail ) ) {
				$site_icon = get_site_icon_url();
				if ( ! empty( $site_icon ) ) {
					$thumbnail = esc_url_raw( $site_icon );
				}
			}
			if ( ! empty( $thumbnail ) ) {
				$item['thumbnailUrl'] = $thumbnail;
			}

			// url: the page this video appears on.
			$permalink = get_permalink( $post_id );
			if ( $permalink ) {
				$item['url'] = esc_url_raw( $permalink );
			}

			// Duration: best-effort for WP Media Library videos.
			$src = $video['src'];
			if ( ! isset( $url_to_id_cache[ $src ] ) ) {
				// Skip external URLs that can't be WP attachments — avoids
				// expensive LIKE queries against the database.
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

		// Publisher: site name + logo.
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

		// Attach publisher to each item.
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

		// Filter is user-modifiable — a non-array return would silently
		// corrupt the JSON-LD payload, so bail.
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
