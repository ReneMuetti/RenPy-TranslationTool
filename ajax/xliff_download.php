<?php
// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'ajax_xliff_download');

// ######################### REQUIRE BACK-END ############################
chdir('../');
require_once( realpath('include/global.php') );

// ########################### INIT VARIABLES ############################
$method = 'r';   // musst be (R)equest => see "download-zip"
$result = array(
              'message' => '',
              'data'    => null,
          );

$file_content = '';

// ########################### IDENTIFY USER #############################
loggedInOrReturn();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
$website -> input -> clean_array_gpc($method, array('action' => TYPE_NOHTML));

if ( $website -> GPC['action'] == 'download' ) {
    $website -> input -> clean_array_gpc($method, array(
                                                      'filename' => TYPE_NOHTML,
                                                      'filepath' => TYPE_NOHTML,
                                                      'filetype' => TYPE_NOHTML,
                                                      'language' => TYPE_UINT,
                                                      'debug'    => TYPE_BOOL,
                                                  )
                                        );

    if ( strlen($website -> GPC['filename']) AND strlen($website -> GPC['filepath']) ) {
        $downloader = new Xliff_Download();

        if ( $website -> GPC['filetype'] == 'xliff' ) {
            $file_content = $downloader -> downloadTranslationFromFileAsXLIFF($website -> GPC['filepath'], $website -> GPC['language']);

            if ( $website -> GPC['debug'] == true ) {
                echo $file_content;
            }
            else {
                $dlFile = str_replace('.rpy', '.xliff', $website -> GPC['filename']);

                header( 'Content-Type: application/x-xliff+xml; charset=utf-8' );
                header( 'Content-Length: ' . strlen($file_content) );
                header( 'Content-Disposition: attachment; filename="' . $dlFile . '"' );

                echo $file_content;
            }
        }
        elseif ( $website -> GPC['filetype'] == 'rpy' ) {
            if ( $website -> GPC['language'] == 0 ) {
                $result['message'] = $website -> user_lang['translation']['download_no_language_select'];
            }
            else {
                $file_content = $downloader -> downloadTranslationFromFileAsRPY($website -> GPC['filepath'], $website -> GPC['language']);

                if ( $website -> GPC['debug'] == true ) {
                    echo $file_content;
                }
                else {

                    header( 'Content-Type: text/rpy; charset=utf-8' );
                    header( 'Content-Length: ' . strlen($file_content) );
                    header( 'Content-Disposition: attachment; filename="' . $website -> GPC['filename'] . '"' );

                    echo $file_content;
                }
            }
        }
        else {
            $result['message'] = $website -> user_lang['translation']['download_no_no_valid_filetype'];
        }
    }
    else {
        $result['message'] = $website -> user_lang['xliff']['ajax_download_parameter_failed'];
    }
}
elseif ( $website -> GPC['action'] == 'create-export-dir' ) {
    $downloader = new Xliff_Download();
    $return = $downloader -> createExportDirectoryForRPY();
    $result['data'] = array(
                          'error'  => $return['error'],
                          'export' => $return['directory'],
                      );
    $result['message'] = $return['message'];

    echo json_encode($result);
}
elseif ( $website -> GPC['action'] == 'download-mass' ) {
    $website -> input -> clean_array_gpc($method, array(
                                                      'filename' => TYPE_NOHTML,
                                                      'filepath' => TYPE_NOHTML,
                                                      'filetype' => TYPE_NOHTML,
                                                      'language' => TYPE_UINT,
                                                      'exportto' => TYPE_NOHTML,
                                                  )
                                        );

    if ( strlen($website -> GPC['filename']) AND strlen($website -> GPC['filepath']) ) {
        if ( $website -> GPC['filetype'] == 'rpy' ) {
            if ( $website -> GPC['language'] > 0 ) {
                $downloader = new Xliff_Download();

                if ( strlen($website -> GPC['exportto']) ) {
                    // export
                    $return = $downloader -> downloadTranslationFromFileAsRPY($website -> GPC['filepath'], $website -> GPC['language'], $website -> GPC['exportto'], true);

                    $result['data'] = array(
                                          'error'   => $return['error'],
                                          'created' => $return['date'],
                                      );
                    echo json_encode($result);
                }
                else {
                    $result['message'] = $website -> user_lang['translation']['download_no_export_directory'];
                }
            }
            else {
                $result['message'] = $website -> user_lang['translation']['download_no_language_select'];
            }
        }
        else {
            $result['message'] = $website -> user_lang['translation']['download_no_no_valid_filetype'];
        }
    }
    else {
        $result['message'] = $website -> user_lang['xliff']['ajax_download_parameter_failed'];
    }
}
elseif ( $website -> GPC['action'] == 'create-zip-file' ) {
    $website -> input -> clean_array_gpc($method, array('exportto' => TYPE_NOHTML, 'language' => TYPE_UINT));

    if ( strlen($website -> GPC['exportto']) AND ($website -> GPC['language'] > 0) ) {
        $downloader = new Xliff_Download();

        $return = $downloader -> createZipFileFromExportDirectory($website -> GPC['exportto'], $website -> GPC['language']);

        $result['data'] = array(
                              'error' => $return['error'],
                              'zip'   => $return['zip'],
                          );
        $result['message'] = $return['message'];

        echo json_encode($result);
    }
    else {
        $result['message'] = $website -> user_lang['translation']['download_no_export_directory'];
    }
}
elseif ( $website -> GPC['action'] == 'download-zip' ) {
    $website -> input -> clean_array_gpc('g', array('zip-file' => TYPE_NOHTML));

    $currentZIP = $website -> config['Misc']['path'] . DS . 'export' . DS . $website -> GPC['zip-file'];
    if ( is_file($currentZIP) ) {
        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/x-zip-compressed' );
        //header('Content-Transfer-Encoding: binary');
        //header('Pragma: private');
        header( 'Content-Length: ' . filesize($currentZIP) );
        header( 'Content-Disposition: attachment; filename="' . $website -> GPC['zip-file'] . '"' );
        readfile($currentZIP);
    }
    else {
        echo 'file not found!';
    }
}
else {
    $result['message'] = $website -> user_lang['global']['unkonwn_action'];

    echo json_encode($result);
}