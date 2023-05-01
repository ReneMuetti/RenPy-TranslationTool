<?php
class UserProfile
{
    private $registry;
    private $renderer;
    
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
    
    public function getUserListForAdmin()
    {
        $currentUserList = $this -> _getCurrentUserListFroAdmin();
        
        $this -> renderer -> loadTemplate('admin' . DS . 'account_list.htm');
            $this -> renderer -> setVariable('admin_table_user_list', $currentUserList);
        return $this -> renderer -> renderTemplate();
    }
    
    public function getUsernameFromUserID($userid)
    {
        return $this -> registry -> db -> querySingleItem("SELECT `username` FROM `users` WHERE `id` = " . intval($userid));
    }
    
    public function getCurrentProfile()
    {
        return $this -> getProfileFromUser($this -> registry -> userinfo, false);
    }

    public function getProfileFromUser($userProfile, $loadFromAdmin)
    {
        if ( is_int($userProfile) ) {
             $userProfile = $this -> registry -> db -> querySingleArray('SELECT * FROM `users` WHERE `id` = ' . intval($userProfile));
        }
        
        if ( is_array($userProfile) AND count($userProfile) ) {
            $admin_block       = '';     // Admin :: Enabled, Active, Confirmed
            $currStatusBlock   = '';     // HidenVar with Admin-Options
            $currLanguageBlock = '';     // Ajax :: Translation-Language in Profile (only for user))
            $currFormAction    = 'updateprofile';
            $currFormScript    = 'account.php';
            
            $langBlocks = $this -> _getAllLaguageAsBlocks($userProfile);

            if ( $loadFromAdmin === true ) {
                // Modify Account by Admin
                $currFormAction  = 'accounts';
                $currFormScript  = 'admin_control.php';
                $currStatusBlock = implode("\n", array(
                                                     '    <input type="hidden" name="accountid" value="' . $userProfile['id'] . '" />',
                                                     '    <input type="hidden" name="accmethod" value="updateaccount" />',
                                                 )
                                          );

                $this -> renderer -> addCustonStyle(array('script' => 'skin/css/account.css'), THIS_SCRIPT);
                $this -> renderer -> addJavascriptToHeader('skin/js/account.js', THIS_SCRIPT);
                
                $this -> renderer -> loadTemplate('account' . DS . 'form_admin.htm');
                    $this -> renderer -> setVariable('checbox_state_enabled', ($userProfile['enabled'] == 'yes'       ? 'checked' : '' ) );
                    $this -> renderer -> setVariable('checbox_state_admin'  , ($userProfile['admin']   == 'yes'       ? 'checked' : '' ) );
                    $this -> renderer -> setVariable('checbox_state_status' , ($userProfile['status']  == 'confirmed' ? 'checked' : '' ) );
                $admin_block = $this -> renderer -> renderTemplate();
            }
            else {
                // Modyfy Account by self
                $this -> renderer -> loadTemplate('account' . DS . 'form_hidden.htm');
                    $this -> renderer -> setVariable('curr_admin'  , $userProfile['admin']);
                    $this -> renderer -> setVariable('curr_enabled', $userProfile['enabled']);
                    $this -> renderer -> setVariable('curr_status' , $userProfile['status']);
                $currStatusBlock = $this -> renderer -> renderTemplate();
                
                // Ajax-Language-Modifier
                $translationList = $this -> _getTranslationBlock();
                
                $this -> renderer -> loadTemplate('account' . DS . 'ajax_language.htm');
                    $this -> renderer -> setVariable('translation_list', $translationList);
                $currLanguageBlock = $this -> renderer -> renderTemplate();
            }
            
            $this -> renderer -> loadTemplate('account' . DS . 'form.htm');
                $this -> renderer -> setVariable('curr_form_script'    , $currFormScript);
                $this -> renderer -> setVariable('curr_form_action'    , $currFormAction);
                $this -> renderer -> setVariable('curr_username'       , $userProfile['username']);
                $this -> renderer -> setVariable('curr_email'          , $userProfile['email']);
                $this -> renderer -> setVariable('curr_added'          , $userProfile['added']);
                $this -> renderer -> setVariable('curr_last_login'     , $userProfile['last_login']);
                $this -> renderer -> setVariable('curr_last_access'    , $userProfile['last_access']);
                $this -> renderer -> setVariable('curr_status_block'   , $currStatusBlock);
                $this -> renderer -> setVariable('language_block'      , $currLanguageBlock);
                $this -> renderer -> setVariable('admin_block'         , $admin_block);
                $this -> renderer -> setVariable('curr_language_blocks', $langBlocks);
            return $this -> renderer -> renderTemplate();
        }
        else {
            // TODO
        }
    }
    
    
    public function updateCurrentProfile()
    {
        return $this -> _updateUserProfileByID($this -> registry -> userinfo['id']);
    }
    
