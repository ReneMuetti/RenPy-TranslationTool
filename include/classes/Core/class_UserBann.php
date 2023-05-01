<?php
class UserBann
{
    var $data = array();

    var $check = array();

    public function __construct()
    {
        $this -> data  = array();
        $this -> check = array();
    }

    public function __destruct()
    {
        unset($this -> data);
        unset($this -> check);
    }

    public function bann_user( $bann_data = array() )
    {
        if ( is_array($bann_data) AND count($bann_data) == 8 )
        {
            $this -> data = $bann_data;

            $this -> do_bann();
        }
    }

    public function check_user( $email = '', $username = '' )
    {
        $email    = trim($email);
        $username = trim($username);

        if ( ( ($email != '') AND (strpos($email, '@')) ) OR ($username != '') )
        {
            $this -> get_bann($email, $username, FALSE);

            return $this -> check;
        }
        else
        {
            return array( 'banned' => TRUE, 'bann_grund' => 'keine gültige eMail oder Benutzername!' );
        }
    }

    public function check_username( $username = '' )
    {
        $username = trim($username);

        if ( $username != '' )
        {
            $this -> get_bann('', $username, TRUE);

            return $this -> check;
        }
        else
        {
            return array( 'banned' => TRUE, 'bann_grund' => 'kein gültiger Benutzername!' );
        }
    }

    public function all_users()
    {
        $this -> get_list();

        return $this -> data;
    }


    private function do_bann()
    {
        global $website;

        $insert = array();

        $insert['bann_name']   = $this -> data['bann_name'];
        $insert['bann_datum']  = $this -> data['bann_datum'];
        $insert['bann_email']  = $this -> data['bann_email'];
        $insert['bann_grund']  = $this -> data['bann_grund'];

        $website -> db -> insertRow($insert, 'users_blacklist');
    }

    private function get_bann($email, $username, $only_name = FALSE)
    {
        global $website;

        if ( strpos($email, '$') )
        {
            $email = substr($email, 0, strpos($email, '$'));
        }

        if ( $only_name === TRUE )
        {
            $where = "bann_name = '" . $username . "'";
        }
        else
        {
            if ( ($email != '') AND ($username != '') )
            {
                $where = "bann_email = '" . $email . "' OR bann_name = '" . $username . "'";
            }
            else
            {
                $where = "bann_email = '" . $email . "'";
            }
        }

        $sql  = "SELECT bann_grund, bann_datum, bann_email FROM users_blacklist WHERE " . $where . " LIMIT 1";
        $data = $website -> db -> querySingleArray($sql);

        if ( is_array($data) AND count($data) > 1 )
        {
            $this -> check['banned']     = TRUE;
            $this -> check['bann_grund'] = $data['bann_grund'];
            $this -> check['bann_datum'] = $data['bann_datum'];
            $this -> check['bann_email'] = $data['bann_email'];
        }
        else
        {
            $this -> check['banned'] = FALSE;
        }
    }

    private function get_list()
    {
        global $website;

        $sql = 'SELECT * FROM users_blacklist ORDER BY bann_datum ASC';

        $this -> data = $website -> db -> queryObjectArray($sql);
    }
}