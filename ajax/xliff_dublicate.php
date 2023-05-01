<?php
// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'ajax_xliff_dublicate');

// ######################### REQUIRE BACK-END ############################
chdir('../');
require_once( realpath('include/global.php') );

// ########################### INIT VARIABLES ############################

// ########################### IDENTIFY USER #############################
loggedInOrReturn();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
$website -> input -> clean_array_gpc('p', array('action' => TYPE_NOHTML));

$result = array(
              'message' => '',
              'data'    => null,
          );

if ( $website -> GPC['action'] == 'serach' ) {
    $info = new Xliff_Information();
    $result['data'] = $info -> searchDublicateStringsInTranslation();
}
elseif ( $website -> GPC['action'] == 'delete' ) {
    $website -> input -> clean_array_gpc('r', array(
                                                  'size' => TYPE_UINT,
                                                  'ids'  => TYPE_NOHTML,
                                              )
                                        );
    if ( strlen($website -> GPC['ids']) ) {
        if ( strlen($website -> GPC['ids']) == $website -> GPC['size'] ) {
            $info = new Xliff_Information();
            $result['data']    = $info -> deleteDublicateStringsInTranslation($website -> GPC['ids']);
            $result['message'] = $website -> user_lang['translation']['ajax_translation_delete_ids_complete'];
        }
        else {
            $result['message'] = $website -> user_lang['translation']['ajax_translation_ids_not_complete'];
        }
    }
    else {
        $result['message'] = $website -> user_lang['translation']['ajax_translation_no_ids_for_delete'];
    }
}
else {
    $result['message'] = $website -> user_lang['language']['unknown_action'];
}

echo json_encode($result);