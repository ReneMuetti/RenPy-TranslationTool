<?php
// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'login');

// ######################### REQUIRE BACK-END ############################
require_once( realpath('include/global.php') );

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
$website -> input -> clean_array_gpc('r', array('action' => TYPE_NOHTML, 'error' => TYPE_NOHTML));

if ($website -> userinfo) {
    header('Location: ' . $website -> baseurl . 'index.php');
}
else {
    if ( isset($website -> GPC['action']) AND ($website -> GPC['action'] == 'dologin') ) {
        $website -> input -> clean_array_gpc('p', array('username' => TYPE_NOHTML, 'password' => TYPE_NOHTML));
        
        if ( empty($website -> GPC['username']) OR empty($website -> GPC['password']) ) {
            $errormessage = 'Benutzername oder Passwort-Fehler!';
            $renderer -> loadTemplate('standard_error.htm');
            $output = $renderer -> renderTemplate();
            $renderer -> addContent(THIS_SCRIPT . '_content', $output);
        }
        
        $security = new LoginLogout();
        $security -> login($website -> GPC['username'], $website -> GPC['password']);
    }
    else {
        if ( isset($website -> GPC['error']) AND strlen($website -> GPC['error']) ) {
            $security  = new LoginLogout();
            $errors    = $security -> decodeErrors($website -> GPC['error']);
            $boxHeight = (count($errors) * 18 + 45) . 'px';
            $errors    = implode("\n        ", $errors);
            
            $renderer -> loadTemplate(THIS_SCRIPT . '_error.htm');
                $renderer -> setVariable('login_errors_list', $errors);
                $renderer -> setVariable('login_errors_box' , $boxHeight);
            $output = $renderer -> renderTemplate();
            $renderer -> addContent(THIS_SCRIPT . '_content', $output);
        }
        
        $renderer -> loadTemplate(THIS_SCRIPT . '_dialog.htm');
            $renderer -> addCustonStyle(array('script' => 'skin/css/login-register.css'), THIS_SCRIPT);
        $output = $renderer -> renderTemplate();
        $renderer -> addContent(THIS_SCRIPT . '_content', $output);
    }
    
    $renderer -> loadTemplate(THIS_SCRIPT . '.htm');
    print_output($renderer -> renderTemplate());
}
