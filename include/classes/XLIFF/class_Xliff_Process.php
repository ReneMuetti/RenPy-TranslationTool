<?php
/**
 * Processing XLIFF-File
 */
class Xliff_Process
{
    private $registry     = null;

    private $upload_path  = null;  // string   :: current upload-path
    private $process_file = null;  // string   :: current filename
    private $last_error   = false; // boolean  :: pre-processor-error-state
    private $currentXLIFF = null;  // array    :: data from current XLIFF-File
    private $languages    = null;  // array    :: all languages

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        global $website;

        $this -> registry = $website;

        $this -> upload_path = $this -> registry -> config['Misc']['path'] . DS . 'upload';

        $lngs = new Languages();
        $this -> languages = $lngs -> getLanguagesByCode();
        $this -> _addLanguageMapper();
    }

    /**
     * Destructor
     *
     * @access public
     */
    public function __destruct()
    {
        unset($this -> registry);
    }

    /**
     * get last Error-Status
     *
     * @access public
     * @return boolean
     */
    public function getLastError()
    {
        return $this -> last_error;
    }

    /**
     * get all Files from Directory
     *
     * @access public
     * @param  string    directoryname
     * @return array|string
     */
    public function getFileFromDirectory($directory = '')
    {
        $directory = $this -> _cleanString($directory);

        $lister = new FileDir('upload' . $directory);
        $liste  = $lister -> getFileList('.xliff');

        if ( is_array($liste) AND count($liste) ) {
            // return only first File
            return $liste[0];
        }
        else {
            return $this -> registry -> user_lang['xliff']['current_dir_is_empty'];
        }
    }

    /**
     * set File to current processinf
     *
     * @access public
     * @param  string    directoryname
     * @param  string    filename
     */
    public function setProcessedFilename($directory = '', $filename = 'empty.xliff')
    {
        $filename  = $this -> _cleanString($filename);
        $directory = $this -> _cleanString($directory);

        $this -> process_file = $this -> upload_path . $directory . $filename;
    }

    /**
     * current file fro processing
     *
     * @access public
     * @return string
     */
    public function getProcessedFilename()
    {
        return $this -> process_file;
    }

    /**
     * read filecontent for parsing
     *
     * @access public
     * @return string
     */
    public function parseCurrentXliffFile()
    {
        $this -> _readCurrentFile();

        if ( is_array($this -> currentXLIFF) AND count($this -> currentXLIFF) ) {
            return $this -> registry -> user_lang['xliff']['found_records'] . ': ' . count($this -> currentXLIFF['file']['unit']);
        }
        else {
            $this -> last_error = true;
            return $this -> registry -> user_lang['xliff']['xml_parser_error'];
        }
    }

    /**
     * Check if current uploaded file has dublicte UUIDs
     *
     * @access public
     * @return string
     */
    public function checkAllUuidInXliffFile()
    {
        $this -> _readCurrentFile();

        $result = array();

        if ( is_array($this -> currentXLIFF) AND count($this -> currentXLIFF) ) {
            $currentGameFile = $this -> currentXLIFF['file']['id'];
            $currentUuids    = $this -> _getAllUuidByFileFromGeneralTable($currentGameFile);
            $uplaodedUuids   = $this -> _getAllUuidsFromCurrentProcessedFile();
            $filteredUuids   = $this -> _removeAllSingleUuidsFromList($uplaodedUuids);

            // check if current data has duplicates
            if ( count($uplaodedUuids) ) {
                if ( count($filteredUuids) > 0 ) {
                    $this -> last_error = true;
                    $result[] = $this -> registry -> user_lang['xliff']['check_xliff_has_dublicate_uuids'];
                    $result[] = '<hr />';
                    $result[] = $this -> registry -> user_lang['xliff']['check_xliff_has_dublicate_uuids_count'] . ': ' . count($filteredUuids);
                    $result[] = $this -> registry -> user_lang['xliff']['check_xliff_has_dublicate_uuids_list']  . ':<br />' . implode(",<br />", $filteredUuids);

                    return implode("<br />", $result);

                }
                else {
                    $result[] = $this -> registry -> user_lang['xliff']['check_xliff_has_no_dublicate_uuids'];
                    $result[] = '<hr />';
                }
            }
            else {
                $this -> last_error = true;
                return $this -> registry -> user_lang['xliff']['check_xliff_has_no_uuids'];
            }

            // check if any UUID exists in Database
            $dublicates = 0;
            foreach( $uplaodedUuids AS $id => $uuid ) {
                $query = "SELECT `org_filename`, `label`, `linenumber`, `filename` FROM `xliff_general` WHERE `active` = 1 AND `uuid` = '" . $uuid . "'";
                $data  = $this -> registry -> db -> querySingleArray($query);

                // current UUID found but assinged file is diffrent
                if ( is_array($data) AND count($data) AND ($currentGameFile != $data['org_filename']) ) {
                    $result[] = $this -> registry -> user_lang['xliff']['check_xliff_found_uuid_in_database']   . ': ' . $uuid;
                    $result[] = $this -> registry -> user_lang['xliff']['check_xliff_found_uuid_file_id']       . ': ' . $currentGameFile;
                    $result[] = $this -> registry -> user_lang['xliff']['check_xliff_found_uuid_file_database'] . ': ' . $data['org_filename'];
                    $result[] = $this -> registry -> user_lang['xliff']['check_xliff_found_uuid_label']         . ': ' . $data['label'];
                    $result[] = $this -> registry -> user_lang['xliff']['check_xliff_found_uuid_linenumber']    . ': ' . $data['linenumber'];
                    $result[] = $this -> registry -> user_lang['xliff']['check_xliff_found_uuid_filename']      . ': ' . $data['filename'];
                    $result[] = '<hr />';

                    $dublicates++;

                    if ( !$this -> last_error ) {
                        // set error while dublicates found
                        $this -> last_error = true;
                    }
                }
            }
            if ( !$this -> last_error ) {
                $result[] = $this -> registry -> user_lang['xliff']['check_xliff_database_check_no_dublicate_uuids'];
            }
            else {
                $result[] = $this -> registry -> user_lang['xliff']['check_xliff_database_check_found_dublicate_uuids'] . ': ' . $dublicates;
            }

            return implode("<br />", $result);
        }
        else {
            $this -> last_error = true;
            return $this -> registry -> user_lang['xliff']['xml_parser_error'];
        }
    }


    /**
     * processing current XLIFF-File
     *
     * @access public
     * @param  bool      save translation
     * @return string
     */
    public function processCurrentXliffFile($saveTranslation = false)
    {
        $this -> _readCurrentFile();

        if ( is_array($this -> currentXLIFF) AND count($this -> currentXLIFF) ) {
            $currentGameFile = $this -> currentXLIFF['file']['id'];
            $currentUuids    = $this -> _getAllUuidByFileFromGeneralTable($currentGameFile);

            $ugStrings = 0;  // counter for update existing General String
            $igStrings = 0;  // counter for insert new General String

            $oStrings = 0;   // Counter for insert Original String

            $tStrings = 0;   // Counter for insert Translating Strings
            $uStrings = 0;   // Counter for update Translating Strings

            $errorCnt = 0;   // Error-Counter

            foreach( $this -> currentXLIFF['file']['unit'] AS $id => $data ) {
                $general  = array();
                $original = array();

                $mulitSegment = false;
                $breakForeach = false;

                if ( is_string($data) AND strlen($data) == 36 ) {
                    // Only one Record in XLIFF-File
                    $breakForeach = true;
                    $data = $this -> currentXLIFF['file']['unit'];
                }

                // General-Data
                $this -> _getGeneralData($data, $general);
                $general['org_filename'] = $currentGameFile;

                if ( !array_key_exists($general['uuid'], $currentUuids) ) {
                    $this -> registry -> db -> insertRow($general, 'xliff_general');
                    $generalId = $this -> registry -> db -> insertID();
                    $igStrings++;
                }
                else {
                    // Update current UUID (Changes?)
                    $this -> registry -> db -> updateRow($general, 'xliff_general', "`uuid` = '" . $general['uuid'] . "'");
                    $generalId = $currentUuids[$general['uuid']];
                    $ugStrings++;

                    // remove current UUID
                    unset($currentUuids[$general['uuid']]);
                }

                $currentOriginals = $this -> _getAllMd5HasFromGeneralByUuid($general['uuid']);

                // Original-Data
                $this -> _getOriginalData($data, $original, $mulitSegment, $generalId, $general['uuid']);

                $searchKey = $general['uuid'] . ':' . $original['md5hash'];
                if ( !array_key_exists($searchKey, $currentOriginals) ) {
                    $this -> registry -> db -> insertRow($original, 'xliff_original');
                    $originalId = $this -> registry -> db -> insertID();
                    $oStrings++;
                }
                else {
                    $originalId = $currentOriginals[$searchKey];

                    // removed current Original
                    unset($currentOriginals[$searchKey]);
                }

                // save translations
                if ( $saveTranslation == true ) {
                    $currentTranslate = $this -> _getAllTranslationsByFilter($general['uuid'], $generalId, $originalId);

                    if ( $mulitSegment == false ) {
                        // Single-Segment
                        if ( isset($data['segment']['target']) AND is_array($data['segment']['target']) ) {
                            // Check if Target set
                            foreach( $data['segment']['target'] AS $id => $translate ) {
                                $translation = array();
                                $multiTarget = true;

                                if ( isset($translate['xml:lang']) ) {
                                    $currentTranslat = $translate;
                                }
                                else {
                                    $currentTranslat = $data['segment']['target'];
                                    $multiTarget = false;
                                }

                                $destLang = $this -> languages[ $currentTranslat['xml:lang'] ];

                                $translation['general']    = $generalId;
                                $translation['original']   = $originalId;
                                $translation['uuid']       = $general['uuid'];
                                $translation['language']   = $destLang;
                                $translation['translatet'] = $currentTranslat['value'];

                                $found = $this -> findTranslationByParameters($generalId, $originalId, $general['uuid'], $destLang);
                                if ( $found === false ) {
                                    // insert new translation
                                    $this -> registry -> db -> insertRow($translation, 'xliff_translate');
                                    $tStrings++;
                                }
                                else {
                                    // strings are diffrent and new string is not empty
                                    if ( ($found['translatet'] != $translation['translatet']) AND strlen($translation['translatet']) ) {
                                        $this -> registry -> db -> updateRow($translation, 'xliff_translate', "`translate_id` = " . $found['translate_id']);
                                        $uStrings++;
                                    }

                                    // remove UUID from list
                                    unset( $currentUuids[$general['uuid']] );
                                }

                                if ( $multiTarget === false ) {
                                    // exit, while no more translations
                                    break;
                                }
                            }
                        }
                        else {
                            // no insert empty strings
                        }
                    }
                    else {
                        // Multi-Segment
                        if ( isset($data['segment'][0]['target']) AND is_array($data['segment'][0]['target']) ) {
                            $segmentCount = count($data['segment']);
                            try {
                                foreach( $data['segment'][0]['target'] AS $id => $translate ) {
                                    $translation = array();

                                    // check if the segment has multiple translations
                                    if ( is_array($translate) ) {
                                        $destLang = $this -> languages[ $translate['xml:lang'] ];
                                    }
                                    else {
                                        // ISO-Language-Code has 3 or less characters
                                        if ( strlen($translate) <= 3 ) {
                                            $destLang = $this -> languages[$translate];
                                        }
                                    }

                                    // Collect all Segments for combine
                                    $translationSegments = array();
                                    for( $i = 0; $i < $segmentCount; $i++ ) {
                                        // check if segment hat single translation
                                        if ( isset($data['segment'][$i]['target']['value']) ) {
                                            $translationSegments[] = $data['segment'][$i]['target']['value'];
                                        }
                                        else {
                                            $translationSegments[] = $data['segment'][$i]['target'][$id]['value'];
                                        }
                                    }

                                    $translation['general']    = $generalId;
                                    $translation['original']   = $originalId;
                                    $translation['uuid']       = $general['uuid'];
                                    $translation['language']   = $destLang;
                                    $translation['translatet'] = implode("\n", $translationSegments);

                                    $found = $this -> findTranslationByParameters($generalId, $originalId, $general['uuid'], $destLang);
                                    if ( $found === false ) {
                                        // insert new translation
                                        $this -> registry -> db -> insertRow($translation, 'xliff_translate');
                                        $tStrings++;
                                    }
                                    else {
                                        // strings are diffrent and new string is not empty
                                        if ( ($found['translatet'] != $translation['translatet']) AND strlen($translation['translatet']) ) {
                                            $this -> registry -> db -> updateRow($translation, 'xliff_translate', "`translate_id` = " . $found['translate_id']);
                                            $uStrings++;
                                        }

                                        // remove UUID from list
                                        unset( $currentUuids[$general['uuid']] );
                                    }
                                }
                            }
                            catch (Throwable $t) {
                                $errorCnt++;

                                $data1 = gettype($data)              . ' :: ' . var_export($data, true);
                                $data2 = gettype($translate)         . ' :: ' . var_export($translate, true);
                                $data3 = gettype($destLang)          . ' :: ' . var_export($destLang, true);
                                $data4 = gettype($this -> languages) . ' :: ' . var_export($this -> languages, true);
                                $data5 = gettype($translation)       . ' :: ' . var_export($translation, true);

                                $logMassage = array(
                                                  'Throwable: ' . $t -> getMessage(),
                                                  'Code: '      . $t -> getCode(),
                                                  'Line: '      . $t -> getLine(),
                                                  'Trace: '     . $t -> getTraceAsString(),
                                                  '$data:',
                                                  $data1,
                                                  '$translate:' . $data2,
                                                  '$destLang:'  . $data3,
                                                  '$this -> languages:',
                                                  $data4,
                                                  '$translation',
                                                  $data5,
                                              );

                                new Logging('xliff_process_error', implode("\n", $logMassage));
                            }
                        }
                        else {
                            // no insert empty strings
                        }
                    }
                }
                // end if translation saved

                if ( $breakForeach == true ) {
                    // beakt, if is Single-Segment
                    break;
                }
            }

            if ( count($currentUuids) ) {
                // deactivate all remaining UUID-entries
                $this -> _disableRemainingUuids($currentUuids, $currentGameFile);
            }

            if ( $saveTranslation == true ) {
                $saving = $this -> registry -> user_lang['global']['status_yes'];
            }
            else {
                $saving = $this -> registry -> user_lang['global']['status_no'];
            }

            $result = $this -> registry -> user_lang['xliff']['import_done']                                         . '<br />' .
                      $this -> registry -> user_lang['xliff']['import_count_error']              . ': ' . $errorCnt  . '<br />' .
                      $this -> registry -> user_lang['xliff']['import_count_general']            . ': ' . $igStrings . '<br />' .
                      $this -> registry -> user_lang['xliff']['update_count_general']            . ': ' . $ugStrings . '<br />' .
                      $this -> registry -> user_lang['xliff']['import_count_strings']            . ': ' . $oStrings  . '<br />' .
                      $this -> registry -> user_lang['xliff']['import_processing_translation']   . ': ' . $saving    . '<br />' .
                      $this -> registry -> user_lang['xliff']['import_count_translations']       . ': ' . $tStrings  . '<br />' .
                      $this -> registry -> user_lang['xliff']['import_count_update_translation'] . ': ' . $uStrings  . '<br />' .
                      $this -> registry -> user_lang['xliff']['import_count_string_disable']     . ': ' . count($currentUuids);

            return $result;
        }
        else {
            $this -> last_error = true;
            return $this -> registry -> user_lang['xliff']['xml_parser_error'];
        }
    }






    /********************************************************/
    /***************** Private Funtions *********************/
    /********************************************************/

    /**
     * sreach translation by specify parameters
     *
     * @access private
     * @param  interger    ID from General-Table
     * @param  integer     ID from Original-Table
     * @param  string      UUID
     * @param  integer     LanguageID
     * @return boool|array
     */
    private function findTranslationByParameters($generalID, $originalID, $uuid, $languageID)
    {
        $query = "SELECT `translate_id`, `translatet` FROM `xliff_translate` " .
                 "WHERE `general` = " . $generalID . " AND `original` = " . $originalID . " " .
                   "AND `uuid` = '" . $uuid . "' AND `language` = " . $languageID;
        $data = $this -> registry -> db -> querySingleArray($query);

        if ( is_array($data) AND count($data) ) {
            return array(
                       'translate_id' => $data['translate_id'],
                       'translatet'   => $data['translatet'],
                   );
        }
        else {
            return false;
        }
    }

    /**
     * collect all UUIDs from curret XLIFF-File
     *
     * @access private
     * @retrun array
     */
    private function _getAllUuidsFromCurrentProcessedFile()
    {
        $uuids = array();

        if ( is_array($this -> currentXLIFF) AND count($this -> currentXLIFF) ) {
            if ( is_string($this -> currentXLIFF['file']['unit']) ) {
                // Only one Record in XLIFF-File
                $uuids[] = $this -> currentXLIFF['file']['unit'];
            }
            else {
                foreach( $this -> currentXLIFF['file']['unit'] AS $key => $value ) {
                    if ( is_string($value) AND (strlen($value) == 36) ) {
                        if ( !array_key_exists($value, $uuids) ) {
                            $uuids[$value] = 1;
                        }
                        else {
                            $uuids[$value]++;
                        }
                    }
                    else {
                        // check, if "notes" exists"
                        if ( isset($value['notes']) ) {
                            if ( is_array($value['notes']) AND is_array($value['notes']['note']) AND count($value['notes']['note']) ) {
                                foreach( $value['notes']['note'] AS $id => $data ) {
                                    if ( $data['category'] == 'uuid' ) {
                                        if ( !array_key_exists($data['value'], $uuids) ) {
                                            $uuids[$data['value']] = 1;
                                        }
                                        else {
                                            $uuids[$data['value']]++;
                                        }
                                        break 1;
                                    }
                                }
                                // end foreach all notes
                            }
                        }
                    }
                    // end processing single or mulit
                }
            }
            // ed if file has one record
        }

        return $uuids;
    }

    /**
     * Remove all UUIDs with 1 using in current File
     *
     * @access private
     * @param  array     UUIDs with her count in File
     * @return array
     */
    private function _removeAllSingleUuidsFromList($uuids)
    {
        $tmp = array();

        if ( count($uuids) ) {
            foreach( $uuids AS $uuid => $count ) {
                if ( $count > 1 ) {
                    $tmp[] = $uuid . ' (' . $count . 'x)';
                }
            }
        }

        return $tmp;
    }

    /**
     * get General-Data
     *
     * @access private
     * @param  array      Data-Array from XLIFF
     * @param  array      Array forGeneral-Data
     */
    private function _getGeneralData($data, &$general)
    {
        // General string information
        foreach( $data['notes']['note'] AS $id => $common ) {
            $general[$common['category']] = $common['value'];
        }

        // TODO :: some entrys has no Emotes
        if ( strpos($general['comment'], '"') !== false ) {
            $segments = explode(" ", $general['comment']);
            if ( strlen($segments[1]) == 2 ) {
                $general['person'] = $segments[0];
                $general['emote']  = $segments[1];
            }
            unset($segments);
        }
        else {
            $general['person'] = '';
            $general['emote']  = '';
        }

        if ( isset($data['ignorable']) and count($data['ignorable']) ) {
            $general['ignorable'] = serialize($data['ignorable']);

            // old system -- removed
            if ( isset($data['ignorable']['source']) ) {
                $general['igno_start'] = '';
                $general['igno_end']   = ( strlen($data['ignorable']['source']) ? $data['ignorable']['source'] : '' );
            }
            else {
                $general['igno_start'] = ( strlen($data['ignorable'][0]['source']) ? $data['ignorable'][0]['source'] : '' );
                $general['igno_end']   = ( strlen($data['ignorable'][1]['source']) ? $data['ignorable'][1]['source'] : '' );
            }
        }
        else {
            $general['ignorable'] = '';

            // old system -- removed
            $general['igno_start'] = '';
            $general['igno_end']   = '';
        }

        if ( !isset($general['label']) ) {
            $general['label'] = '';
        }
        $general['active'] = true;
    }

    /**
     * get Original-Data
     *
     * @access private
     * @param  array      Data-Array from XLIFF
     * @param  array      Array for Original-Data
     * @param  boolean    if Sinlge-Segment or Multible
     * @param  integer    ID from General-String
     * @param  string     UUID from General
     */
    private function _getOriginalData($data, &$original, &$mulitSegment, $generalId, $generalUuid)
    {
        if ( isset($data['segment']['source']) ) {
            // Single-Line of text
            $mulitSegment = false;

            $original['source']  = $data['segment']['source'];
            $original['md5hash'] = md5($data['segment']['source']);
            $original['uuid']    = $generalUuid;
            $original['general'] = $generalId;
        }
        else {
            // Multi-Line of text
            $mulitSegment = true;
            $sourceArray = array();

            foreach( $data['segment'] AS $key => $line ) {
                $sourceArray[] = $line['source'];
            }
            $sourceString = implode("\n", $sourceArray);

            $original['source']  = $sourceString;
            $original['md5hash'] = md5($sourceString);
            $original['uuid']    = $generalUuid;
            $original['general'] = $generalId;
        }
    }

    /**
     * get all translations from Table
     *
     * @access private
     * @param  string     current UUID
     * @param  integer    current ID from General-String
     * @param  integer    current ID from Original-String
     * @return array
     */
    private function _getAllTranslationsByFilter($uuid, $generalId, $originalId)
    {
        $query = "SELECT `translate_id`, `language` FROM `xliff_translate` WHERE `uuid` = '" . $uuid .
                 "' AND `general` = " . $generalId . " AND `original` = " . $originalId;
        $data  = $this -> registry -> db -> queryObjectArray($query);
        $return = array();

        if ( is_array($data) AND count($data) ) {
            foreach( $data AS $key => $value ) {
                $newKey = $uuid . ':' . $value['language'];
                $return[$newKey] = $value['translate_id'];
            }
        }

        return $return;
    }

    /**
     * get all MD5-Hashes for UUIDs
     *
     * @access private
     * @param  string     current UUID
     * @return array
     */
    private function _getAllMd5HasFromGeneralByUuid($uuid)
    {
        $query = "SELECT `md5hash`, `original_id` FROM `xliff_original` WHERE `uuid` = '" . trim($uuid) . "'";
        $data  = $this -> registry -> db -> queryObjectArray($query);
        $return = array();

        if ( is_array($data) AND count($data) ) {
            foreach( $data AS $key => $value ) {
                $newKey = $uuid . ':' . $value['md5hash'];
                $return[$newKey] = $value['original_id'];
            }
        }

        return $return;
    }

    /**
     * get all active UUIDs
     *
     * @access private
     * @param  string     current Filename
     * @return array
     */
    private function _getAllUuidByFileFromGeneralTable($currentFileName)
    {
        $query = "SELECT `uuid`, `general_id` FROM `xliff_general` WHERE `active` = 1 " .
                 "AND `org_filename` = '" . trim($currentFileName) . "'";
        $data  = $this -> registry -> db -> queryObjectArray($query);
        $return = array();

        if ( is_array($data) AND count($data) ) {
            foreach( $data AS $key => $value ) {
                $return[$value['uuid']] = $value['general_id'];
            }
        }

        return $return;
    }

    /**
     * deactivete all remaining UUIDs
     *
     * @access private
     * @param  array     remaining UUIDs
     * @param  string    current Filename
     * @return boolean|integer
     */
    private function _disableRemainingUuids($currentUuids, $currentFileName)
    {
        $query = "UPDATE `xliff_general` SET `active` = 0 WHERE `uuid` IN ('" .
                 implode( "', '", array_keys($currentUuids) ) .
                 "') AND `org_filename` = '" . trim($currentFileName) . "';";
        return $this -> registry -> db -> execute($query);
    }

    /**
     * add Language-Mapper
     *
     * @access private
     */
    private function _addLanguageMapper()
    {
        $this -> languages['en'] = $this -> languages['us'];
        $this -> languages['gb'] = $this -> languages['us'];
        $this -> languages['sl'] = $this -> languages['sk'];
        $this -> languages['zh'] = $this -> languages['cn'];
    }

    /**
     * process current XLIFF as XML
     *
     * @access private
     */
    private function _readCurrentFile()
    {
        $this -> currentXLIFF = read_xml( $this -> process_file );
    }

    /**
     * remove critical Path-Strings
     *
     * @access private
     * @param  string    Path or Filename
     * @return string
     */
    private function _cleanString($string)
    {
        return str_replace(array('\\..', '\\.', '/..', '/.'), '', trim($string));
    }
}