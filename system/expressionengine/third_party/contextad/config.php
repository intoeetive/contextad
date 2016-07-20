<?php

if ( ! defined('CONTEXTAD_ADDON_NAME'))
{
	define('CONTEXTAD_ADDON_NAME',         'Context Ads');
	define('CONTEXTAD_ADDON_VERSION',      '0.1.2');
}

$config['name']=CONTEXTAD_ADDON_NAME;
$config['version']=CONTEXTAD_ADDON_VERSION;

$config['nsm_addon_updater']['versions_xml']='http://www.intoeetive.com/index.php/update.rss/290';