=== Alpha Cache ===
Contributors: Shra
Donate link: https://www.paypal.me/YuriyKing
Tags: advanced cache, benchmark, cache, loading, performance
Requires at least: 3.0
Tested up to: 6.4
Stable tag: 1.2.006
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Caching plug-in. Easy to setup, free to use and fast in action.

== Description ==

This plugin caches web requests and stores static html code. You can configure what urls do not to cache, what user shouldn't be cached and ect.

After first web request plugin will create a cache of this requested webpage, and on second request your webserver will serve static html code instead of processing the comparatively heavier and more expensive WordPress PHP scripts.

It has some options to drive caching process.

**You can:**

* ... define filter by regular expressions pattern to exclude
necessary urls from cache.
* ... fill user's list to define who will not cached.
* ... do cache only for anonymous users.
* ... forcing rebuild cache on page/comment updating.
* ... setup cache timing.
* ... activate server confing boosters.

and etc.

== Installation ==

1. Upload and extract arhive files to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Check plugin's configuration through wp-admin option's section. Default
configuration is situatable for most cases.
4. And enjoy, of course!

== Screenshots ==

1. Alpha Cache main option's page
2. Boosters options
3. Cache status

== Changelog ==

= 1.2.006 =
* Code review and adding ability to provide what GET params should be ignored on caching.

= 1.2.004 =
* Separate HTTP and HTTPS requests

= 1.2.003 =
* Test and fix for PHP 5.2 compatibility
* Test for WP 4.8

= 1.2.002 =
* Fixed maintain routine check

= 1.2.001 =
* Did some cosmetic changes
* Fixed cache stats
* Replaced short php tags

= 1.2 =
* Now cache uses server HDD, not database.
* Plugin settings also were stored on HDD.
* Added module rewrite Apache support to make cache routine really fast.
* Added Apache setting boosters to make other files transfer faster.

= 1.1.005 =
* Some changes for init section, to apply default params after module install.

= 1.1.004 =
* Fixed database maintain function call.

= 1.1.003 =
* Added support of Wptouch Mobile Plugin. AC will create different caches for mobile and normal pages. Deactivate and then activate the plugin after if you are updating old version.

= 1.1.002 =
* Short cache size datatype was changed from "text" to "mediumtext". Deactivate and then activate the plugin after if you updated old versions.

= 1.1.001 =
* Add usage table_prefix. Don't upgrade. Use uninstall plugin then install this version.

= 1.1 =
* Add compatibility for some WP configurations.
* Fix some bugs
* Add default rule for wp-login.php to avoid cache this script. You can add this rule manually or use "Load defaults".
* Expand statistics. Now you can see table with actual number of cached pages by each user.
* Remove some debug code

= 1.0 =
First version, here is everything we have.

== Upgrade Notice ==

= 1.1.004 =
Just download new version and enjoy.

= 1.1.003 =
Deactivate and then activate the plugin after if you are updating old version. New database structures will be created.

= 1.1.001 =
Delete old version before install this new. Table alpha_cache will be deleted and then will re-created with correct table_prefix.

= 1.1 =
Delete old version before install this new.

== Frequently Asked Questions ==

Ready for your questions :)