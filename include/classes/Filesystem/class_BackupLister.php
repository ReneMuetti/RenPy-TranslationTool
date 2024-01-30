<?php
class BackupLister
{
    private $registry;
    private $renderer;


    /**
     * Init
     *
     * @access    public
     */
    public function __construct()
    {
        global $website, $renderer;

        $this -> registry = $website;
        $this -> renderer = $renderer;
    }

    /**
     * Final
     *
     * @access    public
     */
    public function __destruct()
    {
        unset($this -> registry);
        unset($this -> renderer);
    }


    /**
     * show File-Grid
     *
     * @access    public
     * @return    string
     */
    public function showIndex()
    {
        $currentBackupList = $this -> _getCurrentBackupList();

        $this -> renderer -> loadTemplate('admin' . DS . 'backup_list.htm');
            $this -> renderer -> setVariable('admin_table_backup_list', $currentBackupList);
        return $this -> renderer -> renderTemplate();
    }

    /**
     * update filelist per Ajax
     *
     * @access    public
     * @return    string
     */
    public function getCurrentFileList()
    {
        return $this -> _getCurrentBackupList();
    }

    /**
     * delete file by filename per Ajax
     *
     * @access    public
     */
    public function deleteBackupByFilename($filename = null)
    {
        $filename = str_replace(array('..', '/', '\\'), '', $filename);
        $fullFileName = $this -> registry -> config['Database']['backup_path'] . DS . trim($filename);

        if ( is_file($fullFileName) ) {
            return unlink($fullFileName);
        }
        else {
            return false;
        }
    }

    /**
     * List all Backup-Files
     *
     * @access    public
     * @return    string
     */
    private function _getCurrentBackupList()
    {
        $filesList = array();
        $lines = array();

        $subPath = str_replace($this -> registry -> config['Misc']['path'], '', $this -> registry -> config['Database']['backup_path']);

        $files = new FileDir($subPath);
        $filesList = $files -> getFileList('.gz', true);

        if ( count($filesList) AND count($filesList[0]) ) {
            foreach( $filesList AS $file ) {
                $this -> renderer -> loadTemplate('admin' . DS . 'backup_list_line.htm');
                    $this -> renderer -> setVariable('file_name' , $file['name']);
                    $this -> renderer -> setVariable('file_size' , $file['size']);
                    $this -> renderer -> setVariable('file_added', $file['added']);
                $lines[] = $this -> renderer -> renderTemplate();
            }

            return implode("\n", $lines);
        }
        else {
            $this -> renderer -> loadTemplate('admin' . DS . 'backup_list_empty.htm');
            return $this -> renderer -> renderTemplate();
        }
    }
}