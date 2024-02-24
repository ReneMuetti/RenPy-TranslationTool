<?php
$config_data['Host']['protocol'] = 'https';
$config_data['Host']['host']     = '** current base-URL **';
$config_data['Host']['script']   = THIS_SCRIPT . '.php';

$config_data['Mail']['charset']  = 'utf-8';
$config_data['Mail']['host']     = '** current mailhost **';
$config_data['Mail']['smtpauth'] = TRUE;
$config_data['Mail']['username'] = '** username **';
$config_data['Mail']['address']  = '** current sender address **';
$config_data['Mail']['password'] = '** password **';
$config_data['Mail']['port']     = '** port **';
$config_data['Mail']['secure']   = FALSE;
$config_data['Mail']['protocol'] = '** encryption-method **';

$config_data['Misc']['path']              = '** physical path to directory **';
$config_data['Misc']['log_path']          = $config_data['Misc']['path'] . '/var/log';
$config_data['Misc']['baseurl']           = $config_data['Host']['protocol'] . '://' . $config_data['Host']['host'] . '/';
$config_data['Misc']['charset']           = 'UTF-8';
$config_data['Misc']['showtemplatenames'] = FALSE;
$config_data['Misc']['showtemplatetree']  = TRUE;
