<?php
global $alpha_cache_obj;

//check object existance
if (!isset($alpha_cache_obj) || get_class($alpha_cache_obj) != 'AlphaCacheClass') {
	exit;
}

?>

<div class="wrap">
	<h2><?php echo __('Alpha cache settings');?></h2>

	<div class="block-elm">
	<form method="post" id="ACS_form">
		<input type="hidden" name="action" value="save_cache_settings" />
		<input type="hidden" name="last-maintain" value="<?php echo $acs['last-maintain']?>" />
		<input type="hidden" id="ACS_as" name="active-section" value="" />

		<table border=0 cellspacing=5 cellpadding=0 width=775 class="sub-page-left-padding">
		<tr><td width="25%"></td><td width="25%"></td><td width="25%"></td><td width="25%"></td></tr>
		<tr valign="top">
			<td colspan=3>
			<label><input type="checkbox" name="on" value="1" <?php echo empty($acs['on']) ? '' : 'checked'?> />
			 <?php echo __('Cache is working')?></label><br />
			</td><td align="right">
				<input type="button" class="button-primary" onclick="this.form.action.value='load defaults'; this.form.submit();"
					 value="<?php echo __('Load defaults')?>" />
			</td>
		</tr>
		</table>

		<nav id="ACH_pager">
			<div id="ACH_pager_stick_1" data-page="ACH_page_1"><?php echo  __('Main controls')?></div>
			<div id="ACH_pager_stick_2" data-page="ACH_page_11"><?php echo  __('Boosters')?></div>
			<div id="ACH_pager_stick_3" data-page="ACH_page_2" id="misc-button"><?php echo  __('Cache')?></div>
			<div id="ACH_pager_stick_4" data-page="ACH_page_3"><?php echo  __('About plugin')?></div>
		</nav>

		<div class="sub-page" id="ACH_page_1" >
		<table border=0 cellspacing=5 cellpadding=0 width=760>
		<tr><td width="25%"></td><td width="25%"></td><td width="25%"></td><td width="25%"></td></tr>
		<tr valign="top">
			<td colspan=3>
			<label for="avoid_urls"><b><?php echo __('Set rules to avoid caching necessary urls')?></b></label><br />
			<small><?php echo __('One line - one rule, here you should use <a target="_blank" href="http://www.php.net/manual/en/pcre.pattern.php">PCRE Patterns</a>.')?></small><br />
			<textarea name="avoid_urls" rows="5" cols="60"><?php echo htmlspecialchars($acs['avoid_urls'])?></textarea><br />
			</td><td>

			</td>
		</tr>
		<tr valign="top">
			<td colspan=2>
			<label for="users_nocache"><b><?php echo __('Don`t cache these users')?></b></label><br />
			<small><?php echo __('Input logins separated by comma or use user`s list.');?></small><br />
			<textarea id="users_nocache" name="users_nocache" rows="5" cols="60"><?php echo htmlspecialchars($acs['users_nocache'])?></textarea>
			</td>
			<td colspan=2>
			<br/>
			<small><?php echo __('You can use multi-select.')?></small><br />
			<select id="user_selector" name="users" multiple size="5" style="width: 350px;">
<?php
	$rows = $wpdb->get_results("SELECT ID, user_login, user_email FROM {$wpdb->prefix}users ORDER BY user_login");
	foreach($rows as $v) {
		echo "<option value=\"{$v->user_login}\">{$v->user_login} ({$v->user_email})</option>";
	}
?>
			</select><br />
			<input type="button" class="button" onclick="
	var slk = document.getElementById('user_selector');
	var st = Array();

	for (var i = 0; i<slk.options.length; i++)
		if (slk.options[i].selected) {
			st[st.length] = slk.options[i].value;
		}
	var txa = document.getElementById('users_nocache');
	var tagList = txa.value.split(/\s*,\s*/);

	if (tagList.length == 1 && tagList[0] == '') tagList = Array();

	for (j = 0; j < st.length; j++) {
		var exst = false;

		for (i = 0; i < tagList.length; i++) {
			if (tagList[i] == st[j]) {
				exst = true;
			}
		}
		if (!exst) {
			tagList[tagList.length] = st[j];
		}
	}

	txa.value = tagList.join(', ');
				" value="<?php echo __('Add to list')?>" />
			</td>
		</tr>

		<tr valign="top">
			<td colspan=3>
			<label >
			<input type="checkbox" name="doStat" value="1" <?php echo empty($acs['doStat']) ? '' : 'checked' ?> />
			<?php echo __('Count hits and misses to cache.')?></label>
