<?php
/**
 * Author: David
 */
class Helper{
    public static function toCdn($url){
        $config = config('@base');
        if(!isset($config['cdn_address'])){
            return $url;
        }
        $cdnHost = $config['cdn_address'];
        if($cdnHost === ''){
            return $url;
        }

        $arr = explode('/',$url);
        $full  = '';
        foreach ($arr as $index=>$p){
            if($index === 2){
                $p = 'sp.shax.vip';
            }
            $full .= $p.'/';
        }
        return $full;
    }
}