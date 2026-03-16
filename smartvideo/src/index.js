import { __ } from '@wordpress/i18n';
import {
	Button,
	Card,
	CardHeader,
	CardBody,
	CardFooter,
	CardMedia,
	CardDivider,
	SelectControl,
	ToggleControl,
	ColorPicker,
	ExternalLink,
	Flex,
	FlexItem,
	__experimentalHStack as HStack,
	__experimentalInputControl as InputControl,
	Notice,
	Spinner,
	TabPanel,
	__experimentalVStack as VStack,
} from '@wordpress/components';

import ImageUpload from './ImageUpload';

import {
	createPortal,
	createRoot,
	memo,
	render,
	useEffect,
	useRef,
	useState,
} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

import { debounce } from 'lodash';

import './index.scss';

// Utility fns
export const boolify = (val) => {
	if (val == undefined) {
		return undefined;
	}
	if (typeof val === 'boolean') {
		return val;
	}
	return val === 'on';
};

export const isValidCdnKey = (key) => {
	if (!key || key === '') {
		return false;
	}
	const uuidRegex =
		/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
	return uuidRegex.test(key);
};
export const onoffify = (val) => {
	if (val == null) {
		return 'off';
	}
	if (typeof val === 'boolean') {
		return val ? 'on' : 'off';
	}
	return val;
};

const SpinnerWrap = ({ checkVal, children }) =>
	checkVal == undefined ? <Spinner /> : children;

const ResetConfirmModal = ({ sectionName, onConfirm, onCancel }) => {
	useEffect(() => {
		const onKey = (e) => { if (e.key === 'Escape') onCancel(); };
		document.addEventListener('keydown', onKey);
		return () => document.removeEventListener('keydown', onKey);
	}, [onCancel]);

	return createPortal(
		<div className="sv-reset-overlay" onClick={onCancel}>
			<div
				className="sv-reset-dialog"
				onClick={(e) => e.stopPropagation()}
				role="alertdialog"
				aria-labelledby="sv-reset-title"
				aria-describedby="sv-reset-desc"
			>
				<div className="sv-reset-dialog__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
						<path d="M1 4v6h6" />
						<path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10" />
					</svg>
				</div>
				<h3 id="sv-reset-title" className="sv-reset-dialog__title">Reset to defaults</h3>
				<p id="sv-reset-desc" className="sv-reset-dialog__desc">
					This will revert <strong>{sectionName}</strong> to factory settings. Your other sections won't be affected.
				</p>
				<div className="sv-reset-dialog__actions">
					<button type="button" className="sv-reset-dialog__btn sv-reset-dialog__btn--cancel" onClick={onCancel}>
						Cancel
					</button>
					<button type="button" className="sv-reset-dialog__btn sv-reset-dialog__btn--confirm" onClick={onConfirm}>
						Reset section
					</button>
				</div>
			</div>
		</div>,
		document.body
	);
};

const ResetIcon = () => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="14"
		height="14"
		viewBox="0 0 24 24"
		fill="none"
		stroke="currentColor"
		strokeWidth="2"
		strokeLinecap="round"
		strokeLinejoin="round"
	>
		<path d="M1 4v6h6" />
		<path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10" />
	</svg>
);

const CdnKeyNotice = ({ cdnKey }) => {
	if (cdnKey !== undefined && cdnKey !== '' && !isValidCdnKey(cdnKey)) {
		return (
			<Notice status="error" isDismissible={false}>
				Invalid CDN key format
			</Notice>
		);
	}
	return null;
};

const ChecklistItem = ({ done, children }) => (
	<div
		className={`sv-checklist-item ${done ? 'sv-checklist-item--done' : ''}`}
	>
		<span className="sv-checklist-item__icon">
			{done ? '\u2713' : '\u25CB'}
		</span>
		<span className="sv-checklist-item__text">{children}</span>
	</div>
);

