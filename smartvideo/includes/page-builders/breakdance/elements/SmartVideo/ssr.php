<?php
/**
 * @var array $propertiesData
 */

$smartvideo_props    = $propertiesData['content']['smartvideo'] ?? array();
$smartvideo_playback = $propertiesData['content']['playback'] ?? array();
$smartvideo_raw_url  = $smartvideo_props['url'] ?? '';
if ( '' === $smartvideo_raw_url ) {
	$smartvideo_raw_url = $smartvideo_props['media']['url'] ?? '';
}
$smartvideo_src    = \Swarmify\Smartvideo\VideoUrl::normalize( $smartvideo_raw_url );
$smartvideo_poster = $smartvideo_props['poster']['url'] ?? '';
if ( '' === $smartvideo_poster ) {
	$smartvideo_poster = $smartvideo_props['poster_url'] ?? '';
}

// Neither signal alone covers the builder: the canvas page load carries the GET
// param with $isBuilder = false, and the control-change re-render is the reverse.
// The param is forgeable, so it only counts alongside edit permission.
$smartvideo_in_builder = ( $isBuilder ?? false )
	|| ( function_exists( 'Breakdance\isRequestFromBuilderIframe' ) && \Breakdance\isRequestFromBuilderIframe()
		&& function_exists( 'Breakdance\Permissions\hasMinimumPermission' ) && \Breakdance\Permissions\hasMinimumPermission( 'edit' ) );

// Settings, not raw get_option: an absent option row defaults to 'on', so a
// fresh site doesn't see a false "disabled" banner.
$smartvideo_settings = new \Swarmify\Smartvideo\Settings( 'smartvideo', defined( 'SWARMIFY_PLUGIN_VERSION' ) ? SWARMIFY_PLUGIN_VERSION : '' );

if ( $smartvideo_in_builder ) {
	$smartvideo_notice = '';
	if ( 'on' !== $smartvideo_settings->get( 'swarmify_status' ) ) {
		$smartvideo_notice = esc_html__( 'SmartVideo is currently disabled. Go to the SmartVideo settings page to enable it.', 'swarmify' );
	} elseif ( '' === $smartvideo_settings->get( 'swarmify_cdn_key' ) ) {
		$smartvideo_notice = esc_html__( 'SmartVideo has no CDN key yet. Connect your account on the SmartVideo settings page.', 'swarmify' );
	}
	if ( '' !== $smartvideo_notice ) {
		printf(
			'<div style="background:#fcf0c0;border:1px solid #d4a72c;border-radius:4px;padding:8px 12px;margin-bottom:10px;font-size:13px;color:#6b5900">%s</div>',
			$smartvideo_notice // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- both branches are esc_html__() output.
		);
	}
}

