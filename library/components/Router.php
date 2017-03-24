<?php

class Router{

	

    public function __construct(){
        $this->rules = array();
    }

    public function getPage(){
        return $this->page?$this->page:1;
    }

    public function data($key){
        if(!isset($this->$key))return false;
        return $this->$key;
    }

	/**
	* Accepts the key/index of path you want to look at, the expected value (0 for any), the variable this value should be applied to, and the conditions that must be met to have this assigned
	* @param $key : index of path rule applies to
	* @param $exp_value : expected value (0 for any, array of values accepted)
	* @param $var : variable you want the value assigned to (ex: 'controller', 'action', 'id', etc)
	* @param $cond : array of conditions that must be true (ex: array(0=>'artist') means the first index's value must be artist)
	*/
	public function addRule($key,$exp_value,$var,$cond){
		if(is_array($exp_value)){
			foreach($exp_value as $val){
				$this->rules[$key][$val][] = array('variable'=>$var,'conditions'=>$cond);
			}
		}else{
			$this->rules[$key][$exp_value][] = array('variable'=>$var,'conditions'=>$cond);
		}
	}

	/**
	* Creates the route from the rules. If 'controller' and 'action' don't get set by the rules, they default to index.
	* Note: it may be worth having an error occur if the 'controller' isn't set and the path has values.
    * Note, divide router from request object (which is actually what gets built and returned here)
    * @todo: clean this up. This is nasty. And also... did I really reimplement $_GET after line 49? 
    * I think I thought it wouldn't work for some reason but can't see any reason why it wouldn't. 
    * This cleanup will take place wheneber the Router gets split from the Request and combined with the get/post logic from the controller.
    * All data, whether from post, get, or Routing rules, will be accessible via the data() function. 
	*/
	public static function createRoute(&$router){
        if(!$rtr = Cache::get('router_'.$_SERVER["REQUEST_URI"])) {
		    $valid_route = true;
                //$path = explode('/',substr($_SERVER["REQUEST_URI"],9));
                //echo $_SERVER["REQUEST_URI"];
            $trim_length = defined('REL_PATH')?strlen(REL_PATH)+1:1;
            if($pos = strpos($_SERVER["REQUEST_URI"],'?')){
                $length = $pos-$trim_length;
                $request = substr($_SERVER["REQUEST_URI"],$trim_length,$length);

                $param_str = substr($_SERVER["REQUEST_URI"],$pos+1);
                $param_split = explode("&",$param_str);
                foreach($param_split as $p){
                    $val = explode("=",$p);
                    $params[$val[0]] = isset($val[1])?$val[1]:true;
                }
            }else{
                $request = substr($_SERVER["REQUEST_URI"],$trim_length);
            }
            $router->current_route = $request;
            if($request){
            $path = explode('/',$request);
            }else{
                $path = false;
            }
            if($path && count($path)){
                foreach($path as $key=>$item){
                    $item = Validator::cleanString(urldecode($item));
                    if($item){
                        if(array_key_exists($key,$router->rules)){
                                if(array_key_exists($item,$router->rules[$key])){
                                        foreach($router->rules[$key][$item] as $rule){
                                                $cond_met = true;
                                                if($rule["conditions"] && is_array($rule["conditions"])){
                                                        foreach($rule["conditions"] as $ckey=>$condition){
                                                                if($path[$ckey] !== $condition){
                                                                        $cond_met = false;
                                                                }
                                                        }
                                                }
                                                if($cond_met){
                                                $router->{$rule["variable"]} = $item;
                                                break;
                                                }
                                        }
                                        if(!$cond_met){
                                            $valid_route = false;//error: no rule setup for route
                                        }
                                }elseif(array_key_exists(0,$router->rules[$key])){
                                        foreach($router->rules[$key][0] as $rule){
                                                $cond_met = true;
                                                if($rule["conditions"] && is_array($rule["conditions"])){
                                                        foreach($rule["conditions"] as $ckey=>$condition){
                                                                if($path[$ckey] !== $condition){
                                                                        $cond_met = false;
                                                                }
                                                        }
                                                }
                                                if($cond_met){
                                                        $router->{$rule["variable"]} = $item;
                                                        break;
                                                }
                                        }
                                        if(!$cond_met){
                                            $valid_route = false;//error: no rule setup for route
                                        }
                                }else{
                                        $valid_route = false;//error: no rule setup for route
                                }
                        }else{
                                $valid_route = false;//error: no rule setup for route
                        }
                    }
                }
            }
    		if(isset($params) && is_array($params)){
                if(isset($params['controller']) || isset($params['action'])){
                    $valid_route = false;                
                }
                foreach($params as $key=>$param){
                    $key = Validator::cleanString($key);
                    if(!$key) continue;
                    $router->$key = Validator::cleanString(urldecode($param));
                }
    		}
    		if(!isset($router->page)){
    			$router->page = 1;
    		}
    		if(!isset($router->controller)){
    			$router->controller = 'index';
    		}

    		if(!isset($router->action)){
    			$router->action = 'index';
    		}
    		if(!isset($router->api)){
    			$router->api = false;
    		}
    		if(!isset($router->ajax)){
    			$router->ajax = false;
    		}

            $router->valid_route = $valid_route;
            Cache::set('router_'.$_SERVER["REQUEST_URI"],$router);

        } else {
            $router = $rtr;
        }
    }

}