<?php
ini_set("memory_limit", '512M');
ini_set("max_execution_time", '0');

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'ajax_upload_xliff');

// ######################### REQUIRE BACK-END ############################
chdir('../');
require_once( realpath('include/global.php') );

// ########################### INIT VARIABLES ############################
$method = 'p';

$return = array(
              'error'       => false,
              'done'        => false,
              'directory'   => '',
              'currstep'    => 0,
              'nexstep'     => 0,
              'message'     => '',
          );

// ########################### IDENTIFY USER #############################
loggedInOrReturn();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
$website -> input -> clean_array_gpc('f', array('xliff' => TYPE_FILE));
$website -> input -> clean_array_gpc($method, array(
                                                  'currstep'    => TYPE_UINT,
                                                  'nexstep'     => TYPE_UINT,
                                                  'directory'   => TYPE_STR,
                                                  'translation' => TYPE_BOOL,
                                              )
                                    );

if ( isset($website -> GPC['currstep']) ) {
    $return['currstep'] = $website -> GPC['currstep'];
}
if ( isset($website -> GPC['nexstep']) ) {
    $return['nexstep'] = $website -> GPC['nexstep'];
}
if ( isset($website -> GPC['directory']) ) {
    $return['directory'] = $website -> GPC['directory'];
}

if ( (isset($website -> GPC['xliff']) AND count($website -> GPC['xliff'])) OR ($website -> GPC['currstep'] >= 1) ) {
    if ( empty($website -> GPC['currstep']) OR ($website -> GPC['currstep'] == 0) ) {
        // Upload new XLIFF-File
        if ( $website -> GPC['xliff']['error'] == 0 ) {
            $uploader = new Xliff_Upload();
            $result = $uploader -> moveUploadedXliffFile($website -> GPC['xliff']['name'], $website -> GPC['xliff']['tmp_name']);
            $return['error']   = $result['error'];
            $return['message'] = $result['message'];

            if ( $result['error'] == false ) {
                // incrase Setps
                $return['currstep']++;
                $return['nexstep']++;

                // Save Informatzion from Upload
                $uploader -> saveUploadInformation($website -> GPC['xliff']['name'], $result['directory'], $website -> GPC['xliff']['size']);

                // Process-Information
                $return['directory'] = $result['directory'];
                $return['message']  .= '<br />' . $website -> user_lang['translation']['xliff_uploaded_file_size'] .
                                       ': ' . $website -> GPC['xliff']['size'] . ' Byte' .
                                       '<br />' . $website -> user_lang['translation']['xliff_uploaded_file_name'] .
                                       ': ' . $website -> GPC['xliff']['name'];
            }
        }
        else {
            // Upload-Error
            $return['error'] = true;
            $return['message'] = $website -> user_lang['global']['upload_error_' . $website -> GPC['xliff']['error']];
        }
    }
    else {
        // process XLIFF-File
        if ( strlen($return['directory']) ) {
            $processor = new Xliff_Process();
            $currentFile = $processor -> getFileFromDirectory($return['directory']);

            if ( $currentFile[0] == DIRECTORY_SEPARATOR ) {
                $processor -> setProcessedFilename($return['directory'], $currentFile);

                switch( $return['currstep'] ) {
                    // Parse current XLIFF-File
                    case 1: $return['message'] = $processor -> parseCurrentXliffFile();

                            // incrase Setps
                            $return['currstep']++;
                            $return['nexstep']++;

                            break;

                    // check all UUID in currrent File not exists in Database
                    case 2: $return['message'] = $processor -> checkAllUuidInXliffFile();

                            // incrase Setps
                            $return['currstep']++;
                            $return['nexstep']++;

                            break;

                    // Save Data into Database
                    case 3: $return['message'] = $processor -> processCurrentXliffFile($website -> GPC['translation']);

                            // Process is done
                            $return['done'] = true;

                            break;
                }

                // get the last Error from XML-Processor
                $return['error'] = $processor -> getLastError();
            }
            else {
                $return['error']   = true;
                $return['message'] = $currentFile;
            }
        }
    }
}
else {
    $return['error']   = true;
    $return['message'] = $website -> user_lang['translation']['xliff_unknown_file'];
}

echo json_encode($return);