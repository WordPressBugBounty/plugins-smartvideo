// External Dependencies
import React, { PureComponent, Fragment } from 'react';

// Internal Dependencies
import './style.css';

class SmartVideoDivi extends PureComponent {

	constructor() {
		super( ...arguments );
		this.containerRef = React.createRef();
		this.container = null;
  }
  
  static slug = 'smartvideo_divi_module';

  getSmartVideoElem( props ) {
    const { 
      video_src,
      media_library, 
      youtube,
      vimeo,
      another_source,
      video_width,
      video_height,
      poster_src,
      internal_poster,
      external_poster,
      autoplay,
      muted,
      loop,
      controls,
      playsinline,
      responsive
    } = props;

    const youtube_parser = url => {
			var regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#&?]*).*/;
			var match = url.match(regExp);
			return (match&&match[7].length===11)? match[7] : false;
    }
    
    const yEmbedUrl = youtube ? `https://www.youtube.com/embed/${youtube_parser(youtube)}` : '';

    // video link 
    let videoUrl = null;
    if( 'media_library' === video_src ){
      videoUrl = media_library;
    } else if ( 'youtube' === video_src ){
      videoUrl = yEmbedUrl;
    } else if( 'vimeo' === video_src ){
      videoUrl = vimeo;
    } else if( 'another_source' === video_src ){
      videoUrl = another_source;
    }

    // poster 
    const poster = 'media_library' === poster_src ? internal_poster : external_poster;

    const autoplayVal = "on" === autoplay ? "autoplay" : '';
    const mutedVal = "on" === muted ? "muted" : '';
    const loopVal = "on" === loop ? "loop" : '';
    const controlsVal = "on" === controls ? "controls" : '';
    const playsinlineVal = "on" === playsinline ? "playsinline" : '';
		const responsiveClass = ( "on" === responsive ? 'swarm-fluid' : '');
		var newSmartVideo = document.createElement('smartvideo');
		newSmartVideo.setAttribute('src', videoUrl);
		newSmartVideo.setAttribute('width', video_width);
		newSmartVideo.setAttribute('height', video_height);
		if("none" !== poster_src) {
			newSmartVideo.setAttribute('poster', poster);
		}
		newSmartVideo.setAttribute('class', responsiveClass);
		if( autoplayVal ) {
			newSmartVideo.setAttribute('autoplay', autoplayVal);
    }
    if(loopVal){
      newSmartVideo.setAttribute('loop', loopVal);
    }
    if(mutedVal){
      newSmartVideo.setAttribute('muted', mutedVal);
    }
    if(controlsVal){
      newSmartVideo.setAttribute('controls', controlsVal);
    }
    if(playsinlineVal){
      newSmartVideo.setAttribute('playsinline', playsinlineVal);
    }
		return newSmartVideo;
	}

	componentDidUpdate( prevProps ) {
		// PureComponent shallow-compares props, so this only fires when a prop actually changed.
		const containDiv = this.container;
		while(containDiv.firstChild) {
			containDiv.removeChild(containDiv.firstChild);
		}
		const newSmartVideo = this.getSmartVideoElem(this.props);
		containDiv.append(newSmartVideo);
	}

	componentDidMount(){
		this.container = this.containerRef.current;
    const containDiv = this.container;
    while(containDiv.firstChild) {
      containDiv.removeChild(containDiv.firstChild);
    }
    const newSmartVideo = this.getSmartVideoElem(this.props);
    containDiv.append(newSmartVideo);
		// console.log('mounted', this.container);
  }
  
  render() {
    return (
      <Fragment>
        <div ref={this.containerRef}></div>
      </Fragment>
    );
  }

}

export default SmartVideoDivi;