export const Setup = ({ cdnKey, status, settingsVisited, updateSwarmifySetting, onEnable }) => {
	const hasCdnKey = isValidCdnKey(cdnKey);
	const isEnabled = hasCdnKey && boolify(status);
	const allDone = hasCdnKey && isEnabled && settingsVisited;

	return (
		<VStack spacing={6}>
			<Card>
				<CardBody>
					<div className="sv-setup-hero">
						<h2>Welcome to SmartVideo</h2>
						<p>
							Replace slow YouTube and Vimeo embeds with a clean,
							fast player that you control — no ads, no branding,
							no traffic leaks.
						</p>
						{!hasCdnKey && (
							<Button
								className="swarmify-button"
								style={{ marginTop: '12px' }}
								variant="primary"
								href="https://swarmify.com/pricing/?smartvideo_wordpress_plugin"
								target="_blank"
								rel="noopener noreferrer"
							>
								Start your free trial
							</Button>
						)}
					</div>
				</CardBody>
				{!allDone && (
					<>
						<CardDivider />
						<CardBody>
							<div className="sv-checklist">
								<h3 className="sv-checklist__title">
									Getting started
								</h3>
								<ChecklistItem done={hasCdnKey}>
									Enter your CDN key
								</ChecklistItem>
								<ChecklistItem done={isEnabled}>
									Enable SmartVideo
								</ChecklistItem>
								<ChecklistItem done={settingsVisited}>
									Visit the <b>Settings</b> tab to customize
									your player
								</ChecklistItem>
							</div>
						</CardBody>
					</>
				)}
			</Card>

			<Card>
				<CardHeader>
					<h2>Connect Your Account</h2>
				</CardHeader>
				<CardBody>
					<div>
						1. Visit{' '}
						<ExternalLink href="https://dash.swarmify.com/">
							dash.swarmify.com
						</ExternalLink>
					</div>
				</CardBody>
				<CardDivider />
				<CardBody>
					<VStack>
						<div>
							2. Copy your Swarm CDN Key to your clipboard like
							so:
						</div>
						<CardMedia>
							<img
								src={
									smartvideoPlugin.assetUrl +
									'/admin/images/screen1.gif'
								}
								alt="Copy your CDN key from the dashboard"
							/>
						</CardMedia>
					</VStack>
				</CardBody>
				<CardDivider />
				<CardBody>
					<Flex direction="column">
						<FlexItem>
							3. Paste your <b>Swarm CDN Key</b> into the field
							below:
						</FlexItem>
						<FlexItem>
							<SpinnerWrap checkVal={cdnKey}>
								<InputControl
									__next40pxDefaultSize
									value={cdnKey}
									onChange={(value) =>
										updateSwarmifySetting(
											'swarmify_cdn_key',
											value
										)
									}
									placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
								></InputControl>
							</SpinnerWrap>
						</FlexItem>
						<FlexItem>
							<CdnKeyNotice cdnKey={cdnKey}></CdnKeyNotice>
						</FlexItem>
					</Flex>
				</CardBody>
				<CardDivider />
				<CardBody>
					<Flex direction="column">
						<FlexItem>
							4. Click the button below to enable SmartVideo:
						</FlexItem>
						<FlexItem>
							<SpinnerWrap checkVal={cdnKey}>
								<Button
									className="swarmify-button"
									variant="primary"
									disabled={!isValidCdnKey(cdnKey)}
									onClick={() => {
										updateSwarmifySetting(
											'swarmify_status',
											'on'
										);
										if (onEnable) onEnable();
									}}
								>
									Enable SmartVideo
								</Button>
							</SpinnerWrap>
						</FlexItem>
					</Flex>
				</CardBody>
			</Card>

			<Card>
				<CardHeader>
					<h2>How It Works</h2>
				</CardHeader>
				<CardBody>
					<p>
						Once enabled, SmartVideo scans your pages for YouTube
						and Vimeo embeds and converts them automatically — no
						extra work needed. You can also add videos directly
						using the <b>SmartVideo block</b> in your page editor.
					</p>
					<p>
						Processing takes roughly <b>1–2× the video length</b> (a
						10-minute video takes 10–20 minutes). You'll know it's
						ready when the <i>Video Acceleration</i> indicator on
						the player says <b>On</b>.
					</p>
				</CardBody>
				<CardFooter>
					<HStack justify="flex-start" spacing={2} wrap={true}>
						<Button
							className="swarmify-button"
							variant="primary"
							href="https://support.swarmify.com/hc/en-us/categories/360003156514--FAQ"
							target="_blank"
							rel="noopener noreferrer"
						>
							FAQs
						</Button>
						<Button
							className="swarmify-button"
							variant="secondary"
							href="https://support.swarmify.com/hc/en-us/articles/360043738653?smartvideo_wordpress_plugin"
							target="_blank"
							rel="noopener noreferrer"
						>
							SmartVideo tags
						</Button>
					</HStack>
				</CardFooter>
			</Card>
		</VStack>
	);
};

