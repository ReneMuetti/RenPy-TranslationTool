<?php
class Translation_History
{
    private $registry;
    private $renderer;

    private $methods = array('insert', 'update');
    private $minStringLen = 2;

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

    public function getHistoryTable()
    {
        $lngs = new Languages();
        $lng_list = $lngs -> getLanguageByID();
        $lng_select = array();

        $lng_select[] = '<option value="0">' . $this -> registry -> user_lang['global']['option_actions_select'] . '</option>';
        foreach( $lng_list AS $id => $code ) {
            $lng_select[] = '<option value="' . $code . '">' . $this -> registry -> user_lang['languages'][$code] . '</option>';
        }

        $method_select = array(
                             '<option value="0">' . $this -> registry -> user_lang['global']['option_actions_select'] . '</option>',
                             '<option value="insert">' . $this -> registry -> user_lang['history']['insert'] . '</option>',
                             '<option value="update">' . $this -> registry -> user_lang['history']['update'] . '</option>',
                         );

        $query = "SELECT `id`, `username` FROM `users`";
        $data  = $this -> registry -> db -> queryObjectArray($query);

        $user_select = array();
        $user_select[] = '<option value="0">' . $this -> registry -> user_lang['global']['option_actions_select'] . '</option>';
        if ( is_array($data) AND count($data) ) {
            foreach( $data AS $key => $value ) {
                $user_select[] = '<option value="' . $value['id'] . '">' . $value['username'] . '</option>';
            }
        }


        $this -> renderer -> loadTemplate('history' . DS . 'table.htm');
            $this -> renderer -> setVariable('language_options', implode("", $lng_select));
            $this -> renderer -> setVariable('method_options'  , implode("", $method_select));
            $this -> renderer -> setVariable('user_options'    , implode("", $user_select));
        return $this -> renderer -> renderTemplate();
    }

    /**
     * get data from History
     *
     * @access public
     * @param  integer     UserID
     * @param  string|3    Language ISO-Code
     * @param  string      Method (insert od update)
     * @param  integer     Result-Count
     * @param  integer     select Page
     * @param  string      (part of) UUID
     * @param  string      (part of) old string
     * @param  string      (part of) new string
     * @param  integer     start-ID from last query
     * @param  integer     end-ID from last query
     * @return array
     */
    public function getDataFromHistoryByFilter($userid = 0, $language = '', $method = '', $count = 20, $page = 1, $uuid = '', $old = '', $new = '', $start = 0, $end = 0)
    {
        $result = array(
                      'error' => false,
                      'data'  => array(
                                     'message' => '',
                                     'general' => array(
                                                      'firstid' => 0,
                                                      'lastid'  => 0,
                                                      'count'   => 0,
                                                  ),
                                     'result' => array(),
                                     'total'  => 0,
                                     //'query'  => '',
                                 ),
                  );

        $countCondition = $this -> _buildFilterQuery($userid, $language, $method, $count, $page, $uuid, $old, $new, $start, $end, true);

        $query = $this -> _buildFilterQuery($userid, $language, $method, $count, $page, $uuid, $old, $new, $start, $end, false);
        $data  = $this -> registry -> db -> queryObjectArray($query);

        //$result['data']['query'] = $query;

        if ( is_array($data) AND count($data) ) {
            // include FineDiff
            require_once( realpath( './include/classes/Core/class_FineDiff.php' ) );
            $granularity = 2;
            $granularityStacks = array(
                                     FineDiff::$paragraphGranularity,
                                     FineDiff::$sentenceGranularity,
                                     FineDiff::$wordGranularity,
                                     FineDiff::$characterGranularity,
                                 );

            $transformer = new HtmlTransform();

            foreach( $data AS $key => $value ) {
                if($key == 0) {
                    $result['data']['general']['firstid'] = $value['history_id'];
                }
                $result['data']['general']['lastid'] = $value['history_id'];

                $value['old_string'] = $transformer -> convertCodeToHtml($value['old_string'], true, true);
                $value['new_string'] = $transformer -> convertCodeToHtml($value['new_string'], true, true);

                $diff_opcodes    = FineDiff::getDiffOpcodes($value['old_string'], $value['new_string'], $granularityStacks[$granularity]);
                $renderNewString = FineDiff::renderDiffToHTMLFromOpcodes($value['old_string'], $diff_opcodes);

                $result['data']['result'][] = array(
                                                  'date'     => $value['date'],
                                                  'username' => $value['username'],
                                                  'language' => $this -> registry -> user_lang['languages'][$value['language']],
                                                  'method'   => $this -> registry -> user_lang['history'][$value['method']],
                                                  'uuid'     => $value['org_filename'] . '<br />' . $value['uuid'],
                                                  'old'      => $value['old_string'],
                                                  'new'      => $renderNewString,
                                              );
            }

            $result['data']['general']['count'] = count($data);

            if ( count($data) >= $count ) {
                $result['data']['total'] = $this -> registry -> db -> tableCount('history', $countCondition);
            }
            else {
                $result['data']['total'] = count($data);
            }
        }
        else {
            $result['data']['message'] = $this -> registry -> user_lang['history']['empty_result'];
        }

        return $result;
    }



    /**
     * build Query by Filter
     *
     * @access private
     * @param  boolean          result is general-Count?
     * @param  @see             $this -> getDataFromHistoryByFilter()
     * @return string           MySQL-Query
     */
    private function _buildFilterQuery($userid, $language, $method, $count, $page, $uuid, $old, $new, $start, $end, $showTotalCount = false)
    {
        if ( $showTotalCount === true ) {
            $query = "";
        }
        else {
            $query = "SELECT `history`.`history_id`, `history`.`date`,`history`. `username`, `history`.`language`, `history`.`method`, `history`.`uuid`, `history`.`old_string`, `history`.`new_string`, " .
                            "`xliff_general`.`org_filename` " .
                     "FROM `history` " .
                     "LEFT JOIN `xliff_general` ON (`xliff_general`.`uuid` = `history`.`uuid`) ";
        }

        $where = array();

        if ( $userid > 0 ) {
            $users = new UserProfile();
            $username = $users -> getUsernameFromUserID($userid);

            if ( strlen($username) ) {
                $where[] = "`history`.`username` = '" . $username . "'";
            }
        }
        if ( strlen($language) AND (strlen($language) >= 2) ) {
             $where[] = "`history`.`language` = '" . $language . "'";
        }
        if ( strlen($method) AND in_array($method, $this -> methods) ) {
            $where[] = "`history`.`method` = '" . $method . "'";
        }
        if ( strlen($uuid) AND (strlen($uuid) >= $this -> minStringLen) ) {
            // combine-field (UUID and Filename)
            $where[] = "`history`.`uuid` LIKE '%" . $uuid . "%'";
            $where[] = "`xliff_general`.`org_filename` LIKE '%" . $uuid . "%'";
        }
        if ( strlen($old) AND (strlen($old) >= $this -> minStringLen) ) {
            $where[] = "`history`.`old_string` LIKE '%" . $old . "%'";
        }
        if ( strlen($new) AND (strlen($new) >= $this -> minStringLen) ) {
            $where[] = "`history`.`new_string` LIKE '%" . $new . "%'";
        }

        if ( $page >= 2 ) {
            $where[] = "`history`.`history_id` <= " . ($end - 1);
        }

        if ( $showTotalCount === true ) {
            $limit = '';
        }
        else {
            $limit = "LIMIT " . $count;
        }

        if ( count($where) ) {
            $query .= "WHERE " . implode(" AND ", $where) . " ";
        }

        $query .= "ORDER BY `history_id` DESC ";

        return $query . $limit;
    }
}