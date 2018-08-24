<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: forum.php 33828 2013-08-20 02:29:32Z nemohou $
 */


define('APPTYPEID', 2);
define('CURSCRIPT', 'forum');


require './source/class/class_core.php';


require './source/function/function_forum.php';         // 导入  forum 的 函数库，以备调用


$modarray = array('ajax','announcement','attachment','forumdisplay',
	'group','image','index','medal','misc','modcp','notice','post','redirect',
	'rss','topicadmin','trade','viewthread','tag','collection','guide'
);

$modcachelist = array(
	'index'		=> array('announcements', 'onlinelist', 'forumlinks',
			'heats', 'historyposts', 'onlinerecord', 'userstats', 'diytemplatenameforum'),
	'forumdisplay'	=> array('smilies', 'announcements_forum', 'globalstick', 'forums',
			'onlinelist', 'forumstick', 'threadtable_info', 'threadtableids', 'stamps', 'diytemplatenameforum'),
	'viewthread'	=> array('smilies', 'smileytypes', 'forums', 'usergroups',
			'stamps', 'bbcodes', 'smilies',	'custominfo', 'groupicon', 'stamps',
			'threadtableids', 'threadtable_info', 'posttable_info', 'diytemplatenameforum'),
	'redirect'	=> array('threadtableids', 'threadtable_info', 'posttable_info'),
	'post'		=> array('bbcodes_display', 'bbcodes', 'smileycodes', 'smilies', 'smileytypes',
			'domainwhitelist', 'albumcategory'),
	'space'		=> array('fields_required', 'fields_optional', 'custominfo'),
	'group'		=> array('grouptype', 'diytemplatenamegroup'),
	'topicadmin'	=> array('usergroups'),
);

$mod = !in_array(C::app()->var['mod'], $modarray) ? 'index' : C::app()->var['mod'];         // 提取当前模块,也就是get请求的mod的参数   discuz_application.php   269行

define('CURMODULE', $mod);          // 设置当前模块           这个是在  source/class/discuz/discuz_application.php     269行 设置的
$cachelist = array();
if(isset($modcachelist[CURMODULE])) {
	$cachelist = $modcachelist[CURMODULE];      // 将制定的页面，如index页面所对应内的列表通过数据库或其他持久化数据方式缓存出对应的内容

	$cachelist[] = 'plugin';        // 向数组列表$cachelist插入   plugin
	$cachelist[] = 'pluginlanguage_system';         //  向数组列表$cachelist插入   pluginlanguage_system
}
if(C::app()->var['mod'] == 'group') {
	$_G['basescript'] = 'group';            // 如果页面是group，那么。。。
}

C::app()->cachelist = $cachelist;           // 向核心类的对象cachelist添加需要进一步缓存数据的对象列表
C::app()->init();               // 重要

loadforum();

set_rssauth();

runhooks();

$navtitle = str_replace('{bbname}', $_G['setting']['bbname'], $_G['setting']['seotitle']['forum']);
$_G['setting']['threadhidethreshold'] = 1;
require DISCUZ_ROOT.'./source/module/forum/forum_'.$mod.'.php';

?>