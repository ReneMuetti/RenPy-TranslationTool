<?php
class Translation
{
    private $registry;
    private $renderer;

    private $languageStatus = null;
    private $openTranslation = null;
    private $languageCodesID = null;

    private $imageExt     = '.webp';
    private $imageSubPath = 'skin/images/emotes/';
    private $imageDefault = 'skin/images/default.png';

    private $languageSepearor = ',';

    public function __construct()
    {
        global $website, $renderer;

        $this -> registry = $website;
        $this -> renderer = $renderer;
    }

    public function __destruct()
    {
        unset($this -> registry);
        unset($this -> renderer);
    }

    public function getLanguageSepearor()
    {
        return $this -> languageSepearor;
    }

    /**
     * generate translation form
     *
     * @access public
     * @return string
     */
    public function getAjaxTranslationForm()
    {
        $currentSelection = unserialize( stripslashes($this -> registry -> userinfo['translation']) );
        $langs = new Languages();
        $langIds = $langs -> getLanguagesByCode();

        $canSave = false;

        if( is_array($currentSelection) AND count($currentSelection) ) {
            $blocks = array();
            $destLangs = array();
            $showLangs = array();
            $destIDs = array();

            foreach( $currentSelection AS $code => $settings ) {
                if ( isset($settings['edit']) AND ($settings['edit'] == 1) ) {
                    // Input-Field for Translation
                    $this -> renderer -> loadTemplate('translation' . DS . 'ajax_edit.htm');
                        $this -> renderer -> setVariable('lang_code', $code);
                    $blocks[] = $this -> renderer -> renderTemplate();

                    $destLangs[] = $code;
                    $destIDs[] = $langIds[$code];

                    $canSave = true;
                }
                elseif ( isset($settings['view']) AND ($settings['view'] == 1) ) {
                    // Pseudo-Input for view
                    $this -> renderer -> loadTemplate('translation' . DS . 'ajax_pseudo.htm');
                        $this -> renderer -> setVariable('lang_code', $code);
                    $blocks[] = $this -> renderer -> renderTemplate();

                    $showLangs[] = $code;
                }
            }

            if ( count($blocks) ) {
                $transBlocks = implode("\n", $blocks);
            }
            else {
                $transBlocks = $this -> registry -> user_lang['index']['no_language_seceted'];
            }

            if ( $canSave === true ) {
                // show save-element
                $this -> renderer -> loadTemplate('translation' . DS . 'ajax_save.htm');
                $save_element = $this -> renderer -> renderTemplate();
            }
            else {
                $save_element = '';
            }

            $this -> renderer -> loadTemplate('translation' . DS . 'ajax_form.htm');
                $this -> renderer -> setVariable('dest_langs'            , implode($this -> languageSepearor, $destLangs));
                $this -> renderer -> setVariable('show_langs'            , implode($this -> languageSepearor, $showLangs));
                $this -> renderer -> setVariable('save_block'            , $save_element);
                $this -> renderer -> setVariable('trans_blocks'          , $transBlocks);
                $this -> renderer -> setVariable('current_lang_sepeartor', $this -> languageSepearor);
            return $this -> renderer -> renderTemplate();
        }
        else {
            return $this -> registry -> user_lang['index']['no_language_seceted'];
        }
    }