    public function updateProfileByID($profileID = 0)
    {
        if ( $profileID > 0 ) {
            return $this -> _updateUserProfileByID($profileID, 'admin_control.php?action=accounts');
        }
        else {
            // TODO
        }
    }
    
    public function deleteProfileById($profileID = 0)
    {
        if ( ($profileID > 0) AND ($profileID != $this -> registry -> userinfo['id']) ) {
            $query = "DELETE FROM `users` WHERE `id` = " . $profileID;
            $this -> registry -> db -> execute($query);
            
            return $this -> _addSuccessMessage( $this -> registry -> user_lang['profile']['success_profile_deleted'], 'admin_control.php?action=accounts' );
        }
        else {
            return $this -> _addNewChangeErrorMessage( $this -> registry -> user_lang['profile']['error_profile_cannot_deleted'] );
        }
    }
    
    
    
    
    
    private function _getCurrentUserListFroAdmin()
    {
        $query = 'SELECT `id`, `username`, `added`, `status`, `enabled` from `users` ORDER BY `id` ASC';
        $data  = $this -> registry -> db -> queryObjectArray($query);
        if ( is_array($data) AND count($data[0]) ) {
            $account = array();
            
            foreach( $data AS $userAccount ) {
                $this -> renderer -> loadTemplate('admin' . DS . 'account_list_line.htm');
                    $this -> renderer -> setVariable('user_id'         , $userAccount['id']);
                    $this -> renderer -> setVariable('user_username'   , $userAccount['username']);
                    $this -> renderer -> setVariable('user_added'      , $userAccount['added']);
                    $this -> renderer -> setVariable('user_raw_status' , ($userAccount['status'] == 'confirmed') ? 'status-okay' : 'status-fail' );
                    $this -> renderer -> setVariable('user_status'     , $this -> registry -> user_lang['admin'][$userAccount['status']]);
                    $this -> renderer -> setVariable('user_raw_enabled', ($userAccount['enabled'] == 'yes') ? 'status-okay' : 'status-fail' );
                    $this -> renderer -> setVariable('user_enabled'    , $this -> registry -> user_lang['global']['status_' . $userAccount['enabled']]);
                $account[] = $this -> renderer -> renderTemplate();
            }
            
            return implode("\n", $account);
        }
        else {
            $this -> renderer -> loadTemplate('admin' . DS . 'account_list_empty.htm');
            return $this -> renderer -> renderTemplate();
        }
    }
    
