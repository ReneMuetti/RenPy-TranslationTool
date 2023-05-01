<?php
// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'ajax_language');

// ######################### REQUIRE BACK-END ############################
chdir('../');
require_once( realpath('include/global.php') );

// ########################### INIT VARIABLES ############################

// ########################### IDENTIFY USER #############################
loggedInOrReturn();
checkIfUserIsAdmin();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
$website -> input -> clean_array_gpc('p', array(
                                              'action'  => TYPE_NOHTML,
                                              'lngcode' => TYPE_NOHTML,   // Methods for Accounts
                                          )
                                    );

$result = array(
              'error' => false,
              'data'  => ''
          );

if ( $website -> GPC['action'] == 'newlanguage' ) {
    if ( (strlen($website -> GPC['lngcode']) >= 2) AND (strlen($website -> GPC['lngcode']) <= 3) ) {
        $checkImage = realpath('skin/images/flags/4x3/' . $website -> GPC['lngcode'] . '.svg');
        if ( is_file($checkImage) ) {
            $language = new Languages();
            $return = $language -> insertNewLanguage($website -> GPC['lngcode']);
            
            if ( $return['error'] == false ) {
                // Insert-Error
                $result['error'] = true;
                $result['data']  = $website -> user_lang['language']['insert_error'];
            }
            else {
                // Insert success
                $result['error'] = false;
                $result['data']  = $return['block'];
            }
        }
        else {
            $result['error'] = true;
            $result['data']  = $website -> user_lang['language']['code_unknown'];
        }
    }
    else {
        $result['error'] = true;
        $result['data']  = $website -> user_lang['language']['code_error'];
    }
}
else {
    $result['error'] = true;
    $result['data']  = $website -> user_lang['language']['unknown_action'];
}

echo json_encode($result);