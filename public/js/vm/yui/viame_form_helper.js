/*
 * ViaMe Form Helper
 * TextAreas already done
 */

YAHOO.util.Event.onDOMReady(function() {
    /* Prevent Double Submission */
        YAHOO.namespace("viame.dubsub");
        YAHOO.viame.dubsub.check = function(sType) {
            if (sType.nodeName == 'FORM') {
                if (sType.vm_submitted == true) {
                    if (!confirm('Your request is being processed.  Please be patient.')) {
                        // If they are not patient enough, reset the flag
                        sType.vm_submitted = false;
                    }
                    return false;
                }
                else {
                    sType.vm_submitted = true;
                    // Try to automatically reset the flag after 20 seconds
                    if (sType.id != "undefined" && sType.id) {
                        setTimeout('document.getElementById(\'' + sType.id + '\').vm_submitted = false;', 20000);
                    }
                    return true;
                }
            }
        };
    /* End Prevent Double Submission */
    
    /* Begin Form Validation */
    if (typeof(regula) != "undefined") {
        regula.bind();
        
        YAHOO.namespace("viame.vivin_regula");
        
        YAHOO.viame.vivin_regula.validate = function(sType, aArgs) {
            if (sType.vivregval_canceled) {
                return true;
            }
            
            validationResults = regula.validate(aArgs);
            // { groups: [regula.Group.FirstGroup, regula.Group.SecondGroup] }
            // { elementId: "someElementId" }
            
            if (validationResults.length) {
                // Form Validation; Roll up messages into one
                if (typeof(sType) == "undefined" || (sType.nodeName == 'FORM' && aArgs)) {
                    var message = '';
                    var counter = 0;
                    for(var index in validationResults) {
                	    var validationResult = validationResults[index];
                		
                		if (validationResult.message) {
                    		if (counter) { message += (aArgs.simple ? "" : "<br />") + "\n"; }
                    		message += validationResult.message;
                		}
                		
                		// Add classes to elements or whatever - Also see below
                		
                		counter++;
                	}
                	
                	if (aArgs.simple) {
                	    alert(message);
                	}
                	else {
                    	// Instantiate the Dialog
                    	if (!YAHOO.viame.vivin_regula.error_dialog) {
                        	YAHOO.viame.vivin_regula.error_dialog = new YAHOO.widget.SimpleDialog("vivin_regula_error_dialog", {
                                width: "300px",
                                fixedcenter: true,
                                visible: false,
                                draggable: false, 
                                zindex: 4,
                                modal: true,
                                close: true,
                                text: message,
                                //icon: YAHOO.widget.SimpleDialog.ICON_WARN,
                                constraintoviewport: true,
                                buttons: [ { text: "OK", handler: function() { this.hide(); }, isDefault: true } ]
                            });
                        	YAHOO.viame.vivin_regula.error_dialog.setHeader("Form Errors");
                        	YAHOO.viame.vivin_regula.error_dialog.render(document.body);
                        }
                        else {
                            YAHOO.viame.vivin_regula.error_dialog.setBody(message);
                        }
                    	YAHOO.viame.vivin_regula.error_dialog.show();
                    }
                }
    	        else {
    	            var counter = 0;
                    for(var index in validationResults) {
                		 var validationResult = validationResults[index];
                		 
                		 // Have element with messages
                		 // Add classes to elements or whatever - Also see above
                	}
    	        }
    	        
    	        return false;
	        }
	        else {
	            return true;
	        }
        };
    }
    /* End Form Validation */
    
    
    /* Begin Character(s) Remaining Update */
	    YAHOO.namespace("viame.textarea_limiter");
        YAHOO.viame.textarea_limiter.check = function(sType, limit) {
            if (sType.value.length > limit) {
        		sType.value = sType.value.substring(0, limit);
        	}
        	
        	if (YAHOO.util.Dom.hasClass(YAHOO.util.Dom.getNextSibling(sType), 'description')) {
        	    //alert('update');
        	    if (!sType.nextQuip) {
        	        sType.nextQuip = YAHOO.util.Dom.getNextSibling(sType).innerHTML.replace(/^[\d\s]*/, '');
        	    }
        	    
        	    if (sType.nextQuip.match(/\d/) && !sType.nextQuip.match(/^\d/)) {
        	        YAHOO.util.Dom.getNextSibling(sType).innerHTML = sType.nextQuip.replace(/\d+/, (limit - sType.value.length));
        	    }
        	    else {
            	    YAHOO.util.Dom.getNextSibling(sType).innerHTML = (limit - sType.value.length) + ' ' + sType.nextQuip;
            	}
        	}
        };
	/* End Character(s) Remaining Update */
    
    
    /* Begin Calendar Helper */
    //if (YAHOO.util.Selector.query('input[type=text].vmfh_date').length) {
    	YAHOO.namespace("viame.calendar.helper");
    	YAHOO.viame.calendar.helper.hover = false;
    	YAHOO.viame.calendar.helper.format = 'mm/dd/yyyy';
    	  if (typeof(vm_date_format_short) != "undefined" && vm_date_format_short) { YAHOO.viame.calendar.helper.format = vm_date_format_short; }
    	  
    	YAHOO.viame.calendar.dialog = new YAHOO.widget.Dialog("vm_calendar_container", {
            visible: false,
            draggable: true,
            close: true,
			effect: { effect: YAHOO.widget.ContainerEffect.FADE, duration: 0.25 }
        });
        YAHOO.viame.calendar.dialog.renderEvent.subscribe(handleRender, YAHOO.viame.calendar.dialog, true);
        YAHOO.viame.calendar.dialog.setHeader('Select Date');
        YAHOO.viame.calendar.dialog.setBody('<div id="vmfh_calendar_helper_date"></div>');
		YAHOO.viame.calendar.dialog.setFooter('<div style="clear: both;"><div id="vmfh_calendar_helper_time" style="display: none; text-align: center;"><select name="vmfh_calendar_helper_time_hour" id="vmfh_calendar_helper_time_hour"><option value="00">00</option><option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option><option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option><option value="09">09</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option></select> : <select name="vmfh_calendar_helper_time_minute" id="vmfh_calendar_helper_time_minute"><option value="00">00</option><option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option><option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option><option value="09">09</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option><option value="24">24</option><option value="25">25</option><option value="26">26</option><option value="27">27</option><option value="28">28</option><option value="29">29</option><option value="30">30</option><option value="31">31</option><option value="32">32</option><option value="33">33</option><option value="34">34</option><option value="35">35</option><option value="36">36</option><option value="37">37</option><option value="38">38</option><option value="39">39</option><option value="40">40</option><option value="41">41</option><option value="42">42</option><option value="43">43</option><option value="44">44</option><option value="45">45</option><option value="46">46</option><option value="47">47</option><option value="48">48</option><option value="49">49</option><option value="50">50</option><option value="51">51</option><option value="52">52</option><option value="53">53</option><option value="54">54</option><option value="55">55</option><option value="56">56</option><option value="57">57</option><option value="58">58</option><option value="59">59</option></select> : <select name="vmfh_calendar_helper_time_second" id="vmfh_calendar_helper_time_second"><option value="00">00</option><option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option><option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option><option value="09">09</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option><option value="24">24</option><option value="25">25</option><option value="26">26</option><option value="27">27</option><option value="28">28</option><option value="29">29</option><option value="30">30</option><option value="31">31</option><option value="32">32</option><option value="33">33</option><option value="34">34</option><option value="35">35</option><option value="36">36</option><option value="37">37</option><option value="38">38</option><option value="39">39</option><option value="40">40</option><option value="41">41</option><option value="42">42</option><option value="43">43</option><option value="44">44</option><option value="45">45</option><option value="46">46</option><option value="47">47</option><option value="48">48</option><option value="49">49</option><option value="50">50</option><option value="51">51</option><option value="52">52</option><option value="53">53</option><option value="54">54</option><option value="55">55</option><option value="56">56</option><option value="57">57</option><option value="58">58</option><option value="59">59</option></select></div></div>');
		YAHOO.viame.calendar.dialog.render(document.body);
		YAHOO.viame.calendar.dialog.showEvent.subscribe(function() { if (YAHOO.env.ua.ie) { YAHOO.viame.calendar.dialog.fireEvent("changeContent"); } });
		
		/*
		YAHOO.util.Event.on(document, "click", function(e) {
            var el = YAHOO.util.Event.getTarget(e);
            var dialogEl = YAHOO.viame.calendar.dialog.element;
            if (el != dialogEl && !YAHOO.util.Dom.isAncestor(dialogEl, el)) {
                YAHOO.viame.calendar.dialog.hide();
            }
        });
        */
        
    	YAHOO.viame.calendar.helper.calendar = new YAHOO.widget.Calendar("vmfh_calendar_helper_date", {
			navigator: true,
            iframe: false,
            hide_blank_weeks: true
        });
        YAHOO.viame.calendar.helper.calendar.selectEvent.subscribe(handleSelect, YAHOO.viame.calendar.helper.calendar, true);
        YAHOO.viame.calendar.helper.calendar.render();
        YAHOO.viame.calendar.helper.calendar.show();
        YAHOO.viame.calendar.helper.calendar.renderEvent.subscribe(function() { YAHOO.viame.calendar.dialog.fireEvent("changeContent"); });
        
    	function handleRender(type, args, obj) {
    	    YAHOO.util.Event.addListener('vm_calendar_container', 'mouseover', overCal);
            YAHOO.util.Event.addListener('vm_calendar_container', 'mouseout', outCal);
    	}
    	
    	function handleSelect(type, args, obj) {
    		var dates = args[0]; 
    		var date = dates[0];
    		var year = date[0], month = date[1], day = date[2];
            
            var temp = YAHOO.viame.calendar.helper.format;
            temp = temp.replace(/m+/i, (month < 10 ? '0' + month : month));
            temp = temp.replace(/d+/i, (day < 10 ? '0' + day : day));
            temp = temp.replace(/y+/i, year);
            
            if (YAHOO.util.Dom.hasClass(YAHOO.viame.calendar.helper.item, 'vmfh_datetime')) {
                temp += ' ' + YAHOO.util.Dom.get('vmfh_calendar_helper_time_hour').value + ':' + YAHOO.util.Dom.get('vmfh_calendar_helper_time_minute').value + ':' + YAHOO.util.Dom.get('vmfh_calendar_helper_time_second').value; 
            }
            
    		//YAHOO.viame.calendar.helper.item.value = year + "-" + (month < 10 ? '0' + month : month) + "-" + (day < 10 ? '0' + day : day);
    		YAHOO.viame.calendar.helper.item.value = temp;
    		YAHOO.viame.calendar.dialog.hide();
    		YAHOO.viame.calendar.helper.calendar.hover = false;
    	}
    	
    	function showCal(iEl) {
    	    YAHOO.viame.calendar.dialog.cfg.setProperty("context", [iEl, "tl", "bl"]);
		    YAHOO.viame.calendar.dialog.align("tl", "bl");
		    
		    if (YAHOO.util.Dom.hasClass(iEl, 'vmfh_datetime')) {
		        YAHOO.util.Dom.get('vmfh_calendar_helper_time').style.display = 'block';
		    }
		    else {
		        YAHOO.util.Dom.get('vmfh_calendar_helper_time').style.display = 'none';
		    }
		    
    	    YAHOO.viame.calendar.helper.item = iEl;
    	    if (iEl.value) {
    	        var date_parts = {};
    	        var value = iEl.value;
    	        var format = YAHOO.viame.calendar.helper.format;

    	        format = format.replace(/\./g, '\\.');
    	        format = format.replace(/\s/g, '\\s*');
    	        var parts = new Array('m', 'd', 'y');
    	        for (var part in parts) {
    	            var temp_value = value.replace(/\s+\d{1,2}:\d{1,2}:\d{1,2}\s*$/, '');
    	            var temp_format = format;
    	            var re = new RegExp( parts[part] + '+', 'ig' );
    	            temp_format = temp_format.replace(re, '(z)');
    	            for (var temp_part in parts) {
    	                var re = new RegExp( parts[temp_part] + '+', 'ig' );
    	                temp_format = temp_format.replace(re, '\z');
    	            }
    	            temp_format = temp_format.replace(/z/g, '\\d+');
    	            temp_value = temp_value.replace(new RegExp(temp_format), "$1");
    	            //alert(temp_value);
    	            date_parts[parts[part]] = temp_value;
    	        }
    	        
    	        if (!isNaN(date_parts['m']) && !isNaN(date_parts['d']) && !isNaN(date_parts['y'])) {
        	        var date = date_parts['m'] + '/' + date_parts['d'] + '/' + date_parts['y'];
        	        YAHOO.viame.calendar.helper.calendar.cfg.setProperty('selected', date); 
        	        YAHOO.viame.calendar.helper.calendar.cfg.setProperty('pagedate', new Date(date), true); 
        	        YAHOO.viame.calendar.helper.calendar.render(); 
        	    }
        	    
        	    if (YAHOO.util.Dom.hasClass(iEl, 'vmfh_datetime')) {
        	        if (value.match(/.*?\s+(\d{1,2}:\d{1,2}:\d{1,2})\s*$/)) {
            	        var timematch = value.replace(/.*?\s+(\d{1,2}:\d{1,2}:\d{1,2})\s*$/, "$1");
            	        var timeparts = timematch.split(':');
            	        YAHOO.util.Dom.get('vmfh_calendar_helper_time_hour').value = (timeparts[0].length < 2 ? '0' : '') + timeparts[0];
            	        YAHOO.util.Dom.get('vmfh_calendar_helper_time_minute').value = (timeparts[1].length < 2 ? '0' : '') + timeparts[1];
            	        YAHOO.util.Dom.get('vmfh_calendar_helper_time_second').value = (timeparts[2].length < 2 ? '0' : '') + timeparts[2];
            	    }
            	    else {
            	        YAHOO.util.Dom.get('vmfh_calendar_helper_time_hour').value = '00';
            	        YAHOO.util.Dom.get('vmfh_calendar_helper_time_minute').value = '00';
            	        YAHOO.util.Dom.get('vmfh_calendar_helper_time_second').value = '00';
            	    }
        	    }
        	        
    	    }
    	    else {
    	        var d = new Date();
    	        //alert(d.getMonth() + 1 + '/' + d.getDate() + '/' + d.getFullYear());
    	        YAHOO.viame.calendar.helper.calendar.cfg.setProperty('selected', d.getMonth() + 1 + '/' + d.getDate() + '/' + d.getFullYear());
    	        YAHOO.viame.calendar.helper.calendar.cfg.setProperty('pagedate', d, true); 
    	        YAHOO.viame.calendar.helper.calendar.render(); 
    	        YAHOO.util.Dom.get('vmfh_calendar_helper_time_hour').value = '00';
    	        YAHOO.util.Dom.get('vmfh_calendar_helper_time_minute').value = '00';
    	        YAHOO.util.Dom.get('vmfh_calendar_helper_time_second').value = '00';
    	    }
    	    
    	    YAHOO.viame.calendar.dialog.show();
    		
    		if (YAHOO.env.ua.opera && document.documentElement) {
    			// Opera needs to force a repaint
    			document.documentElement.className += "";
    		}
    	}
    	
    	function hideCal(iEl) {
    	    if (!YAHOO.viame.calendar.helper.hover) {
    	        YAHOO.viame.calendar.dialog.hide();
    	    }
    	}
    	
    	function overCal() {
    	    YAHOO.viame.calendar.helper.hover = true;
    	}
    	function outCal() {
    	    YAHOO.viame.calendar.helper.hover = false;
    	}
    	
    	YAHOO.util.Event.on(YAHOO.util.Selector.query('input[type=text].vmfh_date'), 'focus', function() { showCal(this); });
    	YAHOO.util.Event.on(YAHOO.util.Selector.query('input[type=text].vmfh_date'), 'blur', function() { hideCal(this); });
    //}
	/* End Calendar Helper */
	
	
	/* Begin Phone Helper */
    if (YAHOO.util.Selector.query('input[type=text].vmfh_phone').length) {
        YAHOO.namespace("viame.phone.helper");
        
        $('head').prepend('<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/11.0.0/css/intlTelInput.css">');
        
        $.getScript('https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/11.0.0/js/intlTelInput.min.js', function(){
            
            $('input[type=text].vmfh_phone')
                .intlTelInput({
                    utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/11.0.0/js/utils.js',
                    preferredCountries: ['us'],
                    initialCountry: "us",
                    formatOnDisplay: false
                });
            $('input[type=text].vmfh_phone').focus(function(e) {
                if ( $(e.target).intlTelInput("isValidNumber") ) {
                    $(e.target).val( $(e.target).intlTelInput("getNumber", intlTelInputUtils.numberFormat.NATIONAL) );
                }
            });
            $('input[type=text].vmfh_phone').blur(function(e) {
                if ( $(e.target).intlTelInput("isValidNumber") ) {
                    $(e.target).val( $(e.target).intlTelInput("getNumber", intlTelInputUtils.numberFormat.INTERNATIONAL) );
                }
            });
            
            //$('input[type=text].vmfh_phone').keyup(function(e) {
            //    $(e.target).val( $(e.target).intlTelInput("getNumber", intlTelInputUtils.numberFormat.NATIONAL) );
            //});
            
            //setTimeout(function() {
            //    $('input[type=text].vmfh_phone').trigger('blur');
            //}, 1000);

        });
    }
	/* End Phone Helper */
	
	
	/* Begin Quote Autocomplete Helper */
	if (YAHOO.util.Selector.query('input[type=text].vmfh_acss').length) {
    	if(typeof(YAHOO.Finance) == "undefined" || typeof(YAHOO.Finance.SymbolSuggest) == "undefined") {
            YAHOO.namespace("YAHOO.Finance.SymbolSuggest");
        }
        
        YAHOO.Finance.SymbolSuggest.ssCallback = (function(data) {
            YAHOO.viame.quote.autocomplete.helper_linenumber = 0;
            var loadedSymbols = new Object();
            
            for (var i = 0; i < data.ResultSet.Result.length; i++) {
                var item = data.ResultSet.Result[i];
                if (item.type == 'I' && !data.ResultSet.Result[i].symbol.match(/^\^/)) {
                    data.ResultSet.Result[i].symbol = '^' + item.symbol;
                }
                
                //if (item.exch == 'OBB' || item.exch == 'PNK' || item.exch == 'WCB') {
                if (item.exch == 'OBB' || item.exch == 'PNK') {
                    data.ResultSet.Result[i].symbol = item.symbol.replace(/\..*/, '');
                }
                
                // Uniques
                if (typeof(loadedSymbols[data.ResultSet.Result[i].symbol]) != 'undefined') {
                    data.ResultSet.Result.splice(i, 1);
                    i = i -1;
                }
                else { loadedSymbols[data.ResultSet.Result[i].symbol] = true; }
        	}
    
        	if (YAHOO.util.ScriptNodeDataSource.callbacks[YAHOO.util.ScriptNodeDataSource._nId - 1]) {
        	    YAHOO.util.ScriptNodeDataSource.callbacks[YAHOO.util.ScriptNodeDataSource._nId - 1](data);
        	}
        });
        
        YAHOO.namespace("viame.quote.autocomplete.helper");
        
        // Instantiate DataSource
        //YAHOO.viame.quote.autocomplete.helper.oDS = new YAHOO.util.ScriptNodeDataSource("http://y.d.yimg.com/autoc.finance.yahoo.com/autoc?");
        //YAHOO.viame.quote.autocomplete.helper.oDS = new YAHOO.util.ScriptNodeDataSource("http://autoc.finance.yahoo.com/autoc?");
        YAHOO.viame.quote.autocomplete.helper.oDS = new YAHOO.util.ScriptNodeDataSource("https://static.viame.com/yfacp/autoc?");
        YAHOO.viame.quote.autocomplete.helper.oDS.responseSchema = {
            resultsList: "ResultSet.Result",
            fields: ["symbol", "name", "exch", "type", "exchDisp", "typeDisp"]
        };
        
        // Setting to default value for demonstration purposes.
        // The webservice needs to support execution of a callback function.
        YAHOO.viame.quote.autocomplete.helper.oDS.scriptCallbackParam = "callback";
    	YAHOO.viame.quote.autocomplete.helper.oDS.generateRequestCallback = (function(id) {
            return "&" + this.scriptCallbackParam + "=YAHOO.Finance.SymbolSuggest.ssCallback" ;
        });
    	
    	
        // Instantiate AutoComplete(s)
        var qfacn = YAHOO.util.Selector.query('input[type=text].vmfh_acss');
        YAHOO.viame.quote.autocomplete.helper.sc_el = new Array();
        YAHOO.viame.quote.autocomplete.helper.oAC = new Array();
        for (var i = 0; i < qfacn.length; i++) {
            var node = qfacn[i];
            YAHOO.viame.quote.autocomplete.helper.oAC[i] = {};
            
            // Create Autocomplete Container
            YAHOO.viame.quote.autocomplete.helper.sc_el[i] = document.createElement("div");
            YAHOO.viame.quote.autocomplete.helper.sc_el[i].setAttribute("class", 'quote_symbol_ac_container');
            YAHOO.util.Dom.generateId(YAHOO.viame.quote.autocomplete.helper.sc_el[i]);
        	node.parentNode.appendChild(YAHOO.viame.quote.autocomplete.helper.sc_el[i]);
        	
        	//YAHOO.util.Dom.setXY(YAHOO.viame.quote.autocomplete.helper.sc_el[i], YAHOO.util.Dom.getXY(node));
        	
            YAHOO.viame.quote.autocomplete.helper.oAC[i] = new YAHOO.widget.AutoComplete(node, YAHOO.viame.quote.autocomplete.helper.sc_el[i], YAHOO.viame.quote.autocomplete.helper.oDS);
            YAHOO.viame.quote.autocomplete.helper.oAC[i].counter = i;
            
            //YAHOO.util.Dom.setX(YAHOO.viame.quote.autocomplete.helper.sc_el[i], YAHOO.util.Dom.getX(node));
        	//YAHOO.util.Dom.setY(YAHOO.viame.quote.autocomplete.helper.sc_el[i], YAHOO.util.Dom.getY(node) + node.offsetHeight);
            //YAHOO.viame.quote.autocomplete.helper.sc_el[i].style.width = '400px';
            // For IE
            YAHOO.util.Dom.setX(YAHOO.viame.quote.autocomplete.helper.sc_el[i], 0);
            
            // Bump up the query delay to reduce server load
            //YAHOO.viame.quote.autocomplete.helper.oAC[i].queryDelay = 0;
            YAHOO.viame.quote.autocomplete.helper.oAC[i].animVert = false;
            YAHOO.viame.quote.autocomplete.helper.oAC[i].animHoriz = false;
            YAHOO.viame.quote.autocomplete.helper.oAC[i].animSpeed = 1.0;
            YAHOO.viame.quote.autocomplete.helper.oAC[i].autoHighlight = false;
            //YAHOO.viame.quote.autocomplete.helper.oAC[i].useIFrame = true;
            
            if (YAHOO.util.Dom.hasClass(node, 'multiple')) {
                YAHOO.viame.quote.autocomplete.helper.oAC[i].delimChar = [","," "];
                YAHOO.viame.quote.autocomplete.helper.oAC[i].setFooter('<em>Tip:</em> Use comma (,) to separate multiple quotes.');
            }
            else if (YAHOO.util.Dom.hasClass(node, 'autogo')) {
                //define your itemSelect handler function:
                YAHOO.viame.quote.autocomplete.helper.oAC[i].itemSelectHandler = function(sType, aArgs) {
                	YAHOO.util.Dom.getAncestorByTagName(aArgs[1], 'form').submit();
                };
                YAHOO.viame.quote.autocomplete.helper.oAC[i].itemSelectEvent.subscribe(YAHOO.viame.quote.autocomplete.helper.oAC[i].itemSelectHandler);
            }
        
            // The webservice needs custom parameters
            YAHOO.viame.quote.autocomplete.helper.oAC[i].generateRequest = function(sQuery) {
                return "query=" + sQuery + "&region=US&lang=en-US";
            };
            
            // Result data passed as object for easy access from custom formatter.
            YAHOO.viame.quote.autocomplete.helper.oAC[i].resultTypeList = false;
            
            YAHOO.viame.quote.autocomplete.helper.oAC[i].formatResult = function(oResultData, sQuery, sResultMatch) {
                var symbol = oResultData.symbol;
        		var name = " " + oResultData.name;
        		var exch = oResultData.exch;
        		var exchDisp = oResultData.exchDisp;
        		var type = oResultData.type;
        		var typeDisp = oResultData.typeDisp;
        		var i = symbol.toLowerCase().indexOf(sQuery.toLowerCase());
        		if(i == 0) {
        		  symbol = '<em>' + symbol.substr(0, sQuery.length) + '</em>' + symbol.substr(sQuery.length);
        		} else {
        		  i = name.toLowerCase().indexOf(" " + sQuery.toLowerCase());
        		  if(i != -1) {
        			name = name.substr(0, i) + ' <em>' + name.substr(i + 1, sQuery.length) + '</em>' + name.substr(i+sQuery.length + 1);
        		  }
        		}
        		var start_wrp = "<div class='" + (((YAHOO.viame.quote.autocomplete.helper_linenumber % 2) == 0) ? 'even' : 'odd') + "'>";
        		YAHOO.viame.quote.autocomplete.helper_linenumber++;
        		var symbol_wrp = "<div class='symbol'>" + symbol + '</div>';
        		var name_wrp = "<div class='name'>" + name.substr(1) + '</div>';
        		var exch_type_wrp = "<div class='exch_type_wrapper'>";
        		if(typeDisp) {
        		  exch_type_wrp += typeDisp + ' - ';
        		}
        		else if (exch && exch == 'CCY') {
        		    exch_type_wrp += 'Currency - ';
        		}
        		if (exchDisp) {
            	  exch_type_wrp += exchDisp;
        		} else if (exch) {
        			exch_type_wrp += exch;
        		}
        		exch_type_wrp += '</div>';
        		var end_wrp = '</div>';
        			return start_wrp + symbol_wrp + exch_type_wrp + name_wrp + end_wrp;
            };
            
            // Nudge AutoComplete into viewable area
            YAHOO.viame.quote.autocomplete.helper.oAC[i].doBeforeExpandContainer = function(elTextbox, elContainer, sQuery, aResults) {
                if ((YAHOO.util.Dom.getX(elContainer) + elContainer.offsetWidth + 15) > YAHOO.util.Dom.getViewportWidth()) {
                    while ((YAHOO.util.Dom.getX(elContainer) + elContainer.offsetWidth + 15) > YAHOO.util.Dom.getViewportWidth()) {
                        YAHOO.util.Dom.setX(elContainer, YAHOO.util.Dom.getX(elContainer) - 10);
                    }
                }
                return true;
            };
        }
    }
	/* End Quote Autocomplete Helper */
	
	
	/* Begin Contact Autocomplete Helper */
	if (YAHOO.util.Selector.query('input[type=text].vmfh_acvc').length) {
    	YAHOO.namespace("viame.contact.autocomplete.helper");
        
        // Instantiate AutoComplete(s)
        var ccacn = YAHOO.util.Selector.query('input[type=text].vmfh_acvc');
        YAHOO.viame.contact.autocomplete.helper.sc_el = new Array();
        YAHOO.viame.contact.autocomplete.helper.oAC = new Array();
        YAHOO.viame.contact.autocomplete.helper.oDS = new Array();
        
        for (var i = 0; i < ccacn.length; i++) {
            // Reformat
            var pNode = new YAHOO.util.Element(ccacn[i].parentNode);
            var node = pNode.removeChild(ccacn[i]);
            var wrapper = document.createElement('div');
              YAHOO.util.Dom.addClass(wrapper, 'vmfh_acvc_wrapper');
            var cleardiv = document.createElement('div');
              cleardiv.style.clear = 'both';
            var unordered_list = document.createElement('ul');
            var list_item = document.createElement('li');
              YAHOO.util.Dom.addClass(list_item, 'vmfh_acvc_input');
            
            list_item.appendChild(node);
            unordered_list.appendChild(list_item);
            wrapper.appendChild(unordered_list);
            wrapper.appendChild(cleardiv);
            if (YAHOO.util.Dom.getFirstChild(pNode)) {
                YAHOO.util.Dom.insertBefore(wrapper, YAHOO.util.Dom.getFirstChild(pNode));
            } else {
                pNode.appendChild(wrapper);
            }
            
            YAHOO.util.Event.on(wrapper, 'click', function (e, obj) {
                obj.focus();
            }, node);
            
            // Auto-Expander
            YAHOO.util.Event.on(node, 'keyup', function (e, obj) { if (obj.value) { obj.style.width = obj.value.length * 10 + 'px'; } }, node);
            YAHOO.util.Event.on(node, 'focus', function (e, obj) { YAHOO.util.Dom.addClass(YAHOO.util.Dom.getAncestorByClassName(obj, 'vmfh_acvc_wrapper'), 'active'); }, node);
            YAHOO.util.Event.on(node, 'blur', function (e, obj) { obj.style.width = '50px'; YAHOO.util.Dom.removeClass(YAHOO.util.Dom.getAncestorByClassName(obj, 'vmfh_acvc_wrapper'), 'active'); }, node);

            YAHOO.viame.contact.autocomplete.helper.oAC[i] = {};
            YAHOO.viame.contact.autocomplete.helper.oDS[i] = {};
            
            // Instantiate DataSource
            var sourceURL;
              if (typeof(vm_pre) != "undefined" && vm_pre && vm_pre.match(/^\/via\//)) { sourceURL = vm_pre + '/contact/widget/p/format/json/'; }
              else { sourceURL = '/contact/widget/p/format/json/'; }
              
              if (YAHOO.util.Dom.hasClass(node, 'contactsonly')) { sourceURL += 'contacts/only/'; }
              if (YAHOO.util.Dom.hasClass(node, 'nocontacts')) { sourceURL += 'no/contacts/'; }
              if (YAHOO.util.Dom.hasClass(node, 'noprofiles')) { sourceURL += 'no/profiles/'; }
              if (YAHOO.util.Dom.hasClass(node, 'addgroups')) { sourceURL += 'add/groups/'; }
            YAHOO.viame.contact.autocomplete.helper.oDS[i] = new YAHOO.util.XHRDataSource(sourceURL);
            YAHOO.viame.contact.autocomplete.helper.oDS[i].maxCacheEntries = 0;
            YAHOO.viame.contact.autocomplete.helper.oDS[i].responseType = YAHOO.util.XHRDataSource.TYPE_JSON;
            YAHOO.viame.contact.autocomplete.helper.oDS[i].responseSchema = {
                resultsList : "ResultSet.Result",
                fields: ["p_name", "p_id", "p_site_admin", "p_active", "type", "b_site_admin", "b_active", "c_id", "c_name", "c_hostname", "vc_creation", "vc_display", "vc_description", "vc_message", "vc_auto_reciprocate", "vc_status", "vc_active"],
                metaFields : {
                    error : "Error",
                    query : "Query"
                }
            };
            
            // Create Autocomplete Container
            YAHOO.viame.contact.autocomplete.helper.sc_el[i] = document.createElement("div");
            YAHOO.viame.contact.autocomplete.helper.sc_el[i].setAttribute("class", 'contact_ac_container');
            YAHOO.util.Dom.generateId(YAHOO.viame.contact.autocomplete.helper.sc_el[i]);
        	node.parentNode.appendChild(YAHOO.viame.contact.autocomplete.helper.sc_el[i]);
        	
        	//YAHOO.util.Dom.setXY(YAHOO.viame.contact.autocomplete.helper.sc_el[i], YAHOO.util.Dom.getXY(node));
        	
            YAHOO.viame.contact.autocomplete.helper.oAC[i] = new YAHOO.widget.AutoComplete(node, YAHOO.viame.contact.autocomplete.helper.sc_el[i], YAHOO.viame.contact.autocomplete.helper.oDS[i]);
            YAHOO.viame.contact.autocomplete.helper.oAC[i].counter = i;
            YAHOO.viame.contact.autocomplete.helper.oAC[i].item_counter = 0;
            YAHOO.viame.contact.autocomplete.helper.oAC[i].total_item_counter = 0;
            YAHOO.viame.contact.autocomplete.helper.oAC[i].uniques = { "G" : {}, "V": {} };
            
            //YAHOO.viame.contact.autocomplete.helper.oAC[i].hidden_element = YAHOO.util.Dom.getElementsByClassName ('vmfh_acvc_hidden' , 'input' , YAHOO.util.Dom.getAncestorByTagName(node, 'form'))[0];
            
            //YAHOO.util.Dom.setX(YAHOO.viame.contact.autocomplete.helper.sc_el[i], YAHOO.util.Dom.getX(node));
        	//YAHOO.util.Dom.setY(YAHOO.viame.contact.autocomplete.helper.sc_el[i], YAHOO.util.Dom.getY(node) + node.offsetHeight);
            //YAHOO.viame.contact.autocomplete.helper.sc_el[i].style.width = '400px';
            
            
            
            // Bump up the query delay to reduce server load
            YAHOO.viame.contact.autocomplete.helper.oAC[i].queryDelay = 0.2;
            //YAHOO.viame.contact.autocomplete.helper.oAC[i].animVert = false;
            //YAHOO.viame.contact.autocomplete.helper.oAC[i].animHoriz = false;
            //YAHOO.viame.contact.autocomplete.helper.oAC[i].animSpeed = 1.0;
            //YAHOO.viame.contact.autocomplete.helper.oAC[i].autoHighlight = true;
            //YAHOO.viame.contact.autocomplete.helper.oAC[i].useIFrame = true;
            //YAHOO.viame.contact.autocomplete.helper.oAC[i].alwaysShowContainer = true;
            YAHOO.viame.contact.autocomplete.helper.oAC[i].allowBrowserAutocomplete = false;
            YAHOO.viame.contact.autocomplete.helper.oAC[i].forceSelection = true;
            

            function addListItem(myAC, oData) {
                var elInput = myAC.getInputEl();
                var elInputName = YAHOO.util.Dom.getAttribute(elInput, 'name');
                
                var newName = (oData.type=='G' ? 'G' : (oData.vc_status && oData.vc_active ? 'C' : 'V')) + '-' + oData.p_id;
                
                var not_present = true;
                if (YAHOO.util.Dom.hasClass(elInput, 'unique')) {
                    if (myAC.uniques[(oData.type=='G' ? 'G' : 'V')][oData.p_id]) {
                        not_present = false;
                    }
                    else {
                        myAC.uniques[(oData.type=='G' ? 'G' : 'V')][oData.p_id] = true;
                    }
                }
                
                if (not_present) {
                    myAC.item_counter++;
                    
                    var el = document.createElement('li');
                      el.id = 'vmfh_acvc-gen-li-' + elInputName + '-' + myAC.item_counter;
                      YAHOO.util.Dom.addClass(el, (oData.type=='G' ? 'group' : (oData.vc_status && oData.vc_active ? 'contact' : 'via')));
                      el.innerHTML = ((oData.vc_status && oData.vc_active && oData.vc_display) ? oData.vc_display : oData.p_name);
                      el.innerHTML += '<a href="javascript:void(null);" title="Click To Remove" class="remove">x</a><input type="hidden" name="' +
                        elInputName + '_ids[]" value="' + newName + '" />';
                    YAHOO.util.Dom.insertBefore(el, YAHOO.util.Dom.getAncestorByTagName(elInput, 'li'));
                    YAHOO.util.Event.on(YAHOO.util.Dom.getFirstChild(el), 'click', function (e, obj) {
                        var myAC = obj[0];
                        var el = obj[1];
                        var elInput = obj[2];
                        
                        var pNode = new YAHOO.util.Element(el.parentNode);
                        var node = pNode.removeChild(el);
                        
                        if (YAHOO.util.Dom.hasClass(elInput, 'unique')) {
                            var el_el = el.getElementsByTagName('input')[0];
                            var parts = el_el.value.split('-');
                            myAC.uniques[(parts[0]=='G' ? 'G' : 'V')][parts[1]] = false;
                        }
                        
                        myAC.total_item_counter--;
                        
                        if (myAC.total_item_counter < 1) {
                            elInput.parentNode.style.display = 'block';
                        }
                    }, [myAC, el, elInput]);
                    myAC.total_item_counter++;
                }
                
                return true;
            }
            
            // Pre-Load Data
            var preload = new Array();
            eval('if (typeof(' + YAHOO.util.Dom.getAttribute(node, 'name') + '_preload_data) != "undefined") { preload = ' + YAHOO.util.Dom.getAttribute(node, 'name') + '_preload_data; }');
            if (preload && preload.length) {
                var myAC = YAHOO.viame.contact.autocomplete.helper.oAC[i];
                
                for (var j = 0; j < preload.length; j++) {
                    addListItem(myAC, preload[j]);
                }
                
                var elInput = myAC.getInputEl();
                
                // Make sure the label and element dt and dd tags are display block
                var ts;
                if (elInput.id) { ts = elInput.id; }
                else if (YAHOO.util.Dom.getAttribute(elInput, 'name')) { ts = YAHOO.util.Dom.getAttribute(elInput, 'name'); }
                if (ts) {
                    tl = YAHOO.util.Dom.get(ts + '-label');
                    if (tl) { YAHOO.util.Dom.setStyle(tl, 'display', 'block'); }
                    te = YAHOO.util.Dom.get(ts + '-element');
                    if (te) { YAHOO.util.Dom.setStyle(te, 'display', 'block'); }
                }
                
                if (!YAHOO.util.Dom.hasClass(elInput, 'multiple')) {
            	    elInput.parentNode.style.display = 'none';
            	}
            }
            // End Pre-Load Data
            
            YAHOO.viame.contact.autocomplete.helper.oAC[i].itemSelectHandler = function(sType, aArgs) {
                var myAC = aArgs[0]; // reference back to the AC instance
                var elLI = aArgs[1]; // reference to the selected LI element
                var oData = aArgs[2]; // object literal of selected item's result data
                
                addListItem(myAC, oData);
                
                var elInput = myAC.getInputEl();
                
                elInput.value = '';
            	
            	if (YAHOO.util.Dom.hasClass(elInput, 'autogo')) {
            	    YAHOO.util.Dom.getAncestorByTagName(elInput, 'form').submit();
            	} else if (!YAHOO.util.Dom.hasClass(elInput, 'multiple')) {
            	    elInput.parentNode.style.display = 'none';
            	}
            	
            	return true;
            };
            YAHOO.viame.contact.autocomplete.helper.oAC[i].itemSelectEvent.subscribe(YAHOO.viame.contact.autocomplete.helper.oAC[i].itemSelectHandler);
            
            // Result data passed as object for easy access from custom formatter.
            YAHOO.viame.contact.autocomplete.helper.oAC[i].resultTypeList = false;
            
            YAHOO.viame.contact.autocomplete.helper.oAC[i].formatResult = function(oResultData, sQuery, sResultMatch) {
                var id = oResultData.p_id;
                var name = oResultData.p_name;
                
                if (oResultData.type=='G') { name += ' (Group / ' + oResultData.c_hostname + ')'; }
                else if (oResultData.vc_active) { name += ' (Contact' + (oResultData.vc_display ? ' : ' + oResultData.vc_display : '') + ')'; }
                        
        		// Bold up match
        		var i = name.toLowerCase().indexOf(sQuery.toLowerCase());
        		if(i == 0) {
        		    name = '<em>' + name.substr(0, sQuery.length) + '</em>' + name.substr(sQuery.length);
        		} else {
        		    i = name.toLowerCase().indexOf(" " + sQuery.toLowerCase());
        		    if(i != -1) {
        			    name = name.substr(0, i) + ' <em>' + name.substr(i + 1, sQuery.length) + '</em>' + name.substr(i+sQuery.length + 1);
        		    }
        		}
        		
        		var start_wrp = "<div class='" + (((YAHOO.viame.contact.autocomplete.helper_linenumber % 2) == 0) ? 'even' : 'odd') + "'>";
        		YAHOO.viame.contact.autocomplete.helper_linenumber++;        		
        		var end_wrp = '</div>';
        		
        		return start_wrp + name + end_wrp;
            };
            
            YAHOO.viame.contact.autocomplete.helper.oAC[i].doBeforeLoadData = function(sQuery, oResponse, oPayload)  {
                YAHOO.viame.contact.autocomplete.helper_linenumber = oResponse.results.length;
                return true;
            };
            
            YAHOO.viame.contact.autocomplete.helper.oAC[i].doBeforeExpandContainer = function(elTextbox, elContainer, sQuery, aResults) {
                ac_footer = YAHOO.util.Dom.getElementsByClassName ('yui-ac-ft' , 'div' , elContainer)[0];
                if (ac_footer) {
                    ac_footer.style.display = 'block';
                    ac_footer.innerHTML = '<div>Results for "' + decodeURIComponent(sQuery.replace(/\+/g,  " ")) + '"</div>';
                    if (aResults.length) {
                        ac_footer.innerHTML += '<div>Displaying top ' + aResults.length + ' results.</div>';
                    }
                    return true;
                }
            };
        }
    }
	/* End Contact Autocomplete Helper */
	
	
	/* Begin Simple Rich Text Editor Helper */
	if (YAHOO.util.Selector.query('textarea.vmfh_simple_textarea').length) {
        YAHOO.namespace("viame.simple_rich_text_editor.helper");

        YAHOO.viame.simple_rich_text_editor.helper.sta = YAHOO.util.Dom.getElementsByClassName('vmfh_simple_textarea');
        
        YAHOO.util.Get.script(['/js/redactor/redactor.min.js', '/js/redactor/fullscreen.js'], { 
            onSuccess: function() {
                YAHOO.util.Get.css('/js/redactor/redactor.css');
                $('textarea.vmfh_simple_textarea').redactor({
    			    minHeight: 80,
    			    buttonSource: false,
    			    buttons: ['bold', 'italic', 'underline', 'deleted', '|',
                        'unorderedlist', 'orderedlist', 'outdent', 'indent', '|',
                        'image', 'link', '|',
                        'fontcolor', 'backcolor', '|', 'alignment', '|', 'horizontalrule']
    			});
            }
        });
        
        /*
        YAHOO.util.Get.script("/js/nicEdit/nicEdit.js", { 
            onSuccess: function() {
                for (var i = 0; i < YAHOO.viame.simple_rich_text_editor.helper.sta.length; i++) {
                    new nicEditor({
                        iconsPath: '/js/nicEdit/nicEditorIcons.gif',
                        maxHeight: 120,
                        fullPanel: false,
                        buttonList: [ 'bold', 'italic', 'underline', 'strikethrough', 'forecolor', 'bgcolor', 'ol', 'ul', 'indent', 'outdent', 'link', 'unlink', 'removeformat'  ]
                    }).panelInstance(YAHOO.viame.simple_rich_text_editor.helper.sta[i].id);
                }
            }
        });
        */
        
        /*
        for (var i = 0; i < YAHOO.viame.simple_rich_text_editor.helper.sta.length; i++) {
            var editor = new YAHOO.widget.SimpleEditor(YAHOO.viame.simple_rich_text_editor.helper.sta[i].id, {
                width: '98%',
                height: '10em',
                dompath: false,
                handleSubmit: true,
                extracss: 'body { padding: 0; margin: 7px; }'
            });
            editor.on('toolbarLoaded', function() {
        		editor.toolbar.collapse();
        	});
    	    editor.render();
        }
        */
    }
	/* End Simple Rich Text Editor Helper */
	
	
	/* Begin Expandable TextArea */
	if (YAHOO.util.Selector.query('textarea.vmfh_expandable_textarea').length) {
        YAHOO.namespace("viame.expandable_textarea.helper");

        YAHOO.viame.expandable_textarea.helper.sta = YAHOO.util.Dom.getElementsByClassName('vmfh_expandable_textarea');
        
        /*
        YAHOO.viame.expandable_textarea.helper.browserAdjust = 0;
        if (YAHOO.env.ua.ie || YAHOO.env.ua.chrome) {
            YAHOO.viame.expandable_textarea.helper.browserAdjust = 0;
        }
        */
        
        YAHOO.viame.expandable_textarea.helper.resizeTextArea = function(e) {
            e = e.target || e;
            var vlen = e.value.length, ewidth = e.offsetWidth;
            if (vlen != e.valLength || ewidth != e.boxWidth) {
                if (!(YAHOO.env.ua.ie || YAHOO.env.ua.opera) && (YAHOO.env.ua.webkit || vlen < e.valLength || ewidth != e.boxWidth)) {
                    e.style.height = "0px";
                }
                var h = Math.max(
                        (YAHOO.util.Dom.getAttribute(e, 'minheight') ? YAHOO.util.Dom.getAttribute(e, 'minheight') : 10),
                        Math.min(
                            e.scrollHeight,
                            (YAHOO.util.Dom.getAttribute(e, 'maxheight') ? YAHOO.util.Dom.getAttribute(e, 'maxheight') : 99999)
                        )
                );
                
                e.style.overflow = (e.scrollHeight > h ? "auto" : "hidden");
                e.style.height = (h - (e.diffAdjust ? e.diffAdjust : 0)) + "px";
                
                if (!e.diffAdjust && e.diffAdjust != 0) {
                   //e.diffAdjust = Math.round((e.scrollHeight - $(e).height()) * 100) / 100 ;
                }
                e.valLength = vlen;
                e.boxWidth = ewidth;
            }
            
            return true;
        };
        
        for (var i = 0; i < YAHOO.viame.expandable_textarea.helper.sta.length; i++) {
            if (!YAHOO.util.Dom.getAttribute(YAHOO.viame.expandable_textarea.helper.sta[i], 'minheight')) {
                YAHOO.viame.expandable_textarea.helper.sta[i].setAttribute("minheight", YAHOO.viame.expandable_textarea.helper.sta[i].offsetHeight);
            }
            YAHOO.util.Event.addListener(YAHOO.viame.expandable_textarea.helper.sta[i], "keyup", YAHOO.viame.expandable_textarea.helper.resizeTextArea);
            YAHOO.util.Event.addListener(YAHOO.viame.expandable_textarea.helper.sta[i], "focus", YAHOO.viame.expandable_textarea.helper.resizeTextArea);
            
            YAHOO.viame.expandable_textarea.helper.resizeTextArea(YAHOO.viame.expandable_textarea.helper.sta[i]);
        }
    }
	/* End Expandable TextArea */
});
