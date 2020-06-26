<?php
error_reporting(E_ALL & ~E_NOTICE);
date_default_timezone_set('PRC');
define('TIME', time());
!defined('ROOT') && define('ROOT', str_replace("\\", "/", dirname(__FILE__)) . '/');

//__autoload方法
function i_autoload($className) {
	if (is_int(strripos($className, '..'))) {
		return;
	}
	$file = ROOT . 'lib/' . $className . '.php';
	if (file_exists($file)) {
		include $file;
	}
}
spl_autoload_register('i_autoload');

!defined('FILE_FLAGS') && define('FILE_FLAGS', LOCK_EX);
/**
 * config('name');
 * config('name@file');
 * config('@file');
 */
if (!function_exists('config')) {
	!defined('CONFIG_PATH') && define('CONFIG_PATH', ROOT . 'config/');
	function config($key) {
		static $configs = array();
		list($key, $file) = explode('@', $key, 2);
		$file = empty($file) ? 'base' : $file;

		$file_name = CONFIG_PATH . $file . '.php';
		//读取配置
		if (empty($configs[$file]) AND file_exists($file_name)) {
			$configs[$file] = @include $file_name;
		}

		if (func_num_args() === 2) {
			$value = func_get_arg(1);
			//写入配置
			if (!empty($key)) {
				$configs[$file] = (array) $configs[$file];
				if (is_null($value)) {
					unset($configs[$file][$key]);
				} else {
					$configs[$file][$key] = $value;
				}
			} else {
				if (is_null($value)) {
					return unlink($file_name);
				} else {
					$configs[$file] = $value;
				}

			}
			file_put_contents($file_name, "<?php return " . var_export($configs[$file], true) . ";", FILE_FLAGS);
		} else {
			//返回结果
			if (!empty($key)) {
				return $configs[$file][$key];
			}

			return $configs[$file];
		}
	}
}
/**
 * config('name');
 * config('name@file');
 * config('@file');
 */
if (!function_exists('cache') && file_exists('config/base.php')) {
	!defined('CACHE_PATH') && define('CACHE_PATH', ROOT . 'cache/');
	function cache($key, $value = null, $ttl=null) {
		$redis=new RedisDrive(config('redis_password'));
        if(!$redis){
            return false;
        }
		$redis->key=$key.md5($key);//为了可读性，做一次转换
		if (is_null($value)) {
            if($redis->exists()){
                return (string)($redis->get());
            }
		} else {
		    if(!$ttl){//when ttl is null
                $redis->value=(string)$value;
                $redis->set();
                return true;
            }
		    $redis->value=(string)$value;
		    $redis->expire=$ttl;
		    $redis->setex();
			return true;
		}
	}
}

//获取token专用函数
if(!function_exists('getToken')){
    function getToken(){
        $token=cache('access_token');
        if($token && $token!=''){
            return $token;
        }
        $refresh_token=cache('refresh_token');
        if($refresh_token){
            $token = onedrive::get_token($refresh_token);
            cache('access_token',$token['access_token'], $token['expires_in']-200);
            cache('refresh_token',$token['refresh_token']);
            return $token['access_token'];
        }

    }
}




if (!function_exists('db')) {
	function db($table) {
		return db::table($table);
	}
}

if(!function_exists('dd')){
    function dd($dump=null){
        if($dump){
            var_dump($dump);
        }
        die();
    }
}

if (!function_exists('view')) {
	function view($file, $set = null) {
		return view::load($file, $set = null);
	}
}

if (!function_exists('_')) {
	function _($str) {
		return htmlspecialchars($str);
	}
}

if (!function_exists('e')) {
	function e($str) {
		echo $str;
	}
}

function get_absolute_path($path) {
    $path = str_replace(array('/', '\\', '//'), '/', $path);
    $parts = array_filter(explode('/', $path), 'strlen');
    $absolutes = array();
    foreach ($parts as $part) {
        if ('.' == $part) continue;
        if ('..' == $part) {
            array_pop($absolutes);
        } else {
            $absolutes[] = $part;
        }
    }
    return str_replace('//','/','/'.implode('/', $absolutes).'/');
}

!defined('CONTROLLER_PATH') && define('CONTROLLER_PATH', ROOT.'controller/');
onedrive::$client_id = config('client_id');
onedrive::$client_secret = config('client_secret');
onedrive::$redirect_uri = config('redirect_uri');