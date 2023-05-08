<?php
// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'index');

// ######################### REQUIRE BACK-END ############################
require_once( realpath('include/global.php') );

// ########################### INIT VARIABLES ############################
$navigation = '';
$content = '';

// ########################### IDENTIFY USER #############################
loggedInOrReturn();
setDefaultForLoggedinUser();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
$website -> input -> clean_array_gpc('g', array('action' => TYPE_NOHTML));


if ( isset($website -> GPC['action']) ) {
    if ( empty($website -> GPC['action']) OR !strlen($website -> GPC['action']) ) {
        $info = new Xliff_Information();
        $content = $info -> getGlobalTranslationStatus();
    }
    elseif ( $website -> GPC['action'] == 'status' ) {
        $info = new Xliff_Information();
        // selected langaues from current user
        $content  = $info -> getCurrentTranslationStatus(true);
        // languages not selected by the current user
        $content .= $info -> getCurrentTranslationStatus(false);
    }
    elseif ( $website -> GPC['action'] == 'translate' ) {
        $form = new Translation();
        $content = $form -> getAjaxTranslationForm();
    }
    elseif ( $website -> GPC['action'] == 'details' ) {
        $langs = new Languages();
        $list  = $langs -> getLanguageByID();

        $info   = new Xliff_Information();
        $filter = $info -> getLanguagesFromCurrentUser();

        $options = array();
        $options[] = '<option value="-1">' . $website -> user_lang['global']['option_actions_select'] . '</option>';
        foreach( $list AS $langID => $langCode ) {
            if ( $langCode != 'ru' ) {
                if ( $website -> userinfo['admin'] == 'yes' OR (is_array($filter) AND in_array($langCode, $filter) ) ) {
                    $options[] = '<option value="' . $langID . '">' . $website -> user_lang['languages'][$langCode] . '</option>';
                }
            }
        }

        $renderer -> loadTemplate('details' . DS . 'overview.htm');
            $renderer -> setVariable('select_options', implode("\n            ", $options));
        $content = $renderer -> renderTemplate();
    }
    elseif ( $website -> GPC['action'] == 'history' ) {
        $history = new Translation_History();
        $content = $history -> getHistoryTable();
    }
    elseif ( $website -> GPC['action'] == 'search' ) {
        $search  = new Xliff_Information();
        $content = $search -> getSearchForm();
    }
    else {
        $content = $website -> user_lang['global']['unkonwn_action'];
    }
}
else {
    $content = $website -> user_lang['global']['unkonwn_action'];
}


$renderer -> loadTemplate(THIS_SCRIPT . '.htm');
    $renderer -> addCustonStyle(array('script' => 'skin/css/index.css'), THIS_SCRIPT);
    $renderer -> setVariable('global_navbar', $navigation);
    $renderer -> setVariable(THIS_SCRIPT . '_content', $content);
print_output($renderer -> renderTemplate());
