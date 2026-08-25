<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Disabled warning — only in BB editor, not on the frontend.
if ( FLBuilderModel::is_builder_active() &&
	( 'on' !== get_option( 'swarmify_status' ) || '' === get_option( 'swarmify_cdn_key', '' ) ) ) {
	printf(
		'<div style="background:#fcf0c0;border:1px solid #d4a72c;border-radius:4px;padding:8px 12px;margin-bottom:10px;font-size:13px;color:#6b5900">%s</div>',
		esc_html__( 'SmartVideo is currently disabled. Go to the SmartVideo settings page to enable it.', 'swarmify' )
	);
}

// New single URL field (v2.4+).
$swarmify_url = ! empty( $settings->video_url )
	? \Swarmify\Smartvideo\VideoUrl::normalize( $settings->video_url )
	: '';

// Backward compat: old source-type fields.
if ( empty( $swarmify_url ) ) {
	$video      = ! empty( $settings->video ) ? FLBuilderPhoto::get_attachment_data( $settings->video ) : null;
	$video_type = isset( $settings->video_type ) ? $settings->video_type : '';
	if ( 'media_library' === $video_type && isset( $video->url ) ) {
		$swarmify_url = $video->url;
	} elseif ( 'youtube' === $video_type ) {
		$swarmify_url = \Swarmify\Smartvideo\VideoUrl::normalize( $settings->youtube ?? '' );
	} elseif ( 'vimeo' === $video_type && ! empty( $settings->vimeo ) ) {
		$swarmify_url = \Swarmify\Smartvideo\VideoUrl::normalize( $settings->vimeo );
	} elseif ( 'swarmify_url' === $video_type && ! empty( $settings->swarmify_url ) ) {
		$swarmify_url = \Swarmify\Smartvideo\VideoUrl::normalize( $settings->swarmify_url );
	} elseif ( 'other_source' === $video_type && ! empty( $settings->other_source ) ) {
		$swarmify_url = \Swarmify\Smartvideo\VideoUrl::normalize( $settings->other_source );
	}
}

// Media library fallback (new modules where user picks from library).
if ( empty( $swarmify_url ) ) {
	$video = ! empty( $settings->video ) ? FLBuilderPhoto::get_attachment_data( $settings->video ) : null;
	if ( isset( $video->url ) ) {
		$swarmify_url = $video->url;
	}
}

if ( empty( $swarmify_url ) ) {
	// Show the "No video selected" placeholder only inside the BB editor.
	// Frontend visitors must not see authoring chrome on empty modules.
	if ( FLBuilderModel::is_builder_active() ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static self-built markup; the only dynamic value is escaped via esc_html__() in empty_placeholder(), and wp_kses would lowercase the SVG viewBox.
		echo \Swarmify\Smartvideo\AspectRatio::empty_placeholder();
	}
	return;
}

	// Beaver Builder omits unset module settings on legacy saves, so every
	// read here needs a default to avoid PHP 8.2 dynamic-property deprecations.
	$aspect_ratio                 = $settings->aspect_ratio ?? '';
	list( $sv_width, $sv_height ) = \Swarmify\Smartvideo\AspectRatio::resolve(
		$aspect_ratio,
		$settings->width ?? 1280,
		$settings->height ?? 720
	);

	$poster_url = '';
	if ( 'none' !== ( $settings->poster ?? 'none' ) ) {
		// poster_internal_src is the URL Beaver Builder derives from the
		// poster_internal photo field; it and poster_external may be unset
		// when the chooser is shown but no image was picked.
		$poster_url = 'media_library' === $settings->poster ? ( $settings->poster_internal_src ?? '' ) : ( $settings->poster_external ?? '' );
	}
	\Swarmify\Smartvideo\SchemaCollector::add( $swarmify_url, $poster_url );

	// Build attribute list — empty/disabled attrs are skipped so we never
	// emit double-space runs inside the tag. Canonical order:
	// src, poster, autoplay, muted, loop, controls, playsinline, width, height, class.
	$attrs   = array();
	$attrs[] = 'src="' . esc_url( $swarmify_url, array_merge( wp_allowed_protocols(), array( 'swarmify' ) ) ) . '"';
	if ( ! empty( $poster_url ) ) {
		$attrs[] = 'poster="' . esc_url( $poster_url ) . '"';
	}
	if ( '1' === ( $settings->autoplay ?? '' ) ) {
		$attrs[] = 'autoplay';
	}
	if ( '1' === ( $settings->muted ?? '' ) ) {
		$attrs[] = 'muted';
	}
	if ( '1' === ( $settings->loop ?? '' ) ) {
		$attrs[] = 'loop';
	}
	if ( '1' === ( $settings->controls ?? '' ) ) {
		$attrs[] = 'controls';
	}
	if ( '1' === ( $settings->inline ?? '' ) ) {
		$attrs[] = 'playsinline';
	}
	$attrs[] = 'width="' . esc_attr( $sv_width ) . '"';
	$attrs[] = 'height="' . esc_attr( $sv_height ) . '"';
	if ( '1' === ( $settings->responsive ?? '' ) ) {
		$attrs[] = 'class="swarm-fluid"';
	}

	$smartvideo = '<smartvideo ' . implode( ' ', $attrs ) . '></smartvideo>';

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes are escaped at construction (esc_url/esc_attr); the facade wrapper escapes its own markup; the <smartvideo> tag is emitted deliberately, not passed through kses.
	echo \Swarmify\Smartvideo\Facade::wrap(
		$smartvideo,
		array(
			'src'      => $swarmify_url,
			'poster'   => $poster_url,
			'width'    => $sv_width,
			'height'   => $sv_height,
			'autoplay' => '1' === ( $settings->autoplay ?? '' ),
		)
	);
