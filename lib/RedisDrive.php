<?php
/**
 *  redisdrive.class.php
 * php redis 操作类
 **/
class RedisDrive{
    //键名
    public $key;
    //值
    public $value;
    //默认生存时间
    public $expire = 60*60; /*60*60*24*/
    //连接是否成功
    public $redis;
    //连接redis服务器ip
    public $ip = '127.0.0.1';
    //端口
    public $port = 6379;
    //密码
    private $password = '';
    //数据库
    public $dbindex = 10;//默认使用高阶数据库


    /**
     * 自动连接到redis缓存
     */
    public function __construct($redis_password =''){
        //判断php是否支持redis扩展
        if(extension_loaded('redis')){
            //实例化redis
            if($this->redis = new redis()){
                //ping连接
                if(!$this->ping()){
                    $this->redis = false;
                }else{
                    if(!empty($redis_password)){//连接通后的数据库选择和密码验证操作
                        $this->password=$redis_password;
                        $this->redis->auth($this->password);
                    }
                    $this->redis -> select($this->dbindex);
                }
            }else{
                $this->redis = false;
            }
        }else{
            $this->redis = false;
        }
    }

    /**
     * ping redis 的连通性
     */
    public function ping(){
        try{
            if($this->redis->connect($this->ip,$this->port)){
                return true;
            }else{
                return false;
            }
        }catch(\Exception $exception){
            die('请先开启redis服务');
        }

    }

    /**
     * 检测redis键是否存在
     */
    public function exists(){
        try{
            if($this->redis->exists($this->key)){
                return true;
            }else{
                return false;
            }
        }catch(\Exception $exception){
            return false;
        }

    }

    /**
     * 获取redis键的值
     */
    public function get(){
        try{
            if($this->exists()){
                return $this->redis->get($this->key);
            }else{
                return false;
            }
        }catch(\Exception $exception){
            return false;
        }

    }

    /**
     * 带生存时间写入key
     */
    public function setex(){
        return $this->redis->setex($this->key,$this->expire,(string)($this->value));
    }

    /**
     * 设置redis键值
     */
    public function set(){
        if($this->redis->set($this->key,(string)($this->value))){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取key生存时间
     */
    public function ttl(){
        return $this->redis->ttl($this->key);
    }

    /**
     *删除key
     */
    public function del(){
        return $this->redis->del($this->key);
    }

    /**
     * 清空所有数据
     */
    public function flushall(){
        return $this->redis->flushall();
    }

    /**
     * 获取所有key
     */
    public function keys(){
        return $this->redis->keys('*');
    }

    public function delItems($exclude=''){
        $keys=$this->keys();
        foreach ($keys as $v){
            if(strpos($v, $exclude)===false){
                $this->redis->del($v);
            }
        }
    }

}