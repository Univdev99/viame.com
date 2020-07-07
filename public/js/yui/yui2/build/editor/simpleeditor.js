/*
Copyright (c) 2011, Yahoo! Inc. All rights reserved.
Code licensed under the BSD License:
http://developer.yahoo.com/yui/license.html
version: 2.9.0
*/
(function() {
var Dom = YAHOO.util.Dom,
    Event = YAHOO.util.Event,
    Lang = YAHOO.lang;
    /**
     * @module editor    
     * @description <p>Creates a rich custom Toolbar Button. Primarily used with the Rich Text Editor's Toolbar</p>
     * @class ToolbarButtonAdvanced
     * @namespace YAHOO.widget
     * @requires yahoo, dom, element, event, container_core, menu, button
     * 
     * Provides a toolbar button based on the button and menu widgets.
     * @constructor
     * @class ToolbarButtonAdvanced
     * @param {String/HTMLElement} el The element to turn into a button.
     * @param {Object} attrs Object liternal containing configuration parameters.
    */
    if (YAHOO.widget.Button) {
        YAHOO.widget.ToolbarButtonAdvanced = YAHOO.widget.Button;
        /**
        * @property buttonType
        * @private
        * @description Tells if the Button is a Rich Button or a Simple Button
        */
        YAHOO.widget.ToolbarButtonAdvanced.prototype.buttonType = 'rich';
        /**
        * @method checkValue
        * @param {String} value The value of the option that we want to mark as selected
        * @description Select an option by value
        */
        YAHOO.widget.ToolbarButtonAdvanced.prototype.checkValue = function(value) {
            var _menuItems = this.getMenu().getItems();
            if (_menuItems.length === 0) {
                this.getMenu()._onBeforeShow();
                _menuItems = this.getMenu().getItems();
            }
            for (var i = 0; i < _menuItems.length; i++) {
                _menuItems[i].cfg.setProperty('checked', false);
                if (_menuItems[i].value == value) {
                    _menuItems[i].cfg.setProperty('checked', true);
                }
            }      
        };
    } else {
        YAHOO.widget.ToolbarButtonAdvanced = function() {};
    }


    /**
     * @description <p>Creates a basic custom Toolbar Button. Primarily used with the Rich Text Editor's Toolbar</p><p>Provides a toolbar button based on the button and menu widgets, &lt;select&gt; elements are used in place of menu's.</p>
     * @class ToolbarButton
     * @namespace YAHOO.widget
     * @requires yahoo, dom, element, event
     * @extends YAHOO.util.Element
     * 
     * 
     * @constructor
     * @param {String/HTMLElement} el The element to turn into a button.
     * @param {Object} attrs Object liternal containing configuration parameters.
    */

    YAHOO.widget.ToolbarButton = function(el, attrs) {
        
        if (Lang.isObject(arguments[0]) && !Dom.get(el).nodeType) {
            attrs = el;
        }
        var local_attrs = (attrs || {});

        var oConfig = {
            element: null,
            attributes: local_attrs
        };

        if (!oConfig.attributes.type) {
            oConfig.attributes.type = 'push';
        }
        
        oConfig.element = document.createElement('span');
        oConfig.element.setAttribute('unselectable', 'on');
        oConfig.element.className = 'yui-button yui-' + oConfig.attributes.type + '-button';
        oConfig.element.innerHTML = '<span class="first-child"><a href="#">LABEL</a></span>';
        oConfig.element.firstChild.firstChild.tabIndex = '-1';
        oConfig.attributes.id = (oConfig.attributes.id || Dom.generateId());
        oConfig.element.id = oConfig.attributes.id;

        YAHOO.widget.ToolbarButton.superclass.constructor.call(this, oConfig.element, oConfig.attributes);
    };

    YAHOO.extend(YAHOO.widget.ToolbarButton, YAHOO.util.Element, {
        /**
        * @property buttonType
        * @private
        * @description Tells if the Button is a Rich Button or a Simple Button
        */
        buttonType: 'normal',
        /**
        * @method _handleMouseOver
        * @private
        * @description Adds classes to the button elements on mouseover (hover)
        */
        _handleMouseOver: function() {
            if (!this.get('disabled')) {
                this.addClass('yui-button-hover');
                this.addClass('yui-' + this.get('type') + '-button-hover');
            }
        },
        /**
        * @method _handleMouseOut
        * @private
        * @description Removes classes from the button elements on mouseout (hover)
        */
        _handleMouseOut: function() {
            this.removeClass('yui-button-hover');
            this.removeClass('yui-' + this.get('type') + '-button-hover');
        },
        /**
        * @method checkValue
        * @param {String} value The value of the option that we want to mark as selected
        * @description Select an option by value
        */
        checkValue: function(value) {
            if (this.get('type') == 'menu') {
                var opts = this._button.options;
                if (opts) {
                    for (var i = 0; i < opts.length; i++) {
                        if (opts[i].value == value) {
                            opts.selectedIndex = i;
                        }
                    }
                }
            }
        },
        /** 
        * @method init
        * @description The ToolbarButton class's initialization method
        */        
        init: function(p_oElement, p_oAttributes) {
            YAHOO.widget.ToolbarButton.superclass.init.call(this, p_oElement, p_oAttributes);

            this.on('mouseover', this._handleMouseOver, this, true);
            this.on('mouseout', this._handleMouseOut, this, true);
            this.on('click', function(ev) {
                Event.stopEvent(ev);
                return false;
            }, this, true);
        },
        /**
        * @method initAttributes
        * @description Initializes all of the configuration attributes used to create 
        * the toolbar.
        * @param {Object} attr Object literal specifying a set of 
        * configuration attributes used to create the toolbar.
        */        
        initAttributes: function(attr) {
            YAHOO.widget.ToolbarButton.superclass.initAttributes.call(this, attr);
            /**
            * @attribute value
            * @description The value of the button
            * @type String
            */            
            this.setAttributeConfig('value', {
                value: attr.value
            });
            /**
            * @attribute menu
            * @description The menu attribute, see YAHOO.widget.Button
            * @type Object
            */            
            this.setAttributeConfig('menu', {
                value: attr.menu || false
            });
            /**
            * @attribute type
            * @description The type of button to create: push, menu, color, select, spin
            * @type String
            */            
            this.setAttributeConfig('type', {
                value: attr.type,
                writeOnce: true,
                method: function(type) {
                    var el, opt;
                    if (!this._button) {
                        this._button = this.get('element').getElementsByTagName('a')[0];
                    }
                    switch (type) {
                        case 'select':
                        case 'menu':
                            el = document.createElement('select');
                            el.id = this.get('id');
                            var menu = this.get('menu');
                            for (var i = 0; i < menu.length; i++) {
                                opt = document.createElement('option');
                                opt.innerHTML = menu[i].text;
                                opt.value = menu[i].value;
                                if (menu[i].checked) {
                                    opt.selected = true;
                                }
                                el.appendChild(opt);
                            }
                            this._button.parentNode.replaceChild(el, this._button);
                            Event.on(el, 'change', this._handleSelect, this, true);
                            this._button = el;
                            break;
                    }
                }
            });

            /**
            * @attribute disabled
            * @description Set the button into a disabled state
            * @type String
            */            
            this.setAttributeConfig('disabled', {
                value: attr.disabled || false,
                method: function(disabled) {
                    if (disabled) {
                        this.addClass('yui-button-disabled');
                        this.addClass('yui-' + this.get('type') + '-button-disabled');
                    } else {
                        this.removeClass('yui-button-disabled');
                        this.removeClass('yui-' + this.get('type') + '-button-disabled');
                    }
                    if ((this.get('type') == 'menu') || (this.get('type') == 'select')) {
                        this._button.disabled = disabled;
                    }
                }
            });

            /**
            * @attribute label
            * @description The text label for the button
            * @type String
            */            
            this.setAttributeConfig('label', {
                value: attr.label,
                method: function(label) {
                    if (!this._button) {
                        this._button = this.get('element').getElementsByTagName('a')[0];
                    }
                    if (this.get('type') == 'push') {
                        this._button.innerHTML = label;
                    }
                }
            });

            /**
            * @attribute title
            * @description The title of the button
            * @type String
            */            
            this.setAttributeConfig('title', {
                value: attr.title
            });

            /**
            * @config container
            * @description The container that the button is rendered to, handled by Toolbar
            * @type String
            */            
            this.setAttributeConfig('container', {
                value: null,
                writeOnce: true,
                method: function(cont) {
                    this.appendTo(cont);
                }
            });

        },
        /** 
        * @private
        * @method _handleSelect
        * @description The event fired when a change event gets fired on a select element
        * @param {Event} ev The change event.
        */        
        _handleSelect: function(ev) {
            var tar = Event.getTarget(ev);
            var value = tar.options[tar.selectedIndex].value;
            this.fireEvent('change', {type: 'change', value: value });
        },
        /** 
        * @method getMenu
        * @description A stub function to mimic YAHOO.widget.Button's getMenu method
        */        
        getMenu: function() {
            return this.get('menu');
        },
        /** 
        * @method destroy
        * @description Destroy the button
        */        
        destroy: function() {
            Event.purgeElement(this.get('element'), true);
            this.get('element').parentNode.removeChild(this.get('element'));
            //Brutal Object Destroy
            for (var i in this) {
                if (Lang.hasOwnProperty(this, i)) {
                    this[i] = null;
                }
            }       
        },
        /** 
        * @method fireEvent
        * @description Overridden fireEvent method to prevent DOM events from firing if the button is disabled.
        */        
        fireEvent: function(p_sType, p_aArgs) {
            //  Disabled buttons should not respond to DOM events
            if (this.DOM_EVENTS[p_sType] && this.get('disabled')) {
                Event.stopEvent(p_aArgs);
                return;
            }
        
            YAHOO.widget.ToolbarButton.superclass.fireEvent.call(this, p_sType, p_aArgs);
        },
        /**
        * @method toString
        * @description Returns a string representing the toolbar.
        * @return {String}
        */        
        toString: function() {
            return 'ToolbarButton (' + this.get('id') + ')';
        }
        
    });
})();
/**
 * @module editor
 * @description <p>Creates a rich Toolbar widget based on Button. Primarily used with the Rich Text Editor</p>
 * @namespace YAHOO.widget
 * @requires yahoo, dom, element, event, toolbarbutton
 * @optional container_core, dragdrop
 */
