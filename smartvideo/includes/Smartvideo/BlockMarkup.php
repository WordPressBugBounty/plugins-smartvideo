<?php

namespace Swarmify\Smartvideo;

/**
 * Rebuilds the Gutenberg block's <smartvideo> markup from its stored attributes.
 *
 * The block is static, so its markup normally lives in post_content. Saves made
 * before 2.4.4 lost that markup to WordPress's kses sanitiser while keeping the
 * attributes in the block delimiter comment, which is what this reads.
 *
 * @since 2.4.4
 */
class BlockMarkup {

	/**
	 * Defaults from the block definition in src/gutenberg-block/index.js. The
	 * delimiter JSON omits any attribute still at its default.
	 */
	const DEFAULTS = array(
		'aspectRatio' => '16:9',
		'width'       => '1280',
		'height'      => '720',
		'responsive'  => true,
		'controls'    => true,
		'autoplay'    => false,
		'muted'       => false,
		'loop'        => false,
		'playsInline' => false,
		'poster'      => 'none',
	);

	/**
	 * Attribute name => rendered name, which differ in case for playsInline.
	 */
	const BOOLEAN_ATTRIBUTES = array(
		'autoplay'    => 'autoplay',
		'muted'       => 'muted',
		'loop'        => 'loop',
		'controls'    => 'controls',
		'playsInline' => 'playsinline',
	);

	/**
	 * Blocks saved before 2.4 had no single URL field. Mirrors legacySourceUrl()
	 * in src/gutenberg-block/index.js, which the block's own migrate() still runs.
	 */
	const LEGACY_SOURCE_KEYS = array(
		'youtube'        => 'youtube',
		'vimeo'          => 'vimeo',
		'media_library'  => 'videoInternalUrl',
		'swarmify_url'   => 'swarmifyUrl',
		'another_source' => 'anotherSource',
	);

	/**
	 * Blocks carrying pre-2.4 source keys are excluded: their deprecation
	 * declares the attribute with no default, so this was never their src.
	 */
	const DEFAULT_EMBED_LINK = 'https://swarmify.com/wp-content/uploads/SmartVideoIntroMain.mp4';

	/**
	 * @param  array $attrs Block attributes from the delimiter comment.
	 * @return string Empty when no video URL can be reconstructed.
	 */
	public static function from_attributes( $attrs ) {
		$is_legacy = false;

		if ( ! array_key_exists( 'smartvideoEmbedLink', $attrs ) ) {
			$source     = isset( $attrs['source'] ) ? $attrs['source'] : 'media_library';
			$legacy_key = isset( self::LEGACY_SOURCE_KEYS[ $source ] ) ? self::LEGACY_SOURCE_KEYS[ $source ] : '';

			if ( '' !== $legacy_key && isset( $attrs[ $legacy_key ] ) ) {
				$attrs['smartvideoEmbedLink'] = self::frozen_embed_url( $attrs[ $legacy_key ] );
				$is_legacy                    = true;
			} elseif ( ! self::has_legacy_source_keys( $attrs ) ) {
				$attrs['smartvideoEmbedLink'] = self::DEFAULT_EMBED_LINK;
			}
		}

		$src = isset( $attrs['smartvideoEmbedLink'] ) ? (string) $attrs['smartvideoEmbedLink'] : '';
		if ( '' !== $src ) {
			$src = esc_url( $src, array_merge( wp_allowed_protocols(), array( 'swarmify' ) ) );
		}

		if ( '' === $src ) {
			return '';
		}

		if ( ! array_key_exists( 'aspectRatio', $attrs )
			&& ( $is_legacy || array_key_exists( 'width', $attrs ) || array_key_exists( 'height', $attrs ) ) ) {
			$attrs['aspectRatio'] = 'custom';
		}

		$attrs                 += self::DEFAULTS;
		list( $width, $height ) = AspectRatio::resolve( $attrs['aspectRatio'], $attrs['width'], $attrs['height'] );

		$markup = '<smartvideo src="' . esc_attr( $src ) . '"'
			. ' width="' . esc_attr( $width ) . '"'
			. ' height="' . esc_attr( $height ) . '"';

		$poster = isset( $attrs['posterUrl'] ) ? esc_url( $attrs['posterUrl'] ) : '';
		if ( 'none' !== $attrs['poster'] && '' !== $poster ) {
			$markup .= ' poster="' . esc_attr( $poster ) . '"';
		}

		$markup .= ' class="' . ( $attrs['responsive'] ? 'swarm-fluid' : '' ) . '"';

		foreach ( self::BOOLEAN_ATTRIBUTES as $attr => $rendered ) {
			if ( ! empty( $attrs[ $attr ] ) ) {
				$markup .= ' ' . $rendered . '=""';
			}
		}

		if ( ! empty( $attrs['preload'] ) && 'auto' !== $attrs['preload'] ) {
			$markup .= ' preload="' . esc_attr( $attrs['preload'] ) . '"';
		}

		return $markup . '></smartvideo>';
	}

