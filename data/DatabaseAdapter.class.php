<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class DatabaseAdapter{

    public function  __construct() {
        $this->id = 0;
        $this->conn = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DATABASE);
        mysqli_set_charset('utf8',$this->conn);
        if(!$this->conn)
                $this->error = mysqli_error();
        $this->query = '';
        $this->ordb = false;
        $this->prep_query = false;
        $this->prev_query = false;
        $this->replace = array();
        $this->params = array();
        $this->result = array();
        $this->dont_wrap = array();

        // mysqli_select_db(DATABASE, $this->conn) or die($this->error = mysqli_error());
    }

    public function applyParams(){
        $replace_search = false;
        if(count($this->replace)){
            $num_params_expected = count($this->replace);
            $replace_search = true;
        }
        else
            $num_params_expected = substr_count($this->query,':?');

        if(count($this->params) != $num_params_expected){
            $this->error = UNEXPECTED_NUM_PARAMS;
            return false;
        }

        if($replace_search)
            $this->prep_query = str_replace($this->replace,$this->params,$this->query);
        elseif(!isset($num_params_expected) || !$num_params_expected){
            $this->prep_query = $this->query;
        }else{
            $tmp_query = str_replace(':?','%s',$this->query);
            $this->prep_query = vsprintf($tmp_query,$this->params);
        }

        return $this;
    }

    private function execute(){
        $this->result = array();
        $this->id = 0;
        if(!$this->prep_query){
            if(!$this->applyParams()){
                    $this->error = isset($this->error)?$this->error:INVALID_PARAMETERS;
                    return false;
            }
        }
        $this->prev_query = $this->prep_query;  
        $db_name = !$this->ordb?DATABASE:$this->ordb;
        mysqli_select_db($db_name);//redundant call to make insert work properly (already called in constructor)
        $result = mysqli_query($this->prep_query,$this->conn);
        $GLOBALS['num_queries']++;
        if(!$result){
            if(DEBUG){
                error_log("[QueryLog: No Results] : ".$this->prep_query);
            }
            $this->prep_query = false;
            return false;
        }
        if(is_resource($result)){
            while($row = mysqli_fetch_assoc($result)){
                $this->result[] = $row;
            }
        }else{
            $this->result = true;
        }        
        
        if(DEBUG){
            error_log("[QueryLog: Results] : ".$this->prep_query);
        }        
        $this->prep_query = false;        
        $this->id = mysqli_insert_id();
        return $this->result;
    }

    public function insertRaw(){
        echo "ADAPTER: INSERTING\n";
        $this->execute();
        if($this->id) return $this->id;
        return false;
    }

    public function getRow(){
        $this->execute();
        if($this->result) return $this->result[0];
        return false;
    }

    public function getAll(){
        $this->execute();
        if($this->result) return $this->result;
        return false;
    }

    public function getOne(){
        $this->execute();
        if($this->result){
            $keys = array_keys($this->result[0]);
            return $this->result[0][$keys[0]];
        }
        return false;
    }

    public function delete(){
        $this->execute();
        return mysqli_affected_rows($this->conn);
    }

    public function autoExec($type,$where='1=1'){
        $db_name = !$this->ordb?DATABASE:$this->ordb;
        if(!$columns = Cache::get("table_{$db_name}_{$this->query}")){
            $results = mysqli_query("SELECT column_name FROM information_schema.columns WHERE table_name='{$this->query}' AND table_schema='{$db_name}'",$this->conn);       
            $columns = array();
            while($row = mysqli_fetch_assoc($results)){
                if(!in_array($row['column_name'],$columns))
                    $columns[] = $row['column_name'];
            }
            Cache::set("table_{$db_name}_{$this->query}",$columns);
        }
        if(count($this->replace) != count($this->params)){
            return false;
        }
        foreach($this->replace as $key=>$replace){
            if(!in_array($replace,$columns) || $this->params[$key] === null){
                unset($this->replace[$key]);
                unset($this->params[$key]);
            }elseif($type == 'insert' || $type == 'update'){
                $this->replace[$key] = "`{$replace}`";
            }
        }
        $cols = $this->replace;
        $vals = $this->params;
        $table = "`{$db_name}`.`{$this->query}`";
        if($type == 'insert'){
            $this->query = "INSERT INTO :table (:columns) VALUES (:values)";
            $this->replace = array(':table',':columns',':values');
            $this->params = array($table,implode(",", $cols),  implode(",",$vals));
        } elseif($type == 'update'){
            $this->query = "UPDATE :table SET :values WHERE :where";
            $this->replace = array(':table',':values',':where');
            $values = array();
            foreach($cols as $key=>$col){
                $values[] = "{$col}={$vals[$key]}";
            }
            $this->params = array($table,implode(',', $values),$where);
        }

        $result = $this->execute();
        if(!$result){
            error_log("Error updating database. \n\t\tType:{$type} \n\t\tMySQLError:".mysqli_error($this->conn)."\n\tFailed MySQLQuery:".$this->prev_query);
        }
        if($result && $type=='insert'){
            return $this->id;
        }else{
            return $result;
        }
    }

    public function autoCommit($auto_commit = true){
        return mysqli_autocommit($this->conn,$auto_commit);
    }

    public function rollback(){
        return mysqli_rollback($this->conn);
    }

    public function commit(){
        return mysqli_commit($this->conn);
    }

    public function close(){
        mysqli_close($this->conn);
    }
    
    public function setString($string,$id = -1){
        if($id !== -1){
            $this->params[$id] = '\''.$string.'\'';
        }
        else{
            $this->params[] = '\''.$string.'\'';
        }
        return $this;
    }

    public function setInt($int, $id = -1){
        if($id !== -1){
            $this->params[$id] = $int;
        }
        else{
            $this->params[] = $int;
        }
        return $this;
    }

    public function clear(){
        $this->id = 0;
        $this->query = '';
        $this->ordb = false;
        $this->prep_query = false;
        $this->replace = array();
        $this->params = array();
        $this->result = array();
        $this->dont_wrap = array();
    }

}


?>
