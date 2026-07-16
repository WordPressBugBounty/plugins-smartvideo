=== SmartVideo - Video Player and CDN ===
Contributors: kinggmobb, jdadmin, chris10sen
Tags: video player, video hosting, youtube, video embed, vimeo
Requires at least: 6.6
Tested up to: 7.0
Stable tag: 2.3.0
Requires PHP: 7.3
License: AGPL-3.0-or-later
License URI: https://www.gnu.org/licenses/agpl-3.0.html

Lightweight HTML5 video player and video hosting with CDN built for WordPress

== Description ==

**SmartVideo** is a YouTube alternative that replaces YouTube and Vimeo embeds on your WordPress site with a clean, ad-free video player served from a global CDN. No "Recommended Videos" pulling visitors away. No YouTube branding competing with yours. Just your content, loading fast.

### ⚡ Why SmartVideo?

* The SmartVideo player loads only on pages with video, unlike YouTube embeds which add weight to every page — pages load faster and PageSpeed scores improve
* Video hosting with global CDN delivery
* Auto-converts existing YouTube and Vimeo embeds with zero manual work
* Only loads its player on pages that actually have video — no player script on the rest of your site

### Why WordPress Site Owners Choose SmartVideo

**Speed & Performance First**
* Lightweight video player loads significantly faster than standard YouTube/Vimeo embeds
* Improve Core Web Vitals and page speed performance
* Global CDN delivers video from the nearest edge server
* Lazy load video with preload control — defer video loading until needed
* Optimized for mobile, desktop, and tablet viewing

**Complete Brand Control**
* Remove YouTube or Vimeo branding, overlays, and distracting related videos
* Custom player branding with your colors and watermark
* Professional, distraction-free playback environment
* Prevent traffic leaks to competitor content

**WordPress Integration Made Simple**
* Native widgets for Elementor, Gutenberg, Beaver Builder, Bricks, and Divi 5
* Auto-converts existing YouTube and Vimeo embeds instantly (no workflow changes)
* Embed videos in seconds with shortcodes or blocks
* Responsive design adapts to any theme
* Per-page disable toggle when you need the original embed

### What Our Users Say

⭐⭐⭐⭐⭐ **Stefan G.** – "After using Swarmify we are incredibly annoyed. But only because of our mistake of not having used Swarmify before. Super fast video player and CDN!"

⭐⭐⭐⭐⭐ **Dan S.** – "This tool is easy to setup and as easy to use. Does the job as intended, no nonsense. Really enhances the experience of having video on your website."

⭐⭐⭐⭐⭐ **Joe W.** – "I implemented this on my site and immediately saw an impact. I am very impressed with how simple they have made the entire setup. It works very well and as advertised."

⭐⭐⭐⭐⭐ **Aditya R.** – "Goodbye YouTube Embeds! Incredibly fast, stable and bloat-free!"

### 🎯 Key Features

* 📹 **Video Hosting**: Generous bandwidth, automatic encoding, and CDN delivery included
* ⚙️ **Playback Customization**: Autoplay, loop, mute, hide controls, play inline, preload control, video speed control
* 🎨 **Visual Enhancement**: Add poster images and custom branding
* 🧩 **Page Builder Support**: Native widgets for Gutenberg, Elementor, Beaver Builder, Bricks, and Divi 5
* 🎯 **Conditional Loading**: Player script only loads on pages with video content
* 🔒 **Per-Page Control**: Disable SmartVideo on individual pages or posts
* 💰 **Monetization Ready**: VAST ad support for revenue generation
* 📁 **Format Support**: MP4, HLS adaptive bitrate streaming (M3U8), MPEG-DASH, WEBM, VP8/9, MP3, AAC, OPUS
* 📊 **Video Schema & SEO**: JSON-LD VideoObject video schema auto-generated for pages with SmartVideo
* 🛠️ **Developer Friendly**: Filters, shortcodes, and extensive customization options

### 🔧 Behind the Scenes: How SmartVideo Works

SmartVideo handles video streaming and optimization automatically:

