<?php
// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'account');

// ######################### REQUIRE BACK-END ############################
require_once( realpath('include/global.php') );

// ########################### INIT VARIABLES ############################
$navigation = null;

// ########################### IDENTIFY USER #############################
loggedInOrReturn();
setDefaultForLoggedinUser();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
$renderer -> addJavascriptToHeader('skin/js/account.js', THIS_SCRIPT);

// UserProfile
$profile = new UserProfile();

$website -> input -> clean_array_gpc('p', array('action' => TYPE_NOHTML));

if ( isset($website -> GPC['action']) AND ($website -> GPC['action'] == 'updateprofile') ) {
    $profileContent = $profile -> updateCurrentProfile();
}
else {
    $profileContent = $profile -> getCurrentProfile();
}

$renderer -> loadTemplate(THIS_SCRIPT . '.htm');
    $renderer -> addCustonStyle(array('script' => 'skin/css/account.css'), THIS_SCRIPT);
    $renderer -> setVariable('global_navbar', $navigation);
    $renderer -> setVariable(THIS_SCRIPT . '_content', $profileContent);
print_output($renderer -> renderTemplate());
