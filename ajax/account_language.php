<?php
// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'ajax_account_language');

// ######################### REQUIRE BACK-END ############################
chdir('../');
require_once( realpath('include/global.php') );

// ########################### INIT VARIABLES ############################

// ########################### IDENTIFY USER #############################
loggedInOrReturn();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
$website -> input -> clean_array_gpc('p', array(
                                              'langCode'   => TYPE_NOHTML,
                                              'langAction' => TYPE_NOHTML,
                                              'langStatus' => TYPE_BOOL,
                                          )
                                    );

$return = array(
              'error'   => null,
              'message' => null,
          );

if ( $website -> userinfo ) {
    if ( strlen($website -> userinfo['translation']) ) {
        $currSelection = unserialize( stripslashes($website -> userinfo['translation']) );
    }
    else {
        $currSelection = array();
    }
    
    $currSelection[$website -> GPC['langCode']][$website -> GPC['langAction']] = $website -> GPC['langStatus'];
    $website -> userinfo['translation'] = addslashes(serialize($currSelection));

    $update = array(
                  'translation' => serialize($currSelection),
              );
    $result = $website -> db -> updateRow($update, 'users', '`id` = ' . $website -> userinfo['id']);

    if ( $result === false ) {
        $return['message'] = $website -> user_lang['profile']['error_profile_update'];
    }
    
    $return['error'] = $result;
}
else {
    $return['error']   = true;
    $return['message'] = $website -> user_lang['global']['no_account_data'];
}

echo json_encode($return);