<?php
class Xliff_Download
{
    private $registry = null;
    private $renderer = null;

    private $export_path = null;

    private $spacer    = '    ';      // spacer for filetree-HTML and RPY
    private $seperator = '/';         // Path-Sepeartor in filename
    private $fileList  = array();     // all unique files from database

    private $dlOriginals   = null;    // entries from original
    private $dlGenerals    = null;    // UUID => Source
    private $dlTranslation = null;    // UUID => all translations

    private $xliffVersion = '2.0';
    private $srcLang      = 'ru';
    private $inlineLB     = '\n';     // inline linebreak in strings
    private $quoteMask    = '#$#';    // masking quote-marks (RPY-export))

    public function __construct()
    {
        global $website, $renderer;

        $this -> registry = $website;
        $this -> renderer = $renderer;

        $this -> export_path = $this -> registry -> config['Misc']['path'] . DS . 'export';
    }

    public function __destruct()
    {
        unset($this -> registry);
        unset($this -> renderer);
    }

    /**
     * generate FileTree for Download
     *
     * @access public
     * @return string
     */
    public function getGameFilesAsTree()
    {
        $langs = new Languages();
        $lngList = $langs -> getLanguageByID();
        $html_languages = array();
        if ( is_array($lngList) ) {
            foreach( $lngList AS $iso => $code ) {
                $html_languages[] = '<option value="' . $iso . '">' . $this -> registry -> user_lang['languages'][$code] . '</option>';
            }
        }

        $tree = $this -> _getAllOriginalFiles();

        $html_tree = array();
        if ( is_array($tree) ) {
            $this -> _transformTreeArrayToHtmlTree($tree, $html_tree);
        }
        else {
            $html_tree[] = '<li><span>' . $this -> registry -> user_lang['xliff']['no_file_tree_data_found'] . '</span></li>';
        }

        $this -> renderer -> loadTemplate('admin' . DS . 'download_tree.htm');
            $this -> renderer -> setVariable('game_file_tree', implode("\n", $html_tree));
            $this -> renderer -> setVariable('language_options', implode("\n                    ", $html_languages));
        return $this -> renderer -> renderTemplate();
    }

    /**
     * create new export directory for RPY-export
     *
     * @access public
     * @return array
     */
    public function createExportDirectoryForRPY()
    {
        $return = array(
                      'directory' => '',
                      'message'   => '',
                      'error'     => false,
                  );

        $newSubDir = DS . TIMENOW;
        $newPath   = $this -> export_path . $newSubDir;

        if ( !is_dir($newPath) ) {
            if( mkdir($newPath) != true ) {
                $return['message'] = $this -> registry -> user_lang['translation']['export_dir_create_fail'];
                $return['error']   = true;
            }
            else {
                $return['directory'] = $newSubDir;
            }
        }

        return $return;
    }

    /**
     * create ZIP-File for download
     */
    public function createZipFileFromExportDirectory($massExportDir, $selectedLanguage)
    {
        $langs = new Languages();
        $lngname = $langs -> getLanguageTitleByID($selectedLanguage);

        $compessingDir = $this -> export_path . $massExportDir;
        $zipOutputPath = $this -> export_path;
        $zipArchivName = date("Y-m-d_H-i") . '_' . str_replace(DS, '', $massExportDir) . '_' . $lngname;

        $zipCompress = new ZIP_File();
        $zipCompress -> compressFolder($compessingDir, $zipArchivName, $zipOutputPath);

        if ( is_file( $zipOutputPath . DS . $zipArchivName . '.zip' ) ) {
            return array(
                       'error'   => false,
                       'zip'     => $zipArchivName . '.zip',
                       'message' => '',
                   );
        }
        else {
            return array(
                       'error'   => true,
                       'zip'     => false,
                       'message' => 'show LOG-File',
                   );
        }
    }

