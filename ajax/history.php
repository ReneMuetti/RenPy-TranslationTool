<?php
// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'ajax_history');

// ######################### REQUIRE BACK-END ############################
chdir('../');
require_once( realpath('include/global.php') );

// ########################### INIT VARIABLES ############################
$method = 'p';
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

if ( $website -> GPC['action'] == 'load_history' ) {
    $website -> input -> clean_array_gpc($method, array(
                                                      'user'       => TYPE_UINT,        // Account-ID
                                                      'language'   => TYPE_NOHTML,      // ISO-LanguageCode
                                                      'method'     => TYPE_NOHTML,      // Methode
                                                      'counter'    => TYPE_UINT,        // Result-Count
                                                      'pager'      => TYPE_UINT,        // selected Page
                                                      'uuid'       => TYPE_NOHTML,      // (part of) UUID
                                                      'old-string' => TYPE_NOHTML,      // (part of) old String
                                                      'new-string' => TYPE_NOHTML,      // (part of) new String
                                                      'start-id'   => TYPE_UINT,        // last start id
                                                      'end-id'     => TYPE_UINT,        // last end id
                                                  )
                                        );
    
    $history = new Translation_History();
    $data = $history -> getDataFromHistoryByFilter($website -> GPC['user'], $website -> GPC['language'], $website -> GPC['method'],
                                                   $website -> GPC['counter'], $website -> GPC['pager'], $website -> GPC['uuid'],
                                                   $website -> GPC['old-string'], $website -> GPC['new-string'],
                                                   $website -> GPC['start-id'], $website -> GPC['end-id'] );
    
    $result['error'] = $data['error'];
    $result['data']  = $data['data'];
}
else {
    $result['message'] = $website -> user_lang['language']['unknown_action'];
}
echo json_encode($result);