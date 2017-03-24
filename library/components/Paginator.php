<?php
/* 
 * Paginator. Static class used to easily handle pagination data in the app.
 */

class Paginator{

    private static $records = array();

    /**
     * Accepts the query, replace and params used in the model and replaces the
     * requested fields with COUNT(*) to get the total number of records
     * for that query. Works regardless of LIMIT (will return total count based
     * on WHERE, ignoring any LIMIT claus you have added)
     * @param <type> $key : an array index used to set and fetch the number of results
     * @param <type> $query : query from Model
     * @param <type> $replace : replace from Model
     * @param <type> $params : params from Model
     */
    public static function addPagination($key, $query, $replace, $params){
        $select_index = strpos($query,'SELECT');
        $select_index += 6;
        $from_index = strpos($query,'FROM');
        $from_index = $from_index - $select_index;
        $fields_to_replace = substr($query,$select_index,$from_index);
        $p_query = str_replace($fields_to_replace, ' COUNT(*) ', $query);
        $limit_index = strpos($p_query,' LIMIT');
        $p_query = substr($p_query,0,$limit_index);
        $model = Model::get('model');
        Paginator::$records[$key] = DataMapper::mapResults($model, $model->returnArray($p_query, $replace, $params, 'one'));
    }

    public static function getPagination($key){
        if(!isset(Paginator::$records[$key])){
            return false;
        }

        $limit = 3;

        $pages = ceil(Paginator::$records[$key] / $limit);
        return $pages;
    }

}

?>