    /**
     * generate RPY-File for download
     *
     * @access public
     * @param  string      General -> Org-Filename
     * @param  integer     selected language
     * @param  string      export directory for mass-export
     * @param  boolean     save export-file for mass-export
     * @return string|array
     */
    public function downloadTranslationFromFileAsRPY($originalFileName, $selectedLanguage, $massExportDir = '', $massExport = false)
    {
        $this -> _getAllGeneralDataByOriginalFilename($originalFileName);
        $this -> _getAllOriginalDataByGeneralOriginalFilename($originalFileName);
        $this -> _getAllTranslationsFromOriginalFilename($originalFileName, $selectedLanguage);

        $langs = new Languages();
        $lngname = $langs -> getLanguageTitleByID($selectedLanguage);

        $output = array();
        $currentstart = time();

        // TODO :: better handling for common.rpy
        if ( count($this -> dlOriginals) AND count($this -> dlGenerals) ) {
            $output[] = '# TODO: Translation updated at ' . date("Y-m-d H:i", $currentstart);
            $hasTopicOut = true;

            foreach( $this -> dlOriginals AS $id => $originalData ) {
                if ( strlen($originalData['renpyid']) ) {
                    $hasTopicOut = true;

                    $sourceStr = $this -> _fixedStringForRPY($originalData['comment'], true);
                    $orgString = stripslashes($this -> dlGenerals[$originalData['uuid']]);

                    $output[] = '';
                    $output[] = '# ' . $originalData['filename'] . ':' . $originalData['linenumber'];
                    $output[] = 'translate ' . $lngname . ' ' . $originalData['renpyid'] . ':';
                    $output[] = '';
                    $output[] = $this -> spacer . '# ' . $sourceStr;

                    if ( is_array($this -> dlTranslation) AND array_key_exists($originalData['uuid'], $this -> dlTranslation) ) {
                        $newString = $this -> dlTranslation[$originalData['uuid']];
                        $key = array_key_first($newString);

                        $translatet = str_replace($orgString, $newString[$key], stripslashes($originalData['comment']) );
                        $translatet = $this -> _fixedStringForRPY($translatet, true);

                        // original and translation is same -- multi-segments
                        if ( $sourceStr == $translatet ) {
                            // source is multi-segmented-string
                            $translatet = $this -> _replaceTokenFromMultiSegmentStrings($sourceStr, $newString[$key], $originalData['ignorable']);
                        }

                        $translatet = $this -> _postProcessingForExport($translatet);

                        $output[] = $this -> spacer . $translatet;
                    }
                    else {
                        // missing translation
                        $translatet = str_replace($orgString, '', stripslashes($originalData['comment']) );

                        $output[] = $this -> spacer . $translatet;
                    }
                }
                else {
                    $output[] = '';

                    if ( $hasTopicOut === true ) {
                        $output[] = 'translate ' . $lngname . ' strings:';
                        $output[] = '';

                        $hasTopicOut = false;
                    }

                    $originalData['comment'] = stripslashes($originalData['comment']);

                    $orgString = $this -> _fixedStringForRPY($originalData['comment']);
                    $sourceStr = $this -> _fixedStringForRPY($originalData['comment'], false, true);

                    $output[] = $this -> spacer . '# ' . $originalData['filename'] . ':' . $originalData['linenumber'];
                    $output[] = $this -> spacer . 'old "' . $sourceStr . '"';

                    if ( is_array($this -> dlTranslation) AND array_key_exists($originalData['uuid'], $this -> dlTranslation) ) {
                        $newString = $this -> dlTranslation[$originalData['uuid']];
                        $key = array_key_first($newString);

                        $ignorable = $this -> _convertSerializedIgnorableToArray($originalData['ignorable']);

                        if ( is_array($ignorable) AND (count($ignorable) >= 2) ) {
                            $newString[$key] = stripslashes($newString[$key]);

                            // ignorable is set an has Data
                            $checkSource = mb_substr($originalData['comment'], 0, mb_strlen($ignorable[0]['source']));
                            $checkDest0  = mb_substr($newString[$key]        , 0, mb_strlen($ignorable[0]['source']));
                            $checkDest1  = mb_substr($newString[$key]        , 0 - mb_strlen($ignorable[1]['source']));

                            if ( (count($ignorable) == 2) AND ($checkSource == $ignorable[0]['source']) AND ($checkDest0 != $ignorable[0]['source']) AND ($checkDest1 != $ignorable[1]['source']) ) {
                                // ignorable has 2 segments and it covers original string
                                $translatet = $this -> _fixedStringForRPY($newString[$key], false, true);
                                $translatet = $this -> _postProcessingForExport($translatet);

                                $output[] = $this -> spacer . 'new "' . $ignorable[0]['source'] . $translatet . $ignorable[1]['source'] . '"';
                            }
                            else {
                                // multible segments or ignorable mixed position
                                $translatet = $this -> _replaceTokenFromMultiSegmentStrings($sourceStr, $newString[$key], $originalData['ignorable']);
                                $translatet = $this -> _postProcessingForExport($translatet);

                                $output[] = $this -> spacer . 'new "' . $translatet . '"';
                            }
                        }
                        else {
                            // simple translation
                            $translatet = $this -> _fixedStringForRPY($newString[$key], false, true);
                            $translatet = $this -> _postProcessingForExport($translatet);

                            $output[] = $this -> spacer . 'new "' . $translatet . '"';
                        }
                    }
                    else {
                        // TODO :: better handling for common.rpy
                        if ( $originalFileName == 'common.rpy' ) {
                            $translatet = $this -> _fixedStringForRPY($newString[$key], false, true);
                            $translatet = $this -> _postProcessingForExport($translatet);

                            $output[] = $this -> spacer . 'new "' . $translatet . '"';
                        }
                        else {
                            // missing translation
                            $output[] = $this -> spacer . 'new ""';
                        }
                    }
                }
            }

            $outString = implode("\n", $output);
            $outString = str_replace($this -> quoteMask, '"', $outString);
        }
        else {
            $outString = 'no data foud for >>' . $originalFileName . '<< or Processing-Error!';
        }

        if ( $massExport === false ) {
            return $outString;
        }
        else {
            // write file in export directory
            if ( is_dir($this -> export_path . $massExportDir) ) {
                $destination  = $this -> export_path . $massExportDir . DS . $originalFileName;
                $pathSegments = pathinfo($destination);
                if ( !is_dir($pathSegments['dirname']) ) {
                    // create direcotry path
                    mkdir( $pathSegments['dirname'], 0777, true );
                }
                file_put_contents($destination, $outString, LOCK_EX);

                $result = array(
                              'error' => false,
                              'date'  => date("Y-m-d H:i", $currentstart),
                          );
                return $result;
            }
            else {
                // somethis is wrong
                $result = array(
                              'error'   => true,
                              'message' => $this -> registry -> user_lang['xliff']['export_mass_directory_fail'],
                          );
                return $result;
            }
        }
    }