// Breakdance truthiness-checks the include's return value, so a guard clause
// here would read as "this element has no ssr.php" and print an error instead.
if ( '' === $smartvideo_src ) {
	if ( $smartvideo_in_builder ) {
		// The Section renders children as flex items, so an unsized div shrinks to its glyph.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static self-built markup; the only dynamic value is escaped via esc_html__() in empty_placeholder(), and wp_kses would lowercase the SVG viewBox.
		echo '<div style="width:100%">' . \Swarmify\Smartvideo\AspectRatio::empty_placeholder() . '</div>';
	}
} elseif ( $smartvideo_in_builder ) {
	$smartvideo_ratio = $smartvideo_props['aspect_ratio'] ?? '16:9';
	if ( ! array_key_exists( $smartvideo_ratio, \Swarmify\Smartvideo\AspectRatio::get_options() ) ) {
		$smartvideo_ratio = '16:9';
	}

	list( $smartvideo_width, $smartvideo_height ) = \Swarmify\Smartvideo\AspectRatio::resolve(
		$smartvideo_ratio,
		$smartvideo_props['width'] ?? 0,
		$smartvideo_props['height'] ?? 0
	);
	$smartvideo_aspect_ratio  = $smartvideo_height > 0 ? $smartvideo_width . '/' . $smartvideo_height : '16/9';
	$smartvideo_preview_url   = $smartvideo_poster;

	if ( '' === $smartvideo_preview_url && preg_match( \Swarmify\Smartvideo\VideoUrl::YT_REGEX, (string) $smartvideo_raw_url, $smartvideo_match ) ) {
		$smartvideo_preview_url = 'https://i.ytimg.com/vi/' . $smartvideo_match[1] . '/hqdefault.jpg';
	}

	$smartvideo_theme_vars = \Swarmify\Smartvideo\Facade::inline_style( $smartvideo_settings );

	$smartvideo_preview_style = 'background:#2d2d2d;aspect-ratio:' . $smartvideo_aspect_ratio . ';width:100%;max-width:100%;border-radius:4px;background-size:cover;background-position:center;';
	if ( '' !== $smartvideo_preview_url ) {
		$smartvideo_preview_style .= 'background-image:url(' . esc_url( $smartvideo_preview_url ) . ');';
	}
	if ( '' !== $smartvideo_theme_vars ) {
		$smartvideo_preview_style .= $smartvideo_theme_vars . ';';
	}

	// sv-facade + the facade stylesheet (enqueued for the canvas) draw the same
	// big-play button the player renders, in the site's configured shape/colors.
	echo '<div class="smartvideo-builder-preview sv-facade" style="' . esc_attr( $smartvideo_preview_style ) . '">'
		. '<button type="button" class="sv-facade-btn' . esc_attr( \Swarmify\Smartvideo\Facade::shape_modifier( $smartvideo_settings ) ) . '" tabindex="-1" aria-hidden="true"><span class="sv-facade-btn__glyph"></span></button>'
		. '</div>';
} else {
	// esc_url_raw strips the quote but leaves square brackets intact, and an
	// unencoded one would close the shortcode early.
	$smartvideo_url_att = static function ( $name, $url, $protocols = null ) {
		return ' ' . $name . '="' . str_replace(
			array( '[', ']' ),
			array( '%5B', '%5D' ),
			esc_url_raw( $url, $protocols )
		) . '"';
	};

	// swarmify:// is not in WordPress's default allowed-protocol list.
	$smartvideo_atts = $smartvideo_url_att( 'src', $smartvideo_src, array_merge( wp_allowed_protocols(), array( 'swarmify' ) ) );

	if ( '' !== $smartvideo_poster ) {
		$smartvideo_atts .= $smartvideo_url_att( 'poster', $smartvideo_poster );
	}

	$smartvideo_ratio = $smartvideo_props['aspect_ratio'] ?? '';
	if ( array_key_exists( $smartvideo_ratio, \Swarmify\Smartvideo\AspectRatio::get_options() ) ) {
		$smartvideo_atts .= ' aspect_ratio="' . $smartvideo_ratio . '"';
	}

	if ( 'custom' === $smartvideo_ratio ) {
		foreach ( array( 'width', 'height' ) as $smartvideo_dimension ) {
			$smartvideo_size = (int) ( $smartvideo_props[ $smartvideo_dimension ] ?? 0 );
			if ( $smartvideo_size > 0 ) {
				$smartvideo_atts .= ' ' . $smartvideo_dimension . '="' . $smartvideo_size . '"';
			}
		}
	}

	// An unchecked toggle is indistinguishable from an absent one, and an omitted
	// attribute falls back to the site default.
	foreach ( array( 'autoplay', 'muted', 'loop', 'controls', 'playsinline', 'responsive' ) as $smartvideo_flag ) {
		$smartvideo_atts .= ' ' . $smartvideo_flag . '="' . ( empty( $smartvideo_playback[ $smartvideo_flag ] ) ? 'false' : 'true' ) . '"';
	}

	echo do_shortcode( '[smartvideo' . $smartvideo_atts . ']' );
}
