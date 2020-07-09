<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class ViaMe_View_Helper_ContentLink extends Zend_View_Helper_Abstract
{
    //protected $_fileName = 'partials/_ContentLink.phtml';
    //protected $_moduleName = 'system';
    
    public function ContentLink($model = null)
    {
        
        if (isset($model) && isset($model['object']) && isset($model['view'])) {
            $object = $model['object'];
            $view = $model['view'];
            
            $host = 'https://' . $view->internal->vars->host;
            
            $link = '';
            
            if ($object->title) {
                $SEO_Title = $view->SEO_Urlify($object->title);
                if ($SEO_Title) {
                    $link = '/' . $SEO_Title . '/s';
                }
            }
            
            if ($view->masked) {
                $link .= $view->internal->target->pre . "/" . $view->module . "/view/p/mid/" . $view->masked . '/id/' . $object->counter . '/';
            }
            else {
                if ($object->net_id) {
                    $link .= '/net/' . $object->net_id;
                }
                elseif ($object->via_id) {
                    $link .= '/via/' . $object->via_id;
                    
                    if (($object->via_id == $object->profile_id) && ($object->c_id != $view->internal->community->id)) {
                        # New Hostname
                        $host = 'https://' .
                          (isset($view->internal->vars->language) ? $view->internal->vars->language . '.' : '') .
                          ($object->c_hostname ? $object->c_hostname : ($object->c_name != 'default' ? $object->c_name : 'www') . '.' . $view->internal->config->default_domain)
                        ;
                    }
                }
                elseif ($object->com_id) {
                    if ($object->com_id != $view->internal->community->id) {
                        if ($object->com_id == $object->c_id) {
                            $host = 'https://' .
                              (isset($view->internal->vars->language) ? $view->internal->vars->language . '.' : '') .
                              ($object->c_hostname ? $object->c_hostname : ($object->c_name != 'default' ? $object->c_name : 'www') . '.' . $view->internal->config->default_domain)
                            ;
                        }
                        else {
                            $link .= '/com/' . $object->com_id;
                        }
                    }
                    elseif (strtoupper($view->internal->target->type) == 'COM' && isset($view->internal->target->pre) && $view->internal->target->pre) {
                        $link .= $view->internal->target->pre;
                    }
                }

                
                $link .= "/" . $view->module . "/view/p/mid/" . $object->matrix_counter . '/id/' . $object->counter . '/';
            }

            return $host . $link;
        }
    }
}
