<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: class_core.php 33982 2013-09-12 06:36:35Z hypowang $
 */

error_reporting(E_ALL);         // 规定不同的错误级别报告，E_ALL：报告所有的错误级别

define('IN_DISCUZ', true);
define('DISCUZ_ROOT', substr(dirname(__FILE__), 0, -12));           // 退到根目录
define('DISCUZ_CORE_DEBUG', false);             // 代码调试模式 false
define('DISCUZ_TABLE_EXTENDABLE', false);

set_exception_handler(array('core', 'handleException'));            // 设置用户定义的异常处理函数：

if(DISCUZ_CORE_DEBUG) {
	set_error_handler(array('core', 'handleError'));        // 类实例异常捕获，捕获core内的异常并调用handleError处理程序处理
	register_shutdown_function(array('core', 'handleShutdown'));    // 在php中止时(脚本执行完成或者exit()后)会执行类函数：core.handleShutdown
}
// 兼容性处理：spl_autoload_register函数，php7以上已废弃 __autoload函数
if(function_exists('spl_autoload_register')) {
	spl_autoload_register(array('core', 'autoload'));           // 自动加载未定义类功能的的重要方法,当加载的类或方法未在该模块里，那么会自动调用该回调处理函数
}
//else {
//	function __autoload($class) {
//		return core::autoload($class);
//	}
//}

C::creatapp();      // 调用core类的静态方法 creatapp()

class core
{
	private static $_tables;            // 存储使用过的表，类型 array
	private static $_imports;           // 记录已经import模块的数组，key:模块路径   value:true/false      104/115row
	private static $_app;
	private static $_memory;        // 创建内存对象，用于存储对象的内存存储

	public static function app() {
		return self::$_app;
	}

	public static function creatapp() {
		if(!is_object(self::$_app)) {
			self::$_app = discuz_application::instance();       // discuz_application类不在当前模块，调用自动加载未定义功能函数 sql_autoload_register 的autolaod 函数
		}
		return self::$_app;
	}
    // 被 /source/function/function_core.php     719行  调用     表名 $name = common_syscache   表类型  表格兼容？
	public static function t($name) {
		return self::_make_obj($name, 'table', DISCUZ_TABLE_EXTENDABLE);            // 表生成对象
	}

	public static function m($name) {
		$args = array();
		if(func_num_args() > 1) {
			$args = func_get_args();
			unset($args[0]);
		}
		return self::_make_obj($name, 'model', true, $args);            // 模块生成对象
	}
    // 根据名称生成对象？
	protected static function _make_obj($name, $type, $extendable = false, $p = array()) {
		$pluginid = null;   // __autoload
		if($name[0] === '#') {
			list(, $pluginid, $name) = explode('#', $name);     //  把字符串打散为数组(explode解释)，然后把该数组中的值赋给一些变量(list解释)
		}
		$cname = $type.'_'.$name;
		if(!isset(self::$_tables[$cname])) {        // 如果该表[table_common_syscache]未设置
			if(!class_exists($cname, false)) {              // 检查类是否已定义,第二个参数是：是否默认调用 __autoload。
				self::import(($pluginid ? 'plugin/'.$pluginid : 'class').'/'.$type.'/'.$name);      // 未定义的话进入这里
			}
			if($extendable) {       // 这里我只看到 模块调用时为true,也就是本脚本的  63row
				self::$_tables[$cname] = new discuz_container();        //  ??
				switch (count($p)) {
					case 0:	self::$_tables[$cname]->obj = new $cname();break;
					case 1:	self::$_tables[$cname]->obj = new $cname($p[1]);break;
					case 2:	self::$_tables[$cname]->obj = new $cname($p[1], $p[2]);break;
					case 3:	self::$_tables[$cname]->obj = new $cname($p[1], $p[2], $p[3]);break;
					case 4:	self::$_tables[$cname]->obj = new $cname($p[1], $p[2], $p[3], $p[4]);break;
					case 5:	self::$_tables[$cname]->obj = new $cname($p[1], $p[2], $p[3], $p[4], $p[5]);break;
					default: $ref = new ReflectionClass($cname);self::$_tables[$cname]->obj = $ref->newInstanceArgs($p);unset($ref);break;
				}
			} else {
				self::$_tables[$cname] = new $cname();      // $cnmae = ‌table_common_syscache      实例化对应的类，然后将对象保存到 self::$_tables的数组内 以便下次直接使用
			}
		}
		return self::$_tables[$cname];      // 将实例化的对象返回
	}