    private function _updateUserProfileByID($profileID = 0, $script = 'account.php')
    {
        if ( $profileID > 0 ) {
            $this -> registry -> input -> clean_array_gpc('p', array(
                                                                   'username'         => TYPE_NOHTML,
                                                                   'email'            => TYPE_NOHTML,
                                                                   'password'         => TYPE_NOHTML,
                                                                   'password_confirm' => TYPE_NOHTML,
                                                                   'select-lang'      => TYPE_NOHTML,
                                                               )
                                                         );
            
            $currentProfile = $this -> registry -> db -> querySingleArray('SELECT * FROM `users` WHERE `id` = ' . $profileID);

            $changeData = array();
            $update = array();
            
            if ( strlen($this -> registry -> GPC['username']) AND ( $this -> registry -> GPC['username'] != $currentProfile['username'] ) ) {
                $update['username'] = $this -> registry -> GPC['username'];
                $changeData[] = $this -> _addNewChangeMessage('username');
            }
            if ( strlen($this -> registry -> GPC['email']) AND ( $this -> registry -> GPC['email'] != $currentProfile['email'] ) ) {
                $update['email'] = $this -> registry -> GPC['email'];
                $changeData[] = $this -> _addNewChangeMessage('email');
            }
            if ( strlen($this -> registry -> GPC['select-lang']) AND ( $this -> registry -> GPC['select-lang'] != $currentProfile['language'] ) ) {
                $update['language'] = $this -> registry -> GPC['select-lang'];
                $changeData[] = $this -> _addNewChangeMessage('language');
            }
            
            if ( strlen($this -> registry -> GPC['password']) OR strlen($this -> registry -> GPC['password_confirm']) ) {
                if ( strlen($this -> registry -> GPC['password']) AND strlen($this -> registry -> GPC['password_confirm']) ) {
                    $hasher = new PasswordHash(8, FALSE);
                    $pass   = $hasher -> HashPassword( $this -> registry -> GPC['password'] );
                    
                    $secret   = mksecret();
                    $passhash = md5($secret . $this -> registry -> GPC['password'] . $secret);
                    
                    $update['passhash'] = $passhash;
                    $update['pass']     = $pass;
                    $update['secret']   = $secret;
                    
                    $changeData[] = $this -> _addNewChangeMessage('password');
                }
                else {
                    $changeData[] = $this -> _addNewChangeErrorMessage( $this -> registry -> user_lang['profile']['error_password_not_equal'] );
                }
            }

            if ( ($script != 'account.php') AND ($currentProfile['id'] != $this -> registry -> userinfo['id']) ) {
                if ( $this -> registry -> userinfo['admin'] == 'yes' ) {
                    $this -> registry -> input -> clean_array_gpc('p', array(
                                                                           'admin'   => TYPE_BOOL,
                                                                           'enabled' => TYPE_BOOL,
                                                                           'status'  => TYPE_BOOL,
                                                                       )
                                                                 );
                    $this -> registry -> GPC['admin']   = ( $this -> registry -> GPC['admin']   ? 'yes'       : 'no' );
                    $this -> registry -> GPC['enabled'] = ( $this -> registry -> GPC['enabled'] ? 'yes'       : 'no' );
                    $this -> registry -> GPC['status']  = ( $this -> registry -> GPC['status']  ? 'confirmed' : 'pending' );

                    if ( isset($this -> registry -> GPC['admin']) AND ( $this -> registry -> GPC['admin'] != $currentProfile['admin'] ) ) {
                        $update['admin'] = $this -> registry -> GPC['admin'];
                        $changeData[] = $this -> _addNewChangeMessage('admin');
                    }
                    if ( isset($this -> registry -> GPC['enabled']) AND ( $this -> registry -> GPC['enabled'] != $currentProfile['enabled'] ) ) {
                        $update['enabled'] = $this -> registry -> GPC['enabled'];
                        $changeData[] = $this -> _addNewChangeMessage('enabled');
                    }
                    if ( isset($this -> registry -> GPC['status']) AND ( $this -> registry -> GPC['status'] != $currentProfile['status'] ) ) {
                        $update['status'] = $this -> registry -> GPC['status'];
                        $changeData[] = $this -> _addNewChangeMessage('status');
                    }                
                }
            }

            if ( count($update) ) {
                $result = $this -> registry -> db -> updateRow($update, 'users', '`id` = ' . $profileID);
                if ( $result === false ) {
                    $changeData[] = $this -> _addNewChangeErrorMessage( $this -> registry -> user_lang['profile']['error_profile_update'] );
                }
                else {
                    $changeData[] = $this -> _addSuccessMessage( $this -> registry -> user_lang['profile']['success_profile_update'], $script );
                }
            }
            else {
                $changeData[] = $this -> _addNewChangeErrorMessage( $this -> registry -> user_lang['profile']['error_no_change_data'] );
            }
            
            return implode("\n", $changeData);
        }
        else {
            // TODO :: no ID
        }
    }    
    
