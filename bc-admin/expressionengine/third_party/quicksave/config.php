<?php

if (! defined('QS_NAME'))
{
	define('QS_NAME', 'QuickSave');
	define('QS_ID', 'quicksave');
	define('QS_VERSION',  '1.5');
	define('QS_DOCS', 'http://www.vayadesign.net/code/addon/quicksave');
}

$config['name'] = QS_NAME;
$config['version'] = QS_VERSION;
$config['nsm_addon_updater']['versions_xml']='http://www.vayadesign.net/addon_feed/quicksave';