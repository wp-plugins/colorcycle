=== ColorCycle ===
Contributors: madjax
Tags: gallery, slideshow, colorbox, cycle, lightbox, jquery
Tested up to: 3.3.2
Stable tag: 1.5
Requires at least: 3.2.1

ColorCycle is a gallery replacement plugin for WordPress. It adds Colorbox for image enlargements, and slideshows using the Cycle plugin for jQuery.

== Description ==

ColorCycle is a gallery replacement plugin for WordPress. It adds Colorbox for image enlargements, and creates slideshows of attached images using the Cycle plugin for jQuery. The [gallery] shortcode is replaced with a Highslide powered gallery. An additional shortcode [slideshow] is provided. 

== Changelog ==
= 1.5 =
* A temporary step towards 3.5 compat

= 1.4.1 =
* Add new ccgallery shortcode
* Allow id of parent to be specified in slideshow shortcode
* Allow selection of images based on group attribute
* More Colorbox options now available
* General cleanup

= 1.4 =
* Name change, replace Highslide with Colorbox
* Remove debugging code in gallery shortcode

= 1.3.3 =
* Remove Highslide from plugin package to conform to repository GPL rules

= 1.3.2 =
* Bugfix - linkto=url in slideshow now displaying specified URL

= 1.3.1 =
* Fix IE 7 error with highcycle.js
* Corrections to readme.txt
* Move localization of highcycle.js to allow override by shortcode attributes

= 1.3 =
* Initial Release

== Upgrade Notice ==
= 1.4 =
* Highslide no longer used. Colorbox powered lightboxes.

= 1.3.3 =
* Highslide JS removed - MUST BE REINSTALLED - see install instructions.

= 1.3.2 =
* Bugfix - linkto=url in slideshow now displaying specified URL

= 1.3.1 =
* Fixes IE 7 error

= 1.3 =
Initial Release

== Installation ==

1. Use automatic installer
1. Activate plugin
1. Your linked images and galleries will now use Colorbox for enlargements.

== Frequently Asked Questions ==

= What shortcodes are available? =

[gallery] or [ccgallery]*

`
'order'      => 'ASC',
'orderby'    => 'menu_order ID',
'id'         => $post->ID,
'itemtag'    => 'dl',
'icontag'    => 'dt',
'captiontag' => 'dd',
'columns'    => 3,
'size'       => 'thumbnail'
`
* Note: WordPress replaces the gallery shortcode in the visual editor with a placeholder, making it difficult to edit attributes after publishing. Using the ccgallery shortcode will maintain visibility of your attributes.

[slideshow] - Available attributes and defaults:

`
'id' => $post->ID,
'size' => 'large',
'show' => 'selected',
'showthumbs' => false,
'linkto' => 'large',
'pager' => false,
'per_page' => 12,
'speed' => 2000,
'pause' => 0,
'delay' => 1000
`
= No images are showing in my slideshow? =

By default, only images which have been selected by using the drop down in the media details 'Show in Slideshow' are included. Alternatively, include the attribute 'show=all' in your shortcode to show all attached images.

= How can I add a slideshow in my template? =
Here's an example:
`
if( class_exists( 'ColorCycle' ) ) {
	
	$ColorCycle = new ColorCycle;
	
	$show = array(
		'size' => 'home-slide',
		'pager' => false,
		'linkto' => 'url',
		'speed' => 3000,
		'timeout' => 1000,
		'show' => all
	);
	
	echo $ColorCycle->colorcycle_show( $show );
	
}
`
= It doesn't look the way I want, how can I style the galleries and slideshows? =
Look at the markup and apply CSS as needed. Future release will include more included styling.