<?php
class Xliff_Information
{
    private $registry = null;
    private $renderer = null;
    private $hiddenLanguage = 'ru';   // Language not shown on Status-Page

    private $inventoryOriginal = null;
    private $inventoryTranslation = null;

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

    /**
     * convert translation-selection into array (edit-only)
     *
     * @access public
     * @param  string       user-selection
     * @return string
     */
    public function getTranslationInformationFromUserData($userData)
    {
        $userLangs = $this -> _extractLanguageData($userData, false);
        return $this -> _getCurrentTranslationStatusByCode($userLangs);
    }

    /**
     * generate searchform for full-text-search
     *
     * @access public
     * @return string
     */
    public function getSearchForm()
    {
        $lang  = new Languages();
        $langs = $lang -> getLanguagesByCode();

        $langOptions = array();
        $langOptions[] = '<option value="0">'   . $this -> registry -> user_lang['global']['option_actions_select'] . '</option>';
        //$langOptions[] = '<option value="255">' . $this -> registry -> user_lang['global']['option_actions_original_language'] . '</option>';
        foreach( $langs AS $code => $lngId ) {
            if ( $code != $this -> hiddenLanguage ) {
                $langOptions[] = '<option value="' . $lngId . '">' . $this -> registry -> user_lang['languages'][$code] . '</option>';
            }
        }

        $allPersons = $this -> _getAllPersonsFromOriginal();
        $charOptions = array();
        $charOptions[] = '<option value="none">'   . $this -> registry -> user_lang['global']['option_actions_select'] . '</option>';
        $charOptions[] = '<option value="empty">'   . $this -> registry -> user_lang['search']['no_person'] . '</option>';
        foreach( $allPersons AS $person ) {
            $charOptions[] = '<option value="' . $person . '">' . $person . '</option>';
        }

        $this -> renderer -> loadTemplate('search' . DS . 'form.htm');
            $this -> renderer -> setVariable('select_options'   , implode("\n                            ", $langOptions));
            $this -> renderer -> setVariable('character_options', implode("\n                            ", $charOptions));
        return $this -> renderer -> renderTemplate();
    }

    /**
     * get all Gamefiles al seperatet Block
     *
     * @access public
     * @return string;
     */
    public function getAllGameFilesFormGeneralAsBlock()
    {
        $query = "SELECT `org_filename` FROM `xliff_general` GROUP BY `org_filename`;";
        $data  = $this -> registry -> db -> queryObjectArray($query);

        $blocks = array();
        if ( is_array($data) AND count($data) ) {
            foreach( $data AS $key => $value ) {
                $this -> renderer -> loadTemplate('details' . DS . 'overview_block.htm');
                    $this -> renderer -> setVariable('file_name', $value['org_filename']);
                    $this -> renderer -> setVariable('file_id'  , $key);
                $blocks[] = $this -> renderer -> renderTemplate();
            }
        }

        return implode("\n", $blocks);
    }

    /**
     * get Information from one Gemafile
     *
     * @access public
     * @param  string     Filename
     * @param  integer    LanguageID
     * @return array
     */
    public function getInformationForDetailsByFile($filename, $language)
    {
        $return = array(
                      'total'   => 0,
                      'open'    => 0,
                      'percent' => 0,
                      'style'   => '',
                  );

        // Total String-Count
        $return['total'] = $this -> registry -> db -> tableCount('xliff_general', "WHERE `org_filename` = '" . $filename . "' AND `active` = 1;");

        // translatet strings
        $condition = "LEFT JOIN `xliff_translate` ON (`xliff_general`.`uuid` = `xliff_translate`.`uuid`) WHERE`org_filename` = '" . $filename . "' " .
                     "AND `xliff_general`.`active` = 1 AND `xliff_translate`.`language` = " . $language . ";";
        $translatet = $this -> registry -> db -> tableCount('xliff_general', $condition);
        $return['open'] = $return['total'] - $translatet;

        // percent
        $return['percent'] = ( $translatet * 100 / $return['total'] );
        $return['percent'] = $this -> _formatPercentCounter($return['percent']);

        // RGB-Bar-Style
        if ( $translatet < $return['total'] ) {
            $return['style'] = 'linear-gradient(to left, transparent ' . (100 - $return['percent']) . '%, white 0%)';
        }
        else {
            $return['style'] = 'linear-gradient(to left, transparent 0%, white 0%)';
        }

        return $return;
    }

