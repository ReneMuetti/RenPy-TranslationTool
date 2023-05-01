<?php
class Xliff_Upload
{
    private $registry = null;
    private $upload_path = null;

    public function __construct()
    {
        global $website;

        $this -> registry = $website;

        $this -> upload_path = $this -> registry -> config['Misc']['path'] . DS . 'upload';
    }

    public function __destruct()
    {
        unset($this -> registry);
    }

    /**
     * save current uploaded file for processing
     *
     * @access public
     * @param  string     uploaded filename
     * @param  string     current tempname from system
     * @return array
     */
    public function moveUploadedXliffFile($currFilename, $tmpName)
    {
        $return = array(
                      'directory' => '',
                      'message'   => '',
                      'error'     => false,
                  );

        if ( is_file($tmpName) ) {
            $newSubDir     = DS . TIMENOW;
            $newUploadPath = $this -> upload_path . $newSubDir;

            if ( !is_dir($newUploadPath) ) {
                if( mkdir($newUploadPath) != TRUE ) {
                    $return['message'] = $this -> registry -> user_lang['translation']['upload_dir_create_fail'];
                    $return['error']   = true;
                }

                $return['directory'] = $newSubDir;
            }

            $newDestinationFile = $newUploadPath . DS . $currFilename;
            if ( !is_file($newDestinationFile) ) {
                if ( move_uploaded_file($tmpName, $newDestinationFile) != TRUE ) {
                    $return['message'] = $this -> registry -> user_lang['translation']['moved_upload_file_fail'];
                    $return['error']   = true;
                }
                else {
                    $return['message'] = $this -> registry -> user_lang['translation']['moved_upload_file_success'];
                }
            }
        }
        else {
            $return['message'] = $this -> registry -> user_lang['translation']['upload_file_not_found'];
            $return['error']   = true;
        }

        return $return;
    }

    /**
     * save Uplaod-History
     *
     * @access public
     * @param  string     current filename
     * @param  string     current sub directory
     * @param  integer    current filesize in bytes
     */
    public function saveUploadInformation($currFilename, $currSubDir, $currentFileSize)
    {
        $insert = array(
                      'upload_file' => $currFilename,
                      'upload_dir'  => $currSubDir,
                      'upload_size' => $currentFileSize,
                      'upload_user' => $this -> registry -> userinfo['username'],
                  );
        $this -> registry -> db -> insertRow($insert, 'xliff_upload');
    }
}