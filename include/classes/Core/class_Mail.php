<?php
class Mail
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

    /**
     * Send an e-mail to a user
     *
     * @access    public
     * @param     string         filename from mail-template
     * @param     string         email-subject
     * @param     string         email-adress from user
     * @param     string         username
     * @param     string|null    cc-adress
     * @param     string|null    bcc-address
     * @param     bool           set for sending multible messages
     * @param     bool           is current Message eq last message
     * @param     bool           set debug-mode
     *
     * @return    string
     */
    public function sendMailToUser($templateFile, $subject, $userMail, $userName, $ccAdress = null, $bccAdress = null, $isMulti = false, $isLast = false, $debugMode = false)
    {
        include_once realpath("./include/classes/PHPMailer/Exception.php");
        include_once realpath("./include/classes/PHPMailer/PHPMailer.php");
        include_once realpath("./include/classes/PHPMailer/SMTP.php");

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        if ( !strlen($templateFile) ) {
            $templateFile = 'test_message.htm';
        }

        if ( !strlen($subject) ) {
            $subject = 'Test-Mail';
        }

        try {
            // Connection-Settings
            if ( $debugMode == true ) {
                $mail->SMTPDebug = 2;
            }

            $mail->isSMTP();
            $mail->Host       = $this -> registry -> config['Mail']['host'];
            $mail->SMTPAuth   = $this -> registry -> config['Mail']['smtpauth'];
            $mail->Username   = $this -> registry -> config['Mail']['username'];
            $mail->Password   = $this -> registry -> config['Mail']['password'];
            $mail->Port       = $this -> registry -> config['Mail']['port'];
            $mail->CharSet    = $this -> registry -> config['Mail']['charset'];

            if ( $this -> registry -> config['Mail']['secure'] ) {
                $mail->SMTPSecure = $this -> registry -> config['Mail']['protocol'];
            }

            // Multi-Mails
            if ( $isMulti == true AND $isLast == false ) {
                $mail->SMTPKeepAlive = true;
            }

            // To load language version
            //$mail->setLanguage('de', '/optional/path/to/language/directory/');

            // Recipients
            $mail->setFrom('translator@info-panel.net', 'BBAS-Translator');
            $mail->addAddress($userMail, $userName);

            // CC
            if ( !is_null($ccAdress) AND strlen($ccAdress) ) {
                $mail->addCC($ccAdress);
            }

            // BCC
            if ( !is_null($bccAdress) AND strlen($bccAdress) ) {
                $mail->addBCC($bccAdress);
            }

            //Content
            $content_data = $this -> _renderMailBody($templateFile, $subject, $userName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $content_data['content'];

            // add embedded Image
            if ( count($content_data['images']) ) {
                foreach( $content_data['images'] AS $image ) {
                    $mail->AddEmbeddedImage($image['file_path'], $image['cid_name'], $image['file_name']);
                }
            }

            //send mail
            $mail->send();

            return sprintf($this -> registry -> user_lang['mail']['send_mail_success'], $userName, $userMail);
        }
        catch(Exception $e) {
            $logMessage = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            new Logging('php_mailer', $logMessage);

            return $this -> registry -> user_lang['mail']['error_mail_not_send'];
        }
    }

    /**
     * get e-mail preview
     *
     * @access    public
     * @param     string         filename from mail-template
     * @param     string         title for email
     * @param     string         username
     *
     * @return    string
     */
    public function eMaiHtmlPreview($templateFile = 'test_message.htm', $subject = 'Test-Mail', $userName = 'Test-User')
    {
        $data = $this -> _renderMailBody($templateFile, $subject, $userName, null, true);
        return $data['content'];
    }






    /**
     * render e-mail
     * @see https://github.com/leemunroe/responsive-html-email-template/blob/master/email.html
     *
     * @access    private
     * @param     string         filename from mail-template
     * @param     string         title for email
     * @param     bool           return valid HTML
     *
     * @return    array
     */
    private function _renderMailBody($templateFile, $title, $userName, $userData = null, $returnValidHtmlOutput = true)
    {
        if ( is_null($userData) OR !is_array($userData) ) {
            $userData = $this -> _loadDataFromUserByUsername($userName);
        }

        if ( is_null($userData) OR !is_array($userData) ) {
            return $this -> registry -> user_lang['mail']['error_loading_userdata'] . ' :: (' . $userName . ')';
        }

        // switch language from current user to selected language
        $orgLanguage = $this -> registry -> user_config['language'];
        $this -> registry -> loadLanguage($userData['language']);

        $content     = '';
        $attachments = array();

        $info = new Xliff_Information();
        $userTranslate = $info -> getTranslationInformationFromUserData($userData['translation']);
        if ( is_array($userTranslate) AND count($userTranslate) ) {
            $lines = array();

            foreach( $userTranslate AS $lngCode => $status ) {
                $image = $this -> _convertSvgToPng($lngCode);
                $cid   = 'flag_' . $lngCode . '_image';

                $attachments[] = array(
                                     'file_path' => $image,
                                     'cid_name'  => $cid,
                                     'file_name' => basename($image),
                                 );

                $this -> renderer -> loadTemplate('email' . DS . 'new_translation_table_line.htm');
                    $this -> renderer -> setVariable('image_cid_name', $cid );
                    $this -> renderer -> setVariable('total_count'   , $status['orgStringCount']);
                    $this -> renderer -> setVariable('current_open'  , $status['translatedCount']);
                $lines[] = $this -> renderer -> renderTemplate();
            }
            $tableLines = implode("\n                ", $lines);
        }
        else {
            // nothing
            $tableLines = '';
        }

        $this -> renderer -> loadTemplate('email' . DS . $templateFile);
            $this -> renderer -> setVariable('mail_title', $title);
            $this -> renderer -> setVariable('username'  , $userName);
            $this -> renderer -> setVariable('usermail'  , $userData['email']);
            $this -> renderer -> setVariable('language'  , $userData['language']);
            $this -> renderer -> setVariable('table_body', $tableLines);

        if ( $returnValidHtmlOutput == true ) {
            $content = html_entity_decode($this -> renderer -> renderTemplate());
        }
        else {
            $content = $this -> renderer -> renderTemplate();
        }

        // restore original language from current user
        $this -> registry -> loadLanguage($orgLanguage);

        return array(
                   'content' => $content,
                   'images'  => $attachments,
               );
    }


    /**
     * get/convert SVG-image to PNG-image
     * @see https://www.phpclasses.org/package/12991-PHP-Convert-an-SVG-image-to-PNG-removing-transparency.html#view_files/files/349142
     *
     * @access    private
     * @param     string         language-code
     *
     * @return    string
     */
    private function _convertSvgToPng($lang_code)
    {
        $png = new Images($lang_code);
        return $png -> getPngFile();
    }

    /**
     * convert SVG-File to one-line-string
     *
     * @access    private
     * @param     string         language-code
     *
     * @return    string
     */
    private function _getRawSvgData($lang_code)
    {
        $svg = new Images($lang_code);
        return $svg -> getSvgData();
    }

    /**
     * user-information
     *
     * @access    private
     * @param     string         get infoprmation from user
     *
     * @return    array|null
     */
    private function _loadDataFromUserByUsername($userName)
    {
        if ( strlen($userName) ) {
            $userInfo = new UserProfile();
            return $userInfo -> getInformationByUsername($userName);
        }
        else {
            return false;
        }
    }
}