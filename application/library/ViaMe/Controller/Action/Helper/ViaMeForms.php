<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Action_Helper_ViaMeForms extends Zend_Controller_Action_Helper_Abstract
{
    public function init()
    {
        ViaMe_Controller_Action::registryLoader($this);
    }
    
    public function addParameters ($parameters = '', $order = 100)
    {
        if ($parameters) {
            $temp_params = explode($this->config->delimiter, $parameters);
            $counter = 0;
            
            $elements = array();
            
            foreach ($temp_params as $param) {
                $order += 5;
                $options = strrchr($param, ':');
                $param = preg_replace("/(.*)${options}$/", '${1}', $param);
                $options = str_split(preg_replace("/:(.*)$/", '${1}', $options));
                
                $element = null;
                
                $params = preg_split('/:/', $param);
                $name = $params[0];
                $label = (isset($params[1]) && $params[1] ? $params[1] : $params[0]);
                $description = (isset($params[2]) && $params[2] ? $params[2] : $params[0]);
                $values = (isset($params[3]) && $params[3] ? $params[3] : null);
                
                # Used Switches - CTHSW DNRX
                
                // Create Element
                if (in_array('C', $options)) {
                    // Checkbox
                    $element = new Zend_Form_Element_Checkbox($name, array('label' => $label, 'description' => $description));
                }
                elseif (in_array('T', $options) || in_array('H', $options) || in_array('W', $options)) {
                    // TextArea
                    $element = new Zend_Form_Element_Textarea($name, array('label' => $label, 'description' => $description));
                }
                elseif (in_array('S', $options)) {
                    // Select
                    $selected = array();
                    $temp = preg_split("/[\s,]+/", preg_replace("/{+(.*)}+/", '${1}', $values), -1, PREG_SPLIT_NO_EMPTY);
                    foreach ($temp as $which) {
                        $selects[$which] = $which;
                    }
                    $element = new Zend_Form_Element_Select($name, array('label' => $label, 'description' => $description, 'multiOptions' => $selects));
                }
                else {
                    // Default to Text
                    $element = new Zend_Form_Element_Text($name, array('label' => $label, 'description' => $description));
                }
                
                // Options
                if ($element) {
                    $element->addPrefixPath('ViaMe', 'ViaMe/');
                    
                    #if (in_array('G', $options)) {
                    #    // Google Gadget Filter
                    #    $element->addFilter('PregReplace', array('#^<script src="http://www\.gmodules\.com.*?ifr\?url=(.*?)"></script>$#i', '${1}'));
                    #}
                    
                    if (in_array('N', $options)) {
                        // Numeric Fields
                        $element->addValidator('Digits');
                    }
                    
                    if (in_array('H', $options)) {
                        // WYSIWYG Text Editor
                        $element->setAttrib('class', 'vmfh_textarea');
                    }
                    
                    if (in_array('W', $options)) {
                        // Code Editor
                        $element->setAttrib('class', 'vmfh_codeeditor');
                    }
                    
                    if (in_array('D', $options)) {
                        // Date
                        $element->setAttrib('class', 'vmfh_date');
                        // Description
                        $element->setDescription(($element->getDescription() ? $element->getDescription() . ' ' : '') . '(' . preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'mm', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ')');
                        // Validators
                        
                        $element->addValidators(array(
                            array('Regex', false, array(
                                'pattern' => '/^' . preg_replace(array('/\./', '/\//', '/d+/i', '/m+/i', '/y+/i'), array('\\\.', '\\\/', '\d+', '\d+', '\d+'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . '$/',
                                'messages' => array(
                                    Zend_Validate_Regex::NOT_MATCH => 'Invalidly formatted date'
                                )
                            )),
                            array('Date', false,
                                preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'MM', 'YYYY'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short')))
                            )
                        ));
                    }
                    
                    if (in_array('R', $options)) {
                        // Required Fields
                        $element->setOptions(array('required' => true));
                    }
                    
                    // All Parameters Need to Be Filtered With HTMLPurify
                    if ((!(isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin))) &&
                        (!(in_array('X', $options)))) {
                        $element->addFilter('HTMLPurify');
                    }
                    
                    $element->setOptions(array('order' => $order));
                    
                    $elements[$name] = $element;
                }

                $counter++;
            }
            
            if ($elements) { return $elements; }
        }
    }
    
    
    public function constructArrayString ($params = null, $form = null)
    {
        if ($params && $form) {
            $iparams = array();
            
            $at_least_one_value = false;
            
            foreach ($params as $param) {
                $tokens = preg_split('/:/', $param);
                if ($form->getValue($tokens[0])) {
                    $iparams[] = $tokens[0] . ':' . $form->getValue($tokens[0]);
                    $at_least_one_value = true;
                }
            }
            
            if ($iparams && $at_least_one_value) {
                $hashkey = null;
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                for ($i = 0; $i < 10; $i++) {
                    $hashkey .= $characters[mt_rand(0, strlen($characters)-1)];
                }
                return "ARRAY[\$$hashkey\$" . implode("\$$hashkey\$,\$$hashkey\$", $iparams) . "\$$hashkey\$]";
            }
        }
    }
}