(function() {
var Dom = YAHOO.util.Dom,
    Event = YAHOO.util.Event,
    Lang = YAHOO.lang;
    
    var getButton = function(id) {
        var button = id;
        if (Lang.isString(id)) {
            button = this.getButtonById(id);
        }
        if (Lang.isNumber(id)) {
            button = this.getButtonByIndex(id);
        }
        if ((!(button instanceof YAHOO.widget.ToolbarButton)) && (!(button instanceof YAHOO.widget.ToolbarButtonAdvanced))) {
            button = this.getButtonByValue(id);
        }
        if ((button instanceof YAHOO.widget.ToolbarButton) || (button instanceof YAHOO.widget.ToolbarButtonAdvanced)) {
            return button;
        }
        return false;
    };

    /**
     * Provides a rich toolbar widget based on the button and menu widgets
     * @constructor
     * @class Toolbar
     * @extends YAHOO.util.Element
     * @param {String/HTMLElement} el The element to turn into a toolbar.
     * @param {Object} attrs Object liternal containing configuration parameters.
    */
    YAHOO.widget.Toolbar = function(el, attrs) {
        
        if (Lang.isObject(arguments[0]) && !Dom.get(el).nodeType) {
            attrs = el;
        }
        var local_attrs = {};
        if (attrs) {
            Lang.augmentObject(local_attrs, attrs); //Break the config reference
        }
        

        var oConfig = {
            element: null,
            attributes: local_attrs
        };
        
        
        if (Lang.isString(el) && Dom.get(el)) {
            oConfig.element = Dom.get(el);
        } else if (Lang.isObject(el) && Dom.get(el) && Dom.get(el).nodeType) {  
            oConfig.element = Dom.get(el);
        }
        

        if (!oConfig.element) {
            oConfig.element = document.createElement('DIV');
            oConfig.element.id = Dom.generateId();
            
            if (local_attrs.container && Dom.get(local_attrs.container)) {
                Dom.get(local_attrs.container).appendChild(oConfig.element);
            }
        }
        

        if (!oConfig.element.id) {
            oConfig.element.id = ((Lang.isString(el)) ? el : Dom.generateId());
        }
        
        var fs = document.createElement('fieldset');
        var lg = document.createElement('legend');
        lg.innerHTML = 'Toolbar';
        fs.appendChild(lg);
        
        var cont = document.createElement('DIV');
        oConfig.attributes.cont = cont;
        Dom.addClass(cont, 'yui-toolbar-subcont');
        fs.appendChild(cont);
        oConfig.element.appendChild(fs);

        oConfig.element.tabIndex = -1;

        
        oConfig.attributes.element = oConfig.element;
        oConfig.attributes.id = oConfig.element.id;

        this._configuredButtons = [];

        YAHOO.widget.Toolbar.superclass.constructor.call(this, oConfig.element, oConfig.attributes);
         
    };

    YAHOO.extend(YAHOO.widget.Toolbar, YAHOO.util.Element, {
        /**
        * @protected
        * @property _configuredButtons
        * @type Array
        */
        _configuredButtons: null,
        /**
        * @method _addMenuClasses
        * @private
        * @description This method is called from Menu's renderEvent to add a few more classes to the menu items
        * @param {String} ev The event that fired.
        * @param {Array} na Array of event information.
        * @param {Object} o Button config object. 
        */
        _addMenuClasses: function(ev, na, o) {
            Dom.addClass(this.element, 'yui-toolbar-' + o.get('value') + '-menu');
            if (Dom.hasClass(o._button.parentNode.parentNode, 'yui-toolbar-select')) {
                Dom.addClass(this.element, 'yui-toolbar-select-menu');
            }
            var items = this.getItems();
            for (var i = 0; i < items.length; i++) {
                Dom.addClass(items[i].element, 'yui-toolbar-' + o.get('value') + '-' + ((items[i].value) ? items[i].value.replace(/ /g, '-').toLowerCase() : items[i]._oText.nodeValue.replace(/ /g, '-').toLowerCase()));
                Dom.addClass(items[i].element, 'yui-toolbar-' + o.get('value') + '-' + ((items[i].value) ? items[i].value.replace(/ /g, '-') : items[i]._oText.nodeValue.replace(/ /g, '-')));
            }
        },
        /** 
        * @property buttonType
        * @description The default button to use
        * @type Object
        */
        buttonType: YAHOO.widget.ToolbarButton,
        /** 
        * @property dd
        * @description The DragDrop instance associated with the Toolbar
        * @type Object
        */
        dd: null,
        /** 
        * @property _colorData
        * @description Object reference containing colors hex and text values.
        * @type Object
        */
        _colorData: {
/* {{{ _colorData */
    '#111111': 'Obsidian',
    '#2D2D2D': 'Dark Gray',
    '#434343': 'Shale',
    '#5B5B5B': 'Flint',
    '#737373': 'Gray',
    '#8B8B8B': 'Concrete',
    '#A2A2A2': 'Gray',
    '#B9B9B9': 'Titanium',
    '#000000': 'Black',
    '#D0D0D0': 'Light Gray',
    '#E6E6E6': 'Silver',
    '#FFFFFF': 'White',
    '#BFBF00': 'Pumpkin',
    '#FFFF00': 'Yellow',
    '#FFFF40': 'Banana',
    '#FFFF80': 'Pale Yellow',
    '#FFFFBF': 'Butter',
    '#525330': 'Raw Siena',
    '#898A49': 'Mildew',
    '#AEA945': 'Olive',
    '#7F7F00': 'Paprika',
    '#C3BE71': 'Earth',
    '#E0DCAA': 'Khaki',
    '#FCFAE1': 'Cream',
    '#60BF00': 'Cactus',
    '#80FF00': 'Chartreuse',
    '#A0FF40': 'Green',
    '#C0FF80': 'Pale Lime',
    '#DFFFBF': 'Light Mint',
    '#3B5738': 'Green',
    '#668F5A': 'Lime Gray',
    '#7F9757': 'Yellow',
    '#407F00': 'Clover',
    '#8A9B55': 'Pistachio',
    '#B7C296': 'Light Jade',
    '#E6EBD5': 'Breakwater',
    '#00BF00': 'Spring Frost',
    '#00FF80': 'Pastel Green',
    '#40FFA0': 'Light Emerald',
    '#80FFC0': 'Sea Foam',
    '#BFFFDF': 'Sea Mist',
    '#033D21': 'Dark Forrest',
    '#438059': 'Moss',
    '#7FA37C': 'Medium Green',
    '#007F40': 'Pine',
    '#8DAE94': 'Yellow Gray Green',
    '#ACC6B5': 'Aqua Lung',
    '#DDEBE2': 'Sea Vapor',
    '#00BFBF': 'Fog',
    '#00FFFF': 'Cyan',
    '#40FFFF': 'Turquoise Blue',
    '#80FFFF': 'Light Aqua',
    '#BFFFFF': 'Pale Cyan',
    '#033D3D': 'Dark Teal',
    '#347D7E': 'Gray Turquoise',
    '#609A9F': 'Green Blue',
    '#007F7F': 'Seaweed',
    '#96BDC4': 'Green Gray',
    '#B5D1D7': 'Soapstone',
    '#E2F1F4': 'Light Turquoise',
    '#0060BF': 'Summer Sky',
    '#0080FF': 'Sky Blue',
    '#40A0FF': 'Electric Blue',
    '#80C0FF': 'Light Azure',
    '#BFDFFF': 'Ice Blue',
    '#1B2C48': 'Navy',
    '#385376': 'Biscay',
    '#57708F': 'Dusty Blue',
    '#00407F': 'Sea Blue',
    '#7792AC': 'Sky Blue Gray',
    '#A8BED1': 'Morning Sky',
    '#DEEBF6': 'Vapor',
    '#0000BF': 'Deep Blue',
    '#0000FF': 'Blue',
    '#4040FF': 'Cerulean Blue',
    '#8080FF': 'Evening Blue',
    '#BFBFFF': 'Light Blue',
    '#212143': 'Deep Indigo',
    '#373E68': 'Sea Blue',
    '#444F75': 'Night Blue',
    '#00007F': 'Indigo Blue',
    '#585E82': 'Dockside',
    '#8687A4': 'Blue Gray',
    '#D2D1E1': 'Light Blue Gray',
    '#6000BF': 'Neon Violet',
    '#8000FF': 'Blue Violet',
    '#A040FF': 'Violet Purple',
    '#C080FF': 'Violet Dusk',
    '#DFBFFF': 'Pale Lavender',
    '#302449': 'Cool Shale',
    '#54466F': 'Dark Indigo',
    '#655A7F': 'Dark Violet',
    '#40007F': 'Violet',
    '#726284': 'Smoky Violet',
    '#9E8FA9': 'Slate Gray',
    '#DCD1DF': 'Violet White',
    '#BF00BF': 'Royal Violet',
    '#FF00FF': 'Fuchsia',
    '#FF40FF': 'Magenta',
    '#FF80FF': 'Orchid',
    '#FFBFFF': 'Pale Magenta',
    '#4A234A': 'Dark Purple',
    '#794A72': 'Medium Purple',
    '#936386': 'Cool Granite',
    '#7F007F': 'Purple',
    '#9D7292': 'Purple Moon',
    '#C0A0B6': 'Pale Purple',
    '#ECDAE5': 'Pink Cloud',
    '#BF005F': 'Hot Pink',
    '#FF007F': 'Deep Pink',
    '#FF409F': 'Grape',
    '#FF80BF': 'Electric Pink',
    '#FFBFDF': 'Pink',
    '#451528': 'Purple Red',
    '#823857': 'Purple Dino',
    '#A94A76': 'Purple Gray',
    '#7F003F': 'Rose',
    '#BC6F95': 'Antique Mauve',
    '#D8A5BB': 'Cool Marble',
    '#F7DDE9': 'Pink Granite',
    '#C00000': 'Apple',
    '#FF0000': 'Fire Truck',
    '#FF4040': 'Pale Red',
    '#FF8080': 'Salmon',
    '#FFC0C0': 'Warm Pink',
    '#441415': 'Sepia',
    '#82393C': 'Rust',
    '#AA4D4E': 'Brick',
    '#800000': 'Brick Red',
    '#BC6E6E': 'Mauve',
    '#D8A3A4': 'Shrimp Pink',
    '#F8DDDD': 'Shell Pink',
    '#BF5F00': 'Dark Orange',
    '#FF7F00': 'Orange',
    '#FF9F40': 'Grapefruit',
    '#FFBF80': 'Canteloupe',
    '#FFDFBF': 'Wax',
    '#482C1B': 'Dark Brick',
    '#855A40': 'Dirt',
    '#B27C51': 'Tan',
    '#7F3F00': 'Nutmeg',
    '#C49B71': 'Mustard',
    '#E1C4A8': 'Pale Tan',
    '#FDEEE0': 'Marble'
/* }}} */
        },
        /** 
        * @property _colorPicker
        * @description The HTML Element containing the colorPicker
        * @type HTMLElement
        */
        _colorPicker: null,
        /** 
        * @property STR_COLLAPSE
        * @description String for Toolbar Collapse Button
        * @type String
        */
        STR_COLLAPSE: 'Collapse Toolbar',
        /** 
        * @property STR_EXPAND
        * @description String for Toolbar Collapse Button - Expand
        * @type String
        */
        STR_EXPAND: 'Expand Toolbar',
        /** 
        * @property STR_SPIN_LABEL
        * @description String for spinbutton dynamic label. Note the {VALUE} will be replaced with YAHOO.lang.substitute
        * @type String
        */
        STR_SPIN_LABEL: 'Spin Button with value {VALUE}. Use Control Shift Up Arrow and Control Shift Down arrow keys to increase or decrease the value.',
        /** 
        * @property STR_SPIN_UP
        * @description String for spinbutton up
        * @type String
        */
        STR_SPIN_UP: 'Click to increase the value of this input',
        /** 
        * @property STR_SPIN_DOWN
        * @description String for spinbutton down
        * @type String
        */
        STR_SPIN_DOWN: 'Click to decrease the value of this input',
        /** 
        * @property _titlebar
        * @description Object reference to the titlebar
        * @type HTMLElement
        */
        _titlebar: null,
        /** 
        * @property browser
        * @description Standard browser detection
        * @type Object
        */
        browser: YAHOO.env.ua,
        /**
        * @protected
        * @property _buttonList
        * @description Internal property list of current buttons in the toolbar
        * @type Array
        */
        _buttonList: null,
        /**
        * @protected
        * @property _buttonGroupList
        * @description Internal property list of current button groups in the toolbar
        * @type Array
        */
        _buttonGroupList: null,
        /**
        * @protected
        * @property _sep
        * @description Internal reference to the separator HTML Element for cloning
        * @type HTMLElement
        */
        _sep: null,
        /**
        * @protected
        * @property _sepCount
        * @description Internal refernce for counting separators, so we can give them a useful class name for styling
        * @type Number
        */
        _sepCount: null,
        /**
        * @protected
        * @property draghandle
        * @type HTMLElement
        */
        _dragHandle: null,
        /**
        * @protected
        * @property _toolbarConfigs
        * @type Object
        */
        _toolbarConfigs: {
            renderer: true
        },
        /**
        * @protected
        * @property CLASS_CONTAINER
        * @description Default CSS class to apply to the toolbar container element
        * @type String
        */
        CLASS_CONTAINER: 'yui-toolbar-container',
        /**
        * @protected
        * @property CLASS_DRAGHANDLE
        * @description Default CSS class to apply to the toolbar's drag handle element
        * @type String
        */
        CLASS_DRAGHANDLE: 'yui-toolbar-draghandle',
        /**
        * @protected
        * @property CLASS_SEPARATOR
        * @description Default CSS class to apply to all separators in the toolbar
        * @type String
        */
        CLASS_SEPARATOR: 'yui-toolbar-separator',
        /**
        * @protected
        * @property CLASS_DISABLED
        * @description Default CSS class to apply when the toolbar is disabled
        * @type String
        */
        CLASS_DISABLED: 'yui-toolbar-disabled',
        /**
        * @protected
        * @property CLASS_PREFIX
        * @description Default prefix for dynamically created class names
        * @type String
        */
        CLASS_PREFIX: 'yui-toolbar',
        /** 
        * @method init
        * @description The Toolbar class's initialization method
        */
        init: function(p_oElement, p_oAttributes) {
            YAHOO.widget.Toolbar.superclass.init.call(this, p_oElement, p_oAttributes);
        },
        /**
        * @method initAttributes
        * @description Initializes all of the configuration attributes used to create 
        * the toolbar.
        * @param {Object} attr Object literal specifying a set of 
        * configuration attributes used to create the toolbar.
        */
        initAttributes: function(attr) {
            YAHOO.widget.Toolbar.superclass.initAttributes.call(this, attr);
            this.addClass(this.CLASS_CONTAINER);

            /**
            * @attribute buttonType
            * @description The buttonType to use (advanced or basic)
            * @type String
            */
            this.setAttributeConfig('buttonType', {
                value: attr.buttonType || 'basic',
                writeOnce: true,
                validator: function(type) {
                    switch (type) {
                        case 'advanced':
                        case 'basic':
                            return true;
                    }
                    return false;
                },
                method: function(type) {
                    if (type == 'advanced') {
                        if (YAHOO.widget.Button) {
                            this.buttonType = YAHOO.widget.ToolbarButtonAdvanced;
                        } else {
                            this.buttonType = YAHOO.widget.ToolbarButton;
                        }
                    } else {
                        this.buttonType = YAHOO.widget.ToolbarButton;
                    }
                }
            });


            /**
            * @attribute buttons
            * @description Object specifying the buttons to include in the toolbar
            * Example:
            * <code><pre>
            * {
            *   { id: 'b3', type: 'button', label: 'Underline', value: 'underline' },
            *   { type: 'separator' },
            *   { id: 'b4', type: 'menu', label: 'Align', value: 'align',
            *       menu: [
            *           { text: "Left", value: 'alignleft' },
            *           { text: "Center", value: 'aligncenter' },
            *           { text: "Right", value: 'alignright' }
            *       ]
            *   }
            * }
            * </pre></code>
            * @type Array
            */
            
            this.setAttributeConfig('buttons', {
                value: [],
                writeOnce: true,
                method: function(data) {
                    var i, button, buttons, len, b;
                    for (i in data) {
                        if (Lang.hasOwnProperty(data, i)) {
                            if (data[i].type == 'separator') {
                                this.addSeparator();
                            } else if (data[i].group !== undefined) {
                                buttons = this.addButtonGroup(data[i]);
                                if (buttons) {
                                    len = buttons.length;
                                    for(b = 0; b < len; b++) {
                                        if (buttons[b]) {
                                            this._configuredButtons[this._configuredButtons.length] = buttons[b].id;
                                        }
                                    }
                                }
                                
                            } else {
                                button = this.addButton(data[i]);
                                if (button) {
                                    this._configuredButtons[this._configuredButtons.length] = button.id;
                                }
                            }
                        }
                    }
                }
            });

            /**
            * @attribute disabled
            * @description Boolean indicating if the toolbar should be disabled. It will also disable the draggable attribute if it is on.
            * @default false
            * @type Boolean
            */
            this.setAttributeConfig('disabled', {
                value: false,
                method: function(disabled) {
                    if (this.get('disabled') === disabled) {
                        return false;
                    }
                    if (disabled) {
                        this.addClass(this.CLASS_DISABLED);
                        this.set('draggable', false);
                        this.disableAllButtons();
                    } else {
                        this.removeClass(this.CLASS_DISABLED);
                        if (this._configs.draggable._initialConfig.value) {
                            //Draggable by default, set it back
                            this.set('draggable', true);
                        }
                        this.resetAllButtons();
                    }
                }
            });

            /**
            * @config cont
            * @description The container for the toolbar.
            * @type HTMLElement
            */
            this.setAttributeConfig('cont', {
                value: attr.cont,
                readOnly: true
            });


            /**
            * @attribute grouplabels
            * @description Boolean indicating if the toolbar should show the group label's text string.
            * @default true
            * @type Boolean
            */
            this.setAttributeConfig('grouplabels', {
                value: ((attr.grouplabels === false) ? false : true),
                method: function(grouplabels) {
                    if (grouplabels) {
                        Dom.removeClass(this.get('cont'), (this.CLASS_PREFIX + '-nogrouplabels'));
                    } else {
                        Dom.addClass(this.get('cont'), (this.CLASS_PREFIX + '-nogrouplabels'));
                    }
                }
            });
            /**
            * @attribute titlebar
            * @description Boolean indicating if the toolbar should have a titlebar. If
            * passed a string, it will use that as the titlebar text
            * @default false
            * @type Boolean or String
            */
            this.setAttributeConfig('titlebar', {
                value: false,
                method: function(titlebar) {
                    if (titlebar) {
                        if (this._titlebar && this._titlebar.parentNode) {
                            this._titlebar.parentNode.removeChild(this._titlebar);
                        }
                        this._titlebar = document.createElement('DIV');
                        this._titlebar.tabIndex = '-1';
                        Event.on(this._titlebar, 'focus', function() {
                            this._handleFocus();
                        }, this, true);
                        Dom.addClass(this._titlebar, this.CLASS_PREFIX + '-titlebar');
                        if (Lang.isString(titlebar)) {
                            var h2 = document.createElement('h2');
                            h2.tabIndex = '-1';
                            h2.innerHTML = '<a href="#" tabIndex="0">' + titlebar + '</a>';
                            this._titlebar.appendChild(h2);
                            Event.on(h2.firstChild, 'click', function(ev) {
                                Event.stopEvent(ev);
                            });
                            Event.on([h2, h2.firstChild], 'focus', function() {
                                this._handleFocus();
                            }, this, true);
                        }
                        if (this.get('firstChild')) {
                            this.insertBefore(this._titlebar, this.get('firstChild'));
                        } else {
                            this.appendChild(this._titlebar);
                        }
                        if (this.get('collapse')) {
                            this.set('collapse', true);
                        }
                    } else if (this._titlebar) {
                        if (this._titlebar && this._titlebar.parentNode) {
                            this._titlebar.parentNode.removeChild(this._titlebar);
                        }
                    }
                }
            });


            /**
            * @attribute collapse
            * @description Boolean indicating if the the titlebar should have a collapse button.
            * The collapse button will not remove the toolbar, it will minimize it to the titlebar
            * @default false
            * @type Boolean
            */
            this.setAttributeConfig('collapse', {
                value: false,
                method: function(collapse) {
                    if (this._titlebar) {
                        var collapseEl = null;
                        var el = Dom.getElementsByClassName('collapse', 'span', this._titlebar);
                        if (collapse) {
                            if (el.length > 0) {
                                //There is already a collapse button
                                return true;
                            }
                            collapseEl = document.createElement('SPAN');
                            collapseEl.innerHTML = 'X';
                            collapseEl.title = this.STR_COLLAPSE;

                            Dom.addClass(collapseEl, 'collapse');
                            this._titlebar.appendChild(collapseEl);
                            Event.addListener(collapseEl, 'click', function() {
                                if (Dom.hasClass(this.get('cont').parentNode, 'yui-toolbar-container-collapsed')) {
                                    this.collapse(false); //Expand Toolbar
                                } else {
                                    this.collapse(); //Collapse Toolbar
                                }
                            }, this, true);
                        } else {
                            collapseEl = Dom.getElementsByClassName('collapse', 'span', this._titlebar);
                            if (collapseEl[0]) {
                                if (Dom.hasClass(this.get('cont').parentNode, 'yui-toolbar-container-collapsed')) {
                                    //We are closed, reopen the titlebar..
                                    this.collapse(false); //Expand Toolbar
                                }
                                collapseEl[0].parentNode.removeChild(collapseEl[0]);
                            }
                        }
                    }
                }
            });

            /**
            * @attribute draggable
            * @description Boolean indicating if the toolbar should be draggable.  
            * @default false
            * @type Boolean
            */

            this.setAttributeConfig('draggable', {
                value: (attr.draggable || false),
                method: function(draggable) {
                    if (draggable && !this.get('titlebar')) {
                        if (!this._dragHandle) {
                            this._dragHandle = document.createElement('SPAN');
                            this._dragHandle.innerHTML = '|';
                            this._dragHandle.setAttribute('title', 'Click to drag the toolbar');
                            this._dragHandle.id = this.get('id') + '_draghandle';
                            Dom.addClass(this._dragHandle, this.CLASS_DRAGHANDLE);
                            if (this.get('cont').hasChildNodes()) {
                                this.get('cont').insertBefore(this._dragHandle, this.get('cont').firstChild);
                            } else {
                                this.get('cont').appendChild(this._dragHandle);
                            }
                            this.dd = new YAHOO.util.DD(this.get('id'));
                            this.dd.setHandleElId(this._dragHandle.id);
                            
                        }
                    } else {
                        if (this._dragHandle) {
                            this._dragHandle.parentNode.removeChild(this._dragHandle);
                            this._dragHandle = null;
                            this.dd = null;
                        }
                    }
                    if (this._titlebar) {
                        if (draggable) {
                            this.dd = new YAHOO.util.DD(this.get('id'));
                            this.dd.setHandleElId(this._titlebar);
                            Dom.addClass(this._titlebar, 'draggable');
                        } else {
                            Dom.removeClass(this._titlebar, 'draggable');
                            if (this.dd) {
                                this.dd.unreg();
                                this.dd = null;
                            }
                        }
                    }
                },
                validator: function(value) {
                    var ret = true;
                    if (!YAHOO.util.DD) {
                        ret = false;
                    }
                    return ret;
                }
            });

        },
        /**
        * @method addButtonGroup
        * @description Add a new button group to the toolbar. (uses addButton)
        * @param {Object} oGroup Object literal reference to the Groups Config (contains an array of button configs as well as the group label)
        */
        addButtonGroup: function(oGroup) {
            if (!this.get('element')) {
                this._queue[this._queue.length] = ['addButtonGroup', arguments];
                return false;
            }
            
            if (!this.hasClass(this.CLASS_PREFIX + '-grouped')) {
                this.addClass(this.CLASS_PREFIX + '-grouped');
            }
            var div = document.createElement('DIV');
            Dom.addClass(div, this.CLASS_PREFIX + '-group');
            Dom.addClass(div, this.CLASS_PREFIX + '-group-' + oGroup.group);
            if (oGroup.label) {
                var label = document.createElement('h3');
                label.innerHTML = oGroup.label;
                div.appendChild(label);
            }
            if (!this.get('grouplabels')) {
                Dom.addClass(this.get('cont'), this.CLASS_PREFIX, '-nogrouplabels');
            }

            this.get('cont').appendChild(div);

            //For accessibility, let's put all of the group buttons in an Unordered List
            var ul = document.createElement('ul');
            div.appendChild(ul);

            if (!this._buttonGroupList) {
                this._buttonGroupList = {};
            }
            
            this._buttonGroupList[oGroup.group] = ul;

            //An array of the button ids added to this group
            //This is used for destruction later...
            var addedButtons = [],
                button;
            

            for (var i = 0; i < oGroup.buttons.length; i++) {
                var li = document.createElement('li');
                li.className = this.CLASS_PREFIX + '-groupitem';
                ul.appendChild(li);
                if ((oGroup.buttons[i].type !== undefined) && oGroup.buttons[i].type == 'separator') {
                    this.addSeparator(li);
                } else {
                    oGroup.buttons[i].container = li;
                    button = this.addButton(oGroup.buttons[i]);
                    if (button) {
                        addedButtons[addedButtons.length]  = button.id;
                    }
                }
            }
            return addedButtons;
        },
        /**
        * @method addButtonToGroup
        * @description Add a new button to a toolbar group. Buttons supported:
        *   push, split, menu, select, color, spin
        * @param {Object} oButton Object literal reference to the Button's Config
        * @param {String} group The Group identifier passed into the initial config
        * @param {HTMLElement} after Optional HTML element to insert this button after in the DOM.
        */
        addButtonToGroup: function(oButton, group, after) {
            var groupCont = this._buttonGroupList[group],
                li = document.createElement('li');

            li.className = this.CLASS_PREFIX + '-groupitem';
            oButton.container = li;
            this.addButton(oButton, after);
            groupCont.appendChild(li);
        },
        /**
        * @method addButton
        * @description Add a new button to the toolbar. Buttons supported:
        *   push, split, menu, select, color, spin
        * @param {Object} oButton Object literal reference to the Button's Config
        * @param {HTMLElement} after Optional HTML element to insert this button after in the DOM.
        */
        addButton: function(oButton, after) {
            if (!this.get('element')) {
                this._queue[this._queue.length] = ['addButton', arguments];
                return false;
            }
            if (!this._buttonList) {
                this._buttonList = [];
            }
            if (!oButton.container) {
                oButton.container = this.get('cont');
            }

            if ((oButton.type == 'menu') || (oButton.type == 'split') || (oButton.type == 'select')) {
                if (Lang.isArray(oButton.menu)) {
                    for (var i in oButton.menu) {
                        if (Lang.hasOwnProperty(oButton.menu, i)) {
                            var funcObject = {
                                fn: function(ev, x, oMenu) {
                                    if (!oButton.menucmd) {
                                        oButton.menucmd = oButton.value;
                                    }
                                    oButton.value = ((oMenu.value) ? oMenu.value : oMenu._oText.nodeValue);
                                },
                                scope: this
                            };
                            oButton.menu[i].onclick = funcObject;
                        }
                    }
                }
            }
            var _oButton = {}, skip = false;
            for (var o in oButton) {
                if (Lang.hasOwnProperty(oButton, o)) {
                    if (!this._toolbarConfigs[o]) {
                        _oButton[o] = oButton[o];
                    }
                }
            }
            if (oButton.type == 'select') {
                _oButton.type = 'menu';
            }
            if (oButton.type == 'spin') {
                _oButton.type = 'push';
            }
            if (_oButton.type == 'color') {
                if (YAHOO.widget.Overlay) {
                    _oButton = this._makeColorButton(_oButton);
                } else {
                    skip = true;
                }
            }
            if (_oButton.menu) {
                if ((YAHOO.widget.Overlay) && (oButton.menu instanceof YAHOO.widget.Overlay)) {
                    oButton.menu.showEvent.subscribe(function() {
                        this._button = _oButton;
                    });
                } else {
                    for (var m = 0; m < _oButton.menu.length; m++) {
                        if (!_oButton.menu[m].value) {
                            _oButton.menu[m].value = _oButton.menu[m].text;
                        }
                    }
                    if (this.browser.webkit) {
                        _oButton.focusmenu = false;
                    }
                }
            }
            if (skip) {
                oButton = false;
            } else {
                //Add to .get('buttons') manually
                this._configs.buttons.value[this._configs.buttons.value.length] = oButton;
                
                var tmp = new this.buttonType(_oButton);
                tmp.get('element').tabIndex = '-1';
                tmp.get('element').setAttribute('role', 'button');
                tmp._selected = true;
                
                if (this.get('disabled')) {
                    //Toolbar is disabled, disable the new button too!
                    tmp.set('disabled', true);
                }
                if (!oButton.id) {
                    oButton.id = tmp.get('id');
                }
                
                if (after) {
                    var el = tmp.get('element');
                    var nextSib = null;
                    if (after.get) {
                        nextSib = after.get('element').nextSibling;
                    } else if (after.nextSibling) {
                        nextSib = after.nextSibling;
                    }
                    if (nextSib) {
                        nextSib.parentNode.insertBefore(el, nextSib);
                    }
                }
                tmp.addClass(this.CLASS_PREFIX + '-' + tmp.get('value'));

                var icon = document.createElement('span');
                icon.className = this.CLASS_PREFIX + '-icon';
                tmp.get('element').insertBefore(icon, tmp.get('firstChild'));
                if (tmp._button.tagName.toLowerCase() == 'button') {
                    tmp.get('element').setAttribute('unselectable', 'on');
                    //Replace the Button HTML Element with an a href if it exists
                    var a = document.createElement('a');
                    a.innerHTML = tmp._button.innerHTML;
                    a.href = '#';
                    a.tabIndex = '-1';
                    Event.on(a, 'click', function(ev) {
                        Event.stopEvent(ev);
                    });
                    tmp._button.parentNode.replaceChild(a, tmp._button);
                    tmp._button = a;
                }

                if (oButton.type == 'select') {
                    if (tmp._button.tagName.toLowerCase() == 'select') {
                        icon.parentNode.removeChild(icon);
                        var iel = tmp._button,
                            parEl = tmp.get('element');
                        parEl.parentNode.replaceChild(iel, parEl);
                        //The 'element' value is currently the orphaned element
                        //In order for "destroy" to execute we need to get('element') to reference the correct node.
                        //I'm not sure if there is a direct approach to setting this value.
                        tmp._configs.element.value = iel;
                    } else {
                        //Don't put a class on it if it's a real select element
                        tmp.addClass(this.CLASS_PREFIX + '-select');
                    }
                }
                if (oButton.type == 'spin') {
                    if (!Lang.isArray(oButton.range)) {
                        oButton.range = [ 10, 100 ];
                    }
                    this._makeSpinButton(tmp, oButton);
                }
                tmp.get('element').setAttribute('title', tmp.get('label'));
                if (oButton.type != 'spin') {
                    if ((YAHOO.widget.Overlay) && (_oButton.menu instanceof YAHOO.widget.Overlay)) {
                        var showPicker = function(ev) {
                            var exec = true;
                            if (ev.keyCode && (ev.keyCode == 9)) {
                                exec = false;
                            }
                            if (exec) {
                                if (this._colorPicker) {
                                    this._colorPicker._button = oButton.value;
                                }
                                var menuEL = tmp.getMenu().element;
                                if (Dom.getStyle(menuEL, 'visibility') == 'hidden') {
                                    tmp.getMenu().show();
                                } else {
                                    tmp.getMenu().hide();
                                }
                            }
                            YAHOO.util.Event.stopEvent(ev);
                        };
                        tmp.on('mousedown', showPicker, oButton, this);
                        tmp.on('keydown', showPicker, oButton, this);
                        
                    } else if ((oButton.type != 'menu') && (oButton.type != 'select')) {
                        tmp.on('keypress', this._buttonClick, oButton, this);
                        tmp.on('mousedown', function(ev) {
                            YAHOO.util.Event.stopEvent(ev);
                            this._buttonClick(ev, oButton);
                        }, oButton, this);
                        tmp.on('click', function(ev) {
                            YAHOO.util.Event.stopEvent(ev);
                        });
                    } else {
                        //Stop the mousedown event so we can trap the selection in the editor!
                        tmp.on('mousedown', function(ev) {
                            //YAHOO.util.Event.stopEvent(ev);
                        });
                        tmp.on('click', function(ev) {
                            //YAHOO.util.Event.stopEvent(ev);
                        });
                        tmp.on('change', function(ev) {
                            if (!ev.target) {
                                if (!oButton.menucmd) {
                                    oButton.menucmd = oButton.value;
                                }
                                oButton.value = ev.value;
                                this._buttonClick(ev, oButton);
                            }
                        }, this, true);

                        var self = this;
                        //Hijack the mousedown event in the menu and make it fire a button click..
                        tmp.on('appendTo', function() {
                            var tmp = this;
                            if (tmp.getMenu() && tmp.getMenu().mouseDownEvent) {
                                tmp.getMenu().mouseDownEvent.subscribe(function(ev, args) {
                                    var oMenu = args[1];
                                    YAHOO.util.Event.stopEvent(args[0]);
                                    tmp._onMenuClick(args[0], tmp);
                                    if (!oButton.menucmd) {
                                        oButton.menucmd = oButton.value;
                                    }
                                    oButton.value = ((oMenu.value) ? oMenu.value : oMenu._oText.nodeValue);
                                    self._buttonClick.call(self, args[1], oButton);
                                    tmp._hideMenu();
                                    return false;
                                });
                                tmp.getMenu().clickEvent.subscribe(function(ev, args) {
                                    YAHOO.util.Event.stopEvent(args[0]);
                                });
                                tmp.getMenu().mouseUpEvent.subscribe(function(ev, args) {
                                    YAHOO.util.Event.stopEvent(args[0]);
                                });
                            }
                        });
                        
                    }
                } else {
                    //Stop the mousedown event so we can trap the selection in the editor!
                    tmp.on('mousedown', function(ev) {
                        YAHOO.util.Event.stopEvent(ev);
                    });
                    tmp.on('click', function(ev) {
                        YAHOO.util.Event.stopEvent(ev);
                    });
                }
                if (this.browser.ie) {
                    /*
                    //Add a couple of new events for IE
                    tmp.DOM_EVENTS.focusin = true;
                    tmp.DOM_EVENTS.focusout = true;
                    
                    //Stop them so we don't loose focus in the Editor
                    tmp.on('focusin', function(ev) {
                        YAHOO.util.Event.stopEvent(ev);
                    }, oButton, this);
                    
                    tmp.on('focusout', function(ev) {
                        YAHOO.util.Event.stopEvent(ev);
                    }, oButton, this);
                    tmp.on('click', function(ev) {
                        YAHOO.util.Event.stopEvent(ev);
                    }, oButton, this);
                    */
                }
                if (this.browser.webkit) {
                    //This will keep the document from gaining focus and the editor from loosing it..
                    //Forcefully remove the focus calls in button!
                    tmp.hasFocus = function() {
                        return true;
                    };
                }
                this._buttonList[this._buttonList.length] = tmp;
                if ((oButton.type == 'menu') || (oButton.type == 'split') || (oButton.type == 'select')) {
                    if (Lang.isArray(oButton.menu)) {
                        var menu = tmp.getMenu();
                        if (menu && menu.renderEvent) {
                            menu.renderEvent.subscribe(this._addMenuClasses, tmp);
                            if (oButton.renderer) {
                                menu.renderEvent.subscribe(oButton.renderer, tmp);
                            }
                        }
                    }
                }
            }
            return oButton;
        },
        /**
        * @method addSeparator
        * @description Add a new button separator to the toolbar.
        * @param {HTMLElement} cont Optional HTML element to insert this button into.
        * @param {HTMLElement} after Optional HTML element to insert this button after in the DOM.
        */
        addSeparator: function(cont, after) {
            if (!this.get('element')) {
                this._queue[this._queue.length] = ['addSeparator', arguments];
                return false;
            }
            var sepCont = ((cont) ? cont : this.get('cont'));
            if (!this.get('element')) {
                this._queue[this._queue.length] = ['addSeparator', arguments];
                return false;
            }
            if (this._sepCount === null) {
                this._sepCount = 0;
            }
            if (!this._sep) {
                this._sep = document.createElement('SPAN');
                Dom.addClass(this._sep, this.CLASS_SEPARATOR);
                this._sep.innerHTML = '|';
            }
            var _sep = this._sep.cloneNode(true);
            this._sepCount++;
            Dom.addClass(_sep, this.CLASS_SEPARATOR + '-' + this._sepCount);
            if (after) {
                var nextSib = null;
                if (after.get) {
                    nextSib = after.get('element').nextSibling;
                } else if (after.nextSibling) {
                    nextSib = after.nextSibling;
                } else {
                    nextSib = after;
                }
                if (nextSib) {
                    if (nextSib == after) {
                        nextSib.parentNode.appendChild(_sep);
                    } else {
                        nextSib.parentNode.insertBefore(_sep, nextSib);
                    }
                }
            } else {
                sepCont.appendChild(_sep);
            }
            return _sep;
        },
        /**
        * @method _createColorPicker
        * @private
        * @description Creates the core DOM reference to the color picker menu item.
        * @param {String} id the id of the toolbar to prefix this DOM container with.
        */
        _createColorPicker: function(id) {
            if (Dom.get(id + '_colors')) {
               Dom.get(id + '_colors').parentNode.removeChild(Dom.get(id + '_colors'));
            }
            var picker = document.createElement('div');
            picker.className = 'yui-toolbar-colors';
            picker.id = id + '_colors';
            picker.style.display = 'none';
            Event.on(window, 'load', function() {
                document.body.appendChild(picker);
            }, this, true);

            this._colorPicker = picker;

            var html = '';
            for (var i in this._colorData) {
                if (Lang.hasOwnProperty(this._colorData, i)) {
                    html += '<a style="background-color: ' + i + '" href="#">' + i.replace('#', '') + '</a>';
                }
            }
            html += '<span><em>X</em><strong></strong></span>';
            window.setTimeout(function() {
                picker.innerHTML = html;
            }, 0);

            Event.on(picker, 'mouseover', function(ev) {
                var picker = this._colorPicker;
                var em = picker.getElementsByTagName('em')[0];
                var strong = picker.getElementsByTagName('strong')[0];
                var tar = Event.getTarget(ev);
                if (tar.tagName.toLowerCase() == 'a') {
                    em.style.backgroundColor = tar.style.backgroundColor;
                    strong.innerHTML = this._colorData['#' + tar.innerHTML] + '<br>' + tar.innerHTML;
                }
            }, this, true);
            Event.on(picker, 'focus', function(ev) {
                Event.stopEvent(ev);
            });
            Event.on(picker, 'click', function(ev) {
                Event.stopEvent(ev);
            });
            Event.on(picker, 'mousedown', function(ev) {
                Event.stopEvent(ev);
                var tar = Event.getTarget(ev);
                if (tar.tagName.toLowerCase() == 'a') {
                    var retVal = this.fireEvent('colorPickerClicked', { type: 'colorPickerClicked', target: this, button: this._colorPicker._button, color: tar.innerHTML, colorName: this._colorData['#' + tar.innerHTML] } );
                    if (retVal !== false) {
                        var info = {
                            color: tar.innerHTML,
                            colorName: this._colorData['#' + tar.innerHTML],
                            value: this._colorPicker._button 
                        };
                    
                        this.fireEvent('buttonClick', { type: 'buttonClick', target: this.get('element'), button: info });
                    }
                    this.getButtonByValue(this._colorPicker._button).getMenu().hide();
                }
            }, this, true);
        },
        /**
        * @method _resetColorPicker
        * @private
        * @description Clears the currently selected color or mouseover color in the color picker.
        */
        _resetColorPicker: function() {
            var em = this._colorPicker.getElementsByTagName('em')[0];
            var strong = this._colorPicker.getElementsByTagName('strong')[0];
            em.style.backgroundColor = 'transparent';
            strong.innerHTML = '';
        },
        /**
        * @method _makeColorButton
        * @private
        * @description Called to turn a "color" button into a menu button with an Overlay for the menu.
        * @param {Object} _oButton <a href="YAHOO.widget.ToolbarButton.html">YAHOO.widget.ToolbarButton</a> reference
        */
        _makeColorButton: function(_oButton) {
            if (!this._colorPicker) {   
                this._createColorPicker(this.get('id'));
            }
            _oButton.type = 'color';
            _oButton.menu = new YAHOO.widget.Overlay(this.get('id') + '_' + _oButton.value + '_menu', { visible: false, position: 'absolute', iframe: true });
            _oButton.menu.setBody('');
            _oButton.menu.render(this.get('cont'));
            Dom.addClass(_oButton.menu.element, 'yui-button-menu');
            Dom.addClass(_oButton.menu.element, 'yui-color-button-menu');
            _oButton.menu.beforeShowEvent.subscribe(function() {
                _oButton.menu.cfg.setProperty('zindex', 5); //Re Adjust the overlays zIndex.. not sure why.
                _oButton.menu.cfg.setProperty('context', [this.getButtonById(_oButton.id).get('element'), 'tl', 'bl']); //Re Adjust the overlay.. not sure why.
                //Move the DOM reference of the color picker to the Overlay that we are about to show.
                this._resetColorPicker();
                var _p = this._colorPicker;
                if (_p.parentNode) {
                    _p.parentNode.removeChild(_p);
                }
                _oButton.menu.setBody('');
                _oButton.menu.appendToBody(_p);
                this._colorPicker.style.display = 'block';
            }, this, true);
            return _oButton;
        },
        /**
        * @private
        * @method _makeSpinButton
        * @description Create a button similar to an OS Spin button.. It has an up/down arrow combo to scroll through a range of int values.
        * @param {Object} _button <a href="YAHOO.widget.ToolbarButton.html">YAHOO.widget.ToolbarButton</a> reference
        * @param {Object} oButton Object literal containing the buttons initial config
        */
        _makeSpinButton: function(_button, oButton) {
            _button.addClass(this.CLASS_PREFIX + '-spinbutton');
            var self = this,
                _par = _button._button.parentNode.parentNode, //parentNode of Button Element for appending child
                range = oButton.range,
                _b1 = document.createElement('a'),
                _b2 = document.createElement('a');
                _b1.href = '#';
                _b2.href = '#';
                _b1.tabIndex = '-1';
                _b2.tabIndex = '-1';
            
            //Setup the up and down arrows
            _b1.className = 'up';
            _b1.title = this.STR_SPIN_UP;
            _b1.innerHTML = this.STR_SPIN_UP;
            _b2.className = 'down';
            _b2.title = this.STR_SPIN_DOWN;
            _b2.innerHTML = this.STR_SPIN_DOWN;

            //Append them to the container
            _par.appendChild(_b1);
            _par.appendChild(_b2);
            
            var label = YAHOO.lang.substitute(this.STR_SPIN_LABEL, { VALUE: _button.get('label') });
            _button.set('title', label);

            var cleanVal = function(value) {
                value = ((value < range[0]) ? range[0] : value);
                value = ((value > range[1]) ? range[1] : value);
                return value;
            };

            var br = this.browser;
            var tbar = false;
            var strLabel = this.STR_SPIN_LABEL;
            if (this._titlebar && this._titlebar.firstChild) {
                tbar = this._titlebar.firstChild;
            }
            
            var _intUp = function(ev) {
                YAHOO.util.Event.stopEvent(ev);
                if (!_button.get('disabled') && (ev.keyCode != 9)) {
                    var value = parseInt(_button.get('label'), 10);
                    value++;
                    value = cleanVal(value);
                    _button.set('label', ''+value);
                    var label = YAHOO.lang.substitute(strLabel, { VALUE: _button.get('label') });
                    _button.set('title', label);
                    if (!br.webkit && tbar) {
                        //tbar.focus(); //We do this for accessibility, on the re-focus of the element, a screen reader will re-read the title that was just changed
                        //_button.focus();
                    }
                    self._buttonClick(ev, oButton);
                }
            };

            var _intDown = function(ev) {
                YAHOO.util.Event.stopEvent(ev);
                if (!_button.get('disabled') && (ev.keyCode != 9)) {
                    var value = parseInt(_button.get('label'), 10);
                    value--;
                    value = cleanVal(value);

                    _button.set('label', ''+value);
                    var label = YAHOO.lang.substitute(strLabel, { VALUE: _button.get('label') });
                    _button.set('title', label);
                    if (!br.webkit && tbar) {
                        //tbar.focus(); //We do this for accessibility, on the re-focus of the element, a screen reader will re-read the title that was just changed
                        //_button.focus();
                    }
                    self._buttonClick(ev, oButton);
                }
            };

            var _intKeyUp = function(ev) {
                if (ev.keyCode == 38) {
                    _intUp(ev);
                } else if (ev.keyCode == 40) {
                    _intDown(ev);
                } else if (ev.keyCode == 107 && ev.shiftKey) {  //Plus Key
                    _intUp(ev);
                } else if (ev.keyCode == 109 && ev.shiftKey) {  //Minus Key
                    _intDown(ev);
                }
            };

            //Handle arrow keys..
            _button.on('keydown', _intKeyUp, this, true);

            //Listen for the click on the up button and act on it
            //Listen for the click on the down button and act on it
            Event.on(_b1, 'mousedown',function(ev) {
                Event.stopEvent(ev);
            }, this, true);
            Event.on(_b2, 'mousedown', function(ev) {
                Event.stopEvent(ev);
            }, this, true);
            Event.on(_b1, 'click', _intUp, this, true);
            Event.on(_b2, 'click', _intDown, this, true);
        },
        /**
        * @protected
        * @method _buttonClick
        * @description Click handler for all buttons in the toolbar.
        * @param {String} ev The event that was passed in.
        * @param {Object} info Object literal of information about the button that was clicked.
        */
        _buttonClick: function(ev, info) {
            var doEvent = true;
            
            if (ev && ev.type == 'keypress') {
                if (ev.keyCode == 9) {
                    doEvent = false;
                } else if ((ev.keyCode === 13) || (ev.keyCode === 0) || (ev.keyCode === 32)) {
                } else {
                    doEvent = false;
                }
            }

            if (doEvent) {
                var fireNextEvent = true,
                    retValue = false;
                    
                info.isSelected = this.isSelected(info.id);

                if (info.value) {
                    retValue = this.fireEvent(info.value + 'Click', { type: info.value + 'Click', target: this.get('element'), button: info });
                    if (retValue === false) {
                        fireNextEvent = false;
                    }
                }
                
                if (info.menucmd && fireNextEvent) {
                    retValue = this.fireEvent(info.menucmd + 'Click', { type: info.menucmd + 'Click', target: this.get('element'), button: info });
                    if (retValue === false) {
                        fireNextEvent = false;
                    }
                }
                if (fireNextEvent) {
                    this.fireEvent('buttonClick', { type: 'buttonClick', target: this.get('element'), button: info });
                }

                if (info.type == 'select') {
                    var button = this.getButtonById(info.id);
                    if (button.buttonType == 'rich') {
                        var txt = info.value;
                        for (var i = 0; i < info.menu.length; i++) {
                            if (info.menu[i].value == info.value) {
                                txt = info.menu[i].text;
                                break;
                            }
                        }
                        button.set('label', '<span class="yui-toolbar-' + info.menucmd + '-' + (info.value).replace(/ /g, '-').toLowerCase() + '">' + txt + '</span>');
                        var _items = button.getMenu().getItems();
                        for (var m = 0; m < _items.length; m++) {
                            if (_items[m].value.toLowerCase() == info.value.toLowerCase()) {
                                _items[m].cfg.setProperty('checked', true);
                            } else {
                                _items[m].cfg.setProperty('checked', false);
                            }
                        }
                    }
                }
                if (ev) {
                    Event.stopEvent(ev);
                }
            }
        },
        /**
        * @private
        * @property _keyNav
        * @description Flag to determine if the arrow nav listeners have been attached
        * @type Boolean
        */
        _keyNav: null,
        /**
        * @private
        * @property _navCounter
        * @description Internal counter for walking the buttons in the toolbar with the arrow keys
        * @type Number
        */
        _navCounter: null,
        /**
        * @private
        * @method _navigateButtons
        * @description Handles the navigation/focus of toolbar buttons with the Arrow Keys
        * @param {Event} ev The Key Event
        */
        _navigateButtons: function(ev) {
            switch (ev.keyCode) {
                case 37:
                case 39:
                    if (ev.keyCode == 37) {
                        this._navCounter--;
                    } else {
                        this._navCounter++;
                    }
                    if (this._navCounter > (this._buttonList.length - 1)) {
                        this._navCounter = 0;
                    }
                    if (this._navCounter < 0) {
                        this._navCounter = (this._buttonList.length - 1);
                    }
                    if (this._buttonList[this._navCounter]) {
                        var el = this._buttonList[this._navCounter].get('element');
                        if (this.browser.ie) {
                            el = this._buttonList[this._navCounter].get('element').getElementsByTagName('a')[0];
                        }
                        if (this._buttonList[this._navCounter].get('disabled')) {
                            this._navigateButtons(ev);
                        } else {
                            el.focus();
                        }
                    }
                    break;
            }
        },
        /**
        * @private
        * @method _handleFocus
        * @description Sets up the listeners for the arrow key navigation
        */
        _handleFocus: function() {
            if (!this._keyNav) {
                var ev = 'keypress';
                if (this.browser.ie) {
                    ev = 'keydown';
                }
                Event.on(this.get('element'), ev, this._navigateButtons, this, true);
                this._keyNav = true;
                this._navCounter = -1;
            }
        },
        /**
        * @method getButtonById
        * @description Gets a button instance from the toolbar by is Dom id.
        * @param {String} id The Dom id to query for.
        * @return {<a href="YAHOO.widget.ToolbarButton.html">YAHOO.widget.ToolbarButton</a>}
        */
        getButtonById: function(id) {
            var len = this._buttonList.length;
            for (var i = 0; i < len; i++) {
                if (this._buttonList[i] && this._buttonList[i].get('id') == id) {
                    return this._buttonList[i];
                }
            }
            return false;
        },
        /**
        * @method getButtonByValue
        * @description Gets a button instance or a menuitem instance from the toolbar by it's value.
        * @param {String} value The button value to query for.
        * @return {<a href="YAHOO.widget.ToolbarButton.html">YAHOO.widget.ToolbarButton</a> or <a href="YAHOO.widget.MenuItem.html">YAHOO.widget.MenuItem</a>}
        */
        getButtonByValue: function(value) {
            var _buttons = this.get('buttons');
            if (!_buttons) {
                return false;
            }
            var len = _buttons.length;
            for (var i = 0; i < len; i++) {
                if (_buttons[i].group !== undefined) {
                    for (var m = 0; m < _buttons[i].buttons.length; m++) {
                        if ((_buttons[i].buttons[m].value == value) || (_buttons[i].buttons[m].menucmd == value)) {
                            return this.getButtonById(_buttons[i].buttons[m].id);
                        }
                        if (_buttons[i].buttons[m].menu) { //Menu Button, loop through the values
                            for (var s = 0; s < _buttons[i].buttons[m].menu.length; s++) {
                                if (_buttons[i].buttons[m].menu[s].value == value) {
                                    return this.getButtonById(_buttons[i].buttons[m].id);
                                }
                            }
                        }
                    }
                } else {
                    if ((_buttons[i].value == value) || (_buttons[i].menucmd == value)) {
                        return this.getButtonById(_buttons[i].id);
                    }
                    if (_buttons[i].menu) { //Menu Button, loop through the values
                        for (var j = 0; j < _buttons[i].menu.length; j++) {
                            if (_buttons[i].menu[j].value == value) {
                                return this.getButtonById(_buttons[i].id);
                            }
                        }
                    }
                }
            }
            return false;
        },
        /**
        * @method getButtonByIndex
        * @description Gets a button instance from the toolbar by is index in _buttonList.
        * @param {Number} index The index of the button in _buttonList.
        * @return {<a href="YAHOO.widget.ToolbarButton.html">YAHOO.widget.ToolbarButton</a>}
        */
        getButtonByIndex: function(index) {
            if (this._buttonList[index]) {
                return this._buttonList[index];
            } else {
                return false;
            }
        },
        /**
        * @method getButtons
        * @description Returns an array of buttons in the current toolbar
        * @return {Array}
        */
        getButtons: function() {
            return this._buttonList;
        },
        /**
        * @method disableButton
        * @description Disables a button in the toolbar.
        * @param {String/Number} id Disable a button by it's id, index or value.
        * @return {Boolean}
        */
        disableButton: function(id) {
            var button = getButton.call(this, id);
            if (button) {
                button.set('disabled', true);
            } else {
                return false;
            }
        },
        /**
        * @method enableButton
        * @description Enables a button in the toolbar.
        * @param {String/Number} id Enable a button by it's id, index or value.
        * @return {Boolean}
        */
        enableButton: function(id) {
            if (this.get('disabled')) {
                return false;
            }
            var button = getButton.call(this, id);
            if (button) {
                if (button.get('disabled')) {
                    button.set('disabled', false);
                }
            } else {
                return false;
            }
        },
        /**
        * @method isSelected
        * @description Tells if a button is selected or not.
        * @param {String/Number} id A button by it's id, index or value.
        * @return {Boolean}
        */
        isSelected: function(id) {
            var button = getButton.call(this, id);
            if (button) {
                return button._selected;
            }
            return false;
        },
        /**
        * @method selectButton
        * @description Selects a button in the toolbar.
        * @param {String/Number} id Select a button by it's id, index or value.
        * @param {String} value If this is a Menu Button, check this item in the menu
        * @return {Boolean}
        */
        selectButton: function(id, value) {
            var button = getButton.call(this, id);
            if (button) {
                button.addClass('yui-button-selected');
                button.addClass('yui-button-' + button.get('value') + '-selected');
                button._selected = true;
                if (value) {
                    if (button.buttonType == 'rich') {
                        var _items = button.getMenu().getItems();
                        for (var m = 0; m < _items.length; m++) {
                            if (_items[m].value == value) {
                                _items[m].cfg.setProperty('checked', true);
                                button.set('label', '<span class="yui-toolbar-' + button.get('value') + '-' + (value).replace(/ /g, '-').toLowerCase() + '">' + _items[m]._oText.nodeValue + '</span>');
                            } else {
                                _items[m].cfg.setProperty('checked', false);
                            }
                        }
                    }
                }
            } else {
                return false;
            }
        },
        /**
        * @method deselectButton
        * @description Deselects a button in the toolbar.
        * @param {String/Number} id Deselect a button by it's id, index or value.
        * @return {Boolean}
        */
        deselectButton: function(id) {
            var button = getButton.call(this, id);
            if (button) {
                button.removeClass('yui-button-selected');
                button.removeClass('yui-button-' + button.get('value') + '-selected');
                button.removeClass('yui-button-hover');
                button._selected = false;
            } else {
                return false;
            }
        },
        /**
        * @method deselectAllButtons
        * @description Deselects all buttons in the toolbar.
        * @return {Boolean}
        */
        deselectAllButtons: function() {
            var len = this._buttonList.length;
            for (var i = 0; i < len; i++) {
                this.deselectButton(this._buttonList[i]);
            }
        },
        /**
        * @method disableAllButtons
        * @description Disables all buttons in the toolbar.
        * @return {Boolean}
        */
        disableAllButtons: function() {
            if (this.get('disabled')) {
                return false;
            }
            var len = this._buttonList.length;
            for (var i = 0; i < len; i++) {
                this.disableButton(this._buttonList[i]);
            }
        },
        /**
        * @method enableAllButtons
        * @description Enables all buttons in the toolbar.
        * @return {Boolean}
        */
        enableAllButtons: function() {
            if (this.get('disabled')) {
                return false;
            }
            var len = this._buttonList.length;
            for (var i = 0; i < len; i++) {
                this.enableButton(this._buttonList[i]);
            }
        },
        /**
        * @method resetAllButtons
        * @description Resets all buttons to their initial state.
        * @param {Object} _ex Except these buttons
        * @return {Boolean}
        */
        resetAllButtons: function(_ex) {
            if (!Lang.isObject(_ex)) {
                _ex = {};
            }
            if (this.get('disabled') || !this._buttonList) {
                return false;
            }
            var len = this._buttonList.length;
            for (var i = 0; i < len; i++) {
                var _button = this._buttonList[i];
                if (_button) {
                    var disabled = _button._configs.disabled._initialConfig.value;
                    if (_ex[_button.get('id')]) {
                        this.enableButton(_button);
                        this.selectButton(_button);
                    } else {
                        if (disabled) {
                            this.disableButton(_button);
                        } else {
                            this.enableButton(_button);
                        }
                        this.deselectButton(_button);
                    }
                }
            }
        },
        /**
        * @method destroyButton
        * @description Destroy a button in the toolbar.
        * @param {String/Number} id Destroy a button by it's id or index.
        * @return {Boolean}
        */
        destroyButton: function(id) {
            var button = getButton.call(this, id);
            if (button) {
                var thisID = button.get('id'),
                    new_list = [], i = 0,
                    len = this._buttonList.length;

                button.destroy();
                
                for (i = 0; i < len; i++) {
                    if (this._buttonList[i].get('id') != thisID) {
                        new_list[new_list.length]= this._buttonList[i];
                    }
                }

                this._buttonList = new_list;
            } else {
                return false;
            }
        },
        /**
        * @method destroy
        * @description Destroys the toolbar, all of it's elements and objects.
        * @return {Boolean}
        */
        destroy: function() {
            var len = this._configuredButtons.length, j, i, b;
            for(b = 0; b < len; b++) {
                this.destroyButton(this._configuredButtons[b]);
            }

            this._configuredButtons = null;
        
            this.get('element').innerHTML = '';
            this.get('element').className = '';
            //Brutal Object Destroy
            for (i in this) {
                if (Lang.hasOwnProperty(this, i)) {
                    this[i] = null;
                }
            }
            return true;
        },
        /**
        * @method collapse
        * @description Programatically collapse the toolbar.
        * @param {Boolean} collapse True to collapse, false to expand.
        */
        collapse: function(collapse) {
            var el = Dom.getElementsByClassName('collapse', 'span', this._titlebar);
            if (collapse === false) {
                Dom.removeClass(this.get('cont').parentNode, 'yui-toolbar-container-collapsed');
                if (el[0]) {
                    Dom.removeClass(el[0], 'collapsed');
                    el[0].title = this.STR_COLLAPSE;
                }
                this.fireEvent('toolbarExpanded', { type: 'toolbarExpanded', target: this });
            } else {
                if (el[0]) {
                    Dom.addClass(el[0], 'collapsed');
                    el[0].title = this.STR_EXPAND;
                }
                Dom.addClass(this.get('cont').parentNode, 'yui-toolbar-container-collapsed');
                this.fireEvent('toolbarCollapsed', { type: 'toolbarCollapsed', target: this });
            }
        },
        /**
        * @method toString
        * @description Returns a string representing the toolbar.
        * @return {String}
        */
        toString: function() {
            return 'Toolbar (#' + this.get('element').id + ') with ' + this._buttonList.length + ' buttons.';
        }
    });
/**
* @event buttonClick
* @param {Object} o The object passed to this handler is the button config used to create the button.
* @description Fires when any botton receives a click event. Passes back a single object representing the buttons config object. See <a href="YAHOO.util.Element.html#addListener">Element.addListener</a> for more information on listening for this event.
* @type YAHOO.util.CustomEvent
*/
/**
* @event valueClick
* @param {Object} o The object passed to this handler is the button config used to create the button.
* @description This is a special dynamic event that is created and dispatched based on the value property
* of the button config. See <a href="YAHOO.util.Element.html#addListener">Element.addListener</a> for more information on listening for this event.
* Example:
* <code><pre>
* buttons : [
*   { type: 'button', value: 'test', value: 'testButton' }
* ]</pre>
* </code>
* With the valueClick event you could subscribe to this buttons click event with this:
* tbar.in('testButtonClick', function() { alert('test button clicked'); })
* @type YAHOO.util.CustomEvent
*/
/**
* @event toolbarExpanded
* @description Fires when the toolbar is expanded via the collapse button. See <a href="YAHOO.util.Element.html#addListener">Element.addListener</a> for more information on listening for this event.
* @type YAHOO.util.CustomEvent
*/
/**
* @event toolbarCollapsed
* @description Fires when the toolbar is collapsed via the collapse button. See <a href="YAHOO.util.Element.html#addListener">Element.addListener</a> for more information on listening for this event.
* @type YAHOO.util.CustomEvent
*/
})();
/**
 * @module editor
 * @description <p>The Rich Text Editor is a UI control that replaces a standard HTML textarea; it allows for the rich formatting of text content, including common structural treatments like lists, formatting treatments like bold and italic text, and drag-and-drop inclusion and sizing of images. The Rich Text Editor's toolbar is extensible via a plugin architecture so that advanced implementations can achieve a high degree of customization.</p>
 * @namespace YAHOO.widget
 * @requires yahoo, dom, element, event, toolbar
 * @optional animation, container_core, resize, dragdrop
 */

