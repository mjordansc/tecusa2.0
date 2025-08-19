=== Video Background ===
Contributors: blakedotvegas, pushlabs
Tags: video background, Visual Composer, WPBakery, SiteOrigin, page builder
Requires at least: 4.5
Tested up to: 6.5.2
Stable tag: 2.7.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily assign a video background to any element on your WordPress pages or posts. Now compatible with WPBakery (Visual Composer) and SiteOrigin Page Builder!

== Description ==

Video Background offers a simple and easy way to add a video background to any element on your WordPress site.

There are 4 simple required fields:

*   Container: This fields specifies where you would like the video background.
*   MP4: Link to the .mp4 file. For Safari and IE support.
*   WEBM: Link to the .webm file. For Chrome, Firefox, and Opera support.
*   Poster: This will be used for the fallback image if video background is not supported (mobile for example)

There are also 5 additional optional fields for having a more beautiful video background:

*   Overlay: Add a color overlay over the video for optimal reading of text.
*   Overlay Color: If overlay is enabled, you can select the color of the overlay using the colorpicker.
*   Overlay Alpha: If overlay is enabled, you can specify the amount of transparency.
*   Loop: Enable or disable the looping of the video background
*   Tap to unmute: Add a "Tap to unmute" button to your video backgrounds to have your users engage with the sound. Compatible with Chrome's newest autoplay policies!

In addition to the Video Background metabox, WPBakery integration, and SiteOrigin Page Builder integration, you can use the shortcode for use anywhere on your Wordpress site:
```
[vidbg container="body" mp4="#" webm="#" poster="#" muted="true" loop="true" overlay="false" overlay_color="#000" overlay_alpha="0.3"]
```

Demo:
<https://pushlabs.co/docs/video-background/>

== Installation ==

Installation is simple.

1. Upload `video-background` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Fill in the settings on the “Video Background” metabox on the page or post you'd like the video background to appear on.

== Frequently Asked Questions ==

= What size is recommended for the videos? =

15mb is usually best, I try to not exceed that. Any video after 15mb starts to slow down and sometimes will not load. Check with your hosting provider to make sure you have enough bandwidth for video backgrounds.

= Is this compatible with Internet Explorer? =

Video background works for Internet Explorer 9 and above. Any version below that will use the fallback image.

= How would I make a full width/height background video? =

Simply fill out the 4 easy fields. Be sure for the container you enter "body" (without the quotes)

= What is the best way to add a full width video background to my website? =

The best way to achieve this is to use a page builder like SiteOrigin or WPBakery (Visual Composer) to add a full width row, and apply the video background to it through the integration.

= What filetypes are allowed for fallback images? =

Fallback images can be .jpg, .png, and .gif.

= I entered all the correct fields, but my video will still not load. What am I doing wrong? =

This can be an issue with the file size of the video you are uploading. Make sure that you video is compressed and is does not exceed 15mb.

= I want to add a video background to a class called "header", how would I do that? =

To add a video background to a class called **header** add ".header" to the container field. (without the quotes)

== Screenshots ==

1. 4 fields? That’s it? Yep, simply enter in the element you’d like the video background to be in and key in the paths to the video and fallback image. Awesome.

2. Use WPBakery (Visual Composer)? Video Background & WPBakery allow you to easily add a video background to your row.

3. Use SiteOrigin Page Builder? Video Background & SiteOrigin allow you to easily add a video background to your row.

== Changelog ==

= 2.7.7 =

- Removed `.changeset`, `package.json`, and `package-lock.json` from production dist

= 2.7.6 =

- Add CHANGELOG.md to production plugin bundle
- Update tested up to version to 6.5.2

= 2.7.5 =

- Fixed: Escaped shortcode booleans (loop, overlay, tap to unmute)

= 2.7.4 =

- Fixed: Escaped shortcode input (container field, and other attributes)

= 2.7.3 =

- Fixed: Fixes a bug introduced in v2.7.2 where the metabox does not appear on some UNIX systems

= 2.7.2 =

- Fixed: Upgraded CMB2 from 2.2.1 to 2.10.1
- Fixed: Fixed critical errors seen when using UpdraftPlus 1.22.16 via the above CMB2 update

= 2.7.1 =

- Fixed: Remove tap to unmute button when video background can’t be played

= 2.7.0 =

