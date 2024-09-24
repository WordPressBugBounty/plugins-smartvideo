import { getSmartVideoElem } from './smartvideo';

/**
 * WordPress dependencies
 */
const {
	Button,
	PanelBody,
	Disabled,
	SelectControl,
	ToggleControl,
	TextControl,
	withNotices,
} = wp.components;

const {
	InspectorControls,
	MediaPlaceholder,
	MediaUpload,
	MediaUploadCheck,
} = wp.blockEditor;

const {
	Component,
	createRef,
} = wp.element;

const {
	__,
	sprintf,
} = wp.i18n;

const {
	compose,
	withInstanceId,
} = wp.compose;

const {
	withSelect,
} = wp.data;

const ALLOWED_MEDIA_TYPES = [ 'video' ];
const VIDEO_POSTER_ALLOWED_MEDIA_TYPES = [ 'image' ];

class VideoEdit extends Component {
	constructor() {
		super( ...arguments );
		this.containerRef = createRef();
		this.container = null;
	}

	componentDidUpdate( prevProps ) {
		if ( prevProps.attributes !== this.props.attributes ) {
			const containDiv = this.container;
			while ( containDiv.firstChild ) {
				containDiv.removeChild( containDiv.firstChild );
			}
			const newSmartVideo = getSmartVideoElem( this.props );
			containDiv.append( newSmartVideo );
			// console.log( newSmartVideo );
			// console.log( 'did-update', containDiv );
		}
	}

	componentDidMount() {
		this.container = this.containerRef.current;
		const newSmartVideo = getSmartVideoElem( this.props );
		// console.log( 'newSmartVideo', newSmartVideo.outerHTML );
		this.container.append( newSmartVideo );
		// console.log( 'mounted', this.container );
	}

	setAttributeVal( attribute ) {
		return ( newValue ) => {
			this.props.setAttributes( { [ attribute ]: newValue } );
		};
	}