(function() {
var Dom = YAHOO.util.Dom,
    Event = YAHOO.util.Event,
    Lang = YAHOO.lang,
    Toolbar = YAHOO.widget.Toolbar;

    /**
     * The Rich Text Editor is a UI control that replaces a standard HTML textarea; it allows for the rich formatting of text content, including common structural treatments like lists, formatting treatments like bold and italic text, and drag-and-drop inclusion and sizing of images. The Rich Text Editor's toolbar is extensible via a plugin architecture so that advanced implementations can achieve a high degree of customization.
     * @constructor
     * @class SimpleEditor
     * @extends YAHOO.util.Element
     * @param {String/HTMLElement} el The textarea element to turn into an editor.
     * @param {Object} attrs Object liternal containing configuration parameters.
    */
    
    YAHOO.widget.SimpleEditor = function(el, attrs) {
        
        var o = {};
        if (Lang.isObject(el) && (!el.tagName) && !attrs) {
            Lang.augmentObject(o, el); //Break the config reference
            el = document.createElement('textarea');
            this.DOMReady = true;
            if (o.container) {
                var c = Dom.get(o.container);
                c.appendChild(el);
            } else {
                document.body.appendChild(el);
            }
        } else {
            if (attrs) {
                Lang.augmentObject(o, attrs); //Break the config reference
            }
        }

        var oConfig = {
            element: null,
            attributes: o
        }, id = null;

        if (Lang.isString(el)) {
            id = el;
        } else {
            if (oConfig.attributes.id) {
                id = oConfig.attributes.id;
            } else {
                this.DOMReady = true;
                id = Dom.generateId(el);
            }
        }
        oConfig.element = el;

        var element_cont = document.createElement('DIV');
        oConfig.attributes.element_cont = new YAHOO.util.Element(element_cont, {
            id: id + '_container'
        });
        var div = document.createElement('div');
        Dom.addClass(div, 'first-child');
        oConfig.attributes.element_cont.appendChild(div);
        
        if (!oConfig.attributes.toolbar_cont) {
            oConfig.attributes.toolbar_cont = document.createElement('DIV');
            oConfig.attributes.toolbar_cont.id = id + '_toolbar';
            div.appendChild(oConfig.attributes.toolbar_cont);
        }
        var editorWrapper = document.createElement('DIV');
        div.appendChild(editorWrapper);
        oConfig.attributes.editor_wrapper = editorWrapper;

        YAHOO.widget.SimpleEditor.superclass.constructor.call(this, oConfig.element, oConfig.attributes);
    };


    YAHOO.extend(YAHOO.widget.SimpleEditor, YAHOO.util.Element, {
        /**
        * @private
        * @property _resizeConfig
        * @description The default config for the Resize Utility
        */
        _resizeConfig: {
            handles: ['br'],
            autoRatio: true,
            status: true,
            proxy: true,
            useShim: true,
            setSize: false
        },
        /**
        * @private
        * @method _setupResize
        * @description Creates the Resize instance and binds its events.
        */
        _setupResize: function() {
            if (!YAHOO.util.DD || !YAHOO.util.Resize) { return false; }
            if (this.get('resize')) {
                var config = {};
                Lang.augmentObject(config, this._resizeConfig); //Break the config reference
                this.resize = new YAHOO.util.Resize(this.get('element_cont').get('element'), config);
                this.resize.on('resize', function(args) {
                    var anim = this.get('animate');
                    this.set('animate', false);
                    this.set('width', args.width + 'px');
                    var h = args.height,
                        th = (this.toolbar.get('element').clientHeight + 2),
                        dh = 0;
                    if (this.dompath) {
                        dh = (this.dompath.clientHeight + 1); //It has a 1px top border..
                    }
                    var newH = (h - th - dh);
                    this.set('height', newH + 'px');
                    this.get('element_cont').setStyle('height', '');
                    this.set('animate', anim);
                }, this, true);
            }
        },
        /**
        * @property resize
        * @description A reference to the Resize object
        * @type YAHOO.util.Resize
        */
        resize: null,
        /**
        * @private
        * @method _setupDD
        * @description Sets up the DD instance used from the 'drag' config option.
        */
        _setupDD: function() {
            if (!YAHOO.util.DD) { return false; }
            if (this.get('drag')) {
                var d = this.get('drag'),
                    dd = YAHOO.util.DD;
                if (d === 'proxy') {
                    dd = YAHOO.util.DDProxy;
                }

                this.dd = new dd(this.get('element_cont').get('element'));
                this.toolbar.addClass('draggable'); 
                this.dd.setHandleElId(this.toolbar._titlebar); 
            }
        },
        /**
        * @property dd
        * @description A reference to the DragDrop object.
        * @type YAHOO.util.DD/YAHOO.util.DDProxy
        */
        dd: null,
        /**
        * @private
        * @property _lastCommand
        * @description A cache of the last execCommand (used for Undo/Redo so they don't mark an undo level)
        * @type String
        */
        _lastCommand: null,
        _undoNodeChange: function() {},
        _storeUndo: function() {},
        /**
        * @private
        * @method _checkKey
        * @description Checks a keyMap entry against a key event
        * @param {Object} k The _keyMap object
        * @param {Event} e The Mouse Event
        * @return {Boolean}
        */
        _checkKey: function(k, e) {
            var ret = false;
            if ((e.keyCode === k.key)) {
                if (k.mods && (k.mods.length > 0)) {
                    var val = 0;
                    for (var i = 0; i < k.mods.length; i++) {
                        if (this.browser.mac) {
                            if (k.mods[i] == 'ctrl') {
                                k.mods[i] = 'meta';
                            }
                        }
                        if (e[k.mods[i] + 'Key'] === true) {
                            val++;
                        }
                    }
                    if (val === k.mods.length) {
                        ret = true;
                    }
                } else {
                    ret = true;
                }
            }
            return ret;
        },
        /**
        * @private
        * @property _keyMap
        * @description Named key maps for various actions in the Editor. Example: <code>CLOSE_WINDOW: { key: 87, mods: ['shift', 'ctrl'] }</code>. 
        * This entry shows that when key 87 (W) is found with the modifiers of shift and control, the window will close. You can customize this object to tweak keyboard shortcuts.
        * @type {Object/Mixed}
        */
        _keyMap: {
            SELECT_ALL: {
                key: 65, //A key
                mods: ['ctrl']
            },
            CLOSE_WINDOW: {
                key: 87, //W key
                mods: ['shift', 'ctrl']
            },
            FOCUS_TOOLBAR: {
                key: 27,
                mods: ['shift']
            },
            FOCUS_AFTER: {
                key: 27
            },
            FONT_SIZE_UP: {
                key: 38,
                mods: ['shift', 'ctrl']
            },
            FONT_SIZE_DOWN: {
                key: 40,
                mods: ['shift', 'ctrl']
            },
            CREATE_LINK: {
                key: 76,
                mods: ['shift', 'ctrl']
            },
            BOLD: {
                key: 66,
                mods: ['shift', 'ctrl']
            },
            ITALIC: {
                key: 73,
                mods: ['shift', 'ctrl']
            },
            UNDERLINE: {
                key: 85,
                mods: ['shift', 'ctrl']
            },
            UNDO: {
                key: 90,
                mods: ['ctrl']
            },
            REDO: {
                key: 90,
                mods: ['shift', 'ctrl']
            },
            JUSTIFY_LEFT: {
                key: 219,
                mods: ['shift', 'ctrl']
            },
            JUSTIFY_CENTER: {
                key: 220,
                mods: ['shift', 'ctrl']
            },
            JUSTIFY_RIGHT: {
                key: 221,
                mods: ['shift', 'ctrl']
            }
        },
        /**
        * @private
        * @method _cleanClassName
        * @description Makes a useable classname from dynamic data, by dropping it to lowercase and replacing spaces with -'s.
        * @param {String} str The classname to clean up
        * @return {String}
        */
        _cleanClassName: function(str) {
            return str.replace(/ /g, '-').toLowerCase();
        },
        /**
        * @property _textarea
        * @description Flag to determine if we are using a textarea or an HTML Node.
        * @type Boolean
        */
        _textarea: null,
        /**
        * @property _docType
        * @description The DOCTYPE to use in the editable container.
        * @type String
        */
        _docType: '<!DOCTYPE HTML PUBLIC "-/'+'/W3C/'+'/DTD HTML 4.01/'+'/EN" "http:/'+'/www.w3.org/TR/html4/strict.dtd">',
        /**
        * @property editorDirty
        * @description This flag will be set when certain things in the Editor happen. It is to be used by the developer to check to see if content has changed.
        * @type Boolean
        */
        editorDirty: null,
        /**
        * @property _defaultCSS
        * @description The default CSS used in the config for 'css'. This way you can add to the config like this: { css: YAHOO.widget.SimpleEditor.prototype._defaultCSS + 'ADD MYY CSS HERE' }
        * @type String
        */
        _defaultCSS: 'html { height: 95%; } body { padding: 7px; background-color: #fff; font: 13px/1.22 arial,helvetica,clean,sans-serif;*font-size:small;*font:x-small; } a, a:visited, a:hover { color: blue !important; text-decoration: underline !important; cursor: text !important; } .warning-localfile { border-bottom: 1px dashed red !important; } .yui-busy { cursor: wait !important; } img.selected { border: 2px dotted #808080; } img { cursor: pointer !important; border: none; } body.ptags.webkit div.yui-wk-p { margin: 11px 0; } body.ptags.webkit div.yui-wk-div { margin: 0; }',
        /**
        * @property _defaultToolbar
        * @private
        * @description Default toolbar config.
        * @type Object
        */
        _defaultToolbar: null,
        /**
        * @property _lastButton
        * @private
        * @description The last button pressed, so we don't disable it.
        * @type Object
        */
        _lastButton: null,
        /**
        * @property _baseHREF
        * @private
        * @description The base location of the editable page (this page) so that relative paths for image work.
        * @type String
        */
        _baseHREF: function() {
            var href = document.location.href;
            if (href.indexOf('?') !== -1) { //Remove the query string
                href = href.substring(0, href.indexOf('?'));
            }
            href = href.substring(0, href.lastIndexOf('/')) + '/';
            return href;
        }(),
        /**
        * @property _lastImage
        * @private
        * @description Safari reference for the last image selected (for styling as selected).
        * @type HTMLElement
        */
        _lastImage: null,
        /**
        * @property _blankImageLoaded
        * @private
        * @description Don't load the blank image more than once..
        * @type Boolean
        */
        _blankImageLoaded: null,
        /**
        * @property _fixNodesTimer
        * @private
        * @description Holder for the fixNodes timer
        * @type Date
        */
        _fixNodesTimer: null,
        /**
        * @property _nodeChangeTimer
        * @private
        * @description Holds a reference to the nodeChange setTimeout call
        * @type Number
        */
        _nodeChangeTimer: null,
        /**
        * @property _nodeChangeDelayTimer
        * @private
        * @description Holds a reference to the nodeChangeDelay setTimeout call
        * @type Number
        */
        _nodeChangeDelayTimer: null,
        /**
        * @property _lastNodeChangeEvent
        * @private
        * @description Flag to determine the last event that fired a node change
        * @type Event
        */
        _lastNodeChangeEvent: null,
        /**
        * @property _lastNodeChange
        * @private
        * @description Flag to determine when the last node change was fired
        * @type Date
        */
        _lastNodeChange: 0,
        /**
        * @property _rendered
        * @private
        * @description Flag to determine if editor has been rendered or not
        * @type Boolean
        */
        _rendered: null,
        /**
        * @property DOMReady
        * @private
        * @description Flag to determine if DOM is ready or not
        * @type Boolean
        */
        DOMReady: null,
        /**
        * @property _selection
        * @private
        * @description Holder for caching iframe selections
        * @type Object
        */
        _selection: null,
        /**
        * @property _mask
        * @private
        * @description DOM Element holder for the editor Mask when disabled
        * @type Object
        */
        _mask: null,
        /**
        * @property _showingHiddenElements
        * @private
        * @description Status of the hidden elements button
        * @type Boolean
        */
        _showingHiddenElements: null,
        /**
        * @property currentWindow
        * @description A reference to the currently open EditorWindow
        * @type Object
        */
        currentWindow: null,
        /**
        * @property currentEvent
        * @description A reference to the current editor event
        * @type Event
        */
        currentEvent: null,
        /**
        * @property operaEvent
        * @private
        * @description setTimeout holder for Opera and Image DoubleClick event..
        * @type Object
        */
        operaEvent: null,
        /**
        * @property currentFont
        * @description A reference to the last font selected from the Toolbar
        * @type HTMLElement
        */
        currentFont: null,
        /**
        * @property currentElement
        * @description A reference to the current working element in the editor
        * @type Array
        */
        currentElement: null,
        /**
        * @property dompath
        * @description A reference to the dompath container for writing the current working dom path to.
        * @type HTMLElement
        */
        dompath: null,
        /**
        * @property beforeElement
        * @description A reference to the H2 placed before the editor for Accessibilty.
        * @type HTMLElement
        */
        beforeElement: null,
        /**
        * @property afterElement
        * @description A reference to the H2 placed after the editor for Accessibilty.
        * @type HTMLElement
        */
        afterElement: null,
        /**
        * @property invalidHTML
        * @description Contains a list of HTML elements that are invalid inside the editor. They will be removed when they are found. If you set the value of a key to "{ keepContents: true }", then the element will be replaced with a yui-non span to be filtered out when cleanHTML is called. The only tag that is ignored here is the span tag as it will force the Editor into a loop and freeze the browser. However.. all of these tags will be removed in the cleanHTML routine.
        * @type Object
        */
        invalidHTML: {
            form: true,
            input: true,
            button: true,
            select: true,
            link: true,
            html: true,
            body: true,
            iframe: true,
            script: true,
            style: true,
            textarea: true
        },
        /**
        * @property toolbar
        * @description Local property containing the <a href="YAHOO.widget.Toolbar.html">YAHOO.widget.Toolbar</a> instance
        * @type <a href="YAHOO.widget.Toolbar.html">YAHOO.widget.Toolbar</a>
        */
        toolbar: null,
        /**
        * @private
        * @property _contentTimer
        * @description setTimeout holder for documentReady check
        */
        _contentTimer: null,
        /**
        * @private
        * @property _contentTimerMax
        * @description The number of times the loaded content should be checked before giving up. Default: 500
        */
        _contentTimerMax: 500,
        /**
        * @private
        * @property _contentTimerCounter
        * @description Counter to check the number of times the body is polled for before giving up
        * @type Number
        */
        _contentTimerCounter: 0,
        /**
        * @private
        * @property _disabled
        * @description The Toolbar items that should be disabled if there is no selection present in the editor.
        * @type Array
        */
        _disabled: [ 'createlink', 'fontname', 'fontsize', 'forecolor', 'backcolor' ],
        /**
        * @private
        * @property _alwaysDisabled
        * @description The Toolbar items that should ALWAYS be disabled event if there is a selection present in the editor.
        * @type Object
        */
        _alwaysDisabled: { undo: true, redo: true },
        /**
        * @private
        * @property _alwaysEnabled
        * @description The Toolbar items that should ALWAYS be enabled event if there isn't a selection present in the editor.
        * @type Object
        */
        _alwaysEnabled: { },
        /**
        * @private
        * @property _semantic
        * @description The Toolbar commands that we should attempt to make tags out of instead of using styles.
        * @type Object
        */
        _semantic: { 'bold': true, 'italic' : true, 'underline' : true },
        /**
        * @private
        * @property _tag2cmd
        * @description A tag map of HTML tags to convert to the different types of commands so we can select the proper toolbar button.
        * @type Object
        */
        _tag2cmd: {
            'b': 'bold',
            'strong': 'bold',
            'i': 'italic',
            'em': 'italic',
            'u': 'underline',
            'sup': 'superscript',
            'sub': 'subscript',
            'img': 'insertimage',
            'a' : 'createlink',
            'ul' : 'insertunorderedlist',
            'ol' : 'insertorderedlist'
        },

        /**
        * @private _createIframe
        * @description Creates the DOM and YUI Element for the iFrame editor area.
        * @param {String} id The string ID to prefix the iframe with
        * @return {Object} iFrame object
        */
        _createIframe: function() {
            var ifrmDom = document.createElement('iframe');
            ifrmDom.id = this.get('id') + '_editor';
            var config = {
                border: '0',
                frameBorder: '0',
                marginWidth: '0',
                marginHeight: '0',
                leftMargin: '0',
                topMargin: '0',
                allowTransparency: 'true',
                width: '100%'
            };
            if (this.get('autoHeight')) {
                config.scrolling = 'no';
            }
            for (var i in config) {
                if (Lang.hasOwnProperty(config, i)) {
                    ifrmDom.setAttribute(i, config[i]);
                }
            }
            var isrc = 'javascript:;';
            if (this.browser.ie) {
                //isrc = 'about:blank';
                //TODO - Check this, I have changed it before..
                isrc = 'javascript:false;';
            }
            ifrmDom.setAttribute('src', isrc);
            var ifrm = new YAHOO.util.Element(ifrmDom);
            ifrm.setStyle('visibility', 'hidden');
            return ifrm;
        },
        /**
        * @private _isElement
        * @description Checks to see if an Element reference is a valid one and has a certain tag type
        * @param {HTMLElement} el The element to check
        * @param {String} tag The tag that the element needs to be
        * @return {Boolean}
        */
        _isElement: function(el, tag) {
            if (el && el.tagName && (el.tagName.toLowerCase() == tag)) {
                return true;
            }
            if (el && el.getAttribute && (el.getAttribute('tag') == tag)) {
                return true;
            }
            return false;
        },
        /**
        * @private _hasParent
        * @description Checks to see if an Element reference or one of it's parents is a valid one and has a certain tag type
        * @param {HTMLElement} el The element to check
        * @param {String} tag The tag that the element needs to be
        * @return HTMLElement
        */
        _hasParent: function(el, tag) {
            if (!el || !el.parentNode) {
                return false;
            }
            
            while (el.parentNode) {
                if (this._isElement(el, tag)) {
                    return el;
                }
                if (el.parentNode) {
                    el = el.parentNode;
                } else {
                    return false;
                }
            }
            return false;
        },
        /**
        * @private
        * @method _getDoc
        * @description Get the Document of the IFRAME
        * @return {Object}
        */
        _getDoc: function() {
            var value = false;
            try {
                if (this.get('iframe').get('element').contentWindow.document) {
                    value = this.get('iframe').get('element').contentWindow.document;
                    return value;
                }
            } catch (e) {
                return false;
            }
        },
        /**
        * @private
        * @method _getWindow
        * @description Get the Window of the IFRAME
        * @return {Object}
        */
        _getWindow: function() {
            return this.get('iframe').get('element').contentWindow;
        },
        /**
        * @method focus
        * @description Attempt to set the focus of the iframes window.
        */
        focus: function() {
            this._getWindow().focus();
        },
        /**
        * @private
        * @depreciated - This should not be used, moved to this.focus();
        * @method _focusWindow
        * @description Attempt to set the focus of the iframes window.
        */
        _focusWindow: function() {
            this.focus();
        },
        /**
        * @private
        * @method _hasSelection
        * @description Determines if there is a selection in the editor document.
        * @return {Boolean}
        */
        _hasSelection: function() {
            var sel = this._getSelection();
            var range = this._getRange();
            var hasSel = false;

            if (!sel || !range) {
                return hasSel;
            }

            //Internet Explorer
            if (this.browser.ie) {
                if (range.text) {
                    hasSel = true;
                }
                if (range.html) {
                    hasSel = true;
                }
            } else {
                if (this.browser.webkit) {
                    if (sel+'' !== '') {
                        hasSel = true;
                    }
                } else {
                    if (sel && (sel.toString() !== '') && (sel !== undefined)) {
                        hasSel = true;
                    }
                }
            }
            return hasSel;
        },
        /**
        * @private
        * @method _getSelection
        * @description Handles the different selection objects across the A-Grade list.
        * @return {Object} Selection Object
        */
        _getSelection: function() {
            var _sel = null;
            if (this._getDoc() && this._getWindow()) {
                if (this._getDoc().selection &&! this.browser.opera) {
                    _sel = this._getDoc().selection;
                } else {
                    _sel = this._getWindow().getSelection();
                }
                //Handle Safari's lack of Selection Object
                if (this.browser.webkit) {
                    if (_sel.baseNode) {
                            this._selection = {};
                            this._selection.baseNode = _sel.baseNode;
                            this._selection.baseOffset = _sel.baseOffset;
                            this._selection.extentNode = _sel.extentNode;
                            this._selection.extentOffset = _sel.extentOffset;
                    } else if (this._selection !== null) {
                        _sel = this._getWindow().getSelection();
                        _sel.setBaseAndExtent(
                            this._selection.baseNode,
                            this._selection.baseOffset,
                            this._selection.extentNode,
                            this._selection.extentOffset);
                        this._selection = null;
                    }
                }
            }
            return _sel;
        },
        /**
        * @private
        * @method _selectNode
        * @description Places the highlight around a given node
        * @param {HTMLElement} node The node to select
        */
        _selectNode: function(node, collapse) {
            if (!node) {
                return false;
            }
            var sel = this._getSelection(),
                range = null;

            if (this.browser.ie) {
                try { //IE freaks out here sometimes..
                    range = this._getDoc().body.createTextRange();
                    range.moveToElementText(node);
                    range.select();
                } catch (e) {
                }
            } else if (this.browser.webkit) {
                if (collapse) {
				    sel.setBaseAndExtent(node, 1, node, node.innerText.length);
                } else {
				    sel.setBaseAndExtent(node, 0, node, node.innerText.length);
                }
            } else if (this.browser.opera) {
                sel = this._getWindow().getSelection();
                range = this._getDoc().createRange();
                range.selectNode(node);
                sel.removeAllRanges();
                sel.addRange(range);
            } else {
                range = this._getDoc().createRange();
                range.selectNodeContents(node);
                sel.removeAllRanges();
                sel.addRange(range);
            }
            //TODO - Check Performance
            this.nodeChange();
        },
        /**
        * @private
        * @method _getRange
        * @description Handles the different range objects across the A-Grade list.
        * @return {Object} Range Object
        */
        _getRange: function() {
            var sel = this._getSelection();

            if (sel === null) {
                return null;
            }

            if (this.browser.webkit && !sel.getRangeAt) {
                var _range = this._getDoc().createRange();
                try {
                    _range.setStart(sel.anchorNode, sel.anchorOffset);
                    _range.setEnd(sel.focusNode, sel.focusOffset);
                } catch (e) {
                    _range = this._getWindow().getSelection()+'';
                }
                return _range;
            }

            if (this.browser.ie) {
                try {
                    return sel.createRange();
                } catch (e2) {
                    return null;
                }
            }

            if (sel.rangeCount > 0) {
                return sel.getRangeAt(0);
            }
            return null;
        },
        /**
        * @private
        * @method _setDesignMode
        * @description Sets the designMode property of the iFrame document's body.
        * @param {String} state This should be either on or off
        */
        _setDesignMode: function(state) {
            if (this.get('setDesignMode')) {
                try {
                    this._getDoc().designMode = ((state.toLowerCase() == 'off') ? 'off' : 'on');
                } catch(e) { }
            }
        },
        /**
        * @private
        * @method _toggleDesignMode
        * @description Toggles the designMode property of the iFrame document on and off.
        * @return {String} The state that it was set to.
        */
        _toggleDesignMode: function() {
            var _dMode = this._getDoc().designMode,
                _state = ((_dMode.toLowerCase() == 'on') ? 'off' : 'on');
            this._setDesignMode(_state);
            return _state;
        },
        /**
        * @private
        * @property _focused
        * @description Holder for trapping focus/blur state and prevent double events
        * @type Boolean
        */
        _focused: null,
        /**
        * @private
        * @method _handleFocus
        * @description Handles the focus of the iframe. Note, this is window focus event, not an Editor focus event.
        * @param {Event} e The DOM Event
        */
        _handleFocus: function(e) {
            if (!this._focused) {
                this._focused = true;
                this.fireEvent('editorWindowFocus', { type: 'editorWindowFocus', target: this });
            }
        },
        /**
        * @private
        * @method _handleBlur
        * @description Handles the blur of the iframe. Note, this is window blur event, not an Editor blur event.
        * @param {Event} e The DOM Event
        */
        _handleBlur: function(e) {
            if (this._focused) {
                this._focused = false;
                this.fireEvent('editorWindowBlur', { type: 'editorWindowBlur', target: this });
            }
        },
        /**
        * @private
        * @method _initEditorEvents
        * @description This method sets up the listeners on the Editors document.
        */
        _initEditorEvents: function() {
            //Setup Listeners on iFrame
            var doc = this._getDoc(),
                win = this._getWindow();

            Event.on(doc, 'mouseup', this._handleMouseUp, this, true);
            Event.on(doc, 'mousedown', this._handleMouseDown, this, true);
            Event.on(doc, 'click', this._handleClick, this, true);
            Event.on(doc, 'dblclick', this._handleDoubleClick, this, true);
            Event.on(doc, 'keypress', this._handleKeyPress, this, true);
            Event.on(doc, 'keyup', this._handleKeyUp, this, true);
            Event.on(doc, 'keydown', this._handleKeyDown, this, true);
            /* TODO -- Everyone but Opera works here..
            Event.on(doc, 'paste', function() {
            }, this, true);
            */
 
            //Focus and blur..
            Event.on(win, 'focus', this._handleFocus, this, true);
            Event.on(win, 'blur', this._handleBlur, this, true);
        },
        /**
        * @private
        * @method _removeEditorEvents
        * @description This method removes the listeners on the Editors document (for disabling).
        */
        _removeEditorEvents: function() {
            //Remove Listeners on iFrame
            var doc = this._getDoc(),
                win = this._getWindow();

            Event.removeListener(doc, 'mouseup', this._handleMouseUp, this, true);
            Event.removeListener(doc, 'mousedown', this._handleMouseDown, this, true);
            Event.removeListener(doc, 'click', this._handleClick, this, true);
            Event.removeListener(doc, 'dblclick', this._handleDoubleClick, this, true);
            Event.removeListener(doc, 'keypress', this._handleKeyPress, this, true);
            Event.removeListener(doc, 'keyup', this._handleKeyUp, this, true);
            Event.removeListener(doc, 'keydown', this._handleKeyDown, this, true);

            //Focus and blur..
            Event.removeListener(win, 'focus', this._handleFocus, this, true);
            Event.removeListener(win, 'blur', this._handleBlur, this, true);
        },
        _fixWebkitDivs: function() {
            if (this.browser.webkit) {
                var divs = this._getDoc().body.getElementsByTagName('div');
                Dom.addClass(divs, 'yui-wk-div');
            }
        },
        /**
        * @private
        * @method _initEditor
        * @param {Boolean} raw Don't add events.
        * @description This method is fired from _checkLoaded when the document is ready. It turns on designMode and set's up the listeners.
        */
        _initEditor: function(raw) {
            if (this._editorInit) {
                return;
            }
            this._editorInit = true;
            if (this.browser.ie) {
                this._getDoc().body.style.margin = '0';
            }
            if (!this.get('disabled')) {
                this._setDesignMode('on');
                this._contentTimerCounter = 0;
            }
            if (!this._getDoc().body) {
                this._contentTimerCounter = 0;
                this._editorInit = false;
                this._checkLoaded();
                return false;
            }
            
            if (!raw) {
                this.toolbar.on('buttonClick', this._handleToolbarClick, this, true);
            }
            if (!this.get('disabled')) {
                this._initEditorEvents();
                this.toolbar.set('disabled', false);
            }

            if (raw) {
                this.fireEvent('editorContentReloaded', { type: 'editorreloaded', target: this });
            } else {
                this.fireEvent('editorContentLoaded', { type: 'editorLoaded', target: this });
            }
            this._fixWebkitDivs();
            if (this.get('dompath')) {
                var self = this;
                setTimeout(function() {
                    self._writeDomPath.call(self);
                    self._setupResize.call(self);
                }, 150);
            }
            var br = [];
            for (var i in this.browser) {
                if (this.browser[i]) {
                    br.push(i);
                }
            }
            if (this.get('ptags')) {
                br.push('ptags');
            }
            Dom.addClass(this._getDoc().body, br.join(' '));
            this.nodeChange(true);
        },
        /**
        * @private
        * @method _checkLoaded
        * @param {Boolean} raw Don't add events.
        * @description Called from a setTimeout loop to check if the iframes body.onload event has fired, then it will init the editor.
        */
        _checkLoaded: function(raw) {
            this._editorInit = false;
            this._contentTimerCounter++;
            if (this._contentTimer) {
                clearTimeout(this._contentTimer);
            }
            if (this._contentTimerCounter > this._contentTimerMax) {
                return false;
            }
            var init = false;
            try {
                if (this._getDoc() && this._getDoc().body) {
                    if (this.browser.ie) {
                        if (this._getDoc().body.readyState == 'complete') {
                            init = true;
                        }
                    } else {
                        if (this._getDoc().body._rteLoaded === true) {
                            init = true;
                        }
                    }
                }
            } catch (e) {
                init = false;
            }

            if (init === true) {
                //The onload event has fired, clean up after ourselves and fire the _initEditor method
                this._initEditor(raw);
            } else {
                var self = this;
                this._contentTimer = setTimeout(function() {
                    self._checkLoaded.call(self, raw);
                }, 20);
            }
        },
        /**
        * @private
        * @method _setInitialContent
        * @param {Boolean} raw Don't add events.
        * @description This method will open the iframes content document and write the textareas value into it, then start the body.onload checking.
        */
        _setInitialContent: function(raw) {

            var value = ((this._textarea) ? this.get('element').value : this.get('element').innerHTML),
                doc = null;

            if (value === '') {
                value = '<br>';
            }

            var html = Lang.substitute(this.get('html'), {
                TITLE: this.STR_TITLE,
                CONTENT: this._cleanIncomingHTML(value),
                CSS: this.get('css'),
                HIDDEN_CSS: ((this.get('hiddencss')) ? this.get('hiddencss') : '/* No Hidden CSS */'),
                EXTRA_CSS: ((this.get('extracss')) ? this.get('extracss') : '/* No Extra CSS */')
            }),
            check = true;

            html = html.replace(/RIGHT_BRACKET/gi, '{');
            html = html.replace(/LEFT_BRACKET/gi, '}');

            if (document.compatMode != 'BackCompat') {
                html = this._docType + "\n" + html;
            } else {
            }

            if (this.browser.ie || this.browser.webkit || this.browser.opera || (navigator.userAgent.indexOf('Firefox/1.5') != -1)) {
                //Firefox 1.5 doesn't like setting designMode on an document created with a data url
                try {
                    //Adobe AIR Code
                    if (this.browser.air) {
                        doc = this._getDoc().implementation.createHTMLDocument();
                        var origDoc = this._getDoc();
                        origDoc.open();
                        origDoc.close();
                        doc.open();
                        doc.write(html);
                        doc.close();
                        var node = origDoc.importNode(doc.getElementsByTagName("html")[0], true);
                        origDoc.replaceChild(node, origDoc.getElementsByTagName("html")[0]);
                        origDoc.body._rteLoaded = true;
                    } else {
                        doc = this._getDoc();
                        doc.open();
                        doc.write(html);
                        doc.close();
                    }
                } catch (e) {
                    //Safari will only be here if we are hidden
                    check = false;
                }
            } else {
                //This keeps Firefox 2 from writing the iframe to history preserving the back buttons functionality
                this.get('iframe').get('element').src = 'data:text/html;charset=utf-8,' + encodeURIComponent(html);
            }
            this.get('iframe').setStyle('visibility', '');
            if (check) {
                this._checkLoaded(raw);
            }            
        },
        /**
        * @private
        * @method _setMarkupType
        * @param {String} action The action to take. Possible values are: css, default or semantic
        * @description This method will turn on/off the useCSS execCommand.
        */
        _setMarkupType: function(action) {
            switch (this.get('markup')) {
                case 'css':
                    this._setEditorStyle(true);
                    break;
                case 'default':
                    this._setEditorStyle(false);
                    break;
                case 'semantic':
                case 'xhtml':
                    if (this._semantic[action]) {
                        this._setEditorStyle(false);
                    } else {
                        this._setEditorStyle(true);
                    }
                    break;
            }
        },
        /**
        * Set the editor to use CSS instead of HTML
        * @param {Booleen} stat True/False
        */
        _setEditorStyle: function(stat) {
            try {
                this._getDoc().execCommand('useCSS', false, !stat);
            } catch (ex) {
            }
        },
        /**
        * @private
        * @method _getSelectedElement
        * @description This method will attempt to locate the element that was last interacted with, either via selection, location or event.
        * @return {HTMLElement} The currently selected element.
        */
        _getSelectedElement: function() {
            var doc = this._getDoc(),
                range = null,
                sel = null,
                elm = null,
                check = true;

            if (this.browser.ie) {
                this.currentEvent = this._getWindow().event; //Event utility assumes window.event, so we need to reset it to this._getWindow().event;
                range = this._getRange();
                if (range) {
                    elm = range.item ? range.item(0) : range.parentElement();
                    if (this._hasSelection()) {
                        //TODO
                        //WTF.. Why can't I get an element reference here?!??!
                    }
                    if (elm === doc.body) {
                        elm = null;
                    }
                }
                if ((this.currentEvent !== null) && (this.currentEvent.keyCode === 0)) {
                    elm = Event.getTarget(this.currentEvent);
                }
            } else {
                sel = this._getSelection();
                range = this._getRange();

                if (!sel || !range) {
                    return null;
                }
                //TODO
                if (!this._hasSelection() && this.browser.webkit3) {
                    //check = false;
                }
                if (this.browser.gecko) {
                    //Added in 2.6.0
                    if (range.startContainer) {
                        if (range.startContainer.nodeType === 3) {
                            elm = range.startContainer.parentNode;
                        } else if (range.startContainer.nodeType === 1) {
                            elm = range.startContainer;
                        }
                        //Added in 2.7.0
                        if (this.currentEvent) {
                            var tar = Event.getTarget(this.currentEvent);
                            if (!this._isElement(tar, 'html')) {
                                if (elm !== tar) {
                                    elm = tar;
                                }
                            }
                        }
                    }
                }
                
                if (check) {
                    if (sel.anchorNode && (sel.anchorNode.nodeType == 3)) {
                        if (sel.anchorNode.parentNode) { //next check parentNode
                            elm = sel.anchorNode.parentNode;
                        }
                        if (sel.anchorNode.nextSibling != sel.focusNode.nextSibling) {
                            elm = sel.anchorNode.nextSibling;
                        }
                    }
                    if (this._isElement(elm, 'br')) {
                        elm = null;
                    }
                    if (!elm) {
                        elm = range.commonAncestorContainer;
                        if (!range.collapsed) {
                            if (range.startContainer == range.endContainer) {
                                if (range.startOffset - range.endOffset < 2) {
                                    if (range.startContainer.hasChildNodes()) {
                                        elm = range.startContainer.childNodes[range.startOffset];
                                    }
                                }
                            }
                        }
                    }
               }
            }
            
            if (this.currentEvent !== null) {
                try {
                    switch (this.currentEvent.type) {
                        case 'click':
                        case 'mousedown':
                        case 'mouseup':
                            if (this.browser.webkit) {
                                elm = Event.getTarget(this.currentEvent);
                            }
                            break;
                        default:
                            //Do nothing
                            break;
                    }
                } catch (e) {
                }
            } else if ((this.currentElement && this.currentElement[0]) && (!this.browser.ie)) {
                //TODO is this still needed?
                //elm = this.currentElement[0];
            }


            if (this.browser.opera || this.browser.webkit) {
                if (this.currentEvent && !elm) {
                    elm = YAHOO.util.Event.getTarget(this.currentEvent);
                }
            }
            if (!elm || !elm.tagName) {
                elm = doc.body;
            }
            if (this._isElement(elm, 'html')) {
                //Safari sometimes gives us the HTML node back..
                elm = doc.body;
            }
            if (this._isElement(elm, 'body')) {
                //make sure that body means this body not the parent..
                elm = doc.body;
            }
            if (elm && !elm.parentNode) { //Not in document
                elm = doc.body;
            }
            if (elm === undefined) {
                elm = null;
            }
            return elm;
        },
        /**
        * @private
        * @method _getDomPath
        * @description This method will attempt to build the DOM path from the currently selected element.
        * @param HTMLElement el The element to start with, if not provided _getSelectedElement is used
        * @return {Array} An array of node references that will create the DOM Path.
        */
        _getDomPath: function(el) {
            if (!el) {
			    el = this._getSelectedElement();
            }
			var domPath = [];
            while (el !== null) {
                if (el.ownerDocument != this._getDoc()) {
                    el = null;
                    break;
                }
                //Check to see if we get el.nodeName and nodeType
                if (el.nodeName && el.nodeType && (el.nodeType == 1)) {
                    domPath[domPath.length] = el;
                }

                if (this._isElement(el, 'body')) {
                    break;
                }

                el = el.parentNode;
            }
            if (domPath.length === 0) {
                if (this._getDoc() && this._getDoc().body) {
                    domPath[0] = this._getDoc().body;
                }
            }
            return domPath.reverse();
        },
        /**
        * @private
        * @method _writeDomPath
        * @description Write the current DOM path out to the dompath container below the editor.
        */
        _writeDomPath: function() { 
            var path = this._getDomPath(),
                pathArr = [],
                classPath = '',
                pathStr = '';

            for (var i = 0; i < path.length; i++) {
                var tag = path[i].tagName.toLowerCase();
                if ((tag == 'ol') && (path[i].type)) {
                    tag += ':' + path[i].type;
                }
                if (Dom.hasClass(path[i], 'yui-tag')) {
                    tag = path[i].getAttribute('tag');
                }
                if ((this.get('markup') == 'semantic') || (this.get('markup') == 'xhtml')) {
                    switch (tag) {
                        case 'b': tag = 'strong'; break;
                        case 'i': tag = 'em'; break;
                    }
                }
                if (!Dom.hasClass(path[i], 'yui-non')) {
                    if (Dom.hasClass(path[i], 'yui-tag')) {
                        pathStr = tag;
                    } else {
                        classPath = ((path[i].className !== '') ? '.' + path[i].className.replace(/ /g, '.') : '');
                        if ((classPath.indexOf('yui') != -1) || (classPath.toLowerCase().indexOf('apple-style-span') != -1)) {
                            classPath = '';
                        }
                        pathStr = tag + ((path[i].id) ? '#' + path[i].id : '') + classPath;
                    }
                    switch (tag) {
                        case 'body':
                            pathStr = 'body';
                            break;
                        case 'a':
                            if (path[i].getAttribute('href', 2)) {
                                pathStr += ':' + path[i].getAttribute('href', 2).replace('mailto:', '').replace('http:/'+'/', '').replace('https:/'+'/', ''); //May need to add others here ftp
                            }
                            break;
                        case 'img':
                            var h = path[i].height;
                            var w = path[i].width;
                            if (path[i].style.height) {
                                h = parseInt(path[i].style.height, 10);
                            }
                            if (path[i].style.width) {
                                w = parseInt(path[i].style.width, 10);
                            }
                            pathStr += '(' + w + 'x' + h + ')';
                        break;
                    }

                    if (pathStr.length > 10) {
                        pathStr = '<span title="' + pathStr + '">' + pathStr.substring(0, 10) + '...' + '</span>';
                    } else {
                        pathStr = '<span title="' + pathStr + '">' + pathStr + '</span>';
                    }
                    pathArr[pathArr.length] = pathStr;
                }
            }
            var str = pathArr.join(' ' + this.SEP_DOMPATH + ' ');
            //Prevent flickering
            if (this.dompath.innerHTML != str) {
                this.dompath.innerHTML = str;
            }
        },
        /**
        * @private
        * @method _fixNodes
        * @description Fix href and imgs as well as remove invalid HTML.
        */
        _fixNodes: function() {
            try {
                var doc = this._getDoc(),
                    els = [];

                for (var v in this.invalidHTML) {
                    if (YAHOO.lang.hasOwnProperty(this.invalidHTML, v)) {
                        if (v.toLowerCase() != 'span') {
                            var tags = doc.body.getElementsByTagName(v);
                            if (tags.length) {
                                for (var i = 0; i < tags.length; i++) {
                                    els.push(tags[i]);
                                }
                            }
                        }
                    }
                }
                for (var h = 0; h < els.length; h++) {
                    if (els[h].parentNode) {
                        if (Lang.isObject(this.invalidHTML[els[h].tagName.toLowerCase()]) && this.invalidHTML[els[h].tagName.toLowerCase()].keepContents) {
                            this._swapEl(els[h], 'span', function(el) {
                                el.className = 'yui-non';
                            });
                        } else {
                            els[h].parentNode.removeChild(els[h]);
                        }
                    }
                }
                var imgs = this._getDoc().getElementsByTagName('img');
                Dom.addClass(imgs, 'yui-img');
            } catch(e) {}
        },
        /**
        * @private
        * @method _isNonEditable
        * @param Event ev The Dom event being checked
        * @description Method is called at the beginning of all event handlers to check if this element or a parent element has the class yui-noedit (this.CLASS_NOEDIT) applied.
        * If it does, then this method will stop the event and return true. The event handlers will then return false and stop the nodeChange from occuring. This method will also
        * disable and enable the Editor's toolbar based on the noedit state.
        * @return Boolean
        */
        _isNonEditable: function(ev) {
            if (this.get('allowNoEdit')) {
                var el = Event.getTarget(ev);
                if (this._isElement(el, 'html')) {
                    el = null;
                }
                var path = this._getDomPath(el);
                for (var i = (path.length - 1); i > -1; i--) {
                    if (Dom.hasClass(path[i], this.CLASS_NOEDIT)) {
                        //if (this.toolbar.get('disabled') === false) {
                        //    this.toolbar.set('disabled', true);
                        //}
                        try {
                             this._getDoc().execCommand('enableObjectResizing', false, 'false');
                        } catch (e) {}
                        this.nodeChange();
                        Event.stopEvent(ev);
                        return true;
                    }
                }
                //if (this.toolbar.get('disabled') === true) {
                    //Should only happen once..
                    //this.toolbar.set('disabled', false);
                    try {
                         this._getDoc().execCommand('enableObjectResizing', false, 'true');
                    } catch (e2) {}
                //}
            }
            return false;
        },
        /**
        * @private
        * @method _setCurrentEvent
        * @param {Event} ev The event to cache
        * @description Sets the current event property
        */
        _setCurrentEvent: function(ev) {
            this.currentEvent = ev;
        },
        /**
        * @private
        * @method _handleClick
        * @param {Event} ev The event we are working on.
        * @description Handles all click events inside the iFrame document.
        */
        _handleClick: function(ev) {
            var ret = this.fireEvent('beforeEditorClick', { type: 'beforeEditorClick', target: this, ev: ev });
            if (ret === false) {
                return false;
            }
            if (this._isNonEditable(ev)) {
                return false;
            }
            this._setCurrentEvent(ev);
            if (this.currentWindow) {
                this.closeWindow();
            }
            if (this.currentWindow) {
                this.closeWindow();
            }
            if (this.browser.webkit) {
                var tar =Event.getTarget(ev);
                if (this._isElement(tar, 'a') || this._isElement(tar.parentNode, 'a')) {
                    Event.stopEvent(ev);
                    this.nodeChange();
                }
            } else {
                this.nodeChange();
            }
            this.fireEvent('editorClick', { type: 'editorClick', target: this, ev: ev });
        },
        /**
        * @private
        * @method _handleMouseUp
        * @param {Event} ev The event we are working on.
        * @description Handles all mouseup events inside the iFrame document.
        */
        _handleMouseUp: function(ev) {
            var ret = this.fireEvent('beforeEditorMouseUp', { type: 'beforeEditorMouseUp', target: this, ev: ev });
            if (ret === false) {
                return false;
            }
            if (this._isNonEditable(ev)) {
                return false;
            }
            //Don't set current event for mouseup.
            //It get's fired after a menu is closed and gives up a bogus event to work with
            //this._setCurrentEvent(ev);
            var self = this;
            if (this.browser.opera) {
                /*
                * @knownissue Opera appears to stop the MouseDown, Click and DoubleClick events on an image inside of a document with designMode on..
                * @browser Opera
                * @description This work around traps the MouseUp event and sets a timer to check if another MouseUp event fires in so many seconds. If another event is fired, they we internally fire the DoubleClick event.
                */
                var sel = Event.getTarget(ev);
                if (this._isElement(sel, 'img')) {
                    this.nodeChange();
                    if (this.operaEvent) {
                        clearTimeout(this.operaEvent);
                        this.operaEvent = null;
                        this._handleDoubleClick(ev);
                    } else {
                        this.operaEvent = window.setTimeout(function() {
                            self.operaEvent = false;
                        }, 700);
                    }
                }
            }
            //This will stop Safari from selecting the entire document if you select all the text in the editor
            if (this.browser.webkit || this.browser.opera) {
                if (this.browser.webkit) {
                    Event.stopEvent(ev);
                }
            }
            this.nodeChange();
            this.fireEvent('editorMouseUp', { type: 'editorMouseUp', target: this, ev: ev });
        },
        /**
        * @private
        * @method _handleMouseDown
        * @param {Event} ev The event we are working on.
        * @description Handles all mousedown events inside the iFrame document.
        */
        _handleMouseDown: function(ev) {
            var ret = this.fireEvent('beforeEditorMouseDown', { type: 'beforeEditorMouseDown', target: this, ev: ev });
            if (ret === false) {
                return false;
            }
            if (this._isNonEditable(ev)) {
                return false;
            }
            this._setCurrentEvent(ev);
            var sel = Event.getTarget(ev);
            if (this.browser.webkit && this._hasSelection()) {
                var _sel = this._getSelection();
                if (!this.browser.webkit3) {
                    _sel.collapse(true);
                } else {
                    _sel.collapseToStart();
                }
            }
            if (this.browser.webkit && this._lastImage) {
                Dom.removeClass(this._lastImage, 'selected');
                this._lastImage = null;
            }
            if (this._isElement(sel, 'img') || this._isElement(sel, 'a')) {
                if (this.browser.webkit) {
                    Event.stopEvent(ev);
                    if (this._isElement(sel, 'img')) {
                        Dom.addClass(sel, 'selected');
                        this._lastImage = sel;
                    }
                }
                if (this.currentWindow) {
                    this.closeWindow();
                }
                this.nodeChange();
            }
            this.fireEvent('editorMouseDown', { type: 'editorMouseDown', target: this, ev: ev });
        },
        /**
        * @private
        * @method _handleDoubleClick
        * @param {Event} ev The event we are working on.
        * @description Handles all doubleclick events inside the iFrame document.
        */
        _handleDoubleClick: function(ev) {
            var ret = this.fireEvent('beforeEditorDoubleClick', { type: 'beforeEditorDoubleClick', target: this, ev: ev });
            if (ret === false) {
                return false;
            }
            if (this._isNonEditable(ev)) {
                return false;
            }
            this._setCurrentEvent(ev);
            var sel = Event.getTarget(ev);
            if (this._isElement(sel, 'img')) {
                this.currentElement[0] = sel;
                this.toolbar.fireEvent('insertimageClick', { type: 'insertimageClick', target: this.toolbar });
                this.fireEvent('afterExecCommand', { type: 'afterExecCommand', target: this });
            } else if (this._hasParent(sel, 'a')) { //Handle elements inside an a
                this.currentElement[0] = this._hasParent(sel, 'a');
                this.toolbar.fireEvent('createlinkClick', { type: 'createlinkClick', target: this.toolbar });
                this.fireEvent('afterExecCommand', { type: 'afterExecCommand', target: this });
            }
            this.nodeChange();
            this.fireEvent('editorDoubleClick', { type: 'editorDoubleClick', target: this, ev: ev });
        },
        /**
        * @private
        * @method _handleKeyUp
        * @param {Event} ev The event we are working on.
        * @description Handles all keyup events inside the iFrame document.
        */
        _handleKeyUp: function(ev) {
            var ret = this.fireEvent('beforeEditorKeyUp', { type: 'beforeEditorKeyUp', target: this, ev: ev });
            if (ret === false) {
                return false;
            }
            if (this._isNonEditable(ev)) {
                return false;
            }
            this._storeUndo();
            this._setCurrentEvent(ev);
            switch (ev.keyCode) {
                case this._keyMap.SELECT_ALL.key:
                    if (this._checkKey(this._keyMap.SELECT_ALL, ev)) {
                        this.nodeChange();
                    }
                    break;
                case 32: //Space Bar
                case 35: //End
                case 36: //Home
                case 37: //Left Arrow
                case 38: //Up Arrow
                case 39: //Right Arrow
                case 40: //Down Arrow
                case 46: //Forward Delete
                case 8: //Delete
                case this._keyMap.CLOSE_WINDOW.key: //W key if window is open
                    if ((ev.keyCode == this._keyMap.CLOSE_WINDOW.key) && this.currentWindow) {
                        if (this._checkKey(this._keyMap.CLOSE_WINDOW, ev)) {
                            this.closeWindow();
                        }
                    } else {
                        if (!this.browser.ie) {
                            if (this._nodeChangeTimer) {
                                clearTimeout(this._nodeChangeTimer);
                            }
                            var self = this;
                            this._nodeChangeTimer = setTimeout(function() {
                                self._nodeChangeTimer = null;
                                self.nodeChange.call(self);
                            }, 100);
                        } else {
                            this.nodeChange();
                        }
                        this.editorDirty = true;
                    }
                    break;
            }
            this.fireEvent('editorKeyUp', { type: 'editorKeyUp', target: this, ev: ev });
        },
        /**
        * @private
        * @method _handleKeyPress
        * @param {Event} ev The event we are working on.
        * @description Handles all keypress events inside the iFrame document.
        */
        _handleKeyPress: function(ev) {
            var ret = this.fireEvent('beforeEditorKeyPress', { type: 'beforeEditorKeyPress', target: this, ev: ev });
            if (ret === false) {
                return false;
            }

            if (this.get('allowNoEdit')) {
                //if (ev && ev.keyCode && ((ev.keyCode == 46) || ev.keyCode == 63272)) {
                if (ev && ev.keyCode && (ev.keyCode == 63272)) {
                    //Forward delete key
                    Event.stopEvent(ev);
                }
            }
            if (this._isNonEditable(ev)) {
                return false;
            }
            this._setCurrentEvent(ev);
            this._storeUndo();
            if (this.browser.opera) {
                if (ev.keyCode === 13) {
                    var tar = this._getSelectedElement();
                    if (!this._isElement(tar, 'li')) {
                        this.execCommand('inserthtml', '<br>');
                        Event.stopEvent(ev);
                    }
                }
            }
            if (this.browser.webkit) {
                if (!this.browser.webkit3) {
                    if (ev.keyCode && (ev.keyCode == 122) && (ev.metaKey)) {
                        //This is CMD + z (for undo)
                        if (this._hasParent(this._getSelectedElement(), 'li')) {
                            Event.stopEvent(ev);
                        }
                    }
                }
                this._listFix(ev);
            }
            this._fixListDupIds();
            this.fireEvent('editorKeyPress', { type: 'editorKeyPress', target: this, ev: ev });
        },
        /**
        * @private
        * @method _handleKeyDown
        * @param {Event} ev The event we are working on.
        * @description Handles all keydown events inside the iFrame document.
        */
        _handleKeyDown: function(ev) {
            var ret = this.fireEvent('beforeEditorKeyDown', { type: 'beforeEditorKeyDown', target: this, ev: ev });
            if (ret === false) {
                return false;
            }
            var tar = null, _range = null;
            if (this._isNonEditable(ev)) {
                return false;
            }
            this._setCurrentEvent(ev);
            if (this.currentWindow) {
                this.closeWindow();
            }
            if (this.currentWindow) {
                this.closeWindow();
            }
            var doExec = false,
                action = null,
                value = null,
                exec = false;


            switch (ev.keyCode) {
                case this._keyMap.FOCUS_TOOLBAR.key:
                    if (this._checkKey(this._keyMap.FOCUS_TOOLBAR, ev)) {
                        var h = this.toolbar.getElementsByTagName('h2')[0];
                        if (h && h.firstChild) {
                            h.firstChild.focus();
                        }
                    } else if (this._checkKey(this._keyMap.FOCUS_AFTER, ev)) {
                        //Focus After Element - Esc
                        this.afterElement.focus();
                    }
                    Event.stopEvent(ev);
                    doExec = false;
                    break;
                //case 76: //L
                case this._keyMap.CREATE_LINK.key: //L
                    if (this._hasSelection()) {
                        if (this._checkKey(this._keyMap.CREATE_LINK, ev)) {
                            var makeLink = true;
                            if (this.get('limitCommands')) {
                                if (!this.toolbar.getButtonByValue('createlink')) {
                                    makeLink = false;
                                }
                            }
                            if (makeLink) {
                                this.execCommand('createlink', '');
                                this.toolbar.fireEvent('createlinkClick', { type: 'createlinkClick', target: this.toolbar });
                                this.fireEvent('afterExecCommand', { type: 'afterExecCommand', target: this });
                                doExec = false;
                            }
                        }
                    }
                    rget: this });
                   _/DownZ             case this._keyMap.CREAUNDO:
                     this._keyMap.CREAREDO:
                        this._checkKey(this._keyMap.CREAREDO)) {
                            on = null'redo                           ec = fals;
                    } else {
  this._checkKey(this._keyMap.CREAUNDO)) {
                            on = null')
                             ec = fals;
                    } els                 rget: this });
                   6/L
  B                  this._keyMap.CREABOLD:
                        this._checkKey(this._keyMap.CREABOLD)) {
                            on = null'bold                           ec = fals;
                    } els                 rget: this });
             this._keyMap.FOCUS_NT_SIZE_UP:
                     this._keyMap.CREAS_NT_SIZE_DOWN:
                        makeufalse;
  , dfalse;
                        this._checkKey(this._keyMap.FOCUS_NT_SIZE_UP)) {
                            utrue;
                                          if (this._isElkKey(this._keyMap.FOCUS_NT_SIZE_DOWN)) {
                            dtrue;
                                          if (thisutr|| dt                        var h = fs_ons fuhis.toolbar.getElemonByValue('creafoyTaize                EXTRRRRRRRRRRRRRlab EveneInt(pathfs_ons fu('limitab E{
  10               EXTRRRRRRRRRRRRRnewV = ((thilab Ev+ i >                  if (if (thisdt                        var RRRRnewV = ((thilab Ev; i >                     }
                             on = null'foyTaize >                     }
  e = null,ewV = ((+ 'px                           ec = fals;
                    } els                 rget: this });
                   73L
  I                  this._keyMap.CREAITALIC:
                        this._checkKey(this._keyMap.CREAITALIC)) {
                            on = null'le(',                            ec = fals;
                    } els                 rget: this });
                   8/End
U             case this._keyMap.CREAUNDER, eE:
                        this._checkKey(this._keyMap.CREAUNDER, eE)) {
                            on = null')
 erClie >                     }
  ec = fals;
                    } els                 rget: this });
             this9                      this._cheser.ie) {
                              Ithtmlimerabthe Inallyet Explore              caseeeeeeeee_e = this._getRange();
                if (((((((((= this._getSelectedElement();
                    if (    thiss._isElement(tar, 'li')) {
                        this    this_e) {
                    retucaseeeeeeeee_e = t.pasMLDocu('&nbsp;&nbsp;&nbsp;&nbsp;                                this_e.collapsed) {se);
                    } el        this_e.collcted')                    if (                                  if (t.stopEvent(ev);
                        }
                    }
                }
      //fox 2 fr3 c                        this._cheser.ie) o) {
0) {.8                        this. this._getSelectedElement();
                    if (    this._isElement(tar, 'li')) {
                        this    thisev.shift) {
                               this.execDoc().execCommand('enaboutdtararg, _ran
                                se {
                            thisthis.execDoc().execCommand('enabxOf(ararg, _ran
                                s                                                  }
   e {
  thiss._hasSelection() &&                             this.closCommand('inserthtml', '<br>&nbsp;&nbsp;&nbsp;&nbsp;                            s                         t.stopEvent(ev);
                    }
                }
      : this });
             this13                      makepull, _ran0; i <                     this._che'limip[i])& (pat!ev.shift) {
                           this._cheser.ie) o) {
                            this. this._getSelectedElement();
                    if (        thiss._hasSelent(this.'li')) {
                        this        this._cheSelent(this.'li') {
                caseeeeeeeeeeeeeeeeeeeeephis._getDoc().getEtelinkent();
 ) {
                  } el        thiseeeeperHTML = str;>&nbsp;                           });








addCrthtmlr Ele(prget:
                  } el        thiseeee._setCuredEl, 'a(pstChild) {
                               }
   e {
  this._checement(tar, 'li')')) {
                    brea        thiseeee._setCommand('inserthtmlm {Egrapharg, _r
                  } el        thiseeeemakepthis._getDoc().getE.getElementsByTagName(v);
) {
                  } el        thiseeee(var 0; i < path.ngth; i++) {
                                    els.....pathSsgetAttribute('href_moz_d = t= -1)ll) {
                if (((((((((((((((((((((((((((((phis._getDoc().getEtelinkent();
 ) {
                  } el        thiseeee((((((((perHTML = str;>&nbsp;                           });
















addCrthtmlr Ele(prgp);
                                }
              ._setCuredEl, 'a(pstChild) {
                               }
  eeee((((((((psgetAveClasibute('href_moz_d = t=                               }
  eeee((((                              }
  ((((                              }
  se {
                            thisthisssssec = fals;
                    } elllllllllllllllllon = null'lthtmlm {Egrapha                              }
                            }
      t.stopEvent(ev);
                        }
  }
                                                  if (sel..browser.webkit) {
                if (((((((((((((. this._getSelectedElement();
                    if (        thiss._hasSelent(this.'li')) {
                        this        ._setCommand('inserthtmlm {Egrapharg, _r
                  } el        thisdoExeivthis._getDoc().getE.getElementsByTagName(v);
)eiv=                               }
  (var 0; i < patheivtgth; i++) {
                                    els.!Dom.hasClass(patheivt 'yui-tag'wk-eiv=                 if (((((((((((((((((((((((((addClass(sel,eivt 'yui-tag'wk- {
                  } el        thiseeee                              }
                            }
      t.stopEvent(ev);
                        }
  }
                                                  se {
                        if (!thi.browser.webkit) {
                if (((((((((((((. this._getSelectedElement();
                    if (        thiss._hasSelent(this.'li')) {
                        this        !thi.browser.webkit) {
4                    brea        thiseeee._setCommand('inserthtmlClie: thi{
                  } el        thisse {
                            thisthisssss.execCommand('inserthtml', '<br>'i = (d="tag'br"></i =
                        Evennnnnnnnnnnnnh = tolderhis._getDoc().getElementsByTByIdi') !-) {
              EXTRRRRRRRRRRRRRRRRRRRRRRRRRbrhis._getDoc().getEtelinkent();
 )) {
              EXTRRRRRRRRRRRRRRRRRRRRRRRRRca= this.fireDoc().getEtelinkent();
 )') {
 >                  if (if (((((((((((((tolderentNode.removee('htd(els[bli'tolder                       Evennnnnnnnnnnnnca= tssName = 'yui-non';
                            });
nnnnnnnnca= tsrHTML = str;>&nbsp;                           });








addCrthtmlr Ele(ca= t,Rbr
                  } el        thiseeee._setCuredEl, 'a(ca= t                               }
                            }
      t.stopEvent(ev);
                        }
  }
                                                  if (sel..browser.webk{
                            if (  Ithtmlime');
ide teada docu<p></p>the Inallyet Explore              caseeeeeeeeeeeee_e = this._getRange();
                if (((((((((((((. this._getSelectedElement();
                    if (        thiss._hasSement(tar, 'li')) {
                        this        this_e) {
                    retucaseeeeeeeeeeeee_e = t.pasMLDocu('');
                        Eveneeeeeeeeeeee_e = t.apsed) {se);
                    } el        thiseeee_e = t.cted')                    if (                                      }
      t.stopEvent(ev);
                        }
  }
                                                                    break;
            }
            thissel..browser.webk{
                    ._listFix(ev);
            }
            thisdomPat = fa&&lon = n                    ._lisCommand('inson = n, e = n                   t.stopEvent(ev);
                    .nodeChange();
                          this._storeUndo();
            this._setEvent('editorKeyPres', { type: 'editorMousown', target: this, ev: ev });
                   /**
        * @private
        * @metherty
    ListDupIRug of      * @meth: 'elean
        */
  scription HandKeeps mohat boce..e ListDupIds();
m selerug of the begis= 'yr to      */
        _fixNodeDupIRug of :l,
                    * @private
        * @method _fixNodeDupIds();
     */
  scription HandSo 'yser.webll stopduplicthe DOM ida docn LI wretutelinkddocumnMode on.      * @des is Cod will alsoode DOM duplicthe i calsThe Howse()does also happs everve DOM tChilment has      * @deshe editment if yFix(h desieditunique i .      */
        _fixNodeDupIds();
nction() {
            try this._checodeDupIRug of                 return false;
            }
            if (this._isNooc()) {
                    ._checodeDupIRug of als;
                    h = Fixhis._getDoc().getE.getElementsByTagName(v);
)) {
              EXTRRRRR0; i , i xhis{}                  (var 0; i < pathFixgth; i++) {
                        thisFixid) ? '#                      if (!thii x[Fixid) ? ]                    retucaseeeeeFixid) ? ';
                        }
                        pathi x[Fixid) ? ]als;
                    } els                               this._listodeDupIRug of alse;
            }
                   /**
        * @private
        * @method _handFix(ev)     * @priva {Event} ev The event we are working on.
        * @description Handles all Editonalldown, TabdKey setsShift + Tabdowns (varDupI Items      */
        _fixNFix(ev)nction(ev) {
            var ret testLiull, _ranp null, _rans eents) {alse;
  , e = null;
            if (d
  alldK               this.browser.webkit) {
                if (!thieyCode && (ev.keyCode == 122) 3
                    if (Dom.._hasParent(this._getSelectedElement(), 'li')) {
                            tar = this._getSarent(this._getSelectedElement(), 'li')) {
                  if (    this.arenreviousSiblof                 retu    if (    this.aretChild) {
(ev.k.aretChild) {
gth === 0) {
                    domPPPPPPPPPPPPP._setCuredEl, 'a(et:
                  } el                              }
                    }
                }
                          //ThisShift + TabdK               thiseyCode && ((ev.ks.browser.webkit3) {
(ev.keyCode == 122)25) (class.browser.webkit) {
3(clas.browser.webkit3) {)((ev.keyCode == 46) 9)ev.keyCshift) {
)
                    .estLiull._getSelectedElement();
                    Dom.._hasParent(this.estLii')) {
                        .estLiull._getSarent(this.estLii')) {
<                     this._cheSarent(this.estLii')ul| this._isElarent(this.estLii')o {
                    el =====p this._getSarent(this.estLii')ul|                   if (    thissp t                    domPPPPPPPPPp this._getSarent(this.estLii')ol|                   if (                          if (sel..browSement(tar,parenreviousSiblof i')) {
                        this    pareveChild(els[.estLi
                  } el        parentNode, 'aCrthtmlBeEdits.estLii'parenextSiblof                    if (        this.browser.webk{
                            if (((((e = this._getRang).getE.getEtelinkTexte();
                if (((((((((((((((((e = t.ChilToent(tarText[.estLi
                  } el        ((((e = t.apsed) {se);
                    } el        thise.collcted')                    if (                                  if (!thi.browser.webkit) {
                if (((((((((((((((((._setCuredEl, 'a(eestListChild) {
                                                         if (t.stopEvent(ev);
                        }
                    }
                }
            }
            //This abdK               thiseyCode && ((ev.keyCode == 46) 9)ev.k(!eyCshift) {
)
               var _selnreLiull._getSelectedElement();
                    Dom.._hasParent(thisnreLii')) {
                        s eents) {als._hasParent(thisnreLii')) {
srHTML = s                                if (this.currser.webkit) {
                if (((((.execDoc().execCommand('enabxOhtml in alse, 'true\t                }
                if (.estLiull._getSelectedElement();
                    Dom.._hasParent(this.estLii')) {
                        p this._getSarent(this.estLii')) {
<                     _selnewUlhis.fireDoc().getEtelinkent();
 parentNode, 'aCame.toLowerCase()].ke
<                     this._cheser.webkit) {
                if (((((((((self titl=
addClementsByTagNCName = '('A-styltab-', funcn', funcpt:
                  } el    //Re invabegistitlent has thatari frompunside                     if (sel.stit[0]                    retucaseeeeepareveChild(els[stit[0]                   } el        parerHTML = str;.isObtrim(parerHTML = s                   } el        //Pue begi = st selebegiLIernaos });
newiLI                         if (!this eents) {                if (((((((((((((((((parerHTML = str;an titlsName="tag'non+ pathS eents) {a/span>';
 &nbsp;                           });
se {
                            thisthisparerHTML = str;an titlsName="tag'non+ &nbsp;an>';
 &nbsp;                           });
s                     }
                    }
   e {
                        if (!this eents) {                if (((((((((((((parerHTML = str;S eents) {a/sp&nbsp;                           se {
                            thisparerHTML = str;a&nbsp;                           s                 }
                         p tentNode.removee('htd(els[newUlncpt:
                  } elnewUl.n oncdd(els[pt:
                  } elthis._cheser.webkit) {
                if (((((((((._getSelectedEl) {
 TimeB)].AndExt);
 paretChild) {
, 1i'paretChild) {
, paretChild) {
erHTMLTextgth) {
                   if (    thiss.browser.webkit) {
3                if (((((((((((((parentNode.remontNode.remoe.widtdise('true'Fix(-item                           });
imeout(function() {
                                selfparentNode.remontNode.remoe.widtdise('true'block                           });
s, i >                     }
                    }
   e {
  this._cheser.webk{
                            e = this._getRang).getE.getEtelinkTexte();
                if (((((((((e = t.ChilToent(tarText[pt:
                  } el    e = t.apsed) {se);
                    } el    e.collcted')                    if (se {
                        this.operCuredEl, 'a(pt:
                  } el                  Event.stopEvent(ev);
                                  if (this.currser.webkit) {
                if (((((t.stopEvent(ev);
                                  if (.nodeChange();
                                 /**
        * @privod _hanChange();
     * @priva {Evenean
   } (vacera () {al a {Eve alldtop kie Mousthrestold cou all     * @description Handles all ime the ue Moustar basedns fus,'s f the entieven[i],,oodethe Chans      */
        _fixChange();
nction(ev) (vace            var ret NC = this;
                ._storeUndo();
            thisthis._che'limiChange();
De('t{
                this.curr_Change();
De('tr = nullow.setTimeout(function() {
                        NC = tr_Change();
De('tr = null;
                        NC = tr_Change();
.n oly(NC = t, argt if s                    ,                 se {
                this.node_Change();
                                 /**
        * @private
        * @method _handChange();
     * @priva {Evenean
   } (vacera () {al a {Eve alldtop kie Mousthrestold cou all     * @description HandF afte seleChange fromin a
imeout(fun      */
        _fixNChange();
nction(ev) (vace            var ret threstold eneInt(path._che'limiChange();
Threstold{
  10               EXTR._chNhange from= Mlengd tra[new D   etElemout(
  /);
 0               EXTR = this;
             thisthis(vacertrue) {
                    ._listFastNhange from= 0                                     thisthis(._listFastNhange from+ threstold) <R._chNhange fro                if (this.browtodeNhansr = nul)ll) {
                if (((((.browtodeNhansr = nuldow.setTimeout(function() {
                             = tr_odeNhansl(self);
                             = tr_odeNhansr = null;
                         ,                                             this._storFastNhange from= ._chNhange fro          thisthis._cheentEvent = e                    .
                        ._storFastNhange frot = fals._cheentEvent = e.: 'e                   ch (e) {}
                               ret reEditNhange from= ._cheEvent('beforeEditNhange fro type: 'beforeEditNhange fro tyet: this });
                thisreEditNhange from=alse) {
                return false;
            }
            if (this._isN'disabom[i],{
                thisow.setTimeout(function() {
                         = tr_writeath(el)l(self);
                     ,                 s         //ThisC if atop e  thire workbled', f reEdit con thuof      * @mif (this!._isN'disabled') ===                if (this.browSTOP_NODE_CHANGE                    //ForwRecurr });
h = foelnextlon = n                     ._stoSTOP_NODE_CHANGEalse;
                        rn false;
            }
      se {
                        sel = Even._getSelectedEl) {
               EXTRRRRRRRRRe = this._getRange();
                EXTRRRRRRRRR Even._getSelectedElement(), 'li             EXTRRRRRRRRRfn_ons fuhis.toolbar.getElemonByValue('creafoyTnEve{
              EXTRRRRRRRRRfs_ons fuhis.toolbar.getElemonByValue('creafoyTaize                EXTRRRRRRRRR)
  _ons fuhis.toolbar.getElemonByValue('crea)
                  EXTRRRRRRRRRee  _ons fuhis.toolbar.getElemonByValue('crearedo  >                  if (ndle elemupda the entitar base desion =veedns fus                     sel _exhis{}                      this.browtFastonByVa                            _ex[.browtFastonByVa ? ]als;
                    } elif (nd.browtFastonByVaull;
                                              thiss._hasSement(tar,'a');')) {
                    brea    this(n_ons fu                                _ex[(n_ons fuN'disai===]als;
                    } elif (                      if (sel.fs_ons fu                                _ex[(s_ons fuN'disai===]als;
                    } elif (                                            thisee  _ons fu                            de key
_ex[ee  _ons fuN'disai===]                                            .toolbar.getE evetAllonByVas(_ex >                  if (ndle elembled', f rns fus                     (var doExe; i < d <R._ch._bled', fgth; i++)d{
                            sel _ons fuhis.toolbar.getElemonByValue('cre._ch._bled', f[d]                   } el    this_ons fuh&& _ons fuN'di                            if (this._nodeFastonByVauv.k(_ons fuN'disai===rtrue)browtFastonByVa ? 
                    domPPPPPPPPPPPPP//Skie                         });
se {
                            thisthisthiss._hasSelection() && (pat!._isN'disaxOhtml=                 if (((((((((((((((((((((ch (ev.k._ch._bled', f[d]                if ((((((((((((((((((((((((( thisafoyTnEve{                      (((((((((((((((((((( thisafoyTaize                       ((((((((((((((((((((((((: this });
                                    default                      ((((((((((((((((((((((((//No ction() & -mbled',                      ((((((((((((((((((((((((.toolbar.getEbled', onByVa(_ons fu
                  } el        thiseeee                              }
   e {
                            thisthisssssthiss._hasSalwaysDled', f[._ch._bled', f[d]]                if (((((((((((((((((((((((((.toolbar.getEend', onByVa(_ons fu
                  } el        thiseeee                              }
                            thisthisthiss._hasSalwaysEnd', f[._ch._bled', f[d]]                if (((((((((((((((((((((.toolbar.getEbeuredElonByVa(_ons fu
                  } el        this                          }
                                                                        _selnathhis._getRang).h(el)                    if (tar = gull, _rancmdull;
                        (var doEx0; i < path.el)lth; i++) {
                            = gull.el)id) ame.toLowerCase()].ke                  } el    this.el)id) ttribute('hrefame=                 if (((((((((((((= gull.el)id) ttribute('hrefame= owerCase()].ke                  } el                              cmdull._getRame2cmd[ame                       if (h &&cmdulrue)
 efine
                            h.ficmdull[                       if (                      if (sel.!.isObisArray&cmd                 if (((((((((((((cmdull[cmd                       if (                   } elif (ndBold setsIe(',  e.wids                     if (sel..el)id) e.widtfoyTWe ArrowerCase()].ke46) 'bold                 if (((((((((((((cmd[cmdlth; i+]ull'bold                                                 if (sel..el)id) e.widtfoyTS.widtwerCase()].ke46) 'le(',                  if (((((((((((((cmd[cmdlth; i+]ull'le(',                                                  if (sel..el)id) e.widt in Decora() &twerCase()].ke46) ')
 erClie                 if (((((((((((((cmd[cmdlth; i+]ull')
 erClie >                     }
                        if (sel..el)id) e.widt in Decora() &twerCase()].ke46) 'Clie-through                 if (((((((((((((cmd[cmdlth; i+]ull'sutekethrough >                     }
                        if (sel.cmdlth; i+ > 0                if ((((((((((((((var doExj; i < jathcmdlth; i+< j{
                                    .toolbar.getEuredElonByVa(cmd[j                                }
  .toolbar.getEend', onByVa(cmd[j                                                                                if (ndle elemAlign(tar                          h (ev.k.el)id) e.widt in AlignowerCase()].ke
               if (((((((((((((cthisaleft                       ((((((((cthisar Arr                       ((((((((cthisace all                       ((((((((cthisajustify                       ((((((((((((doExalignT 'elll.el)id) e.widt in AlignowerCase()].ke                              }
  sel..el)id) e.widt in AlignowerCase()].ke46) 'justify                 if (((((((((((((((((((((alignT 'elll'f
  a                              }
                            }
      .toolbar.getEuredElonByVa('justify  +(alignT 'e                               }
  .toolbar.getEend', onByVa('justify  +(alignT 'e                               }
  : this });
                                      }
                }
      //r Elem(varloop                  if (ndRecurrFoyT Family setsSize aos }ominle(' configs                     this(n_ons fu                            h = family =Rfn_ons fu._configs.lab E._inleialConfig.e = ns });
                    fn_ons fu.slimitab E{,;an titlsName="tag'bar.get-foyTnEve-'m+ thgetRrTimnCName = '(family)a/sp+ pathfamily /span>';
                         this._lastupda eMenuC if edeafoyTnEve{,hfamily
                  } el                       this(s_ons fu                            (s_ons fuNslimitab E{,;(s_ons fuN_configs.lab E._inleialConfig.e = n
                  } el                       h = td_ons fuhis.toolbar.getElemonByValue('creaheading{
<                     thistd_ons fu                            td_ons fuNslimitab E{,;td_ons fuN_configs.lab E._inleialConfig.e = n
                  } elllll._lastupda eMenuC if edeaheading{,;anoie                                              doEx0mg_ons fuhis.toolbar.getElemonByValue('creartimageClic{
<                     this0mg_ons fuhhis.currentWindow) {
(ev.k.currentWindow) {
.n= 'yu= artimageClic{
                if (((((((((._getbar.getEbled', onByVa(0mg_ons fu                                             this._nodeFastonByVauv.k)browtFastonByVa ?sctedElem                if (((((((((._getbar.getEbeuredElonByVa()browtFastonByVa ? 
                                            .tool_)
  Nhange();
                                                 ._cheEvent('beforExecNhange fro type: 'beforExecNhange fro tyet: this });
                   /**
        * @private
        * @method _handupda eMenuC if ed     * @priva {EvenObjdEl} rns fu evencnd('en if(arifilemofs }omrns fu you waas to kKey(     * @priva {EvenSuteng} e = nuevene = nuofs }om(tau item you waas to kKey(     * @priva {Even<a href="YAHOO.wid th.Tar.getE', '">YAHOO.wid th.Tar.get</a>}uevenTar.getide tances }omrns fu b Eongs to (defaults aos });tbar.get)      * @description HandGetss }om(tau  seleamrns fu de tance, thi }om(tau is not Win erkddoes alsoWin erdoe.sIes alsotretusearchs }om(tau  orabegistecifildne = n, unkKey(of thlso begr items setskKey(of tbegistecifildn        * @de      _fixNupda eMenuC if ednction(ev) rns fu,ne = n, tb t                thiss.b t                    .b this._getbar.get              s         //Thsel _ons fuhis.getElemonByValue('creons fu               _ons fuNkKey(e('cree = n
                 /**
        * @private
        * @method _handleKeyDTar.getk', t     * @priva {Event} ev The event we athatauteggerkdd }omrns fu c', t     * @privription Hand is Cis sent we aleKeyDExattakKed aos }omTar.get'smrns fuk', tnt we .sIes alsoEven Command('enh desieditcnd('en if(arifilem selebegiTar.getionByVa      * @de      _fixNleKeyDTar.getk', tnction(ev) {
            var ret e = null                sel =trull                sel cmdullev.ons fuNe = ns });
        thiseyCons fuN(taucmd                var _s= nullcmd                  cmdullev.ons fuN(taucmd              s         //Th.browtFastonByVaullev.ons fu          thisthis._cheSTOP_EXEC_COMMAND                    ._lisSTOP_EXEC_COMMANDalse;
                    rn false;
            }
   e {
                this.nodeCommand('enacmd, e = n                   thiss.browser.webkit) {
                if ((((((sel F = this;
                         imeout(function() {
                             F = tus();
l(selfF);
                         }, 5                                            thist.stopEvent(ev);
                   /**
        * @private
        * @method _handimeupAElement.foc     * @privription HandCelinkss }omaccessibility h2 headDExaen e('htsdoesrExecs }omif docuhe editevenfoelnaviga() &t     * @de      _fixNimeupAElement.focnction() {
            try thiss.browseEditont.foc                    ._lisseEditont.fochisment.
   telinkent();
 )[0];                  ._lisseEditont.focssName = 'yui-non'orMous- kieheadDEa                  ._lisseEditont.focstabIn exhis'-1a                  ._lisseEditont.focsrHTML = str;._lisSTR_BEFORE_EDITOR                  ._lis'disaent has_cont')s'disatChild) {
{
srHhtmlBeEdits._lisseEditont.foc,s.toolbar.getElem('nextSiblof 'e
<                       if (thiss.browrElement.foc                    ._lisrElement.fochisment.
   telinkent();
 )[0];                  ._lisrElement.focusName = 'yui-non'orMous- kieheadDEa                  ._lisrElement.focutabIn exhis'-1a                  ._lisrElement.focurHTML = str;._lisSTR_LEAVE_EDITOR                  ._lis'disaent has_cont')s'disatChild) {
{
sn oncdd(els[.browrElement.foc <                              /**
        * @private
        * @method _handbled', orKeyD     * @priva {Evenean
   } bled', f Pames;
   aosbled', lse, 't aosend',      * @privription HandCelinkssa mas atope('ht ovecs }omorKeyDt     * @de      _fixNbled', orKeyDnction() {
bled', f            var ret if doc, par,;t, ',;te Arr          thisthis!._isN'disabled') =_if doc==                if (thr= 'yuithgetRrelinkIhr= '                    Dor= 'Nslimiitargabled') =_'m+ thget'disaxf doc==N'disai===                   Dor= 'NsliS.wideahe Arr rga100%];                  Dor= 'NsliS.wideadise('t{,;anoie                    Dor= 'NsliS.wideavisibility{,;avisible                    thgetsdisabled') =_if doc=, Dor= '                   p this._get'disaxf doc==N'disantNode.rem                    paren oncdd(els[Dor= 'N'disaent has'e
<                       if (thissDor= '                if (thr= 'yuithget'disabled') =_if doc==<                       if (thisbled', f            var     thget_orgIhr= 'yuithget'disaif doc==<                  this });tbar.get)                       .toolbar.getEurisabled') ==,e) {
                                      t, 'yuithget'diorKeyDDocu(                   he Arrhis._get'disaxf doc==N'disaoffuriHe Arr                    Dor= 'NsliS.wideavisibility{,;a                    Dor= 'NsliS.wideaposi() {{,;a                    Dor= 'NsliS.wideaven{,;a                    Dor= 'NsliS.widealeft ,;a                    thget_orgIhr= 'NsliS.wideavisibility{,;ahidf(a                    thget_orgIhr= 'NsliS.wideaposi() {{,;aabsolute                    thget_orgIhr= 'NsliS.wideaven{,;a-99999px                    thget_orgIhr= 'NsliS.widealeft ,;a-99999px                    thgetsdisaxf doc=, Dor= '                   .operCurtInleialCont);
 ) {
                                    thiss.brow_mas                 if (((((.browtmas aisment.
   telinkent();
 )DIV{
<                     addClass(sel,.browtmas ,i-non'orMous-mas  ===<                     this._cheser.webk{
                            .browtmas  e.widthe Arrhishe Arrh+ 'px                                             .tool'disaxf doc==N'disantNode.rem  sn oncdd(els[.brow_mas                                      .toolVa('orMousCont);
ReloadD==,etion() {
                        ._getRang).getE.getE_rteLoadD=alse;
                        thgetsdiorKeyDDocu(t, '=<                     thr= 'NsliS.wideadise('t{,;ablock =<                     thgetunsubptiobeAelf'orMousCont);
ReloadD==                                   se {
                thisthis._nodemas                 if (((((.browtmas entNode.removeChild(els[.brow_mas                    ((((.browtmas ull;
                        this });tbar.get)                       ((((.browbar.getEurisabled') ==,ee);
                    } el                      thr= 'NsliS.wideavisibility{,;ahidf(a                        thr= 'NsliS.wideaposi() {{,;aabsolute                        thr= 'NsliS.wideaven{,;a-99999px                        thr= 'NsliS.widealeft ,;a-99999px                    ((((.brow_orgIhr= 'NsliS.wideavisibility{,;a                    ((((.brow_orgIhr= 'NsliS.wideaposi() {{,;a                    ((((.brow_orgIhr= 'NsliS.wideaven{,;a                    ((((.brow_orgIhr= 'NsliS.widealeft ,;a                    ((((.browsdisaxf doc=, .brow_orgIhr= ' >                  if (._cheE();
                    }
  sel = Ethis;
                        ow.setTimeout(function() {
                             = trChange();
.(self);
                        s, i0                                                   /**
        * @privatty
    SEP_DOMPATH     * @privription Hand iene = nutope('ht iu b tweee editevennathhitems     * @priv: 'elSuteng     * @de      _fixSEP_DOMPATH: '<'      /**
        * @privatty
    STR_LEAVE_EDITOR     * @privription Hand ienaccessibility suteng  orabegient has rExecs }omiFr= '     * @priv: 'elSuteng     * @de      _fixSTR_LEAVE_EDITOR: 'You have lefts }omRich TextmorKeyDt'      /**
        * @privatty
    STR_BEFORE_EDITOR     * @privription Hand ienaccessibility suteng  orabegient has reEdit  }omiFr= '     * @priv: 'elSuteng     * @de      _fixSTR_BEFORE_EDITOR: ' is C in  field citlsontaiu e.wiized aextlond graphics. To cyclusthroughthlsoEdimaf the on Hans, ust  }omkeyboard shortcutsShift + Escapnutope('ht E();
 on entitar baseond naviga(e b tweee on Hansh desiyouseorer.downs. To exirr });
aextlorMous ust  }omEscapnukey setscon thueerabbthe. <h4>and(on Edimaf the keyboard shortcuts:</h4><ul><li>ControlsShift B ime;
aextltopbold</li> <li>ControlsShift I ime;
aextltople(', </li> <li>ControlsShift U )
 erClie;
aext</li> <li>ControlsShift L lass sen = stClic</li></ul>'      /**
        * @privatty
    STR_TITLE     * @privription Hand ienTitlnuofs }om = stment if ythata);
telinkddocu }omiFr= '     * @priv: 'elSuteng     * @de      _fixSTR_TITLE: 'Rich TextmAelit'      /**
        * @privatty
    STR_IMAGE_HERE     * @privription Hand ienaextltope('ht iu  }omURLnaextbox wretuusof tbegiblankeClic.     * @priv: 'elSuteng     * @de      _fixSTR_IMAGE_HERE: 'IClicmURLnHere'      /**
        * @privatty
    STR_IMAGE_URL     * @privription Hand ienlab Evsuteng  oraIClicmURL     * @priv: 'elSuteng     * @de      _fixSTR_IMAGE_URL: 'IClicmURL=,             /**
        * @privatty
    STR_LINK_URL     * @privription Hand ienlab Evsuteng  orabegiLlicmURL.     * @priv: 'elSuteng     * @de      _fixSTR_LINK_URL: 'LlicmURL'      /**
        * @privatttdElem     * @privatty
    STOP_EXEC_COMMAND     * @privription HandSetltop;
   wretuyou waas tditmefault Command('enhtion() {ltopnot attcess sey })f      * @meth: 'elean
        */
        _fixSTOP_EXEC_COMMAND:se, 'tr     /**
        * @privatttdElem     * @privatty
    STOP_NODE_CHANGE     * @privription HandSetltop;
   wretuyou waas tditmefault Change fromtion() {ltopnot attcess sey })f      * @meth: 'elean
        */
        _fixSTOP_NODE_CHANGE:se, 'tr     /**
        * @privatttdElem     * @privatty
    CLASS_NOEDIT     * @privription HandCSSlsName n olied aosent hassythataworknot orMod') .     * @priv: 'elSuteng     * @de      _fixCLASS_NOEDIT:i-non';
orMo'      /**
        * @privatttdElem     * @privatty
    CLASS_CONTAINER     * @privription HandDefault CSSlsName aosn oly aos }omorMousslsontaiuecsent.foc     * @priv: 'elSuteng     * @de      _fixCLASS_CONTAINER:i-non'orMous-sontaiuec'      /**
        * @privatttdElem     * @privatty
    CLASS_EDITABLE     * @privription HandDefault CSSlsName aosn oly aos }omorMousslthr= 'yent.foc     * @priv: 'elSuteng     * @de      _fixCLASS_EDITABLE:i-non'orMous-orMod') '      /**
        * @privatttdElem     * @privatty
    CLASS_EDITABLE_CONT     * @privription HandDefault CSSlsName aosn oly aos }omorMousslthr= ''s ntNodeyent.foc     * @priv: 'elSuteng     * @de      _fixCLASS_EDITABLE_CONT:i-non'orMous-orMod') -sontaiuec'      /**
        * @privatttdElem     * @privatty
    CLASS_PREFIX     * @privription HandDefault preode  oradynami(sely
telinkddsName n= 's     * @priv: 'elSuteng     * @de      _fixCLASS_PREFIX:i-non'orMous'      /**
         * @privatty
    ser.web     * @privription HandSt'enard ser.webtmeten = n         riv: 'elObjdEl     * @de      _fixser.webnction() {
            try ret rthisYAHOO.env.ua              isC if a orait) {
3             thisrbkit) {
 >= 420                if (bbkit) {
3(=(bbkit) {
              se {
                thisbbkit) {
3(=(0                            thisrbkit) {
 >= 530                if (bbkit) {
4(=(bbkit) {
              se {
                thisbbkit) {
4(=(0                            bbkmacalse;
                isC if a oraMac             thisnaviga(yDtustrAgfocurHdexOf('Macrnaosh= -1)ll-1                if (bbkmacals;
                               rn falsbt          }'li                  * @privod _haninle     * @privription Hand ienorKeyDdsName' inleializa() &Cod wil     * @de      _fixinlenction() {
p_oont.foc,sp_oibute('hrs                 thiss.brow_mefaultTar.get)                   .brow_mefaultTar.gethis{                     apsed) {:s;
                EXTRRRRRtitlnget: ' extmorKeeng Tar.s'              EXTRRRRRdraggd') :se, 'tr     /**












rns fus: [     /**
















{ group:safoyTa.wid=, lab E:saFoyT  = 'ysetsSize'              EXTRRRRR







rns fus: [     /**
























{ : 'befouredEl=, lab E:saArial', e = n: afoyTnEve{,hbled') =:s;
                EXTRRRRRRRRRRRRRRRRRRRRR(tau: [     /**
































{ :ext:saArial', c if ednc;
   }              EXTRRRRRRRRRRRRRRRRRRRRR



{ :ext:saArial B('hk' }              EXTRRRRRRRRRRRRRRRRRRRRR



{ :ext:saandic Sans MS' }              EXTRRRRRRRRRRRRRRRRRRRRR



{ :ext:saanurilemNew' }              EXTRRRRRRRRRRRRRRRRRRRRR



{ :ext:saLucida Cfusn
 ' }              EXTRRRRRRRRRRRRRRRRRRRRR



{ :ext:saTahoma' }              EXTRRRRRRRRRRRRRRRRRRRRR



{ :ext:saTi 'smNew Roman' }              EXTRRRRRRRRRRRRRRRRRRRRR



{ :ext:saTrebuc it MS' }              EXTRRRRRRRRRRRRRRRRRRRRR



{ :ext:saVerdana' }             EXTRRRRRRRRRRRRRRRRRRRRR]             EXTRRRRRRRRRRRRRRRRR}              EXTRRRRRRRRRRRRRRRRR{ : 'befoupin=, lab E:sa13', e = n: afoyTaize , e = n: [ 9, 75 ],hbled') =:s;
   }             EXTRRRRRRRRRRRRR]             EXTRRRRRRRRR}              EXTRRRRRRRRR{ : 'befoura {Eous'R}              EXTRRRRRRRRR{ group:sa:exta.wid=, lab E:saFoyT S.wid=,             EXTRRRRR







rns fus: [     /**
























{ : 'befopush=, lab E:saBold CTRL + SHIFT + B', e = n: abold R}              EXTRRRRRRRRRRRRRRRRR{ : 'befopush=, lab E:saIe(',  CTRL + SHIFT + I', e = n: ale(',  R}              EXTRRRRRRRRRRRRRRRRR{ : 'befopush=, lab E:saU
 erClie CTRL + SHIFT + U', e = n: a)
 erClie R}              EXTRRRRRRRRRRRRRRRRR{ : 'befopush=, lab E:saSuteked irough , e = n: asutekethrough R}              EXTRRRRRRRRRRRRRRRRR{ : 'befoura {Eous'R}              EXTRRRRRRRRRRRRRRRRR{ : 'befoapsus'  lab E:saFoyT Cpsus'  e = n: aforeapsus'  bled') =:s;
   }              EXTRRRRRRRRRRRRRRRRR{ : 'befoapsus'  lab E:saB'hkgd tra Cpsus'  e = n: ab'hkapsus'  bled') =:s;
   }             EXTRRRRRRRRRRRRRRRRR             EXTRRRRRRRRRRRRR]             EXTRRRRRRRRR}              EXTRRRRRRRRR{ : 'befoura {Eous'R}              EXTRRRRRRRRR{ group:sarHdentFix('  lab E:saDupIs'              EXTRRRRRRRRRRRRRrns fus: [     /**
























{ : 'befopush=, lab E:saCelink senUnor erkddLix('  e = n: alHhtmlunor erkdFix('R}              EXTRRRRRRRRRRRRRRRRR{ : 'befopush=, lab E:saCelink senOr erkddLix('  e = n: alHhtmlor erkdFix('R}             EXTRRRRRRRRRRRRR]             EXTRRRRRRRRR}              EXTRRRRRRRRR{ : 'befoura {Eous'R}              EXTRRRRRRRRR{ group:sarHimagetem , lab E:saIthtmliItem ,             EXTRRRRRRRRRRRRRrns fus: [     /**
























{ : 'befopush=, lab E:sa = stLlicmCTRL + SHIFT + L'  e = n: atelinkClic'  bled') =:s;
   }              EXTRRRRRRRRRRRRRRRRR{ : 'befopush=, lab E:saIthtmliIClic{  e = n: alHhtmleClic{R}             EXTRRRRRRRRRRRRR]             EXTRRRRRRRRR}             EXTRRRRR]             EXTR}                             YAHOO.wid th.Simp, orKeyD.suy
 sName.inle.(self.bro,sp_oont.foc,sp_oibute('hrs ;             YAHOO.wid th.orKeyDInfo._in tances[.tool'disax===]als;
                  ._cheentEventnt.fochis[               .toolVa('cont);
Ready=,etion() {
                    .toolDOMReadyals;
                    ._cheEvenQueu
                 , .bro,e) {
            }      /**
        * @privod _haninleibute('hrs     * @privription HandInleializkssalso fieditcnnfigura() &xattte('hrs ustd aostelink      * @pri }omorMous.     * @priva {EvenObjdEl} atttlObjdEl leteral stecifyof th imeo fi     * @pricnnfigura() &xattte('hrs ustd aostelink  }omorMous.     * @pr      _fixinleibute('hrsnction() {
attt                YAHOO.wid th.Simp, orKeyD.suy
 sName.inleibute('hrs.(self.bro,sattt               sel == this;
             this        * @p* @privcnnfig imeDnMode on.     * @p* @privription HandShoulds }omorKeyD imeomnMode on. on entiment.
   dDefault:s;
  .     * @p* @privrifault ;
       * @p* @priv: 'elean
        */
 * @pr      _fixxxxx.browsdiibute('hrConfig('imeDnMode on. typ                 e = n: (
attt.imeDnMode on.m=alse) {
  ?se, 't :s;
  )                                     * @p* @privcnnfig Change();
De('t     * @p* @privription HandDoire wrae MousChange fromod _haninth tut(funa oray
 Edimance dDefault:s;
  .     * @p* @privrifault ;
       * @p* @priv: 'elean
        */
 * @pr      _fixxxxx.browsdiibute('hrConfig('Change();
De('t{typ                 e = n: (
attt.Change();
De('tm=alse) {
  ?se, 't :s;
  )                                     * @p* @privcnnfig max();
     * @p* @privription Hand ienmax numblemofs)
   levele aoseUndo.     * @p* @privrifault 30     * @p* @priv: 'elNumble     */
 * @pr      _fixxxxx.browsdiibute('hrConfig('max();
{typ                 writeOnce:s;
                EXTRe = n: attt.max();
this30     * @p* @p} >                      * @p* @privcnnfig ptags             rivription HandIfs;
     }omorMous usts &lt;P&gt;(= gs de teadmofs&lt;br&gt;(= gs. (U't Shift + E alldtop thth &lt;br&gt;)     * @p* @privrifault e, 't     * @p* @priv: 'elean
        */
 * @pr      _fixxxxx.browsdiibute('hrConfig('ptags{typ                 writeOnce:s;
                EXTRe = n: attt.ptagsthise, 't     * @p* @p                        * @p* @privcnnfig lHhtml             rivription HandIfs;
    stion() & is not Wiqu afte yDnctoyTnEve, foyTaize, foreapsus, b'hkapsus.     * @p* @privrifault e, 't     * @p* @priv: 'elean
        */
 * @pr      _fixxxxx.browsdiibute('hrConfig('xOhtml=typ                 writeOnce:s;
                EXTRe = n: attt.xOhtmlthise, 't              EXTRod _hanction() {
xOhtml                    if (Dom.xOhtml                    if (EXTRe sedns fushis{                             toyTnEve:s;
                EXTRRRRRRRRRRRRRfoyTaize:s;
                EXTRRRRRRRRRRRRRforeapsus:s;
                EXTRRRRRRRRRRRRRb'hkapsus: ;
       * @p* @ppppppppppppp}                          tar =mpyuithgetRmefaultTar.get.ons fu                           (var doEx0; i < path=mplth; i++) {
                                this mpid) ons fu                 if ((((((((((((((((((var doExa; i < aath=mpid) ons fu lth; i++)a{
                                        this mpid) ons fu [a].e = n
               if (((((((((((((((((((((((((thisrns fu [ mpid) ons fu [a].e = n]                if (((((((((((((((((((((((((((((de key
 mpid) ons fu [a].bled') =                                          }             EXTRRRRRRRRRRRRRRRRRRRRR                              }
                            this                      }
                    }
                }
                                        * @p* @privcnnfig sontaiuec             rivription HandUstd wretudynami(sely
telinof tbegiorKeyD  seleJavaption h desinotmefault :extaelit             riWes alsotelink onexaen e('htdoesiu  }islsontaiuec.dIfsnotsontaiuecsis ntsstd wes alson oncd aosbent.
   .getE     * @p* @privrifault e, 't     * @p* @priv: 'el = sent.foc     * @p* @pr      _fixxxxx.browsdiibute('hrConfig('sontaiuec' yp                 writeOnce:s;
                EXTRe = n: attt.sontaiuecshise, 't     * @p* @p                        * @p* @privcnnfig e('in ext             rivription HandPttcess  }ominle(' :extaeli data aslthdoes asle('in :ext. Accou aeng  orasp'htstyetbs setsClie feeds.     * @p* @privrifault e, 't     * @p* @priv: 'elean
        */
 * @pr      _fixxxxx.browsdiibute('hrConfig('e('in ext{typ                 writeOnce:s;
                EXTRe = n: attt.p('in extshise, 't     * @p* @p                        * @p* @private
        * @m* @privcnnfig thr= '             rivription HandI alln(' config  oratoldof tbegithr= 'yent.foc.     * @p* @privrifault ;
       * @p* @priv: 'el = sent.foc     * @p* @pr      _fixxxxx.browsdiibute('hrConfig('xf doc=, p                 e = n: ;
       * @p* @p                        * @p* @private
        * @m* @privcnnfig bled') =_if doc             rivription HandI alln(' config  oratoldof tbegithr= 'yent.foc ustd wretudled')of tbegiorKeyD.     * @p* @privrifault ;
       * @p* @priv: 'el = sent.foc     * @p* @pr      _fixxxxx.browsdiibute('hrConfig('bled') =_if doc=, p                 e = n: ;
       * @p* @p                        * @p* @private
        * @m* @privdepreaiinkdd- N  lo);
s ustd, shouldsust  }lis'disaent has')     * @p* @privconfig :extaeli             rivription HandI alln(' config  oratoldof tbegi:extaeli ent.foc (vee('htnh desient.foc).     * @p* @privrifault ;
       * @p* @priv: 'el = sent.foc     * @p* @pr      _fixxxxx.browsdiibute('hrConfig(':extaeli=, p                 e = n: ;
                EXTRwriteOnce:s;
                                       * @p* @privcnnfig Change();
Threstold     * @p* @privription Hand iennumblemofsseapndsythatanetd aosbt iu b tweee Change fromattcessof      * @mif (rivrifault 3     * @p* @priv: 'elNumble     */
 * @pr     */
 * @p     _fixxxxx.browsdiibute('hrConfig('Change();
Threstold{, p                 e = n: attt.Change();
Threstoldthis3              EXTRe =idaeyDncYAHOO.lisObisNumble     */
 * @p                        * @p* @privcnnfig selowNoorKe     * @p* @privription HandShoulds }omorMous c if a oranon'orMo fields.sIesshouldsbrknotkdd }arr });
aechnique is not y
 Eon(.dIfs }omuwebtmokss }omr Arr  })f s   }oys alsostalsobrkd')  aosmake c ();
s.     * @p* @priSuch aslh Arl Arrof thnient has relow setsabinvabegicont);
 setshif the astar basedns fu oExa;shortcutsketE     * @p* @privrifault e, 't     * @p* @priv: 'elean
        */
 * @pr     */
 * @p     _fixxxxx.browsdiibute('hrConfig('selowNoorKe{, p                 e = n: attt.selowNoorKethise, 't              EXTRe =idaeyDncYAHOO.lisObisean
        */
 * @p                        * @p* @privcnnfig limitand('ens             rivription HandShoulds }omorKeyD limits }omaelowkddCommand('ens aos }omoie;
availd')  in entitar bas.dIfs;
     }on Command('enh'enhkeyboard shortcutss alsoEail thi }oyaworknot  efine
 in entitar bas.     * @p* @privrifault e, 't     * @p* @priv: 'elean
        */
 * @pr     */
 * @p     _fixxxxx.browsdiibute('hrConfig('limitand('ens{, p                 e = n: attt.limitand('ensthise, 't              EXTRe =idaeyDncYAHOO.lisObisean
        */
 * @p                        * @p* @privcnnfig ent has_cont             rivription HandI alln(' config  ora }omorMousslsontaiuec     * @p* @privrifault e, 't     * @p* @priv: 'el = sent.foc     * @p* @pr      _fixxxxx.browsdiibute('hrConfig('ent has_cont', p                 e = n: attt.ent has_cont                                     * @p* @private
        * @m* @privcnnfig orMous_wraepec             rivription Hand ienons ebtwraepec  ora }omo aeromorMous.     * @p* @privrifault ;
       * @p* @priv: 'el = sent.foc     * @p* @pr      _fixxxxx.browsdiibute('hrConfig('orMous_wraepec', p                 e = n: attt.erMous_wraepecthis;
                EXTRwriteOnce:s;
                                       * @p* @privattte('hrshe Arr             rivription Hand ienhe Arrh fieditorMous thr= 'ysontaiuec,knot includof tbegi:ar bas..     * @p* @privrifault Best guesstd aizeh fiedit:extaeli, for rest Wisults ust CSSlaoseUylvabegihe Arrh fiedit:extaeli orayame oesiu as senargt if      * @p* @priv: 'elSuteng     * @d* @pr      _fixxxxx.browsdiibute('hrConfig('he Arr rgp                 e = n: attt.he ArrhhisaddCgliS.wide = tr'disaent has'),;ahe Arr                EXTRod _hanction() {
he Arr                    if (Dom.thgetRWin erkd                if (((((((((//We have beee Win erkd, c ();
abegihe Arr             if (((((((((this._isN'disaanimatc{
                if (((((((((    e seonimull;ewcYAHOO.utal.Anims._isN'disaxf doc==N'disantNode.rem  rgp                                 he Arr:               if (((((((((((((((((((((.o:neInt(pathhe Arr  10                              }
                            this , 0.5                               onim.animatcke                  } el     e {
                            thisaddCsliS.wide._isN'disaxf doc==N'disantNode.rem  rg'he Arr rghe Arr                   } el                      }
                }
                                        * @p* @privcnnfig autoHe Arr             rivription HandReChilabegiptiol bassm selebegiorMo aeli 'enhWisizehitltopfits }omcont);
.sIes alsonot go sey lowkra }an entientEven config he Arr.     * @p* @privrifault e, 't     * @p* @priv: 'elean
   hhisNumble     */
 * @pr      _fixxxxx.browsdiibute('hrConfig('autoHe Arr{, p                 e = n: attt.sutoHe Arrthise, 't              EXTRod _hanction() {
a                    if (Dom.a                    if (if (Dom.._isN'disaxf doc==                if (((((((((((((=_isN'disaxf doc==N'disaent has')wsdiibute('hr('ptiol ing{,;ano                        this                          .toolVa('rExecNhange fro tyehgetRleKeyDAutoHe Arr, .bro,e) {
                           .toolVa('orMousKeyDown tyehgetRleKeyDAutoHe Arr, .bro,e) {
                           .toolVa('orMousKeyPWiss tyehgetRleKeyDAutoHe Arr, .bro,e) {
                        e {
                            Dom.._isN'disaxf doc==                if (((((((((((((=_isN'disaxf doc==N'disaent has')wsdiibute('hr('ptiol ing{,;asuto                        this                          .toolunsubptiobe('rExecNhange fro tyehgetRleKeyDAutoHe Arr                           .toolunsubptiobe('orMousKeyDown tyehgetRleKeyDAutoHe Arr                           .toolunsubptiobe('orMousKeyPWiss tyehgetRleKeyDAutoHe Arr                   } el                                                        * @p* @privattte('hrswidth             rivription Hand ienwidthh fieditorMous sontaiuec.     * @p* @privrifault Best guesstd aizeh fiedit:extaeli, for rest Wisults ust CSSlaoseUylvabegiwidthh fiedit:extaeli orayame oesiu as senargt if      * @p* @priv: 'elSuteng     * @d* @pr     * @d* @p     _fixxxxx.browsdiibute('hrConfig('width{, p                 e = n: attt.widthhhisaddCgliS.wide }lis'disaent has'),;awidth{               EXTRod _hanction() {
width                    if (Dom.thgetRWin erkd                if (((((((((//We have beee Win erkd, c ();
abegiwidth                     if (Dom.thget'disaanimatc{
                if (((((((((    e seonimull;ewcYAHOO.utal.Anims._isN'disaent has_cont')s'disaent has'),;              if (((((((((((((((((width:               if (((((((((((((((((((((.o:neInt(pathwidth  10                              }
                            this , 0.5                               onim.animatcke                  } el     e {
                            this._isN'disaent has_cont')ssliS.wideawidth{, width                   } el                      }
                }
                                                                 * @p* @privattte('hrsblankeClic             rivription Hand ienURLn ora }omeClic e('httoldlldtoppuesiu wretulHhtmlef thnieClic.     * @p    rivrifault  ienyahooapcheeeleaddWissn ora }omentEven rentthis+;assime;/blankeClic.png{     * @p* @priv: 'elSuteng     * @d* @pr     * @d* @p     _fixxxxx.browsdiibute('hrConfig('blankeClic{, p                 e = n: attt.blankeClichhis._getRangBlankIClic()                                     * @p* @privattte('hrscss             rivription Hand ienBast CSSlustd aosEdimafabegicont);
  fieditorMous     * @p    rivrifault <chan><pre>t, 'yp                 he Arr: 95%                            bodyyp                 he Arr: 100%                  paddeng: 7px;Rb'hkgd tra-apsus: #fff;RfoyT:13px/1.22narial,helvetica,rTimn,sans-htmif;*foyT-aize:small;*foyT:x-small;                           ayp                 apsus: b= ns });
            :ext-decora() &: )
 erClies });
            entsus: poi all;                           .warneng-localfil                thisbor er-bottom: 1px dasKed afte!importan
              s             .non'busyyp                 entsus: waoes!importan
              s             imgEuredElfte{P//SafarimeClic stion() &             thisbor er: 2px dottfte#808080              s             imgyp                 entsus: poi alls!importan
              thisbor er: noie              s             </pre></chan>     * @p* @priv: 'elSuteng     * @d* @pr     * @d* @p     _fixxxxx.browsdiibute('hrConfig('css{, p                 e = n: attt.csshhis._getRrifaultCSS              EXTRwriteOnce:s;
                                       * @p* @privattte('hrsh, '             rivription Hand ienrifault  = staosbt written aos }omihr= 'yment if yreEdit  }omcont);
saworkloadD= (Nonk  }afabegiDOCTYPE atttlwalsobrkdddD= atoWin erdoeem)     * @p* @privrifault  is C = stWiqu afssa fewc })f s(Domyou workaosovecride                  <p><chan>{TITLE , {CSS , {HIDDEN_CSS , {EXTRA_CSS </chan> 'enh<chan>{CONTENT </chan> netd aosbt begre,i }oyaworkntsstd aosYAHOO.lisObsubptit'hrsaosbt vee('hth desi begr sutengs.<p>                 <p><chan>onload="bent.
   .getE_rteLoadD=als;
   "</chan> :s }omoiload suatc if ymustsbt begre ora }omorMouss alsonot finishkloadthe.</p>                 <chan>     * @p* @p    <prn>     * @p* @p    &lt;h, '&gt;                     &lt;head&gt;                         &lt;titln&gt;{TITLE &lt;/titln&gt;                         &lt;od ash,tp-iqu v="Cont);
-T 'e"mcont);
=":ext/h, '; c (nt(t=UTF-8" /&gt;                         &lt;eUylv&gt;                         {CSS                          &lt;/eUylv&gt;                         &lt;eUylv&gt;                         {HIDDEN_CSS                          &lt;/eUylv&gt;                         &lt;eUylv&gt;                         {EXTRA_CSS                          &lt;/eUylv&gt;                     &lt;/head&gt;                 &lt;bodyyonload="bent.
   .getE_rteLoadD=als;
   "&gt;                 {CONTENT                  &lt;/.get&gt;                 &lt;/h, '&gt;                 </pre>                 </chan>     * @p* @priv: 'elSuteng     * @d* @pr     * @d* @p     _fixxxxx.browsdiibute('hrConfig('h, ' rgp                 e = n: attt.h, 'yhis'<h, '><head><titln>{TITLE </titln><od ash,tp-iqu v="Cont);
-T 'e"mcont);
=":ext/h, '; c (nt(t=UTF-8" /><bast href="'m+ thgetRbastHREFa/sp+ <eUylv>{CSS </eUylv><eUylv>{HIDDEN_CSS </eUylv><eUylv>{EXTRA_CSS </eUylv></head><bodyyonload="bent.
   .getE_rteLoadD=als;
   ">{CONTENT </.get></h, '> ,             EXTRwriteOnce:s;
                                        * @p* @privattte('hrsextracss             rivription HandExtramuwebtmefine
 cme aosload rExecs }omrifault Simp, orKeyD CSS     * @p* @privrifault '{     * @p* @priv: 'elSuteng     * @d* @pr     * @d* @p     _fixxxxx.browsdiibute('hrConfig('extracss', p                 e = n: attt.extracssyhis' ,             EXTRwriteOnce:s;
                                        * @p* @privattte('hrsleKeyDSubmKe     * @p* @privription HandConfig heKeyDs thi }omorMouss alsoattakKdoe== thaos }om:extaelis ntNodeyEdim's submKealeKeyDE.     * @p    Ihdoess Csetltop;
  ,i }omorMouss alsoattemptltopattakKda submKeaFix(enlldtop }om:extaelis ntNodeyEdim.     * @p    Tretules alsotteggera }omorMousslsave leKeyDExaen e('htdMousCewccont);
 b'hk rnaop }om:ext aeli reEdit  }omEdimss CsubmKetft.     * @p* @privrifault e, 't     * @p* @priv: 'elean
        */
 * @pr     */
 * @p     _fixxxxx.browsdiibute('hrConfig('leKeyDSubmKe rgp                 e = n: attt.heKeyDSubmKethise, 't              EXTRod _hanction() {
Comm                    if (Dom.thget'disaent has')wEdim                    if (if (Dom.s.brow_EdimBns fu                 if (((((((((((((.brow_EdimBns fu his[                                                     thiseomm                    if (((((((((t.stopVa()brow'disaent has')wEdim, 'submKe rgehgetRleKeyDFdimSubmKe, .bro,e) {
                               doEx0; i)brow'disaent has')wEdimt'diont hassByTag = '('inpur                                (var doExs; i < s < ilth; i++)s{
                                    tar = 'el= i[s]t'diibute('hr('= 'e                                    Dom.t 'elev.k. 'e.toLowkrCast()yu= asubmKe 
                if (((((((((            t.stopVa(i[s], 'c', t rgehgetRleKeyDFdimBns fuk', t, .bro,e) {
                                       .brow_EdimBns fu [.brow_EdimBns fu lth; i+]l= i[s]                                                            this                      }
   e {
                            thist.stopveChilLix(enll()brow'disaent has')wEdim, 'submKe rgehgetRleKeyDFdimSubmKe                               Dom.thgetREdimBns fu                 if (((((((((((((thist.stopveChilLix(enll()browREdimBns fu , 'c', t rgehgetRleKeyDFdimBns fuk', t                                                     }
                    }
                }
                                        * @p* @privattte('hrsbled') =             rivription Hand iiss alsotogglk  }omorMous's bled', f suatc. Whee editorMous ts bled', f,omnMode on. );
aurne
 off setsa mas ais e('htnhovecs }omihr= 'ysopno i allan() & citltake e('ht.     * @p* @pAlsoTar basedns fusawork, 'o bled', f soi }oyacitnot bomuwet.     * @p* @privrifault e, 't     * @p* @priv: 'elean
        */
 * @pr               ._chesdiibute('hrConfig('bled') = rgp                 e = n: e, 't              EXTRod _hanction() {
bled', f            var         Dom.thgetRWin erkd                if (((((((((._getRrled', orKeyD
bled', f                   } el                                                        * @p* @privcnnfig iaveE'             rivription HandWhee save  = stislsal, f,o });
ent has walsobrkupda ed as welsoass }omsourceh fidata.     * @p* @privrifault ent.foc     * @p* @priv: 'el = sent.foc     * @p* @pr      _fixxxxx.browsdiibute('hrConfig('iaveE' rgp                 e = n:  }lis'disaent has')     * @p* @p                        * @p* @privcnnfig :ar bas_cont             rivription HandI alln(' config  ora }om:ar basslsontaiuec     * @p* @privrifault e, 't     * @p* @priv: 'elean
        */
 * @pr      _fixxxxx.browsdiibute('hrConfig(':ar bas_cont=, p                 e = n: ;
                EXTRwriteOnce:s;
                                       * @p* @privattte('hrs:ar bas             rivription Hand ienrifault tar baseconfig.     * @p* @priv: 'elObjdEl     * @d* @pr     * @d* @p     _fixxxxx.browsdiibute('hrConfig(':ar bas rgp                 e = n: attt.tar basehis._getRrifaultTar bas              EXTRwriteOnce:s;
                EXTRod _hanction() {
bar.get)                       Dom.s.ar.get.ons fuT 'e                if (((((((((.ar.get.ons fuT 'eyuithgetRmefaultTar.get.ons fuT 'e                  } el                  xxxx.brow_mefaultTar.gethis.ar.get                                                        * @p* @privattte('hrsanimatc             rivription HandShoulds }omorMous animatc ow.set Chil hass     * @p* @privrifault e, 't unlissnAnimat) & is f tra   }on ;
       * @p* @priv: 'elean
        */
 * @pr     */
 * @p     _fixxxxx.browsdiibute('hrConfig('animatc{typ                 e = n: (
attt.animatc  ?s((YAHOO.utal.Anim  ?s;
   :se) {
  :se) {
               EXTRe =idaeyDnction() {
e = n
               if (((((tar retals;
                        Dom.sYAHOO.utal.Anim                if (((((((((retalse;
                                          xxxxrn falsrn                                                         * @p* @privcnnfig eane'             rivription HandAsrnferkncedtop }omeane' wesworkusof t oraiw.sets.     * @p* @privrifault e, 't     * @p* @priv: 'elean
        */
 * @pr     */
 * @p     _fixxxxx.browsdiibute('hrConfig('eane'=, p                 e = n: ;
                EXTRwriteOnce:s;
                EXTRe =idaeyDnction() {
e = n
               if (((((tar retals;
                        Dom.sYAHOO.wid th.Oveclay                if (((((((((retalse;
                                          xxxxrn falsrn                                                                        * @p* @privattte('hrsE();
AtStaml             rivription HandShouldswt E();
 begiwi.set whee editcont);
 is ready?     * @p* @privrifault e, 't     * @p* @priv: 'elean
        */
 * @pr     */
 * @p     _fixxxxx.browsdiibute('hrConfig('E();
AtStaml rgp                 e = n: attt.E();
AtStamlthise, 't              EXTRwriteOnce:s;
                EXTRod _hanction() {
f                 if (((((Dom.f                 if (((((xxxx.browVa('orMousCont);
LoadD==,etion() {
                                e l = Ethis;
                                imeout(function() {
                                    imlf.E();
.(self);
                                    imlf.orMousDi    lse;
                                }, 40                            }, .bro,e) {
                                     }
                                        * @p* @privattte('hrsbomnath             rivription Hand ogglk  }omdise('to fieditcntEven evennathhrelow editorMous     * @p    rivrifault e, 't     * @p* @priv: 'elean
        */
 * @pr     */
 * @p     _fixxxxx.browsdiibute('hrConfig('bomnath rgp                 e = n: attt.bomnaththise, 't              EXTRod _hanction() {
bomnath                if (((((Dom.bomnatht&& s.browbomnath                if (((((xxxx.browbomnathtisment.
   telinkent();
 )DIV{
<                     xxxx.browbomnath.idhis._get'disaxd')a/sp_bomnath <                     xxxxaddClass(sel,.browbomnath, 'bomnath 
<                     xxxx.brow'disaent has_cont')s'disafirstd(els  sn oncdd(els[.browbomnath <                     xxxxDom.._isN'disaxf doc==                if (((((((((((((=_isN_writeaddPathke                  } el                           e {
  Dom.sbomnatht&& .browbomnath                if (((((xxxx.browbomnathentNode.removeChild(els[.browbomnath <                     xxxx.browbomnathtis;
                                      }
                                        * @p* @privattte('hrsmarkup             rivription HandShouldswt try aosadjuststhrsmarkup  ora }omfoelowof tb 'es: immantic, cme,nrifault oraxh, '             rivrifault "immantic"     * @p* @priv: 'elSuteng     * @d* @pr     * @d* @p     _fixxxxx.browsdiibute('hrConfig('markup rgp                 e = n: attt.markup his'immantic ,             EXTRe =idaeyDnction() {
markup                if (((((switch 
markup.toLowkrCast()                if (((((xxxxcast 'immantic                   ((((xxxxcast 'css'                  ((((xxxxcast 'rifault'                  ((((xxxxcast 'xh, ''                  ((((xxxxrn fals;
                                          xxxxrn false;
                                                          * @p* @privattte('hrsveChilLineBeliks             rivription HandShouldswrsveChilsCliebeliks setsextraasp'hts on rTimnup             rivrifault e, 't     * @p* @priv: 'elean
        */
 * @pr     */
 * @p     _fixxxxx.browsdiibute('hrConfig('veChilLineBeliks rgp                 e = n: attt.veChilLineBeliksthise, 't              EXTRe =idaeyDncYAHOO.lisObisean
        */
 * @p                                     * @p* @privcnnfig drag             rivription HandSerr });
cnnfig :asmake  }omorKeyD draggd') ,ayame 'proxy' :asmake ust YAHOO.utal.DDProxy.     * @p* @priv: 'el{ean
   /Suteng}     */
 * @pr      _fixxxxx.browsdiibute('hrConfig('brag{typ                 writeOnce:s;
                EXTRe = n: attt.bragshise, 't     * @p* @p                         * @p* @privcnnfig Wisize             rivription HandSerr });
top;
   :asmake  }omorKeyD Risizd')   desiYAHOO.utal.Risize.d ienrifault cnnfig t;
availd') : myorKeyD._WisizeConfig             riAnimat) & walsobrkignortd wril  y
 Edimof tbeis reaizehaosaelow  ora }omdynami( c ();
aiu eizeh fiedit:ar bas.     * @p* @priv: 'elean
        */
 * @pr      _fixxxxx.browsdiibute('hrConfig('reaize{typ                 writeOnce:s;
                EXTRe = n: attt.reaizehhise, 't     * @p* @p                         * @p* @privcnnfig filterWor=             rivription HandAttemptltopfilternons MS Wor=  = st selebegiErMous's onspur.     * @p* @priv: 'elean
        */
 * @pr      _fixxxxx.browsdiibute('hrConfig('filterWor= rgp                 e = n: attt.EilterWor=thise, 't              EXTRe =idaeyDncYAHOO.lisObisean
        */
 * @p         */
 }      /**
        * @private
        * @m*ivod _hanRangBlankIClic     * @m*ivription HandReuteevkss }omf
   urso fiediteClic topust ass }omblankieClic.     * @p*ivrn fals{Suteng}d ienURLntop }omblankieClic     * @p*      _fixRangBlankIClicnction() {
            try Dom.s.browDOMReady)                   .brow_queu
[.brow_queu
lth; i+]l= ['RangBlankIClic rgargt if s]                  rn fals' <             }     */
 * @pdoEx0mgl= ' <             Dom.s.brow_blankIClicLoadD=)                   Dom.YAHOO.wid th.orKeyDInfo.blankIClic                if (((((.browsdi('blankeClic{, YAHOO.wid th.orKeyDInfo.blankIClic                       .brow_blankIClicLoadD=als;
                     e {
                        doExdivtisment.
   telinkent();
 )div 
<                     div.eUylv.posi Hand= 'absol'hr <                     div.eUylv.topyui'-9999px <                     div.eUylv.leftyui'-9999px <                     div.sName = 'yis._getCLASS_PREFIXa/sp-blankeClic{<                     dent.
   .getEn oncdd(els[div
<                     0mgl= YAHOO.utal.DddCgliS.widediv, ab'hkgd tra-eClic{
<                     0mgl= imgEvee('ht('urs({,;a  svee('ht('){,;a  svee('ht(/"/g,;a  <                     //AdobrkAIR Con.     * @p* @p        0mgl= imgEvee('ht('n o:/{,;a  ;p* @p                     if (((((.browsdi('blankeClic{, img                       .brow_blankIClicLoadD=als;
                        div.ntNode.removeChild(els[div
<                     YAHOO.wid th.orKeyDInfo.blankIClicl= img                                 e {
                    0mgl= ._isN'disablankeClic{                             rn falsimg          }      /**
        * @private
        * @m*ivod _hanRleKeyDAutoHe Arr     * @m*ivription HandHeKeyDs reaizof tbegiorMous's he Arrhbastd on enticont);
     * @p*      _fixRleKeyDAutoHe Arrnction() {
            try ret denyuithgetR'diDoc(               EXTRbodyyismen .get              EXTRmenElyismen dent.
  ent();
               ret he Arrh=neInt(pathaddCgliS.wide }lis'disaerMous_wraepec'),;ahe Arr    10 ;             ret newHe Arrh=n.getEptiol He Arr<             Dom. }lisser.webkit) {
                if (newHe Arrh=nmenElEptiol He Arr<                           thisnewHe Arrh<neInt(path }lis'disahe Arr    10                 if (newHe Arrh=neInt(path }lis'disahe Arr    10 <                           thishhe Arr !=(newHe Arr)lev.knewHe Arrh>=neInt(path }lis'disahe Arr    10                     if (e seonimullthget'disaanimatc{
                  .browsdi('animatc{tye) {
                   .browsdi('he Arr rgnewHe Arrh/sppx 
                  .browsdi('animatc{tyanim
                  Dom. }lisser.webkic                if (((((//I allnerrExplortr netds .bro                     .brow'disaxf doc==NsliS.wideahe Arr rg'99%  <                     .brow'disaxf doc==NsliS.wideazoom , '1  <                     e l = Ethis;
                        wi.set.imeout(function() {
                             = tr'disaxf doc==NsliS.wideahe Arr rg'100%  <                     }  1 <                 }                       }      /**
        * @private
        * @m*ivatty
    REdimBns fu      * @m*ivription HandArr'to fidns fusa }afaworkin entiErMous's ntNodeyEdim ( orateKeyDSubmKe)     * @m*iv: 'elArr't     * @p*      _fixREdimBns fu : ;
                    * @private
        * @m*ivatty
    REdimBns fuk', t =         rivription Hand ienEdim dns fu  }afa aslc', t =laoseubmKet }omEdim.     * @m*iv: 'el = sent.foc     * @p*      _fixREdimBns fuk', t =: ;
                    * @private
        * @m*ivod _hanRleKeyDFdimBns fuk', t         rivription Hand ienc', taFix(enlldameign =laoseakKdeubmKetdns fu in entiErMous's ntNodeyEdim.     * @priva {Event.sto e vd ienc', taev);
     * @p*      _fixRleKeyDFdimBns fuk', tnction() {
Cv            try ret tethist.stop'diTargdisev
<             )browREdimBns fuk', t =his;et          }      /**
        * @private
        * @m*ivod _hanRleKeyDFdimSubmKe     * @m*ivription HandHeKeyDs  }omEdimseubmKmeion.     * @priva {EvenObjdEl}  vd ienFdim SubmKetEv);
     * @p*      _fixRleKeyDFdimSubmKenction() {
Cv            try .browsave = s(                ret Edims i)brow'disaent has')wEdim              EXTRtethis)browREdimBns fuk', t =hhise, 't               t.stopveChilLix(enll(Edim, 'submKe rgehgetRleKeyDFdimSubmKe               Dom.YAHOO.env.uakic                if (//Edim.Event.sto("onsubmKe"
                  Dom. eth&& s.ar.bled', f            var         .ar.c', tke                                 e {
    (// Gecko, Oy
 a,ysetsSafari                 Dom. eth&& s.ar.bled', f            var         .ar.c', tke                                    ret ot.stotisment.
   telinke.sto(" = se.stos"
                  ot.sto.inlee.sto("submKe",p;
  ,i  {
                   Edim.diseatche.sto(ot.sto
                  Dom.YAHOO.env.uakit) {
                if (    Dom.YAHOO.lisObisFion() {
fdim.submKe)                if (((((xxxxfdim.submKeke                  } el              }
                              //2.6.0             //ReChild .bro,enot netd sincedveChiof tSafarim2.x             //t.sto.stope.sto(ev
<         }      /**
        * @private
        * @m*ivod _hanRleKeyDFdntSize         *ivription HandHeKeyDs  }omEdtotaizehdns fu in enti:ar bas.     * @priva {EvenObjdEl} olObjdEl rn falfte seleTar.get's bns fuk', ttEv);
     * @p*      _fixRleKeyDFdntSizenction() {
o            try ret dns fu is)brow:ar bas.angBns fuById(o.ons fu.if               ret e = nh=n.ns fu.'disalab E')a/sppx <             )browCommand('en(afoyTaize , e = n
<             rn false;
            }      /**
        * @private
        * @m*ivription HandHeKeyDs  }omapsusp, t sedns fusain enti:ar bas.     * @priva {EvenObjdEl} olObjdEl rn falfte seleTar.get's bns fuk', ttEv);
     * @p*      _fixRleKeyDCpsusP, t snction() {
o            try ret cm=hiso.ons fu              ret e = nh=n'#'m+ o.apsus              Dom.(cm=hi= aforeapsus')hhis(cm=hi= ab'hkapsus')                if ()browCommand('en(cm=, e = n
<                       }      /**
        * @private
        * @m*ivod _hanRleKeyDAlign     * @m*ivription HandHeKeyDs  }omalign if yrns fusain enti:ar bas.     * @priva {EvenObjdEl} olObjdEl rn falfte seleTar.get's bns fuk', ttEv);
     * @p*      _fixRleKeyDAlignnction() {
o            try ret cm=his;
                (var doEx0; i < patho.ons fu. ifulth; i++) {
                    Dom.o.ons fu. ifuid) e = nh=iso.ons fu.e = n
               if (((((cm=hiso.ons fu. ifuid) e = n<                 }                       try ret e = nh=nthgetR'diStion() &(                )browCommand('en(cm=, e = n
<             rn false;
            }      /**
        * @private
        * @m*ivod _hanRleKeyDAExecNhange fro     * @m*ivription HandF afssaExecsa Change fromhn once aosen fe Mous })f s( }afa egre afsmeo n entiChan c ();
asrns fu suatc).     * @p*      _fixRleKeyDAExecNhange fronction() {
            try ret nathtisthgetR'diDodPathke              EXTRelmhis;
                EXTRfamilyhis;
                EXTRfoyTaizehis;
                EXTRe =idFdnt lse;
                EXTRfn_dns fu is)brow:ar bas.angBns fuByV = n(afoyTndoc==              EXTRfs_dns fu is)brow:ar bas.angBns fuByV = n(afoyTaize =              EXTRhd_dns fu is)brow:ar bas.angBns fuByV = n(aheading{                (var doEx0; i < pathnatheth; i++) {
                    elmhisnathid)               EXTRe t tegl= elm.tag = '.toLowkrCast()                    thiselmt'diibute('hr('=ag==                if (((((tegl= elm.'diibute('hr('=ag==<                 }              EXTRfamilyhiselm.'diibute('hr('facc{
                  thisaddCgliS.wideelm, afoyT-family==                if (((((familyhisaddCgliS.wideelm, afoyT-family==<                     //AdobrkAIR Con.     * @p* @p        familyhisfamilysvee('ht(/'/g,;a  <    * @p* @p                         }              EXTRDom. eObsubptteng(0  1 hi= ah'                if (    Dom.hd_dns fu                if (((((xxxxfdir doExh; i < hathhd_dns fu._cnnfigs. ifule = neth; i++)h{
                                thishd_dns fu._cnnfigs. ifule = n[h) e = n.toLowkrCast()yu=  eO                                    hd_dns fu.sdisalab E',hhd_dns fu._cnnfigs. ifule = n[h) :ext                                                     }
                    }
      thgetRupda eMifuC if ed(aheading{,  eO                   } el              }
                               Dom.fn_dns fu                    fdir doExb; i < bathfn_dns fu._cnnfigs. ifule = neth; i++)b{
                        Dom.familyh&& fn_dns fu._cnnfigs. ifule = n[b) :ext.toLowkrCast()yu= familystoLowkrCast()                if (((((xxxxe =idFdnt ls;
                            familyhisfn_dns fu._cnnfigs. ifule = n[b) :ext; //Puet }omatty
   ifu ndocain entidns fu                 } el              }
                    Dom.!e =idFdnt                if (((((familyhisfn_dns fu._cnnfigs.lab E._inleialConfig e = n<                 }             xxxxe r(familyLab Eh=n'<span rTass="non':ar bas-foyTndoc-'m+ thgetRrTimnCName = '.family)a/sp+ 'm+ familyh/sp</span>{<                 Dom.fn_dns fu.'disalab E')a!=(familyLab E                if (((((fn_dns fu.sdisalab E',hfamilyLab E                       .brow_upda eMifuC if ed(afoyTndoc=,hfamily <                 }                            Dom.fs_dns fu                    fdyTaizehiseInt(pathaddCgliS.wideelm, afoyTSize =  10 <                 Dom.(fdyTaizehi=is;
  )hhisie =N(fdyTaize=                if (((((fdyTaizehisfs_dns fu._cnnfigs.lab E._inleialConfig e = n<                 }             xxxxfs_dns fu.sdisalab E',h''+fdyTaize=<                                        Dom.s.brow_isent();
 elm, a.get')t&& s.brow_isent();
 elm, aimg==                if ()brow:ar bas.end', Bns fu.fn_dns fu                   .brow:ar bas.end', Bns fu.fs_dns fu                   .brow:ar bas.end', Bns fu.aforeapsus')                  .brow:ar bas.end', Bns fu.ab'hkapsus')<                           this.brow_isent();
 elm, aimg==                if (Dom.YAHOO.wid th.Oveclay                if (((((.brow:ar bas.end', Bns fu.atelinklink==<                 }                           this.brow_hasPtNode elm, a.lockquotc{
                if (.brow:ar bas.stion(Bns fu.aindhas')                  .brow:ar bas.bled', Bns fu.aindhas')                  .brow:ar bas.end', Bns fu.aoutdhas')                            this.brow_hasPtNode elm, aol')hhis.brow_hasPtNode elm, aul{
                if (.brow:ar bas.bled', Bns fu.aindhas')                            .brow_Nam(Bns fuhis;
                         }      /**
        * @private
        * @m*ivod _hanRleKeyDIHhtmlIClick', t         rivription HandOonce antiICliclPtty
  ifssWi.set whee editlHhtmliIClicldns fu islc', t =lus aniICliclislDou')  k', t =.     * @p*      _fixRleKeyDIHhtmlIClick', tnction() {
            try Dom.)brow'disalimitand('ens{=                if (Dom.!)brow:ar bas.angBns fuByV = n(alHhtmleClic{                 if (((((rn false;
                                                         .brow:ar bas.stt('bled') = rg  {
   //Dled')  enti:ar bas whee editattmptls Cshowof          try ret RleKeyDAEChisfion() {
                    ret  Eh=n.browcntEvenent();
[0]              EXTRRRRRsrnyui'h,tp://{<                 Dom.! E                if ((((( Eh=n.browR'diStion(edent();
 e                                    thisel                        Dom. E.'diibute('hr('srn==                if (((((((((srnyui E.'diibute('hr('srn=, 2 <                     xxxxDom.srn.indhxOf.)brow'disablankeClic{ )a!=(-1                                srnyui)browSTR_IMAGE_HERE<                     xxxx                  }
                }
                    e l =tthisattmpt()browSTR_IMAGE_URLn/sp: =, srn <                 Dom.(=tth!i= a')t&& (=tth!i= ;
  )                if ((((( Ewsdiibute('hr('prn=, =tte                   e {
  Dom.=tthii= a')t              if ((((( EwntNode.removeChild(els[ E                       .browcntEvenent();
his[                       .browChange fro e                   e {
  Dom..=tthii= ;
  )                if (((((srnyui E.'diibute('hr('srn=, 2 <                     Dom.srn.indhxOf.)brow'disablankeClic{ )a!=(-1                             EwntNode.removeChild(els[ E                           .browcntEvenent();
his[                           .browChange fro e                                    }
                    .browcloseWi.set e                  .brow:ar bas.stt('bled') = rge) {
                   .browunsubptiobe('rExecEommand('en rgRleKeyDAEC, .bro,e) {
               }<             )browVa('rExecEommand('en rgRleKeyDAEC, .bro,e) {
           }      /**
        * @private
        * @m*ivod _hanRleKeyDIHhtmlIClicWi.setClose         rivription HandHeKeyDs  }omalosof t fieditICliclPtty
  ifssWi.set.     * @p*      _fixRleKeyDIHhtmlIClicWi.setClosenction() {
            try .browChange fro e          }      /**
        * @private
        * @m*ivod _hanRisLocalFil      * @m*iva {EvenSuteng}dursoTHedurs/ptteng aosc if          rivription HandC if e aosen  Doma suteng (hreflus 0mglsrn ais eossiblyha local fil  rnferknce..     * @p*      _fixRisLocalFil nction() {
urs            try Dom.
urs  && (urso!i= a')t&& ((urs.indhxOf.'file:/')a!=(-1  his(urs.indhxOf.':\\')a!=(-1 )                if (rn fals;
                              rn false;
            }      /**
        * @private
        * @m*ivod _hanRleKeyDCelinkLinkk', t         rivription HandHeKeyDs  }omty
nof t fieditLinklPtty
  ifssWi.set whee editCelinktLinkldns fu islc', t =lus anihreflts bou') c', t =.     * @p*      _fixRleKeyDCelinkLinkk', tnction() {
            try Dom.)brow'disalimitand('ens{=                if (Dom.!)brow:ar bas.angBns fuByV = n(atelinklink==                if (((((rn false;
                                                         .brow:ar bas.stt('bled') = rg  {
   //Dled')  enti:ar bas whee editattmptls Cshowof           try ret RleKeyDAEChisfion() {
                    ret  Eh=n.browcntEvenent();
[0]              EXTRRRRRurso= ' <                  thisel                        Dom. E.'diibute('hr('href=, 2 h!i= ;
  )                           urso=  E.'diibute('hr('href=, 2                                     }
                    e l =tthisattmpt()browSTR_LINK_URLn/sp: =, urs <                 Dom.(=tth!i= a')t&& (=tth!i= ;
  )                if (((((e l ursV = nh=n=tt<                     Dom.(ursV = n.indhxOf.':/'+'/')a==(-1  && (ursV = n.subptteng(0 1)a!=('/')a&& (ursV = n.subptteng(0  6).toLowkrCast()y!=('mailto                      if (if (Dom.(ursV = n.indhxOf.'@')a!=(-1  && (ursV = n.subptteng(0  6).toLowkrCast()y!=('mailto                      if (if (((((//F tra ani@ eign,satefix  desimailto                  ((((xxxxxxxxursV = nh=n'mailto:'m+ ursV = n<                     xxxx e {
                            this/* ://onot f tra addengpr      _fixxxxx        if (if (Dom.ursV = n.subptteng(0  1)a!=('#')t              if (((((        this//ursV = nh=n'h,tp:/'+'/'m+ ursV = n<                     xxxxxxxx                      xxxx                  }
                }
       Ewsdiibute('hr('href=, ursV = ne                   e {
  Dom.=tth!i= ;
  )                       ret Rspan uithgetR'diDoc(  telinkent();
 )span==<                     Rspan.inner = st=  E.inner = s<                     addClass(sel,Rspan, 'non'non==<                      EwntNode.removee('htd(els[Rspan,  E                                     .browcloseWi.set e                  .brow:ar bas.stt('bled') = rge) {
                   .browunsubptiobe('rExecEommand('en rgRleKeyDAEC, .bro,e) {
               }<             )browVa('rExecEommand('en rgRleKeyDAEC, .bro        */
 }      /**
        * @private
        * @m*ivod _hanRleKeyDCelinkLinkWi.setClose         rivription HandHeKeyDs  }omalosof t fieditLinklPtty
  ifssWi.set.     * @p*      _fixRleKeyDCelinkLinkWi.setClosenction() {
            try .browChange fro e              .browcntEvenent();
his[           }      /**
        * @privod _hanWin er         rivription HandCalls editate
    od _hanRWin erdonma smeout(funhaosaelow  ora begr  })f s( n entiplic topcontin   :asload.     * @p*      _fixWin ernction() {
            try Dom.)browRWin erkd                if (rn false;
                              thiss.browDOMReady)                   .brow_queu
[.brow_queu
lth; i+]l= ['Win er rgargt if s]                  rn false;
                              this)brow'disaent has')                if (Dom.)brow'disaent has')wtag = '                if (((((.brow_:extaeli ls;
                        Dom.)brow'disaent has')wtag = '.toLowkrCast()y!==n':extaeli')t              if (((((    .brow_:extaeli lse;
                                           e {
                        rn false;
                                   e {
                    rn false;
                              )browRWin erkd ls;
                e l = Ethis;
                wi.set.imeout(function() {
                     = trRWin er.(self);
                }, 4e          }      /**
        * @private
        * @m*ivod _hanRWin er         rivription HandCausDs  }om:ar bas sets }omorMous :asWin erdsetsvee('hth }om:extaeli.     * @p*      _fixRWin ernction() {
            try e l = Ethis;
                .browsdi(':extaeli',i)brow'disaent has')                )brow'disaent has_cont')ssliS.wideadise('t',h'noie==<             )brow'disaent has_cont')slass(sel,.browCLASS_CONTAINER                            .browsdi('xf doc=rgehgetRtelinkIf doc()                wi.set.imeout(function() {
                     = trRimeInleialCont
   tself);
                }, 10 <              )brow'disaerMous_wraepec')sn oncdd(els[.brow'disaxf doc==N'disaent has') <              Dom.)brow'disabled') = 
                if (.browRrled', orKeyD
) {
               }          try ret tbasConfs i)brow'disa:ar bas                 /CelinktTar.gethinstance             Dom.)basConfsinstance fiTar.get)                   .brow:ar bass i)basConf                  //Serr }om:ar bas to bled', f untiltcont);
 is loadD=                 .brow:ar bas.stt('bled') = rg) {
               }e {
                    //Serr }om:ar bas to bled', f untiltcont);
 is loadD=                 .basConf.bled', f ls;
                    .brow:ar bass iCewcTar.get()brow'disa:ar bas_cont'), .basConf               }          try )browEvent.sto(a:ar basLoadD==,e{tb 'e: a:ar basLoadD==,etargdi: .brow:ar bass                              .brow:ar bas.Va(':ar basCollapsD==,etion() {
                    Dom.)browcntEvenWi.set                if (((((.browChilWi.set e                  }             }, .bro,e) {
               .brow:ar bas.Va(':ar basExpandD==,etion() {
                    Dom.)browcntEvenWi.set                if (((((.browChilWi.set e                  }             }, .bro,e) {
               .brow:ar bas.Va('fdyTaizeC', t rgehgetRleKeyDFdyTSize, .bro,e) {
                            .brow:ar bas.Va('cpsusP, t sk', t ==,etion() {
o            try     .browRleKeyDCpsusP, t s
o                   rn false;
    //Stopyentidns fuC', taev);
     * @p    }, .bro,e) {
                .brow:ar bas.Va('alignC', t rgehgetRleKeyDAlign, .bro,e) {
               .browVa('rExecNhange fro rgehgetRleKeyDAExecNhange fro, .bro,e) {
               .brow:ar bas.Va('lHhtmleClicC', t rgehgetRleKeyDIHhtmlIClick', t, .bro,e) {
               .browVa('wi.setlHhtmleClicC'ose rgehgetRleKeyDIHhtmlIClicWi.setClose, .bro,e) {
               .brow:ar bas.Va('telinklinkC', t rgehgetRleKeyDCelinkLinkk', t, .bro,e) {
               .browVa('wi.settelinklinkC'ose rgehgetRleKeyDCelinkLinkWi.setClose, .bro,e) {
                              /Ree('hthTextaeli  desierMod')  aeli             )brow'disantNode.rem  svee('htd(els[.brow'disaent has_cont')s'disaent has'),i)brow'disaent has')                             .browsdiS.wideavisibilitt',h'hidden==<             .browsdiS.wideaposi Han',h'absol'hr =<             .browsdiS.wideatop',h'-9999px =<             .browsdiS.widealeft',h'-9999px =<             .brow'disaent has_cont')sl oncdd(els[.brow'disaent has')               .brow'disaent has_cont')ssliS.wideadise('t',h'.lock')                addClass(sel,.brow'disaxf doc==N'disantNode.rem  ,i)browCLASS_EDITABLE_CONT               .brow'disaxf doc==Nlass(sel,.browCLASS_EDITABLE                 /Serrhe Arr setswidesi fmorMous sontaiuec     * @p* @p.brow'disaent has_cont')ssliS.wideawides',i)brow'disawides')               addCsliS.wide }lis'disaxf doc==N'disantNode.rem  ,iahe Arr rg }lis'disahe Arr   <              )brow'disaxf doc==NsliS.wideawides',i'100%  <  /WIDTH             )brow'disaxf doc==NsliS.wideahe Arr rg'100%  <              )browRen feDD(                wi.set.imeout(function() {
                     = trRimeupAExecent();
 tself);
                }, 0 <             )browEvent.sto(arExecRin er rg{tb 'e: arExecRin er rgtargdi: .brop            }      /**
        * @privod _hanCommand('en     * @priva {EvenSuteng}dan() &  ien"Command('en"dan() & top;
y aosComm'hrs(Examp, : bold, lHhtmleClic, lHhtmlh, ')     * @priva {EvenSuteng}de = nh(on Hanal)  iene = nh oraa gi.stdan() & sukKdasdan() &: foyTndoc e = n: 'Verdana'     * @privription Hand iissod _hanattempts top;
y setslev)l  }omdifferkncesain entiretious ser.webs sets }oil =upporeyEdinCommand('endan() &      * @m*      _fixCommand('ennction() {
an() &, e = n
           try ret deEditEomms i)browEvent.sto(adeEditEommand('en rg{tb 'e: adeEditEommand('en rgtargdi: .brorgargs:gargt if sp                Dom.(deEditEomms ==se) {
  his()browSTOP_EXEC_COMMAND
                if (.browSTOP_EXEC_COMMAND lse;
                    rn false;
                              )browRNam(and('end=dan() &<             )browRimeMarkupT 'e
an() &               Dom.)browser.webkic                if (thgetR'diWi.set e.E();
 e                        try ret eomms i)
                             Dom.)brow'disalimitand('ens{=                if (Dom.!)brow:ar bas.angBns fuByV = n(an() & )t              if ((((( omms ie;
                                                 )broworMousDi    ls)
                             Dom.k. 'e fiedis['cmd_'m+ an() &.toLowkrCast()]hi= afion() {')a&& eomm                    tar retV = nh=nedis['cmd_'m+ an() &.toLowkrCast()](e = n
<             (((( omms iretV = n[0]                  Dom.retV = n[1])t              if (((((an() &  iretV = n[1]                                    thisretV = n[2])t              if (((((e = nh=nretV = n[2]                                              thiseomm                    ;
y               if (((((.brow_'diDoc(  Command('en(an() &, e;
    e = n
<                   catch(c                if (               e {
                              )browVa('rExecEommand('en rgtion() {
                    .browunsubptiobeAelf'rExecEommand('en                    .browChange fro e              }, .bro,e) {
               .browEvent.sto(arExecEommand('en rg{tb 'e: arExecEommand('en rgtargdi: .brop                         }      /* {{{  and('endOvecririppr                   * @privod _hancmd_bold     * @priva {Evee = nhV = nhyamefte sele }omoommand('endod _ha     * @privription Hand iisst;
anmoommand('endovecriridod _ha. Ittislsal, fe seleoommand('endwhee editCommand('en(abold' ais uwet.     * @p*      _fixcmd_boldnction() {
e = n
               Dom.!)browser.webkit) {
                if (ret  Eh=n.browR'diStion(edent();
 e                  Dom. Et&& .brow_isent();
 el, 'span==t&& .brow_hasStion() &(                         Dom. E.eUylv.foyTWe Arrh== abold' a              if (((((     E.eUylv.foyTWe Arrh= ' <                     if (ret b uithgetR'diDoc(  telinkent();
 )b =              EXTRRRRRRRRRpass i EwntNode.rem<                     if (ntNsvee('htd(els[b,  E                           bsl oncdd(els[ E                                     }
                              rn fals[) {
           }      /**
        * @privod _hancmd_italic     * @priva {Evee = nhV = nhyamefte sele }omoommand('endod _ha     * @privription Hand iisst;
anmoommand('endovecriridod _ha. Ittislsal, fe seleoommand('endwhee editCommand('en(aitalic' ais uwet.     * @p*       _fixcmd_italicnction() {
e = n
               Dom.!)browser.webkit) {
                if (ret  Eh=n.browR'diStion(edent();
 e                  Dom. Et&& .brow_isent();
 el, 'span==t&& .brow_hasStion() &(                         Dom. E.eUylv.foyTS.widh== aitalic' a              if (((((     E.eUylv.foyTS.widh= ' <                     if (ret i uithgetR'diDoc(  telinkent();
 )i =              EXTRRRRRRRRRpass i EwntNode.rem<                     if (ntNsvee('htd(els[i,  E                           isl oncdd(els[ E                                     }
                              rn fals[) {
           }                    * @privod _hancmd_un erClie     * @priva {Evee = nhV = nhyamefte sele }omoommand('endod _ha     * @privription Hand iisst;
anmoommand('endovecriridod _ha. Ittislsal, fe seleoommand('endwhee editCommand('en(aun erClie' ais uwet.     * @p*      _fixcmd_un erClienction() {
e = n
               Dom.!)browser.webkit) {
                if (ret  Eh=n.browR'diStion(edent();
 e                  Dom. Et&& .brow_isent();
 el, 'span==                        Dom. E.eUylv.:extDecorat) & == aun erClie' a              if (((((     E.eUylv.:extDecorat) & =h'noie=                       e {
                             E.eUylv.:extDecorat) & =h'un erClie'                                    }
      rn fals[e;
  ]                                              rn fals[) {
           }      /**
        * @privod _hancmd_b'hkapsus     * @priva {Evee = nhV = nhyamefte sele }omoommand('endod _ha     * @privription Hand iisst;
anmoommand('endovecriridod _ha. Ittislsal, fe seleoommand('endwhee editCommand('en(ab'hkapsus')ais uwet.     * @p*      _fixcmd_b'hkapsusnction() {
e = n
               ret eomms i)
                EXTRelh=n.browR'diStion(edent();
 e              EXTRan() &  iab'hkapsus'<              Dom.)browser.webkgeckohhis.browser.webkty
 a                if (thgetRsetorKeyDS.wide  {
                   an() &  iahiliteapsus'<                            Dom.s.brow_isent();
 el, a.get')t&& s.brow_hasStion() &(                      E.eUylv.b'hkgd traCpsus  ie = n<                 thgetRseion(.rem[ E                    omms ie;
                 e {
                    0om.._isN'disaxHhtml==                        elh=n.browRtelinkIHhtmlent();
 { b'hkgd traCpsus:ee = nh                     e {
                        thgetRtelinkCntEvenent();
 )span=rg{tb'hkgd traCpsus:ee = n, apsusnc E.eUylv.apsus, foyTSize:  E.eUylv.foyTSize, foyTFamily:  E.eUylv.foyTFamilyh                        thgetRseion(.rem[)browcntEvenent();
[0]                                      omms ie;
                 
             rn fals[ omm, an() &           }      /**
        * @privod _hancmd_foreapsus     * @priva {Evee = nhV = nhyamefte sele }omoommand('endod _ha     * @privription Hand iisst;
anmoommand('endovecriridod _ha. Ittislsal, fe seleoommand('endwhee editCommand('en(aforeapsus')his uwet.     * @p*      _fixcmd_foreapsusnction() {
e = n
               ret eomms i)
                EXTRelh=n.browR'diStion(edent();
 e                               if (Dom.!)brow_isent();
 el, a.get')t&& s.brow_hasStion() &(                         addCsliS.wideel, aapsus'  e = n
<                     thgetRseion(.rem[ E                   EXTReomms ie;
                     e {
                        0om.._isN'disaxHhtml==                        EXTRelh=n.browRtelinkIHhtmlent();
 { cpsus:ee = nh                         e {
                            thgetRtelinkCntEvenent();
 )span=rg{tcpsus:ee = n, foyTSize:  E.eUylv.foyTSize, foyTFamily:  E.eUylv.foyTFamily,tb'hkgd traCpsus:e E.eUylv.b'hkgd traCpsus                             thgetRseion(.rem[)browcntEvenent();
[0]                                         EXTReomms ie;
                                      rn fals[ omm           }      /**
        * @privod _hancmd_unlink     * @priva {Evee = nhV = nhyamefte sele }omoommand('endod _ha     * @privription Hand iisst;
anmoommand('endovecriridod _ha. Ittislsal, fe seleoommand('endwhee editCommand('en(aunlink' ais uwet.     * @p*      _fixcmd_unlinknction() {
e = n
               thgetRswapEl[)browcntEvenent();
[0], 'span=rgtion() {
el                     E.sName = 'yis'non'non=               
<             rn fals[e;
  ]          }      /**
        * @privod _hancmd_telinklink     * @priva {Evee = nhV = nhyamefte sele }omoommand('endod _ha     * @privription Hand iisst;
anmoommand('endovecriridod _ha. Ittislsal, fe seleoommand('endwhee editCommand('en(atelinklink==ais uwet.     * @p*      _fixcmd_telinklinknction() {
e = n
               ret elh=n.browR'diStion(edent();
 e  _ahis;
                this.brow_hasPtNode el,h'a 
                if (.browcntEvenent();
[0]h=n.browRhasPtNode el,h'a 
               e {
  this.brow_isent();
 el,h'li 
                if (_ahisthgetR'diDoc(  telinkent();
 )a 
              if (_a.inner = st=  E.inner = s<                  E.inner = sh= ' <                  E.l oncdd(els[_a                   .browcntEvenent();
[0]h=n_a               e {
  this!)brow_isent();
 el, aa 
                if (.browRtelinkCntEvenent();
 )a 
              if (_ahisthgetRswapEl[)browcntEvenent();
[0], 'a 
              if (.browcntEvenent();
[0]h=n_a               e {
                if (.browcntEvenent();
[0]h=ne                             rn fals[e;
  ]          }      /**
        * @privod _hancmd_lHhtmleClic     * @priva {Evee = nhV = nhyamefte sele }omoommand('endod _ha     * @privription Hand iisst;
anmoommand('endovecriridod _ha. Ittislsal, fe seleoommand('endwhee editCommand('en(alHhtmleClic{ ais uwet.     * @p*      _fixcmd_lHhtmleClicnction() {
e = n
               ret eomms i)
    _0mgl= ;
    an() &  ialHhtmleClic{              EXTRelh=n.browR'diStion(edent();
 e               thise = nh=i= a')t              if (e = nh=nthget'disablankeClic{                              /      * @p* @privknowniss nhSafarimCntsus Posi Han     * @p* @privser.webtSafarim2.x             rivription Hand ieniss nhegre is( }afa omhnvtiCh w'to fiknowof t egre  }omantsus posi Handis             rilHhirid fieditef doc, soa omhnvtito e('hth }omnewlyhlHhtml fedataain entidest e('hth }afa omcan.     * @p* @pr      _fixxxxx             this.brow_isent();
 el, aimg==                if ()browcntEvenent();
[0]h=ne                    omms ie;
                 e {
                    0om.._isNR'diDoc(  queryand('enEnd', d(an() & )t              if (((((.brow_'diDoc(  Command('en(an() &, e;
    e = n
<                     doEx0mgshisthgetR'diDoc(  'dient();
sByTag = '(aimg==<                     (var doEx0; i < path0mgseth; i++) {
                            0om.!YAHOO.utal.DddChass(sel,0mgs[i], 'non'img==                if (((((((((((((YAHOO.utal.DddClass(sel,0mgs[i], 'non'img==                          ((((.browcntEvenent();
[0]h=n0mgs[i]                                            }
                }
       omms ie;
                     e {
                        0om.elh=isthgetR'diDoc(  .get                if (((((((((_0mgl= ._isNR'diDoc(  telinkent();
 )img==                          _0mgwsdiibute('hr('prn=, e = n
<                         YAHOO.utal.DddClass(sel,_0mg, 'non'img==                          thgetR'diDoc(  .get.l oncdd(els[_img                        e {
                            thgetRtelinkCntEvenent();
 )img==                          _0mgl= ._isNR'diDoc(  telinkent();
 )img==                          _0mgwsdiibute('hr('prn=, e = n
<                         YAHOO.utal.DddClass(sel,_0mg, 'non'img==                          thgetcntEvenent();
[0]wntNode.removee('htd(els[R0mg, )browcntEvenent();
[0]                                         EXTR.browcntEvenent();
[0]h=n_img                       omms ie;
                                                rn fals[ omm           }      /**
        * @privod _hancmd_lHhtmlh, '     * @priva {Evee = nhV = nhyamefte sele }omoommand('endod _ha     * @privription Hand iisst;
anmoommand('endovecriridod _ha. Ittislsal, fe seleoommand('endwhee editCommand('en(alHhtmlh, '{ ais uwet.     * @p*      _fixcmd_lHhtmlh, 'nction() {
e = n
               ret eomms i)
    an() &  ialHhtmlh, '{, Rspan ui;
    _r();
ais;
                /      * @p* @privknowniss nhSafarimantsus posi Han     * @p* @privser.webtSafarim2.x             rivription Hand ieniss nhegre is( }afa omhnvtiCh w'to fiknowof t egre  }omantsus posi Handis             rilHhirid fieditef doc, soa omhnvtito e('hth }omnewlyhlHhtml fedataain entidest e('hth }afa omcan.     * @ * @p*      _fixxxxxDom. }lisser.webkit) {
t&& s.brow_'diDoc(  queryand('enEnd', d(an() & )t              if (thgetRtelinkCntEvenent();
 )img==                  Rspan uithgetR'diDoc(  telinkent();
 )span==<                 Rspan.inner = st= e = n<                 thgetcntEvenent();
[0]wntNode.removee('htd(els[Rspan, )browcntEvenent();
[0]                    omms ie;
                 e {
  Dom.)browser.webkic                if (_r();
aisthgetR'diR fro e                  Dom._r();
.item                if (((((_r();
.item(0).o'hrr = st= e = n<                  e {
                        _r();
.yamte = s(e = n
<                                    omms ie;
                                                  rn fals[ omm           }      /**
        * @privod _hancmd_Fix(     * @priva {Evetegl ientegl fieditFix( you w'nnhaostelinkm.eg, ullus o')     * @privription Hand iisst;
a combilfteoommand('endovecriridod _ha. Ittislsal, fe sele }omamd_lHhtmlor erkdFix( 'endamd_lHhtmlunor erkdFix( od _has.     * @p*      _fixcmd_Fix(nction() {
 eO                ret eomms i)
    Fix( ui;
    l0; i ,Relh=n;
    =tthis'{              EXTRselElh=n.browR'diStion(edent();
 e  an() &  ialHhtmlor erkdFix({<                 Dom.tegl== aul{
t              if (((((an() &  i'lHhtmlunor erkdFix({<                               /      * @p* @privknowniss nhSafarim2.+ doesn't =upporeyor erkd 'endunor erkd Fix(s             rivser.webtSafarim2.x             ri ieniss nh desi.bropworkad tra is( }afa hee l oli =laosa smel fieext             ri }afahas BR'sain it,tSafarimm'to rmm'tonot p, t fe Mousindividual item;
as             riFix( item;.d iisst;
fix =lin WebK{
t(Safarim3)             ri2.6.0: Seem;
tegre workstall someniss nsh desiLix(tCelin) & setsSafari 3, rnvtmleng aosprnviouslyhworkof tSafarim2.x con.     * @p* @p*      _fixxxxx//Dom.k.}lisser.webkit) {
t&& s.brow_'diDoc(  queryand('enEnd', d(an() & )            try Dom.
.}lisser.webkit) {
t&& s.browser.webkit) {
4  his()browser.webkty
 a                     Dom.)brow_isent();
 selEl,h'li 
t&& .brow_isent();
 selElwntNode.rem,  eO                         elh=nselElwntNode.rem                      Fix( uithgetR'diDoc(  telinkent();
 )span==<                     YAHOO.utal.DddClass(sel,Fix(, 'non'non==<                     =tthis'{<                     doExFixo=  E.'dient();
sByTag = '(ali 
, p_tegl= (()browser.webkty
 at&& .brow'disantegs{=  ? 'p' : 'div==<                     (var l0; i < lpathFixeth; i++)l {
                            =tth+=n'<'m+ p_tegl+ ' 'm+ Fix[li].inner = st/sp</'m+ p_tegl+ ' '                                        EXTRFix(.inner = st= =tt<                     )browcntEvenent();
[0]h=ne                       )browcntEvenent();
[0]wntNode.removee('htd(els[Fix(, )browcntEvenent();
[0]                    e {
                        thgetRtelinkCntEvenent();
  eObtoLowkrCast()                       Fix( uithgetR'diDoc(  telinkent();
  eO                   } el(var l0; i < lpath)browcntEvenent();
eth; i++)l {
                            doExnewli uithgetR'diDoc(  telinkent();
 )li 
                          newli.inner = st= )browcntEvenent();
[li].inner = st/sp<span rTass="non'non">&nbsp;</span>&nbsp; <                     if (Fix(.l oncdd(els[newli <                     xxxxDom.li > 0                if ((((((((((((()browcntEvenent();
[li].ntNode.removeChild(els[)browcntEvenent();
[li] <                     xxxx                  }
                }
      ret b_tegl= (()browser.webkty
 a  ? '<BR>' : '<br> =              EXTRRRRRitem;
=(Fix(.firstd(els.inner = s.solit(b_teg
, i,Ritem<                     Dom.item;.th; i+ > 0                if (((((((((Fix(.inner = st= ' <                     if ((var 0; i < path0tem;.th; i++) {
                            RRRRitem uithgetR'diDoc(  telinkent();
 )li 
                          RRRRitem.inner = st= 0tem;[i]                          ((((Fix(.l oncdd(els[item <                     xxxx                  }
                         )browcntEvenent();
[0]wntNode.removee('htd(els[Fix(, )browcntEvenent();
[0]                       )browcntEvenent();
[0]
=(Fix(<                     doEx_ht= )browcntEvenent();
[0]wfirstd(els<                     _ht= addCglient();
sByCName = '.'non'non=, 'span=rg_h)[0]                      Dom. }lisser.webkit) {
)t              if (((((    .brow_'diStion() &( wsdiBastAenExt);
(_h, 1rg_hrg_h.innerText.th; i+                                     }
                     omms ie;
                 e {
                     Eh=n.browR'diStion(edent();
 e                  Dom..brow_isent();
 el,h'li 
t&& .brow_isent();
 elwntNode.rem,  eO  his()browser.webkiet&& .brow_isent();
 thgetR'diR fro ewntNodeent();
,h'li 
  his()browser.webkiet&& .brow_isent();
 el,h'ul{
  his()browser.webkiet&& .brow_isent();
 el,h'ol')    x//we workonma Fix(..                     Dom. }lisser.webkic                if (        Dom.()browser.webkiet&& .brow_isent();
 el,h'ul{
  his()browser.webkiet&& .brow_isent();
 el,h'ol')                             ((((elo=  E.'dient();
sByTag = '(ali 
[0]                                                    =tth= ' <                     if (ret Fix2s i EwntNode.rem.'dient();
sByTag = '(ali 
<                     if ((var ret j; i < jathFix2.th; i++)j{
                            RRRR=tth+=nFix2[j].inner = st/sp<br>                                                     doExnewElh=n.browR'diDoc(  telinkent();
 )span==<                         newEl.inner = st= =tt<                          EwntNode.rem.ntNode.removee('htd(els[newEl,  EwntNode.rem                        e {
                            thgetChange fro e                          thget_'diDoc(  Command('en(an() &, '',  EwntNode.rem                           thgetChange fro e                                        EXTReomms ie;
                                      Dom. }lisser.webkty
 a                if (((((e l = Ethis;
                        wi.set.imeout(function() {
                    if (((((e l Fixoh=nselftR'diDoc(  'dient();
sByTag = '(ali 
<                     if ((var ret 0; i < pathFixo.th; i++) {
                            RRRRiom.liso[i].inner = s.toLowkrCast()yu= '<br> =t              if (((((        thisliso[i].ntNode.rem.ntNode.removeChild(els[liso[i].ntNode.rem
                          RRRR                      xxxx                  }
   ,30 <                                   Dom. }lisser.webkiet&& eomm                        doExhtmlh= ' <                     0om.._isNR'diR fro ewh, ')t              if (((((    htmlh= '<li>'m+ thgetR'diR fro ewh, '/sp</li>'                       e {
                            ret taisthgetR'diR fro e :ext.solit('\n==<                         0om...th; i+ > 1                                htmlh= ' <                             (var ret 0e; i < peath).th; i++) e{
                            RRRR    htmlh+= '<li>'m+ t[ie] /sp</li>'                                                    xxxx e {
                            thisret txtaisthgetR'diR fro e :ext                          RRRRiom..xtaii= a')t              if (((((((((((((((((htmlh= '<li id="new_Fix(_item+ 'm+ .xta/sp</li>'                               e {
                            this((((htmlh= '<li 'm+ .xta/sp</li>'                                                    xxxx                  }
                }
      thgetR'diR fro ewntmte = s('<'m+ tegl+ ' 'm+ htmlh+sp</'m+ tegl+ ' '
<                     doExnew_item uithgetR'diDoc(  'dient();
ById('new_Fix(_item'
<                     iom.new_item                    if (((((e l r();
aisthgetR'diDoc(  .get.telinkTextR fro e                          r();
.ChilToent();
Text.new_item                           r();
.collapsD(e) {
                           r();
.seion( e                                                 new_item.idh= ' <                                       EXTReomms ie;
                                                rn falseomm          }      /**
        * @privod _hancmd_lHhtmlor erkdFix(     * @priva {Evee = nhV = nhyamefte sele }omoommand('endod _ha     * @privription Hand iisst;
anmoommand('endovecriridod _ha. Ittislsal, fe seleoommand('endwhee editCommand('en(alHhtmlor erkdFix( { ais uwet.     * @p*      _fixcmd_lHhtmlor erkdFix(nction() {
e = n
               rn fals[)browcmd_Fix(('ol')           }      /**
        * @privod _hancmd_lHhtmlunor erkdFix(      * @priva {Evee = nhV = nhyamefte sele }omoommand('endod _ha     * @privription Hand iisst;
anmoommand('endovecriridod _ha. Ittislsal, fe seleoommand('endwhee editCommand('en(alHhtmlunor erkdFix({ ais uwet.     * @p*      _fixcmd_lHhtmlunor erkdFix(nction() {
e = n
               rn fals[)browcmd_Fix(('ul')           }      /**
        * @privod _hancmd_foyTndoc     * @priva {Evee = nhV = nhyamefte sele }omoommand('endod _ha     * @privription Hand iisst;
anmoommand('endovecriridod _ha. Ittislsal, fe seleoommand('endwhee editCommand('en(afoyTndoc=)his uwet.     * @p*      _fixcmd_foyTndocnction() {
e = n
               ret eomms i)
                EXTRselElh=n.browR'diStion(edent();
 e<              )browcntEvenFdyTt= e = n<             Dom.=elElh&& selElwtag = 't&& s.brow_hasStion() &( t&& s.brow_isent();
 selEl,h'.get')t&& s.brow'disaxHhtml==                    YAHOO.utal.DddCsliS.wideselEl,h'foyT-family=, e = n
<                  omms ie;
                 e {
  Dom.)brow'disaxHhtml==t&& s.brow_hasStion() &(                     ret elh=n.browRtelinkIHhtmlent();
 { foyTFamily: e = nh                     omms ie;
                 
            rn fals[ omm           }      /**
        * @privod _hancmd_fdyTaize     * @priva {Evee = nhV = nhyamefte sele }omoommand('endod _ha     * @privription Hand iisst;
anmoommand('endovecriridod _ha. Ittislsal, fe seleoommand('endwhee editCommand('en(afoyTaize=)his uwet.     * @p*      _fixcmd_foyTsize: tion() {
e = n
               ret elh=n;
    go ls)
                 Eh=n.browR'diStion(edent();
 e              Dom. }lisser.webkit) {
)t              if (Dom.)browcntEvenent();
[0]                        0om.elh=isthgetcntEvenent();
[0]                            go lse;
                            YAHOO.utal.DddCsliS.wideel,h'foyTSize=, e = n
<                         thgetRseion(.rem[ E                   EXTR    thgetcntEvenent();
[0]h=ne                                     }
                              0om.go            try     this!)brow_isent();
 .browR'diStion(edent();
 e  '.get')t&& (s.brow_hasStion() &(                  if ((((( Eh=n.browR'diStion(edent();
 e                      YAHOO.utal.DddCsliS.wideel,h'foyTSize=, e = n
<                     Dom.)brow'disaxHhtml==t&&  }lisser.webkic                if (        e l raisthgetR'diR fro e                          r.collapsD(e) {
                           r.seion( e                       e {
                            thgetRseion(.rem[ E                   EXTR                   e {
  Dom.)browcntEvenent();
t&& ()browcntEvenent();
eth; i+ > 0  && (s.brow_hasStion() &(   && (s.brow'disaxHhtml==                 if (    YAHOO.utal.DddCsliS.wide)browcntEvenent();
,h'foyTSize=, e = n
<                  e {
                        0om.._isN'disaxHhtml==t&& s.brow_hasStion() &(                             elh=n.browRtelinkIHhtmlent();
 { foyTSize: e = nh                            thgetcntEvenent();
[0]h=ne                           thgetRseion(.rem[)browcntEvenent();
[0]                        e {
                            thgetRtelinkCntEvenent();
 )span=rg{'foyTSize=:ee = n, foyTFamily:  E.eUylv.foyTFamily,tapsusnc E.eUylv.apsus, b'hkgd traCpsus:e E.eUylv.b'hkgd traCpsus                             thgetRseion(.rem[)browcntEvenent();
[0]                                                                     rn fals[e;
  ]          }      /* }}}p*      _fix        * @private
        * @m*ivod _hanRswapEl     * @priva {Eve{ = sent();
 e {i ienent();
taosewaph des     * @priva {Eve{Suteng}dtag = 't ientegndoc  fieditent();
ta}afayou wishhaostelink     * @priva {Eve{Fion() {}lsal,b'hkh(on Hanal) A tion() { :asWun( n entient();
trExec ittislselinkd,idns deEdit ittislvee('hta. Anient();
trnferknceais eameftetop;iisstion() {.     * @privription Hand iisstion() { wall telinkmaiCewcent();
tin entiDOM setspopulinkmitt desi.betcont);
s  fian begr ent();
.d ienmitt dll ameumenit's e('ht.     * @p*      _fixRswapEl:gtion() {
el,dtag = ',lsal,b'hk
               ret _elh=n.browR'diDoc(  telinkent();
 tag = '               Dom.el                    _ E.inner = sh=  E.inner = s<                           this) 'e fisal,b'hkhi= afion() {')a                  sal,b'hk tself.bro, _ Ee                        try Dom.el                     EwntNode.removee('htd(els[Rel,  E                             rn fals_e           }      /**
        * @private
        * @m*ivod _hanRtelinkIHhtmlent();
     * @privription HandCelinksmaiCewc"cntEvenent();
"i.ben adds somen:ext ('endobegr  })f s)etopmakkmittseion(d')  aendeUyld') .d ienm.betuwebtcan rontin   :yp)f .     * @priva {Eve{Objon(}lsssh(on Hanal) Objon( literal sontaiuof teUylvs topl olyetop;ieiCewcent();
.     * @privrn fals{ = sent();
      * @p*      _fixRtelinkIHhtmlent();
:gtion() {
sss
               thgetRtelinkCntEvenent();
 )span=rgsss
;             ret  Eh=n.browcntEvenent();
[0]              Dom. }lisser.webkit) {
)t              if (//LittlnhSafarimHa t syhegre..                  E.inner = sh= '<span rTass="non'non">&nbsp;</span> <                  Eh=  E.firstd(els<                 .brow_'diStion() &( wsdiBastAenExt);
(el, 1,  E,  E.innerText.th; i+                                    e {
  Dom.)browser.webkiethis.browser.webkty
 a                if ( E.inner = sh= '&nbsp; <                           )browE();
 e              thgetRseion(.rem[ E,e) {
               rn false           }      /**
        * @private
        * @m*ivod _hanRtelinkCntEvenent();
     * @priva {Eve{Suteng}dtag = 't(on Hanal defaults topa)t ientegndoc  fieditent();
ta}afayou wishhaostelink     * @priva {Eve{Objon(}ltegS.widh(on Hanal) Objon( literal sontaiuof teUylvs topl olyetop;ieiCewcent();
.     * @privription Hand iisst;
a work ad tra (varentiretious ser.webniss nsh desiCommand('en.d iissod _han dll Wun(<con.>Command('en(afoyTndoc=, e;
    'non'tmp')</con.>( n entigi.stdstion() &.     * @priItt dll .ben searcsi.betd();();
t oraanient();
t desi.betfoyT-family smeltop<strong>non'tmp</strong>dsetsvee('hth }a
t desian begr span  }afahas  begr in ormin) & in it,t.ben aseignp;ieiCewcspan  op;iei     * @pri<con.>.browcntEvenent();
</con.>(array, soa omnowmhnvtient();
trnferknces  op;ieient();
s( }afa oit jux( oodifita. Atp;iisspoinfa omcantuwe standardiDOM ('eipulin) { :asce frop;iemdasd omseetfit.     * @p*      _fixRtelinkCntEvenent();
nction() {
 eO = ',ltegS.wid
               tag = 't= (()ag = '  ? tag = 't: 'a 
              ret tass iC
                     Eh= []                  _d()h=n.browR'diDoc(                            Dom. }liscntEvenFdyT            try     this!)egS.wid
                       tagS.widh= {}<                                   .agS.wid.foyTFamilyh=n.browcntEvenFdyT<                 .browcntEvenFdyTt= ;
                              )browcntEvenent();
his[                ret _elCelinkt=ction() {
 eO = ',ltegS.wid
                   ret elh=n;
  <                 .ag = 't= (()ag = '  ? tag = 't: 'span==<                 .ag = 't= tag = '.toLowkrCast()<                 s decsi()ag = '                        cast 'h1':                     cast 'h2':                     cast 'h3':                     cast 'h4':                     cast 'h5':                     cast 'h6':                         elh=n_d() telinkent();
 tag = '                           belik                      default:                         elh=n_d() telinkent();
 tag = '                           Dom.teg = 't=i= aspan==t                          thisYAHOO.utal.DddClass(sel,el,h'non'tag-'m+ teg = '                           thisYAHOO.utal.DddClass(sel,el,h'non'tag 
                          RRRR E.ediibute('hr('tag , teg = '                                                      (var ret ktin eegS.wid
                               Dom.YAHOO.l frChasOwnPrty
 ty(eegS.wid, k                             ((((RRRR E.e.wid[k]t= tagS.wid[k]                                                    xxxx                  }
      belik                                    rn false                               thiss.brow_hasStion() &(                     0om.._isNR'diDoc(  queryand('enEnd', d(alHhtmleClic{ )t              if (((((.brow_'diDoc(  Command('en(alHhtmleClic{  e;
    'non'tmp'img==                      doEx0mgshisthgetR'diDoc(  'dient();
sByTag = '(aimg==<                     (var doExj; i < jath0mgseth; i++)j{
                            Dom.imgs[j] 'diibute('hr('prn=, 2)yu= 'non'tmp'img==                           ((((elo= _elCelink
 eO = ',ltegS.wid
                          RRRRimgs[j] ntNode.removee('htd(els[el,himgs[j]=                          ((((.browcntEvenent();
[)browcntEvenent();
eth; i+]h=ne                                             }
                }
   e {
                        0om.._isNcntEvene.sto)t              if (((((    .ass iYAHOO.utal.e.sto 'diTargdi.._isNcntEvene.sto)                       e {
                            //FobtSafari..                         .ass ithgetR'diDoc(  .get                                                            }
                    Dom.tet)                       /*                     rivknowniss nhSafarimCntsus Posi Han     * @p* @p        rivser.webtSafarim2.x                     rivription Hand ieniss nhegre is( }afa omhnvtiCh w'to fiknowof t egre  }omantsus posi Handis                     rilHhirid fieditef doc, soa omhnvtito e('hth }omnewlyhlHhtml fedataain entidest e('hth }afa omcan.     * @ * @p        r/     * @ * @p        elo= _elCelink
 eO = ',ltegS.wid
                      Dom..brow_isent();
 tet  '.get')this.brow_isent();
 tet  'h, '{                             Dom..brow_isent();
 tet  'h, '{                                 .ass ithgetR'diDoc(  .get                      xxxx                  }
      .assl oncdd(els[ E                        e {
  Dom.)assnextSiblof )t              if (((((    .as ntNode.remolHhtmlBeEdit
el,dtassnextSiblof )                       e {
                            tas ntNode.remol oncdd(els[ E                                     }
      //)browcntEvenent();
hise                       )browcntEvenent();
[)browcntEvenent();
eth; i+]h=ne                       ._isNcntEvene.stoh=n;
  <                     Dom. }lisser.webkit) {
)t              if (((((    //FobcnhSafarimto E();
p;ieiCewcent();
                         tbrow_'diStion() &( wsdiBastAenExt);
(el, 0,  E, 0 <                         Dom. }lisser.webkit) {
3                if ((((((((((((()brow_'diStion() &( wcollapsDToStas( e                           e {
                            this)brow_'diStion() &( wcollapsDe  {
                                                               }
                 e {
                    //FobcnhCSS S.wiof t(varenisdan() &...                 thgetRsetorKeyDS.wide  {
                   .brow_'diDoc(  Command('en(afoyTndoc=, e;
    'non'tmp')                  ret _tmph= []  __tmp  __ {
h= [afoyT=, 'span=rg'i',h'.',h'u'                    this!)brow_isent();
 .browR'diStion(edent();
 e  '.get')                if (((((__ {
[__ {
eth; i+]h=nthgetR'diDoc(  'dient();
sByTag = '(.browR'diStion(edent();
 e.teg = '                       __ {
[__ {
eth; i+]h=nthgetR'diDoc(  'dient();
sByTag = '(.browR'diStion(edent();
 e.ntNode.remoteg = '                                 }
  (var doEx_ {
h=  < _ {
h< __ {
eth; i+< _ {
{
                        ret _tmp1h=nthgetR'diDoc(  'dient();
sByTag = '(__ {
[_ {
]=                      (var doExe; i < eath_tmp1eth; i+< e{
                            _tmp[_tmpeth; i+]h=n_tmp1[ ]                                    }
                                  }
  (var doEx0; i < path_tmpeth; i++) {
                        Dom.(YAHOO.utal.DddCgliS.wide_tmp[i], 'foyT-family=)yu= 'non'tmp')thise_tmp[i].f'hth&& (_tmp[i].f'hthu= 'non'tmp')                             Dom..eg = 't!i= aspan==t                          thiselo= _elCelink
 eO = ',ltegS.wid
                           e {
                            thiselo= _elCelink
_tmp[i]. eO = ',ltegS.wid
                                                    el.inner = sh= _tmp[i].inner = s<                         Dom..brow_isent();
 _tmp[i], 'ol') his()brow_isent();
 _tmp[i], 'ul')                             ((((doExfch= _tmp[i].'dient();
sByTag = '(ali 
[0]                              _tmp[i].eUylv.foyTFamilyh i'lHegri({<                             fc.eUylv.foyTFamilyh i'lHegri({<                             el.inner = sh= fc.inner = s<                             fc.inner = st= ' <                     if (    fc.l oncdd(els[ E                               .browcntEvenent();
[)browcntEvenent();
eth; i+]h=ne                            e {
  this.brow_isent();
 _tmp[i], 'li 
                if (((((((((((((_tmp[i].inner = st= ' <                     if (    _tmp[i].l oncdd(els[ E                               _tmp[i].eUylv.foyTFamilyh i'lHegri({<                             .browcntEvenent();
[)browcntEvenent();
eth; i+]h=ne                            e {
                                Dom._tmp[i].ntNode.rem
                           ((((RRRR_tmp[i].ntNode.removee('htd(els[el,h_tmp[i]                                   .browcntEvenent();
[)browcntEvenent();
eth; i+]h=ne                                   .browcntEvene.stoh=n;
  <                                 Dom. }lisser.webkit) {
)t              if (((((                //FobcnhSafarimto E();
p;ieiCewcent();
                                     .brow_'diStion() &( wsdiBastAenExt);
(el, 0,  E, 0 <                                     Dom. }lisser.webkit) {
3                if (((((((((((((            .brow_'diStion() &( wcollapsDToStas( e                                       e {
                            this((((        .brow_'diStion() &( wcollapsDe  {
                                                                                                           Dom. }lisser.webkiet&& tagS.widh&& tagS.wid.foyTSize                if (((((((((((((        .brow_'diStion() &( wempty e                                                                    Dom. }lisser.webkgecko                if (((((((((((((        .brow_'diStion() &( wcollapsDToStas( e                                                                                      xxxx                  }
                }
                }
  e l Fen uithgetcntEvenent();
eth; i++             }
  (var doExo; i < oathFen< o{
                        Dom.(o + 1  !=hFen   x//Skie Mouslast oneain entiFix(     * @p                Dom..browcntEvenent();
[o]t&&  }liscntEvenent();
[o]snextSiblof )t              if (((((        Dom..brow_isent();
 .browcntEvenent();
[o]  '.r 
                if (((((((((((((((((.browcntEvenent();
[)browcntEvenent();
eth; i+]h=n }liscntEvenent();
[o]snextSiblof                                                     xxxx                  }
                                            }      /**
        * @privod _hansave = s     * @privription HandClean
p;iei = st desi.betclean = stod _han.ben e('hts( }afasuteng b'hkain op;iei:exttNoa.     * @privrn falsSuteng     * @pr/     * @ save = snction() {

               ret htmlh=  }lisclean = s e              Dom. }lis_:exttNoa)t              if (thget'disaent();
').e = nh=nhtml               e {
                if (.brow'disaent();
').inner = st= html                            Dom. }lis'disasaveEl') !=isthget'disaent();
')
                   ret outaisthget'disasaveEl')                  Dom.L frCisSuteng(out
                if (((((outaisDddCgli(out
                                }
  Dom.out
                       Dom.out.tag = '.toLowkrCast()t=i= a:exttNoa==t                          out.e = nh=nhtml                       e {
                            out.inner = st= html                                    }
                              rn falshtml          }      /**
        * @privod _hansetorKeyD = s     * @priva {Eve{Suteng}dincomeng = st ienhtmlhcont);
mto loadain op;ieierKeyD     * @privription HandLoadsi = stin op;ieierKeyDs .get     * @pr/     * @ setorKeyD = snction() {
incomeng = s
               ret htmlh=  }lis_cleanIncomeng = s
incomeng = s
              htmlh= htmlovee('ht(/RIGHT_BRACKET/gi  '{')              htmlh= htmlovee('ht(/LEFT_BRACKET/gi  '}')              thgetR'diDoc(  .get.inner = st= html              thgetChange fro e          }      /**
        * @privod _hangetorKeyD = s     * @privription HandGet
p;ieiunprohtssed/unfilterkd  = st sele }omorKeyD     * @pr/     * @ getorKeyD = snction() {

               try                   ret bs ithgetR'diDoc(  .get                  Dom.bt=i= ;
  =t                      rn fals;
  <                                   rn falsthgetR'diDoc(  .get.inner = s               ecaecsi(e                if (rn fals' <                       }      /**
        * @privod _hanshow     * @privription Hand iissod _hanneeds  opbelsal, feifieditorKeyD was hidden (likrkonma TabViewcus Panel). Ittislueftetopresmelt}omorKeyDtrExec beeng onma sontaiugr  }afa as smeltopdise('tonont.     * @p*      _fixshownction() {

               Dom. }lisser.webkgecko                if (thgetRsetDeeignMrem['on==<                 )browE();
 e                            Dom. }lisser.webkit) {
)t              if (e l = Ethis;
                    wi.set.imeout(function() {
                    if (selftRimeInitialCont);
 tselfself
                   , 10 <                           //Addeng aiisswall tlo
  sel  begr orKeyD wi.set'sdwhee showeng aiissont.     * @p    Dom. }liscntEvenWi.set                if ()browclo
 Wi.set e                            //Putieditef doc b'hkain e('ht             thget'disaxf doc')CsliS.wide'posi Han=, 'static')              thget'disaxf doc')CsliS.wide'lefT=, ''e          }      /**
        * @privod _hanhide     * @privription Hand iissod _hanneeds  opbelsal, feifieditorKeyD is  opbelhidden (likrkonma TabViewcus Panel). Ittshouldpbelsal, fe:asclear  ut(funs 'endalo
  open erKeyD wi.sets.     * @p*      _fixhidenction() {

               //Addeng aiisswall tlo
  sel  begr orKeyD wi.set's.     * @p    Dom. }liscntEvenWi.set                if ()browclo
 Wi.set e                            Dom. }lis_fix.remsout(t)                   clearout(func }lis_fix.remsout(t)                  .brow_fix.remsout(tt= ;
                              Dom. }lis_Change froout(t)                   clearout(func }lis_Change froout(t)                  .brow_Change froout(tt= ;
                              )brow_lastNhange fro; i <             //Moveieditef doc offd fieditptieen, soa }afain sontaiugrsh desivisiblotylhidden, IEswall not covec  begr ent();
s.     * @p    thget'disaxf doc')CsliS.wide'posi Han=, 'absolutc{               thget'disaxf doc')CsliS.wide'lefT=, '-9999px'e          }      /**
        * @privod _han_cleanIncomeng = s     * @priva {Eve{Suteng}dhtmlhTieiunfilterkd  = s     * @privription HandProhtssp;iei = st desia fewcregexes  opcleanmittup aendeUabilizeieditenpu
     * @privrn fals{Suteng}dTieifilterkd  = s     * @pr      _fixRtleanIncomeng = snction() {
h, ')t              htmlh= htmlovee('ht(/{/gi  'RIGHT_BRACKET')              htmlh= htmlovee('ht(/}/gi  'LEFT_BRACKET'e<              htmlh= htmlovee('ht(/<strong([^>]*)>/gi  '<b$1 '
<             htmlh= htmlovee('ht(/<\/strong>/gi  '</b '
<                 //vee('hthembed deEdit em check             htmlh= htmlovee('ht(/<embed([^>]*)>/gi  '<YUI_EMBED$1 '
<             htmlh= htmlovee('ht(/<\/embed>/gi  '</YUI_EMBED>'e<              htmlh= htmlovee('ht(/<em([^>]*)>/gi  '<i$1 '
<             htmlh= htmlovee('ht(/<\/em>/gi  '</i '
<             htmlh= htmlovee('ht(/_moz_dirty=""/gi  ''
<                          //Putiembed tegs b'hkain..             htmlh= htmlovee('ht(/<YUI_EMBED([^>]*)>/gi  '<embed$1 '
<             htmlh= htmlovee('ht(/<\/YUI_EMBED>/gi  '</embed>'
<             Dom. }lis'disae('inText')
                   htmlh= htmlovee('ht(/\n/g, '<br> =ovee('ht(/\r/g, '<br> =                  htmlh= htmlovee('ht(/  /gi  '&nbsp;&nbsp; 
< //Ree('hthsel dou')  sp'hts                 htmlh= htmlovee('ht(/\t/gi  '&nbsp;&nbsp;&nbsp;&nbsp; 
< //Ree('hthsel Uabs                           //Removof tStion  Tags  sele }omErKeyD     * @p    htmlh= htmlovee('ht(/<ption ([^>]*)>/gi  '<bad>'
<             htmlh= htmlovee('ht(/<\/stion ([^>]*)>/gi  '</bad>'
<             htmlh= htmlovee('ht(/&lt;stion ([^>]*)&gt;/gi  '<bad>'
<             htmlh= htmlovee('ht(/&lt;\/stion ([^>]*)&gt;/gi  '</bad>'
<             //Ree('hthentiFineifeeds             htmlh= htmlovee('ht(/\r\n/g, '<YUI_LF> =ovee('ht(/\n/g, '<YUI_LF> =ovee('ht(/\r/g, '<YUI_LF> =<                          //Remove Bad  = stent();
s((ueftetopbitption  Chans)             htmlh= htmlovee('ht(CewcRegExp('<bad([^>]*)>(.*?)<\/bad>', 'gi 
, ''
<             //Ree('hthentiFinesifeeds             htmlh= htmlovee('ht(/<YUI_LF>/g, '\n==<             rn falshtml          }      /**
        * @privod _hanclean = s     * @priva {Eve{Suteng}dhtmlhTieiunfilterkd  = s     * @privription HandProhtssp;iei = st desia fewcregexes  opcleanmittup aendeUabilizeieditfunpu
     * @privrn fals{Suteng}dTieifilterkd  = s     * @pr      _fixclean = snction() {
h, ')t              //Stas( Filterof tOunpu
     * @p    //Begin RegExs..     * @p    Dom.!h, ')t               }
  htmlh=  }lisgetorKeyD = s e                            e l markupaisthget'disamarkup'
<             //Makkmsomenb'hkups...             htmlh=  }lispre_filter_Finebeliks
h, ', markupe<              //Filter MS Word             htmlh=  }lisfilter_msword
h, ')<  		    htmlh= htmlovee('ht(/<img([^>]*)\/>/gi  '<YUI_IMG$1 '
< 		    htmlh= htmlovee('ht(/<img([^>]*)>/gi  '<YUI_IMG$1 '
<  		    htmlh= htmlovee('ht(/<inpu
([^>]*)\/>/gi  '<YUI_INPUT$1 '
< 		    htmlh= htmlovee('ht(/<inpu
([^>]*)>/gi  '<YUI_INPUT$1 '
<  		    htmlh= htmlovee('ht(/<ul([^>]*)>/gi  '<YUI_UL$1 '
< 		    htmlh= htmlovee('ht(/<\/ul>/gi  '<\/YUI_UL '
< 		    htmlh= htmlovee('ht(/<blockquote([^>]*)>/gi  '<YUI_BQ$1 '
< 		    htmlh= htmlovee('ht(/<\/blockquote>/gi  '<\/YUI_BQ '
<  		    htmlh= htmlovee('ht(/<embed([^>]*)>/gi  '<YUI_EMBED$1 '
< 		    htmlh= htmlovee('ht(/<\/embed>/gi  '<\/YUI_EMBED>'e<              //Convtml b aendi tegs aosetrong aendevetegs     * @p    Dom.(markupai= asemantic') his(markupai= axh, '{                     //htmlh= htmlovee('ht(/<i(\s+[^>]*)?>/gi  "<em$1 "=                  htmlh= htmlovee('ht(/<i([^>]*)>/gi  "<em$1 "=                  htmlh= htmlovee('ht(/<\/i /gi  '</em> =                  //htmlh= htmlovee('ht(/<b(\s+[^>]*)?>/gi  "<etrong$1 "=                  htmlh= htmlovee('ht(/<b([^>]*)>/gi  "<etrong$1 "=                  htmlh= htmlovee('ht(/<\/b /gi  '</strong> =              }              htmlh= htmlovee('ht(/_moz_dirty=""/gi  ''
<              //normilizeisutekd _rough             htmlh= htmlovee('ht(/<sutekd/gi  '<span eUylv=":ext-decorin) {:iFine- _rough;"'
<             htmlh= htmlovee('ht(/\/strekd /gi  '/span> =<                                       //Cast ge freng     * @pppppDom. }lisser.webkic                if (htmlh= htmlovee('ht(/:ext-decorin) {/gi  ':ext-decorin) { =                  htmlh= htmlovee('ht(/foyT-weight/gi  'foyT-weight =                  htmlh= htmlovee('ht(/_width="([^>]*)"/gi  ''
<                 htmlh= htmlovee('ht(/_height="([^>]*)"/gi  ''
<                 //CleanupaIClic URL's                 e l urlh=  }lis_bastHREFovee('ht(/\//gi  '\\/ =              EXTRRRRRr
ais;ewcRegExp('src="'m+ url, 'gi 
<                 htmlh= htmlovee('ht(r   'src="'=              } 		    htmlh= htmlovee('ht(/<foyT/gi  '<foyT=
< 		    htmlh= htmlovee('ht(/<\/foyT /gi  '</foyT =
< 		    htmlh= htmlovee('ht(/<span/gi  '<span=
< 		    htmlh= htmlovee('ht(/<\/span>/gi  '</span> =<             Dom.(markupai= asemantic') his(markupai= axh, '{  his(markupai= acss')
                   htmlh= htmlovee('ht(CewcRegExp('<foyT([^>]*)f'ht="([^>]*)">(.*?)<\/foyT =, 'gi 
, '<span $1 eUylv="foyT-family: $2;">$3</span> =<                 htmlh= htmlovee('ht(/<u/gi  '<span eUylv=":ext-decorin) {:iunderFine;"'
<                 Dom. }lisser.webkit) {
)t              if (((((htmlh= htmlovee('ht(CewcRegExp('<span rTass="Apple-eUylv-span" eUylv="foyT-weight: bold;">([^>]*)<\/span>=, 'gi 
, '<strong>$1</strong> =              if (((((htmlh= htmlovee('ht(CewcRegExp('<span rTass="Apple-eUylv-span" eUylv="foyT-eUylv:mitilic;">([^>]*)<\/span>=, 'gi 
, '<em>$1</em> =                                    htmlh= htmlovee('ht(/\/u /gi  '/span> =<                 Dom.markupai= acss')t              if (((((htmlh= htmlovee('ht(/<em([^>]*)>/gi  '<i$1 '
<             if (((((htmlh= htmlovee('ht(/<\/em>/gi  '</i '
<             if (((((htmlh= htmlovee('ht(/<strong([^>]*)>/gi  '<b$1 '
<             if (((((htmlh= htmlovee('ht(/<\/strong>/gi  '</b '
<             if (((((htmlh= htmlovee('ht(/<b/gi  '<span eUylv="foyT-weight: bold;"'
<             if (((((htmlh= htmlovee('ht(/\/b /gi  '/span> =<                 ((((htmlh= htmlovee('ht(/<i/gi  '<span eUylv="foyT-eUylv:mitilic;"'
<             if (((((htmlh= htmlovee('ht(/\/i /gi  '/span> =<                                   htmlh= htmlovee('ht(/  /gi  '  
< //Ree('hthsel dou')  sp'htsdsetsvee('hth desia senglt              e {
    		    ((((htmlh= htmlovee('ht(/<u/gi  '<u=
< 		    ((((htmlh= htmlovee('ht(/\/u /gi  '/u> =              } 		    htmlh= htmlovee('ht(/<ol([^>]*)>/gi  '<ol$1 '
< 		    htmlh= htmlovee('ht(/\/ol>/gi  '/ol>=
< 		    htmlh= htmlovee('ht(/<li/gi  '<li 
< 		    htmlh= htmlovee('ht(/\/li /gi  '/li '
<             htmlh=  }lisfilter_safari
h, ')<              htmlh=  }lisfilter_internals
h, ')<              htmlh=  }lisfilter_sel_rgb
h, ')<              //Ree('hthournb'hkupst desi.betreal  })f              htmlh=  }lispost_filter_Finebeliks
h, ', markupe<              Dom.markupai= axh, '{    		    ((((htmlh= htmlovee('ht(/<YUI_IMG([^>]*)>/g  '<img $1 />=
< 		    ((((htmlh= htmlovee('ht(/<YUI_INPUT([^>]*)>/g  '<inpu
 $1 />=
<              e {
    		    ((((htmlh= htmlovee('ht(/<YUI_IMG([^>]*)>/g  '<img $1>=
< 		    ((((htmlh= htmlovee('ht(/<YUI_INPUT([^>]*)>/g  '<inpu
 $1> =              } 		    htmlh= htmlovee('ht(/<YUI_UL([^>]*)>/g  '<ul$1 '
< 		    htmlh= htmlovee('ht(/<\/YUI_UL /g  '<\/ul>'e<              htmlh=  }lisfilter_invilid_Fix(s
h, ')<  		    htmlh= htmlovee('ht(/<YUI_BQ([^>]*)>/g  '<blockquote$1 '
< 		    htmlh= htmlovee('ht(/<\/YUI_BQ /g  '<\/blockquote>'
<  		    htmlh= htmlovee('ht(/<YUI_EMBED([^>]*)>/g  '<embed$1 '
< 		    htmlh= htmlovee('ht(/<\/YUI_EMBED>/g  '<\/embed>'
<                          // iissshouldpfix &amp;'sain URL's             htmlh= htmlovee('ht(/ &amp; /gi  ' YUI_AMP '
<             htmlh= htmlovee('ht(/ &amp;/gi  ' YUI_AMP_F '
<             htmlh= htmlovee('ht(/&amp; /gi  ' YUI_AMP_R '
<             htmlh= htmlovee('ht(/&amp;/gi  '&'
<             htmlh= htmlovee('ht(/ YUI_AMP /gi  ' &amp; '
<             htmlh= htmlovee('ht(/ YUI_AMP_F /gi  ' &amp;'
<             htmlh= htmlovee('ht(/ YUI_AMP_R /gi  '&amp; '
<              // rimieditfunpu
, removof twhitesp'ht  sele }ombeginuof taendend             htmlh= YAHOO.l frCtrim
h, ')<              Dom. }lis'disaveChilLineBeliks')
                   htmlh= htmlovee('ht(/\n/g, ' =ovee('ht(/\r/g, ' =                  htmlh= htmlovee('ht(/  /gi  '  
< //Ree('hthsel dou')  sp'htsdsetsvee('hth desia senglt                                        (var doExvain enlisinvilid = s
                   Dom.YAHOO.l frChasOwnPrty
 ty(enlisinvilid = s, v
                if (((((Dom.L frCisObjon((v=t&& v.keepCont);
s
                           htmlh= htmlovee('ht(CewcRegExp('<'m+ vl+ '([^>]*)>(.*?)<\/'m+ vl+ '>=, 'gi 
, '$1'
<             if ((((( e {
                            htmlh= htmlovee('ht(CewcRegExp('<'m+ vl+ '([^>]*)>(.*?)<\/'m+ vl+ '>=, 'gi 
, ''
<             if (((((                                }              /* LATER -- AddiDOM ('eipulin) {             console.log
h, ')<             doExfragh= d();();
 telinkD();();
Frag();
 e              frag.inner = st= html               doExp
h= frag.'dient();
sByTag = '(ap =              EXTRFen uipseth; i++             (var ret 0; i < pathFen<  {
                    doExp
2 uips[i].'dient();
sByTag = '(ap')                  Dom.px2.th; i+
                                                                                     htmlh= frag.inner = s;             console.log
h, ')<             */              )browfiree.sto('clean = s=rg{ :ypv:m'clean = s=rgtargdi: )bro, html: htmlh})<              rn falshtml          }      /**
        * @privod _hanfilter_msword     * @priva {EveSuteng htmlhTiei = stsuteng to Eilter     * @privription HandFilters(outamsword htmlhabute('hrsdsets begr junk. Acte
   h desiEilterWord:e) {
ain sonfig     * @pr/     * @ filter_mswordnction() {
h, ')t              this!)brow'disaEilterWord')
                   rn falshtml                            //Remove  }omms(o:etegs     * @p    htmlh= htmlovee('ht(/<o:p>\s*<\/o:p>/g, ' =              htmlh= htmlovee('ht(/<o:p>[\s\S]*?<\/o:p>/g, '&nbsp; 
<              //Remove  }omms(w:etegs     * @p    htmlh= htmlovee('ht( /<w:[^>]*>[\s\S]*?<\/w:[^>]*>/gi  ''
<              //Remove mso-?teUylvs.             htmlh= htmlovee('ht( /\s*mso-[^:]+:[^;"]+;?/gi  ''
<              //Remove mdit bogus MS eUylvs.             htmlh= htmlovee('ht( /\s*MARGIN: 0cm 0cm 0pt\s*;/gi  ''
<             htmlh= htmlovee('ht( /\s*MARGIN: 0cm 0cm 0pt\s*"/gi  "\""=              htmlh= htmlovee('ht( /\s*TEXT-INDENT: 0cm\s*;/gi  ''
<             htmlh= htmlovee('ht( /\s*TEXT-INDENT: 0cm\s*"/gi  "\""=              htmlh= htmlovee('ht( /\s*PAGE-BREAK-BEFORE: [^\s;]+;?"/gi  "\""=              htmlh= htmlovee('ht( /\s*FONT-VARIANT: [^\s;]+;?"/gi  "\"" =              htmlh= htmlovee('ht( /\s*tab-stops:[^;"]*;?/gi  ''
<             htmlh= htmlovee('ht( /\s*tab-stops:[^"]*/gi  ''
<              //Remove X stdeclarin) {s     * @p    htmlh= htmlovee('ht(/<\\?\?xml[^>]*>/gi  ''
<              //Remove l fr     * @p    htmlh= htmlovee('ht(/<(\w[^>]*) l fr=([^ |>]*)([^>]*)/gi  "<$1$3"
<              //Remove l frulic tegs     * @p    htmlh= htmlovee('ht( /<(\w[^>]*) l frulic=([^ |>]*)([^>]*)/gi  "<$1$3"
<              //Remove onmouseovec sets nmouseoutiev);
s(( seleMS Word som();
s(effon()             htmlh= htmlovee('ht( /<(\w[^>]*) onmouseovec="([^\"]*)"([^>]*)/gi  "<$1$3"
<             htmlh= htmlovee('ht( /<(\w[^>]*) onmouseout="([^\"]*)"([^>]*)/gi  "<$1$3"
<                          rn falshtml          }      /**
        * @privod _hanfilter_invilid_Fix(s     * @priva {EveSuteng htmlhTiei = stsuteng to Eilter     * @privription HandFilters(invilid ol setsuliFix( markup,tapnvtmls )bro: <li </li><ol>..</ol>etop;iis: <li </li><li><ol>..</ol></li>     * @pr/     * @ filter_invilid_Fix(snction() {
h, ')t              htmlh= htmlovee('ht(/<\/li \n/gi  '</li '
<              htmlh= htmlovee('ht(/<\/li <ol>/gi  '</li><li><ol>'
<             htmlh= htmlovee('ht(/<\/ol>/gi  '</ol></li>'
<             htmlh= htmlovee('ht(/<\/ol><\/li \n/gi  "</ol>"
<              htmlh= htmlovee('ht(/<\/li <ul>/gi  '</li><li><ul>'
<             htmlh= htmlovee('ht(/<\/ul>/gi  '</ul></li>'
<             htmlh= htmlovee('ht(/<\/ul><\/li \n?/gi  "</ul>"
<              htmlh= htmlovee('ht(/<\/li /gi  "</li "
<             htmlh= htmlovee('ht(/<\/ol>/gi  "</ol>"
<             htmlh= htmlovee('ht(/<ol>/gi  "<ol>"
<             htmlh= htmlovee('ht(/<ul>/gi  "<ul>"
<             rn falshtml          }      /**
        * @privod _hanfilter_safari     * @priva {EveSuteng htmlhTiei = stsuteng to Eilter     * @privription HandFilters(sutengs(specific to Safari     * @privrn falsSuteng     * @pr/     * @ filter_safarinction() {
h, ')t              this }lisser.webkit) {
)t              if (//<span rTass="Apple-tab-span" eUylv="white-sp'ht:pre">	</span>                 htmlh= htmlovee('ht(/<span rTass="Apple-tab-span" eUylv="white-sp'ht:pre">([^>])<\/span>/gi  '&nbsp;&nbsp;&nbsp;&nbsp; 
<                 htmlh= htmlovee('ht(/Apple-eUylv-span/gi  ''
<                 htmlh= htmlovee('ht(/eUylv="Fine-height: normil;"/gi  ''
<                 htmlh= htmlovee('ht(/non'wk-div/gi  ''
<                 htmlh= htmlovee('ht(/non'wk-p/gi  ''
<               if (//Remove bogus LI's                 htmlh= htmlovee('ht(/<li><\/li /gi  ' =<                 htmlh= htmlovee('ht(/<li  <\/li /gi  ' =<                 htmlh= htmlovee('ht(/<li   <\/li /gi  ' =<                 //Remove bogus DIV's - updinkd  selejux( removof t.betdiv'setopree('hof t/divh desia belik                 Dom. }lis'disaetegs')
   		    ((((    htmlh= htmlovee('ht(/<div([^>]*)>/g  '<p$1 '
< 				    htmlh= htmlovee('ht(/<\/div>/gi  '</p> =<                  e {
                        //htmlh= htmlovee('ht(/<div>/gi  '<br> =                      htmlh= htmlovee('ht(/<div([^>]*)>([ tnr]*)<\/div>/gi  '<br> =  				    htmlh= htmlovee('ht(/<\/div>/gi  ' =<                                             rn falshtml          }      /**
        * @privod _hanfilter_internals     * @priva {EveSuteng htmlhTiei = stsuteng to Eilter     * @privription HandFilters(internal RTE(sutengs(setsbogus abutsd omdon'fa an
     * @privrn falsSuteng     * @pr/     * @ filter_internalsnction() {
h, ')t  		    htmlh= htmlovee('ht(/\r/g, ' =              //Fix(suuffd omdon'fa an
 	    ((((htmlh= htmlovee('ht(/<\/?(.get|head|h, ')[^>]*>/gi  ''
<             //Fix(last BRain LI 		    htmlh= htmlovee('ht(/<YUI_BR><\/li /gi  '</li '
<  		    htmlh= htmlovee('ht(/non'tag-span/gi  ''
< 		    htmlh= htmlovee('ht(/non'tag/gi  ''
< 		    htmlh= htmlovee('ht(/non'non/gi  ''
< 		    htmlh= htmlovee('ht(/non'img/gi  ''
< 		    htmlh= htmlovee('ht(/ teg="span"/gi  ''
< 		    htmlh= htmlovee('ht(/ rTass=""/gi  ''
< 		    htmlh= htmlovee('ht(/ eUylv=""/gi  ''
< 		    htmlh= htmlovee('ht(/ rTass=" "/gi  ''
< 		    htmlh= htmlovee('ht(/ rTass="  "/gi  ''
< 		    htmlh= htmlovee('ht(/ targdi=""/gi  ''
< 		    htmlh= htmlovee('ht(/ titlv=""/gi  ''
<              this }lisser.webkic    		    ((((htmlh= htmlovee('ht(/ rTass= /gi  ''
< 		    ((((htmlh= htmlovee('ht(/ rTass= >/gi  ''
<                                        rn falshtml          }      /**
        * @privod _hanfilter_sel_rgb     * @priva {EveSuteng suthTiei = stsuteng to Eilter     * @privription HandCpnvtmls sel RGBtapsus(sutengs(f tra in eameftesuteng to a hex apsus, examplv:meUylv="apsusncrgb
0, 255, 0 "tapnvtmls )omeUylv="apsusnc#00ff00"     * @privrn falsSuteng     * @pr/     * @ filter_sel_rgbnction() {
sut
               ret expais;ewcRegExp("rgb\\s*?\\(\\s*?([0-9]+).*?,\\s*?([0-9]+).*?,\\s*?([0-9]+).*?\\)"  "gi"
<             ret artt= sut.maecs(exp=<             Dom.L frCisArray(art)
                   (var ret 0; i < patharteth; i++) {
                        ret apsus(=  }lisfilter_rgb
art[i]                       suth= sut.vee('ht(art[i].toSuteng(),tapsus=<                                                          rn falssut          }      /**
        * @privod _hanfilter_rgb     * @priva {EveSuteng ssshTieiCSS suteng sontaiuof trgb
#,#,#=<         rivription HandCpnvtmls sn RGBtapsus(suteng to a hex apsus, examplv:mrgb
0, 255, 0 tapnvtmls )om#00ff00     * @privrn falsSuteng     * @pr/     * @ filter_rgbnction() {
sss
               Dom.sss.toLowkrCast().indexOf('rgb') != -1
                   ret expais;ewcRegExp("(.*?)rgb\\s*?\\(\\s*?([0-9]+).*?,\\s*?([0-9]+).*?,\\s*?([0-9]+).*?\\)(.*?)"  "gi"
<                 ret rgbaissss.vee('ht(exp  "$1,$2,$3,$4,$5").spliisa,'
<                              Dom.rgbeth; i+ai= 5                        ret th= a {seInt.rgb[1], 10 .toSuteng(16                       ret gh= a {seInt.rgb[2], 10 .toSuteng(16                       ret bh= a {seInt.rgb[3], 10 .toSuteng(16                        th= reth; i+ai= 1 ? '0'm+ r :mr                      gh= geth; i+ai= 1 ? '0'm+ g :m                       bh= beth; i+ai= 1 ? '0'm+ b :mb                       sssh= "#"m+ r + g +mb                                              rn falssss          }      /**
        * @privod _hanpre_filter_Finebeliks     * @priva {EveSuteng htmlhTiei = stto Eilter     * @priva {EveSuteng markupaTieimarkupa:ypvtto Eiltertto         rivription Hand = stPredFilter     * @privrn falsSuteng     * @pr/     * @ pre_filter_Finebeliksnction() {
h, ', markupet              this }lisser.webkit) {
)t  		    ((((htmlh= htmlovee('ht(/<br rTass="khtml-block-e('htholder">/gi  '<YUI_BR>=
< 		    ((((htmlh= htmlovee('ht(/<br rTass="it) {
-block-e('htholder">/gi  '<YUI_BR>=
<               		    htmlh= htmlovee('ht(/<br>/gi  '<YUI_BR>=
< 		    htmlh= htmlovee('ht(/<br (.*?)>/gi  '<YUI_BR>=
< 		    htmlh= htmlovee('ht(/<br\/>/gi  '<YUI_BR>=
< 		    htmlh= htmlovee('ht(/<br \/>/gi  '<YUI_BR>=
< 		    htmlh= htmlovee('ht(/<div><YUI_BR><\/div>/gi  '<YUI_BR>=
< 		    htmlh= htmlovee('ht(/<p>(&nbsp;|&#160;)<\/p>/g, '<YUI_BR>=
<             		    htmlh= htmlovee('ht(/<p><br>&nbsp;<\/p>/gi  '<YUI_BR>=
< 		    htmlh= htmlovee('ht(/<p>&nbsp;<\/p>/gi  '<YUI_BR>=
<             //Fix(last BR 	    ((((htmlh= htmlovee('ht(/<YUI_BR>$/  ''
<             //Fix(last BRain P 	    ((((htmlh= htmlovee('ht(/<YUI_BR><\/p>/g, '</p> =<             this }lisser.webkic    	            htmlh= htmlovee('ht(/&nbsp;&nbsp;&nbsp;&nbsp;/g, '\t =                            rn falshtml          }      /**
        * @privod _hanpost_filter_Finebeliks     * @priva {EveSuteng htmlhTiei = stto Eilter     * @priva {EveSuteng markupaTieimarkupa:ypvtto Eiltertto         rivription Hand = stPredFilter     * @privrn falsSuteng     * @pr/     * @ post_filter_Finebeliksnction() {
h, ', markupet              thismarkupai= axh, '{    		    ((((htmlh= htmlovee('ht(/<YUI_BR>/g, '<br />=
<              e {
    		    ((((htmlh= htmlovee('ht(/<YUI_BR>/g, '<br>=
<                           rn falshtml          }      /**
        * @privod _hanclearorKeyDDoc         rivription HandClear  betd()d fieditErKeyD     * @pr/     * @ clearorKeyDDocnction() {

               thgetR'diDoc(  .get.inner = st= '&nbsp;           }      /**
        * @privod _hanopenWi.set         rivription HandOvtmriridMd _hanfus(AdvancfteErKeyD     * @pr/     * @ openWi.setnction() {
win
           }      /**
        * @privod _hanmoveWi.set         rivription HandOvtmriridMd _hanfus(AdvancfteErKeyD     * @pr/     * @ moveWi.setnction() {

           }      /**
        * @privpre
        * @privod _han_clo
 Wi.set         rivription HandOvtmriridMd _hanfus(AdvancfteErKeyD     * @pr/     * @ _clo
 Wi.setnction() {

           }      /**
        * @privod _hanclo
 Wi.set         rivription HandOvtmriridMd _hanfus(AdvancfteErKeyD     * @pr/     * @ clo
 Wi.setnction() {

           /**
 /)browunsubptiobeAll('rExecEommand('en=
<             )browtoolbat.veediillButton
 e              )browE();
 e                  }      /**
        * @privod _hanriptroy         rivription HandDiptroyslt}omorKeyD, sel  f it'stent();
s(sets bjon(s.     * @privrn fals{Boolean}     * @pr/     * @ riptroynction() {

               Dom. }lis_Change froDelayout(t)                   clearout(func }lis_Change froDelayout(t)<                            }lishide e                       Dom. }lisveeize                if ( }lisveeize.riptroy e                            Dom. }lisdd                if ( }lisddwunreg e                            Dom. }lis'disaeanel')
                    }lis'disaeanel').riptroy e                             }lissave = s e              )browtoolbat.riptroy e               }lissliS.wide'visibiloty=, 'visiblc{               thgetsliS.wide'posi Han=, 'static')              thgetsliS.wide'top=, ''e              thgetsliS.wide'lefT=, ''e              ret :extAeli isthget'disaent();
')              thget'disaent();
_coyT=
s'disaeaNode.rem =ovee('htd(els[:extAeli, thget'disaent();
_coyT=
s'disaent();
')
              thget'disaent();
_coyT=
s'disaent();
').inner = st= ' <             thgetsli('e fdleSubmit=, e;
  
< //Remove  }omsubmit e fdleD     * @p    rn falstrue          }              /**
        * @privod _hantoSuteng         rivription HandRn falsia suteng veeveedntof t.betorKeyD.     * @privrn fals{Suteng}     * @pr/     * @ toSutengnction() {

               ret suth= 'SimplvErKeyD <             Dom. }lis'dit&&  }lis'disaent();
_coyT=

                   suth= 'SimplvErKeyD (#'m+ thget'disaent();
_coyT=
s'disain=
l+ ')'m+ (. }lis'disadisd', d=
l? ' Disd', d= : ''e
<                           rn falssut          }     })<      rivev);
 toolbatLoaded
rivription Hande.stohis fireanrurof t.betNodder prohtss directlytrExec .betToolbathis loaded. illoweng you to attachiev);
s( op;iei:oolbat. See <a href="YAHOO.utal.ent();
ehtml#addListener">ent();
eaddListener</a>nfus(mdit informi Handandlistenof t(varenisdev);
.
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 clean = s rivription Hande.stohis fireanrExec .betclean = stod _hanis sal, f.
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 rExecRodder rivription Hande.stohis fireanrExec .betNodder prohtss finishes. See <a href="YAHOO.utal.ent();
ehtml#addListener">ent();
eaddListener</a>nfus(mdit informi Handandlistenof t(varenisdev);
.
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 orKeyDCont);
Loaded
rivription Hande.stohis fireanrExec .beterKeyD xf doc's d();();
ctillytloadsisetsfires it'stonloadaev);
. Fseleegre you can eUas( injon(eng your own  })f stin op;ieid();();
  See <a href="YAHOO.utal.ent();
ehtml#addListener">ent();
eaddListener</a>nfus(mdit informi Handandlistenof t(varenisdev);
.
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 deEditNhange fro
rivription Hande.stohfires ate }ombeginuof t fieditChange fro prohtss  See <a href="YAHOO.utal.ent();
ehtml#addListener">ent();
eaddListener</a>nfus(mdit informi Handandlistenof t(varenisdev);
.
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 rExecNhange fro
rivription Hande.stohfires ate }omeets fieditChange fro prohtss  See <a href="YAHOO.utal.ent();
ehtml#addListener">ent();
eaddListener</a>nfus(mdit informi Handandlistenof t(varenisdev);
.
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 deEditEommand('en
rivription Hande.stohfires ate }ombeginuof t fieditCommand('en prohtss  See <a href="YAHOO.utal.ent();
ehtml#addListener">ent();
eaddListener</a>nfus(mdit informi Handandlistenof t(varenisdev);
.
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 rExecEommand('en
rivription Hande.stohfires ate }omeets fieditCommand('en prohtss  See <a href="YAHOO.utal.ent();
ehtml#addListener">ent();
eaddListener</a>nfus(mdit informi Handandlistenof t(varenisdev);
.
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 erKeyDMouseUp
riva {Eve{e.sto}devaTieiDOM e.stoh }afaoccured
rivription HandPamefte _roughd = stEv);
  See <a href="YAHOO.utal.ent();
ehtml#addListener">ent();
eaddListener</a>nfus(mdit informi Handandlistenof t(varenisdev);
.
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 erKeyDMouseDown
riva {Eve{e.sto}devaTieiDOM e.stoh }afaoccured
rivription HandPamefte _roughd = stEv);
  See <a href="YAHOO.utal.ent();
ehtml#addListener">ent();
eaddListener</a>nfus(mdit informi Handandlistenof t(varenisdev);
.
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 erKeyDDou') Click
riva {Eve{e.sto}devaTieiDOM e.stoh }afaoccured
rivription HandPamefte _roughd = stEv);
  See <a href="YAHOO.utal.ent();
ehtml#addListener">ent();
eaddListener</a>nfus(mdit informi Handandlistenof t(varenisdev);
.
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 orKeyDClick
riva {Eve{e.sto}devaTieiDOM e.stoh }afaoccured
rivription HandPamefte _roughd = stEv);
  See <a href="YAHOO.utal.ent();
ehtml#addListener">ent();
eaddListener</a>nfus(mdit informi Handandlistenof t(varenisdev);
.
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 orKeyDKeyUp
riva {Eve{e.sto}devaTieiDOM e.stoh }afaoccured
rivription HandPamefte _roughd = stEv);
  See <a href="YAHOO.utal.ent();
ehtml#addListener">ent();
eaddListener</a>nfus(mdit informi Handandlistenof t(varenisdev);
.
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 orKeyDKeyPress
riva {Eve{e.sto}devaTieiDOM e.stoh }afaoccured
rivription HandPamefte _roughd = stEv);
  See <a href="YAHOO.utal.ent();
ehtml#addListener">ent();
eaddListener</a>nfus(mdit informi Handandlistenof t(varenisdev);
.
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 orKeyDKeyDown
riva {Eve{e.sto}devaTieiDOM e.stoh }afaoccured
rivription HandPamefte _roughd = stEv);
  See <a href="YAHOO.utal.ent();
ehtml#addListener">ent();
eaddListener</a>nfus(mdit informi Handandlistenof t(varenisdev);
.
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 deEditErKeyDMouseUp
riva {Eve{e.sto}devaTieiDOM e.stoh }afaoccured
rivription HandFires deEdit erKeyD ev);
, rn falof t(;
  swall stopieditenternal prohtssof .
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 deEditErKeyDMouseDown
riva {Eve{e.sto}devaTieiDOM e.stoh }afaoccured
rivription HandFires deEdit erKeyD ev);
, rn falof t(;
  swall stopieditenternal prohtssof .
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 deEditErKeyDDou') Click
riva {Eve{e.sto}devaTieiDOM e.stoh }afaoccured
rivription HandFires deEdit erKeyD ev);
, rn falof t(;
  swall stopieditenternal prohtssof .
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 deEditErKeyDClick
riva {Eve{e.sto}devaTieiDOM e.stoh }afaoccured
rivription HandFires deEdit erKeyD ev);
, rn falof t(;
  swall stopieditenternal prohtssof .
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 deEditErKeyDKeyUp
riva {Eve{e.sto}devaTieiDOM e.stoh }afaoccured
rivription HandFires deEdit erKeyD ev);
, rn falof t(;
  swall stopieditenternal prohtssof .
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 deEditErKeyDKeyPress
riva {Eve{e.sto}devaTieiDOM e.stoh }afaoccured
rivription HandFires deEdit erKeyD ev);
, rn falof t(;
  swall stopieditenternal prohtssof .
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 deEditErKeyDKeyDown
riva {Eve{e.sto}devaTieiDOM e.stoh }afaoccured
rivription HandFires deEdit erKeyD ev);
, rn falof t(;
  swall stopieditenternal prohtssof .
riv:ypvtYAHOO.utal.Custome.sto
r/      rivev);
 orKeyDWi.setF();

rivription HandFires whee editef doc is f();
ed. Note, thge isswa.set f();
 ev);
, not an orKeyD f();
 ev);
.
riv:ypvtYAHOO.utal.Custome.sto
r/     rivev);
 orKeyDWi.setBlur rivription HandFires whee editef doc is blntEvd. Note, thge isswa.set blnt ev);
, not an orKeyD blnt ev);
.
riv:ypvtYAHOO.utal.Custome.sto
r/        rivription HandSenglttandabjon(lueftetoptr'hkaeditfpen wa.set  bjon(sisetseanelsiscrossp;ieivarious open erKeyDs  rivrTass orKeyDInfo  rivstatic
r/ YAHOO.widget.orKeyDInfo =               rivpre
        rivprty
 ty _instanhts     rivription HandA rnferencvtto sel erKeyDs oe editpagt.     riv:ypvtObjon(
* @pr/     _instanhts: {}              rivpre
        rivprty
 ty blankIClic     rivription HandA rnferencvtto  }omblankIClic url     riv:ypvtSuteng 
* @pr/     blankIClic: ''              rivpre
        rivprty
 ty wi.set     rivription HandA rnferencvtto  }omcntEvenlytfpen wa.set  bjon(konmany erKeyD oe editpagt.     riv:ypvtObjon( <a href="YAHOO.widget.orKeyDWi.setehtml">YAHOO.widget.orKeyDWi.set</a>
* @pr/     wi.setnc{}              rivpre
        rivprty
 ty eanel     rivription HandA rnferencvtto  }omcntEvenlytfpen eanelkonmany erKeyD oe editpagt.     riv:ypvtObjon( <a href="YAHOO.widget.Ovtmlayehtml">YAHOO.widget.Ovtmlay</a>
* @pr/     eanel: ;
                rivod _hangetorKeyDById     rivription HandRn falsia rnferencvtto  }omorKeyD  bjon(kassociinkd  desi.betgivee eexttNoa     rivp {Eve{Suteng/ = sent();
} idaTieiid or rnferencvt fiediteexttNoaetopre falsthitorKeyD instanhtt f     rivre falsObjon( <a href="YAHOO.widget.orKeyDehtml">YAHOO.widget.orKeyD</a>
* @pr/     getorKeyDByIdnction() {
id            this!YAHOO.l frCisSuteng(id 
           /**
 /Not a suteng,kassuoc atChandRnferencv             Dd = id.id          }         this }lis_instanhts[id]
           /**
rn falsthgetRinstanhts[id]          }         rn fals(;
        }              rivod _hansaveAll     rivription HandSaves sel orKeyD instanhts oe editpagt. Ifia form rnferencvtisseameft,tonly orKeyD's .gtra top;iis form wall bitpaved.     rivp {Eve{ = sent();
} form Tieiform  opcheckeifiedistorKeyD instanhttbelongs ao
* @pr/     saveAllnction() {
form
           ret 0, e, itemsh= YAHOO.widget.orKeyDInfotRinstanhts;         thisform
               (var ikonmitems
                   Dom.L frChasOwnPrty
 ty(items, i
                if (((((
aisitems[i];             if (((((Dom.es'disaent();
').form &&m.es'disaent();
').form ==iform
                if (((((((((
ssave = s e                                                      }         }e {
                (var ikonmitems
                   Dom.L frChasOwnPrty
 ty(items, i
                if (((((items[i]ssave = s e                                }         }     }              rivod _hantoSuteng     rivription HandRn falsia suteng veeveedntof t.betorKeyDInfot     rivre fals{Suteng}     r/     toSutengnction() {

           ret Fen ui <         (var ret 0;ilsthgetRinstanhts
               Dom.L frChasOwnPrty
 ty(thgetRinstanhts, i
                if (Fen++<                       }         rn fals'orKeyD Info ('m+ Fen + 'cregistereanentanht'm+ (.Fen > 1
l? 's= : ''el+ ')'      }
}<        
}) e  YAHOO.register("simplverKeyD", YAHOO.widget.SimplvErKeyD, {vtms) {:i"2.9.0", build:i"2800"})< 