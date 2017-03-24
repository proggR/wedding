<?php


class Debug{
	
	public static function step($msg){
		$s = Model::get('session');
		if($s->isAdmin()){
			MessageHandler::addError($msg);
		}
	}

	public static function log($msg){
		$s = Model::get('session');
		if($s->isAdmin()){
			MessageHandler::addError($msg);
		}
	}	

}