const PlayerPreview = memo(({ buttonShape, accentColor, watermark }) => {
	// Convert watermark URL to base64 so it works inside a data: URI SVG.
	const [watermarkDataUrl, setWatermarkDataUrl] = useState('');
	useEffect(() => {
		if (!watermark) {
			setWatermarkDataUrl('');
			return;
		}
		const img = new Image();
		img.crossOrigin = 'anonymous';
		img.onload = () => {
			const canvas = document.createElement('canvas');
			canvas.width = img.naturalWidth;
			canvas.height = img.naturalHeight;
			canvas.getContext('2d').drawImage(img, 0, 0);
			setWatermarkDataUrl(canvas.toDataURL());
		};
		img.onerror = () => setWatermarkDataUrl('');
		img.src = watermark;
	}, [watermark]);

	const color = accentColor || '#ffde17';

	let secondaryColor = '#333';
	let bgCenter = '#302820';
	let bgEdge = '#181210';
	try {
		const hex = color.replace('#', '');
		if (/^[0-9a-f]{6}$/i.test(hex)) {
			const r = parseInt(hex.slice(0, 2), 16) / 255;
			const g = parseInt(hex.slice(2, 4), 16) / 255;
			const b = parseInt(hex.slice(4, 6), 16) / 255;
			const max = Math.max(r, g, b),
				min = Math.min(r, g, b);
			const luminance = (max + min) / 2;
			secondaryColor = luminance < 0.5 ? '#fff' : '#333';

			let h = 0;
			if (max !== min) {
				const d = max - min;
				if (max === r) {
					h = ((g - b) / d + (g < b ? 6 : 0)) / 6;
				} else if (max === g) {
					h = ((b - r) / d + 2) / 6;
				} else {
					h = ((r - g) / d + 4) / 6;
				}
			}
			const hDeg = Math.round(h * 360);
			bgCenter = `hsl(${hDeg}, 30%, 18%)`;
			bgEdge = `hsl(${hDeg}, 20%, 8%)`;
		}
	} catch (e) {
		/* keep defaults */
	}

	const s = 0.22;
	const tx = 160 - 147 * s;
	const ty = 90 - 168 * s;
	const btnTransform = `translate(${tx}, ${ty}) scale(${s})`;

	const triMarkup = `<g transform="rotate(-30 289.199 -57.894)" id="tri">
		<path d="M53.499 7.951l51.961 90H1.539z" fill="${secondaryColor}" />
		<path stroke-width="4" d="M53.499 3.951l55.424 96H-1.927l55.429-96z" fill="${secondaryColor}" stroke="${secondaryColor}" />
	</g>`;

	let shapeMarkup;
	switch (buttonShape) {
		case 'circle':
			shapeMarkup = `<g id="hex"><circle cx="147" cy="168" r="125" fill="${color}" stroke="${secondaryColor}" stroke-width="10" /></g>`;
			break;
		case 'rectangle':
			shapeMarkup = `<g id="hex"><rect x="7" y="81" width="280" height="174" rx="8" fill="${color}" stroke="${secondaryColor}" stroke-width="10" /></g>`;
			break;
		default:
			shapeMarkup = `<g id="hex" transform="translate(-11, 10)">
				<path d="M158 0l136.83 79v158L158 316 21.168 237V79z" fill="${color}" />
				<path stroke-width="10" d="M158-5.774l141.83 81.888v163.77L158 321.774l-141.83-81.88V76.124L158-5.77z" fill="none" stroke="${secondaryColor}" />
			</g>`;
	}

	const watermarkMarkup = watermarkDataUrl
		? `<image href="${watermarkDataUrl}" x="250" y="146" width="64" height="28" opacity="0.55" preserveAspectRatio="xMaxYMax meet" />`
		: '';

	const svgString = `<svg viewBox="0 0 320 180" xmlns="http://www.w3.org/2000/svg">
		<defs><radialGradient id="sv-preview-bg" cx="50%" cy="40%" r="70%">
			<stop offset="0%" stop-color="${bgCenter}" /><stop offset="100%" stop-color="${bgEdge}" />
		</radialGradient></defs>
		<rect width="320" height="180" fill="url(#sv-preview-bg)" />
		<g transform="${btnTransform}" stroke-linejoin="round" fill-rule="evenodd" stroke-linecap="round">
			${shapeMarkup}${triMarkup}
		</g>
		${watermarkMarkup}
	</svg>`;

	const dataUrl = `data:image/svg+xml,${encodeURIComponent(svgString)}`;

	return (
		<div className="sv-player-preview">
			<img src={dataUrl} alt="Player preview" width="320" height="180" />
		</div>
	);
});

const ColorFormatPicker = ({ containerRef }) => {
	const [activeFormat, setActiveFormat] = useState('hex');
	const [portalTarget, setPortalTarget] = useState(null);
	const formats = ['hex', 'rgb', 'hsl'];

	const switchFormat = (format) => {
		if (!containerRef.current) {
			return;
		}
		const select = containerRef.current.querySelector(
			'.components-color-picker select'
		);
		if (!select) {
			return;
		}
		const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
			window.HTMLSelectElement.prototype,
			'value'
		).set;
		nativeInputValueSetter.call(select, format);
		select.dispatchEvent(new Event('change', { bubbles: true }));
		setActiveFormat(format);
	};

	useEffect(() => {
		if (!containerRef.current) {
			return;
		}
		const tryFind = () => {
			const picker = containerRef.current.querySelector(
				'.components-color-picker'
			);
			if (!picker) {
				return;
			}
			const inputRow = picker.querySelector('.components-h-stack');
			if (!inputRow) {
				return;
			}
			let mount = picker.querySelector('.sv-format-portal');
			if (!mount) {
				mount = document.createElement('div');
				mount.className = 'sv-format-portal';
				inputRow.parentNode.insertBefore(mount, inputRow);
			}
			setPortalTarget(mount);
			const select = containerRef.current.querySelector(
				'.components-color-picker select'
			);
			if (select) {
				setActiveFormat(select.value);
			}
		};
		const timer = setTimeout(tryFind, 100);
		return () => clearTimeout(timer);
	}, []);

	const pills = (
		<div className="sv-format-picker">
			{formats.map((fmt) => {
				const isActive = activeFormat === fmt;
				return (
					<button
						key={fmt}
						type="button"
						className={`sv-format-btn ${isActive ? 'sv-format-btn--active' : ''}`}
						onClick={() => switchFormat(fmt)}
					>
						{fmt.toUpperCase()}
					</button>
				);
			})}
		</div>
	);

	return portalTarget ? createPortal(pills, portalTarget) : null;
};

