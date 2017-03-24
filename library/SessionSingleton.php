<?php

class SessionSingleton {
    public static $session;

    public static function startSession(){
        self::$session = Model::get('session');
    }

    public static function setSession($session){
        self::$session = $session;
    }

    public static function getSession(){
        if(!self::$session)
            self::$session = Model::get('session');
        return self::$session;
    }

    public static function endSession(){
        session_destroy();
    }
}