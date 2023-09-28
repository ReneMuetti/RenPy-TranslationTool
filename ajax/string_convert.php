<?php
// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'ajax_string_convert');

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

if ( $website -> GPC['action'] == 'convert' ) {
    $website -> input -> clean_array_gpc($method, array('translation' => TYPE_NOHTML));

    $translation = new HtmlTransform();
    $result['error'] = false;
    $result['data']  = $translation -> convertCodeToHtml($website -> GPC['translation'], true);
}
else {
    $result['message'] = $website -> user_lang['language']['unknown_action'];
}

echo json_encode($result);