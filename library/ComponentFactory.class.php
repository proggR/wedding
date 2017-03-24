<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once 'components/Cache.php';

class ComponentFactory{

    private static $components;

    static function getComponent($type,$var=false){
        $dir = dirname(__FILE__).'/components/';
        if(!self::$components){
            self::prepareList($dir);
        }
        if($type){
            if(array_key_exists($type, self::$components)){
                require_once $dir.self::$components[$type]['file'];
                if($var){
                    $r = new ReflectionClass(self::$components[$type]['class']);
                    return $r->newInstanceArgs(array($var));
                }else{
                    return new self::$components[$type]['class'];
                }
            }
        }
        return false;
    }
    
    static function initComponent($type,$var=false){
        $dir = dirname(__FILE__).'/components/';
        if(!self::$components){
            self::prepareList($dir);
        }
        if($type){
            if(array_key_exists($type, self::$components)){
                require_once $dir.self::$components[$type]['file'];
            }
        }
        return false;
    }

    private static function prepareList($dir){
        
        if(!CACHE_ENABLED || !$components = Cache::get('components')){
            $components = array();
            if ($handle = opendir($dir)) {
                while (false !== ($file = readdir($handle))) {
                        $split = explode('.',$file);
                        $components[strtolower($split[0])] = array('file'=>$file,'class'=>$split[0]);
                }
                closedir($handle);
            }
            if(CACHE_ENABLED){
                Cache::set('components',$components);
            }
        }
        self::$components = $components;
    }
}

?>
