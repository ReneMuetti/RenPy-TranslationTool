<?php
// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'ajax_html_convert');

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

if ( $website -> GPC['action'] == 'convert' ) {
    $website -> input -> clean_array_gpc($method, array('string' => TYPE_NOHTML));

    $convert = new HtmlTransform();

    $result['error'] = false;
    $result['data']['html'] = $convert -> convertCodeToHtml($website -> GPC['string'], true, true);
    $result['data']['text'] = $convert -> replaceQuoteInTranslationString($website -> GPC['string']);
}
else {
    $result['message'] = $website -> user_lang['language']['unknown_action'];
}

echo json_encode($result);