    /**
     * generate XML-File for download AS XLIFF
     *
     * @access public
     * @param  string      General -> Org-Filename
     * @param  integer     selected language
     * @param  boolean     remove CDATA from output
     * @return string
     */
    public function downloadTranslationFromFileAsXLIFF($originalFileName, $selectedLanguage, $removeCdata = true)
    {
        $this -> _getAllGeneralDataByOriginalFilename($originalFileName);
        $this -> _getAllOriginalDataByGeneralOriginalFilename($originalFileName);
        $this -> _getAllTranslationsFromOriginalFilename($originalFileName, $selectedLanguage);

        $output = '';

        if ( count($this -> dlOriginals) AND count($this -> dlGenerals) AND count($this -> dlTranslation) ) {
            $xliffBuilder = new XML_Builder($this -> registry, CHARSET, 'xliff');

            // open XLIFF-Element
            $xliffBuilder -> add_group('xliff', array(
                                                    'xmlns'   => 'urn:oasis:names:tc:xliff:document:' . $this -> xliffVersion,
                                                    'version' => $this -> xliffVersion,
                                                    'srcLang' => ( ($originalFileName == 'common.rpy') ? 'us' : $this -> srcLang ),
                                                )
                                      );

            // open root-element with original filename
            $xliffBuilder -> add_group('file', array('id' => $originalFileName));

            foreach( $this -> dlOriginals AS $id => $originalData ) {
                // open unit-element per UUID
                $xliffBuilder -> add_group('unit', array('id' => $originalData['uuid'] ));

                // START :: all note-elements
                $xliffBuilder -> add_group('notes');
                    $formatetString = $this -> _fixedString($originalData['comment'], true);

                    $xliffBuilder -> add_tag('note', $formatetString            , array('category' => 'comment'));
                    $xliffBuilder -> add_tag('note', $originalData['uuid']      , array('category' => 'uuid'));
                    $xliffBuilder -> add_tag('note', $originalData['filename']  , array('category' => 'filename'));
                    $xliffBuilder -> add_tag('note', $originalData['linenumber'], array('category' => 'linenumber'));
                $xliffBuilder -> close_group();
                // END :: notes-group

                // fixed Original-String
                $originalString = $this -> _fixedString($this -> dlGenerals[$originalData['uuid']], true);

                // add first ignorable -- if exists
                if ( strlen($originalData['igno_start']) AND (mb_strpos($formatetString, $this -> inlineLB) === false) ) {
                    $xliffBuilder -> add_group('ignorable');
                        $xliffBuilder -> add_tag('source', $this -> _fixedString($originalData['igno_start']) );
                    $xliffBuilder -> close_group();
                }

                // START translation
                if ( mb_strpos($originalString, $this -> inlineLB) ) {
                    // String has line break -- convert to mulit-array
                    $multiData = $this -> _extractLinebreakStringToMultiArray($originalString, $originalData['uuid']);

                    foreach( $multiData AS $id => $multiSegments ) {
                        $xliffBuilder -> add_group('segment');
                        foreach( $multiSegments AS $what => $string ) {
                            // add all segments
                            $string = $this -> _convertHtmlSpecialChars($string);
                            if ( $what == 'source' ) {
                                $xliffBuilder -> add_tag($what, $string);
                            }
                            else {
                                $xliffBuilder -> add_tag('target', $string, array("xml:lang" => $what) );
                            }
                        }
                        $xliffBuilder -> close_group();

                        if ( $id < count($multiData) - 1 ) {
                            $xliffBuilder -> add_group('ignorable');
                                $xliffBuilder -> add_tag('source', ' \\\n' );
                            $xliffBuilder -> close_group();
                        }
                    }
                }
                else {
                    // single-string
                    $xliffBuilder -> add_group('segment');
                        $xliffBuilder -> add_tag('source', $originalString );

                        // add all translatet strings
                        foreach( $this -> dlTranslation[$originalData['uuid']] AS $isoCode => $translation ) {
                            $translation = $this -> _convertHtmlSpecialChars($translation);
                            $xliffBuilder -> add_tag('target', $translation, array("xml:lang" => $isoCode) );
                        }
                    $xliffBuilder -> close_group();
                }
                // END translation

                // add last ignorable -- if exists
                if ( strlen($originalData['igno_end']) AND (mb_strpos($formatetString, $this -> inlineLB) === false) ) {
                    $xliffBuilder -> add_group('ignorable');
                        $xliffBuilder -> add_tag('source', $this -> _fixedString($originalData['igno_end']) );
                    $xliffBuilder -> close_group();
                }

                // close unit-element per UUID
                $xliffBuilder -> close_group();
            }

            // closed root-element
            $xliffBuilder -> close_group();

            // cose XLIFF-Element
            $xliffBuilder -> close_group();

            // generate output
            $output = $xliffBuilder -> output();

            // removed CDATA-Tags
            if ( $removeCdata ) {
                $output = str_replace(array('<![CDATA[', ']]>'), '', $output);
            }

            unset($xliffBuilder);
        }
        else {
            // TODO
        }

        return $output;
    }