- Added: You can now add a video background to your WPBakery (Visual Composer) row!
- Added: You can now add a video background to your SiteOrigin Page Builder row!
- Added: vidbg_update_message() to let users know of crucial updates
- Removed: vidbg_is_wp_version()
- Removed: Pesky Video Background Pro fields
- Updated: Settings page slug
- Fixed: Simplified the plugin’s resize methods.
- Added: As a result of recent browser autoplay policy changes, the default audio on option has been removed. Now, you can add a nice “Tap to unmute” button to the video background!

= 2.6.3 =

- Fixed: ability to display just fallback image.
- Fixed: if fallback image is present, but video files are not. The video will not load to save on browser page size.

= 2.6.2 =

- Added: version to wp_register_style()

= 2.6.1 =

- Fixed: Critical issue with the [vidbg] shortcode not displaying video backgrounds.

= 2.6.0 =

- Fixed: Compatibility issue with IOS 11 and High Sierra
- Added: Reengineered vidbg scripts for more consistency across websites and faster loading speeds
- Added: All instances of Video Background are now ran straight from the shortcode for more consistency.
- Fixed: Compatibility issues in the opacity slider
- Added: WordPress required version is now 4.2

= 2.5.8 =

- Fixed: Conditionally enqueue vidbg script

= 2.5.7 =

- Added ability to filter the post types using “vidbg_post_types” filter.

= 2.5.6 =

- Fixed compatibility issues with other CMB2 instances

= 2.5.5 =

- Fixed notice issue when hide muted fields checkbox was checked

= 2.5.4 =

- Fixed a bug that made the browser position jump when clicking the “Advanced Options” button
- Added better localization support for translations
- Cleaner code, well documented.
- Added Muted Video Background Pro fields (Do not worry, you can hide these)
- Added new stable tag for WP 4.5.3

= 2.5.3 =

- Fixed notice option

= 2.5.2 =

- Added fade in/out transitions on advanced panel
- Simplified/cleaned up a lot of code
- Added security
- Some small but helpful new styles
- Update admin notice for new pro version

= 2.5.1 =

- Added “Follow me on Twitter” button
- Added premium notice message for those on WP 4.2 or greater
- updated language file

= 2.5.0 =

- Added Overlay Color
- Added Overlay Alpha
- Added ability to upload video files through wordpress media
- Safer metaboxes
- Added localization for translations in the future
- added text domain and languages folder
- Cleaned up source files
- Integrated with CMB2
- Added donate link
- Updated stable tag

= 2.4.1 =

- Removed those pesky php notices on blog page when no front page was set and WP_DEBUG was true
- Updated tested up to tag

= 2.4.0 =

- Now stable for WordPress 4.4
- Modified links to new URL
- Now using official version of vidbg.js from github.
- Added shortcode attributes for unmute, overlay, and loop.

= 2.3.0 =

- Changed position value when container is set to “body” from “absolute” to “fixed”
- Dissolved pattern image and upgraded to data uri svg
- Cleaned up and optimized code

= 2.2.3 =

- Fixed typo in settings submenu

= 2.2.2 =

- Fixed blurred circle play button bug on iOS 9

= 2.2.1 =

- Cleaned up code
- Added donate link

= 2.2.0 =

- Fixed notices on 404 page when debug mode is set to true
- Fixed blog posts page video background.

= 2.1.4 =

- Added toggle loop
- Added toggle mute
- Added advanced section toggle
- Got rid of the getting started row in the metabox

= 2.1.3 =

- Added last versions changelog
- Added FAQ

= 2.1.2 =

- Updated links in readme.txt

= 2.1.1 =

- Added FAQ
- Added instructions on settings page
- Cleaned up code
- Changed plugin compatibility

= 2.1.0 =

- Added overlay featured
- Cleaned up code, added comments, etc.
- Dissolved OGV featured, now use video background with only MP4 and WEBM.

= 2.0.1 =

- Added page post type

= 2.0.0 =

- Video Background: Now in a metabox! No longer do you have to worry about generating a shortcode.

= 1.0.6 =

- Fixed path to js file

= 1.0.4 =

- Getting Started/settings page added

= 1.0.3 =

- ReadME update

= 1.0.2 =

- ReadME update

= 1.0.1 =

- Updated Assets
- Updated Readme

= 1.0 =

- Initial Release
