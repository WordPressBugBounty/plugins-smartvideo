<?php
/**
 * SmartVideo::render_callback()
 *
 * Server-side rendering of the SmartVideo module. Produces the <smartvideo>
 * custom element, matching the D4 module's render() output.
 *
 * @package Swarmify\Divi5\Modules\SmartVideo
 * @since 2.3.0
 */

namespace Swarmify\Divi5\Modules\SmartVideo\SmartVideoTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Packages\Module\Module;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\Packages\Module\Options\Element\ElementComponents;

trait RenderCallbackTrait {
	use ModuleClassnamesTrait;
	use ModuleStylesTrait;
	use ModuleScriptDataTrait;

	/**
	 * SmartVideo render callback for the front-end.
	 *
	 * @since 2.3.0
	 *
	 * @param array     $attrs    Block attributes saved by VB.
	 * @param string    $content  Block content.
	 * @param \WP_Block $block    Parsed block object.
	 * @param object    $elements ModuleElements instance.
	 *
	 * @return string HTML output.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ) {
		wp_enqueue_style( 'smartvideo-divi5-builder-bundle-style' );

		// Helper to read innerContent value (always desktop for non-responsive fields).
		$get_val = function ( $attr_name, $default = '' ) use ( $attrs ) {
			return $attrs[ $attr_name ]['innerContent']['desktop']['value'] ?? $default;
		};

		// Read all video configuration attributes.
		// Defaults must match module.json — D5 omits unchanged values from saved content.
		$video_url_attr = $get_val( 'videoUrl' );
		$media_library  = $get_val( 'mediaLibrary' );
		$poster_source  = $get_val( 'posterSource', 'none' );
		$poster_image   = $get_val( 'posterImage' );
		$poster_url     = $get_val( 'posterUrl' );
		$aspect_ratio   = $get_val( 'aspectRatio', '' );
		// D5's range component saves values with a "px" suffix — strip to pure integers.
		$raw_width                          = (int) $get_val( 'videoWidth', '1280' );
		$raw_height                         = (int) $get_val( 'videoHeight', '720' );
		list( $video_width, $video_height ) = \Swarmify\Smartvideo\AspectRatio::resolve( $aspect_ratio, $raw_width, $raw_height );
		$autoplay                           = $get_val( 'autoplay', 'off' );
		$muted                              = $get_val( 'muted', 'off' );
		$loop                               = $get_val( 'loop', 'off' );
		$controls                           = $get_val( 'controls', 'on' );
		$playsinline                        = $get_val( 'playsinline', 'off' );
		$responsive                         = $get_val( 'responsive', 'on' );

		// New single URL field (v2.4+).
		$swarmify_url = '';
		if ( ! empty( $video_url_attr ) ) {
			$swarmify_url = \Swarmify\Smartvideo\VideoUrl::normalize( $video_url_attr );
		}

		// Media library fallback (new modules where user picks from library).
		if ( empty( $swarmify_url ) && ! empty( $media_library ) ) {
			$swarmify_url = $media_library;
		}

		// Backward compat: old source-type fields.
		if ( empty( $swarmify_url ) ) {
			$video_source     = $get_val( 'videoSource', 'media_library' );
			$youtube_url      = $get_val( 'youtubeUrl' );
			$vimeo_url        = $get_val( 'vimeoUrl' );
			$another_source   = $get_val( 'anotherSource' );
			$swarmify_url_val = $get_val( 'swarmifyUrl' );

			switch ( $video_source ) {
				case 'media_library':
					$swarmify_url = $media_library ?: 'https://swarmify.com/wp-content/uploads/SmartVideoIntroMain.mp4';
					break;

				case 'youtube':
					$swarmify_url = \Swarmify\Smartvideo\VideoUrl::normalize( $youtube_url );
					break;

				case 'vimeo':
					$swarmify_url = $vimeo_url;
					break;

				case 'swarmify_url':
					$swarmify_url = $swarmify_url_val;
					break;

				case 'another_source':
					$swarmify_url = $another_source;
					break;
			}
		}

		if ( empty( $swarmify_url ) ) {
			// Show the "No video selected" placeholder only in the Divi 5
			// Visual Builder. Frontend visitors must not see authoring
			// chrome on empty modules.
			if ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) {
				return \Swarmify\Smartvideo\AspectRatio::empty_placeholder();
			}
			return '';
		}

		// Auto-detect native video dimensions only for backward compat (no aspectRatio saved).
		// When user explicitly selects "custom" or a preset, respect their choice.
		if ( '' === $aspect_ratio && $swarmify_url ) {
			// Try to resolve WP attachment for dimension detection.
			$probe_url              = $media_library ?: $swarmify_url;
			static $url_to_id_cache = [];
			if ( ! isset( $url_to_id_cache[ $probe_url ] ) ) {
				// Skip external URLs that can't be WP attachments — avoids
				// expensive LIKE queries against the database.
				$is_external_host = preg_match(
					'#^https?://(www\.)?(youtube\.com|youtu\.be|vimeo\.com|player\.vimeo\.com|dailymotion\.com|dai\.ly|twitch\.tv|facebook\.com|fb\.watch)/#i',
					$probe_url
				);
				if ( $is_external_host ) {
					$url_to_id_cache[ $probe_url ] = 0;
				} else {
					$url_to_id_cache[ $probe_url ] = attachment_url_to_postid( $probe_url );
				}
			}
			$att_id = $url_to_id_cache[ $probe_url ];
			if ( $att_id ) {
				$meta = wp_get_attachment_metadata( $att_id );
				if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
					$video_width  = (int) $meta['width'];
					$video_height = (int) $meta['height'];
				}
			}
		}

		// Build <smartvideo> tag attributes.
		$tag_attrs = [
			'src'    => esc_url( $swarmify_url, array_merge( wp_allowed_protocols(), array( 'swarmify' ) ) ),
			'width'  => esc_attr( $video_width ),
			'height' => esc_attr( $video_height ),
		];

		// Responsive class.
		if ( 'on' === $responsive ) {
			$tag_attrs['class'] = 'swarm-fluid';
		}

		// Poster.
		if ( 'none' !== $poster_source ) {
			$poster_val = 'media_library' === $poster_source ? $poster_image : $poster_url;
			if ( $poster_val ) {
				$tag_attrs['poster'] = esc_url( $poster_val );
			}
		}

		$schema_poster = isset( $tag_attrs['poster'] ) ? $tag_attrs['poster'] : '';
		\Swarmify\Smartvideo\SchemaCollector::add( $swarmify_url, $schema_poster );

		// Boolean attributes.
		$bool_attrs = [
			'autoplay'    => $autoplay,
			'muted'       => $muted,
			'loop'        => $loop,
			'controls'    => $controls,
			'playsinline' => $playsinline,
		];

		foreach ( $bool_attrs as $attr_name => $attr_value ) {
			if ( 'on' === $attr_value ) {
				$tag_attrs[ $attr_name ] = $attr_name;
			}
		}

		// Build the attribute string.
		$attr_parts = [];
		foreach ( $tag_attrs as $key => $val ) {
			$attr_parts[] = sprintf( '%s="%s"', $key, $val );
		}
		$smartvideo_tag = sprintf( '<smartvideo %s></smartvideo>', implode( ' ', $attr_parts ) );

		$smartvideo_tag = \Swarmify\Smartvideo\Facade::wrap(
			$smartvideo_tag,
			[
				'src'      => $swarmify_url,
				'poster'   => $schema_poster,
				'width'    => $video_width,
				'height'   => $video_height,
				'autoplay' => 'on' === $autoplay,
			]
		);

		// Disabled warning — only in D5 Visual Builder, not on the frontend.
		$warning = '';
		if ( ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) &&
			( 'on' !== get_option( 'swarmify_status' ) || '' === get_option( 'swarmify_cdn_key', '' ) ) ) {
			$warning = sprintf(
				'<div style="background:#fcf0c0;border:1px solid #d4a72c;border-radius:4px;padding:8px 12px;margin-bottom:10px;font-size:13px;color:#6b5900">%s</div>',
				esc_html__( 'SmartVideo is currently disabled. Go to the SmartVideo settings page to enable it.', 'swarmify' )
			);
		}

		$inner_content = $warning . $smartvideo_tag;

		// Background component.
		$background_component = ElementComponents::component(
			[
				'attrs'         => $attrs['module']['decoration'] ?? [],
				'id'            => $block->parsed_block['id'],
				'orderIndex'    => $block->parsed_block['orderIndex'],
				'storeInstance' => $block->parsed_block['storeInstance'],
			]
		);

		$parent       = BlockParserStore::get_parent( $block->parsed_block['id'], $block->parsed_block['storeInstance'] );
		$parent_attrs = $parent->attrs ?? [];

		return Module::render(
			[
				'orderIndex'          => $block->parsed_block['orderIndex'],
				'storeInstance'       => $block->parsed_block['storeInstance'],
				'id'                  => $block->parsed_block['id'],
				'name'                => $block->block_type->name,
				'moduleCategory'      => $block->block_type->category,
				'attrs'               => $attrs,
				'elements'            => $elements,
				'classnamesFunction'  => [ self::class, 'module_classnames' ],
				'stylesComponent'     => [ self::class, 'module_styles' ],
				'scriptDataComponent' => [ self::class, 'module_script_data' ],
				'parentAttrs'         => $parent_attrs,
				'parentId'            => $parent->id ?? '',
				'parentName'          => $parent->blockName ?? '',
				'children'            => $background_component . $inner_content,
			]
		);
	}
}
