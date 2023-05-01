<?php
// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'ajax_translation');

// ######################### REQUIRE BACK-END ############################
chdir('../');
require_once( realpath('include/global.php') );

// ########################### INIT VARIABLES ############################
$method = 'p';

// ########################### IDENTIFY USER #############################
loggedInOrReturn();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
$website -> input -> clean_array_gpc($method, array('action' => TYPE_NOHTML));

$return = array(
              'error'   => false,
              'message' => '',
              'data'    => '',
          );

$translate = new Translation();

if ( $website -> GPC['action'] == 'load_translation' ) {
    // find new emtpy string for translation
    $website -> input -> clean_array_gpc($method, array(
                                                      'lastUUID'  => TYPE_NOHTML,
                                                      'languages' => TYPE_NOHTML,
                                                      'shows'     => TYPE_NOHTML,
                                                      'common'    => TYPE_BOOL,
                                                  )
                                        );
    
    $return['data'] = $translate -> getNewStringForTranslation($website -> GPC['lastUUID'], $website -> GPC['languages'], $website -> GPC['shows'], $website -> GPC['common'] );

    if ( !is_array($return['data']) OR !count($return['data']) ) {
        // PHP-Error => view Log-File
        $return['error']   = true;
        $return['message'] = $website -> user_lang['translation']['ajax_error_data_failed'];
    }
    elseif ( isset($return['data']['done']) AND ($return['data']['done'] === true) ) {
        // translation is done
        $return['error']   = false;
        $return['message'] = $return['data']['message'];
    }
}
elseif ( $website -> GPC['action'] == 'save_translation' ) {
    // get static information
    $website -> input -> clean_array_gpc($method, array(
                                                      'lastUUID'  => TYPE_NOHTML,
                                                      'languages' => TYPE_NOHTML,
                                                      'allMD5'    => TYPE_BOOL,
                                                  )
                                        );
    if ( strlen($website -> GPC['languages']) ) {
        if ( strpos($website -> GPC['languages'], $translate -> getLanguageSepearor() ) ) {
            $languages = explode( $translate -> getLanguageSepearor(), $website -> GPC['languages'] );
            foreach( $languages AS $lang ) {
                $website -> input -> clean_array_gpc($method, array('destination-' . $lang => TYPE_NOHTML));
            }
        }
        else {
            $website -> input -> clean_array_gpc($method, array('destination-' . $website -> GPC['languages'] => TYPE_NOHTML));
        }
    }
    
    $return = $translate -> saveNewTranslation($website -> GPC);
}

echo json_encode($return);