	public static function memory() {
		if(!self::$_memory) {
			self::$_memory = new discuz_memory();           // 创建内存存储
			self::$_memory->init(self::app()->config['memory']);
		}
		return self::$_memory;
	}
    // 自动导入 php 类模块   如：   class/table/syscache   ->   class/table/table_syscache.php
	public static function import($name, $folder = '', $force = true) {
		$key = $folder.$name;
		if(!isset(self::$_imports[$key])) {         // 判断是否原先已经调用过了..
			$path = DISCUZ_ROOT.'/source/'.$folder;         //   root + /source/ + $folder
			if(strpos($name, '/') !== false) {
				$pre = basename(dirname($name));
				$filename = dirname($name).'/'.$pre.'_'.basename($name).'.php';
			} else {
				$filename = $name.'.php';
			}                       //  folder: c    if name: class/a    $filename: class/class_a.php                if $name: class/a/b      $filename: class/a/a_b.php

			if(is_file($path.'/'.$filename)) {
				include $path.'/'.$filename;
				self::$_imports[$key] = true;

				return true;
			} elseif(!$force) {
				return false;
			} else {
				throw new Exception('Oops! System file lost: '.$filename);
			}
		}
		return true;
	}

	public static function handleException($exception) {
		discuz_error::exception_error($exception);
	}


	public static function handleError($errno, $errstr, $errfile, $errline) {
		if($errno & DISCUZ_CORE_DEBUG) {
			discuz_error::system_error($errstr, false, true, false);
		}
	}

	public static function handleShutdown() {
		if(($error = error_get_last()) && $error['type'] & DISCUZ_CORE_DEBUG) {
			discuz_error::system_error($error['message'], false, true, false);
		}
	}

	public static function autoload($class) {
		$class = strtolower($class);            // 类名-小写
		if(strpos($class, '_') !== false) {         // 查看类名是否存在 _   ;存在则：
			list($folder) = explode('_', $class);           //  把字符串打散为数组
			$file = 'class/'.$folder.'/'.substr($class, strlen($folder) + 1);       //  class/数组下标1的值/数组下标2的值
		} else {
			$file = 'class/'.$class;        // class/类名
		}           //  a_b ->    class/a/b        a ->  class/a

		try {

			self::import($file);
			return true;

		} catch (Exception $exc) {

			$trace = $exc->getTrace();
			foreach ($trace as $log) {
				if(empty($log['class']) && $log['function'] == 'class_exists') {
					return false;
				}
			}
			discuz_error::exception_error($exc);
		}
	}

	public static function analysisStart($name){
		$key = 'other';
		if($name[0] === '#') {
			list(, $key, $name) = explode('#', $name);
		}
		if(!isset($_ENV['analysis'])) {
			$_ENV['analysis'] = array();
		}
		if(!isset($_ENV['analysis'][$key])) {
			$_ENV['analysis'][$key] = array();
			$_ENV['analysis'][$key]['sum'] = 0;
		}
		$_ENV['analysis'][$key][$name]['start'] = microtime(TRUE);
		$_ENV['analysis'][$key][$name]['start_memory_get_usage'] = memory_get_usage();
		$_ENV['analysis'][$key][$name]['start_memory_get_real_usage'] = memory_get_usage(true);
		$_ENV['analysis'][$key][$name]['start_memory_get_peak_usage'] = memory_get_peak_usage();
		$_ENV['analysis'][$key][$name]['start_memory_get_peak_real_usage'] = memory_get_peak_usage(true);
	}

	public static function analysisStop($name) {
		$key = 'other';
		if($name[0] === '#') {
			list(, $key, $name) = explode('#', $name);
		}
		if(isset($_ENV['analysis'][$key][$name]['start'])) {
			$diff = round((microtime(TRUE) - $_ENV['analysis'][$key][$name]['start']) * 1000, 5);
			$_ENV['analysis'][$key][$name]['time'] = $diff;
			$_ENV['analysis'][$key]['sum'] = $_ENV['analysis'][$key]['sum'] + $diff;
			unset($_ENV['analysis'][$key][$name]['start']);
			$_ENV['analysis'][$key][$name]['stop_memory_get_usage'] = memory_get_usage();
			$_ENV['analysis'][$key][$name]['stop_memory_get_real_usage'] = memory_get_usage(true);
			$_ENV['analysis'][$key][$name]['stop_memory_get_peak_usage'] = memory_get_peak_usage();
			$_ENV['analysis'][$key][$name]['stop_memory_get_peak_real_usage'] = memory_get_peak_usage(true);
		}
		return $_ENV['analysis'][$key][$name];
	}
}

class C extends core {}     // C 继承 core
class DB extends discuz_database {}     // DB 继承  discuz_database

?>