<?php
	if (!empty($acs['doStat'])) {
		echo '<br /><i>';

		if ($acs['hits'] + $acs['miss']) {
			$total = $acs['hits'] + $acs['miss'];
			$ratio = sprintf("%01.2f", $acs['hits'] / $total * 100);

			echo __("We have $ratio % of cached queries of $total total requests.");
		} else {
			echo __("We have no statistics yet.");
		}
		echo "</i>";
	}
?>
			</td><td align="right">
<?php
			if (!empty($acs['doStat'])):
?>
				<input type="button" class="button-secondary-red" onclick="this.form.action.value='clear statistics'; this.form.submit();"
					 value="<?php echo __('Clear stats')?>" />
<?php
			endif;
?>

			</td>
		</tr>

		<tr valign="top">
			<td colspan=4>
			<label>
			<input type="checkbox" name="chAnon" value="1" <?php echo empty($acs['chAnon']) ? '' : 'checked'?> />
			<?php echo __('Do cache only for anonymous users.')?></label><br />
			</td>
		</tr>

		<tr valign="top">
			<td colspan=4>
			<label>
			<input type="checkbox" name="chTRACK" value="1" <?php echo empty($acs['chTRACK']) ? '' : 'checked' ?> />
			<?php echo __('Clean cache for updated posts/comments')?></label>
			</td>
		</tr>

		<tr valign="top">
			<td colspan=4>
			<label>
			<input type="checkbox" name="multythemes" value="1" <?php echo empty($acs['multythemes']) ? '' : 'checked' ?> />
			<?php echo __('Multy theme site')?></label><br />
			<small><?php echo __('Check it if your website uses plugins like «Any mobile theme switcher», which allows to use more then one theme.');?></small>
			</td>
		</tr>

		<tr valign="top">
			<td colspan=4>
			<label>
			<input type="checkbox" name="getIns" value="1" <?php echo empty($acs['getIns']) ? '' : 'checked' ?> />
			<?php echo __('GET vars insensitive')?></label><br />
			<small><?php echo __('Cache will ignore GET parameters (everything after ? in url address). So page /?a=1 will be equal to /?a=2.');?>
			      <?php echo __('Also you may provide list of GET parameters to ignore (space separated keys like "param1 param2 param3").');?><br />
						<?php echo __('If provided only they will be ignored.');?></small><br />
			<textarea name="ignore_gets" rows="3" cols="60"><?php echo htmlspecialchars($acs['ignore_gets'])?></textarea><br />

			</td>
		</tr>
		</table>
		</div>

		<div class="sub-page" id="ACH_page_11" >
		<table border=0 cellspacing=5 cellpadding=0 width=760>
		<tr><td width="25%"></td><td width="25%"></td><td width="25%"></td><td width="25%"></td></tr>
		<tr valign="top">
			<td colspan=4>
			<p><?php echo __('Next switchers can decrease server load and traffic volume for Apache web servers.')?></p>
			<label>
			<input type="checkbox" name="speed-expire" value="1" <?php echo empty($acs['speed-expire']) ? '' : 'checked' ?> />
			<?php echo __('Activate browser cache')?></label><br />
			<small><?php echo __('This checkbox plays with Apache mod_headers and mod_expires params.');?></small>
			</td>
		</tr>

		<tr valign="top">
			<td colspan=4>
			<label>
			<input type="checkbox" name="speed-deflate" value="1" <?php echo empty($acs['speed-deflate']) ? '' : 'checked' ?> />
			<?php echo __('Activate server side compression')?></label><br />
			<small><?php echo __('This checkbox plays with Apache mod_deflate params.');?></small>
			</td>
		</tr>
		</table>
		</div>

		<div class="sub-page" id="ACH_page_2" >
		<table border=0 cellspacing=5 cellpadding=0 width=760>
		<tr><td width="25%"></td><td width="25%"></td><td width="25%"></td><td width="25%"></td></tr>
		<tr valign="top">
			<td colspan=4>
				<p><?php echo __('It`s all about cache.')?></p>
			</td>
		</tr>
		<tr valign="top">
			<td colspan=2>
			<label for="cache_lifetime"><b><?php echo __('Cache lifetime')?></b></label><br />
			<small><?php echo __('Set lifetime of single cache record in seconds.');?></small>
			</td><td colspan=2>
			<input type="text" style="text-align: right;" name="cache_lifetime" size="10" value="<?php echo htmlspecialchars($acs['cache_lifetime'])?>" /> <?php echo __('s.')?>
			</td><td align="right">
				<input type="button" class="button-secondary-red" onclick="this.form.action.value='clear cache data'; this.form.submit();"
					 value="<?php echo __('Clear cache')?>" />
			</td>
		</tr>
		<tr valign="top">
			<td colspan=2>
			<label for="dbmaintain_period"><b><?php echo __('Maintain period')?></b></label><br />
			<small><?php echo __('Periodically it checks and cache clean-ups. All expired cache data will be removed.');?></small>
			</td><td colspan=2>
			<input type="text" style="text-align: right;" name="dbmaintain_period" size="10" value="<?php echo htmlspecialchars($acs['dbmaintain_period'])?>" /> <?php echo __('s.')?>
			</td>
		</tr>
		<tr valign="top">
			<td colspan=2>
			<b><?php echo __('Last maintain')?></b><br />
			<small><?php echo __('When clean-up cache routine was ran last time.');?></small>
			</td><td colspan=2><?php echo !empty($acs['last-maintain']) ? date('j M Y H:i', $acs['last-maintain']) . ' GTM' : __('Never')?></td>
		</tr>
		<tr valign="top">
			<td colspan=2>
			<label for="cache-dir"><b><?php echo __('Cached files directory')?></b></label><br />
			<small><?php echo __('Where cached files actually will placed.');?></small>
			</td><td colspan=2>
			<textarea readonly style="width: 100%; resize: none;"><?php echo htmlspecialchars($acs['cache-dir'])?></textarea>
			</td>
		</tr>
		<tr valign="top">
			<td colspan=2>
			<label for="cache-dir"><b><?php echo __('Disk usage stats')?></b></label><br />
			</td><td colspan=2>
