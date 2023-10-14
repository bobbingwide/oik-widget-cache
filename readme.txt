=== oik-widget-cache ===
Contributors: bobbingwide
Donate link: http://www.oik-plugins.com/oik/oik-donate/
Tags: widget, cache, optional
Requires at least: 5.5
Tested up to: 5.6-RC2
Stable tag: 0.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Cache for widgets. 

- Provides a checkbox to indicate if a widget should be cached. 
- Default is no caching.
- Caches the widget for 12 hours.
- Supports caching of widgets which enqueue styles or scripts.
- Supports caching of widgets which enqueue inline jQuery using bw_jq.

Not suitable widgets where the content changes depending on the context.


== Installation ==
1. Upload the contents of the oik-widget-cache plugin to the `/wp-content/plugins/oik-widget-cache' directory
1. Activate the oik-widget-cache plugin through the 'Plugins' menu in WordPress
1. Choose the widgets that may be cached.

== Frequently Asked Questions ==

= Why is this dependent upon oik?

It's not really. It will use oik's class-dependencies-cache if available.



== Screenshots ==
1. oik-widget-cache in action

== Upgrade Notice ==
= 0.0.2 = 
Tested with WordPress 5.6-RC2 and WordPress Multi Site

= 0.0.1 = 
Now supports widgets which enqueue scripts and styles, including inline jQuery code with bw_jq.

= 0.0.0 =
New plugin, available from GitHub

== Changelog == 
= 0.0.2 = 
* Tested: With WordPress 5.6-RC2 and WordPress Multi Site
* Tested: With PHP 7.4


= 0.0.1 = 
* Added: Works with oik base plugin to handling enqueued script and style dependencies
* Added: Support for inline jQuery enqueued using bw_jq
* Changed: Increased cache time from 12 mins to 12 hours
* Tested: With WordPress 4.7.1 and WordPress Multisite

= 0.0.0 =
* Added: New plugin

== Further reading ==
If you want to read more about the oik plugins then please visit the
[oik plugin](https://www.oik-plugins.com/oik) 
**"the oik plugin - for often included key-information"**

oik-widget-cache was originally based on the widget-output-cache plugin by Kaspars Dambis ( @kasparsd ).
It was forked from version 0.5.1. 
oik-widget-cache reversed the logic for determining which widgets should be cached; inclusions rather than exclusions.