    /**
     * get List from all Users with there selected Languages
     *
     * @accees public
     * @return string
     */
    public function getGlobalTranslationStatus()
    {
        $lang  = new Languages();
        $langs = $lang -> getLanguagesByCode();

        $xliff = $this -> _getCurrentTranslationStatus();
        $users = $this -> _getTranslationUserStatus();

        $headLangs = array();
        $headLangs2 = array();
        foreach($langs AS $code => $id) {
            if ( $code != $this -> hiddenLanguage ) {
                $headLangs[] = '<th colspan="2">' . $this -> registry -> user_lang['languages'][$code] . '</th>';

                $headLangs2[] = '<th class="table-symbol" title="' . $this -> registry -> user_lang['profile']['ajax_translation_view'] . '">' .
                                '<img src="' .  $this -> registry -> baseurl . 'skin/images/view.png"></th>';
                $headLangs2[] = '<th class="table-symbol" title="' . $this -> registry -> user_lang['profile']['ajax_translation_edit'] . '">' .
                                '<img src="' .  $this -> registry -> baseurl . 'skin/images/edit.png"></th>';
            }
        }

        $userLines = array();
        foreach( $users AS $key => $tUser ) {
            $userLines[] = '<tr>';
            $userLines[] = '    <td>' . $tUser['username'] . '</td>';

            foreach($langs AS $code => $id) {
                if ( $code != 'ru' ) {
                    if ( isset($tUser['languages'][$code]['view']) AND ($tUser['languages'][$code]['view'] == 1) ) {
                        $userLines[] = '    <td><span class="status status-okay">X</span></td>';
                    }
                    else {
                        $userLines[] = '    <td></td>';
                    }
                    if ( isset($tUser['languages'][$code]['edit']) AND ($tUser['languages'][$code]['edit'] == 1) ) {
                        $userLines[] = '    <td><span class="status status-okay">X</span></td>';
                    }
                    else {
                        $userLines[] = '    <td></td>';
                    }
                }
            }

            $userLines[] = '</tr>';
        }

        $this -> renderer -> loadTemplate('index' . DS . 'user_table.htm');
            $this -> renderer -> setVariable('thead_col1' , implode("\n            ", $headLangs));
            $this -> renderer -> setVariable('thead_col2' , implode("\n            ", $headLangs2));
            $this -> renderer -> setVariable('tbody_lines', implode("\n        ", $userLines));
        $userTable = $this -> renderer -> renderTemplate();

        if ( $xliff['transStringCount'] > 0 ) {
            $translatePercent = ( $xliff['translatedCount'] * 100 ) / $xliff['transStringCount'];
        }
        else {
            $translatePercent = 0;
        }

        $this -> renderer -> loadTemplate('index' . DS . 'xliff_table.htm');
            $this -> renderer -> setVariable('language_count'    , count($langs));
            $this -> renderer -> setVariable('org_string_count'  , $xliff['orgStringCount']);
            $this -> renderer -> setVariable('trans_string_count', $xliff['transStringCount']);
            $this -> renderer -> setVariable('translated_count'  , $xliff['translatedCount']);
            $this -> renderer -> setVariable('translated_percent', $this -> _formatPercentCounter($translatePercent));
        $xliffTable = $this -> renderer -> renderTemplate();

        return $userTable . "\n<br /><br />\n" . $xliffTable;
    }

