<?php
// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'ajax_db_backup');

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
$website -> input -> clean_array_gpc($method, array(
                                                  'action'   => TYPE_NOHTML,
                                                  'filename' => TYPE_NOHTML,
                                              )
                                    );

$return = array(
              'error'   => null,
              'message' => null,
          );

if ( $website -> userinfo ) {
    if ( $website -> GPC['action'] == 'update' ) {
        // Update Table-Lines
        $backup = new BackupLister();

        $return['error'] = false;
        $return['message'] = $backup -> getCurrentFileList();
    }
    elseif ( $website -> GPC['action'] == 'delete' ) {
        // Delete Backup
        $backup = new BackupLister();
        $return['error'] = $backup -> deleteBackupByFilename($website -> GPC['filename']);
    }
    else {
        $result = $website -> db -> doFullBackup();
        $return['error'] = true;

        // see https://gist.github.com/flickerfly/1da964009ccf3dd80e551157a1ac6cfe
        switch($result) {
            case 0: // succeeded
                    $return['message'] = $website -> user_lang['backup']['db_dump_0'];
                    $return['error'] = false;
                    break;
            case 1: // EX_USAGE 1 <-- command syntax issue
                    $return['message'] = $website -> user_lang['backup']['db_dump_1'];
                    break;
            case 2: // EX_MYSQLERR 2 <-- privilege problem or other issue completing the command
                    $return['message'] = $website -> user_lang['backup']['db_dump_2'];
                    break;
            case 3: // EX_CONSCHECK 3 <-- consistency check problem
                    $return['message'] = $website -> user_lang['backup']['db_dump_3'];
                    break;
            case 4: // EX_EOM 4 <-- End of Memory
                    $return['message'] = $website -> user_lang['backup']['db_dump_4'];
                    break;
            case 5: // EX_EOF 5 <-- Result file problem writing to file, space issue?
                    $return['message'] = $website -> user_lang['backup']['db_dump_5'];
                    break;
            case 6: // EX_ILLEGAL_TABLE 6
                    $return['message'] = $website -> user_lang['backup']['db_dump_6'];
                    break;
            default: // Backup presumed failed Unknown exit code
                     $return['message'] = $website -> user_lang['backup']['db_dump_default'];
                     break;

        }
    }
}
else {
    $return['error']   = true;
    $return['message'] = $website -> user_lang['global']['no_account_data'];
}

echo json_encode($return);