<?php
/**
 *  Handles any messages occurring during processing.
 * @todo make Session messages better. Make use of session model if possible. 
 */
@session_start();
class MessageHandler{

    private static $errors;
    private static $successes;
    private static $achievements;

    public static function addError($msg, $display = true, $session = false){        
        if($session){
            if(!isset($_SESSION['errors']))$_SESSION['errors'] = array();
            $_SESSION['errors'][] = array('message'=>$msg,'display'=>$display);
        }else{
            if(!self::$errors) self::$errors = array();
            self::$errors[] = array('message'=>$msg,'display'=>$display);
        }
    }

    public static function getErrors($session = false){
        if($session && isset($_SESSION['errors'])){
            self::$errors = is_array(self::$errors)?array_merge($_SESSION['errors'], self::$errors):$_SESSION['errors'];
            unset($_SESSION['errors']);
        }
        return (isset(self::$errors))?self::$errors:false;
    }

    public static function jsonErrors($session = false, $single = false){
        if($session && isset($_SESSION['errors'])){
            self::$errors = is_array(self::$errors)?array_merge($_SESSION['errors'], self::$errors):$_SESSION['errors'];
            unset($_SESSION['errors']);
        }
        $errors = array();
        if(isset(self::$errors)){
            foreach(self::$errors as $error){
                $errors[] = $error['message'];
            }
        }
        if($single) return $errors[0];
        return $errors;
    }    

    public static function addSuccess($msg, $display = true, $session = false){        
        if($session){
            if(!isset($_SESSION['successes']))$_SESSION['successes'] = array();
            $_SESSION['successes'][] = array('message'=>$msg,'display'=>$display);
        }else{
            if(!self::$successes) self::$successes = array();
            self::$successes[] = array('message'=>$msg,'display'=>$display);
        }

    }

    public static function getSuccesses($session = false){        
        if($session && isset($_SESSION['successes'])){
            if(is_array(self::$successes)){
                self::$successes = array_merge($_SESSION['successes'], self::$successes);
            }else{
                self::$successes = $_SESSION['successes'];
            }
            unset($_SESSION['successes']);
        }
        return (isset(self::$successes)?self::$successes:false);
    }

    public static function addAchievement($title, $display = true, $session = false){        
        if($session){
            if(!isset($_SESSION['achievements']))$_SESSION['achievements'] = array();
            $_SESSION['achievements'][] = array('message'=>"Achievement Earned - {$title}",'display'=>$display);
        }else{
            if(!self::$achievements) self::$achievements = array();
            self::$achievements[] = array('message'=>"Achievement Earned - {$title}",'display'=>$display);
        }
    }

    public static function getAchievements($session = false){
        if($session && isset($_SESSION['achievements'])){
            self::$achievements = is_array(self::$achievements)?array_merge($_SESSION['achievements'], self::$achievements):$_SESSION['achievements'];
            unset($_SESSION['achievements']);
        }
        return (isset(self::$achievements))?self::$achievements:false;
    }
}
?>
