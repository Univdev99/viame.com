<?php
        require dirname(dirname(dirname(__FILE__))) . '/models/members_form.php';
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions($form_config);
        
        // Do not set filters - Have user fix all errors
        #$form->setElementFilters(array('StringTrim'));
        
        // Load Up Time Zones
        $temparray = $tz_multioptions = $timezone_to_country = array();
        $temp = $other = null;
        foreach ($this->db->fetchAll("SELECT t.timezone, t.country_code, COALESCE(c.country, '') AS country FROM system_timezones t LEFT JOIN system_countries c ON t.country_code=c.code ORDER BY t.orderby, t.id") as $tza) {
            if ($tza->country_code && !isset($timezone_to_country[$tza->country_code])) {
                $timezone_to_country[$tza->country_code] = $tza->timezone;
            }
            $temparray[] = $tza->timezone;
        }
        
        // Group the Time Zones
        for ($i = 0; $i < count($temparray); $i++) {
            $temp_parts = explode('/', $temparray[$i]);
            if (count($temp_parts) > 1) {
                if ($temp_parts[0] == 'Etc') {
                    $other = true;
                    $tz_multioptions['GMT'][$temparray[$i]] = $temparray[$i];
                }
                else {
                    $tz_multioptions[$temp_parts[0]][$temparray[$i]] = $temparray[$i];
                }
            }
            else {
                if ($other) {
                    $tz_multioptions['Other'][$temparray[$i]] = $temparray[$i];
                }
                else {
                    $tz_multioptions[$temparray[$i]][$temparray[$i]] = $temparray[$i];
                }
            }
        }
        $form->getElement('timezone')->setMultiOptions($tz_multioptions);
        
        // Translation
        $locale_list = $this->locale->getLocaleList();
        $country_to_language = array();
        $language_to_locale = array();
        foreach ($locale_list as $key => $val) {
            if (strpos($key, '_')) {
                $parts = explode('_', $key);
                if (isset($country_to_language[strtoupper($parts[1])])) {
                    $country_to_language[strtoupper($parts[1])] = false;
                }
                else {
                    $country_to_language[strtoupper($parts[1])] = strtolower($parts[0]);
                }
                
                if (isset($parts[0]) && isset($parts[1]) && !isset($language_to_locale[$parts[1]])) {
                    $language_to_locale[$parts[0]] = $key;
                }
            }
        }
        
        // Load Up Countries
        $multioptions = array();
        $temp = null;
        foreach ($this->db->fetchAll("SELECT country, code FROM system_countries WHERE active='t' ORDER BY orderby, country") as $temp) {
            $multioptions[$temp->code] = $temp->country;
        }

        // Translation
        $multioptions_nt = $multioptions;
        foreach ($multioptions as $key => $val) {
            $key = strtoupper($key);
            if (isset($this->vars->language) && !preg_match('/^en/', $this->vars->language)) {
                try {
                    #$output = $this->locale->getCountryTranslation($key, $this->vars->language);
                    $output = $this->locale->getTranslation($key, 'country', $this->vars->language);
                    if (is_string($output) && $val != $output) {
                        $multioptions[$key] = "$val ($output)";
                    }
                } catch (Exception $e) {}
            }
            elseif (isset($this->locale) && !preg_match('/^en/', $this->locale->getLanguage())) {
                try {
                    #$output = $this->locale->getCountryTranslation($key);
                    $output = $this->locale->getTranslation($key, 'country');
                    if (is_string($output) && $val != $output) {
                        $multioptions[$key] = "$val ($output)";
                    }
                } catch (Exception $e) {}
            }
            elseif (array_key_exists($key, $country_to_language) && $country_to_language[$key] !== false) {
                try {
                    #$output = $this->locale->getCountryTranslation($key, strtolower($country_to_language[$key]));
                    $output = $this->locale->getTranslation($key, 'country', strtolower($country_to_language[$key]));
                    if (is_string($output) && $val != $output) {
                        $multioptions[$key] = "$val ($output)";
                    }
                } catch (Exception $e) {}
            }
        }
        
        $form->getElement('country')->setMultiOptions($multioptions);
        
        // Load Up Currencies
        $multioptions = array();
        $temp = null;
        foreach ($this->db->fetchAll("SELECT currency, code FROM system_currencies WHERE active='t' ORDER BY orderby, currency") as $temp) {
            $multioptions[$temp->code] = $temp->currency;
        }

        // Translation
        // Translation of Currencies TAKING TOO LONG!
        if (0) {
        $multioptions_nt = $multioptions;
        foreach ($multioptions as $key => $val) {
            $key = strtoupper($key);
            $ZC = new ViaMe_Currency($key, (isset($this->vars->language) && isset($language_to_locale[$this->vars->language]) ? $language_to_locale[$this->vars->language] : null));
            try {
                $output = $ZC->getName();
                if (is_string($output) && $val != $output) {
                    $multioptions[$key] = "$val ($output)";
                }
            } catch (Exception $e) {}
        }
        }
        
        $form->getElement('currency')->setMultiOptions($multioptions);
        
        // Load Up Languages
        $temparray = array();
        $temp = $counter = null;
        foreach ($this->db->fetchAssoc("SELECT code, language FROM system_languages WHERE active='t' ORDER BY orderby, language") as $temp) {
            $temparray[$temp['code']] = $temp['language'];
        }

        // Translation
        $temparray_nt = $temparray;
        foreach ($temparray as $key => $val) {
            $key = strtolower($key);
            if (isset($this->vars->language) && !preg_match('/^en/', $this->vars->language)) {
                try {
                    #$output = $this->locale->getLanguageTranslation($key, $this->vars->language);
                    $output = $this->locale->getTranslation($key, 'language', $this->vars->language);
                    if (is_string($output) && $val != $output) {
                        $temparray[$key] = "$val ($output)";
                    }
                } catch (Exception $e) {}
            }
            else {
                try {
                    #$output = $this->locale->getLanguageTranslation($key, $key);
                    $output = $this->locale->getTranslation($key, 'language', $key);
                    if (is_string($output) && $val != $output) {
                        $temparray[$key] = "$val ($output)";
                    }
                } catch (Exception $e) {}
            }
        }
        
        $form->getElement('language')->setMultiOptions($temparray);
        
        // DOB
        $sel_lang = ((isset($this->vars->language) && $this->vars->language) ? $this->vars->language : ((isset($this->locale) && $this->locale->getLanguage()) ? $this->locale->getLanguage() : 'en_US'));
        $temparray = array_merge(array('-- ' . $this->locale->getTranslation('month', 'field', $sel_lang) . ' --'), Zend_Locale::getTranslationList('month', $sel_lang));
        $form->getElement('dob_month')->setMultiOptions($temparray);
        
        $temparray = array_merge(array('-- ' . $this->locale->getTranslation('day', 'field', $sel_lang) . ' --'), range(1, 31));
        $form->getElement('dob_day')->setMultiOptions($temparray);
        
        $temparray = array('-- ' . $this->locale->getTranslation('year', 'field', $sel_lang) . ' --');
        for ($i = date('Y'); $i > (date('Y') - 120); $i--) {
            $temparray[$i] = $i;
        }
        $form->getElement('dob_year')->setMultiOptions($temparray);
        // Rearrange the order
        $ORDER = strtolower(Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short')));
        $MO_POS = strpos($ORDER, 'm');
        $DY_POS = strpos($ORDER, 'd');
        $YR_POS = strpos($ORDER, 'y');
        $SORT_ARRAY = array($MO_POS => 'month', $DY_POS => 'day', $YR_POS => 'year');
        ksort($SORT_ARRAY);
        $counter = 0;
        foreach ($SORT_ARRAY as $key) {
            $counter++;
            $form->getElement("dob_$key")->setOrder(30 + $counter);
        }
        
        // Set Some Validators
        $name_validator = new Zend_Validate_Regex("/^[\p{L}-',.][\p{L}-',. ]*[\p{L}-',.]*$/u");
          $name_validator->setMessage('Remove invalid characters and whitespace.', Zend_Validate_Regex::NOT_MATCH);
          
        $form->getElement('first_name')->addValidator($name_validator);
        $form->getElement('middle_name')->addValidator($name_validator);
        $form->getElement('last_name')->addValidator($name_validator);
        //$form->getElement('email')->addValidator('EmailAddress');
