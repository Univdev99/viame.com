<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class ViaMe_View_Helper_DisplayWidget2 extends Zend_View_Helper_Abstract
{
    protected $_fileName = 'partials/_DisplayWidget.phtml';
    protected $_moduleName = 'widget';
    
    
    public function __construct()
    {
        ViaMe_Controller_Action::registryLoader($this);
    }
	public function DisplayWidget2($id = null, $widget = null, $override_params = null) {
		//echo "Id: " . $id; exit();
		//echo "<pre>"; print_r($widget); echo "</pre>"; 
		list($type, $spec, $counter) = explode('-', $id);
		if($id) {
			if($widget && !empty($widget)) {
				
				list($type, $spec, $counter) = explode('-', $id);
            
            	$no_widget_passed_in = false;
            
	            if (!$widget) {
	                // No widget passed in, try to locate
	                //  Probably in a container...Therefore, we should probably not add a class and remove ID
	                $no_widget_passed_in = true;
	                
	                if (strtolower($type) == 'w' && isset($this->target->widgets)) {
	                    foreach ($this->target->widgets as $temp) {
	                        if ($temp->widget_id == $spec && $temp->counter == $counter) {
	                            $widget = $temp;
	                            break;
	                        }
	                    }
	                }
	                elseif (strtolower($type) == 'm' && isset($this->target->modules)) {
	                    foreach ($this->target->modules as $temp) {
	                        if ($temp->module_id == $spec && $temp->counter == $counter) {
	                            $widget = $temp;
	                            break;
	                        }
	                    }
	                }
	            }
	           
	           // echo "<pre>"; print_r($widget); echo "</pre>"; exit();
            	
	            if ($widget) {
	                $start_timer = microtime(true);
	                $data = array(
	                    'id' => $id,
	                    'class' => 'cm decorated widget',
	                    'extra' => ''
	                );
	                
	                #if (isset($this->member) && isset($this->member->profile) && $this->member->profile->id == 2) {
	                #    $data['extra'] .= ' <a href="' . $this->view->internal->target->pre . '/system/grid/toggle/id/' . $id . '/" title="toggle" class="toggle">T</a>';
	                #}
	                
	                /*if ($this->view->internal->target->acl->privilege >= ViaMe_Controller_Action::ACL_OWNER) 			{
                    $data['extra'] .= '<a href="' . $this->view->internal->target->pre . '/' . (strtolower($type) == 'w' ? 'widget' : 'module') . '/edit/p/' . strtolower($type) . 'id/' . $spec . '/id/' . $counter . '/" rel="nofollow" title="Configure" class="configure">C</a>';
                    $data['extra'] .= '<a href="' . $this->view->internal->target->pre . '/system/grid/remove/vmpd_fp/1/id/' . $id . '/" rel="nofollow" title="Hide" class="remove">X</a>';
                }*/
	                
	                if (strtolower($type) == 'w' && isset($this->view->internal->widget_widgets[$spec])) {
	                    $data['hd'] = ($widget->display_url ? '<a href="' . $this->view->escape($widget->display_url) . '">' : '') . $this->view->escape($widget->display ? $widget->display : $widget->w_display) . ($widget->display_url ? '</a>' : '');
	                    if ($widget->secondary) {
	                        $data['hd2'] = ($widget->secondary_url ? '<a href="' . $this->view->escape($widget->secondary_url) . '">' : '') . $this->view->escape($widget->secondary) . ($widget->secondary_url ? '</a>' : '');
	                    }
	                    
	                    $data['bd'] = $this->view->action('index', 'widgets_'.$this->view->internal->widget_widgets[$spec]->name, 'widget', array_merge((array) $this->view->internal->params, array('widget' => $widget)));
	                    
	                    if (!$widget->display_cm) {
	                        unset($data['tl'], $data['hd'], $data['hd2'], $data['tr'], $data['bl'], $data['ft'], $data['ft2'], $data['br'], $data['extra']);
	                        $data['class'] = 'cm widget';
	                    }
	                    
	                    $data['class'] .= ' w_' . $this->view->internal->widget_widgets[$spec]->name;
	                }
	                
	                if ($no_widget_passed_in) 																	{
                    // Widgets within containers
                    if ($override_params) {
                        foreach (array('id', 'tl', 'hd', 'hd2', 'tr', 'bl', 'ft', 'ft2', 'br', 'extra') as $key) {
                            if (!in_array($key, $override_params['doNotUnset'])) {
                                //unset($data[$key]);
                            }
                        }
                        
                        if (!in_array('class', $override_params['doNotUnset'])) {
                            $data['class'] = 'cm';
                        }
                    }
                    else {
                        unset($data['id'], $data['tl'], $data['hd'], $data['hd2'], $data['tr'], $data['bl'], $data['ft'], $data['ft2'], $data['br'], $data['extra']);
                        $data['class'] = 'cm';
                    }
                }
	                
	                /*
	                # Widget Load Time
	                if ($this->config->debug >= 5 && isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
	                    $data['ft'] = sprintf ('%.4f', microtime(true) - $start_timer);
	                }
	                */
	                
	                if ((isset($widget->display_cm) && $widget->display_cm) || $data['bd']) 		{
                    return $this->view->CM($data);
                }
					
					
	            }
            }
		}
	}
}