    /**
     * update single translation from detail-page
     *
     * @access public
     * @param  string      new translation text
     * @param  string      UUID
     * @param  integer     Original-ID
     * @param  integer     Translation-ID
     * @return array
     */
    public function updateSingleTranslation($newTranslation, $uuid, $originalID, $translationID)
    {
        $query = "SELECT * FROM `xliff_translate` WHERE `translate_id` = " . $translationID;
        $data  = $this -> registry -> db -> querySingleArray($query);

        if ( is_array($data) AND count($data) ) {
            if ( ($data['uuid'] == $uuid) AND ($data['original'] == $originalID) ) {
                // save translation
                $this -> _updateTranslationIntoDatabase($data['general'], $originalID, $uuid, $data['language'], $newTranslation, $translationID);
                // save history-data
                $this -> _saveHistoryData($data['language'], $uuid, 'update', $data['translatet'], $newTranslation, $translationID);

                return array(
                           'error'   => false,
                           'data'    => $this -> registry -> user_lang['translation']['details_ajax_updatet_datetime'] . ': ' . date("Y-m-d H:i:s"),
                           'message' => $this -> registry -> user_lang['translation']['details_ajax_translation_updatet'],
                       );
            }
            else {
                return array(
                           'error'   => true,
                           'data'    => '',
                           'message' => $this -> registry -> user_lang['translation']['details_ajax_uuid_is_different'],
                       );
            }
        }
        else {
            return array(
                       'error'   => true,
                       'data'    => '',
                       'message' => $this -> registry -> user_lang['translation']['details_ajax_no_translation_from_id'],
                   );
        }
    }

    /**
     * get all Translation from Database by Game-File-Name
     *
     * @access public
     * @param  string     filename
     * @param integer     language ID
     */
    public function getTranslationFromFileByLanguage($filename, $language)
    {
        $query = "SELECT `xliff_translate`.*, " .
                        "`xliff_original`.`source`, " .
                        "`xliff_general`.`linenumber`, `xliff_general`.`person`, `xliff_general`.`emote`, `xliff_general`.`comment`, `xliff_general`.`ignorable` " .
                 "FROM `xliff_translate` " .
                 "LEFT JOIN `xliff_original` ON (`xliff_translate`.`original` = `xliff_original`.`original_id`) " .
                 "LEFT JOIN `xliff_general` ON (`xliff_translate`.`general` = `xliff_general`.`general_id`) " .
                 "WHERE `xliff_translate`.`uuid` IN (SELECT `uuid` FROM `xliff_general` WHERE `org_filename` = '" . $filename . "' AND `active` = 1) " .
                   "AND `xliff_translate`.`language` = " . $language . " " .
                 "ORDER BY `xliff_general`.`linenumber` ASC;";
        $data = $this -> registry -> db -> queryObjectArray($query);

        $blocks = array();
        if ( is_array($data) AND count($data) ) {
            $this -> _generateBlocksForInlineTranslations($blocks, $data);
        }
        else {
            $blocks[] = $this -> registry -> user_lang['translation']['details_ajax_no_result_for_request'];
        }

        return implode("\n", $blocks);
    }

    /**
     * find translations by search pattern
     *
     * @access public
     * @param  string     search string in translation
     * @param  integer    Language-ID
     * @return string
     */
    public function getTranslationFromSearchPatterns($searchPattern, $language)
    {
        $searchPattern = $this -> registry -> db -> escapeString($searchPattern);
        $replaceWhere = 'REPLACE(`xliff_translate`.`translatet`, "\\\", \'\')';

        $query = "SELECT `xliff_translate`.*, " .
                        "`xliff_original`.`source`, " .
                        "`xliff_general`.`linenumber`, `xliff_general`.`person`, `xliff_general`.`emote`, `xliff_general`.`comment`, `xliff_general`.`ignorable` " .
                 "FROM `xliff_translate` " .
                 "LEFT JOIN `xliff_original` ON (`xliff_translate`.`original` = `xliff_original`.`original_id`) " .
                 "LEFT JOIN `xliff_general` ON (`xliff_translate`.`general` = `xliff_general`.`general_id`) " .
                "WHERE " . $replaceWhere . " LIKE '%" . $searchPattern . "%' " .
                 ( ($language >= 1) ? "AND `xliff_translate`.`language` = " . $language . " " : '' ) .
                 "ORDER BY `xliff_general`.`linenumber` ASC;";
        $data = $this -> registry -> db -> queryObjectArray($query);

        $blocks = array();
        if ( is_array($data) AND count($data) ) {
            $this -> _generateBlocksForInlineTranslations($blocks, $data);
        }
        else {
            $blocks[] = $this -> registry -> user_lang['translation']['search_no_result_for_request'];
        }
        return implode("\n", $blocks);
    }

