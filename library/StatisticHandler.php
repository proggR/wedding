<?php

class StatisticHandler{


    /**
     *
     * @param <type> $uid
     * @param <type> $action
     * @param <type> $etype
     * @param <type> $eid
     * @param <type> $success
     * @param <type> $time
     * @return <type> 
     */
    public static function addStatistic($action,$resource_id,$success = 1,$uid = false){        
        Model::get('statistic')->addStatistic($action, $resource_id, $success,$uid);
        return true;
    }
}