<?php
// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'logout');

// ######################### REQUIRE BACK-END ############################
require_once( realpath('include/global.php') );

// ########################### IDENTIFY USER #############################
loggedInOrReturn();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
$security = new LoginLogout();
$security -> logout();