    /**
     * Get first string to translation from Total-List
     *
     * @access   public
     * @param    string      UUID
     * @param    string      Comma list with language codes for translations
     * @param    string      Comma list with language codes for show
     * @param    boolean     show strings from common.rpy
     * @return   array
     */
    public function getNewStringForTranslation($lastUUID, $translationLangs, $showLangs, $common)
    {
        $result = array();
        $status = array();

        $insertSource = true;
        $currentUUID  = '';

        $translationIds = $this -> _getIdsFromLanguageCodes($translationLangs);

        // find all open translations
        $this -> _getAllUntranslatedStrings($translationIds, $common);

        if ( is_array($this -> openTranslation) AND count($this -> openTranslation) ) {
            foreach( $translationIds AS $id ) {
                $langIsoCode = $this -> languageCodesID[$id];

                foreach( $this -> openTranslation[$id] AS $uuid => $data ) {
                    if ( ($uuid != $lastUUID) AND
                         ( is_null($data['translatet']) OR
                         ( is_string($data['translatet']) AND !strlen($data['translatet']) )
                         )
                       ) {
                        $currentUUID = $uuid;

                        if ( $insertSource === true ) {
                            $status[] = '<div class="status status-fail">' . $this -> registry -> user_lang['translation']['ajax_translation_inset_linebrak_manual'] . '</div>';

                            $imagename = $this -> _getImageNameFromCharacter($data['person'], $data['emote']);

                            if ( strpos($data['ignorable'], '{') !== false ) {
                                $sourceString = $this -> _transformStringToHtmlOutput($data['comment']);
                            }
                            else {
                                $sourceString = $this -> _transformStringToHtmlOutput($data['source']);
                            }

                            $result['general_id']  = $data['general_id'];
                            $result['original_id'] = $data['original_id'];
                            $result['uuid']        = $data['uuid'];
                            $result['source']      = $sourceString;
                            $result['filename']    = $data['filename'];
                            $result['imagename']   = $imagename;

                            $insertSource = false;

                            $status[] = $this -> registry -> user_lang['translation']['ajax_translation_open_translation'] . ': ' .
                                        count($this -> openTranslation[$id]) - 1 .
                                        ' (' . $this -> registry -> user_lang['languages'][$langIsoCode] . ')';

                            // search translation with same MD5-Hash
                            $foundTranslation = $this -> _searchTranslationWithMd5FromLanguage($data['original_md5'], $id);
                            if ( is_string($foundTranslation) AND strlen($foundTranslation) ) {
                                $result[$langIsoCode] = $foundTranslation;

                                $status[] = '<div class="status status-fail">' . $this -> registry -> user_lang['translation']['ajax_translation_found_translation_with_md5'] . '</div>';
                            }
                            else {
                                $result[$langIsoCode] = '';
                            }
                        }

                        // Exit foreach
                        break;
                    }
                }

                $status[] = $this -> registry -> user_lang['translation']['ajax_translation_filename'] . ': ' .
                            ( isset($result['filename']) ? $result['filename'] : 'unknown' );

                // add elements for return
                $result['status'] = implode("<br />", $status);
                $result['done']   = false;

                if ( !array_key_exists($currentUUID, $this -> openTranslation[$id]) ) {
                    // UUID not found in current Data => text already translated
                    $result[$langIsoCode] = $this -> _getTranslatetStringFromDatabase($uuid, $id);
                }
            }
        }
        else {
            return array(
                       'done'    => true,
                       'message' => $this -> registry -> user_lang['translation']['ajax_translation_finish'],
                   );
        }


        // load all translations, which are only observed
        $showLangIds = $this -> _getIdsFromLanguageCodes($showLangs);
        foreach( $showLangIds AS $id ) {
            $langIsoCode  = $this -> languageCodesID[$id];
            $searchString = $this -> _getTranslatetStringFromDatabase($uuid, $id);

            if ( $searchString == -1 ) {
                $result[$langIsoCode] = $this -> registry -> user_lang['translation']['ajax_translation_not_found'];
            }
            elseif ( $searchString == -2 ) {
                $result[$langIsoCode] = $this -> registry -> user_lang['translation']['ajax_translation_error_params'];
            }
            else {
                $result[$langIsoCode] = $this -> _transformStringToHtmlOutput($searchString);
                $result[$langIsoCode] = str_replace( array('.r<br />', '.r<br />\n'), '.<br />', $result[$langIsoCode]);
            }
        }

        return $result;
    }