* 🤖 **Automatic Encoding**: Uploads are optimized for web delivery
* 🌍 **Global CDN**: Routes video from the nearest edge server for fast playback
* 📱 **Responsive Rendering**: Adapts to any device or screen size
* 📦 **Generous Limits**: Bandwidth, encoding, and storage included with every plan

Whether you're showcasing product demos, educational content, sales videos, or background visuals, SmartVideo loads fast, looks professional, and keeps visitors on your site.

### 🚀 Get Started in Minutes

**Start with our free 14-day trial:**

1. 📥 **Install** the SmartVideo plugin from your WordPress dashboard
2. 🔗 **Connect** your free Swarmify account (14-day trial included)
3. 👀 **Watch** your existing embeds automatically convert to the clean SmartVideo player
4. 📈 **Upgrade** when you're ready for more hosting and advanced features

SmartVideo requires a Swarmify account. Visit [swarmify.com/pricing](https://swarmify.com/pricing/) for current plans. The WordPress plugin itself is free and open source (AGPL-3.0).

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/SmartVideo` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress.
1. Use the Settings->SmartVideo screen to configure the plugin.
1. You will need to sign up for an account at [Swarmify](https://swarmify.com/?smartvideo_wordpress_plugin).

== Frequently Asked Questions ==

= How does SmartVideo speed up videos on my WordPress site? =

SmartVideo replaces heavy YouTube/Vimeo embeds (hundreds of KB of JavaScript) with a lightweight player served from a global CDN. Pages load faster, Core Web Vitals and PageSpeed scores improve, and videos start playing sooner. The plugin also uses conditional loading — the player script only loads on pages that actually have video.

= Which page builders does SmartVideo support? =

SmartVideo includes native widgets for Gutenberg (block editor), Elementor, Beaver Builder, Bricks, and Divi 5. Each widget supports full visual editing with live preview. You can also use the `[smartvideo]` shortcode in any builder or the classic editor.

= How does auto-conversion from YouTube/Vimeo work? =

When you enable auto-convert in settings, SmartVideo detects YouTube and Vimeo embeds on your pages and serves them through the SmartVideo player instead. For Vimeo, the video file is re-encoded and delivered via our CDN. Visitors see a clean, ad-free player with no "Recommended Videos" or third-party branding.

= I use Vimeo on my site. How do I switch to SmartVideo? =

Install the plugin and enable Vimeo auto-convert in settings. All your existing Vimeo embeds will be automatically converted — no need to re-embed anything. Your workflow stays the same.

= What video and audio formats are supported? =

Video: MP4, HLS (M3U8), MPEG-DASH, WEBM, VP8/9. Audio: MP3, AAC, OPUS.

= Can I disable SmartVideo on specific pages? =

Yes. SmartVideo 2.2.0 adds a per-page disable toggle in both the Gutenberg editor sidebar and the classic editor. This lets you keep the original embed on specific pages while SmartVideo handles the rest of your site.

= Who is Swarmify? =

Swarmify is a video acceleration company focused on making fast, professional video accessible to any website owner. SmartVideo is our flagship product, combining a clean video player with CDN-powered delivery.

= Who is SmartVideo for? =

SmartVideo is built for site owners who want fast, professional video without the complexity of managing encoding, CDNs, and player configuration. Whether you're hosting self-hosted video files or replacing YouTube/Vimeo embeds, SmartVideo handles delivery and keeps visitors on your site.

= Who is SmartVideo not for? =

SmartVideo requires a paid account for ongoing use (14-day free trial to start). If you need a completely free solution and are willing to accept third-party branding and related video recommendations, YouTube or Vimeo embeds may be a better fit.

= Why choose SmartVideo as a Vimeo alternative? =

Vimeo places its branding, overlays, and recommendations in your video experience, which can distract viewers and redirect traffic away from your site. As a Vimeo alternative, SmartVideo removes those elements and gives you full control of the player appearance and your brand.

= Can I use SmartVideo for live video? =

SmartVideo currently supports on-demand video only. Live streaming is not yet available. Contact our support team if you'd like to be notified when live streaming support is added.

= Are there any content restrictions? =

You may use SmartVideo to deliver any content that is legally permissible under United States law and for which you have the appropriate usage and distribution rights. Swarmify adheres to DMCA procedures and responds to takedown requests.

== Screenshots ==

1. Settings page — Video conversion toggles, player appearance, color picker
2. Gutenberg block editor — Video URL field, poster, playback options in sidebar
3. Elementor widget — SmartVideo widget with live preview in the editor
4. Bricks Builder element — Video source, poster, dimensions, and playback controls
5. Divi 5 Visual Builder — SmartVideo module with playback options
6. Frontend player — Embed video without ads: clean, ad-free player as your visitors see it

== Changelog ==

= 2.3.0 =
* Feature: Enhanced Video SEO — JSON-LD VideoObject schema now auto-fills description, thumbnail, duration, page URL, and publisher from your existing content (post excerpts, SEO plugin meta — Yoast, Rank Math, AIOSEO, SEOPress, and more — featured images, Media Library), with a new `smartvideo_schema_data` developer filter. The classic widget and Divi modules now emit schema too, joining the other builders.
* Feature: Divi 5 modules now render on the live site (previously editor-only) — modules you've already built start appearing to visitors automatically, no changes needed.
* Feature: Modernized classic-editor video dialog — redesigned popup with live YouTube/Vimeo thumbnail preview, a poster-source dropdown, keyboard-navigable tabs, and fields pre-filled from your site defaults.
* Feature: Quick link to your Swarmify dashboard from the plugin settings.
* Security: Chunked uploads now accumulate in a private, server-only directory (closing an attack vector on shared hosting), with permission and nonce checks before any file handling, bounds-checked chunk indices, and hourly cleanup of stale chunks. Nothing changes in how you upload videos.
* Security: REST settings writes hardened — inputs restricted to known settings keys, and every setting now runs both validate and sanitize callbacks; `swarmify://` dropped from the global `wp_kses` allowlist; Vimeo oEmbed thumbnails validated and fetched via `wp_safe_remote_get`; plus broad input-validation, output-escaping, capability-check, and nonce hardening from extensive code review.
* Fix: Empty SmartVideo blocks/modules no longer show the "No video selected" placeholder to site visitors — it now appears only in the builder editor (all builders + shortcode).
* Fix: Upload accelerator — fixed silent corruption on chunk-0 retries and same-filename collisions between browser tabs; added a server-side upload size cap based on free disk space; failed uploads now report a reason.
* Fix: Pages with Beaver Builder videos picked from the Media Library now load the player script, so those videos play; Beaver Builder/Elementor modules missing width/height no longer render 0×0 or trigger PHP 8 warnings; Divi 5 responsive modules reserve a 16:9 ratio to prevent layout shift.
* Fix: Conditional loading now reads post content from the database (so Divi 5 runtime swaps are detected) and no longer false-loads on non-video iframes such as Google Maps.
* Fix: YouTube Shorts and live-stream URLs are now recognized, and `?t=`/`?start=` start times are preserved when links are converted via the shortcode or page-builder modules; negative dimensions are clamped.
* Fix: Responsive iframe CSS now applies even when YouTube auto-conversion is off, so converted Vimeo embeds are responsive.
* Fix: The Gutenberg per-page "disable" toggle now saves, and the classic widget's Controls toggle now sticks when unchecked.
* Fix: Settings polish — unsaved edits persist on navigation, the CDN key field trims whitespace, the status panel now probes `swarmcdn.js` (the script the player actually loads), the conflict-plugin check covers more plugins, and the dashboard help link is fixed.
* Fix: Complete data cleanup on uninstall, including multisite.
* Compatibility: Tested with WordPress 7.0; minimum supported version raised to 6.6.
* Compatibility: Stable player loads `swarmcdn.js` directly (replacing the deprecated `swarmdetect.js` proxy), fixing playback on sites where the proxy was blocked.
* Accessibility: Improved ARIA roles, keyboard navigation, focus management, and contrast across the admin settings and the classic-editor dialog.
* Internationalization: All user-facing strings audited and made translatable; the translation template is now populated.
* Note: `[smartvideo]` shortcodes without explicit attributes now follow your site-level defaults (autoplay/muted/loop/controls/playsinline/responsive) instead of fixed built-in values, so existing bare shortcodes may behave differently after updating. Add attributes to any individual shortcode to pin its behavior.
* Note: Per-module `preload` settings have been removed from all builders — the player now manages preloading itself, and any preload value you previously set in a builder is ignored. The shortcode still accepts an explicit `preload` attribute for backward compatibility.
* Note: Classic-editor SmartVideo Widget instances saved before the controls setting was tracked now render with player controls enabled, restoring the historical default. Widgets saved with controls explicitly unchecked since 2.x are unaffected.

= 2.2.2 =
* **Beta player toggle**
  * Added beta player toggle in Advanced settings for testing upcoming player builds
  * Hardened build pipeline for more reliable releases

= 2.2.1 =
* **Bug fixes for 2.2.0**
  * Fixed conditional loading edge cases introduced in the 2.2.0 overhaul
  * Fixed block migration issues when editing legacy Gutenberg blocks

= 2.2.0 =
* **Divi 5 + Bricks Builder support**
  * Native SmartVideo module for Divi 5 Visual Builder with live preview
  * Native SmartVideo element for Bricks Builder
  * Divi 5 video blocks detected by conditional loading
* **Conditional loading overhaul**
  * Standard mode: loads on any page with video content (safe default)
  * Strict mode: only loads when features that will convert content are enabled
  * Elementor content scanning rebuilt to read the widget tree directly — eliminates false positives
  * Per-builder native detection for Elementor, Beaver Builder, Bricks, and Divi
  * Builder edit modes (Divi VB, BB editor, Bricks editor, Elementor preview) always load the player
  * `smartvideo_force_load_script` filter for themes/plugins to force-load the player
* **Modernized admin settings**
  * Rebuilt settings page with React — cleaner UI, better defaults display
  * Dashboard widget showing video stats at a glance
  * Reset buttons for each settings section with branded confirm dialog
* **Gutenberg block improvements**
  * Unified video URL field replaces source-type dropdowns
  * Legacy blocks migrate automatically on first edit — no "invalid content" errors
  * Per-page SmartVideo disable toggle in the editor sidebar
  * Responsive auto-converted YouTube/Vimeo iframes scale properly at all widths
* **New features**
  * Preload attribute support across all builders and shortcode
  * Per-page SmartVideo disable via post meta (classic editor + Gutenberg)
  * JSON-LD VideoObject schema auto-generated for pages with SmartVideo
  * Aspect ratio helper class for responsive embeds
* **Security & bug fixes**
  * Fixed XSS vulnerabilities in all builder render paths (URL sanitization, poster escaping)
  * Fixed poster leak when poster source set to "none" but stale URL remained
  * Empty video source now shows placeholder instead of broken tag
  * Invalid YouTube/Vimeo URLs return empty output instead of malformed tags
  * Improved YouTube URL normalization (short URLs, privacy-enhanced, already-embedded)
  * Fixed upload accelerator corrupting temp file paths during chunked uploads
  * Per-page disable meta no longer saved on revisions or autosaves
  * Elementor widget frontend script now loads reliably via proper dependency registration

= 2.1.2 =
* **Fixed Fancybox conflict bug**
  * Fixed a bug where old Fancybox versions were conflicting with SmartVideo
  * Improved BuddyBoss compatibility

= 2.1.1 =
* **Bugfixes for Elementor**
  * Added 'swarmify://' protocol type to allowed list in Elementor
  * Also fixed a broken link to the settings page

= 2.1.0 =
* **Improved New Admin Interface**
  * The same top-notch video playing and CDN, with a more modern admin panel

= 2.0.25 =
* **WordPress 6.3 Updates**
  * Updated for enhanced compatibility with the latest version of WordPress 6.3

= 2.0.24 =
* **WordPress 5.9 Updates**
  * Updated for enhanced compatibility with the latest version of WordPress 5.9.3

= 2.0.23.2 =
* **Welcome Tatum(WP 5.8)**
  * WordPress 5.8 is out with many new and awesome features. SmartVideo fully supports this new version so worry not. Have your cake and eat it too! Yum
  * Fixing an Elementor bug that caused unseemly warnings to appear while in dev mode. Can't have that now.
  * Added some minor fixes for 5.8.1

= 2.0.22 =
* **Fairy dust magic for better PageSpeed Scores (FCP, LCP, CLS)**
  * We're going to be straight with you, this brings some magic pixie dust which will boost your Web Vitals. But you are boosting the score only and not actually changing anything real with the performance of the site. So if the score is important to you, then this will make you happy. But don't fool yourself into thinking anything changed in actual speed, it just tests better and unfortunately a lot of people are judging yours and our works by a number generated by Google which currently doesn't have a basis in reality. Hence us putting out this update because fair or not, people gonna judge.
  * For more reading on the current problems in Web Vitals:
    * [Framehole: PageSpeed 6.0 loophole for easy perfect score](https://buzz.swarmify.com/how-to-get-a-100-score-on-lighthouse-pagespeed-in-one-easy-step/?smartvideo_wordpress_plugin)
    * [Get a perfect Largest Contentful Paint (LCP) time with a single line of code](https://www.devisedlabs.com/blog/largest-contentful-paint-lcp-hack)
  * Also fixed a minor bug(z-index) in the layout of our Classic Widget causing you to be unable to pick from your Media Library. Sorry about that and hat tip to Mirco for pointing it out!
  * You're among friends here. You are going to update for the PageSpeed Score improvement aren't you? No judgement here, do what you need to do because business is a contact sport.

= 2.0.21 =
* **2020 is nevermore, so it's time for 2.0.21**
  * Look, everyone wants to forget 2020 and move forward to a better future in 2021. But our developers had one problem, Some how, some way, our plugin just happened to be at version 2.0.20. It was like a continuous nightmare where everytime we thought we were done with 2020, there it is again in our face. So what do developers do when faced with a problem......they solve it. We present the fresh new version 2.0.21. And just like the new year brings fresh optimism, so too did this version for our developers. Now they feel cheery, and ready to take on the world. Basically like they just finished a double espresso.
  * Features....well this version brings inspiration, hope, and......some minor housekeeping fixes. Really let's be honest, we are all ready for change, so here it is, version 2.0.21. Can't be any worse than the last one right?

= 2.0.20 =
* **CSS Conflict resolution update**
  * What happens when different plugins have a CSS conflict? If you answered Style Battle Royale, well then you are an interesting person who is probably fun at parties. But if you answered,  "*shrug* things probably break?" You would be correct. Our new release prevents these style sheet conflicts where our player and your theme's video player just couldn't get along. Now they at least they are divided by a wall and can only stare angrily across the room.
  * Namespaced the SmartVideo player CSS styles so that conficts between our video player and those included via your theme or other plugins should be eliminated. But there may be some sneaky conflicts we missed and if you see them, just reach out to support so we can take care of them. Thanks!

= 2.0.19 =
* **No need to fear, dynamic tags are here**
  * Need to template up your massive video archive so that each post displays with the associated video in a SmartVideo tag. Easy breezy if you are an Elementor Pro user. Our SmartVideo widget can now dynamically load both the video source and/or poster image from any Elementor Pro dynamic tag. Quick, efficient, and beautiful
  * Elementor SmartVideo widget supports [dynamic tags](https://elementor.com/help/dynamic-tags-pro/) for Elementor Pro users

= 2.0.18 =
* **Now you see me, now you don't**
  * Sometimes it's fun to revert to our inner child and play games like hide & seek. Try it, we'll wait. You really needed that, right? While games are good for humans, it's not good for our Gutenberg block editor to play hide & seek with your block attributes. Naughty code was hiding the URL you set when you specified direct video URL's in our SmartVideo block. Don't worry we had a talk with the code, and it will never hide this data again. Also, rest assured everything is in there from previous blocks you have inserted, it was just.....hidden.
  * Gutenberg block editor was not showing the URL's you previously set for videos when you chose the "Another source" option. This has been fixed.

= 2.0.17 =
* **Crouching tiger, hidden classic**
  * Fixed issue with SmartVideo Classic widget where you would click to specify a YouTube video and the popup to enter it would never show up
  * For those without that problem, well now is a great time to turn on automatic updates for our plugin if you have the new WordPress 5.5 release. That way if a future update fixes a problem you have, then it gets installed automatically. Like robots, but in the cloud...
  * Adjusted for deprecated jquery functionality

= 2.0.15 =
* **Tested for the fresh WordPress release coming soon ... WP 5.5 Codename: WFH -- Stay Safe & Sane**
  * Updated testing for the latest WordPress builds so you can confidently install and get your video on
  * *Disclaimer: Not responsible for overconfidence or lack there of in the actual videos. That part is up to you. We believe in you though!!!*

= 2.0.14 =
* **Trimming down for speed**
  * Fixed a bug causing extra stylesheets to get included in frontend output. I mean we're stylish, but no need for excess styling!

= 2.0.13 =
* **Pro lovin, for our Pro Customers**
  * All of the Swarmlings: Moved lesser used options to a collapsed `Advanced Options` panel because everyone likes a tidy settings page, right?
  * Pro Customers: Configure a watermark for your video. It can be your logo, your friends logo, or even a disco dancing dog gif if that's your style
  * Pro Customers: Looking to level up your YouTube revenue and take control of your own ads. Well we made that simple with our javascript snippet, but who wants to figure out that gobbledy gook. You are using our WordPress plugin to simplify, and now you can configure your ad platform URL directly in the plugin.

= 2.0.12 =
* **Sometimes you need MOAR POWER**
  * You can now statically configure the Swarmify CDN key in advance from your 'wp-config.php' file. Just define the constant `SWARMIFY_CDN_KEY` and set it to your key. Config->Done
  * Elementor Pro giving you heartburn when used with our widget, antacid has arrived. We worked out an issue causing Elementor Pro's form widget to run itself out of memory.

= 2.0.11 =
* **WordPress host wants you to upload slower, fine we can make that happen**
  * Added a toggle in the settings to disable our upload acceleration if your WordPress host has issues with it.
  * We wish they would just adjust on their side, but we can be flexible so now you can turn this off in the settings if its giving your web host heartburn ;)

= 2.0.10 =
* **Watch video faster for those who enjoy chipmunk voices**
  * Why would you want the video to play at 1.25x, 1.5x, 2x speeds? Well because you were a fan of those Chipmunk albums where you could listen to popular music sung in a high squeaky voice.
  * Or it may just be your customers enjoy watching at differing paces. That must be the reason because nobody likes chipmunks, right? Alvin, alvin, ALVIN?
  * All SmartVideo players now allow viewers to change the playback speed to suit their preferences.

= 2.0.9 =
* **Too much speed!!!**
  * Some hosts weren't ready for maximum upload acceleration so they put on the brakes and prevented media file uploads. Now we detect these slowpokes and kindly slow down from insanely fast to somewhat crazy fast upload speeds.
  * If you were having trouble with uploads after previous update, that should be fixed now ;)

= 2.0.8 =
* **Closed minded no more**
  * Display closed captions on all YouTube source videos... Or don't. It's up to you with a new plugin setting that enables imported captions from your YouTube videos to be shown at the click of a button. (Don't worry, manual closed caption config is coming soon......)
  * Accelerate your video uploads with new faster, better, stronger video uploading behind the scenes. Uploading videos to use in your WordPress media library will be faster and smoother than ever

= 2.0.6 =
* **Elementary my dear Beaver**
  * Quoting for the SmartVideo widget in Elementor and Beaver Builder was not so smart. But we educated it and all of your quoting issues have been resolved.

= 2.0.5 =
* **Oldie but goodie**
  * Fixed a bug causing WordPress versions < 4.9 to have issues with our plugin. We're sorry Classic, you are still loved!

= 2.0.4 =
* **Hex a Gone?**: Some users have a dislike for 6 sided polygons. We are partial to them ourselves, but realize everyone has the right to the basics of life, liberty, and choice of play button shapes.
* **Change play button shapes**:
  * Hexagon - Our personal favorite
  * Circle - For those inclined to a single side
  * Rectangle - Do you like 4 sides and the golden ratio, then this is for you!

= 2.0.3 =
* **Divi'ded we fall**: If your theme colors weren't working properly in Divi, they are now!

= 2.0.2 =
* **Vime 'ode to our customers**: It would be really neat if this could autoconvert all Vimeo videos like it does for YouTube, you said. Well maybe not you, but some people said this. And our answer, done! Our global autoconvert toggle now handles converting Vimeo and YouTube videos throughout your site. All without needing to get your hands dirty with any pesky template editing.

= 2.0.1 =
* **Classic is still cool**: Fixed a bug with our Classic Editor widget where the edit window wouldn't close when you clicked "Insert into Post". That wasn't very awesome at all. So we fixed it, and now our classic widget is 20% cooler.
* Fixed some minor casing inconsistencies so that we can be more consistent :)

= 2.0.0 =
* **Codename: Van Gogh**
  * All of the pretty native widgets you want
  * Less of those pesky ears!!
  * You wanted modern native widgets for all your favorite page builders, and we are here to deliver.
* **Native widgets for:**
  * **Gutenberg** - For when *blocks* are your thing
  * **Divi Builder** - Fancy style, drag and drop, and now the world's fastest video
  * **Elementor** - Adding the best video is now elemental my dear Watson
  * **Beaver Builder** - Chuck slow wooden, video players out the window. And upgrade your video to the player that will make you say *dam*
* **Automatic Vimeo Import** - All widgets allow for simple import of your legacy Vimeo content. This is of course in addition to our existing support for YouTube, Amazon S3, Media Library, and more!!
* **Easier to find Settings Panel** - If you ever forgot where to configure your account, we feel you. We always lost track of where the settings were at. No more searching in menus for settings. Now it's front and center in your WordPress Admin

= 1.2.1 =
* Awesome Color Picker for video player color customization now in settings

= 1.2.0 =
* Significantly increased Lighthouse & Pagespeed scores with our enhanced embed engine
* Extreme Video Theme Optimization - Automatically optimize and accelerate your theme's background videos. Never face slow loading video again

= 1.1.7 =
Bug fixes

= 1.1.4 =
Adds widgets for standard WP editor, Beaver Builder, Elementor, and KingComposer

= 1.0.2 =
Moves scripts from body to head

= 1.0.1 =
Minor link updates

= 1.0 =
Initial version

== Upgrade Notice ==

= 2.3.0 =
Adds Video SEO schema markup, security hardening (REST settings sanitization, kses scope tightening, Vimeo transient validation), and cache plugin compatibility improvements. Recommended for all users.

= 2.2.0 =
Major release: Divi 5 and Bricks Builder support, conditional script loading overhaul, modernized settings UI, Gutenberg block improvements, per-page disable, security fixes. Recommended for all users.

= 1.2.1 =
* Awesome Color Picker for video player color customization now in settings

= 1.2.0 =
* Significantly increased Lighthouse & Pagespeed scores with our enhanced embed engine
* Extreme Video Theme Optimization - Automatically optimize and accelerate your theme's background videos. Never face slow loading video again

= 1.1.7 =
All users of 1.1.4 should upgrade.

= 1.1.4 =
You should upgrade if you want more control over video parameters, you are using non-YouTube videos, or you are using Beaver Builder, Elementor, or KingComposer.

= 1.0.2 =
You should upgrade if you are experiencing visible delays in the conversions from standard video tags/YouTube to SmartVideo.

= 1.0.1 =
You should upgrade only if you have not signed up for a Swarmify account.

= 1.0 =
You should upgrade from no plugin if you want to accelerate your videos, of course :)
