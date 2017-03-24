<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once MODEL_FACTORY;
require_once DATA_ADAPTER;
require_once VALIDATOR;
class DataMapper{
    /**
     *
     * @param <type> $model The model you are querying and populating
     * @param <type> $request The model function you are calling
     * @param <type> $params An array of parameters that you wish to pass to model function
     * @return <type>
     */
    static $db = false;
    static function mapResults($model,$params){       

        if(!self::$db){
            self::$db = new DatabaseAdapter;
        }
        $class = strtolower(get_class($model));
        if(!$params) return false;        
        if($params instanceof  Statement){
            if(!$params->is_valid) return false;
            self::$db->query = $params->query;
            self::$db->replace = $params->replace;
            self::$db->params = $params->params;
            self::$db->ordb = $params->database;
            $type = $params->type;
            $where = isset($params->where)?$params->where:false;
        }elseif(is_array($params)){
            self::$db->query = $params['query'];
            self::$db->replace = $params['replace'];
            self::$db->params = $params['params'];
            $type = isset($params['type'])?$params['type']:'all';
            $where = isset($params['where'])?$params['where']:false;
        }else return false;
        if(in_array($type,array('insert','update','delete'))) {
            if(defined('READ_ONLY') && READ_ONLY){
                MessageHandler::addError(READ_ONLY_ERROR);
                return false;
            }
        }        
        switch($type){
            case 'insert':            
                $data = self::$db->autoExec($type);
                break;            
            case 'insert_raw':                           
                $data = self::$db->insertRaw();
                break;
            case 'update':
                $data = $where?self::$db->autoExec($type,$where):false;
                break;
            case 'delete':
                $results = call_user_func(array(self::$db,'delete'));
                self::$db->clear();
                return $results;
                break;
            default:                
                $results = call_user_func(array(self::$db,'get'.ucwords($type)));
                switch($type){
                    case 'all':                        
                        $data = null;
                        if($results){
                            $data = array();                            
                            foreach($results as $result){
                                unset($inst);
                                $inst = Model::get($class);
                                $data[] = $inst->fromResults($result);
                            }
                        }
                        break;
                    case 'one':
                        $data = $results;
                        break;
                    case 'row':
                    default:
                        $data = $results?$model->fromResults($results):false;
                        break;
                }
                break;
        }
        self::$db->clear();   
        return $data;
    }

    /**
     * Loads model with data from form (or from any array really)
     * @param <type> $model the model you want loaded
     * @param <type> $post the values from $_POST
     * @param <type> $validate flag which decides if model will be validated while being loaded
     * @return <type>
     */
    static function fromForm($model,$post,$validate = false){
        if(!$post) return false;
        if($validate){
            $v = new Validator();
            foreach($post as $key=>$value){
                if(isset($model->validation_rules[$key])){
                    $rules = explode(',',$model->validation_rules[$key]['rule']);
                    foreach($rules as $rule){
                        $model->$key = $v->clean($value,$rule,$model->validation_rules[$key]['fieldname']);
                    }
                }else{
                    $model->$key = $v->clean($value,'string',ucwords($key));
                }
            }
           return $v->isValid()?$model:false;
        }else{
            foreach($post as $key=>$value){
                $model->$key = Validator::cleanString($value);
            }
        }
        return $model;
    }

    public static function getStatement(){
        if(self::$db == false){
            self::$db = new DatabaseAdapter;
        }
        return new Statement(self::$db);
    }
}

class Statement{
    public function __construct($db=false){
        $this->db = $db;
        $this->replace = array();
        $this->params = array();
        $this->type = 'all';
        $this->database = false;
        $this->is_valid = true;
        //set_exception_handler(array($this,'handleException'));
    }

    public function bind($r,$p,$type_string = "string"){          
        $types = explode(',',$type_string);
        if($types){
            foreach($types as $type){
                $p = Validator::staticClean($p,$type);
            }
        }
        switch(true){
            case in_array('unix_date',$types):
            case in_array('unix_time',$types):
            case in_array('int',$types):
                $this->params[] = (int)$p;
                break;
            case in_array('array',$types):
                if(is_array($p)){
                    $list = array();
                    foreach($p as $item){
                        $list[] = "'".mysqli_real_escape_string(trim($item),$this->db->conn)."'";
                    }
                    $this->params[] = implode(",",$list);            
                }else{
                    $this->params[] = "'".mysqli_real_escape_string($p,$this->db->conn)."'";
                }                
                break;
            case in_array('nonzero',$types):
            case in_array('required',$types):
                $this->params[] = "'".mysqli_real_escape_string($p,$this->db->conn)."'";
                if(!$p || $p == ""){                    
                    $this->is_valid = false;
                    return false;
                }
                break;
            case in_array('null',$types):
                $this->params[] = "NULL";
                break;
            default:
                $this->params[] = "'".mysqli_real_escape_string($p,$this->db->conn)."'";
                break;
        }
        $this->replace[] = $r;
        return true;
    }

    public function unbind($key){
        $idx = in_array($key, $this->replace);
        if($idx === false) return false;  
        unset($this->replace[$idx]);
        unset($this->params[$idx]);
    }

    public function unbindAll(){
        unset($this->replace);
        unset($this->params);
        $this->replace = array();
        $this->params = array();
    }

    public function bindModel($model){
        if(!$model) return false;
        $response = true;
        $update_types = array('insert','update');
        foreach($model as $key=>$value){
            $field = !in_array($this->type,$update_types)?':'.$key:$key;
            if(in_array($key, array('validation_rules','validation_rulesets','validation_ignore'))
                || in_array($key, $model->validation_ignore)) continue;
            if(isset($model->validation_rules[$key])){
                if(!$this->bind($field,$value,$model->validation_rules[$key]["rule"])){
                    $response = false;
                    MessageHandler::addError($model->validation_rules[$key]["fieldname"]." is a required field.");
                }
            }elseif(!$model->$key instanceof Model){
                $this->bind($key,$value,'string');
            }
        }
        return $response;
    }

    public function query($q = false){
        if($q){
            $this->query = $q;
        }

        return $this->query;
    }

    public function type($t = false){
        if($t){
            $this->type = $t;
        }

        return $this->type;
    }

    public function database($d = false){
        if($d){
            $this->database = $d;
        }

        return $this->database;
    }

    public function handleException($e){
        throw new Exception($e->getMessage());
    }
}

class Form{

    function __construct(){

    }
}