const StatusPanel = ({ cdnKey, status }) => {
	const [cdnReachable, setCdnReachable] = useState(null);
	const [diagnostics, setDiagnostics] = useState(null);
	const [diagError, setDiagError] = useState(false);
	const [expanded, setExpanded] = useState(false);

	useEffect(() => {
		fetch('https://assets.swarmcdn.com/cross/swarmdetect.js', {
			method: 'HEAD',
		})
			.then((res) => setCdnReachable(res.ok))
			.catch(() => setCdnReachable(false));

		apiFetch({
			url: smartvideoPlugin.settingsUrl.replace(
				/\/settings\/?$/,
				'/diagnostics'
			),
		})
			.then((data) => setDiagnostics(data))
			.catch(() => setDiagError(true));
	}, []);

	const pluginEnabled = boolify(status);
	const cdnKeyValid = isValidCdnKey(cdnKey);
	const scriptReady = pluginEnabled && cdnKeyValid;

	const checks = [
		{
			label: 'Plugin',
			ok: pluginEnabled,
			pass: 'Enabled',
			fail: 'Disabled',
		},
		{ label: 'CDN key', ok: cdnKeyValid, pass: 'Valid', fail: 'Missing' },
		{
			label: 'CDN',
			ok: cdnReachable === null ? null : cdnReachable,
			pass: 'Reachable',
			fail: 'Unreachable',
		},
		{ label: 'Script', ok: scriptReady, pass: 'Ready', fail: 'Not loaded' },
	];

	// Collect issues for expanded view.
	const issues = [];
	if (!pluginEnabled) {
		issues.push('Plugin is disabled');
	}
	if (!cdnKeyValid) {
		issues.push('CDN key missing or invalid');
	}
	if (cdnReachable === false) {
		issues.push('Cannot reach assets.swarmcdn.com');
	}
	if (!scriptReady) {
		issues.push(
			'Player script will not load — enable plugin and set CDN key'
		);
	}
	if (diagnostics && diagnostics.conflicts.length > 0) {
		diagnostics.conflicts.forEach((c) => issues.push(c));
	}
	if (diagError) {
		issues.push('Could not load diagnostics');
	}

	const allLoaded = cdnReachable !== null && diagnostics !== null;
	const hasProblems =
		issues.length > 0 && !issues.every((i) => i.includes('this is normal'));
	const isLoading = cdnReachable === null;

	// Auto-expand if there are real problems.
	const showExpanded = expanded || (allLoaded && hasProblems);

	return (
		<div
			className={`sv-status-bar ${hasProblems && allLoaded ? 'sv-status-bar--warn' : ''}`}
		>
			<div
				className="sv-status-bar__summary"
				onClick={() => setExpanded(!expanded)}
				onKeyDown={(e) => {
					if (e.key === 'Enter' || e.key === ' ') {
						e.preventDefault();
						setExpanded(!expanded);
					}
				}}
				role="button"
				tabIndex={0}
			>
				<div className="sv-status-bar__dots">
					{checks.map((c, i) => (
						<span
							key={i}
							className={`sv-status-dot ${c.ok === true ? 'sv-status-dot--pass' : c.ok === false ? 'sv-status-dot--fail' : 'sv-status-dot--loading'}`}
							title={c.label}
						/>
					))}
				</div>
				<span className="sv-status-bar__label">
					{isLoading
						? 'Checking...'
						: hasProblems
							? `${issues.length} issue${issues.length > 1 ? 's' : ''} found`
							: 'All systems healthy'}
				</span>
				{diagnostics && (
					<span className="sv-status-bar__env">
						WP {diagnostics.wp_version} &middot; PHP{' '}
						{diagnostics.php_version}
					</span>
				)}
				<span
					className={`sv-status-bar__chevron ${showExpanded ? 'sv-status-bar__chevron--open' : ''}`}
				>
					&#9662;
				</span>
			</div>
			{showExpanded && (
				<div className="sv-status-bar__details">
					{checks.map((c, i) => (
						<div key={i} className="sv-status-bar__row">
							<span
								className={`sv-status-dot ${c.ok === true ? 'sv-status-dot--pass' : c.ok === false ? 'sv-status-dot--fail' : 'sv-status-dot--loading'}`}
							/>
							<span className="sv-status-bar__check-label">
								{c.label}
							</span>
							<span
								className={`sv-status-bar__check-value ${c.ok === true ? '' : c.ok === false ? 'sv-status-bar__check-value--bad' : ''}`}
							>
								{c.ok === true
									? c.pass
									: c.ok === false
										? c.fail
										: '...'}
							</span>
						</div>
					))}
					{diagnostics && diagnostics.conflicts.length > 0 && (
						<div className="sv-status-bar__conflicts">
							{diagnostics.conflicts.map((c, i) => (
								<div
									key={i}
									className="sv-status-bar__conflict"
								>
									{c}
								</div>
							))}
						</div>
					)}
				</div>
			)}
		</div>
	);
};

export const SECTION_DEFAULTS = {
	conversion: {
		label: 'video conversion settings',
		values: {
			swarmify_toggle_youtube: 'off',
			swarmify_toggle_youtube_cc: 'off',
			swarmify_toggle_bgvideo: 'off',
		},
	},
	appearance: {
		label: 'player appearance',
		values: {
			swarmify_theme_button: 'default',
			swarmify_theme_primarycolor: '#ffde17',
			swarmify_watermark: '',
		},
	},
	videoDefaults: {
		label: 'video defaults',
		values: {
			swarmify_default_autoplay: 'off',
			swarmify_default_muted: 'off',
			swarmify_default_loop: 'off',
			swarmify_default_controls: 'on',
			swarmify_default_playsinline: 'off',
			swarmify_default_responsive: 'on',
		},
	},
	advanced: {
		label: 'advanced settings',
		values: {
			swarmify_toggle_layout: 'on',
			swarmify_toggle_uploadacceleration: 'on',
			swarmify_toggle_schema: 'on',
			swarmify_ads_vasturl: '',
			swarmify_toggle_conditional_loading: 'off',
		},
	},
};

