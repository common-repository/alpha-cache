<?php
/*
Plugin Name: Alpha cache
Plugin URI: http://wordpress.org/plugins/alpha-cache/
Description: Caching plug-in. Its makes your WP faster and your blog life happy. Easy to setup, free to use and fast in action.
Author: Korol Yuriy aka Shra <to@shra.ru>
Author URI: http://shra.ru
Requires at least: 3.0
Version: 1.2.006
Donate link: https://www.paypal.me/YuriyKing
Tags: advanced cache, benchmark, benchmarking, cache, cacheing, caching, cash, execution, fast, highly extensible, loading, options panel included, performance, quick cache, quickcache, speed, super cache
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

*/

if (function_exists( 'register_uninstall_hook' )) {
	require_once dirname(__FILE__) . '/ac.class.php';

	register_uninstall_hook( __FILE__, array('AlphaCacheClass', 'uninstall'));
	register_activation_hook( __FILE__, array('AlphaCacheClass', 'install') );

	if (class_exists("AlphaCacheClass")) {
		global $alpha_cache_obj;
		$alpha_cache_obj = new AlphaCacheClass();
	}
}
