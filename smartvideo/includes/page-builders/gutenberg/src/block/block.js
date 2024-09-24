/**
 * BLOCK: smartvideo-guten
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */

// Import CSS.
import './editor.scss';
import './style.scss';

import icon from './icon';
import edit from './edit';
import { getSmartVideoElem } from './smartvideo';

const { __ }                = wp.i18n;
const { registerBlockType } = wp.blocks;

/**
 * Register: aa Gutenberg Block.
 */
registerBlockType(
	'smartvideo/block-smartvideo-guten',
	{
		title: __( 'SmartVideo' ),
		description: __( 'SmartVideo makes building a beautiful, professional video experience for your site effortless.' ),
		icon: icon,
		category: 'common',
		keywords: [
		__( 'video' ),
		__( 'smartvideo' ),
		__( 'embed' ),
		],
		supports: {
			align: true,
		},
		attributes: {
			autoplay: {
				type: 'boolean',
				default: false,
					},
					loop: {
						type: 'boolean',
						default: false,
							},
							muted: {
								type: 'boolean',
								default: false,
									},
									width: {
										type: 'string',
										default: '1280',
											},
											height: {
												type: 'string',
												default: '720',
													},
													controls: {
														type: 'boolean',
														default: true,
															},
															playsInline: {
																type: 'boolean',
																default: false,
																	},
																	responsive: {
																		type: 'boolean',
																		default: true,
																			},
																			poster: {
																				type: 'string',
																				default: 'none',
																					},
																					posterId: {
																						type: 'number',
																					},
																					posterMediaLibrary: {
																						type: 'string',
																					},
																					posterAnoterSource: {
																						type: 'string',
																					},
																					posterUrl: {
																						type: 'string',
																					},
																					source: {
																						type: 'string',
																						default: 'media_library',
																							},
																							videoId: {
																								type: 'number',
																							},
																							videoInternalUrl: {
																								type: 'string',
																							},
																							youtube: {
																								type: 'string',
																							},
																							vimeo: {
																								type: 'string',
																							},
																							anotherSource: {
																								type: 'string',
																							},
																							smartvideoEmbedLink: {
																								type: 'string',
																								default: 'https://swarmify.com/wp-content/uploads/SmartVideoIntroMain.mp4',
																									},
																									},

																									/**
																									 * The edit function describes the structure of your block in the context of the editor.
																							 *
																									 * @param {Object} props Props.
																									 * @returns {Mixed} JSX Component.
																									 */
																									edit,
																									/**
																									 * The save function defines the way in which the different
																									 *
																									 * @param {Object} props Props.
																									 * @returns {Mixed} JSX Frontend HTML.
																									 */
																									save: ( props ) => {
																											const { smartvideoEmbedLink, poster, posterMediaLibrary, posterAnoterSource, posterUrl, width, height, responsive, autoplay, loop, muted, controls, playsInline } = props.attributes;
																											const posterVal           = 'none' !== poster ? posterUrl : '';
																											const smartVideoEl        = getSmartVideoElem( props );
																											const smartVideoHtml      = smartVideoEl.outerHTML;
																											const dangerHtml          = { __html: smartVideoHtml };
																											return (
																											< React.Fragment >
																										< div dangerouslySetInnerHTML = { dangerHtml } > < / div >
																											< / React.Fragment >
																									);
																									},
																									}
																									);