    /**************************************************************/
    /*********************  private functions *********************/
    /**************************************************************/

    private function _postProcessingForExport($translation)
    {
        $translation = str_replace(
                           array('{b}{b}', '{/b}:{/b}', '"Lisa:r{/b}'),
                           array('{b}'   , '{/b}:'    , 'Lisa:{/b}'),
                           $translation
                       );
        $translation = str_replace(
                           array('&amp;amp;', '&amp;', '&lt;br /&gt;', '&lt;', '&gt;'),
                           array('&'        , '&'    , ''            , '<'   , '>'),
                           $translation
                       );

        return $translation;
    }

    /**
     * convert serialized string to array
     *
     * @access private
     * @param  string
     * @return array|bool
     */
    private function _convertSerializedIgnorableToArray($ignorableString)
    {
        $ignorable = str_replace("\\\n", '\\\n', stripslashes($ignorableString) );
        return unserialize( $ignorable );
    }

    /**
     * fixed igonabel segments if its consecutive
     *
     * @access private
     * @param  string     original string for searching positions
     * @param  array      ignorable data
     */
    private function _findConsecutiveIgnorableStrings($originalString, &$ignorabeData)
    {
        if ( !is_array($ignorabeData) OR (count($ignorabeData) < 2) ) {
            return;
        }

        $removableIds = array();

        $curStart = -1;
        $lastEnd  = -1;
        $lastSrc  = '';

        foreach( $ignorabeData AS $key => $value ) {
            if ( strpos($value['source'], '\\\\') !== false ) {
                $value['source'] = str_replace('\\\\', '\\', $value['source']);
            }

            $curStart = strpos($originalString, $value['source']);

            if ( ($curStart == $lastEnd) AND ($lastEnd >= 1) ) {
                $removableIds[] = $key;
                $lastKey = $key - 1;
                $ignorabeData[$lastKey]['source'] = $lastSrc . $value['source'];
            }

            $lastEnd = $curStart + mb_strlen($value['source']);
            $lastSrc = $value['source'];
        }

        if ( count($removableIds) ) {
            // remove useless items
            foreach( $removableIds AS $removable ) {
                unset($ignorabeData[$removable]);
            }

            $ignorabeData = array_values($ignorabeData);
        }
    }