    /**
     * get translation status by language
     *
     * @access public
     * @param  boolean     show personal selected language
     * @return string
     */
    public function getCurrentTranslationStatus($personalOnly = true)
    {
        if ( $personalOnly === true ) {
            $currentSelectedLangs = $this -> _getLanguagesFromCurrentUser();
            $section_title = $this -> registry -> user_lang['index']['language_seceted'];
        }
        else {
            $currentSelectedLangs = $this -> _getLanguagesWithoutCurrentUser();
            $section_title = $this -> registry -> user_lang['index']['language_other'];
        }

        $currentTranslationStatus = $this -> _getCurrentTranslationStatusByCode($currentSelectedLangs);

        $blocks = array();
        if ( is_array($currentTranslationStatus) AND count($currentTranslationStatus) ) {

            foreach( $currentTranslationStatus AS $code => $status ) {
                if ( $status['orgStringCount'] > 0 ) {
                    $percentCount = ( $status['translatedCount'] * 100 / $status['orgStringCount'] );
                }
                else {
                    $percentCount = 0;
                }


                if ( $status['translatedCount'] < $status['orgStringCount'] ) {
                    $masktStyle = ' style="mask: linear-gradient(to left, transparent ' . (100 - $percentCount) . '%, white 0%); ' .
                                          '-webkit-mask: linear-gradient(to left, transparent ' . (100 - $percentCount) . '%, white 0%);"';
                }
                else {
                    $masktStyle = '';
                }

                $this -> renderer -> loadTemplate('index' . DS . 'xliff_lanuage_block.htm');
                    $this -> renderer -> setVariable('lang_code'      , $code);
                    $this -> renderer -> setVariable('lang_name'      , $this -> registry -> user_lang['languages'][$code]);
                    $this -> renderer -> setVariable('total_count'    , $status['orgStringCount']);
                    $this -> renderer -> setVariable('current_percent', $this -> _formatPercentCounter($percentCount));
                    $this -> renderer -> setVariable('current_open'   , $status['orgStringCount'] - $status['translatedCount']);
                    $this -> renderer -> setVariable('rgb_mask_style' , $masktStyle);
                $blocks[] = $this -> renderer -> renderTemplate();
            }
        }

        if ( count($blocks) ) {
            $grid_content = implode("\n", $blocks);
        }
        else {
            $grid_content = $this -> registry -> user_lang['index']['no_language_seceted'];
        }

        $this -> renderer -> loadTemplate('index' . DS . 'xliff_lanuage_grid.htm');
            $this -> renderer -> setVariable('section_title', $section_title);
            $this -> renderer -> setVariable('grid_content' , $grid_content);
        return $this -> renderer -> renderTemplate();
    }

    /**
     * get current status from Languages (view and/or edit)
     *
     * @access public
     * @return array
     */
    public function getLanguagesFromCurrentUser()
    {
        return $this -> _getLanguagesFromCurrentUser();
    }

    /**
     * find dublicate strings in translation table
     *
     * @access public
     * @return array
     */
    public function searchDublicateStringsInTranslation()
    {
        $this -> _getAllOriginalTranslations();
        $this -> _getAllTranslationsByOriginalAndLanguage();

        $globalCount = 0;
        $globalIds = array();
        $langCount = array();

        foreach( $this -> inventoryTranslation AS $langID => $value ) {
            $langCount[$langID] = 0;
            foreach ($value AS $uuid => $data) {
                $globalCount += $data['count'];
                $langCount[$langID] += $data['count'];
                $globalIds[] = $data['ids'];
            }
        }

        $mess = array();
        if ( $globalCount > 0 ) {
            $mess[] = $this -> registry -> user_lang['translation']['total_count_duplicates_from_translation_found'] . ': ' . $globalCount;

            $langs = new Languages();
            $liste = $langs -> getLanguageByID();

            foreach( $langCount AS $langId => $count ) {
                $mess[] = $this -> registry -> user_lang['translation']['count_duplicates_from_translation_found'] . ': ' . $count . ' (' . $this -> registry -> user_lang['languages'][$liste[$langId]] . ')';
            }
        }
        else {
            $mess[] = $this -> registry -> user_lang['translation']['no_duplicates_from_translation_found'];
        }

        return array(
                   'details' => implode("<br />", $mess),
                   'total'   => $globalCount,
                   'ids'     => implode(',', $globalIds),
               );
    }