export const Settings = ({ opts, updateSwarmifySetting }) => {
	const colorPickerRef = useRef(null);
	const [resetTarget, setResetTarget] = useState(null);

	useEffect(() => {
		if (!colorPickerRef.current) {
			return;
		}
		const select = colorPickerRef.current.querySelector(
			'.components-color-picker select'
		);
		if (!select) {
			return;
		}
		const desired = ['hex', 'rgb', 'hsl'];
		const options = Array.from(select.options);
		desired.forEach((val) => {
			const opt = options.find((o) => o.value === val);
			if (opt) {
				select.appendChild(opt);
			}
		});
	}, []);

	if (opts.swarmify_cdn_key == undefined) {
		return (
			<Card>
				<CardBody>
					<HStack alignment="center">
						<Spinner
							style={{
								width: '50%',
								height: '50%',
								maxHeight: '200px',
							}}
						/>
					</HStack>
				</CardBody>
			</Card>
		);
	}

	return (
		<VStack spacing={6}>
			<StatusPanel
				cdnKey={opts.swarmify_cdn_key}
				status={opts.swarmify_status}
			/>

			{resetTarget && (
				<ResetConfirmModal
					sectionName={SECTION_DEFAULTS[resetTarget].label}
					onConfirm={() => {
						Object.entries(SECTION_DEFAULTS[resetTarget].values).forEach(
							([key, val]) => updateSwarmifySetting(key, val)
						);
						setResetTarget(null);
					}}
					onCancel={() => setResetTarget(null)}
				/>
			)}

			<div className="sv-settings-group">
				<div className="sv-settings-group__header">
					<span>Video Conversion</span>
					<button
						type="button"
						className="sv-reset-btn"
						title="Reset to factory defaults"
						onClick={() => setResetTarget('conversion')}
					>
						<ResetIcon />
					</button>
				</div>
				<div className="sv-settings-group__content">
					<div className="sv-setting-row">
						<div className="sv-setting-row__info">
							<div className="option-text">
								Auto-convert YouTube & Vimeo
							</div>
							<p className="sv-setting-row__desc">
								Replace YouTube and Vimeo embeds with the
								SmartVideo player — no code changes needed.
							</p>
						</div>
						<div className="sv-setting-row__control">
							<ToggleControl
								__nextHasNoMarginBottom
								label="Auto-convert YouTube and Vimeo"
								checked={boolify(opts.swarmify_toggle_youtube)}
								onChange={(val) =>
									updateSwarmifySetting(
										'swarmify_toggle_youtube',
										val
									)
								}
							/>
						</div>
					</div>
					<div className="sv-setting-row">
						<div className="sv-setting-row__info">
							<div className="option-text">Import captions</div>
							<p className="sv-setting-row__desc">
								Carry over closed captions and subtitles from
								YouTube and Vimeo.
							</p>
						</div>
						<div className="sv-setting-row__control">
							<ToggleControl
								__nextHasNoMarginBottom
								label="Import captions"
								checked={boolify(
									opts.swarmify_toggle_youtube_cc
								)}
								onChange={(val) =>
									updateSwarmifySetting(
										'swarmify_toggle_youtube_cc',
										val
									)
								}
							/>
						</div>
					</div>
					<div className="sv-setting-row">
						<div className="sv-setting-row__info">
							<div className="option-text">
								Background & HTML5 videos
							</div>
							<p className="sv-setting-row__desc">
								Speed up background and inline HTML5 videos.
								These keep their original layout — not placed in
								the player. May conflict with some page
								builders.
							</p>
						</div>
						<div className="sv-setting-row__control">
							<ToggleControl
								__nextHasNoMarginBottom
								label="Background and HTML5 videos"
								checked={boolify(opts.swarmify_toggle_bgvideo)}
								onChange={(val) =>
									updateSwarmifySetting(
										'swarmify_toggle_bgvideo',
										val
									)
								}
							/>
						</div>
					</div>
				</div>
			</div>

			<div className="sv-settings-group">
				<div className="sv-settings-group__header">
					<span>Player Appearance</span>
					<button
						type="button"
						className="sv-reset-btn"
						title="Reset to factory defaults"
						onClick={() => setResetTarget('appearance')}
					>
						<ResetIcon />
					</button>
				</div>
				<div className="sv-settings-group__content">
					<PlayerPreview
						buttonShape={opts.swarmify_theme_button}
						accentColor={opts.swarmify_theme_primarycolor}
						watermark={opts.swarmify_watermark}
					/>
					<div className="sv-appearance-controls">
						<div className="sv-appearance-control">
							<div className="option-text">Play button shape</div>
							<div className="sv-shape-picker">
								{[
									{ value: 'default', label: 'Hexagon' },
									{ value: 'circle', label: 'Circle' },
									{ value: 'rectangle', label: 'Rectangle' },
								].map((shape) => {
									const isActive =
										(opts.swarmify_theme_button ||
											'default') === shape.value;
									return (
										<button
											key={shape.value}
											type="button"
											className={`sv-shape-btn ${isActive ? 'sv-shape-btn--active' : ''}`}
											onClick={() =>
												updateSwarmifySetting(
													'swarmify_theme_button',
													shape.value
												)
											}
										>
											{isActive && (
												<svg
													className="sv-shape-btn__check"
													viewBox="0 0 16 16"
													fill="none"
													xmlns="http://www.w3.org/2000/svg"
												>
													<path
														d="M3 8.5l3.5 3.5 6.5-7"
														stroke="currentColor"
														strokeWidth="2"
														strokeLinecap="round"
														strokeLinejoin="round"
													/>
												</svg>
											)}
											{shape.label}
										</button>
									);
								})}
							</div>
							<div className="sv-watermark-zone">
								<div className="option-text">
									Watermark{' '}
									<span className="sv-pro-badge">Pro</span>
								</div>
								<ImageUpload
									compact
									image={opts.swarmify_watermark}
									onChange={(newImage) =>
										updateSwarmifySetting(
											'swarmify_watermark',
											newImage
										)
									}
								/>
							</div>
						</div>
						<div
							className="sv-appearance-control"
							ref={colorPickerRef}
						>
							<div className="option-text">Accent color</div>
							<ColorPicker
								color={opts.swarmify_theme_primarycolor}
								copyFormat="hex"
								defaultValue="#ffde17"
								onChange={(val) =>
									updateSwarmifySetting(
										'swarmify_theme_primarycolor',
										val
									)
								}
							/>
							<ColorFormatPicker containerRef={colorPickerRef} />
						</div>
					</div>
				</div>
			</div>

			<div className="sv-settings-group">
				<div className="sv-settings-group__header">
					<span>Video Defaults</span>
					<button
						type="button"
						className="sv-reset-btn"
						title="Reset to factory defaults"
						onClick={() => setResetTarget('videoDefaults')}
					>
						<ResetIcon />
					</button>
				</div>
				<p className="sv-settings-group__desc">
					Default settings for new video blocks. Existing videos keep
					their saved values.
				</p>
				<div className="sv-settings-group__content">
					<div className="sv-setting-row">
						<div className="sv-setting-row__info">
							<div className="option-text">Autoplay</div>
							<p className="sv-setting-row__desc">
								Start playing automatically when the page loads.
								Most browsers require muted for autoplay to
								work.
							</p>
						</div>
						<div className="sv-setting-row__control">
							<ToggleControl
								__nextHasNoMarginBottom
								label="Autoplay"
								checked={boolify(
									opts.swarmify_default_autoplay
								)}
								onChange={(val) =>
									updateSwarmifySetting(
										'swarmify_default_autoplay',
										val
									)
								}
							/>
						</div>
					</div>
					<div className="sv-setting-row">
						<div className="sv-setting-row__info">
							<div className="option-text">Muted</div>
							<p className="sv-setting-row__desc">
								Start with sound off. Required by most browsers
								for autoplay.
							</p>
						</div>
						<div className="sv-setting-row__control">
							<ToggleControl
								__nextHasNoMarginBottom
								label="Muted"
								checked={boolify(opts.swarmify_default_muted)}
								onChange={(val) =>
									updateSwarmifySetting(
										'swarmify_default_muted',
										val
									)
								}
							/>
						</div>
					</div>
					<div className="sv-setting-row">
						<div className="sv-setting-row__info">
							<div className="option-text">Loop</div>
							<p className="sv-setting-row__desc">
								Restart the video from the beginning when it
								ends.
							</p>
						</div>
						<div className="sv-setting-row__control">
							<ToggleControl
								__nextHasNoMarginBottom
								label="Loop"
								checked={boolify(opts.swarmify_default_loop)}
								onChange={(val) =>
									updateSwarmifySetting(
										'swarmify_default_loop',
										val
									)
								}
							/>
						</div>
					</div>
					<div className="sv-setting-row">
						<div className="sv-setting-row__info">
							<div className="option-text">Controls</div>
							<p className="sv-setting-row__desc">
								Show play/pause, progress bar, and volume
								controls.
							</p>
						</div>
						<div className="sv-setting-row__control">
							<ToggleControl
								__nextHasNoMarginBottom
								label="Controls"
								checked={boolify(
									opts.swarmify_default_controls
								)}
								onChange={(val) =>
									updateSwarmifySetting(
										'swarmify_default_controls',
										val
									)
								}
							/>
						</div>
					</div>
					<div className="sv-setting-row">
						<div className="sv-setting-row__info">
							<div className="option-text">Play inline</div>
							<p className="sv-setting-row__desc">
								Prevent iOS Safari from forcing fullscreen when
								playing.
							</p>
						</div>
						<div className="sv-setting-row__control">
							<ToggleControl
								__nextHasNoMarginBottom
								label="Play inline"
								checked={boolify(
									opts.swarmify_default_playsinline
								)}
								onChange={(val) =>
									updateSwarmifySetting(
										'swarmify_default_playsinline',
										val
									)
								}
							/>
						</div>
					</div>
					<div className="sv-setting-row">
						<div className="sv-setting-row__info">
							<div className="option-text">Responsive</div>
							<p className="sv-setting-row__desc">
								Fill the container width and maintain aspect
								ratio.
							</p>
						</div>
						<div className="sv-setting-row__control">
							<ToggleControl
								__nextHasNoMarginBottom
								label="Responsive"
								checked={boolify(
									opts.swarmify_default_responsive
								)}
								onChange={(val) =>
									updateSwarmifySetting(
										'swarmify_default_responsive',
										val
									)
								}
							/>
						</div>
					</div>
				</div>
			</div>

			<div className="sv-settings-group">
				<div className="sv-settings-group__header">
					<span>Advanced</span>
					<button
						type="button"
						className="sv-reset-btn"
						title="Reset to factory defaults"
						onClick={() => setResetTarget('advanced')}
					>
						<ResetIcon />
					</button>
				</div>
				<div className="sv-settings-group__content">
					<div className="sv-setting-row">
						<div className="sv-setting-row__info">
							<div className="option-text">iFrame player</div>
							<p className="sv-setting-row__desc">
								When replacing YouTube and Vimeo embeds, use an
								iFrame for better CSS isolation from your theme.
								Turn off if you see layout issues.
							</p>
						</div>
						<div className="sv-setting-row__control">
							<ToggleControl
								__nextHasNoMarginBottom
								label="iFrame player"
								checked={boolify(opts.swarmify_toggle_layout)}
								onChange={(val) =>
									updateSwarmifySetting(
										'swarmify_toggle_layout',
										val
									)
								}
							/>
						</div>
					</div>
					<div className="sv-setting-row">
						<div className="sv-setting-row__info">
							<div className="option-text">
								Upload acceleration
							</div>
							<p className="sv-setting-row__desc">
								Speeds up video uploads. Turn off if uploads
								stall or fail.
							</p>
						</div>
						<div className="sv-setting-row__control">
							<ToggleControl
								__nextHasNoMarginBottom
								label="Upload acceleration"
								checked={boolify(
									opts.swarmify_toggle_uploadacceleration
								)}
								onChange={(val) =>
									updateSwarmifySetting(
										'swarmify_toggle_uploadacceleration',
										val
									)
								}
							/>
						</div>
					</div>
					<div className="sv-setting-row">
						<div className="sv-setting-row__info">
							<div className="option-text">
								Video schema markup
							</div>
							<p className="sv-setting-row__desc">
								Add VideoObject structured data (JSON-LD) to
								pages with videos. Helps Google show rich video
								results in search.
							</p>
						</div>
						<div className="sv-setting-row__control">
							<ToggleControl
								__nextHasNoMarginBottom
								label="Schema markup"
								checked={boolify(opts.swarmify_toggle_schema)}
								onChange={(val) =>
									updateSwarmifySetting(
										'swarmify_toggle_schema',
										val
									)
								}
							/>
						</div>
					</div>
					<div className="sv-setting-row">
						<div className="sv-setting-row__info">
							<div className="option-text">
								VAST ads{' '}
								<span className="sv-pro-badge">Pro</span>
							</div>
							<p className="sv-setting-row__desc">
								Paste your VAST tag URL from your ad platform.
							</p>
						</div>
						<div className="sv-setting-row__control">
							<InputControl
									__next40pxDefaultSize
								value={opts.swarmify_ads_vasturl}
								type="url"
								onChange={(val) =>
									updateSwarmifySetting(
										'swarmify_ads_vasturl',
										val
									)
								}
								placeholder="https://ads.example.com/vast.xml"
							/>
						</div>
					</div>
					<div className="sv-setting-row">
						<div className="sv-setting-row__info">
							<div className="option-text">
								Conditional script loading{' '}
								<span className="sv-beta-badge">Beta</span>
							</div>
							<p className="sv-setting-row__desc">
								Controls when the player script loads.{' '}
								<strong>Standard</strong> skips pages with no
								video content (YouTube, Vimeo, &lt;video&gt;,
								iframes, or SmartVideo embeds).{' '}
								<strong>Standard</strong> loads when video
								content is detected.{' '}
								<strong>Strict</strong> loads only when
								SmartVideo will actively enhance detected
								content. If a video isn't detected (e.g.,
								added by your theme), use the{' '}
								<code>smartvideo_force_load_script</code> filter.
							</p>
						</div>
						<div className="sv-setting-row__control">
							<div className="sv-segmented-control">
								{[
									{ value: 'off', label: 'Off' },
									{ value: 'standard', label: 'Standard' },
									{ value: 'strict', label: 'Strict' },
								].map((opt) => (
									<button
										key={opt.value}
										type="button"
										className={`sv-segmented-control__btn${
											opts.swarmify_toggle_conditional_loading === opt.value
												? ' is-active'
												: ''
										}`}
										onClick={() =>
											updateSwarmifySetting(
												'swarmify_toggle_conditional_loading',
												opt.value
											)
										}
									>
										{opt.label}
									</button>
								))}
							</div>
						</div>
					</div>
				</div>
			</div>
		</VStack>
	);
};