	render() {
		const {
			autoplay,
			muted,
			loop,
			width,
			height,
			controls,
			playsInline,
			responsive,
			poster,
			posterMediaLibrary,
			posterId,
			posterAnoterSource,
			posterUrl,
			source,
			videoInternalUrl,
			videoId,
			anotherSource,
			youtube,
			vimeo,
			smartvideoEmbedLink,
		} = this.props.attributes;

		const {
			setAttributes,
		} = this.props;

		// poster image
		const posterVal = 'none' !== poster ? posterUrl : '';

		// Fix for non-storage of 'anotherSource'
		if ( 'another_source' === source &&
			! anotherSource ) {
			setAttributes( { anotherSource: smartvideoEmbedLink } );                
		}

		// define supported media upload
		const ALLOWED_MEDIA_TYPES = [ 'video' ];
		const VIDEO_POSTER_ALLOWED_MEDIA_TYPES = [ 'image' ];

		// video fields handler
		const onSelectVideo = media => {
			setAttributes( {
				videoInternalUrl: media.url,
				smartvideoEmbedLink: media.url, // store to use for embed
				videoId: media.id,
			} );
		};
		const youtube_parser = url => {
			let regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#\&\?]*).*/;
			let match = url.match( regExp );
			return ( match && match[ 7 ].length == 11 ) ? match[ 7 ] : false;
		};
		const onChangeVimeo = ( newValue ) => {
			setAttributes( { vimeo: newValue } );
			setAttributes( { smartvideoEmbedLink: newValue } );
		};
		const onChangeYouTube = ( newValue ) => {
			const yEmbedUrl = `https://www.youtube.com/embed/${ youtube_parser( newValue ) }`;
			setAttributes( { youtube: newValue } );
			setAttributes( { smartvideoEmbedLink: yEmbedUrl } );
		};
		const onChangeAnotherSource = ( newValue ) => {
			setAttributes( { anotherSource: newValue } );
			setAttributes( { smartvideoEmbedLink: newValue } );
		};

		// poster fields handler
		const onChangePosterMediaLibrary = media => {
			setAttributes( {
				posterMediaLibrary: media.url,
				posterId: media.id,
				posterUrl: media.url,
			} );
		};
		const onChangePosterAnotherSource = newValue => {
			setAttributes( {
				posterAnoterSource: newValue,
				posterUrl: newValue,
			} );
		};

		// help message
		function getAutoplayHelp( checked ) {
			return checked ? __( 'Note: Autoplaying videos may cause usability issues for some visitors.' ) : null;
		}

		return (
			<React.Fragment>
				<InspectorControls>
					<PanelBody title={ __( 'Video' ) } >
						<SelectControl
							label={ __( 'Source' ) }
							value={ source }
							onChange={ this.setAttributeVal( 'source' ) }
							options={ [
								{ value: 'media_library', label: __( 'Media library' ) },
								{ value: 'youtube', label: __( 'YouTube' ) },
								{ value: 'vimeo', label: __( 'Vimeo' ) },
								{ value: 'another_source', label: __( 'Another source' ) },
							] }
						/>
						{ 'media_library' === source && (
							<MediaUploadCheck>
								<MediaUpload
									onSelect={ onSelectVideo }
									allowedTypes={ ALLOWED_MEDIA_TYPES }
									value={ videoId }
									render={ ( { open } ) => (
										<Button
											isDefault
											onClick={ open }
										>
											{ ! videoInternalUrl ? __( 'Select video' ) : __( 'Replace video' ) }
										</Button>
									) }
								/>
							</MediaUploadCheck>
						) }
						{ 'youtube' === source && (
							<TextControl
								label="YouTube link"
								value={ youtube }
								onChange={ onChangeYouTube }
							/>
						) }
						{ 'vimeo' === source && (
							<TextControl
								label="Vimeo link"
								value={ vimeo }
								onChange={ onChangeVimeo }
							/>
						) }
						{ 'another_source' === source && (
							<TextControl
								label="Video Url"
								value={ anotherSource }
								onChange={ onChangeAnotherSource }
							/>
						) }
					</PanelBody>
					<PanelBody title={ __( 'Poster' ) }
						initialOpen={ false } >
						<SelectControl
							label={ __( 'Source' ) }
							value={ poster }
							onChange={ this.setAttributeVal( 'poster' ) }
							options={ [
								{ value: 'media_library', label: __( 'Media library' ) },
								{ value: 'another_source', label: __( 'Another source' ) },
								{ value: 'none', label: __( 'None' ) },
							] }
						/>
						{ 'media_library' === poster && (
							<MediaUploadCheck>
								<MediaUpload
									onSelect={ onChangePosterMediaLibrary }
									allowedTypes={ VIDEO_POSTER_ALLOWED_MEDIA_TYPES }
									value={ posterId }
									render={ ( { open } ) => (
										<Button
											isDefault
											onClick={ open }
										>
											{ ! posterMediaLibrary ? __( 'Select poster image' ) : __( 'Replace image' ) }
										</Button>
									) }
								/>
							</MediaUploadCheck>
						) }
						{ 'another_source' === poster && (
							<TextControl
								label="Poster Url"
								value={ posterAnoterSource }
								onChange={ onChangePosterAnotherSource }
							/>
						) }
					</PanelBody>
					<PanelBody title={ __( 'Basic options' ) } initialOpen={ false } >
						<TextControl
							label={ __( 'Width' ) }
							value={ width }
							onChange={ this.setAttributeVal( 'width' ) }
						/>
						<TextControl
							label={ __( 'Height' ) }
							value={ height }
							onChange={ this.setAttributeVal( 'height' ) }
						/>
						<ToggleControl
							label={ __( 'Autoplay' ) }
							onChange={ this.setAttributeVal( 'autoplay' ) }
							checked={ autoplay }
							// help={ getAutoplayHelp }
						/>
						<ToggleControl
							label={ __( 'Muted' ) }
							onChange={ this.setAttributeVal( 'muted' ) }
							checked={ muted }
						/>
						<ToggleControl
							label={ __( 'Loop' ) }
							onChange={ this.setAttributeVal( 'loop' ) }
							checked={ loop }
						/>
					</PanelBody>
					<PanelBody title={ __( 'Advanced options' ) } initialOpen={ false }>
						<ToggleControl
							label={ __( 'Controls' ) }
							onChange={ this.setAttributeVal( 'controls' ) }
							checked={ controls }
						/>
						<ToggleControl
							label={ __( 'Play inline' ) }
							onChange={ this.setAttributeVal( 'playsInline' ) }
							checked={ playsInline }
						/>
						<ToggleControl
							label={ __( 'Responsive' ) }
							onChange={ this.setAttributeVal( 'responsive' ) }
							checked={ responsive }
						/>
					</PanelBody>
				</InspectorControls>
				<div ref={ this.containerRef }></div>
			</React.Fragment>
		);
	}
}

export default compose( [
	withSelect( ( select ) => {
		const { getSettings } = select( 'core/block-editor' );
		const { __experimentalMediaUpload } = getSettings();
		return {
			mediaUpload: __experimentalMediaUpload,
		};
	} ),
	withNotices,
	withInstanceId,
] )( VideoEdit );
