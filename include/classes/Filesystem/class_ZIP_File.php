<?php
class ZIP_File
{
    /**
     * public function compress_folder( $dir, $version, $archive_dest );
     * @param  {string}  $dir | absolute path to the directory
     * @param  {string}  $version_number | ex: 0.1.1
     * @param  {string}  $archive_dest | absolute path to the future compressed file
     * @return {void}    DO A COMPRESSION OF A FOLDER
     */
    public function compressFolder($dir, $name, $archive_dest)
    {
        $archive_name = $archive_dest . DS . $name . '.zip';

        if( !is_dir($dir) ) {
            exit('No working directory ...');
        }

        // Iterate and archive API DIRECTORIES AND FOLDERS

        // create zip archive + manager
        $zip = new ZipArchive();

        if ( $zip -> open($archive_name, ZipArchive::CREATE) !== true ) {
            exit("cannot open |$archive_name|");
        }

        // iterator / SKIP_DOTS -> ignore '..' and '.'
        $it = new RecursiveIteratorIterator(
                      new RecursiveDirectoryIterator(
                              $dir,
                              RecursiveDirectoryIterator::SKIP_DOTS
                          )
                  );

        // loop iterator
        foreach( $it AS $file ) {
            //echo $it -> getPathname() . ' => ' . $it -> getSubPathName() . "\r\n";

            // no need to check if is a DIRECTORY with $it->getSubPathName()
            // DIRECTORIES are added automatically
            $zip -> addFile( $it -> getPathname(),  $it -> getSubPathName() );
        }
        // end  loop

        $zip -> close();
        // END Iterate and archive API DIRECTORIES AND FOLDERS
    }
}