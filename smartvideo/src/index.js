import { __ } from '@wordpress/i18n';
import { 
	Button, 
	Card, 
	CardHeader, 
	CardBody, 
	CardFooter,
	CardMedia, 
	CardDivider, 
	CheckboxControl,
	ColorPicker,
	__experimentalDivider as Divider,
	ExternalLink, 
	Flex, 
	FlexBlock, 
	FlexItem,
	__experimentalHStack as HStack,
	__experimentalInputControl as InputControl,
	Notice,
	Panel,
	PanelBody,
	PanelRow,
	SelectControl,
	Spinner,
	TabPanel,
	__experimentalText as Text,
	ToggleControl,
	__experimentalVStack as VStack
 } from '@wordpress/components';

import ImageUpload from './ImageUpload';

import { render, Fragment, useCallback, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

import { debounce, partial } from 'lodash';

import './index.scss';


// Utility fns
const boolify = val => {
	if(val == undefined) {
		return undefined;
	} else {
		return val === "on" ? true : false;
	}
};
const onoffify = val => {
	if(typeof val === "boolean") {
		return val ? "on" : "off"
	}
	return val;
};

const SpinnerWrap = ({checkVal, children}) => (
	(checkVal == undefined) ?
		<Spinner/> :
		children
);


const Welcome = ({jumpToSetup}) => (
	<Card>	
		<CardBody>
			<h2>Welcome to SmartVideo! 👋</h2>
			<p className="paragraph">We're excited to start powering your site's video experience. After just a few minutes of setup, your site will have a fast, clean, professional video experience that your visitors will love.</p>
		</CardBody>
		<CardDivider/>
		<CardBody>
			<Flex direction="column">
				<FlexItem>
					<h2>If you already have a SmartVideo account, click the button below:</h2>
				</FlexItem>
				<FlexItem>
					<Button
						className="swarmify-button"
						variant="primary"
						onClick={jumpToSetup}>Setup
					</Button>
				</FlexItem>
			</Flex>
		</CardBody>
	</Card>
)

const CdnKeyNotice = ({cdnKey}) => {
	const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-a-f]{12}$/i;

	if (!(cdnKey === undefined || cdnKey === "" || uuidRegex.test(cdnKey))) {
		return <Notice status="error" isDismissible={false}>Invalid CDN key format</Notice>;
	}
}

const Setup = ({cdnKey, updateSwarmifySetting, jumpToUsage}) => {
	const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-a-f]{12}$/i;

	return <Fragment>
			{/* <VStack spacing={8}> */}
				{/* <FlexBlock>
					<h2>Let's get you set up! 👍</h2>
				</FlexBlock> */}
				<Card>
					<CardHeader><h2>Your CDN Key</h2></CardHeader>
					<CardBody>
						<div>1. Visit <ExternalLink href="https://swarmify.idevaffiliate.com/idevaffiliate.php?id=10275&url=49">dash.swarmify.com</ExternalLink></div>
					</CardBody>
					<CardDivider/>
					<CardBody>
						<VStack>
							<div>2. Copy your Swarm CDN Key to your clipboard like so:</div>
							<CardMedia>
								<img src={smartvideoPlugin.assetUrl + '/admin/images/screen1.gif'} alt=""/>
							</CardMedia>
						</VStack>
					</CardBody>
					<CardDivider/>
					<CardBody>
						<Flex direction="column">
							<FlexItem>3. Paste your <b>Swarm CDN Key</b> into the field below:</FlexItem>
							<FlexItem>
								<SpinnerWrap checkVal={cdnKey}>
									
									<InputControl
										value={cdnKey}
										onChange={ value => updateSwarmifySetting("swarmify_cdn_key", value) }
										placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
									</InputControl>
								</SpinnerWrap>
							</FlexItem>
							<FlexItem><CdnKeyNotice cdnKey={cdnKey}></CdnKeyNotice></FlexItem>
						</Flex>
					</CardBody>
					<CardDivider/>
					<CardBody>
						<Flex direction="column">
							<FlexItem>4. Click the button below:</FlexItem>
							<FlexItem>
								<SpinnerWrap checkVal={cdnKey}>
									<Button
										className="swarmify-button"
										variant="primary"
										onClick={ () => updateSwarmifySetting("swarmify_status", "on") }>Enable SmartVideo</Button>
								</SpinnerWrap>
							</FlexItem>
						</Flex>
					</CardBody>
					<CardDivider/>
					<CardBody>
						<Flex direction="column">
							<FlexItem>5. Now that everything's enabled, visit the <b>Usage</b> tab to learn how to add SmartVideos, or check out the Settings tab to customize how SmartVideo works.</FlexItem>
							{/* <FlexItem>
								<Button
									className="swarmify-button"
									variant="primary"
									onClick={ jumpToUsage }>Usage</Button>
							</FlexItem> */}
						</Flex>
					</CardBody>
				</Card>
			{/* </VStack> */}
			</Fragment>;
};

