<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'Model.class.php';

class ModelFactory{

    private static $models;

    static function getModel($type,$var=false,$autoload=false){
        if(!self::$models){            
            if(!CACHE_ENABLED || !$models = Cache::get('models')){
                $models = array();
                if ($handle = opendir(MODEL_PATH)) {
                    while (false !== ($file = readdir($handle))) {
                            $split = explode('.',$file);
                            $models[strtolower($split[0])] = array('file'=>$file,'class'=>$split[0]);
                    }
                    closedir($handle);                    
                }
                if(CACHE_ENABLED){
                    Cache::set('models',$models);
                }
            }
            self::$models = $models;
        }
        if($type){           

            if(array_key_exists($type, self::$models)){
                require_once MODEL_PATH.self::$models[$type]['file'];
                if($var){
                    if(!is_array($var)){
                        $var = array($var);
                    }
                    $r = new ReflectionClass(self::$models[$type]['class']);
                    return $r->newInstanceArgs($var);
                }else{
                    return new self::$models[$type]['class'];
                }
            }
        }
        return false;
    }
}

?>