    /**
     * Insert new Translation
     *
     * @access public
     * @param  array       Post-Data
     *
     * @return array
     */
    public function saveNewTranslation($postData)
    {
        $return = array(
                      'error'   => false,
                      'message' => '',
                      'data'    => '',
                  );

        $query = "SELECT `md5hash`, `general` AS `general_id`, `original_id` FROM `xliff_original` WHERE `uuid` = '" . $postData['lastUUID'] . "';";
        $currData = $this -> registry -> db -> querySingleArray($query);

        $langs = new Languages();
        $langIds = $langs -> getLanguagesByCode();

        if ( is_array($currData) AND count($currData) ) {
            // Data from UUID found
            if ( strpos($postData['languages'], $this -> languageSepearor) ) {
                // multible translation
                $tmp = explode($this -> languageSepearor, $postData['languages']);

                foreach( $tmp AS $iso_code ) {
                    $found = $this -> _getTranslationIdFromDatabase($postData['lastUUID'], $langIds[$iso_code]);

                    if ( is_null($found) OR ($found <= 0) ) {
                        $newTranslation = $postData['destination-' . $iso_code];
                        if ( strlen($newTranslation) ) {
                            // save in translation-table
                            $insertID = $this -> _saveTranslationIntoDatabase($currData['general_id'], $currData['original_id'], $postData['lastUUID'], $langIds[$iso_code], $newTranslation);
                            $return['message'] = $this -> registry -> user_lang['translation']['ajax_translation_success_insert_database'];

                            if ( $insertID > 0 ) {
                                // save history-data
                                $this -> _saveHistoryData($iso_code, $postData['lastUUID'], 'insert', '', $newTranslation, $insertID);
                            }
                            else {
                                $return['error']   = true;
                                $return['message'] = $this -> registry -> user_lang['translation']['ajax_translation_error_insert_database'];
                                return $return;
                            }

                            if ( isset($postData['allMD5']) AND ($postData['allMD5'] == true) ) {
                                // Save all Strings with the same MD5-Hash
                            }
                        }
                    }
                    else {
                        // check, if translation is empty
                        $string = $this -> _getTranslatetStringFromDatabase($postData['lastUUID'], $langIds[$iso_code], $found);

                        if ( !strlen($string) OR (strlen($string) > 1) ) {
                            // save in translation-table
                            $this -> _updateTranslationIntoDatabase($currData['general_id'], $currData['original_id'], $postData['lastUUID'], $langIds[$iso_code], $newTranslation, $found);
                            $return['message'] = $this -> registry -> user_lang['translation']['ajax_translation_success_insert_database'];

                            // save history-data
                            $this -> _saveHistoryData($iso_code, $postData['lastUUID'], 'update', $string, $newTranslation, $found);
                        }
                        else {
                            if ( $string == -1 ) {
                                $return['message'] = $this -> registry -> user_lang['translation']['ajax_translation_not_found'];
                            }
                            elseif ( $string == -2 ) {
                                $return['message'] = $this -> registry -> user_lang['translation']['ajax_translation_error_params'];
                            }
                            else {
                                $return['message'] = '(' . $found . ') ' . $this -> registry -> user_lang['translation']['ajax_translation_error_translation_exsist'];
                            }

                            $return['error'] = true;
                            return $return;
                        }
                    }
                }
            }
            else {
                // only one translation
                $found = $this -> _getTranslationIdFromDatabase($postData['lastUUID'], $langIds[$postData['languages']]);

                if ( is_null($found) OR ($found <= 0) ) {
                    $newTranslation = $postData['destination-' . $postData['languages']];
                    if ( strlen($newTranslation) ) {
                        // save in translation-table
                        $insertID = $this -> _saveTranslationIntoDatabase($currData['general_id'], $currData['original_id'], $postData['lastUUID'], $langIds[$postData['languages']], $newTranslation);
                        $return['message'] = $this -> registry -> user_lang['translation']['ajax_translation_success_insert_database'];

                        if ( $insertID > 0 ) {
                            // save history-data
                            $this -> _saveHistoryData($postData['languages'], $postData['lastUUID'], 'insert', '', $newTranslation, $insertID);
                        }
                        else {
                            $return['error']   = true;
                            $return['message'] = $this -> registry -> user_lang['translation']['ajax_translation_error_insert_database'];
                        }
                    }
                }
                else {
                    // check, if translation is empty
                    $string = $this -> _getTranslatetStringFromDatabase($postData['lastUUID'], $langIds[$postData['languages']], $found);

                    if ( !strlen($string) OR (strlen($string) > 1) ) {
                        // save in translation-table
                        $newTranslation = $postData['destination-' . $postData['languages']];
                        $this -> _updateTranslationIntoDatabase($currData['general_id'], $currData['original_id'], $postData['lastUUID'], $langIds[$postData['languages']], $newTranslation, $found);
                        $return['message'] = $this -> registry -> user_lang['translation']['ajax_translation_success_insert_database'];

                        // save history-data
                        $this -> _saveHistoryData($postData['languages'], $postData['lastUUID'], 'update', $string, $newTranslation, $found);
                    }
                    else {
                        if ( $string == -1 ) {
                            $return['message'] = $this -> registry -> user_lang['translation']['ajax_translation_not_found'];
                        }
                        elseif ( $string == -2 ) {
                            $return['message'] = $this -> registry -> user_lang['translation']['ajax_translation_error_params'];
                        }
                        else {
                            $return['message'] = '(' . $found . ') ' . $this -> registry -> user_lang['translation']['ajax_translation_error_translation_exsist'];
                        }

                        $return['error'] = true;

                        return $return;
                    }
                }

            }
        }
        else {
            // Data from UUID missing
            $return['error']   = true;
            $return['message'] = $this -> registry -> user_lang['translation']['ajax_translation_error_uuid'];
        }

        return $return;
    }


