<?php

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
	$video = ! empty( $settings->video ) ? FLBuilderPhoto::get_attachment_data( $settings->video ) : null;
	$video_type = isset( $settings->video_type ) ? $settings->video_type : '';
	if ( 'media_library' === $video_type && isset( $video->url ) ) {
		$swarmify_url = $video->url;
	} elseif ( 'youtube' === $video_type ) {
		$swarmify_url = \Swarmify\Smartvideo\VideoUrl::normalize( $settings->youtube ?? '' );
	} elseif ( 'vimeo' === $video_type && ! empty( $settings->vimeo ) ) {
		$swarmify_url = $settings->vimeo;
	} elseif ( 'swarmify_url' === $video_type && ! empty( $settings->swarmify_url ) ) {
		$swarmify_url = $settings->swarmify_url;
	} elseif ( 'other_source' === $video_type && ! empty( $settings->other_source ) ) {
		$swarmify_url = $settings->other_source;
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
	echo \Swarmify\Smartvideo\AspectRatio::empty_placeholder();
	return;
}

	// Resolve dimensions from aspect ratio preset.
	$aspect_ratio = isset( $settings->aspect_ratio ) ? $settings->aspect_ratio : '';
	list( $sv_width, $sv_height ) = \Swarmify\Smartvideo\AspectRatio::resolve(
		$aspect_ratio,
		$settings->width,
		$settings->height
	);

	$responsive   = $settings->responsive ? 'class="' . esc_attr( 'swarm-fluid' ) . '"' : '';
	$poster_url   = '';
	if ( 'none' !== $settings->poster ) {
		$poster_url = 'media_library' === $settings->poster ? $settings->poster_internal_src : $settings->poster_external;
	}
	$poster       = ! empty( $poster_url ) ? sprintf( 'poster="%s"', esc_url( $poster_url )) : '';
	$autoplay     = $settings->autoplay ? 'autoplay' : '';
	$muted        = $settings->muted ? 'muted' : '';
	$loop         = $settings->loop ? 'loop' : '';
	$controls     = $settings->controls ? 'controls' : '';
	$video_inline = $settings->inline ? 'playsinline' : '';
	$preload_val  = isset( $settings->preload ) ? $settings->preload : 'auto';
	$preload_attr = ( 'auto' !== $preload_val ) ? sprintf( 'preload="%s"', esc_attr( $preload_val ) ) : '';

	\Swarmify\Smartvideo\SchemaCollector::add( $swarmify_url, $poster_url );

	printf(
		'<smartvideo src="%s" width="%s" height="%s" %s %s %s %s %s %s %s %s></smartvideo>',
		esc_url( $swarmify_url ),
		esc_attr( $sv_width ),
		esc_attr( $sv_height ),
		$poster,
		esc_attr( $muted ),
		$responsive,
		esc_attr( $autoplay ),
		esc_attr( $loop ),
		esc_attr( $controls ),
		esc_attr( $video_inline ),
		$preload_attr
	);

