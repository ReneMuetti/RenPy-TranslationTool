<?php
// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'ajax_search');

// ######################### REQUIRE BACK-END ############################
chdir('../');
require_once( realpath('include/global.php') );

// ########################### INIT VARIABLES ############################
$method = 'r';
$result = array(
              'message' => '',
              'error'   => true,
              'data'    => null,
          );

// ########################### IDENTIFY USER #############################
loggedInOrReturn();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
$website -> input -> clean_array_gpc($method, array('action' => TYPE_NOHTML));

if ( $website -> GPC['action'] == 'find_translations' ) {
    $website -> input -> clean_array_gpc($method, array(
                                                      'language' => TYPE_UINT,
                                                      'pattern'  => TYPE_NOHTML,
                                                  )
                                        );

    $translation = new Translation();
    $result['error'] = false;
    $result['data']  = $translation -> getTranslationFromSearchPatterns($website -> GPC['pattern'], $website -> GPC['language']);
}
else {
    $result['message'] = $website -> user_lang['language']['unknown_action'];
}

echo json_encode($result);