    private function _addSuccessMessage($message, $script)
    {
        $this -> renderer -> loadTemplate('account' . DS . 'success_message.htm');
            $this -> renderer -> setVariable('success_messasage', $message);
            $this -> renderer -> setVariable('curr_form_script' , $script);
        return $this -> renderer -> renderTemplate();
    }
    
    private function _addNewChangeErrorMessage($message)
    {
        $this -> renderer -> loadTemplate('account' . DS . 'error_message.htm');
            $this -> renderer -> setVariable('error_messasage', $message);
        return $this -> renderer -> renderTemplate();
    }
    
    private function _addNewChangeMessage($fieldName)
    {
        $this -> renderer -> loadTemplate('account' . DS . 'change_field.htm');
            $this -> renderer -> setVariable('change_fieldname', $this -> registry -> user_lang['profile'][$fieldName]);
        return $this -> renderer -> renderTemplate();
    }
    
    private function _getTranslationBlock()
    {
        $query = 'SELECT * FROM `language`';
        $data  = $this -> registry -> db -> queryObjectArray($query);

        $selection = unserialize( stripslashes($this -> registry -> userinfo['translation']) );
        $blocks = array();
        
        foreach( $data AS $lang ) {
            if ( $lang['lng_code'] != 'ru' ) {
                if ( is_array($selection) AND array_key_exists($lang['lng_code'], $selection) ) {
                    if ( isset($selection[$lang['lng_code']]['view']) AND ($selection[$lang['lng_code']]['view'] == '1') ) {
                        $currLangView = ' checked';
                    }
                    else {
                        $currLangView = '';
                    }
                    
                    if ( isset($selection[$lang['lng_code']]['edit']) AND ($selection[$lang['lng_code']]['edit'] == '1') ) {
                        $currLangEdit = ' checked';
                    }
                    else {
                        $currLangEdit = '';
                    }
                }
                else {
                    $currLangView = '';
                    $currLangEdit = '';
                }
                
                $this -> renderer -> loadTemplate('account' . DS . 'ajax_language_block.htm');
                    $this -> renderer -> setVariable('ajax_language_name', $this -> registry -> user_lang['languages'][$lang['lng_code']]);
                    $this -> renderer -> setVariable('ajax_language_code', $lang['lng_code']);
                    $this -> renderer -> setVariable('view_status', $currLangView);
                    $this -> renderer -> setVariable('edit_status', $currLangEdit);
                $blocks[] = $this -> renderer -> renderTemplate();
            }
        }        
        
        return implode("\n", $blocks);
    }
    
    private function _getAllLaguageForSelect()
    {
        $files = $this -> __getLanguageList();
        
        $options = array();
        $options[] = '<option value="" class="">' . $this -> registry -> user_lang['global']['option_actions_select'] . '</option>';
            
        foreach( $files AS $lang ) {
            if ( $lang == $this -> registry -> userinfo['language'] ) {
                $selected = " selected";
            }
            else {
                $selected = "";
            }
            
            $options[] = '<option value="' . $lang . '" class="lang-' . $lang . '"' . $selected . '>' . $this -> registry -> user_lang['languages'][$lang] . '</option>';
        }
        
        return implode("\n                        ", $options);
    }
    
    private function _getAllLaguageAsBlocks($userData)
    {
        $files = $this -> __getLanguageList();
        $blocks = array();
        
        foreach( $files AS $lang ) {
            if ( $lang == $userData['language'] ) {
                $selected = " current-language";
            }
            else {
                $selected = "";
            }
            
            $this -> renderer -> loadTemplate('account' . DS . 'language_block.htm');
                $this -> renderer -> setVariable('lang_code'      , $lang);
                $this -> renderer -> setVariable('lang_name'      , $this -> registry -> user_lang['languages'][$lang]);
                $this -> renderer -> setVariable('lang_is_current', $selected);
            $blocks[] = $this -> renderer -> renderTemplate();
        }
        
        return implode("\n", $blocks);
    }


    private function __getLanguageList()
    {
        $xmls = new FileDir('language');
        $files = $xmls -> getFileList('xml');
        $result = array();
        
        if ( is_array($files) AND count($files) ) {
            foreach( $files AS $key => $lang ) {
                $result[] = substr($lang, 1, -4);
            }
        }
        
        return $result;
    }
}