const SignupFooter = ({ cdnKey }) => {
	const hasCdnKey = isValidCdnKey(cdnKey);

	return (
		<footer>
			<Card>
				{!hasCdnKey && (
					<>
						<CardBody>
							<h2>Don't have a SmartVideo account yet?</h2>
							<p className="paragraph">
								Start a 14-day free trial to get up and running.
								Once you've signed up, come back here and click
								the <b>Setup</b> tab to connect your account.
							</p>
							<Button
								className="swarmify-button"
								variant="primary"
								href="https://swarmify.com/pricing/?smartvideo_wordpress_plugin"
								target="_blank"
								rel="noopener noreferrer"
							>
								Start your free trial
							</Button>
						</CardBody>
					</>
				)}
				<CardFooter justify="center">
					<p className="copyright">
						SmartVideo Version{' '}
						{smartvideoPlugin?.version ?? '1.0.0'} powered by{' '}
						<a
							target="_blank"
							rel="noopener noreferrer"
							href="https://swarmify.com/?smartvideo_wordpress_plugin"
						>
							Swarmify
						</a>
					</p>
				</CardFooter>
			</Card>
		</footer>
	);
};

export const AdminHeader = ({ status, cdnKey, onToggle }) => {
	const cdnKeyValid = isValidCdnKey(cdnKey);
	const smartVideoOn = boolify(status) && cdnKeyValid;

	const handleClick = () => {
		if (!cdnKeyValid) {
			return;
		}
		onToggle('swarmify_status', !smartVideoOn);
	};

	return (
		<header>
			<Flex direction="row" justify="space-between" align="center">
				<img
					className="img-responsive"
					src={
						smartvideoPlugin.assetUrl +
						'/admin/images/smartvideo_logo.png'
					}
					alt="SmartVideo"
				/>
				<SpinnerWrap checkVal={cdnKey}>
					<button
						className={`sv-power-toggle ${smartVideoOn ? 'sv-power-toggle--on' : 'sv-power-toggle--off'}`}
						onClick={handleClick}
						disabled={!cdnKeyValid}
						aria-label={
							smartVideoOn
								? 'Disable SmartVideo'
								: 'Enable SmartVideo'
						}
						title={
							!cdnKeyValid
								? 'Enter your CDN key first'
								: undefined
						}
						type="button"
					>
						<span className="sv-power-toggle__dot" />
						<span className="sv-power-toggle__label">
							{smartVideoOn ? 'ON' : 'OFF'}
						</span>
					</button>
				</SpinnerWrap>
			</Flex>
		</header>
	);
};

