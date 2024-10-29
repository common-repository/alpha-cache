<?php

require_once dirname(__FILE__) . '/ac.class.php';

$timer = microtime(true);
$uri = $_SERVER['REQUEST_URI'];
$ac_obj = new AlphaCacheClass();
$ac_obj->log('routed...');

if (($data = $ac_obj->get_cache($uri)) !== false) {
	$ac_obj->stat_hit();
	$ac_obj->log('FAST HIT!');
	echo $data . "\n<!-- Alpha cache content. Generated from cache in " . (microtime(true) - $timer) . ' s. '
		. ' DB queries count : 0! -->';
	exit;
} else {
	unset($ac_obj);
	include $_SERVER['DOCUMENT_ROOT'] . '/index.php';
}
