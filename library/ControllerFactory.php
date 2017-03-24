<?php
/* @todo check stuff
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class ControllerFactory{

    static function getController($type,$action){
        switch($type){
         case 'album':
             require_once 'controllers/album_controller.php';
             return new album_controller($action);
             break;
         case 'artist':
             require_once 'controllers/artists_controller.php';
             return new artists_controller($action);
             break;
         case 'fan':
             require_once 'controllers/fans_controller.php';
             return new fans_controller($action);
             break;
         case 'index':
             require_once 'controllers/index_controller.php';
             return new index_controller($action);
             break;
         case 'projects':
             require_once 'controllers/projects_controller.php';
             return new projects_controller($action);
             break;
         case 'settings':
             require_once 'controllers/settings_controller.php';
             return new settings_controller($action);
             break;
         case 'individual':
         case 'user':
             require_once 'controllers/users_controller.php';
             return new users_controller($action);
             break;
         case 'view':
             require_once 'controllers/view_controller.php';
             return new view_controller($action);
             break;
         default:
             return false;
             break;
        }
    }
}

?>
