<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

include 'function/code.php';

class plugin_zxsq_code {
	function checkflag() {
		global $_G;
		if(array_key_exists('zxsq_plugin_flags', $_G)) {
			if(in_array('zxsq_code', $_G['zxsq_plugin_flags']) || in_array('zxsq_markdown', $_G['zxsq_plugin_flags'])) {
				return True;
			}
		}
		return False;
	}

	function global_header() {
		// 按需加载资源文件
		if(!$this->checkflag()) {
			return "";
		}
		//代码高亮处理
		$code = new Highlight();
		$hilight = $code->header();

		//浏览帖子时才插入脚本
		if(CURMODULE == 'viewthread') {
			return $hilight;
		}
		return "";
	}
	
	function setflag($item) {
		global $_G;
		if(array_key_exists("zxsq_plugin_flags", $_G)) {
			$zxsq_plugin_flags = $_G['zxsq_plugin_flags'];
		}else {
			$zxsq_plugin_flags = array();
		}
		$zxsq_plugin_flags[] = $item;
		setglobal("zxsq_plugin_flags", $zxsq_plugin_flags);
	}

	function discuzcode($param) {
		global $_G;
		//print_r($_G['discuzcodemessage']);	
		// 如果内容中没有 tex 的话则不尝试正则匹配
		if (strpos($_G['discuzcodemessage'], '[/pre]') === false) {
			return false;
		}
		$this->setflag('zxsq_code');
		// 仅在解析discuzcode时执行对 tex 的解析
		$pattern = '/\s?\[pre\][\n\r]*(.+?)[\n\r]*\[\/pre\]\s?/s';
		if($param['caller'] == 'discuzcode') {
			$_G['discuzcodemessage'] = preg_replace_callback($pattern, array($this,'optex'), $_G['discuzcodemessage']);
		} else {
			$_G['discuzcodemessage'] = preg_replace('/\[\/?pre\]/s', '', $_G['discuzcodemessage']);
		}
	}
	function optex($match) {
		$texcode = $match[1];
		$res = new Highlight();
		return $res->run($texcode);
	}
}


class plugin_zxsq_code_forum extends plugin_zxsq_code {
	//回调函数，替换<br />
	function delbr($match) {
		$match[2] = str_replace("<br />", "", $match[2]);
		$match[2] = str_replace("[zxsq-anti-bbcode-", "[", $match[2]);
		
		//$match[2] = preg_replace("#(<br />)?<br />([^<][^b][^r][^\s][^/][^>])?#m", "\\1", $match[2]);
		//替换code中多余的&amp;
		if(substr($match[1], 0, 5) == "<code") {
			$match[2] = str_replace("&amp;", "&", $match[2]);
		}
        $match[2] =  "<ol>".preg_replace('/(.*)[\n|$]?/', "<li>\$1</li>", $match[2])."</ol>";
		return $match[1] . $match[2] . $match[3];
	}	

	function viewthread_bottom_output() {
		global $postlist;
		$pattern = array();
		$pattern[] = '/(<code class=)(.+?)(<\/code>)/s';
		$pattern[] = '/(<code>)(.+?)(<\/code>)/s';
		foreach($postlist as $pid => $post) {
			for($i=0; $i<count($pattern); $i++) {
				$post['message'] = preg_replace_callback($pattern[$i],array($this,'delbr'),$post['message']);
			}
			$postlist[$pid] = $post;
		}
		return '';
	}

}

class plugin_zxsq_code_group extends plugin_zxsq_code_forum {
}

class mobileplugin_zxsq_code extends plugin_zxsq_code {
	function global_header_mobile() {
		return parent::global_header();
	}
}

class mobileplugin_zxsq_code_forum extends plugin_zxsq_code_forum {
}
