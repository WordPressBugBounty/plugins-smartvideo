export function getSmartVideoElem( props ) {
	const { smartvideoEmbedLink, poster, posterMediaLibrary, posterAnoterSource, posterUrl, width, height, responsive, autoplay, loop, muted, controls, playsInline } = props.attributes;
	const responsiveClass = ( responsive ? 'swarm-fluid' : '' );
	const newSmartVideo   = document.createElement( 'smartvideo' );
	newSmartVideo.setAttribute( 'src', smartvideoEmbedLink );
	newSmartVideo.setAttribute( 'width', width );
	newSmartVideo.setAttribute( 'height', height );
	if ( 'none' !== poster ) {
		newSmartVideo.setAttribute( 'poster', posterUrl );
	}
	newSmartVideo.setAttribute( 'class', responsiveClass );
	if ( autoplay ) {
		newSmartVideo.setAttribute( 'autoplay', '' );
	}
	if ( muted ) {
		newSmartVideo.setAttribute( 'muted', '' );
	}
	if ( loop ) {
		newSmartVideo.setAttribute( 'loop', '' );
	}
	if ( controls ) {
		newSmartVideo.setAttribute( 'controls', '' );
	}
	if ( playsInline ) {
		newSmartVideo.setAttribute( 'playsinline', '' );
	}
	return newSmartVideo;
}
