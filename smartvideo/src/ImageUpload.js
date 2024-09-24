// Adapted from the WooCommerce components library
// - allowed because the library is licensed under GPL-3.0 and we're under AGPL

/**
 * External dependencies
 */
import { createElement, Component, Fragment } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Icon, upload } from '@wordpress/icons';

class ImageUpload extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			frame: false,
		};
		this.openModal = this.openModal.bind( this );
		this.handleImageSelect = this.handleImageSelect.bind( this );
		this.removeImage = this.removeImage.bind( this );
	}

	openModal() {
		if ( this.state.frame ) {
			this.state.frame.open();
			return;
		}

		const frame = wp.media( {
			title: __( 'Select or upload image', 'swarmify' ),
			button: {
				text: __( 'Select', 'swarmify' ),
			},
			library: {
				type: 'image',
			},
			multiple: false,
		} );

		frame.on( 'select', this.handleImageSelect );
		frame.open();

		this.setState( { frame } );
	}

	handleImageSelect() {
		const { onChange } = this.props;
		const attachment = this.state.frame
			.state()
			.get( 'selection' )
			.first()
			.toJSON()
			.url;
		onChange( attachment );
	}

	removeImage() {
		const { onChange } = this.props;
		onChange( null );
	}

	render() {
		const { className, image } = this.props;
		return (
			<Fragment>
				{ !! image && (
					<div className={ className }>
						<div>
							<img src={ image } alt="" />
						</div>
						<Button 
							isSecondary
							onClick={ this.removeImage }>
							{ __( 'Remove image', 'swarmify' ) }
						</Button>
					</div>
				) }
				{ ! image && (
					<div className={ className }>
						<Button
							onClick={ this.openModal }
							isSecondary>
							<Icon icon={ upload } />
							{ __( 'Add an image', 'swarmify' ) }
						</Button>
					</div>
				) }
			</Fragment>
		);
	}
}

export default ImageUpload;
