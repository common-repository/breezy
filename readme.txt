=== Breezy ===
Contributors: breezyteam
Tags: map, locations
Requires at least: 6.6.1
Tested up to: 6.6.2
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin is created by [WhooshPro](https://www.whooshpro.com/) to help WordPress users to generate Singapore map (OneMap).

== Description ==

Using Breezy Map:

* Generate Singapore maps easily using OneMap API.
* Search locations easily using OneMap API.
* Add unlimited number of markers.
* Map can work in any page builder.

Refer to [Breezy Map website](https://www.breezyplugins.com/onemap/) for more detailed instructions!

More map providers will be supported in the future!

== Dependencies ==

- Breezy Map integrates with the OneMap service to provide map-related functionalities.

- The OneMap Location API is used to find a location during map creation:

 ```
https://www.onemap.gov.sg/api/common/elastic/search?searchVal=[KEYWORD]&returnGeom=Y&getAddrDetails=Y&pageNum=1
```

- The following OneMap Leaflet script and style are used in loading of maps:
 ```
https://www.onemap.gov.sg/web-assets/libs/leaflet/leaflet.css
https://www.onemap.gov.sg/web-assets/libs/leaflet/onemap-leaflet.js
```

- **Important**: The following OneMap attribution must remain in the code and visible in the plugin output:

 ```
  /** DO NOT REMOVE the OneMap attribution below **/
  attribution: '<img src="https://www.onemap.gov.sg/web-assets/images/logo/om_logo.png" style="height:20px;width:20px;"/>&nbsp;<a href="https://www.onemap.gov.sg/" target="_blank" rel="noopener noreferrer">OneMap</a>&nbsp;&copy;&nbsp;contributors&nbsp;&#124;&nbsp;<a href="https://www.sla.gov.sg/" target="_blank" rel="noopener noreferrer">Singapore Land Authority</a>'
```

== Installation ==

1. Create a map 
2. Edit the map under “Manage Maps” 
3. Copy map’s shortcode into a shortcode widget 
4. Publish page 

== Frequently Asked Questions ==

= What maps are supported in Breezy Map? =

Singapore maps are supported in v1.0.3.

= Is there a limit to the number of maps I can create? =

No.

= Can I remove the OneMap attribution? =

No, the OneMap attribution is a mandatory requirement as per the service's terms. It must remain in the code and visible in the plugin output.

== Screenshots ==

1. Create map
2. Manage map
3. Manage markers

== Changelog ==

= 1.0.5 =
* Update text in readme

= 1.0.4 =
* Fixed missing admin menu icons.
* Fixed bug related when clicked on back to maps link.

= 1.0.3 =
* Remove authentication check for map's data AJAX endpoint so public users can load the map.

= 1.0.2 =
* Moved embedded styles into its own stylesheet and enqueued with proper functions & hook.
* Added authorization check for all AJAX calls.

= 1.0.1 =
* Updated Breezy logo.
* Updated Breezy banner.
* Updated code to enqueue scripts and styles.
* Updated readme to include dependencies.
* Added gettext functions to support translation.

= 1.0.0 =
* Breezy Map v1.0.0 release.

