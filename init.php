<?php

@set_include_path(implode(PATH_SEPARATOR, array(
	dirname(__FILE__),
    dirname(__FILE__) . "/library",
    // dirname(__FILE__) . "/app/controllers",
    dirname(__FILE__) . "/app/models",
    // dirname(__FILE__) . "/app/templates",
    // dirname(__FILE__) . "/app/templates/modules",
    get_include_path(),
)));

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors',1);
require_once dirname(__FILE__) .'/app_settings/settings.php';
$GLOBALS['num_queries'] = 0;
$GLOBALS['request_start'] = microtime(true);
//require_once 'app_settings/bootstrap.php';

// require_once 'library/phpthumb/ThumbLib.inc.php';
require_once MODEL_FACTORY;
require_once DATA_MAPPER;
require_once COMPONENT_FACTORY;

ComponentFactory::initComponent('cache');
// ComponentFactory::initComponent('paginator');
ComponentFactory::initComponent('validator');
// ComponentFactory::initComponent('router');