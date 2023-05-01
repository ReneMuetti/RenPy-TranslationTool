<?php
class Languages
{
    private $registry = null;
    private $renderer = null;
    private $guiLangList = array();

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

    public function getXmlLanguagesForGui()
    {
        $this -> _loadAllGuiLanguage();
        return $this -> guiLangList;
    }

    public function insertNewLanguage($newLngCode)
    {
        $check = $this -> registry -> db -> querySingleItem("SELECT `lng_id` FROM `language` WHERE `lng_code` = '" . $newLngCode . "';" );
        if ( is_null($check) OR !strlen($check) ) {
            $insert = array(
                          'lng_code' => $newLngCode,
                      );
            $result = $this -> registry -> db -> insertRow($insert, 'language');

            if ( $result === false ) {
                $newBlock = '';
            }
            else {
                $this -> renderer -> loadTemplate('admin' . DS . 'language_block.htm');
                    $this -> renderer -> setVariable('new_language', $newLngCode);
                $newBlock = $this -> renderer -> renderTemplate();
            }

            return array(
                       'error' => $result,
                       'block' => $newBlock,
                   );
        }
        else {
            return array(
                       'error' => false,
                       'block' => $this -> registry -> user_lang['language']['allready_exists'],
                   );
        }
    }

    public function getLanguageListForAdmin()
    {
        $data = $this -> _getAllLanguages();
        $lng  = array();

        if ( is_array($data) AND count($data[0]) ) {

            foreach( $data AS $language ) {
                $this -> renderer -> loadTemplate('admin' . DS . 'language_block.htm');
                    $this -> renderer -> setVariable('new_language', $language['lng_code']);
                $lng[] = $this -> renderer -> renderTemplate();
            }
        }
        else {
            // empty List
        }

        $this -> renderer -> loadTemplate('admin' . DS . 'language_list.htm');
            $this -> renderer -> setVariable('curr_lang_list', implode("\n", $lng));
        $fillList = $this -> renderer -> renderTemplate();

        return $fillList;
    }

    public function getLanguagesByCode()
    {
        $data = $this -> _getAllLanguages();
        $lng  = array();

        if ( is_array($data) AND count($data[0]) ) {
            foreach( $data AS $language ) {
                $lng[$language['lng_code']] = $language['lng_id'];
            }
        }

        return $lng;
    }

    public function getLanguageByID()
    {
        $data = $this -> _getAllLanguages();
        $lng  = array();

        if ( is_array($data) AND count($data[0]) ) {
            foreach( $data AS $language ) {
                $lng[$language['lng_id']] = $language['lng_code'];
            }
        }

        return $lng;
    }

    public function getLanguageTitleByID($langId)
    {
        $query = 'SELECT `lng_title` FROM `language` WHERE `lng_id` = ' . $langId;
        return $this -> registry -> db -> querySingleItem($query);
    }

    public function getIsoCodeFromLanguageId($langId)
    {
        $query = 'SELECT `lng_code` FROM `language` WHERE `lng_id` = ' . $langId;
        return $this -> registry -> db -> querySingleItem($query);
    }






    /*************************************************************************************/
    /********************************  Private Functions  ********************************/
    /*************************************************************************************/

    private function _getAllLanguages()
    {
        $query = 'SELECT * FROM `language`';
        return $this -> registry -> db -> queryObjectArray($query);
    }

    private function _loadAllGuiLanguage()
    {
        $lister = new FileDir('language');
        $liste  = $lister -> getFileList('.xml');

        if ( is_array($liste) AND count($liste) ) {
            foreach( $liste AS $lang ) {
                // remove first Char
                $file = substr($lang, 1);
                $code = substr($file, 0, -4);

                $this -> guiLangList[] = array(
                                             'xml'  => $lang,
                                             'file' => $file,
                                             'code' => $code,
                                         );
            }
        }
    }
}