<?php
		$stats = array();
		if (is_dir($this->ac_set['cache-dir']) && $hdir = opendir($this->ac_set['cache-dir'])) {
			while (false !== ($entry = readdir($hdir))) {
				$dname = $this->ac_set['cache-dir'] . '/' . $entry;
				if ($entry != "." && $entry != ".." && is_dir($dname) && $hcache = opendir($dname)) {

					$stats[$entry] = array('total' => 0);

					while (false !== ($entry_file = readdir($hcache))) {
						$fname = $dname . '/' . $entry_file;
						if ($entry_file != "." && $entry_file != ".." && !is_dir($fname) ) {
							$spl = explode('.', $entry_file);
							$filesize = filesize($fname);
							if (!isset($stats[$entry][$spl[1]])) {
								$stats[$entry][$spl[1]]['cnt'] = 1;
								$stats[$entry][$spl[1]]['size'] = $filesize;
							} else {
								$stats[$entry][$spl[1]]['cnt'] ++;
								$stats[$entry][$spl[1]]['size'] += $filesize;
							}
							$stats[$entry]['total'] += $filesize;
						}
					}
					closedir($hcache);
				}
			}
			closedir($hdir);
		}

		echo "<table border=1 cellpadding=5 cellspacing=0 width=100% style='border-collapse: collapse'><tr>
			<th>" . __('User name') . "</th>
			<th>" . __('Urls') . "</th>
			<th>" . __('Size') . "</th></tr>";

		if (!empty($stats)) {
			foreach($stats as $host => $data) {
				echo '<tr><td colspan="3">' . $host. '</td></tr>';

				foreach($data as $usrID => $usr) {
					if ($usrID === 'total') continue;
					echo "<tr><td>" . ($usrID ? htmlspecialchars($usrID) : __('Anonymous')) . '</td><td td align=center>' . $usr['cnt']
					. '</td><td align=right>' . self::inttoMB($usr['size']) .  '</td></tr>';
				}
				echo '<tr><td colspan="2">' . __('total for') . ' ' . $host . '</td><td td align=right>' . self::inttoMB($stats[$host]['total']) . '</td></tr>';
			}
		}
		echo '</table>';