    /**
     * separated string in to tokens an replace this with trnaslated segments
     *
     * @access private
     * @param  string     Source-String with quotemarks for replacement
     * @param  string     translated segments with line breaks
     * @param  string     serialized data from ignorable
     * @return string
     */
    private function _replaceTokenFromMultiSegmentStrings($sourceString, $destinationString, $ignorableData)
    {
        $helpStringForSegmentation = '|#|';   // helperstring for segmentation

        // extract the core string, if necessary
        if ( mb_strpos($sourceString, $this -> quoteMask) !== false ) {
            $coreString = mb_substr($sourceString, mb_strpos($sourceString, $this -> quoteMask) + mb_strlen($this -> quoteMask));
            $coreString = mb_substr($coreString, 0, mb_strpos($coreString, $this -> quoteMask));
        }
        else {
            $coreString = $sourceString;
        }
        $destString = $this -> _fixedStringForRPY($destinationString);

        // transform serialized string to an array
        $ignorable = $this -> _convertSerializedIgnorableToArray($ignorableData);

        // combine consecutive ignorable strings
        $this -> _findConsecutiveIgnorableStrings($coreString, $ignorable);

        $temp = $coreString;
        // modify original string for sepeerating to array
        if ( is_array($ignorable) AND count($ignorable) ) {
            foreach( $ignorable AS $key => $igno ) {
                $segmenter = str_replace('\\\n', $this -> inlineLB, $igno['source']);
                $temp      = str_replace($segmenter, $helpStringForSegmentation, $temp);
            }
        }

        // switch segments to array for precessing
        $orgSegments  = explode($helpStringForSegmentation, $temp);
        $destSegments = explode($this -> inlineLB, $destString);

        $startWithSegment = false;

        // check if the frist item is empty => translation must be start with ignorable-item
        if ( !mb_strlen($orgSegments[0]) ) {
            unset($orgSegments[0]);  // remove first item from array (its empty))
            $startWithSegment = true;
        }

        $output = '';
        // recombine translated string for replacement (hope this works :/))
        foreach( $destSegments AS $key => $value ) {
            // check if key is set -- ignorable can have diffent count
            if ( isset($ignorable[$key]) ) {
                $ignorable[$key]['source'] = str_replace('\\\n', $this -> inlineLB, $ignorable[$key]['source']);

                if ( $startWithSegment == true ) {
                    $output .= $ignorable[$key]['source'];
                    $output .= $value;
                }
                else {
                    $output .= $value;
                    $output .= $ignorable[$key]['source'];
                }
            }
            else {
                $output .= $value;
            }
        }

        // replace original string with combined translation
        return str_replace($coreString, $output, $sourceString);
    }

