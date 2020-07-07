<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class ViaMe_View_Helper_SEO_Quip extends Zend_View_Helper_Abstract
{
    #protected $_fileName = 'partials/_CM.phtml';
    #protected $_moduleName = 'system';
    
    public function SEO_Quip($content = null, $limit = 300, $range = 50, $ext = '...')
    {
        if ($content) {
            $content = iconv(mb_detect_encoding($content), 'UTF-8//TRANSLIT', $content);
            
            $content = preg_replace(
                array(
                  // Remove invisible content
                    '@<head[^>]*?>.*?</head>@siu',
                    '@<style[^>]*?>.*?</style>@siu',
                    '@<script[^>]*?.*?</script>@siu',
                    '@<object[^>]*?.*?</object>@siu',
                    '@<embed[^>]*?.*?</embed>@siu',
                    '@<applet[^>]*?.*?</applet>@siu',
                    '@<noframes[^>]*?.*?</noframes>@siu',
                    '@<noscript[^>]*?.*?</noscript>@siu',
                    '@<noembed[^>]*?.*?</noembed>@siu',
                    '@<area[^>]*?.*?</area>@siu',
                    '@<map[^>]*?.*?</map>@siu',
                    '@<marquee[^>]*?.*?</marquee>@siu',
                    '@<menu[^>]*?.*?</menu>@siu',
                    '@<select[^>]*?.*?</select>@siu',
                    '@<textarea[^>]*?.*?</textarea>@siu',
                    '@<\?xml.*?>@i',
                    '@&lt;\?xml.*?&gt;@i',
                  // Add line breaks before and after blocks
                    '@</?((address)|(blockquote)|(center)|(del))@iu',
                    '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
                    '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
                    '@</?((table)|(th)|(td)|(caption))@iu',
                    '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
                    '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
                    '@</?((frameset)|(frame)|(iframe))@iu',
                ),
                array(
                    ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
                    "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0"
                ),
                $content );
        
            $content = strip_tags($content);
            $content = trim(preg_replace(array("/(\s|\p{Z}|&nbsp;)+/siu"), array(' '), $content));
        
            if ($limit && (strlen($content) > $limit)) {
                $content = preg_replace("/^(.{" . $limit . "," . ($limit + $range) . "}?)\W.*/su", "$1", $content);
        	    $content .= $ext;
            }
        }
        
        return $content;
    }
}