	// Mirrors deriveLegacyEmbedUrlFrozen in src/gutenberg-block/index.js so the repair reproduces the historical save() output.
	private static function frozen_embed_url( $url ) {
		if ( preg_match( '#^(?:https?://)?(?:(?:www|m|music)\.)?(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?|shorts)/|.*[?&]v=)|youtu\.be/)([A-Za-z0-9_-]{11})#', (string) $url, $match ) ) {
			return 'https://www.youtube.com/embed/' . $match[1];
		}

		return $url;
	}

	private static function has_legacy_source_keys( $attrs ) {
		if ( array_key_exists( 'source', $attrs ) ) {
			return true;
		}

		foreach ( self::LEGACY_SOURCE_KEYS as $legacy_key ) {
			if ( array_key_exists( $legacy_key, $attrs ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Put the element back into one block's saved body, keeping the wrapper
	 * exactly as stored - it carries alignment and custom classes this cannot
	 * reconstruct from attributes.
	 *
	 * @return string The body unchanged when it is intact or not repairable.
	 */
	public static function repair_body( $block_content, $attrs ) {
		$markup = self::from_attributes( $attrs );
		if ( '' === $markup ) {
			return $block_content;
		}

		$open_match = array();
		if ( ! preg_match( '/<smartvideo\b/', $block_content, $open_match, PREG_OFFSET_CAPTURE ) ) {
			$close = strrpos( $block_content, '</div>' );

			return false === $close ? $block_content : substr_replace( $block_content, $markup, $close, 0 );
		}
		$open = $open_match[0][1];

		// save() always emits the pair, so an opener with no closing tag is deliberately left alone.
		$end = strpos( $block_content, '</smartvideo>', $open );
		if ( false === $end ) {
			return $block_content;
		}

		$length    = $end + strlen( '</smartvideo>' ) - $open;
		$element   = substr( $block_content, $open, $length );
		$tag_end   = strpos( $element, '>' );
		$tag       = false === $tag_end ? '' : substr( $element, 0, $tag_end + 1 );
		$protocols = array_merge( wp_allowed_protocols(), array( 'swarmify' ) );
		$src       = esc_url( self::element_src( $markup ), $protocols );
		$stored    = esc_url( self::element_src( $tag ), $protocols );

		// The default clip must not displace a stored src.
		if ( $stored === $src || self::DEFAULT_EMBED_LINK === $src ) {
			return $block_content;
		}

		if ( preg_match( '/\ssrc=(["\'])(.*?)\1/', $tag, $match, PREG_OFFSET_CAPTURE ) ) {
			$element = substr_replace( $element, esc_attr( $src ), $match[2][1], strlen( $match[2][0] ) );
		} else {
			$element = substr_replace( $element, ' src="' . esc_attr( $src ) . '"', strlen( '<smartvideo' ), 0 );
		}

		return substr_replace( $block_content, $element, $open, $length );
	}

	private static function element_src( $element ) {
		return preg_match( '/\ssrc=(["\'])(.*?)\1/', $element, $match ) ? html_entity_decode( $match[2], ENT_QUOTES, 'UTF-8' ) : '';
	}
}
