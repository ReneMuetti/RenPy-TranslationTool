<?php
// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'admin_control');

// ######################### REQUIRE BACK-END ############################
chdir('../');
require_once( realpath('include/global.php') );

// ########################### INIT VARIABLES ############################
$navigation = null;
$pageContent = '';

// ########################### IDENTIFY USER #############################
loggedInOrReturn();
checkIfUserIsAdmin();
setDefaultForLoggedinUser();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
$website -> input -> clean_array_gpc('r', array(
                                              'action'    => TYPE_NOHTML,
                                              'accountid' => TYPE_UINT,     // ID from Accounts
                                              'accmethod' => TYPE_NOHTML,   // Methods for Accounts
                                          )
                                    );

$pageTitle = $website -> user_lang['page_titles']['admin_' . $website -> GPC['action']];

if ( $website -> GPC['action'] == 'accounts' ) {
    // User-Accounts
    $profile = new UserProfile();

    if ( isset($website -> GPC['accountid']) AND ($website -> GPC['accountid'] >= 1) ) {
        if ( isset($website -> GPC['accmethod']) AND ($website -> GPC['accmethod'] == 'edit') ) {
            $pageContent = $profile -> getProfileFromUser($website -> GPC['accountid'], true);
        }
        elseif ( isset($website -> GPC['accmethod']) AND ($website -> GPC['accmethod'] == 'updateaccount') ) {
            $renderer -> addCustonStyle(array('script' => 'skin/css/account.css'), THIS_SCRIPT);
            $pageContent = $profile -> updateProfileByID($website -> GPC['accountid']);
        }
        elseif ( isset($website -> GPC['accmethod']) AND ($website -> GPC['accmethod'] == 'delete') ) {
            $pageContent = $profile -> deleteProfileById($website -> GPC['accountid']);
        }
        else {
            $pageContent = $website -> user_lang['global']['unkonwn_action'];
        }
    }
    else {
        // show Account-List
        $pageContent = $profile -> getUserListForAdmin();
    }
}
elseif ( $website -> GPC['action'] == 'language' ) {
    // Laguages
    $language = new Languages();
    
    // show current Language-List
    $pageContent = $language -> getLanguageListForAdmin();
}
elseif ( $website -> GPC['action'] == 'translation' ) {
    // Translations
    $renderer -> loadTemplate('admin' . DS . 'translations.htm');
    $pageContent = $renderer -> renderTemplate();
}
elseif ( $website -> GPC['action'] == 'download' ) {
    // File-Tree
    $download = new Xliff_Download();
    $pageContent = $download -> getGameFilesAsTree();
}
else {
    $pageContent = $website -> user_lang['global']['unkonwn_action'];
}

$renderer -> loadTemplate(THIS_SCRIPT . '.htm');
    $renderer -> setVariable('title_admin'  , $pageTitle);
    $renderer -> setVariable('global_navbar', $navigation);
    $renderer -> setVariable(THIS_SCRIPT . '_content', $pageContent);
print_output($renderer -> renderTemplate());