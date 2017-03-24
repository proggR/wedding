<?php

//require_once 'library/HTMLPurifier.auto.php';
// require_once "HTMLPurifier.auto.php";
require_once "HTMLPurifier.standalone.php";
class Validator {

    private static $valid = true;

    private $rules;
    private static $init = false;
    private static $purifier = false;

    public function __construct() {
        $this->rules = array();
        self::$valid = true;
        self::init();
    }

    private static function init(){
        if(!self::$init && (!isset(self::$purifier) || !self::$purifier)){
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'h2,p,ul,ol,li,strong,b,i,em,a[href|title],br');
            self::$purifier = new HTMLPurifier($config);            
            self::$init = true;
        }
    }

    public static function newField($post = '',$field ='Field',$rules=array()){
        return new ValidatorField($post,$field,$rules);
    }

    public function addRule($field, $fieldname, $rules) {
        $rules_array = explode(',', $rules);
        $this->rules[$field] = array('fieldname' => $fieldname, 'rules' => $rules_array);
    }

    public function invalid($message,$valid = false){
        self::staticInvalid($message, $valid);
    }
    
    public static function staticInvalid($message = false,$valid = false){        
        if($message){
            MessageHandler::addError($message);
        }
        /**
         * Makes it so you don't accidentally make it valid again if a previous rule has already decided it was invalid.
         */
        if(self::$valid){
            self::$valid = $valid;
        }
    }

    public function validate(&$model) {
        if (isset($model->validation_rules)) {
            $this->loadRules($model->validation_rules);
        }
        foreach ($model as $var => $value) {
            if (isset($this->rules[$var])) {
                foreach ($this->rules[$var]['rules'] as $rule) {
                    $model->$var = $this->clean($value, $rule, $this->rules[$var]['fieldname']);
                }
            } else {
                if ($var) {
                    $model->$var = $this->clean($value, 'string', ucwords($var));
                }
            }
        }

        return $model;
    }

    public function loadRules($rules) {
        if ($rules) {
            foreach ($rules as $var => $rule) {
                $vrules = explode(',', $rule['rule']);
                $this->rules[$var] = array('fieldname' => $rule['fieldname'], 'rules' => $vrules);
            }
        }
    }

    public function clean($value, $rule = 'string', $fieldname = '', $allow_array = true) {
        return self::staticClean($value, $rule, $fieldname, $allow_array);
    }

    public static function staticClean($value, $rule, $fieldname = '', $allow_array = true) {
        self::init();
        switch ($rule) {
            case 'longstring':
                return self::editor($value, $fieldname);
                break;
            case 'editor':                
                return self::editor($value, $fieldname);
                break;                
            case 'int':
                return self::int($value, $fieldname);
                break;
            case 'float':
                return self::cleanFloat($value, $fieldname);
                break;
            case 'array':
                if ($allow_array) {
                    return self::vArray($value, $fieldname);
                }
                return false;
                break;
            case 'email':
                return self::email($value, $fieldname);
                break;
            case 'url':
                return self::url($value, $fieldname);
                break;
            case 'nonzero':
                return self::nonzero($value, $fieldname);
                break;
            case 'datestring':
                return self::datestring($value, $fieldname);
                break;
            case 'datetime':
                return self::datetime($value, $fieldname);
                break;
            case 'required':
                return self::required($value, $fieldname);
                break;
            case 'html':
                return self::html($value, $fieldname);
                break;     
            case 'null':
                return null;
                break;                     
            case 'juststring': 
            case 'string':
            default:
                $linkify = $rule == 'juststring'?false:true;
                return self::editor($value, $fieldname,$linkify);
                break;
        }

    }

    public function isValid() {
        return self::$valid;
    }


    public static function cleanString($string, $fieldname = 'value') {
        return filter_var($string, FILTER_SANITIZE_STRING);
    }

    public static function cleanFloat($float, $fieldname = 'value') {
        return filter_var($float, FILTER_VALIDATE_FLOAT);
    }

    public static function vArray($array, $fieldname = 'value') {
        if ($array && is_array($array)) {
            $clean_array = array();
            foreach ($array as $key => $value) {
                $clean_array[self::cleanString($key)] = self::cleanString($value);
            }
            return $clean_array;
        }
        return false;
    }

    public static function string($string, $fieldname = 'value') {
        $string = trim($string);
        return filter_var($string, FILTER_SANITIZE_STRING);
    }

    public static function email($email, $fieldname = 'value') {
        if(!$email = filter_var($email, FILTER_VALIDATE_EMAIL)){
            self::staticInvalid('Invalid Email provided.');
        }
        return $email;
    }

    public static function url($url, $fieldname = 'value') {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    public function float($float, $fieldname = 'value') {
        if (($float = filter_var($float, FILTER_VALIDATE_FLOAT)) !== false) {
            return $float;
        }
        $this->invalid("Invalid data entered for {$fieldname}.");
    }

    public static function longstring($string, $fieldname = 'value') {
        $string = trim($string);
        return nl2br(filter_var($string, FILTER_SANITIZE_STRING));
    }


    /**
    * Should only EVER be used for emails. Nothing else should require being passed through unsanitized
    */
    public static function html($string, $fieldname = 'value') {
        return $string;
    }  

    public static function editor($string, $fieldname = 'value', $linkify = true) {
        if(is_array($string)) return "";
        if(!is_string($string)) return (int)$string;
        $string = trim($string);
        if($linkify){
            $string = self::preLinkify($string);
        }        
        $string = str_replace("&", ':and:', $string);        
        $string = self::$purifier->purify($string);       
        $string = str_replace(":and:", '&', $string);
        return $string;//nl2br(filter_var($string, FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_LOW));
    }    

    private static function preLinkify($string){
        $url_regex = '(http|https|ftp)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}'. 
                        '(:[a-zA-Z0-9]*)?\/?([a-zA-Z0-9\-\._\?\,\'\/\\\+&%\$#\=~])*[^\.\,\)\(\s]';
       // Only replace if it isn't already a Markdown style link
        return preg_replace(
            '/' . '(^|[^\[\(<"]\s*)' . '(' . $url_regex . ')' . '/',
            '$1[$2]($2)',
            $string
        );        
    }

    public static function int($int, $fieldname = 'value') {
        return (int) $int;
    }

    public static function datestring($date, $fieldname = 'date') {
        if(is_numeric($date) && (int)$date)
            return (int)$date;
        return strtotime($date);
    }

    public static function datetime($date, $fieldname = 'date') {
        return (int) $date;
    }

    public static function required($value, $fieldname = 'value') {
        if ($value == '' || $value == null || $value === 0 || !$value){
            $arg = false;//($fieldname && $fieldname? "{$fieldname} is a required field.":false);
            self::staticInvalid($arg);
        }else{

        }

        return $value;
    }

    public static function nonzero($value, $fieldname = 'value') {
        if ($value == 0) {
            self::staticInvalid(false);//"{$fieldname} must contain integer that is not zero.");
        }
        return $value;
    }
}


/**
 * @todo - flesh this out and use it in place of the array based rules 
 */
class ValidatorField{

    public $fieldname = 'Field';
    public $rules = array();
    public $post_value = '';
    public $message = 'Invalid data provided for :field';

    public function __construct($post = '',$field = 'Field',$rules = array(),$message = false){
        $this->fieldname = $field;
        $this->post_value = $post;
        $this->rules = $rules;
        if($message){
            $this->message = $message;
        }
    }

    public function addRule($rule){
        $this->rules[] = $rule;
    }

    public function getMessage(){
        return str_replace(':field', $this->fieldname, $this->message);
    }
}

