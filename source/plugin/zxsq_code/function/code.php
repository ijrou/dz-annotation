<?php

if(!defined('IN_DISCUZ')) {
		exit('Access Denied');
}

class Highlight {
	function header() {
		global $_G;
		@extract($_G['cache']['plugin']['zxsq_code']);

		$dir = "source/plugin/zxsq_code/";
		$css = $dir . "tools/highlight/styles/";
		$js = $dir . "tools/highlight/highlight.pack.js";

		if($hilightStyle=="") {
				$hilightStyle="far";
		}
		$hilightcss = '<link rel="stylesheet" href="' . $css . $hilightStyle . '.css" />';
		$hilightcss .= '<link rel="stylesheet" href="source/plugin/zxsq_code/css/code.css" />';

		$hilightjs = '<script src="' . $js . '" charset="utf-8"></script>';
		$hilightrun = <<<EOT
 <script>
hljs.initHighlightingOnLoad();
</script>
EOT;
		return $hilightcss . $hilightjs . $hilightrun;

	}
	
	function run($texcode) {
		global $_G;
		@extract($_G['cache']['plugin']['zxsq_code']);
		if($codeHeight<=0) {
			$codeHeight=4000;
		}
		$texcode = preg_replace('/\[(.+?)\]/', "[zxsq-anti-bbcode-\${1}]", $texcode);
		include template('zxsq_code:code');
		return trim($code);
	}
}
