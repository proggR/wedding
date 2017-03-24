<?php
/* 
 * Settings File
 * Note: must have a separate config.php file in the same directory with the following:
 *  Definitions:
 *  -'DB_HOST'
 *  -'DB_USER'
 *  -'DB_PASS'
 *  -'DATABASE'
 *  -'SITE'
 *  -'REL_PATH'
 *  -'DOCUMENT_ROOT'
 *  -'SYSTEM_EMAIL'
 *  -'SYSTEM_DISPLAY'
 *  -'SOCIAL_ON'
 *  -'PAYPAL_EMAIL'
 *  -'READ_ONLY'
 *  -'MAINTENANCE'
 *  -'ALLOW_DONATIONS'
 */
if(@!stream_resolve_include_path("config.php")){
?>
<!DOCTYPE html>
<html>
<head>
    <title>Missing Configuration</title>
    <style type="text/css">
        body{
            font-family:'Helvetica','Verdana','sans-serif';
            text-align: center;
        }
        h1{
            font-weight:100;
        }
        p{
            border-radius: 10px;
            border:1px solid #EED3D7;
            padding:20px;
            background-color: #F2DEDE;
            color:#B94A48;
        }
    </style>
</head>
<body>
    <h1>Missing <strong>configuration</strong>.</h1>
    <p>
        This site has not been configured yet. This message will continue until a config file is generated.
    </p>
    </body>
</html>
<?php
    exit;
}
require_once 'config.php';

/**
 * Configuration settings
 */

define('CACHE_ENABLED',false);

define('MODEL_PATH',DOCUMENT_ROOT.'/app/models/');
define('MODEL_FACTORY',DOCUMENT_ROOT.'/library/ModelFactory.class.php');
define('MODEL_MODEL',DOCUMENT_ROOT.'/library/Model.class.php');



define('VALIDATOR',DOCUMENT_ROOT.'/library/components/Validator.php');
/**
 * Data Paths
 */
define('DATA_ADAPTER',DOCUMENT_ROOT.'/data/DatabaseAdapter.class.php');
define('DATA_MAPPER',DOCUMENT_ROOT.'/data/DataMapper.class.php');

define('COMPONENT_FACTORY',DOCUMENT_ROOT.'/library/ComponentFactory.class.php');
?>