    /**
     * generate Blocks from filter
     *
     * @access private
     * @param  array       returned HTML-Blocks
     * @param  array       filter-data from SQL
     */
    private function _generateBlocksForInlineTranslations(&$blocks, $data)
    {
        foreach( $data AS $key => $value ) {
            $imagename = $this -> _getImageNameFromCharacter($value['person'], $value['emote'], true);

            if ( strpos($value['ignorable'], '{') !== false ) {
                $sourceString = $this -> _transformStringToHtmlOutput($value['comment']);
            }
            else {
                $sourceString = $this -> _transformStringToHtmlOutput($value['source']);
            }

            $this -> renderer -> loadTemplate('details' . DS . 'line_from_file.htm');
                $this -> renderer -> setVariable('translate_id'  , $value['translate_id']);
                $this -> renderer -> setVariable('original_id'   , $value['original']);
                $this -> renderer -> setVariable('uuid'          , $value['uuid']);
                $this -> renderer -> setVariable('source_text'   , $sourceString );
                $this -> renderer -> setVariable('translate_text', $this -> _transformStringToHtmlOutput($value['translatet']) );
                $this -> renderer -> setVariable('char_image'    , $imagename);
            $blocks[] = $this -> renderer -> renderTemplate();
        }
    }

    /**
     * convert string for HTML-Output
     *
     * @access private
     * @param  string
     * @return string
     */
    private function _transformStringToHtmlOutput($string, $replaceBBCode = true)
    {
        $string = stripslashes($string);
        $string = nl2br($string);

        // convert BB-Code to HTML
        if ( $replaceBBCode === true ) {
            $string = str_replace(
                          array('{i}', '{/i}', '{b}', '{/b}', '{u}', '{/u}'),
                          array('<i>', '</i>', '<b>', '</b>', '<u>', '</u>'),
                          $string
                      );
        }

        return $string;
    }