const Usage = () => (
	<Card>	
		<CardHeader>
			<h2>How do I add a SmartVideo to my website?</h2>
		</CardHeader>
		<CardBody>
			<VStack spacing={8} alignment="left" expanded={false}>
				<div className="setup-paragraph">After enabling SmartVideo, it will begin scanning your site for YouTube and Vimeo videos.</div>
				<div className="setup-paragraph"><b>If you have YouTube or Vimeo videos on your site</b>, they will be converted to SmartVideo and be displayed in a clean, fast-loading player automatically, requiring no extra work on your part.</div>
				<div className="setup-paragraph"><b>If you want to add a video to your site directly</b>, simply use our included SmartVideo block. After enabling SmartVideo, this block will be visible in your page editor <i>(current supported editors: Classic WordPress Editor, Gutenberg, Beaver Builder, Divi, and Elementor)</i>.</div>
				<CardMedia>
					<img src={smartvideoPlugin.assetUrl + '/admin/images/widgetdemo.gif'} alt=""/>
				</CardMedia>
				<div className="setup-paragraph">When a page with a video loads for the first time, SmartVideo fetches that video, encodes it, and stores it on our network. Depending on the resolution of the video file, <b>a video typically takes one to two times the length of the video to process</b> <i>(a 10-minute video should take 10-20 minutes)</i>.</div>
				<div className="setup-paragraph">You will know that a video has been fully converted by SmartVideo when, while hovering over the <i>Video Acceleration</i> icon on the player, the popup box says <b>Video Acceleration: On</b></div>
				<CardMedia>
					<img src={smartvideoPlugin.assetUrl + '/admin/images/accelon.gif'} alt=""/>
				</CardMedia>
				<div className="setup-paragraph">If the popup box says <b>Video Acceleration: Off</b>, the video is still being processed.</div>
				<div className="setup-paragraph">After the conversion process is complete, the video is hosted on our global delivery network and served via our accelerated playback technology. This means you can keep uploading your videos to YouTube and placing them on your site, as SmartVideo will continuously look for new videos and convert them automatically.</div>
			</VStack>
		</CardBody>
		<CardBody>	
			<p><b>If you have questions</b>, take a look at the Frequently Asked Questions collection in our Help Center.</p>
			<Button className="swarmify-button" variant="primary" href="https://swarmify.idevaffiliate.com/idevaffiliate.php?id=10275&url=50" target="_blank">FAQs</Button>
		</CardBody>
		<CardDivider/>
		<CardBody>
			<p><b>If you are not using a supported builder or editor</b>, YouTube and Vimeo videos should be auto-converted just fine. However, if you want to add a SmartVideo directly to your site, you'll have to make use of a SmartVideo tag. Click the button below to learn about SmartVideo tags.</p>
			<Button className="swarmify-button" variant="primary" href="https://swarmify.idevaffiliate.com/idevaffiliate.php?id=10275&url=51" target="_blank">SmartVideo tags</Button>
		</CardBody>
	</Card>
);

const StatusToggle = ({opts, updateToggleOption}) => {
	const cdnKeyValid = opts.swarmify_cdn_key !== "";
	const label = cdnKeyValid ? "Enable SmartVideo" : "You must enter a valid Swarm CDN Key before you can enable SmartVideo. Click on the Setup tab.";

	return <ToggleControl
			className="status-toggle-label"
			label={label}
			checked={ boolify(opts.swarmify_status) }
			disabled={!cdnKeyValid}
			__nextHasNoMarginBottom={true}
			onChange={ val => updateToggleOption("swarmify_status", val) }/>;
};