const SmartVideoAdmin = () => {
	const [settings, setLocalSettings] = useState(
		smartvideoPlugin.initialSettings
	);
	const [saveError, setSaveError] = useState(null);
	const [settingsVisited, setSettingsVisited] = useState(
		() => localStorage.getItem('sv_settings_visited') === '1'
	);
	const saveVersionRef = useRef(0);

	const pendingChanges = useRef({});
	const flushSettings = useRef(
		debounce(() => {
			const data = { ...pendingChanges.current };
			pendingChanges.current = {};
			const version = ++saveVersionRef.current;
			setSaveError(null);
			apiFetch({
				url: smartvideoPlugin.settingsUrl,
				method: 'POST',
				data,
			})
				.then((result) => {
					if (version !== saveVersionRef.current) {
						return;
					}
					if (!result.success) {
						setSaveError(
							'Error saving settings. Try reloading the admin page.'
						);
					}
				})
				.catch(() => {
					if (version !== saveVersionRef.current) {
						return;
					}
					setSaveError(
						'Error saving settings. Check your input and try again.'
					);
				});
		}, 300)
	).current;

	useEffect(() => () => flushSettings.cancel(), []);

	const updateSwarmifySetting = (name, val) => {
		// Only convert booleans to on/off; pass strings (URLs, colors, keys) through as-is.
		// null/undefined from image removal becomes empty string for URL fields.
		let converted;
		if (typeof val === 'boolean') {
			converted = val ? 'on' : 'off';
		} else {
			converted = val ?? '';
		}
		setLocalSettings((prev) => ({ ...prev, [name]: converted }));
		pendingChanges.current[name] = converted;
		flushSettings();
	};

	const hasCdnKey = isValidCdnKey(settings.swarmify_cdn_key);
	const tabPanelRef = useRef(null);

	const switchToSettings = () => {
		const el = tabPanelRef.current;
		if (!el) return;
		const settingsTab = el.querySelector('.swarmify-tab-settings');
		if (settingsTab) settingsTab.click();
	};

	return (
		<>
			{/* Action Scheduler plugin used by WP/Woo looks for some H* tag to insert itself after... */}
			<h2 id="smartvideo-action-scheduler-notice-trap"></h2>
			<section id="smartvideo-admin" ref={tabPanelRef}>
				<VStack spacing={8}>
					<AdminHeader
						status={settings.swarmify_status}
						cdnKey={settings.swarmify_cdn_key}
						onToggle={updateSwarmifySetting}
					/>
					{saveError && (
						<Notice
							status="error"
							onDismiss={() => setSaveError(null)}
						>
							{saveError}
						</Notice>
					)}
					<TabPanel
						initialTabName={hasCdnKey ? 'settings' : 'setup'}
						activeClass="is-active"
						className="swarmify-tab-panel"
						onSelect={(tabName) => {
							if (tabName === 'settings' && !settingsVisited) {
								localStorage.setItem(
									'sv_settings_visited',
									'1'
								);
								setSettingsVisited(true);
							}
						}}
						tabs={[
							{
								name: 'setup',
								title: __('Setup', smartvideoPlugin.textDomain),
								className: 'swarmify-tab swarmify-tab-setup',
							},
							{
								name: 'settings',
								title: __(
									'Settings',
									smartvideoPlugin.textDomain
								),
								className: 'swarmify-tab swarmify-tab-settings',
							},
						]}
					>
						{(activeTab) => {
							switch (activeTab.name) {
								case 'setup':
									return (
										<Setup
											cdnKey={settings.swarmify_cdn_key}
											status={settings.swarmify_status}
											settingsVisited={settingsVisited}
											updateSwarmifySetting={
												updateSwarmifySetting
											}
											onEnable={switchToSettings}
										/>
									);
								case 'settings':
									return (
										<Settings
											opts={settings}
											updateSwarmifySetting={
												updateSwarmifySetting
											}
										/>
									);
								default:
									throw new Error(
										'Unknown tab: ' + activeTab
									);
							}
						}}
					</TabPanel>

					<SignupFooter cdnKey={settings.swarmify_cdn_key} />
				</VStack>
			</section>
		</>
	);
};

if (typeof document !== 'undefined') {
	const rootEl = document.getElementById('smartvideo-admin-root');
	if (rootEl) {
		if (typeof createRoot === 'function') {
			createRoot(rootEl).render(<SmartVideoAdmin />);
		} else {
			render(<SmartVideoAdmin />, rootEl);
		}
	}
}
