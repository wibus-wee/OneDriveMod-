<?php

class IndexController {
    private $url_path;
    private $name;
    private $path;
    private $items;
    private $time;

    public function __construct() {
        //获取路径和文件名
        $paths = explode('/', rawurldecode($_GET['path']));
        if (substr($_SERVER['REQUEST_URI'], -1) != '/') {
            $this->name = array_pop($paths);
        }
        $this->url_path = get_absolute_path(join('/', $paths));
        $this->path = get_absolute_path(config('onedrive_root') . $this->url_path);
        //获取文件夹下所有元素
        $this->items = $this->items($this->path);
    }


    public function index() {
        //是否404
        $this->is404();

        $this->is_password();

        header("Expires:-1");
        header("Cache-Control:no_cache");
        header("Pragma:no-cache");

        if (!empty($this->name)) {//file
            return $this->file();
        } else {//dir
            return $this->dir();
        }
    }

    //判断是否加密
    public function is_password() {
        if (empty($this->items['.password'])) {
            return false;
        } else {
            $this->items['.password']['path'] = get_absolute_path($this->path) . '.password';
        }

        $password = $this->get_content($this->items['.password']);
        list($password) = explode("\n", $password);
        $password = trim($password);
        unset($this->items['.password']);
        if (!empty($password) && $password == $_COOKIE[md5($this->path)]) {
            return true;
        }

        $this->password($password);

    }

    public function password($password) {
        if (!empty($_POST['password']) && $password == $_POST['password']) {
            setcookie(md5($this->path), $_POST['password']);
            return true;
        }
        $navs = $this->navs();
        echo view::load('password')->with('navs', $navs);
        exit();
    }

    //文件
    public function file() {
        $item = $this->items[$this->name];
        if ($item['folder']) {//是文件夹
            $url = $_SERVER['REQUEST_URI'] . '/';
        } elseif (!is_null($_GET['t'])) {//缩略图
            $url = Helper::toCdn($this->thumbnail($item));
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST' || !is_null($_GET['s'])) {
            return $this->show($item);
        } else {//返回下载链接
            $url = Helper::toCdn($item['downloadUrl']);
        }
        header('Location: ' . $url);
    }


    //文件夹
    public function dir() {
        $root = get_absolute_path(dirname($_SERVER['SCRIPT_NAME'])) . config('root_path');
        $navs = $this->navs();
        if ($this->items['index.html']) {
            $this->items['index.html']['path'] = get_absolute_path($this->path) . 'index.html';
            $index = $this->get_content($this->items['index.html']);
            header('Content-type: text/html');
            echo $index;
            exit();
        }

        if ($this->items['README.md']) {
            $this->items['README.md']['path'] = get_absolute_path($this->path) . 'README.md';
            $readme = $this->get_content($this->items['README.md']);
            $Parsedown = new Parsedown();
            $readme = $Parsedown->text($readme);
            //不在列表中展示
            unset($this->items['README.md']);
        }

        if ($this->items['HEAD.md']) {
            $this->items['HEAD.md']['path'] = get_absolute_path($this->path) . 'HEAD.md';
            $head = self::get_content($this->items['HEAD.md']);
            $Parsedown = new Parsedown();
            $head = $Parsedown->text($head);
            //不在列表中展示
            unset($this->items['HEAD.md']);
        }
        return view::load('list')->with('title', 'index of ' . urldecode($this->url_path))
            ->with('navs', $navs)
            ->with('path', join("/", array_map("rawurlencode", explode("/", $this->url_path))))
            ->with('root', $root)
            ->with('items', $this->items)
            ->with('head', $head)
            ->with('readme', $readme);
    }

    public function show($item) {
        $root = get_absolute_path(dirname($_SERVER['SCRIPT_NAME'])) . (config('root_path') ? '?/' : '');
        $ext = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));
        $data['title'] = $item['name'];
        $data['navs'] = $this->navs();
        $data['item'] = $item;
        $data['ext'] = $ext;
        $data['item']['path'] = get_absolute_path($this->path) . $this->name;
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $uri = onedrive::urlencode(get_absolute_path($this->url_path . '/' . $this->name));
        $data['url'] = $http_type . $_SERVER['HTTP_HOST'] . $root . $uri;
        $data['item']['downloadUrl'] = Helper::toCdn($data['item']['downloadUrl']);
        $item['downloadUrl'] = Helper::toCdn($item['downloadUrl']);
        $show = config('show');
        foreach ($show as $n => $exts) {
            if (in_array($ext, $exts)) {
                return view::load('show/' . $n)->with($data);
            }
        }

        header('Location: ' . $item['downloadUrl']);
    }

    //缩略图
    public function thumbnail($item) {
        if (!empty($_GET['t'])) {
            list($width, $height) = explode('|', $_GET['t']);
        } else {
            //800 176 96
            $width = $height = 800;
        }
        $item['thumb'] = onedrive::thumbnail($this->path . $this->name);
        list($item['thumb'], $tmp) = explode('&width=', $item['thumb']);
        $item['thumb'] .= strpos($item['thumb'], '?') ? '&' : '?';
        return $item['thumb'] . "width={$width}&height={$height}";
    }

    //文件夹下元素
    public function items($path, $fetch = false) {
        //是否有缓存
        $items = json_decode(cache('dir_' . $this->path), true);
        if (is_null($items) || !is_array($items) || $fetch) {
            $items = onedrive::dir($path);
            if (is_array($items)) {
                $this->time = TIME;
                cache('dir_' . $path, json_encode($items), config('cache_expire_time'));
            }
            return $items;
        }
        return $items;
    }

    public function navs() {
        $root = get_absolute_path(dirname($_SERVER['SCRIPT_NAME'])) . config('root_path');
        $navs['/'] = get_absolute_path($root . '/');
        foreach (explode('/', $this->url_path) as $v) {
            if (empty($v)) {
                continue;
            }
            $navs[rawurldecode($v)] = end($navs) . $v . '/';
        }
        if (!empty($this->name)) {
            $navs[$this->name] = end($navs) . urlencode($this->name);
        }

        return $navs;
    }

    public static function get_content($item) {
        $path = $item['path'];

        list($time, $content) = cache('content_' . $path);
        if (is_null($content) /*|| (TIME - $time) > config('cache_expire_time')*/) {
            $resp = fetch::get($item['downloadUrl']);
            if ($resp->http_code == 200) {
                $content = $resp->content;
                cache('content_' . $path, $content, config('cache_expire_time'));
            }
        }
        return $content;
    }

    //时候404
    public function is404() {
        if (!empty($this->items[$this->name]) || (empty($this->name) && is_array($this->items))) {
            return false;
        }

        cache('404_' . $this->path . $this->name, true);

        http_response_code(404);
        view::load('404')->show();
        die();
    }

    public function __destruct() {
        if (!function_exists("fastcgi_finish_request")) {
            return;
        }
        //后台刷新缓存
//		if((TIME - $this->time) > config('cache_refresh_time')){
//			fastcgi_finish_request();
//			$items = onedrive::dir($this->path);
//			if(is_array($items)){
//				cache('dir_'.$this->path, $items);
//			}
//		}
    }
}
