<?php
// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'ajax_details');

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


if ( $website -> GPC['action'] == 'load_file_blocks' ) {
    $website -> input -> clean_array_gpc($method, array('language' => TYPE_UINT));
    
    if ( $website -> GPC['language'] >= 1 ) {
        $info = new Xliff_Information();
        $result['data']  = $info -> getAllGameFilesFormGeneralAsBlock();
        $result['error'] = false;
    }
    else {
        $result['message'] = $website -> user_lang['translation']['details_placeholder'];
    }
}
elseif ( $website -> GPC['action'] == 'load_file_details' ) {
    $website -> input -> clean_array_gpc($method, array(
                                                      'language' => TYPE_UINT,
                                                      'filename' => TYPE_NOHTML,
                                                  )
                                        );
    if ( $website -> GPC['language'] >= 1 ) {
        $info = new Xliff_Information();
        $result['data']  = $info -> getInformationForDetailsByFile($website -> GPC['filename'], $website -> GPC['language']);
        $result['error'] = false;
    }
    else {
        $result['message'] = $website -> user_lang['translation']['details_ajax_error'];
    }
}
elseif ( $website -> GPC['action'] == 'load_file_translations' ) {
    $website -> input -> clean_array_gpc($method, array(
                                                      'language' => TYPE_UINT,
                                                      'filename' => TYPE_NOHTML,
                                                  )
                                        );
    
    if ( $website -> GPC['language'] >= 1 ) {
        $translation = new Translation();
        
        $result['error'] = false;
        $result['data']  = $translation -> getTranslationFromFileByLanguage($website -> GPC['filename'], $website -> GPC['language']);
    }
    else {
        $result['message'] = $website -> user_lang['translation']['details_ajax_error'];
    }
}
elseif ( $website -> GPC['action'] == 'update_translation_inline' ) {
    $website -> input -> clean_array_gpc($method, array(
                                                      'translation' => TYPE_NOHTML,
                                                      'uuid'        => TYPE_NOHTML,
                                                      'original'    => TYPE_UINT,
                                                      'data-id'     => TYPE_UINT,
                                                  )
                                        );
    if ( strlen($website -> GPC['uuid']) AND ($website -> GPC['original'] >= 1) AND ($website -> GPC['data-id'] >= 1) ) {
        $translator = new Translation();
        $return = $translator -> updateSingleTranslation($website -> GPC['translation'], $website -> GPC['uuid'], $website -> GPC['original'], $website -> GPC['data-id']);
        
        $result['error']   = $return['error'];
        $result['data']    = $return['data'];
        $result['message'] = $return['message'];
    }
    else {
        $result['message'] = $website -> user_lang['translation']['details_ajax_error'];
    }
}
else {
    $result['message'] = $website -> user_lang['language']['unknown_action'];
}

echo json_encode($result);