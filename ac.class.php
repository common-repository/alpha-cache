<?php

if (!class_exists('AlphaCacheClass'))  {
class AlphaCacheClass
{
	const actual_version = 1.2006;
	const start_marker = '# START ALPHACACHE';
	const fin_marker = '# END ALPHACACHE';
	const status = 'production';

	var $active;
	var $ac_set; //settings
	var $timer;  //store timer value here
	var $mutex_handler = false, $mutex_options = false;

	var $optionFilename = '';

	var $messages = array();

	static function version()
	{
		return self::actual_version;
	}

    public function __construct()
    {
		$this->timer = microtime(true);
		$this->active = true;
		$this->optionFilename = dirname(__FILE__) . '/settings.php';

		$this->load_settings();

		if (function_exists('add_action')) {
			//Actions
			add_action('init', array($this, 'init_hook'), 0);
			//add_action('plugins_loaded', array($this, 'init_hook'), 0);

			if (is_admin()) {
				add_action( 'admin_menu', array($this, '_add_menu'));
				add_action( 'admin_init', array($this, 'upgrade'), 0);
				add_action( 'admin_notices', array($this, 'admin_notice'), 10);
				add_action( 'admin_footer', array($this, 'htaccess'));
				add_filter( 'plugin_action_links', array($this, 'add_action_links'), 10, 2 );
			}

			//Activity hooks
			if (!empty($this->ac_set['chTRACK'])) {

				//changing post
				add_action('delete_post', array($this, 'post_hook'));
				add_action('post_updated', array($this, 'post_hook'));

				//changing comment
				add_action('wp_set_comment_status', array($this, 'comment_status_hook'));
				add_action('wp_insert_comment', array($this, 'comment_status_hook'));
				add_action('trash_comment', array($this, 'comment_status_hook'));
				add_action('spam_comment', array($this, 'comment_status_hook'));
				add_action('edit_comment', array($this, 'comment_status_hook'));
			}
		}
		//start maintain routine
		$this->maintain();
	}

	public function add_action_links($links, $file) {
		if(strpos($file, 'alpha_cache.php' ) === false ) return $links;
		$mylinks = array(
			'<a href="' . admin_url( 'options-general.php?page=alpha-cache/ac.class.php' ) . '">' . __('Settings') . '</a>',
		);
		return array_merge( $links, $mylinks );
	}

	/* comment status hook */
	public function comment_status_hook($comment_id) {
		global $wpdb;

		$comment_id += 0;
		$post_id = $wpdb->get_var("SELECT comment_post_ID FROM {$wpdb->prefix}comments WHERE comment_ID = {$comment_id}");
		$uri = $this->posturi($post_id);
		$this->delete_cache($uri);
	}

	/* site relative uri - get by post_id */
	static function posturi($post_id) {
		$uri = get_permalink($post_id);
		$a = parse_url($uri);
		unset($a['scheme'], $a['host'], $a['fragment']);
		if (!empty($a['query'])) $a['query'] = '?' . $a['query'];
		return implode('', $a);
	}

	/* post hook */
	public function post_hook($post_id) {
		$uri = $this->posturi($post_id);
		$this->delete_cache('/'); //front page
		$this->delete_cache($uri);
	}

	/* admin_menu hook */
	public function _add_menu() {
		add_options_page('Alpha Cache', 'Alpha Cache', 8, __FILE__, array($this, '_options_page'));
	}

	/* output buffer hook */
	public function call_back_ob($data) {
		$this->log($_SERVER['REQUEST_URI']);
		$this->set_cache($_SERVER['REQUEST_URI'], $data);
		return $data;
	}

	/* init hook */
	public function init_hook() {
		global $wpdb;
		$uri = $_SERVER['REQUEST_URI'];

		if ($this->canDo($uri)) {
			//look to cache
			if (($data = $this->get_cache($uri)) !== false) {
				$this->stat_hit();
				$this->log('HIT!');
				echo $data . "\n<!-- Alpha cache content. Generated from cache in " . (microtime(true) - $this->timer) . ' s. '
					. ' DB queries count : ' . $wpdb->num_queries . ' -->';
				exit;
			}

			$this->stat_miss();
			$this->log('MISS!');
			//start buffering
			ob_start(array($this, 'call_back_ob'));
		} else {
			$this->log('Uncachable request: ' . $uri);
		}
	}

	/* can do cache? */
	public function canDo($uri) {
		global $user_ID, $user_login;

		if (is_admin() || empty($this->ac_set['on']) || !$this->active) return false;

		//check URL list
		$u = explode("\n", $this->ac_set['avoid_urls']);
		foreach($u as $v) {
			$v = trim($v);
			if ($v && preg_match("#{$v}#is", $uri, $m)) {
				$this->active = false;
				return false;
			}
		}

		//cache for anonymous users only
		if (!empty($this->ac_set['chAnon']) && $user_ID > 0) {
			$this->active = false;
			return false;
		}

		if (!empty($user_login)) {
			//check users list
			$u = split("[\s]*,[\s]*", $this->ac_set['users_nocache']);
			if (in_array($user_login, $u)) {
				$this->active = false;
				return false;
			}
		}

		/* check post vars */
		if (!empty($_POST)) {
			$this->active = false;
			return false;
			//allow any kind of form to do what they do
		}

		return $this->active;
	}

	/* successful hit to cache */
	function stat_hit() {
		if (!empty($this->ac_set['doStat'])) {
			if ($this->lockOPT('c+', LOCK_EX)) {
				$data = unserialize(substr(@fread($this->mutex_options, filesize($this->optionFilename)), 8));
				$data['hits'] ++;
				$this->ac_set = $data;
				$data = '<?php /*' . serialize($data);
				ftruncate($this->mutex_options, 0);
				rewind($this->mutex_options);
				fwrite($this->mutex_options, $data);
				$this->unlockOPT();
			}
		}
	}

	/* miss to cache */
	private function stat_miss() {
		if (!empty($this->ac_set['doStat'])) {
			if ($this->lockOPT('c+', LOCK_EX)) {
				$data = unserialize(substr(@fread($this->mutex_options, filesize($this->optionFilename)), 8));
				$data['miss'] ++;
				$this->ac_set = $data;
				$data = '<?php /*' . serialize($data);
				ftruncate($this->mutex_options, 0);
				rewind($this->mutex_options);
				fwrite($this->mutex_options, $data);
				$this->unlockOPT();
			}
		}
	}

	/* Build cache key by provided URL */
	private function getkey(string $uri) {
		static $theme_key = '';

		$p = parse_url($uri);
		//query insensitive case
		if (!empty($this->ac_set['getIns'])) {
			if (empty($this->ac_set['ignore_gets']) || empty($p['query'])) {
				$p['query'] = '';
			} else {
				$params_to_ignore = preg_split('#\s+#', $this->ac_set['ignore_gets']);
				$queries = [];
				parse_str($p['query'], $queries);
				$newQuery = '';
				ksort($queries);
				foreach ($queries as $key => $value) {
					if (in_array($key, $params_to_ignore)) continue;
					$newQuery .= (strlen($newQuery) ? '&' : '') . $key . '=' . $value;
				}
				$p['query'] = $newQuery;
			}
		}

		//normalize
		if (substr($p['path'], 0, 1) == '/')
			$p['path'] = substr($p['path'], 1);
		if (substr($p['path'], -1) == '/')
			$p['path'] = substr($p['path'], 0, -1);
		$uri = $p['path'] . (empty($p['query']) ? '' : '?' . $p['query']);
		$uri = str_replace('..', '', preg_replace('/[ <>\'\"\r\n\t\(\)]/', '', $uri) );

		if (function_exists('wp_get_theme') && !empty($this->ac_set['multythemes'])	&& $theme_key == '') {
			$obj = wp_get_theme();
			$theme_key = '|' . $obj->__get('theme_root') . '/' . $obj->__get('stylesheet') . AUTH_KEY;
		}
		return md5($uri . $theme_key);
	}

	static function server_chema() {
		if (isset($_SERVER['REQUEST_SCHEME']))
			return $_SERVER['REQUEST_SCHEME'];
		if (empty($_SERVER["HTTPS"])) return 'http';
		return 'https';
	}

	//get cache_file_path by key suffix.
	public function cache_file_path($key_end, $ext = 'html') {
		return "{$this->ac_set['cache-dir']}/" . $this->server_chema() . "-{$_SERVER['SERVER_NAME']}/{$key_end}.$ext";
	}

	private function delete_cache($uri) {
		$key = $this->getkey($uri);
		$cache_files = $this->cache_file_path($key, '*');
		array_map("unlink", glob($cache_files));
	}

	private function delete_all_cache() {
		$cache_files = $this->cache_file_path('*');
		array_map("unlink", glob($cache_files));
	}

	public function get_cache($uri) {
		global $user_ID;
		$key = self::getkey($uri);
		$user_ID += 0;

		$cache_file = $this->cache_file_path("$key.{$user_ID}");
		$this->log($cache_file);

		if (file_exists($cache_file)) {
			$time = filemtime($cache_file);
			if (!$time || $time < time() - $this->ac_set['cache_lifetime']) {
				//expired
				$this->log('EXPIRED');
				$this->log(date('d H:i:s', $time) . ' ' . date('d H:i:s', time() - $this->ac_set['cache_lifetime']));
				$this->lock('r', LOCK_EX);
				unlink($cache_file);
				$this->unlock();
				return false;
			};

			//read
			if ($this->lock('r', LOCK_SH)) {
				$data = file_get_contents($cache_file);
			} else {
				$data = '';
			}
			$this->unlock();
			return $data;
		}

		return false;
	}

	private function set_cache($uri, $data) {

		if (empty($data)) return false;
		global $user_ID;
		$user_ID += 0;

		if (is_404()) {
			//prevent cache spamming
			$uri = '404-not-found-page.html';
		}

		$key = $this->getkey($uri);
		//try restore cache storage
		if (!is_dir($this->ac_set['cache-dir'])) {
			$def = self::default_settings();
			$this->ac_set['cache-dir'] = $def['cache-dir'];
			$this->touch_cache_dir();
			$this->save_setttings();
		} else {

			$HOSTDIR = "{$this->ac_set['cache-dir']}/" . $this->server_chema() . "-{$_SERVER['SERVER_NAME']}";
			if (!file_exists($HOSTDIR)) mkdir($HOSTDIR, 0750);

			$cache_file = "{$HOSTDIR}/$key.{$user_ID}.html";

			$this->lock('r', LOCK_EX);
			file_put_contents($cache_file, $data);
			$this->unlock();
			return true;
		}

		return false;
	}

	//MUTEXES
	private function lock($openflag, $locker) {
		$this->mutex_handler = fopen($this->ac_set['cache-dir'] . '/cache_mutex.lock', $openflag);
		$got_lock = true;
		$timeout_secs = 500;

		while (!flock($this->mutex_handler, $locker | LOCK_NB, $wouldblock)) {
			if ($wouldblock && --$timeout_secs > 0) {
				sleep(1);
			} else {
				$got_lock = false;
				break;
			}
		}
		return $got_lock;
	}
	private function unlock() {
		flock($this->mutex_handler, LOCK_UN);
		fclose($this->mutex_handler);
	}

	private function lockOPT($openflag, $locker) {
		$this->mutex_options = fopen($this->optionFilename, $openflag);
		$timeout_secs = 500;
		$got_lock = true;
		while (!flock($this->mutex_options, $locker | LOCK_NB, $wouldblock)) {
			if ($wouldblock && --$timeout_secs > 0) {
				sleep(1);
			} else {
				$got_lock = false;
				break;
			}
		}

		return $got_lock;
	}

	private function unlockOPT() {
		flock($this->mutex_options, LOCK_UN);
		fclose($this->mutex_options);
	}

	public function load_settings() {
		if (!file_exists($this->optionFilename)) {
			$this->ac_set = self::default_settings();
			$this->save_setttings();
		} else {
			if ($this->lockOPT('r', LOCK_SH)) {
				$data = unserialize(substr(@fread($this->mutex_options, filesize($this->optionFilename)), 8));
			} else {
				$data = self::default_settings();
			}
			$this->unlockOPT();
			$this->ac_set = array_merge(self::default_settings(), $data);
		}
	}

	public function save_setttings() {
		$data = '<?php /*' . serialize($this->ac_set);
		$this->lockOPT('c', LOCK_EX);
		ftruncate($this->mutex_options, 0);
		fwrite($this->mutex_options, $data);
		$this->unlockOPT();
	}

	//clean up cache
	public function maintain() {
		//too early for maintain
		if ($this->ac_set['last-maintain'] + $this->ac_set['dbmaintain_period'] > time()) return;

		$expire_limit = time() - $this->ac_set['dbmaintain_period'];

		if (is_dir($this->ac_set['cache-dir']) && $hdir = opendir($this->ac_set['cache-dir'])) {
			$this->lock('r', LOCK_EX);

			while (false !== ($entry = readdir($hdir))) {
				$dname = $this->ac_set['cache-dir'] . '/' . $entry;
				if ($entry != "." && $entry != ".." && is_dir($dname) && $hcache = opendir($dname)) {
					while (false !== ($entry_file = readdir($hcache))) {
						$fname = $dname . '/' . $entry_file;
						if ($entry_file != "." && $entry_file != ".." && filectime($fname) < $expire_limit ) {
							//expired
							unlink($fname);
						}
					}
					closedir($hcache);
				}
			}

			$this->unlock();
			closedir($hdir);
		}

		$this->ac_set['last-maintain'] =  time();
		$this->save_setttings();
	}

	/* check htaccess */
	public function htaccess() {
		$this->lockOPT('r', LOCK_EX);
		//update and check .htaccess
		$ht = file_get_contents(ABSPATH . '.htaccess');

		//find host name
		$host = get_option('siteurl');
		if (preg_match('@^https?://(.+)$@is', $host, $m)) {
			$host = $m[1];
		} else {
			$host = $_SERVER['HTTP_HOST'];
		}

		$code = '';

		if (!empty($this->ac_set['on'])) {

			$relative_url = str_replace("\\", '/', substr(dirname(__FILE__), strlen($_SERVER['DOCUMENT_ROOT']) + 1));
			$abs_url = str_replace("\\", '/', ABSPATH) . $relative_url;

			$code .= '
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{HTTP_USER_AGENT} !(facebookexternalhit|WhatsApp|Mediatoolkitbot)
RewriteCond %{REQUEST_METHOD} !POST
RewriteCond %{HTTP:Cookie} !(wordpress_logged_in|wp_woocommerce_session|safirmobilswitcher=mobil)
RewriteCond %{HTTP:Profile} !^[a-z0-9\"]+ [NC]
RewriteCond ' . $abs_url . '/router.php -f
RewriteRule .? /' . $relative_url . '/router.php [L]
</IfModule>';
			if (!empty($this->ac_set['speed-expire'])) {
				$code .= '
<ifModule mod_headers.c>
<FilesMatch "\.(html|htm)$">
Header set Cache-Control "max-age=43200"
</FilesMatch>
<FilesMatch "\.(js|css|txt)$">
Header set Cache-Control "max-age=604800"
</FilesMatch>
<FilesMatch "\.(flv|swf|ico|gif|jpg|jpeg|png)$">
Header set Cache-Control "max-age=2592000"
</FilesMatch>
<FilesMatch "\.(pl|php|cgi|spl|scgi|fcgi)$">
Header unset Cache-Control
</FilesMatch>
</IfModule>
<ifModule mod_expires.c>
ExpiresActive On
ExpiresDefault "access plus 5 seconds"
ExpiresByType image/x-icon "access plus 30 days"
ExpiresByType image/jpeg "access plus 30 days"
ExpiresByType image/png "access plus 30 days"
ExpiresByType image/gif "access plus 30 days"
ExpiresByType image/webp "access plus 30 days"
ExpiresByType application/x-shockwave-flash "access plus 30 days"
ExpiresByType text/css "access plus 30 days"
ExpiresByType text/javascript "access plus 30 days"
ExpiresByType application/javascript "access plus 30 days"
ExpiresByType application/x-javascript "access plus 30 days"
ExpiresByType text/html "access plus 12 hours"
ExpiresByType application/xhtml+xml "access plus 5 minutes"
ExpiresByType application/xml "access plus 5 minutes"
</ifModule>';
			}
			if (!empty($this->ac_set['speed-deflate'])) {
				$code .= '
<IfModule mod_deflate.c>
AddType x-font/woff .woff
AddOutputFilterByType DEFLATE image/svg+xml
AddOutputFilterByType DEFLATE text/plain
AddOutputFilterByType DEFLATE text/html
AddOutputFilterByType DEFLATE text/xml
AddOutputFilterByType DEFLATE text/css
AddOutputFilterByType DEFLATE text/javascript
AddOutputFilterByType DEFLATE application/xml
AddOutputFilterByType DEFLATE application/xhtml+xml
AddOutputFilterByType DEFLATE application/rss+xml
AddOutputFilterByType DEFLATE application/javascript
AddOutputFilterByType DEFLATE application/x-javascript
AddOutputFilterByType DEFLATE application/x-font-ttf
AddOutputFilterByType DEFLATE application/vnd.ms-fontobject
AddOutputFilterByType DEFLATE font/opentype font/ttf font/eot font/otf
<IfModule mod_setenvif.c>
BrowserMatch ^Mozilla/4 gzip-only-text/html
BrowserMatch ^Mozilla/4\.0[678] no-gzip
BrowserMatch \bMSIE !no-gzip !gzip-only-text/html
</IfModule>
</IfModule>';
			}
		}

		if (!empty($code)) {
		$code = '# START ALPHACACHE' . $code . '
# END ALPHACACHE
';
		}

		if (strpos($ht, self::start_marker) === false) {
			if (!empty($code))
				//insert the code
				file_put_contents(ABSPATH . '.htaccess', $code . $ht);
		} else {
			if (empty($code)) {
				//remove code
				$ht = $this->ht_clean($ht);
				file_put_contents(ABSPATH . '.htaccess', $ht);
			} else {
				if (strpos($ht, $code) === false) {
					//codes differ
					$ht = $this->ht_clean($ht);
					file_put_contents(ABSPATH . '.htaccess', $code . $ht);
				} else {
					//codes are equal
					;
				}
			}

		}

		$this->unlockOPT();
	}

	private function ht_clean($ht) {
		$start = strpos($ht, self::start_marker);
		$out = '';
		if ($start !== false) {
			$out .= substr($ht, 0, $start);
		}

		$fin = strpos($ht, self::fin_marker . '
');
		if ($fin !== false) {
			$out .= substr($ht, $fin + strlen(self::fin_marker . '
'));
		}

		return empty($out) ? $ht : $out;
	}

	/* Upgade routines */
	public function upgrade() {
		global $wpdb;
		$upgraded = false;

		//upgrade up to 1.2
		if ($this->ac_set['ver'] < 1.2) {
			//remove cache table, now we use file-cache
			$wpdb->query("DROP TABLE IF EXISTS `cache_alpha`");
			$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}cache_alpha`");
			delete_option('alpha_cache_settings');

			$this->touch_cache_dir();
			$upgraded = true;
			$this->ac_set['ver'] = 1.2;
		}

		//upgrade up to 1.2004
		if ($this->ac_set['ver'] < 1.2004) {
			$this->delete_all_cache();
		}

		if ($upgraded) {
			$this->ac_set['ver'] = self::actual_version;
			$this->save_setttings();
		}
	}

	public function touch_cache_dir() {
		if (!file_exists($this->ac_set['cache-dir'])) {
			if (!mkdir($this->ac_set['cache-dir'], 0750 )) {
				$this->messages[] = 'create_cache_dir_error';
			} else {
				$this->messages[] = 'create_cache_dir_success';

				$mutexFilename = $this->ac_set['cache-dir'] . '/cache_mutex.lock';
				if (!file_exists($mutexFilename))
					touch($mutexFilename);
			}
		}
	}

	/* different admin notices */
	public function admin_notice() {

		if (empty($this->messages)) return false;

		foreach ($this->messages as $code) {

			if (preg_match('/[_]([a-z]+)$/', $code, $m))
				$type = $m[1];
			else
				$type = 'info';

			switch($code) {
			case 'wp_cache_postload_error':
				$str = 'Alpha Cache conflicts with someone another caching module. Please use only one module for proper work.';
				break;
			case 'create_cache_dir_error':
				$str = 'Can`t create the directory for cache files.';
				break;
			case 'create_cache_dir_success':
				$str = 'Cache files directory created.';
				break;
			default:
				$str = 'WTF error';
			}
?>
    <div class="notice notice-<?php echo $type; ?> is-dismissible">
        <p><?php echo __( $str ); ?></p>
    </div>
<?php
		}
	}

	/* Options admin page */
	public function _options_page() {
		global $wpdb;

		if (isset($_POST['action'])) {
			switch ($_POST['action']) {
			case 'save_cache_settings':
				//check & store new values
				if ($_POST['cache_lifetime'] < 60) {
					echo '<div class="error>' . __('Lifetime period too short. I set minimum - 60 s.') . '</div>';
					$_POST['cache_lifetime'] = 60;
				}
				unset($_POST['action'], $_POST['sbm'], $_POST['users']);
				if ($_POST['dbmaintain_period'] < 3600) {
					echo '<div class="error>' . __('Maintain period too short. I set minimum - 1 hour.') . '</div>';
					$_POST['dbmaintain_period'] = 3600;
				}

				$flags = array('doStat', 'chTRACK', 'chAnon', 'on', 'multythemes', 'speed-expire', 'speed-deflate', 'getIns');
				foreach ($flags as $flag) {
					if (!isset($_POST[$flag]))
						$_POST[$flag] = 0;
				}
				//
				$_POST['cache-dir'] = $this->ac_set['cache-dir'];
				$this->ac_set = array_merge($this->ac_set, $_POST);
				unset($this->ac_set['action'], $this->ac_set['active-section'], $this->ac_set['users']);
				$this->save_setttings();
				echo '<div class="updated"><p>' . __("Settings were updated.") . '</p></div>';

				break;
			case 'clear cache data':
				$this->delete_all_cache();
				break;
			case 'clear statistics':
				$this->ac_set['hits'] = 0;
				$this->ac_set['miss'] = 0;
				$this->save_setttings();
				break;
			case 'load defaults':
				$new_set = self::default_settings();
				$new_set['hits'] = $this->ac_set['hits'];
				$new_set['miss'] = $this->ac_set['miss'];
				$this->ac_set = $new_set;
				$this->save_setttings();
			}
		}

		$acs = $this->ac_set;
		require_once dirname(__FILE__) . '/page-options.php';
  }

	/* install actions (when activate first time) */
    static function install() {
		//nothing relly to do :)
	}

	public function log($mess) {
		if (self::status != 'production')
			file_put_contents(dirname(__FILE__) . '/log.txt', $mess . "\n", FILE_APPEND);
	}

	static function default_settings() {
		$content_dir = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : $_SERVER['DOCUMENT_ROOT'] . '/wp-content';

		return array(
			'cache_lifetime' => 21600,
			'dbmaintain_period' => 86400,
			//no cache on admin's pages
			'avoid_urls' => '^/wp-login.php',
			'users_nocache' => '',
			'doStat' => 0,
			'chTRACK' => 1,
			'chAnon' => 1,
			'last-maintain' => time(),
			//since v1.2
			'cache-dir' => dirname(__FILE__) . '/cache',
			'ver' => 1.0,
			'multythemes' => 0,
			'on' => 1,
			'speed-expire' => 1,
			'speed-deflate' => 1,
			//since v1.2.001
			'getIns' => 0,
			//since v1.2.006
			'ignore_gets' => '',
		);
	}

	/* uninstall hook */
    static function uninstall() {
		global $wpdb;
		$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}cache_alpha`");
	}

	static function inttoMB($int) {
		return number_format($int / 1048576, 2, '.', ',') . ' Mb';
	}

}}
