<?php
/*
Plugin Name: oik-widget-cache
Plugin URI: http://www.oik-plugins.com/oik-plugins/oik-widget-cache
Description: Caches widget output for selected widgets
Version: 0.0.1
Author: bobbingwide
Author URI: http://www.oik-plugins.com/author/bobbingwide
Text Domain: oik-widget-cache
Domain Path: /languages/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

    Copyright 2016,2017 Bobbing Wide (email : herb@bobbingwide.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2,
    as published by the Free Software Foundation.

    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    The license for this software can likely be found here:
    http://www.gnu.org/licenses/gpl-2.0.html

*/
oik_widget_cache_loaded();

/**
 * Function to invoke when oik-widget-cache is loaded
 *
 * Load the oik_widget_cache class and instantiate it
 * 
 */
function oik_widget_cache_loaded() {
	oik_require( "class-oik-widget-cache.php", "oik-widget-cache" );
  oik_widget_cache::instance();
}

