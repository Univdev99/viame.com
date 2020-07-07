<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class ViaMe_View_Helper_SEO_Keywords extends Zend_View_Helper_Abstract
{
    #protected $_fileName = 'partials/_CM.phtml';
    #protected $_moduleName = 'system';
    
    public function SEO_Keywords($content = null, $limit = 20, $randomize = false, $minwordlength = 5, $joiner = ', ')
    {
        if ($content) {
            $content = $this->view->SEO_Quip($content, null);
            
            $content = html_entity_decode($content, ENT_QUOTES, "UTF-8");
            $content = preg_replace('/[^\p{L}\p{N}\p{Z}]/su', '', $content);
            
            $tokens = preg_split('/(\s|\p{Z})+/su', $content);
            
            $token_counter = array();
            foreach ($tokens as $key) {
                $key = mb_strtolower($key);
                if (mb_strlen($key) >= $minwordlength) {
                    if (isset($token_counter[$key])) {
                        $token_counter[$key]++;
                    }
                    else {
                        $token_counter[$key] = 1;
                    }
                }
            }
            
            if ($randomize === 1 || $randomize === true) {
                shuffle($token_counter);
            }
            elseif ($randomize != 'none') {
                arsort($token_counter);
            }
            
            $tokens = array_keys($token_counter);
            
            if ($limit) {
                $tokens = array_slice($tokens, 0, $limit);
            }
            
            return join($joiner, $tokens);
        }
    }
}