    /**
     * search translatet string from MD5-Hash by UUID
     *
     * @access private
     * @param  string      MD5-Hash
     * @param  integer     Language-ID
     * @return bool@string
     */
    private function _searchTranslationWithMd5FromLanguage($md5_hash, $language)
    {
        $query   = "SELECT * FROM `xliff_original` WHERE md5hash = '" . $md5_hash . "' LIMIT 1";
        $md5data = $this -> registry -> db -> querySingleArray($query);

        if ( is_array($md5data) AND count($md5data) ) {
            $query     = "SELECT * FROM `xliff_translate` WHERE `uuid` = '" . $md5data['uuid'] . "' AND `language` = " . $language . ";";
            $foundData = $this -> registry -> db -> querySingleArray($query);

            if ( is_array($foundData) AND count($foundData) ) {
                return stripslashes($foundData['translatet']);
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }

    /**
     * get the current Image from character
     *
     * @access private
     * @param  string    name from character
     * @param  string    id from emote
     * @param  boolean   show full image path or imagename only
     * @return string
     */
    private function _getImageNameFromCharacter($person, $emote, $fullUrl = false)
    {
        if ( strlen($person) ) {
            $imagename = 'e' . strtolower($person) . '-' . $emote . $this -> imageExt;

            // reformat for Max
            $imagename = str_replace(array('etmax', 'epmax'), 'emax', $imagename);

            // reformat for Ann
            $imagename = str_replace(array('epann'), 'eann', $imagename);

            // reformat for Lisa
            $imagename = str_replace(array('eplisa'), 'elisa', $imagename);

            if ( $fullUrl === true ) {
                return $this -> registry -> config['Misc']['baseurl'] .
                       $this -> imageSubPath . $imagename;
            }
            else {
                return $imagename;
            }
        }
        else {
            if ( $fullUrl === true ) {
                return $this -> registry -> config['Misc']['baseurl'] .
                       $this -> imageDefault;
            }
            else {
                return '';
            }

        }
    }


    /**
     * save Edit-History
     *
     * @access private
     * @param  string|3    Laguage ISO-Code
     * @param  string      UUID
     * @param  string      insert|update
     * @param  string      old translation from Database
     * @param  string      translation to save
     * @param  integer     ID from Databes-Insert|Update
     */
    private function _saveHistoryData($isoCode, $uuid, $method, $oldString, $newTranslation, $insertID)
    {
        if ( is_integer($oldString) AND ($oldString <= 0) ) {
            $oldString = '';
        }

        if ( is_integer($isoCode) ) {
            $lang = new Languages();
            $isoCode = $lang -> getIsoCodeFromLanguageId($isoCode);
        }

        $insert = array(
                      'username'     => $this -> registry -> userinfo['username'],
                      'language'     => $isoCode,
                      'uuid'         => $uuid,
                      'method'       => $method,
                      'old_string'   => $oldString,
                      'new_string'   => $newTranslation,
                      'translate_id' => $insertID,
                  );
        $this -> registry -> db -> insertRow($insert, 'history');
    }

    /**
     * save new Translation to Database
     *
     * @access private
     * @param  integer    ID from xliff_general
     * @param  integer    IR from xliff_original
     * @param  string     UUID
     * @param  integer    ID from current ISO-Language
     * @param  string     translation to save
     *
     * @return integer    ID from Databes-Insert
     */
    private function _saveTranslationIntoDatabase($generalID, $originalID, $uuid, $langId, $newTranslation)
    {
        $insert = array(
                      'general'    => $generalID,
                      'original'   => $originalID,
                      'uuid'       => $uuid,
                      'language'   => $langId,
                      'translatet' => trim($newTranslation),
                  );
        $result = $this -> registry -> db -> insertRow($insert, 'xliff_translate');

        return $this -> registry -> db -> insertID();
    }

    /**
     * update existing Translation in Database
     *
     * @access private
     * @param  integer    ID from xliff_general
     * @param  integer    IR from xliff_original
     * @param  string     UUID
     * @param  integer    ID from current ISO-Language
     * @param  string     translation to save
     * @param  integer    ID from xliff_translate
     *
     * @return integer    ID from Databes-Insert
     */
    private function _updateTranslationIntoDatabase($generalID, $originalID, $uuid, $langId, $newTranslation, $updateID)
    {
        $update = array(
                      'general'    => $generalID,
                      'original'   => $originalID,
                      'uuid'       => $uuid,
                      'language'   => $langId,
                      'translatet' => trim($newTranslation),
                  );
        $this -> registry -> db -> updateRow($update, 'xliff_translate', 'WHERE `translate_id` = ' . $updateID);
    }

    /**
     * transform ISO-Code to Language-ID from Database
     *
     * @access private
     * @param  string|3    ISO-Code
     *
     * @return integer
     */
    private function _getIdsFromLanguageCodes($codes)
    {
        $ids = array();

        if ( strlen($codes) ) {
            $langs = new Languages();
            $langIds = $langs -> getLanguagesByCode();

            $this -> languageCodesID = array();

            if ( strpos($codes, $this -> languageSepearor) ) {
                $tmp = explode($this -> languageSepearor, $codes);
                foreach( $tmp AS $code ) {
                    $ids[] = $langIds[$code];

                    $this -> languageCodesID[$langIds[$code]] = $code;
                }
            }
            else {
                $ids[] = $langIds[$codes];

                $this -> languageCodesID[$langIds[$codes]] = $codes;
            }
        }

        return $ids;
    }

    /**
     * find all strings to be need translation
     *
     * @access private
     * @param  integer     ID from Language
     * @param  boolean     show strings from common,rpy
     */
    private function _getAllUntranslatedStrings($destLangs, $common)
    {
        if ( count($destLangs) ) {
            $this -> openTranslation = array();

            foreach( $destLangs AS $langId ) {
                // search all Entries, where is no exists
                $query = "SELECT `xliff_original`.*, " .
                                "`xliff_general`.`person`, `xliff_general`.`emote`, `xliff_general`.`org_filename`, `xliff_general`.`linenumber`, `xliff_general`.`comment`, `xliff_general`.`ignorable`, " .
                                "(SELECT `translatet` FROM `xliff_translate` WHERE `xliff_translate`.`uuid` = `xliff_original`.`uuid` AND `xliff_translate`.`language` = " . $langId . ") AS `translatet` " .
                         "FROM `xliff_original` " .
                             "LEFT JOIN `xliff_general` ON (`xliff_general`.`general_id` = `xliff_original`.`general`) " .
                         "WHERE `original_id` NOT IN ( SELECT `original` FROM `xliff_translate` WHERE `language` = " . $langId . " ) " .
                             ( ($common == false) ? "AND `xliff_general`.`org_filename` <> 'common.rpy' " : "" ) .
                         "GROUP by `xliff_original`.`uuid` " .
                         "ORDER BY `xliff_general`.`org_filename` ASC, " .
                                  "`xliff_general`.`linenumber` ASC;";
                $data = $this -> registry -> db -> queryObjectArray($query);

                if ( is_array($data) AND count($data) ) {
                    foreach( $data AS $key => $values ) {
                        $this -> openTranslation[$langId][$values['uuid']] = array(
                                                                                 'general_id'   => $values['general'],
                                                                                 'original_id'  => $values['original_id'],
                                                                                 'uuid'         => $values['uuid'],
                                                                                 'source'       => $values['source'],
                                                                                 'original_md5' => $values['md5hash'],
                                                                                 'person'       => $values['person'],
                                                                                 'emote'        => $values['emote'],
                                                                                 'filename'     => $values['org_filename'],
                                                                                 'translatet'   => $values['translatet'],
                                                                                 'comment'      => $values['comment'],
                                                                                 'ignorable'    => $values['ignorable'],
                                                                             );
                    }
                } // End if Result has Data

                // search all Entries, where translatet is empty
                $query = "SELECT `xliff_translate`.*, " .
                                "`xliff_original`.`source`, `xliff_original`.`md5hash`, " .
                                "`xliff_general`.`person`, `xliff_general`.`emote`, `xliff_general`.`org_filename` " .
                         "FROM `xliff_translate` " .
                             "LEFT JOIN `xliff_original` ON (`xliff_original`.`uuid` = `xliff_translate`.`uuid`) " .
                             "LEFT JOIN `xliff_general` ON (`xliff_general`.`uuid` = `xliff_translate`.`uuid`) " .
                         "WHERE `xliff_translate`.`translatet` = '' " .
                           "AND `xliff_translate`.`language` = " . $langId;
                $data = $this -> registry -> db -> queryObjectArray($query);

                if ( is_array($data) AND count($data) ) {
                    foreach( $data AS $key => $values ) {
                        $this -> openTranslation[$langId][$values['uuid']] = array(
                                                                                 'general_id'   => $values['general'],
                                                                                 'original_id'  => $values['original'],
                                                                                 'uuid'         => $values['uuid'],
                                                                                 'translatet'   => $values['translatet'],
                                                                                 'source'       => $values['source'],
                                                                                 'original_md5' => $values['md5hash'],
                                                                                 'person'       => $values['person'],
                                                                                 'emote'        => $values['emote'],
                                                                                 'filename'     => $values['org_filename'],
                                                                                 'comment'      => $values['comment'],
                                                                                 'ignorable'    => $values['ignorable'],
                                                                             );
                    }
                }// End if Result has Data

            } // End for all LanguageIds
        } // End if Language Selected
    }

    /**
     * find string in Database by UUID and Language-Code
     *
     * @access private
     * @param  string          UUID
     * @param  integer         ID from Language
     * @return integer|false
     */
    private function _getTranslationIdFromDatabase($uuid, $langId)
    {
        if ( strlen($uuid) AND ($langId >= 1) ) {
            $query = "SELECT `translate_id` FROM `xliff_translate` WHERE `uuid` = '" . $uuid . "' AND `language` = " . $langId . ";";
            return $this -> registry -> db -> querySingleItem($query);
        }
        else {
            return false;
        }
    }

    /**
     * find translation in Database
     *
     * @access private
     * @param  string         UUID
     * @param  integer        ID from Language
     * @param  integer|null   ID from xliff_translate (optional)
     */
    private function _getTranslatetStringFromDatabase($uuid, $langId, $translateID = null)
    {
        if ( strlen($uuid) AND ($langId >= 1) ) {
            if ( is_null($translateID) OR ($translateID <= 0) ) {
                $query = "SELECT `translatet` FROM `xliff_translate` WHERE `uuid` = '" . $uuid . "' AND `language` = " . $langId;
            }
            else {
                $query = "SELECT `translatet` FROM `xliff_translate` WHERE `uuid` = '" . $uuid . "' AND `language` = " . $langId . " AND `translate_id` = " . $translateID;
            }

            $string = $this -> registry -> db -> querySingleItem($query);

            if ( is_string($string) AND strlen($string) >= 1 ) {
                // translation is as string and has langth
                return $string;
            }
            else {
                // no Result found
                return -1;
            }
        }
        else {
            // Parameter-Failed
            return -2;
        }
    }
}