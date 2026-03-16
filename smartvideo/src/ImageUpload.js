// Adapted from the WooCommerce components library
// - allowed because the library is licensed under GPL-3.0 and we're under AGPL

import { Component, Fragment } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Icon, upload } from '@wordpress/icons';

class ImageUpload extends Component {
	constructor() {
		super(...arguments);
		this.state = {
			frame: false,
		};
		this.openModal = this.openModal.bind(this);
		this.handleImageSelect = this.handleImageSelect.bind(this);
		this.removeImage = this.removeImage.bind(this);
	}

	openModal() {
		if (this.state.frame) {
			this.state.frame.open();
			return;
		}

		const frame = wp.media({
			title: __('Select or upload image', 'swarmify'),
			button: {
				text: __('Select', 'swarmify'),
			},
			library: {
				type: 'image',
			},
			multiple: false,
		});

		frame.on('select', this.handleImageSelect);
		frame.open();

		this.setState({ frame });
	}

	handleImageSelect() {
		const { onChange } = this.props;
		const selection = this.state.frame.state().get('selection').first();
		if (!selection) {
			return;
		}
		onChange(selection.toJSON().url);
	}

	removeImage() {
		const { onChange } = this.props;
		onChange(null);
	}

	componentWillUnmount() {
		if (this.state.frame) {
			this.state.frame.off('select', this.handleImageSelect);
		}
	}

	render() {
		const { className, image, compact } = this.props;

		if (compact) {
			return (
				<Fragment>
					{!!image && (
						<div className="sv-image-compact">
							<button
								type="button"
								className="sv-image-compact__preview"
								onClick={this.openModal}
								aria-label={__('Change image', 'swarmify')}
							>
								<img src={image} alt="" />
							</button>
							<button
								type="button"
								className="sv-image-compact__remove"
								onClick={this.removeImage}
								aria-label={__('Remove image', 'swarmify')}
							>
								<svg
									viewBox="0 0 16 16"
									fill="none"
									xmlns="http://www.w3.org/2000/svg"
								>
									<path
										d="M4 4l8 8M12 4l-8 8"
										stroke="currentColor"
										strokeWidth="2"
										strokeLinecap="round"
									/>
								</svg>
							</button>
						</div>
					)}
					{!image && (
						<Button onClick={this.openModal} isSecondary>
							<Icon icon={upload} />
							{__('Add an image', 'swarmify')}
						</Button>
					)}
				</Fragment>
			);
		}

		return (
			<Fragment>
				{!!image && (
					<div className={className}>
						<div>
							<img src={image} alt="" />
						</div>
						<Button isSecondary onClick={this.removeImage}>
							{__('Remove image', 'swarmify')}
						</Button>
					</div>
				)}
				{!image && (
					<div className={className}>
						<Button onClick={this.openModal} isSecondary>
							<Icon icon={upload} />
							{__('Add an image', 'swarmify')}
						</Button>
					</div>
				)}
			</Fragment>
		);
	}
}

export default ImageUpload;