    /**
     * delete dublicate strings from translation table
     *
     * @access public
     * @param  string      coma-seperatet list with IDs
     * @return string
     */
    public function deleteDublicateStringsInTranslation($deleteIds)
    {
        if ( strpos($deleteIds, ',') ) {
            // multible IDs
            $list = explode(',', $deleteIds);
            foreach( $list AS $id => $number ) {
                $list[$id] = intval($number);
            }

            $query  = "DELETE FROM `xliff_translate` WHERE `translate_id` IN (" . implode(',', $list) . ");";
            $result = $this -> registry -> db -> execute($query);

            if ( $result >= 1 ) {
                return $this -> registry -> user_lang['translation']['ajax_translation_delete_ids'] . ': ' . $result;
            }
            else {
                return $this -> registry -> user_lang['translation']['ajax_translation_faile_delete_ids'];
            }
        }
        else {
            // sinlge-ID
            $query  = "DELETE FROM `xliff_translate` WHERE `translate_id` = " . intval($deleteIds) . ";";
            $result = $this -> registry -> db -> execute($query);

            if ( $result >= 1 ) {
                return $this -> registry -> user_lang['translation']['ajax_translation_delete_ids'] . ': ' . $result;
            }
            else {
                return $this -> registry -> user_lang['translation']['ajax_translation_faile_delete_ids'];
            }
        }
    }




    /**
     * get all Persons as array
     *
     * @access private
     * @return array
     */
    private function _getAllPersonsFromOriginal()
    {
        $persons = array();

        $query = 'SELECT `person` FROM `xliff_general` WHERE `person` <> "" GROUP BY `person` ORDER BY `person`;';
        $data  = $this -> registry -> db -> queryObjectArray($query);
        if ( is_array($data) AND count($data) ) {
            foreach($data AS $key => $value) {
                if ( ctype_upper($value['person'][0]) ) {
                    $persons[] = $value['person'];
                }
            }
        }

        return $persons;
    }

    /**
     * sort all translations by LanguageID an UUID
     *
     * @access private
     */
    private function _getAllTranslationsByOriginalAndLanguage()
    {
        $this -> inventoryTranslation = array();

        $langs = new Languages();
        $liste = $langs -> getLanguagesByCode();

        foreach( $liste AS $langIso => $langCode ) {
            foreach( $this -> inventoryOriginal AS $uuid => $orgData ) {
                $query = "SELECT `translate_id`, `translatet` FROM `xliff_translate` WHERE `uuid` = '" . $uuid . "' " .
                         "AND `language` = " . $langCode . " " .
                         "AND `general` = " . $orgData['general'] . " AND `original` = " . $orgData['original'] . " " .
                         "ORDER BY `translate_id` ASC;";
                $result = $this -> registry -> db -> queryObjectArray($query);

                if ( is_array($result) AND (count($result) > 1) ) {
                    $tmp = array();
                    foreach( $result AS $id => $found ) {
                        if ( !strlen($found['translatet']) ) {
                            $tmp[] = $found['translate_id'];
                        }
                    }
                    // remove first item
                    array_shift($tmp);

                    $this -> inventoryTranslation[$langCode][$uuid] = array(
                                                                          'count' => count($tmp),
                                                                          'ids'   => implode(",", $tmp),
                                                                      );
                }

            }
        }
    }

    /**
     * collect all Data from Original-Table
     *
     * @access private
     */
    private function _getAllOriginalTranslations()
    {
        $this -> inventoryOriginal = array();

        $query = "SELECT `original_id`, `uuid`, `general` FROM `xliff_original`;";
        $data  = $this -> registry -> db -> queryObjectArray($query);

        if ( is_array($data) AND count($data) ) {
            foreach( $data AS $value ) {
                $this -> inventoryOriginal[$value['uuid']] = array(
                                                                 'original' => $value['original_id'],
                                                                 'general'  => $value['general'],
                                                             );
            }
        }
    }

    /**
     * roud percent-values
     *
     * @access private
     * @param  float
     * @return float    with 2 post-comma-values
     */
    private function _formatPercentCounter($percent)
    {
        return number_format($percent, 2, '.', ',');
    }

