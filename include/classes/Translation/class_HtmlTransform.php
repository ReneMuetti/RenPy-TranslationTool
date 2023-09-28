<?php
class HtmlTransform
{
    /**
     * all HTML-Codes
     */
    private $html_array = array();

    /**
     * all Codes in Scripts
     */
    private $code_array = array();


    public function __construct()
    {
        $this -> _fillCodeArrays();
    }

    public function __destruct()
    {
        unset($this -> html_array, $this -> code_array);
    }

    /**
     * convert string from HTML to Inline-Code
     *
     * @access public
     * @param  string
     * @return string
     */
    public function convertHtmlToCode($text)
    {
        if ( is_string($text) AND strlen($text) ) {
            return str_replace($this -> html_array, $this -> code_array, $text);
        }

        return $text;
    }

    /**
     * convert string from Inline-Code to HTML
     *
     * @access public
     * @param  string
     * @param  boolean
     * @return string
     */
    public function convertCodeToHtml($text, $replaceAllCodes = true)
    {
        if ( is_string($text) AND strlen($text) ) {
            $text = stripslashes($text);
            $text = nl2br($text);

            $text = str_replace( array('.r<br />', '.r<br />\n'), '.<br />', $text);

            if ( $replaceAllCodes == true ) {
                $text = str_replace($this -> code_array, $this -> html_array, $text);
            }

            $text = str_replace(array('<br /><br />', '<br /><br>'), '<br />', $text);
        }

        return $text;
    }

    /**
     * replace " characters in « and »
     *
     * @access private
     * @param  string
     * @return string
     */
    public function replaceQuoteInTranslationString($string)
    {
        $result = '';
        $quoteCount = 0;

        $processString = stripslashes($string);

        // replace HTML-Char in translation string if need
        if ( mb_strpos($processString, 'quot;') !== false ) {
            $processString = str_replace( array('&quot;', '&amp;quot;'), '"', $processString);
        }

        // get string length
        $stringLength = mb_strlen($processString);

        for ( $pos = 0; $pos < $stringLength; $pos++ ) {
            $char = mb_substr($processString, $pos, 1);

            if ($char === '"') {
                $quoteCount++;

                $replacement = ($quoteCount % 2 === 1) ? '«' : '»';
                $result .= $replacement;
            }
            else {
                $result .= $char;
            }
        }

        return $result;
    }




    /*************************************************************************************/
    /********************************  Private Functions  ********************************/
    /*************************************************************************************/

    private function _fillCodeArrays()
    {
        $this -> html_array = array(
                                  '<b>', '</b>', '<i>', '</i>', '<u>', '</u>',
                                  '<br>', '<br />',
                              );

        $this -> code_array = array(
                                  '{b}', '{/b}', '{i}', '{/i}', '{u}', '{/u}',
                                  "\n" , "\n"  ,
                              );
    }
}