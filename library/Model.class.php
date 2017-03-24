<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

//require_once 'data/DatabaseAdapter.class.php';
class Model{

    /**
    * @todo
    * Just realized the below three fields *NEED* to be made private
    * update bind process and add getters for them
    * the reason being someone could create a form and POST a validation_rules field that's an empty array and validate 
    * whatever the hell they want
    * temporary fix, fromForm has been updated to skip these fields but they should still be made private, probably using _validation_rules
    */
        
    public function  __construct($adapter = true) {
        $this->validation_rules = array();
        $this->validation_rulesets = array();
        $this->validation_ignore = array();
        return true;
    }


    public static function get($type,$var=false,$autoload=false){
        return ModelFactory::getModel($type,$var,$autoload);
    }

    public function applyRuleset($setname, $overwrite = false){
        if(isset($this->validation_rulesets[$setname])){
            if($overwrite)$this->validation_rules = array();
            foreach($this->validation_rulesets[$setname] as $field=>$rules){
                if($field == 'dependent_set') $this->applyRuleset($rules);
                elseif($field == 'dependent_sets'){
                    foreach($rules as $set){
                        $this->applyRuleset($set);
                    }
                }else $this->validation_rules[$field] = $rules;
            }
            return true;
        }
        return false;
    }

    public function validate(){
        $v = new Validator();
        if($v->validate($this)){
            return $this;
        }else{
            return false;
        }
    }

    protected function checkDBConnection(){
        if(!$this->db)
            $this->db = new DatabaseAdapter();
        if(isset($this->db->error))
            echo $this->db->error;
    }
    
    public function fromForm($post,$validate = false){
        /**
         * @todo: Fix all this shit. Its bad. Make field objects and a form helper that handle all this shit for you easier.
         * @todo: listen to angry brandon above. he was not happy
         */
        if(!$post) return false;
        if(!$validate){
            foreach($post as $key=>$value){
                $this->$key = Validator::cleanString($value);
            }
            return $this;            
        }
        $v = ComponentFactory::getComponent('validator');//new Validator();
        foreach($post as $key=>$value){
            if(in_array($key, array('validation_rules','validation_rulesets','validation_ignore'))) {
                error_log("[BREACH ATTEMPT] Some dickhead at {$_SERVER['REMOTE_ADDR']} is trying to POST a form that overrides validates fields.");
                continue;
            }
            if(isset($this->validation_rules[$key])){                    
                $rules = explode(',',$this->validation_rules[$key]['rule']);
                foreach($rules as $rule){
                    if(in_array($rule,array('nonzero','required'))){
                        $this->$key = $v->clean($this->$key,$rule,$this->validation_rules[$key]['fieldname']);
                    }else{
                        $this->$key = $v->clean($value,$rule,$this->validation_rules[$key]['fieldname']);
                    }

                }
            }else{
                $this->$key = $v->clean($value,'string',ucwords($key));
            }
        }

        /**
        * To future Brandon, so you don't delete it without thinking about it,
        * this is to ensure that any fields specified as being required are existed.
        * Example Case:
        * Someone isn't using the form to submit and has excluded required fields from POST.
        * The above check will not notice that a field was ommitted. This just does due diligence
        * to ensure that only proper requests are being made.
        */
        if(isset($this->validation_rules)){
            foreach($this->validation_rules as $field=>$frules){
                $rules = explode(',',$frules['rule']);
                foreach($rules as $rule){
                    if(in_array($rule, array('required','nonzero')) && (!isset($this->$field) || !$this->$field || $this->$field == "")){
                        $v->invalid($frules["fieldname"].' is required.',false);
                    }
                }
            }
        }
       return $v->isValid()?$this:false;
        
    }

    protected function processWith($with){
        $where = array();
        foreach($with as $search=>$value){
            $operand = "=";
            if($value === true){
                $operand = "IS NOT NULL AND {$search} != '0' AND {$search} != ''";
                $value = "";
            }if($value === true){
                $operand = "IS NULL";
                $value = "";
            }elseif(strpos($value,"=") !== false){
                substr_replace($value, "", 0,1);
            }elseif(strpos($value,">") !== false){
                substr_replace($value, "", 0,1);
                $operand = ">";
            }elseif(strpos($value,"<") !== false){
                substr_replace($value, "", 0,1);
                $operand = "<";
            }
            $where[] = "{$search} {$operand} {$value}";
        }  
        return $where;      
    }    

    protected function fn($name,$vars){
        if(is_scalar($vars)) $vars = array($vars);
        
        return call_user_func_array(array($this,$name),$vars);        
    }

    protected function fld($name,$value = false,$require_val = false){
        if(!isset($this->$name)) return false;            

        if($value) $this->$name = $value;

        if($require_val && (!$this->$name || trim($this->$name) == '')) return false;

        return $this->$name;
    }

    public function fromResults($result){
        if(!$result) return false;

        foreach($result as $key=>$value){
            $this->$key = stripslashes($value);
        }
        return $this;
    }

    public function getForUpdate(){
        $this_array = get_object_vars($this);
        $replace = array_keys($this_array);
        $params = array_values($this_array);

        return array($replace,$params);
    }

    public function returnArray($query,$replace,$params,$type,$where=false){
        return array('query'=>$query,'replace'=>$replace,'params'=>$params,'type'=>strtolower($type),'where'=>$where);
    }

    public function toArray(){
        return (array)$this;
    }

    public function handleException($e) {
        throw new Exception($e->getMessage());
        return false;
    }

    public function  __destruct() {
        //$this->db->close();
    }
}


class ModelField{
    
}
?>
