<?php
$config_data['Host']['protocol'] = 'https';
$config_data['Host']['host']     = '** current base-URL **';
$config_data['Host']['script']   = THIS_SCRIPT . '.php';

$config_data['Misc']['path']              = '** physical path to directory **';
$config_data['Misc']['baseurl']           = $config_data['Host']['protocol'] . '://' . $config_data['Host']['host'] . '/';
$config_data['Misc']['charset']           = 'UTF-8';
$config_data['Misc']['showtemplatenames'] = FALSE;
$config_data['Misc']['showtemplatetree']  = TRUE;
