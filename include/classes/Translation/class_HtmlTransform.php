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

    private $quote_start = '«';
    private $quote_end   = '»';


    public function __construct()
    {
        $this -> _fillCodeArrays();
    }

    public function __destruct()
    {
        unset($this -> html_array, $this -> code_array);
    }

    /**
     * fixed brocken HTML
     *
     * @access public
     * @param  string
     * @return string
     */
    public function fixtWrongHTML($text)
    {
        $text = $this -> _fixedWrongQuote($text);

        return str_replace(
                   array('&lt;br', '/&gt;'),
                   array('<br'   , '/>'),
                   $text
               );
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
     * @param  boolean
     * @return string
     */
    public function convertCodeToHtml($text, $replaceAllCodes = true, $fixedHTML = false)
    {
        if ( is_string($text) AND strlen($text) ) {
            $text = stripslashes($text);
            $text = nl2br($text);

            $text = str_replace(
                        array('.\r', '\\'),
                        array(''   , ''),
                        $text
                    );
            $text = str_replace(
                        array('.r<br />', '.r<br />\n'),
                        '.<br />',
                        $text
                    );

            if ( $replaceAllCodes == true ) {
                $text = str_replace($this -> code_array, $this -> html_array, $text);
            }

            $text = str_replace(array('<br /><br />', '<br /><br>'), '<br />', $text);

            if ( $fixedHTML == true ) {
                $text = $this -> fixtWrongHTML($text);
            }
        }

        return $text;
    }

    /**
     * replace " in string
     *
     * @access public
     * @param  string
     * @param  bool
     * @return string
     */
    public function replaceQuoteInTranslationString($string, $newLine = false)
    {
        $processString = trim($string);

        if ( $newLine == true ) {
            $processString = nl2br($processString);
        }

        $processString = stripslashes($processString);

        // replace HTML-Char in translation string if need
        if ( mb_strpos($processString, 'quot;') !== false ) {
            $processString = str_replace( array('&quot;', '&amp;quot;'), '"', $processString);
        }

        $processString = $this -> setStringInlineQuotes($processString);

        return $processString;
    }

     /**
     * replace 2-set of " characters in « and »
     *
     * @access public
     * @param  string
     * @return string
     */
    public function setStringInlineQuotes($string)
    {
        $result = '';
        $quoteCount = 0;

        // get string length
        $stringLength = mb_strlen($string);

        for ( $pos = 0; $pos < $stringLength; $pos++ ) {
            $char = mb_substr($string, $pos, 1);

            if ($char === '"') {
                $quoteCount++;

                $replacement = ($quoteCount % 2 === 1) ? $this -> quote_start : $this -> quote_end;
                $result .= $replacement;
            }
            else {
                $result .= $char;
            }
        }

        $result = $this -> _fixedWrongQuote($result);

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

    private function _fixedWrongQuote($text)
    {
        // Max «Маx => Max "Маx
        $search = 'Max ' . $this -> quote_start . 'Маx';
        if ( mb_strpos($text, $search) !== false ) {
            $text = str_replace($search, 'Max "Max', $text);
            $text = mb_substr($text, 0, -1) . '"';
        }

        return $text;
    }
}