    /**
     * modified string for PRY-Export
     *
     * @access private
     * @param  string
     * @param  boolean
     * @return string
     */
    private function _fixedStringForRPY($string, $replaceQuoteMarks = false, $isShortTranslation = false)
    {
        $string = stripslashes($string);
        $string = str_replace(
                      array("\n"             , "\r"),
                      array($this -> inlineLB, ''),
                      $string
                  );

        // replace HTML-Char in translation string if need
        if ( mb_strpos($string, 'quot;') !== false ) {
            $string = str_replace( array('&quot;', '&amp;quot;'), '"', $string);
        }

        if ( mb_substr_count($string, '"') > 2 ) {
            // the string contains more than 2 quote marks; all inner ones must therefore be escaped
            // Long-Strings with char-emotes on beginning

            $string = str_replace('\\"', '"', $string);

            if ( mb_substr($string, -1) == '"' ) {
                $tmp = mb_substr($string, 0, -1);
            }
            else {
                $tmp = $string;
            }

            $segments  = explode('"', $tmp);
            $startWith = $segments[0];
            $endWith   = end($segments);

            unset($segments[0]);
            array_pop($segments);

            // reassemble all segments with masked quote
            $center = implode('\"', $segments) . '\"';

            if ( $replaceQuoteMarks === true ) {
                $string = $startWith . $this -> quoteMask . $center . $endWith . $this -> quoteMask;
            }
            else {
                $string = $startWith . '"' . $center . $endWith . '"';
            }
        }
        else {
            // short-strings (old/new - duo)
            if ( (strpos($string, '"') !== false) AND ($isShortTranslation === true) ) {
                $string = str_replace('"', '\\"', $string);
            }

            if ( $replaceQuoteMarks === true ) {
                $string = str_replace('"' , $this -> quoteMask, $string);
            }
        }

        return $string;
    }

    /**
     * remove add slashes and convert linebreak to \n
     *
     * @access private
     * @param  string
     * @param  boolean
     * @return string
     */
    private function _fixedString($string, $convertHtmlChars = false)
    {
        $string = stripslashes($string);

        if ( $convertHtmlChars ) {
            $string = str_replace("\n", $this -> inlineLB, $string);
            return $this -> _convertHtmlSpecialChars($string);
        }
        else {
            return str_replace("\n", $this -> inlineLB, $string);
        }
    }

    /**
     * fixed string has HTML-Characters
     *
     * @access private
     * @param  string
     * @return string
     */
    private function _convertHtmlSpecialChars($string)
    {
        if ( (mb_strpos($string, '<') !== false) OR mb_strpos($string, '>') !== false ) {
            return htmlspecialchars($string, ENT_QUOTES);
        }
        else {
            return $string;
        }
    }

    /**
     * convert string with line break into multible array
     *
     * @access private
     * @param  string
     * @param  string    UUID
     * @return array
     */
    private function _extractLinebreakStringToMultiArray($orgString, $uuid)
    {
        $return = array();

        // Original / Source
        $parts = explode($this -> inlineLB, $orgString);
        foreach( $parts AS $id => $part ) {
            $return[$id] = array(
                               'source' => $part,
                           );
        }

        // Translations
        foreach( $this -> dlTranslation[$uuid] AS $isoCode => $translation ) {
            $parts = explode("\n", $translation);
            foreach( $parts AS $id => $part ) {
                $return[$id][$isoCode] = $part;
            }
        }

        return $return;
    }

    /**
     * all files from database
     *
     * @access private
     * @param  string    full-filename from general-table
     * @param  bool      return query as string for SubQuery
     * @return null|string
     */
    private function _getAllGeneralDataByOriginalFilename($originalFileName, $returnUuidQuery = false)
    {
        $query = "SELECT " . ( ($returnUuidQuery === true) ? "`uuid`" : "*" ) . " " .
                 "FROM `xliff_general` " .
                 "WHERE `org_filename` = '" . $originalFileName .  "' " .
                   "AND `active` = 1 " .
                 "ORDER BY `linenumber`";

        if ( $returnUuidQuery === true ) {
            return $query;
        }
        else {
            $this -> dlOriginals = $this -> registry -> db -> queryObjectArray($query);
        }
    }

