<?php


class Cache{
    public static function get($key){
        if(defined('CACHE_ENABLED') && CACHE_ENABLED){
            return apc_fetch($key);
        }
        return false;
    }

    public static function set($key,$value,$ttl = 7200){
        if(defined('CACHE_ENABLED') && CACHE_ENABLED){
            apc_store($key, $value, $ttl);
        }
        return false;
    }

    public static function remove($key){
        if(defined('CACHE_ENABLED') && CACHE_ENABLED){
            apc_delete($key);
        }
    }
}