?>
			</td>
		</tr>

		<tr>
			<td colspan="4">
			</td>
		</tr>
		</table>
		</div>

	</form>

	<div class="sub-page" id="ACH_page_3" >
		<p>
			<b>Version: <?php echo AlphaCacheClass::version()?><br />
			<?php echo __('Developer')?>: <a href="http://shra.ru" rel="nofollow" target="_blank" >Korol Yuriy</a><br />
			<?php echo __('Plugin page')?>: <a href="https://wordpress.org/plugins/alpha-cache/" rel="nofollow" target="_blank" >wordpress.org/plugins/alpha-cache</a><br />
		</p>
		<p>
			<?php echo __('Enjoy, this plugin is free to use. But you can support plugin development.')?><br />
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="BLLQY2VNMZ44Y">
			<input type="image" src="https://www.paypalobjects.com/en_US/RU/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
			</form>
		</p>
	</div>

	<hr />
	<div class="sub-page-left-padding">
		<button class="button-primary" onclick="jQuery('#ACS_form').submit();"><?php echo __('Save changes')?></button>
	</div>

	</div>
</div>
<style>
	.button-secondary-red {
		background: #ba0000;
		border-color: #690000 #690000 #690000;
		-webkit-box-shadow: 0 1px 0 #690000;
		box-shadow: 0 1px 0 #690000;
		color: #fff;
		text-decoration: none;
		text-shadow: 0 -1px 1px #690000, 1px 0 1px #690000, 0 1px 1px #690000, -1px 0 1px #690000;
		display: inline-block;
		text-decoration: none;
		font-size: 13px;
		line-height: 26px;
		height: 28px;
		margin: 0;
		padding: 0 10px 1px;
		cursor: pointer;
		border-width: 1px;
		border-style: solid;
		-webkit-appearance: none;
		-webkit-border-radius: 3px;
		border-radius: 3px;
		white-space: nowrap;
	}
	.button-secondary-red:hover {
	    background: #ca0000;
		color: #fff;
	}
	.block-elm {
		display: inline-block;
	}
	.block-elm .about {

	}

	nav#ACH_pager {
		border-bottom: 1px solid #696969;
		padding: 0 0 0 15px;
		box-shadow: 0 3px 0px #ccc;
	}
	nav#ACH_pager div {
		background: #FFF;
		-webkit-box-shadow: 0 1px 0 #696969;
		box-shadow: 0 1px 0 #696969;
		color: #000;
		text-decoration: none;
		display: inline-block;
		text-decoration: none;
		font-size: 20px;
		line-height: 30px;
		height: 32px;
		margin: 0 5px 0 0;
		padding: 0 20px 1px;
		cursor: pointer;
		border: 1px solid #696969;
		border-bottom: 0;
		border-radius: 3px 3px 0 0 ;
		white-space: nowrap;
	}
	nav#ACH_pager div.active {
		background: #008ec2;
		color: #FFF;
	}

	.sub-page {
		padding: 0 0 0 15px;
		display: none;
		font-size: 14px;
		line-height: 18px;
	}
	.sub-page p {
		line-height: 18px;
		font-size: 14px;
	}
	.sub-page-left-padding {
		padding-left: 15px;
	}

</style>

<script>
jQuery(function() {
	jQuery('nav#ACH_pager div').click(function (){
		jQuery('nav#ACH_pager div').removeClass('active');
		jQuery(this).addClass('active');
		var pageID = jQuery(this).attr('data-page');
		jQuery('.sub-page').hide();
		jQuery('#' + pageID).show();
		jQuery('#' + pageID).show();
		jQuery('#ACS_as').get(0).value = this.id;
	});

	jQuery('nav#ACH_pager <?php echo (!empty($_POST['active-section'])) ? '#' . $_POST['active-section'] : 'div:first-child' ?>').trigger('click');
  // Handler for .ready() called.
});

</script>