    /**
     * all original-strings from current file
     *
     * @access private
     * @param  string    full-filename from general-table
     */
    private function _getAllOriginalDataByGeneralOriginalFilename($originalFileName)
    {
        $subQuery = $this -> _getAllGeneralDataByOriginalFilename($originalFileName, true);
        $query    = "SELECT * FROM `xliff_original` " .
                    "WHERE `xliff_original`.`uuid` IN (" . $subQuery . ");";
        $data = $this -> registry -> db -> queryObjectArray($query);

        if ( is_array($data) AND count($data) ) {
            foreach( $data AS $key => $value ) {
                $this -> dlGenerals[$value['uuid']] = $value['source'];
            }
        }
    }

    /**
     * all translatet strings from current file
     *
     * @access private
     * @param  string    full-filename from general-table
     * @param  integer   selected language
     */
    private function _getAllTranslationsFromOriginalFilename($originalFileName, $selectedLanguage)
    {
        if ( $selectedLanguage == 0 ) {
            $languageSelectFilter = "";
        }
        else {
            $languageSelectFilter = "AND `xliff_translate`.`language` = " . $selectedLanguage . " ";
        }

        $subQuery = $this -> _getAllGeneralDataByOriginalFilename($originalFileName, true);
        $query    = "SELECT `xliff_translate`.*, " .
                           "`language`.`lng_code` " .
                    "FROM `xliff_translate` " .
                    "LEFT JOIN `language` ON (`xliff_translate`.`language` = `language`.`lng_id`) " .
                    "WHERE `xliff_translate`.`uuid` IN (" . $subQuery . ") " .
                    $languageSelectFilter .
                    "ORDER BY `xliff_translate`.`language` ASC;";
        $data = $this -> registry -> db -> queryObjectArray($query);

        if ( is_array($data) AND count($data) ) {
            $this -> dlTranslation = array();

            foreach( $data AS $key => $value ) {
                if ( !array_key_exists($value['uuid'], $this -> dlTranslation) ) {
                    $this -> dlTranslation[$value['uuid']] = array();
                }

                $this -> dlTranslation[$value['uuid']][$value['lng_code']] = stripslashes($value['translatet']);
            }
        }
    }

    /**
     * all original-files for filetree
     *
     * @access private
     * @return array|boolean
     */
    private function _getAllOriginalFiles()
    {
        $query = "SELECT `org_filename` FROM `xliff_general` GROUP BY `org_filename` ORDER BY `org_filename` ASC;";
        $data  = $this -> registry -> db -> queryObjectArray($query);

        if ( is_array($data) AND count($data) ) {
            $tree_array = array();
            $this -> fileList = array();

            foreach( $data AS $value ) {
                if ( strpos($value['org_filename'], $this -> seperator) !== false ) {
                    $pathParts = explode($this -> seperator, $value['org_filename']);
                    $path = [array_pop($pathParts)];
                    $this -> fileList[$path[0]] = $value['org_filename'];

                    foreach( array_reverse($pathParts) AS $pathPart ) {
                        $path = [$pathPart => $path];
                    }
                    $tree_array = array_merge_recursive($tree_array, $path);
                }
                else {
                    $tree_array[] = $value['org_filename'];
                    $this -> fileList[$value['org_filename']] = $value['org_filename'];
                }
            }
            return $tree_array;
        }
        else {
            return false;
        }
    }

    /**
     * render an full filetree as UL=>LI
     *
     * @access private
     * @param  array    full-filenames
     * @param  array    output-data
     * @param  integer  level for tree
     */
    private function _transformTreeArrayToHtmlTree($array, &$output, $level = 0)
    {
        if ( is_array($array) AND count($array) ) {
            foreach( $array AS $key => $value ) {
                if ( is_int($key) ) {
                    // its single file
                    $output[] = str_repeat($this -> spacer, $level) .
                                '<li><span class="file" data-filenpath="' . $this -> fileList[$value] .
                                '" data-filename="' . $value . '">' .
                                $value . '<span></span></span></li>';
                }
                else {
                    $fill = str_repeat($this -> spacer, $level);
                    $output[] = $fill . '<li><span class="directory">' . $key . '</span>';
                        $level++;
                        $output[] = $fill . '<ul>';
                        $this -> _transformTreeArrayToHtmlTree($value, $output, $level);
                        $output[] = $fill . '</ul>';
                    $output[] = $fill . '</li>';
                }
            }
        }
    }
}