<?php
// #############################################################################
/**
* Unicode-safe version of htmlspecialchars()
*
* @param	string	Text to be made html-safe
*
* @return	string
*/
function htmlspecialchars_uni($text, $entities = true)
{
	return str_replace(
		// replace special html characters
		array('<', '>', '"'),
		array('&lt;', '&gt;', '&quot;'),
		// translates all non-unicode entities
		preg_replace('/&(?!' . ($entities ? '#[0-9]+|shy' : '(#[0-9]+|[a-z]+)') . ';)/si',
			         '&amp;',
			         $text
		            )
	);
}


// #############################################################################
/**
* Convert String to sepcified Charset
*
* @param	string	Text to be converted
* @param    bool    using PHP-Function htmlentities for output
*
* @return	string
*/
function output_string($string = '', $htmlentities = true)
{
    global $website;

    if ( $htmlentities ) {
        return htmlentities($string, ENT_XHTML, $website -> user_config['output_charset'] );
    }
    else {
        return mb_convert_encoding($string, $website -> user_config['output_iso_charset'] );
    }
}

// #############################################################################
/**
* Check if User is LoggedIn
*/
function loggedInOrReturn()
{
    global $website;
    
    $security = new LoginLogout();
    $security -> checkUserIfLogedin();

    if (!$website -> userinfo) {
        header("Location: " . $website -> baseurl . "login.php");
    }
}

// #############################################################################
/**
* Check if User is Admin
*/
function checkIfUserIsAdmin()
{
    global $website;
    
    if ( isset($website -> userinfo) AND count($website -> userinfo) ) {
        if ( !isset($website -> userinfo['admin']) OR ($website -> userinfo['admin'] != 'yes') ) {
            header("Location: " . $website -> baseurl . "index.php");
        }
    }
    else {
        header("Location: " . $website -> baseurl . "index.php");
    }
}

// #############################################################################
/**
* Set Defaults for loggedin Users
*/
function setDefaultForLoggedinUser()
{
    global $website, $renderer, $navigation;
    
    if ( isset($website -> userinfo) AND count($website -> userinfo) ) {
        // Default-JS
        $renderer -> addJavascriptToHeader('skin/js/jquery-3.6.4.min.js', THIS_SCRIPT);
        $renderer -> addJavascriptToHeader('skin/js/navigation.js', THIS_SCRIPT);
        
        // Rest Footer
        $renderer -> setVariable('global_footer', '');
        
        // Check, is User is Admin
        if ( isset($website -> userinfo['admin']) AND ($website -> userinfo['admin'] == 'yes') ) {
            $renderer -> addJavascriptToHeader('skin/js/admin.js', THIS_SCRIPT);
            $renderer -> addCustonStyle(array('script' => 'skin/css/admin.css'), THIS_SCRIPT);
            
            $renderer -> loadTemplate('navigation' . DS . 'admin.htm');
            $navAdmin = $renderer -> renderTemplate();
        }
        else {
            $navAdmin = '';
        }
        
        // Loading Main-Navigation
        $renderer -> loadTemplate('navigation' . DS . 'main.htm');
            $renderer -> setVariable('navbar_admin', $navAdmin);
        $navigation = $renderer -> renderTemplate();
    }
}