const Settings = ({opts, updateSwarmifySetting}) => {

	if( opts.swarmify_cdn_key == undefined ) {
		return <Card>
					<CardBody>
						<HStack alignment="center">
							<Spinner style={{width: '50%', height: '50%', maxHeight: "200px"}}/>
						</HStack>
					</CardBody>
				</Card>;
	}

	return <Card>
				<CardBody>
					{/* <VStack spacing={4}> */}
						{/* <h2>Toggle SmartVideo on/off</h2> */}
						<StatusToggle opts={opts} updateToggleOption={updateSwarmifySetting} />
					{/* </VStack> */}
				</CardBody>
				
				<CardBody>
					{/* <StatusToggle opts={opts} updateToggleOption={updateToggleOption} /> */}
					<Card>
						<CardHeader isShady={true}>Basic Options</CardHeader>
						<CardBody>
							<VStack spacing={4}>
								<div className="option-text">YouTube & Vimeo auto-conversions</div>
								<CheckboxControl
									label="Convert YouTube and Vimeo embeds to SmartVideos automatically."
									checked={ boolify(opts.swarmify_toggle_youtube) }
									onChange={ val => updateSwarmifySetting("swarmify_toggle_youtube", val) }/>
								<CardDivider/>
								<div className="option-text">YouTube captions</div>
								<CheckboxControl
									label="Import and display closed captions/subtitles from YouTube."
									checked={ boolify(opts.swarmify_toggle_youtube_cc) }
									onChange={ val => updateSwarmifySetting("swarmify_toggle_youtube_cc", val) }/>
								<CardDivider/>
								<div className="option-text">Background & HTML video auto-conversions</div>
								<CheckboxControl
									label="Optimizes background and HTML videos but does not place them in SmartVideo player. May conflict with some layouts."
									checked={ boolify(opts.swarmify_toggle_bgvideo) }
									onChange={ val => updateSwarmifySetting("swarmify_toggle_bgvideo", val) }/>
								<CardDivider/>
								<div className="option-text">Play button shape</div>
								<SelectControl
									value={opts.swarmify_theme_button}
									onChange={ val => updateSwarmifySetting("swarmify_theme_button", val) }>
									<option value="default">Hexagon (default)</option>
									<option value="rectangle">Rectangle</option>
									<option value="circle">Circle</option>
								</SelectControl>
								<CardDivider/>
								<div className="option-text">Player accent color</div>
								<ColorPicker
									color={opts.swarmify_theme_primarycolor}
									copyFormat="hex"
									defaultValue="#ffde17"
									onChange={ val => updateSwarmifySetting("swarmify_theme_primarycolor", val) }
								/>
							</VStack>
						</CardBody>	
					</Card>
				</CardBody>

				<CardBody>
					<Panel>
						<PanelBody title="Advanced Options" initialOpen={false}>
							<PanelRow>
								<VStack spacing={4} style={{margin: "15px 0"}}>
									<div className="option-text">Use iframe layout</div>
									<CheckboxControl 
										label="Enables iframe-based layouts. If disabled, falls back to video tags."
										checked={ boolify(opts.swarmify_toggle_layout) }
										onChange={ val => updateSwarmifySetting("swarmify_toggle_layout", val) }/>
									<CardDivider/>

									<div className="option-text">Upload acceleration</div>
									<CheckboxControl 
										label="If you have trouble with uploads, try turning this off."
										checked={ boolify(opts.swarmify_toggle_uploadacceleration) }
										onChange={ val => updateSwarmifySetting("swarmify_toggle_uploadacceleration", val) }/>
									<CardDivider/>

									<div className="option-text">Watermark (Video Pro plan and up only)</div>
									<div>Choose an image from your WordPress Media Library to watermark on the SmartVideo Player.</div>
									<ImageUpload 
										image={ opts.swarmify_watermark } 
										onChange={ newImage => updateSwarmifySetting("swarmify_watermark", newImage) } />
									<CardDivider/>

									<div className="option-text">VAST advertising (Video Pro plan and up only)</div>
									<div>
										<InputControl
											value={opts.swarmify_ads_vasturl}
											type='url'
											onChange={ val => updateSwarmifySetting("swarmify_ads_vasturl", val) }
											placeholder="https://example.com">
										</InputControl>
									</div>
									<div style={{fontSize: "smaller"}}>Grab your VAST ad URL from your advertising management platform and paste it here.</div>
								</VStack>
							</PanelRow>
						</PanelBody>
					</Panel>
				</CardBody>
			</Card>;
};

const SignupFooter = () => (
	<footer>
			<Card>
				<CardBody>
					<h2>If you do not have a SmartVideo account yet, click the button below and create an account:</h2>
					<p className="paragraph">Every account comes with a free trial. Once you create an account, return here and click the <b>Setup</b> tab.</p>
					<Button className="swarmify-button" variant="primary" href="https://swarmify.idevaffiliate.com/idevaffiliate.php?id=10275&url=46" target="_blank">Create an account</Button>
				</CardBody>
				<CardFooter justify="flex-end">
					<p className="copyright">SmartVideo Version {smartvideoPlugin?.version ?? "1.0.0" } powered by <a target="_blank" href="https://swarmify.idevaffiliate.com/idevaffiliate.php?id=10275&url=48">Swarmify</a></p>
				</CardFooter>
			</Card>
		</footer>
);

