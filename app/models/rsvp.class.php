<?php

require_once MODEL_MODEL;

class RSVP extends Model{

    public function __construct($user = ''){
        parent::__construct();
        $this->name = '';
        $this->phone = '';
        $this->names_attending = '';
        $this->dietary_notes = '';
        $this->song_suggestions = '';
        $this->tmestmp = time();
        $this->validation_rules = array(
            'name' => array('rule'=>'string,required','fieldname'=>'Fullname'),
            'phone' => array('rule'=>'string,required','fieldname'=>'Phone Number'),
            'names_attending' => array('rule'=>'string,required','fieldname'=>'Names of Attendees'),
            'dietary_notes' => array('rule'=>'string','fieldname'=>'Dietary Restrictions'),
            'song_suggestions' => array('rule'=>'string','fieldname'=>'Song Suggestions'),
        );        
    } 

    public function create(){        
        $this->id = null;
        $stmt = DataMapper::getStatement();
        $stmt->type = 'insert';
        $stmt->query = 'rsvps';
        $stmt->bind('name',$this->name,'string');
        $stmt->bind('phone',$this->phone,'string');
        $stmt->bind('names_attending',$this->names_attending,'string');
        $stmt->bind('dietary_notes',$this->dietary_notes,'string');
        $stmt->bind('song_suggestions',$this->song_suggestions,'string');
        $stmt->bind('tmestmp',$this->tmestmp,'int');

        if($r = DataMapper::mapResults($this, $stmt)){ 
            return $r;
        }
        return false;        
    }    

   
   
}