    /**
     * get translation-status over all
     *
     * @access private
     * @return array
     */
    private function _getCurrentTranslationStatus()
    {
        $orgCounter = $this -> registry -> db -> tableCount('xliff_general', "WHERE `active` = 1");

        $return = array(
                      'orgStringCount'   => $orgCounter,
                      'transStringCount' => ($this -> registry -> db -> tableCount('language') - 1) * $orgCounter,
                      'translatedCount'  => $this -> registry -> db -> tableCount('xliff_translate', "WHERE `translatet` != ''"),
                  );
        return $return;
    }

    /**
     * get translationstatus from user
     *
     * @access private
     * @return array
     */
    private function _getTranslationUserStatus()
    {
        $query = 'SELECT `username`, `translation` FROM `users`';
        $data  = $this -> registry -> db -> queryObjectArray($query);
        $users = array();

        if ( is_array($data) AND count($data) ) {
            foreach( $data AS $key => $value ) {
                $users[] = array(
                               'username'  => $value['username'],
                               'languages' => unserialize( stripslashes($value['translation']) ),
                           );
            }
        }

        return $users;
    }

    /**
     * stansation-status from language
     *
     * @access private
     * @param  array     ID from Languages
     * @return array
     */
    private function _getCurrentTranslationStatusByCode($currentSelectedLangs)
    {
        if ( !is_array($currentSelectedLangs) OR !count($currentSelectedLangs) ) {
            return;
        }

        $lngs = new Languages();
        $languages = $lngs -> getLanguagesByCode();

        // select all strings, where not in common.rpy
        $filterGeneral    = "WHERE `org_filename` <> 'common.rpy' AND  `active` = 1";
        $filterTranslatet = "WHERE `uuid` IN (SELECT `uuid` FROM `xliff_general` " . $filterGeneral . ") AND `translatet` <> '' AND `language` = ";

        $result = array();
        foreach( $currentSelectedLangs AS $language ) {
            $result[$language] = array(
                                     'orgStringCount'  => $this -> registry -> db -> tableCount('xliff_general'  , $filterGeneral),
                                     'translatedCount' => $this -> registry -> db -> tableCount('xliff_translate', $filterTranslatet . $languages[$language]),
                                 );
        }

        return $result;
    }

    /**
     * language-selection from current user
     *
     * @access private
     * @return array
     */
    private function _getLanguagesFromCurrentUser()
    {
        return $this -> _extractLanguageData($this -> registry -> userinfo['translation'], true);
    }

    /**
     * convert language-selection from serialize to array
     *
     * @access private
     * @param  array         user-selection
     * @param  bool          result include view-languages
     * @return array
     */
    private function _extractLanguageData($data, $incView = false)
    {
        $data = unserialize( stripslashes($data) );

        $return = array();

        if ( is_array($data) AND count($data) ) {
            foreach( $data AS $code => $settings ) {
                if ( $incView == true ) {
                    if (
                         ( isset($settings['view']) AND ($settings['view'] == 1) ) OR
                         ( isset($settings['edit']) AND ($settings['edit'] == 1) )
                       ) {
                        $return[] = $code;
                    }
                }
                else {
                    if ( isset($settings['edit']) AND ($settings['edit'] == 1) ) {
                        $return[] = $code;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * all languages which the current user has not chosen
     *
     * @access private
     * @return array
     */
    private function _getLanguagesWithoutCurrentUser()
    {
        $userLangs = $this -> _getLanguagesFromCurrentUser();

        $lngs = new Languages();
        $languages = $lngs -> getLanguagesByCode();

        $return = array();

        foreach( $languages AS $code => $id ) {
            if ( $code != $this -> hiddenLanguage ) {
                if ( is_array($userLangs) AND count($userLangs) ) {
                    if ( !in_array($code, $userLangs) ) {
                        $return[] = $code;
                    }
                }
                else {
                    $return[] = $code;
                }
            }
        }

        return $return;
    }
}