const AdminHeader = ({status, cdnKey}) => {
	// let statusIndicator;

	// if (cdnKey == undefined) {
	// 	statusIndicator = <Spinner/>;
	// } else {
		const smartVideoOn = boolify(status) && cdnKey !== "";
		const {str, color} = smartVideoOn ? {str: "ON", color: "#85E996"} : {str: "OFF", color: "#F45052"};

		// statusIndicator = <span style={{color}}>{ str }</span>;
	// }

	return <header>
		<Flex direction="row" justify="space-between" wrap={true}>
			<img className="img-responsive" src={smartvideoPlugin.assetUrl + '/admin/images/smartvideo_logo.png'} alt="SmartVideo header"/>
			{/* <div className="swarmify-status">SmartVideo: {statusIndicator}</div> */}
			<div className="swarmify-status">SmartVideo: <SpinnerWrap checkVal={cdnKey}><span style={{color}}>{ str }</span></SpinnerWrap></div>
		</Flex>
	</header>;
};

const SmartVideoAdmin = () => {

	const [settings, setLocalSettings] = useState(smartvideoPlugin.initialSettings);

	const updateServerSettings = useCallback(debounce(newSettings => {
		apiFetch({
			url: smartvideoPlugin.settingsUrl,
			method: 'POST',
			data: newSettings,
		}).then(result => {
			if (result.success) {
				// console.log('Settings saved successfully!');
			} else {
			  alert('Error saving settings. Try reloading the admin page.');
			} 
			
		});
	}, 100));

	const updateSettings = (newSettings) => {
		setLocalSettings(newSettings);
		updateServerSettings(newSettings);
	}

	const updateSwarmifySetting = (name, val) => {
		const newOpts = { ...settings, [name]: onoffify(val) };
	    updateSettings(newOpts);	
	};

	// console.log("Settings", settings);

	// the TabPanel offers no easy way to do this, so we have to hack it
	const jumpToTab = (tabClass) => {
		document.getElementsByClassName(tabClass)?.[0]?.click();

		// const tab = document.getElementsByClassName(tabClass)?.[0]?.click();
		// if (tab) {
		// 	tab.click();
		// 	// tab.scrollIntoView();
		// 	document.documentElement.scrollTop = 0;
		// 	document.body.scrollTop = 0;
		// }
	};

	return <Fragment>
		{/* Action Scheduler plugin used by WP/Woo looks for some H* tag to insert itself after... */}
		<h2 id="smartvideo-action-scheduler-notice-trap"></h2> 
		<section id="smartvideo-admin">
			<VStack spacing={8}>
				<AdminHeader status={ settings.swarmify_status } cdnKey={ settings.swarmify_cdn_key }/>
				<TabPanel
					initialTabName="welcome"
					onSelect={function noRefCheck(){}}
					activeClass="is-active"
					className="swarmify-tab-panel"
					tabs={[
						{
							name: 'welcome',
							title: __('Welcome', smartvideoPlugin.textDomain),
							className: 'swarmify-tab swarmify-tab-welcome'
						},
						{
							name: 'setup',
							title: __('Setup', smartvideoPlugin.textDomain),
							className: 'swarmify-tab swarmify-tab-setup',
						},
						{
							name: 'usage',
							title: __('Usage', smartvideoPlugin.textDomain),
							className: 'swarmify-tab swarmify-tab-usage',
						},
						{
							name: 'settings',
							title: __('Settings', smartvideoPlugin.textDomain),
							className: 'swarmify-tab swarmify-tab-settings'
						}
					]}>
					{
						activeTab => {
							switch (activeTab.name) {
								case 'welcome':
									return <Welcome jumpToSetup={ partial(jumpToTab, "swarmify-tab-setup") }/>;
								case 'setup':
									return <Setup cdnKey={settings.swarmify_cdn_key} updateSwarmifySetting={updateSwarmifySetting} jumpToUsage={ partial(jumpToTab, "swarmify-tab-usage") }/>;
								case 'usage':
									return <Usage/>;
								case 'settings':
									return <Settings opts={settings} updateSwarmifySetting={updateSwarmifySetting} />;
								default:
									throw new Error('Unknown tab: ' + activeTab);
							}
						}
					}
				</TabPanel>
		
				<SignupFooter/>
			</VStack>
		</section>
	</Fragment>
};

if (document.getElementById('smartvideo-admin-root')) {
	render(<SmartVideoAdmin/>, document.getElementById('smartvideo-admin-root'));
}
