/*
Copyright (c) 2011, Yahoo! Inc. All rights reserved.
Code licensed under the BSD License:
http://developer.yahoo.com/yui/license.html
version: 2.9.0
*/
(function () {

    /**
    * Config is a utility used within an Object to allow the implementer to
    * maintain a list of local configuration properties and listen for changes 
    * to those properties dynamically using CustomEvent. The initial values are 
    * also maintained so that the configuration can be reset at any given point 
    * to its initial state.
    * @namespace YAHOO.util
    * @class Config
    * @constructor
    * @param {Object} owner The owner Object to which this Config Object belongs
    */
    YAHOO.util.Config = function (owner) {

        if (owner) {
            this.init(owner);
        }


    };


    var Lang = YAHOO.lang,
        CustomEvent = YAHOO.util.CustomEvent,
        Config = YAHOO.util.Config;


    /**
     * Constant representing the CustomEvent type for the config changed event.
     * @property YAHOO.util.Config.CONFIG_CHANGED_EVENT
     * @private
     * @static
     * @final
     */
    Config.CONFIG_CHANGED_EVENT = "configChanged";
    
    /**
     * Constant representing the boolean type string
     * @property YAHOO.util.Config.BOOLEAN_TYPE
     * @private
     * @static
     * @final
     */
    Config.BOOLEAN_TYPE = "boolean";
    
    Config.prototype = {
     
        /**
        * Object reference to the owner of this Config Object
        * @property owner
        * @type Object
        */
        owner: null,
        
        /**
        * Boolean flag that specifies whether a queue is currently 
        * being executed
        * @property queueInProgress
        * @type Boolean
        */
        queueInProgress: false,
        
        /**
        * Maintains the local collection of configuration property objects and 
        * their specified values
        * @property config
        * @private
        * @type Object
        */ 
        config: null,
        
        /**
        * Maintains the local collection of configuration property objects as 
        * they were initially applied.
        * This object is used when resetting a property.
        * @property initialConfig
        * @private
        * @type Object
        */ 
        initialConfig: null,
        
        /**
        * Maintains the local, normalized CustomEvent queue
        * @property eventQueue
        * @private
        * @type Object
        */ 
        eventQueue: null,
        
        /**
        * Custom Event, notifying subscribers when Config properties are set 
        * (setProperty is called without the silent flag
        * @event configChangedEvent
        */
        configChangedEvent: null,
    
        /**
        * Initializes the configuration Object and all of its local members.
        * @method init
        * @param {Object} owner The owner Object to which this Config 
        * Object belongs
        */
        init: function (owner) {
    
            this.owner = owner;
    
            this.configChangedEvent = 
                this.createEvent(Config.CONFIG_CHANGED_EVENT);
    
            this.configChangedEvent.signature = CustomEvent.LIST;
            this.queueInProgress = false;
            this.config = {};
            this.initialConfig = {};
            this.eventQueue = [];
        
        },
        
        /**
        * Validates that the value passed in is a Boolean.
        * @method checkBoolean
        * @param {Object} val The value to validate
        * @return {Boolean} true, if the value is valid
        */ 
        checkBoolean: function (val) {
            return (typeof val == Config.BOOLEAN_TYPE);
        },
        
        /**
        * Validates that the value passed in is a number.
        * @method checkNumber
        * @param {Object} val The value to validate
        * @return {Boolean} true, if the value is valid
        */
        checkNumber: function (val) {
            return (!isNaN(val));
        },
        
        /**
        * Fires a configuration property event using the specified value. 
        * @method fireEvent
        * @private
        * @param {String} key The configuration property's name
        * @param {value} Object The value of the correct type for the property
        */ 
        fireEvent: function ( key, value ) {
            var property = this.config[key];
        
            if (property && property.event) {
                property.event.fire(value);
            } 
        },
        
        /**
        * Adds a property to the Config Object's private config hash.
        * @method addProperty
        * @param {String} key The configuration property's name
        * @param {Object} propertyObject The Object containing all of this 
        * property's arguments
        */
        addProperty: function ( key, propertyObject ) {
            key = key.toLowerCase();
        
            this.config[key] = propertyObject;
        
            propertyObject.event = this.createEvent(key, { scope: this.owner });
            propertyObject.event.signature = CustomEvent.LIST;
            
            
            propertyObject.key = key;
        
            if (propertyObject.handler) {
                propertyObject.event.subscribe(propertyObject.handler, 
                    this.owner);
            }
        
            this.setProperty(key, propertyObject.value, true);
            
            if (! propertyObject.suppressEvent) {
                this.queueProperty(key, propertyObject.value);
            }
            
        },
        
        /**
        * Returns a key-value configuration map of the values currently set in  
        * the Config Object.
        * @method getConfig
        * @return {Object} The current config, represented in a key-value map
        */
        getConfig: function () {
        
            var cfg = {},
                currCfg = this.config,
                prop,
                property;
                
            for (prop in currCfg) {
                if (Lang.hasOwnProperty(currCfg, prop)) {
                    property = currCfg[prop];
                    if (property && property.event) {
                        cfg[prop] = property.value;
                    }
                }
            }

            return cfg;
        },
        
        /**
        * Returns the value of specified property.
        * @method getProperty
        * @param {String} key The name of the property
        * @return {Object}  The value of the specified property
        */
        getProperty: function (key) {
            var property = this.config[key.toLowerCase()];
            if (property && property.event) {
                return property.value;
            } else {
                return undefined;
            }
        },
        
        /**
        * Resets the specified property's value to its initial value.
        * @method resetProperty
        * @param {String} key The name of the property
        * @return {Boolean} True is the property was reset, false if not
        */
        resetProperty: function (key) {
            key = key.toLowerCase();

            var property = this.config[key];

            if (property && property.event) {
                if (key in this.initialConfig) {
                    this.setProperty(key, this.initialConfig[key]);
                    return true;
                }
            } else {
                return false;
            }
        },
        
        /**
        * Sets the value of a property. If the silent property is passed as 
        * true, the property's event will not be fired.
        * @method setProperty
        * @param {String} key The name of the property
        * @param {String} value The value to set the property to
        * @param {Boolean} silent Whether the value should be set silently, 
        * without firing the property event.
        * @return {Boolean} True, if the set was successful, false if it failed.
        */
        setProperty: function (key, value, silent) {
        
            var property;
        
            key = key.toLowerCase();
        
            if (this.queueInProgress && ! silent) {
                // Currently running through a queue... 
                this.queueProperty(key,value);
                return true;
    
            } else {
                property = this.config[key];
                if (property && property.event) {
                    if (property.validator && !property.validator(value)) {
                        return false;
                    } else {
                        property.value = value;
                        if (! silent) {
                            this.fireEvent(key, value);
                            this.configChangedEvent.fire([key, value]);
                        }
                        return true;
                    }
                } else {
                    return false;
                }
            }
        },
        
        /**
        * Sets the value of a property and queues its event to execute. If the 
        * event is already scheduled to execute, it is
        * moved from its current position to the end of the queue.
        * @method queueProperty
        * @param {String} key The name of the property
        * @param {String} value The value to set the property to
        * @return {Boolean}  true, if the set was successful, false if 
        * it failed.
        */ 
        queueProperty: function (key, value) {
        
            key = key.toLowerCase();
        
            var property = this.config[key],
                foundDuplicate = false,
                iLen,
                queueItem,
                queueItemKey,
                queueItemValue,
                sLen,
                supercedesCheck,
                qLen,
                queueItemCheck,
                queueItemCheckKey,
                queueItemCheckValue,
                i,
                s,
                q;
                                
            if (property && property.event) {
    
                if (!Lang.isUndefined(value) && property.validator && 
                    !property.validator(value)) { // validator
                    return false;
                } else {
        
                    if (!Lang.isUndefined(value)) {
                        property.value = value;
                    } else {
                        value = property.value;
                    }
        
                    foundDuplicate = false;
                    iLen = this.eventQueue.length;
        
                    for (i = 0; i < iLen; i++) {
                        queueItem = this.eventQueue[i];
        
                        if (queueItem) {
                            queueItemKey = queueItem[0];
                            queueItemValue = queueItem[1];

                            if (queueItemKey == key) {
    
                                /*
                                    found a dupe... push to end of queue, null 
                                    current item, and break
                                */
    
                                this.eventQueue[i] = null;
    
                                this.eventQueue.push(
                                    [key, (!Lang.isUndefined(value) ? 
                                    value : queueItemValue)]);
    
                                foundDuplicate = true;
                                break;
                            }
                        }
                    }
                    
                    // this is a refire, or a new property in the queue
    
                    if (! foundDuplicate && !Lang.isUndefined(value)) { 
                        this.eventQueue.push([key, value]);
                    }
                }
        
                if (property.supercedes) {

                    sLen = property.supercedes.length;

                    for (s = 0; s < sLen; s++) {

                        supercedesCheck = property.supercedes[s];
                        qLen = this.eventQueue.length;

                        for (q = 0; q < qLen; q++) {
                            queueItemCheck = this.eventQueue[q];

                            if (queueItemCheck) {
                                queueItemCheckKey = queueItemCheck[0];
                                queueItemCheckValue = queueItemCheck[1];

                                if (queueItemCheckKey == 
                                    supercedesCheck.toLowerCase() ) {

                                    this.eventQueue.push([queueItemCheckKey, 
                                        queueItemCheckValue]);

                                    this.eventQueue[q] = null;
                                    break;

                                }
                            }
                        }
                    }
                }


                return true;
            } else {
                return false;
            }
        },
        
        /**
        * Fires the event for a property using the property's current value.
        * @method refireEvent
        * @param {String} key The name of the property
        */
        refireEvent: function (key) {
    
            key = key.toLowerCase();
        
            var property = this.config[key];
    
            if (property && property.event && 
    
                !Lang.isUndefined(property.value)) {
    
                if (this.queueInProgress) {
    
                    this.queueProperty(key);
    
                } else {
    
                    this.fireEvent(key, property.value);
    
                }
    
            }
        },
        
        /**
        * Applies a key-value Object literal to the configuration, replacing  
        * any existing values, and queueing the property events.
        * Although the values will be set, fireQueue() must be called for their 
        * associated events to execute.
        * @method applyConfig
        * @param {Object} userConfig The configuration Object literal
        * @param {Boolean} init  When set to true, the initialConfig will 
        * be set to the userConfig passed in, so that calling a reset will 
        * reset the properties to the passed values.
        */
        applyConfig: function (userConfig, init) {
        
            var sKey,
                oConfig;

            if (init) {
                oConfig = {};
                for (sKey in userConfig) {
                    if (Lang.hasOwnProperty(userConfig, sKey)) {
                        oConfig[sKey.toLowerCase()] = userConfig[sKey];
                    }
                }
                this.initialConfig = oConfig;
            }

            for (sKey in userConfig) {
                if (Lang.hasOwnProperty(userConfig, sKey)) {
                    this.queueProperty(sKey, userConfig[sKey]);
                }
            }
        },
        
        /**
        * Refires the events for all configuration properties using their 
        * current values.
        * @method refresh
        */
        refresh: function () {

            var prop;

            for (prop in this.config) {
                if (Lang.hasOwnProperty(this.config, prop)) {
                    this.refireEvent(prop);
                }
            }
        },
        
        /**
        * Fires the normalized list of queued property change events
        * @method fireQueue
        */
        fireQueue: function () {
        
            var i, 
                queueItem,
                key,
                value,
                property;
        
            this.queueInProgress = true;
            for (i = 0;i < this.eventQueue.length; i++) {
                queueItem = this.eventQueue[i];
                if (queueItem) {
        
                    key = queueItem[0];
                    value = queueItem[1];
                    property = this.config[key];

                    property.value = value;

                    // Clear out queue entry, to avoid it being 
                    // re-added to the queue by any queueProperty/supercedes
                    // calls which are invoked during fireEvent
                    this.eventQueue[i] = null;

                    this.fireEvent(key,value);
                }
            }
            
            this.queueInProgress = false;
            this.eventQueue = [];
        },
        
        /**
        * Subscribes an external handler to the change event for any 
        * given property. 
        * @method subscribeToConfigEvent
        * @param {String} key The property name
        * @param {Function} handler The handler function to use subscribe to 
        * the property's event
        * @param {Object} obj The Object to use for scoping the event handler 
        * (see CustomEvent documentation)
        * @param {Boolean} overrideContext Optional. If true, will override
        * "this" within the handler to map to the scope Object passed into the
        * method.
        * @return {Boolean} True, if the subscription was successful, 
        * otherwise false.
        */ 
        subscribeToConfigEvent: function (key, handler, obj, overrideContext) {
    
            var property = this.config[key.toLowerCase()];
    
            if (property && property.event) {
                if (!Config.alreadySubscribed(property.event, handler, obj)) {
                    property.event.subscribe(handler, obj, overrideContext);
                }
                return true;
            } else {
                return false;
            }
    
        },
        
        /**
        * Unsubscribes an external handler from the change event for any 
        * given property. 
        * @method unsubscribeFromConfigEvent
        * @param {String} key The property name
        * @param {Function} handler The handler function to use subscribe to 
        * the property's event
        * @param {Object} obj The Object to use for scoping the event 
        * handler (see CustomEvent documentation)
        * @return {Boolean} True, if the unsubscription was successful, 
        * otherwise false.
        */
        unsubscribeFromConfigEvent: function (key, handler, obj) {
            var property = this.config[key.toLowerCase()];
            if (property && property.event) {
                return property.event.unsubscribe(handler, obj);
            } else {
                return false;
            }
        },
        
        /**
        * Returns a string representation of the Config object
        * @method toString
        * @return {String} The Config object in string format.
        */
        toString: function () {
            var output = "Config";
            if (this.owner) {
                output += " [" + this.owner.toString() + "]";
            }
            return output;
        },
        
        /**
        * Returns a string representation of the Config object's current 
        * CustomEvent queue
        * @method outputEventQueue
        * @return {String} The string list of CustomEvents currently queued 
        * for execution
        */
        outputEventQueue: function () {

            var output = "",
                queueItem,
                q,
                nQueue = this.eventQueue.length;
              
            for (q = 0; q < nQueue; q++) {
                queueItem = this.eventQueue[q];
                if (queueItem) {
                    output += queueItem[0] + "=" + queueItem[1] + ", ";
                }
            }
            return output;
        },

        /**
        * Sets all properties to null, unsubscribes all listeners from each 
        * property's change event and all listeners from the configChangedEvent.
        * @method destroy
        */
        destroy: function () {

            var oConfig = this.config,
                sProperty,
                oProperty;


            for (sProperty in oConfig) {
            
                if (Lang.hasOwnProperty(oConfig, sProperty)) {

                    oProperty = oConfig[sProperty];

                    oProperty.event.unsubscribeAll();
                    oProperty.event = null;

                }
            
            }
            
            this.configChangedEvent.unsubscribeAll();
            
            this.configChangedEvent = null;
            this.owner = null;
            this.config = null;
            this.initialConfig = null;
            this.eventQueue = null;
        
        }

    };
    
    
    
    /**
    * Checks to determine if a particular function/Object pair are already 
    * subscribed to the specified CustomEvent
    * @method YAHOO.util.Config.alreadySubscribed
    * @static
    * @param {YAHOO.util.CustomEvent} evt The CustomEvent for which to check 
    * the subscriptions
    * @param {Function} fn The function to look for in the subscribers list
    * @param {Object} obj The execution scope Object for the subscription
    * @return {Boolean} true, if the function/Object pair is already subscribed 
    * to the CustomEvent passed in
    */
    Config.alreadySubscribed = function (evt, fn, obj) {
    
        var nSubscribers = evt.subscribers.length,
            subsc,
            i;

        if (nSubscribers > 0) {
            i = nSubscribers - 1;
            do {
                subsc = evt.subscribers[i];
                if (subsc && subsc.obj == obj && subsc.fn == fn) {
                    return true;
                }
            }
            while (i--);
        }

        return false;

    };

    YAHOO.lang.augmentProto(Config, YAHOO.util.EventProvider);

}());
(function () {

    /**
    * The Container family of components is designed to enable developers to 
    * create different kinds of content-containing modules on the web. Module 
    * and Overlay are the most basic containers, and they can be used directly 
    * or extended to build custom containers. Also part of the Container family 
    * are four UI controls that extend Module and Overlay: Tooltip, Panel, 
    * Dialog, and SimpleDialog.
    * @module container
    * @title Container
    * @requires yahoo, dom, event 
    * @optional dragdrop, animation, button
    */
    
    /**
    * Module is a JavaScript representation of the Standard Module Format. 
    * Standard Module Format is a simple standard for markup containers where 
    * child nodes representing the header, body, and footer of the content are 
    * denoted using the CSS classes "hd", "bd", and "ft" respectively. 
    * Module is the base class for all other classes in the YUI 
    * Container package.
    * @namespace YAHOO.widget
    * @class Module
    * @constructor
    * @param {String} el The element ID representing the Module <em>OR</em>
    * @param {HTMLElement} el The element representing the Module
    * @param {Object} userConfig The configuration Object literal containing 
    * the configuration that should be set for this module. See configuration 
    * documentation for more details.
    */
    YAHOO.widget.Module = function (el, userConfig) {
        if (el) {
            this.init(el, userConfig);
        } else {
        }
    };

    var Dom = YAHOO.util.Dom,
        Config = YAHOO.util.Config,
        Event = YAHOO.util.Event,
        CustomEvent = YAHOO.util.CustomEvent,
        Module = YAHOO.widget.Module,
        UA = YAHOO.env.ua,

        m_oModuleTemplate,
        m_oHeaderTemplate,
        m_oBodyTemplate,
        m_oFooterTemplate,

        /**
        * Constant representing the name of the Module's events
        * @property EVENT_TYPES
        * @private
        * @final
        * @type Object
        */
        EVENT_TYPES = {
            "BEFORE_INIT": "beforeInit",
            "INIT": "init",
            "APPEND": "append",
            "BEFORE_RENDER": "beforeRender",
            "RENDER": "render",
            "CHANGE_HEADER": "changeHeader",
            "CHANGE_BODY": "changeBody",
            "CHANGE_FOOTER": "changeFooter",
            "CHANGE_CONTENT": "changeContent",
            "DESTROY": "destroy",
            "BEFORE_SHOW": "beforeShow",
            "SHOW": "show",
            "BEFORE_HIDE": "beforeHide",
            "HIDE": "hide"
        },
            
        /**
        * Constant representing the Module's configuration properties
        * @property DEFAULT_CONFIG
        * @private
        * @final
        * @type Object
        */
        DEFAULT_CONFIG = {
        
            "VISIBLE": { 
                key: "visible", 
                value: true, 
                validator: YAHOO.lang.isBoolean 
            },

            "EFFECT": {
                key: "effect",
                suppressEvent: true,
                supercedes: ["visible"]
            },

            "MONITOR_RESIZE": {
                key: "monitorresize",
                value: true
            },

            "APPEND_TO_DOCUMENT_BODY": {
                key: "appendtodocumentbody",
                value: false
            }
        };

    /**
    * Constant representing the prefix path to use for non-secure images
    * @property YAHOO.widget.Module.IMG_ROOT
    * @static
    * @final
    * @type String
    */
    Module.IMG_ROOT = null;
    
    /**
    * Constant representing the prefix path to use for securely served images
    * @property YAHOO.widget.Module.IMG_ROOT_SSL
    * @static
    * @final
    * @type String
    */
    Module.IMG_ROOT_SSL = null;
    
    /**
    * Constant for the default CSS class name that represents a Module
    * @property YAHOO.widget.Module.CSS_MODULE
    * @static
    * @final
    * @type String
    */
    Module.CSS_MODULE = "yui-module";
    
    /**
    * CSS classname representing the module header. NOTE: The classname is inserted into the DOM as HTML, and should be escaped by the implementor if coming from an external source.
    * @property YAHOO.widget.Module.CSS_HEADER
    * @static
    * @final
    * @type String
    */
    Module.CSS_HEADER = "hd";

    /**
    * CSS classname representing the module body. NOTE: The classname is inserted into the DOM as HTML, and should be escaped by the implementor if coming from an external source.
    * @property YAHOO.widget.Module.CSS_BODY
    * @static
    * @final
    * @type String
    */
    Module.CSS_BODY = "bd";
    
    /**
    * CSS classname representing the module footer. NOTE: The classname is inserted into the DOM as HTML, and should be escaped by the implementor if coming from an external source.
    * @property YAHOO.widget.Module.CSS_FOOTER
    * @static
    * @final
    * @type String
    */
    Module.CSS_FOOTER = "ft";
    
    /**
    * Constant representing the url for the "src" attribute of the iframe 
    * used to monitor changes to the browser's base font size
    * @property YAHOO.widget.Module.RESIZE_MONITOR_SECURE_URL
    * @static
    * @final
    * @type String
    */
    Module.RESIZE_MONITOR_SECURE_URL = "javascript:false;";

    /**
    * Constant representing the buffer amount (in pixels) to use when positioning
    * the text resize monitor offscreen. The resize monitor is positioned
    * offscreen by an amount eqaul to its offsetHeight + the buffer value.
    * 
    * @property YAHOO.widget.Module.RESIZE_MONITOR_BUFFER
    * @static
    * @type Number
    */
    // Set to 1, to work around pixel offset in IE8, which increases when zoom is used
    Module.RESIZE_MONITOR_BUFFER = 1;

    /**
    * Singleton CustomEvent fired when the font size is changed in the browser.
    * Opera's "zoom" functionality currently does not support text 
    * size detection.
    * @event YAHOO.widget.Module.textResizeEvent
    */
    Module.textResizeEvent = new CustomEvent("textResize");

    /**
     * Helper utility method, which forces a document level 
     * redraw for Opera, which can help remove repaint
     * irregularities after applying DOM changes.
     *
     * @method YAHOO.widget.Module.forceDocumentRedraw
     * @static
     */
    Module.forceDocumentRedraw = function() {
        var docEl = document.documentElement;
        if (docEl) {
            docEl.className += " ";
            docEl.className = YAHOO.lang.trim(docEl.className);
        }
    };

    function createModuleTemplate() {

        if (!m_oModuleTemplate) {
            m_oModuleTemplate = document.createElement("div");
            
            m_oModuleTemplate.innerHTML = ("<div class=\"" + 
                Module.CSS_HEADER + "\"></div>" + "<div class=\"" + 
                Module.CSS_BODY + "\"></div><div class=\"" + 
                Module.CSS_FOOTER + "\"></div>");

            m_oHeaderTemplate = m_oModuleTemplate.firstChild;
            m_oBodyTemplate = m_oHeaderTemplate.nextSibling;
            m_oFooterTemplate = m_oBodyTemplate.nextSibling;
        }

        return m_oModuleTemplate;
    }

    function createHeader() {
        if (!m_oHeaderTemplate) {
            createModuleTemplate();
        }
        return (m_oHeaderTemplate.cloneNode(false));
    }

    function createBody() {
        if (!m_oBodyTemplate) {
            createModuleTemplate();
        }
        return (m_oBodyTemplate.cloneNode(false));
    }

    function createFooter() {
        if (!m_oFooterTemplate) {
            createModuleTemplate();
        }
        return (m_oFooterTemplate.cloneNode(false));
    }

    Module.prototype = {

        /**
        * The class's constructor function
        * @property contructor
        * @type Function
        */
        constructor: Module,
        
        /**
        * The main module element that contains the header, body, and footer
        * @property element
        * @type HTMLElement
        */
        element: null,

        /**
        * The header element, denoted with CSS class "hd"
        * @property header
        * @type HTMLElement
        */
        header: null,

        /**
        * The body element, denoted with CSS class "bd"
        * @property body
        * @type HTMLElement
        */
        body: null,

        /**
        * The footer element, denoted with CSS class "ft"
        * @property footer
        * @type HTMLElement
        */
        footer: null,

        /**
        * The id of the element
        * @property id
        * @type String
        */
        id: null,

        /**
        * A string representing the root path for all images created by
        * a Module instance.
        * @deprecated It is recommend that any images for a Module be applied
        * via CSS using the "background-image" property.
        * @property imageRoot
        * @type String
        */
        imageRoot: Module.IMG_ROOT,

        /**
        * Initializes the custom events for Module which are fired 
        * automatically at appropriate times by the Module class.
        * @method initEvents
        */
        initEvents: function () {

            var SIGNATURE = CustomEvent.LIST;

            /**
            * CustomEvent fired prior to class initalization.
            * @event beforeInitEvent
            * @param {class} classRef class reference of the initializing 
            * class, such as this.beforeInitEvent.fire(Module)
            */
            this.beforeInitEvent = this.createEvent(EVENT_TYPES.BEFORE_INIT);
            this.beforeInitEvent.signature = SIGNATURE;

            /**
            * CustomEvent fired after class initalization.
            * @event initEvent
            * @param {class} classRef class reference of the initializing 
            * class, such as this.beforeInitEvent.fire(Module)
            */  
            this.initEvent = this.createEvent(EVENT_TYPES.INIT);
            this.initEvent.signature = SIGNATURE;

            /**
            * CustomEvent fired when the Module is appended to the DOM
            * @event appendEvent
            */
            this.appendEvent = this.createEvent(EVENT_TYPES.APPEND);
            this.appendEvent.signature = SIGNATURE;

            /**
            * CustomEvent fired before the Module is rendered
            * @event beforeRenderEvent
            */
            this.beforeRenderEvent = this.createEvent(EVENT_TYPES.BEFORE_RENDER);
            this.beforeRenderEvent.signature = SIGNATURE;
        
            /**
            * CustomEvent fired after the Module is rendered
            * @event renderEvent
            */
            this.renderEvent = this.createEvent(EVENT_TYPES.RENDER);
            this.renderEvent.signature = SIGNATURE;
        
            /**
            * CustomEvent fired when the header content of the Module 
            * is modified
            * @event changeHeaderEvent
            * @param {String/HTMLElement} content String/element representing 
            * the new header content
            */
            this.changeHeaderEvent = this.createEvent(EVENT_TYPES.CHANGE_HEADER);
            this.changeHeaderEvent.signature = SIGNATURE;
            
            /**
            * CustomEvent fired when the body content of the Module is modified
            * @event changeBodyEvent
            * @param {String/HTMLElement} content String/element representing 
            * the new body content
            */  
            this.changeBodyEvent = this.createEvent(EVENT_TYPES.CHANGE_BODY);
            this.changeBodyEvent.signature = SIGNATURE;
            
            /**
            * CustomEvent fired when the footer content of the Module 
            * is modified
            * @event changeFooterEvent
            * @param {String/HTMLElement} content String/element representing 
            * the new footer content
            */
            this.changeFooterEvent = this.createEvent(EVENT_TYPES.CHANGE_FOOTER);
            this.changeFooterEvent.signature = SIGNATURE;
        
            /**
            * CustomEvent fired when the content of the Module is modified
            * @event changeContentEvent
            */
            this.changeContentEvent = this.createEvent(EVENT_TYPES.CHANGE_CONTENT);
            this.changeContentEvent.signature = SIGNATURE;

            /**
            * CustomEvent fired when the Module is destroyed
            * @event destroyEvent
            */
            this.destroyEvent = this.createEvent(EVENT_TYPES.DESTROY);
            this.destroyEvent.signature = SIGNATURE;

            /**
            * CustomEvent fired before the Module is shown
            * @event beforeShowEvent
            */
            this.beforeShowEvent = this.createEvent(EVENT_TYPES.BEFORE_SHOW);
            this.beforeShowEvent.signature = SIGNATURE;

            /**
            * CustomEvent fired after the Module is shown
            * @event showEvent
            */
            this.showEvent = this.createEvent(EVENT_TYPES.SHOW);
            this.showEvent.signature = SIGNATURE;

            /**
            * CustomEvent fired before the Module is hidden
            * @event beforeHideEvent
            */
            this.beforeHideEvent = this.createEvent(EVENT_TYPES.BEFORE_HIDE);
            this.beforeHideEvent.signature = SIGNATURE;

            /**
            * CustomEvent fired after the Module is hidden
            * @event hideEvent
            */
            this.hideEvent = this.createEvent(EVENT_TYPES.HIDE);
            this.hideEvent.signature = SIGNATURE;
        }, 

        /**
        * String identifying whether the current platform is windows or mac. This property
        * currently only identifies these 2 platforms, and returns false otherwise. 
        * @property platform
        * @deprecated Use YAHOO.env.ua
        * @type {String|Boolean}
        */
        platform: function () {
            var ua = navigator.userAgent.toLowerCase();

            if (ua.indexOf("windows") != -1 || ua.indexOf("win32") != -1) {
                return "windows";
            } else if (ua.indexOf("macintosh") != -1) {
                return "mac";
            } else {
                return false;
            }
        }(),
        
        /**
        * String representing the user-agent of the browser
        * @deprecated Use YAHOO.env.ua
        * @property browser
        * @type {String|Boolean}
        */
        browser: function () {
            var ua = navigator.userAgent.toLowerCase();
            /*
                 Check Opera first in case of spoof and check Safari before
                 Gecko since Safari's user agent string includes "like Gecko"
            */
            if (ua.indexOf('opera') != -1) { 
                return 'opera';
            } else if (ua.indexOf('msie 7') != -1) {
                return 'ie7';
            } else if (ua.indexOf('msie') != -1) {
                return 'ie';
            } else if (ua.indexOf('safari') != -1) { 
                return 'safari';
            } else if (ua.indexOf('gecko') != -1) {
                return 'gecko';
            } else {
                return false;
            }
        }(),
        
        /**
        * Boolean representing whether or not the current browsing context is 
        * secure (https)
        * @property isSecure
        * @type Boolean
        */
        isSecure: function () {
            if (window.location.href.toLowerCase().indexOf("https") === 0) {
                return true;
            } else {
                return false;
            }
        }(),
        
        /**
        * Initializes the custom events for Module which are fired 
        * automatically at appropriate times by the Module class.
        */
        initDefaultConfig: function () {
            // Add properties //
            /**
            * Specifies whether the Module is visible on the page.
            * @config visible
            * @type Boolean
            * @default true
            */
            this.cfg.addProperty(DEFAULT_CONFIG.VISIBLE.key, {
                handler: this.configVisible, 
                value: DEFAULT_CONFIG.VISIBLE.value, 
                validator: DEFAULT_CONFIG.VISIBLE.validator
            });

            /**
            * <p>
            * Object or array of objects representing the ContainerEffect 
            * classes that are active for animating the container.
            * </p>
            * <p>
            * <strong>NOTE:</strong> Although this configuration 
            * property is introduced at the Module level, an out of the box
            * implementation is not shipped for the Module class so setting
            * the proroperty on the Module class has no effect. The Overlay 
            * class is the first class to provide out of the box ContainerEffect 
            * support.
            * </p>
            * @config effect
            * @type Object
            * @default null
            */
            this.cfg.addProperty(DEFAULT_CONFIG.EFFECT.key, {
                handler: this.configEffect,
                suppressEvent: DEFAULT_CONFIG.EFFECT.suppressEvent, 
                supercedes: DEFAULT_CONFIG.EFFECT.supercedes
            });

            /**
            * Specifies whether to create a special proxy iframe to monitor 
            * for user font resizing in the document
            * @config monitorresize
            * @type Boolean
            * @default true
            */
            this.cfg.addProperty(DEFAULT_CONFIG.MONITOR_RESIZE.key, {
                handler: this.configMonitorResize,
                value: DEFAULT_CONFIG.MONITOR_RESIZE.value
            });

            /**
            * Specifies if the module should be rendered as the first child 
            * of document.body or appended as the last child when render is called
            * with document.body as the "appendToNode".
            * <p>
            * Appending to the body while the DOM is still being constructed can 
            * lead to Operation Aborted errors in IE hence this flag is set to 
            * false by default.
            * </p>
            * 
            * @config appendtodocumentbody
            * @type Boolean
            * @default false
            */
            this.cfg.addProperty(DEFAULT_CONFIG.APPEND_TO_DOCUMENT_BODY.key, {
                value: DEFAULT_CONFIG.APPEND_TO_DOCUMENT_BODY.value
            });
        },

        /**
        * The Module class's initialization method, which is executed for
        * Module and all of its subclasses. This method is automatically 
        * called by the constructor, and  sets up all DOM references for 
        * pre-existing markup, and creates required markup if it is not 
        * already present.
        * <p>
        * If the element passed in does not have an id, one will be generated
        * for it.
        * </p>
        * @method init
        * @param {String} el The element ID representing the Module <em>OR</em>
        * @param {HTMLElement} el The element representing the Module
        * @param {Object} userConfig The configuration Object literal 
        * containing the configuration that should be set for this module. 
        * See configuration documentation for more details.
        */
        init: function (el, userConfig) {

            var elId, child;

            this.initEvents();
            this.beforeInitEvent.fire(Module);

            /**
            * The Module's Config object used for monitoring 
            * configuration properties.
            * @property cfg
            * @type YAHOO.util.Config
            */
            this.cfg = new Config(this);

            if (this.isSecure) {
                this.imageRoot = Module.IMG_ROOT_SSL;
            }

            if (typeof el == "string") {
                elId = el;
                el = document.getElementById(el);
                if (! el) {
                    el = (createModuleTemplate()).cloneNode(false);
                    el.id = elId;
                }
            }

            this.id = Dom.generateId(el);
            this.element = el;

            child = this.element.firstChild;

            if (child) {
                var fndHd = false, fndBd = false, fndFt = false;
                do {
                    // We're looking for elements
                    if (1 == child.nodeType) {
                        if (!fndHd && Dom.hasClass(child, Module.CSS_HEADER)) {
                            this.header = child;
                            fndHd = true;
                        } else if (!fndBd && Dom.hasClass(child, Module.CSS_BODY)) {
                            this.body = child;
                            fndBd = true;
                        } else if (!fndFt && Dom.hasClass(child, Module.CSS_FOOTER)){
                            this.footer = child;
                            fndFt = true;
                        }
                    }
                } while ((child = child.nextSibling));
            }

            this.initDefaultConfig();

            Dom.addClass(this.element, Module.CSS_MODULE);

            if (userConfig) {
                this.cfg.applyConfig(userConfig, true);
            }

            /*
                Subscribe to the fireQueue() method of Config so that any 
                queued configuration changes are excecuted upon render of 
                the Module
            */ 

            if (!Config.alreadySubscribed(this.renderEvent, this.cfg.fireQueue, this.cfg)) {
                this.renderEvent.subscribe(this.cfg.fireQueue, this.cfg, true);
            }

            this.initEvent.fire(Module);
        },

        /**
        * Initialize an empty IFRAME that is placed out of the visible area 
        * that can be used to detect text resize.
        * @method initResizeMonitor
        */
        initResizeMonitor: function () {

            var isGeckoWin = (UA.gecko && this.platform == "windows");
            if (isGeckoWin) {
                // Help prevent spinning loading icon which 
                // started with FireFox 2.0.0.8/Win
                var self = this;
                setTimeout(function(){self._initResizeMonitor();}, 0);
            } else {
                this._initResizeMonitor();
            }
        },

        /**
         * Create and initialize the text resize monitoring iframe.
         * 
         * @protected
         * @method _initResizeMonitor
         */
        _initResizeMonitor : function() {

            var oDoc, 
                oIFrame, 
                sHTML;

            function fireTextResize() {
                Module.textResizeEvent.fire();
            }

            if (!UA.opera) {
                oIFrame = Dom.get("_yuiResizeMonitor");

                var supportsCWResize = this._supportsCWResize();

                if (!oIFrame) {
                    oIFrame = document.createElement("iframe");

                    if (this.isSecure && Module.RESIZE_MONITOR_SECURE_URL && UA.ie) {
                        oIFrame.src = Module.RESIZE_MONITOR_SECURE_URL;
                    }

                    if (!supportsCWResize) {
                        // Can't monitor on contentWindow, so fire from inside iframe
                        sHTML = ["<html><head><script ",
                                 "type=\"text/javascript\">",
                                 "window.onresize=function(){window.parent.",
                                 "YAHOO.widget.Module.textResizeEvent.",
                                 "fire();};<",
                                 "\/script></head>",
                                 "<body></body></html>"].join('');

                        oIFrame.src = "data:text/html;charset=utf-8," + encodeURIComponent(sHTML);
                    }

                    oIFrame.id = "_yuiResizeMonitor";
                    oIFrame.title = "Text Resize Monitor";
                    oIFrame.tabIndex = -1;
                    oIFrame.setAttribute("role", "presentation");

                    /*
                        Need to set "position" property before inserting the 
                        iframe into the document or Safari's status bar will 
                        forever indicate the iframe is loading 
                        (See YUILibrary bug #1723064)
                    */
                    oIFrame.style.position = "absolute";
                    oIFrame.style.visibility = "hidden";

                    var db = document.body,
                        fc = db.firstChild;
                    if (fc) {
                        db.insertBefore(oIFrame, fc);
                    } else {
                        db.appendChild(oIFrame);
                    }

                    // Setting the background color fixes an issue with IE6/IE7, where
                    // elements in the DOM, with -ve margin-top which positioned them 
                    // offscreen (so they would be overlapped by the iframe and its -ve top
                    // setting), would have their -ve margin-top ignored, when the iframe 
                    // was added.
                    oIFrame.style.backgroundColor = "transparent";

                    oIFrame.style.borderWidth = "0";
                    oIFrame.style.width = "2em";
                    oIFrame.style.height = "2em";
                    oIFrame.style.left = "0";
                    oIFrame.style.top = (-1 * (oIFrame.offsetHeight + Module.RESIZE_MONITOR_BUFFER)) + "px";
                    oIFrame.style.visibility = "visible";

                    /*
                       Don't open/close the document for Gecko like we used to, since it
                       leads to duplicate cookies. (See YUILibrary bug #1721755)
                    */
                    if (UA.webkit) {
                        oDoc = oIFrame.contentWindow.document;
                        oDoc.open();
                        oDoc.close();
                    }
                }

                if (oIFrame && oIFrame.contentWindow) {
                    Module.textResizeEvent.subscribe(this.onDomResize, this, true);

                    if (!Module.textResizeInitialized) {
                        if (supportsCWResize) {
                            if (!Event.on(oIFrame.contentWindow, "resize", fireTextResize)) {
                                /*
                                     This will fail in IE if document.domain has 
                                     changed, so we must change the listener to 
                                     use the oIFrame element instead
                                */
                                Event.on(oIFrame, "resize", fireTextResize);
                            }
                        }
                        Module.textResizeInitialized = true;
                    }
                    this.resizeMonitor = oIFrame;
                }
            }
        },

        /**
         * Text resize monitor helper method.
         * Determines if the browser supports resize events on iframe content windows.
         * 
         * @private
         * @method _supportsCWResize
         */
        _supportsCWResize : function() {
            /*
                Gecko 1.8.0 (FF1.5), 1.8.1.0-5 (FF2) won't fire resize on contentWindow.
                Gecko 1.8.1.6+ (FF2.0.0.6+) and all other browsers will fire resize on contentWindow.

                We don't want to start sniffing for patch versions, so fire textResize the same
                way on all FF2 flavors
             */
            var bSupported = true;
            if (UA.gecko && UA.gecko <= 1.8) {
                bSupported = false;
            }
            return bSupported;
        },

        /**
        * Event handler fired when the resize monitor element is resized.
        * @method onDomResize
        * @param {DOMEvent} e The DOM resize event
        * @param {Object} obj The scope object passed to the handler
        */
        onDomResize: function (e, obj) {

            var nTop = -1 * (this.resizeMonitor.offsetHeight + Module.RESIZE_MONITOR_BUFFER);

            this.resizeMonitor.style.top = nTop + "px";
            this.resizeMonitor.style.left = "0";
        },

        /**
        * Sets the Module's header content to the markup specified, or appends 
        * the passed element to the header. 
        * 
        * If no header is present, one will 
        * be automatically created. An empty string can be passed to the method
        * to clear the contents of the header.
        * 
        * @method setHeader
        * @param {HTML} headerContent The markup used to set the header content.
        * As a convenience, non HTMLElement objects can also be passed into 
        * the method, and will be treated as strings, with the header innerHTML
        * set to their default toString implementations. 
        * 
        * <p>NOTE: Markup passed into this method is added to the DOM as HTML, and should be escaped by the implementor if coming from an external source.</p>
        * 
        * <em>OR</em>
        * @param {HTMLElement} headerContent The HTMLElement to append to 
        * <em>OR</em>
        * @param {DocumentFragment} headerContent The document fragment 
        * containing elements which are to be added to the header
        */
        setHeader: function (headerContent) {
            var oHeader = this.header || (this.header = createHeader());

            if (headerContent.nodeName) {
                oHeader.innerHTML = "";
                oHeader.appendChild(headerContent);
            } else {
                oHeader.innerHTML = headerContent;
            }

            if (this._rendered) {
                this._renderHeader();
            }

            this.changeHeaderEvent.fire(headerContent);
            this.changeContentEvent.fire();

        },

        /**
        * Appends the passed element to the header. If no header is present, 
        * one will be automatically created.
        * @method appendToHeader
        * @param {HTMLElement | DocumentFragment} element The element to 
        * append to the header. In the case of a document fragment, the
        * children of the fragment will be appended to the header.
        */
        appendToHeader: function (element) {
            var oHeader = this.header || (this.header = createHeader());

            oHeader.appendChild(element);

            this.changeHeaderEvent.fire(element);
            this.changeContentEvent.fire();

        },

        /**
        * Sets the Module's body content to the HTML specified. 
        * 
        * If no body is present, one will be automatically created. 
        * 
        * An empty string can be passed to the method to clear the contents of the body.
        * @method setBody
        * @param {HTML} bodyContent The HTML used to set the body content 
        * As a convenience, non HTMLElement objects can also be passed into 
        * the method, and will be treated as strings, with the body innerHTML
        * set to their default toString implementations.
        * 
        * <p>NOTE: Markup passed into this method is added to the DOM as HTML, and should be escaped by the implementor if coming from an external source.</p>
        * 
        * <em>OR</em>
        * @param {HTMLElement} bodyContent The HTMLElement to add as the first and only
        * child of the body element.
        * <em>OR</em>
        * @param {DocumentFragment} bodyContent The document fragment 
        * containing elements which are to be added to the body
        */
        setBody: function (bodyContent) {
            var oBody = this.body || (this.body = createBody());

            if (bodyContent.nodeName) {
                oBody.innerHTML = "";
                oBody.appendChild(bodyContent);
            } else {
                oBody.innerHTML = bodyContent;
            }

            if (this._rendered) {
                this._renderBody();
            }

            this.changeBodyEvent.fire(bodyContent);
            this.changeContentEvent.fire();
        },

        /**
        * Appends the passed element to the body. If no body is present, one 
        * will be automatically created.
        * @method appendToBody
        * @param {HTMLElement | DocumentFragment} element The element to 
        * append to the body. In the case of a document fragment, the
        * children of the fragment will be appended to the body.
        * 
        */
        appendToBody: function (element) {
            var oBody = this.body || (this.body = createBody());
        
            oBody.appendChild(element);

            this.changeBodyEvent.fire(element);
            this.changeContentEvent.fire();

        },

        /**
        * Sets the Module's footer content to the HTML specified, or appends 
        * the passed element to the footer. If no footer is present, one will 
        * be automatically created. An empty string can be passed to the method
        * to clear the contents of the footer.
        * @method setFooter
        * @param {HTML} footerContent The HTML used to set the footer 
        * As a convenience, non HTMLElement objects can also be passed into 
        * the method, and will be treated as strings, with the footer innerHTML
        * set to their default toString implementations.
        * 
        * <p>NOTE: Markup passed into this method is added to the DOM as HTML, and should be escaped by the implementor if coming from an external source.</p>
        * 
        * <em>OR</em>
        * @param {HTMLElement} footerContent The HTMLElement to append to 
        * the footer
        * <em>OR</em>
        * @param {DocumentFragment} footerContent The document fragment containing 
        * elements which are to be added to the footer
        */
        setFooter: function (footerContent) {

            var oFooter = this.footer || (this.footer = createFooter());

            if (footerContent.nodeName) {
                oFooter.innerHTML = "";
                oFooter.appendChild(footerContent);
            } else {
                oFooter.innerHTML = footerContent;
            }

            if (this._rendered) {
                this._renderFooter();
            }

            this.changeFooterEvent.fire(footerContent);
            this.changeContentEvent.fire();
        },

        /**
        * Appends the passed element to the footer. If no footer is present, 
        * one will be automatically created.
        * @method appendToFooter
        * @param {HTMLElement | DocumentFragment} element The element to 
        * append to the footer. In the case of a document fragment, the
        * children of the fragment will be appended to the footer
        */
        appendToFooter: function (element) {

            var oFooter = this.footer || (this.footer = createFooter());

            oFooter.appendChild(element);

            this.changeFooterEvent.fire(element);
            this.changeContentEvent.fire();

        },

        /**
        * Renders the Module by inserting the elements that are not already 
        * in the main Module into their correct places. Optionally appends 
        * the Module to the specified node prior to the render's execution. 
        * <p>
        * For Modules without existing markup, the appendToNode argument 
        * is REQUIRED. If this argument is ommitted and the current element is 
        * not present in the document, the function will return false, 
        * indicating that the render was a failure.
        * </p>
        * <p>
        * NOTE: As of 2.3.1, if the appendToNode is the document's body element
        * then the module is rendered as the first child of the body element, 
        * and not appended to it, to avoid Operation Aborted errors in IE when 
        * rendering the module before window's load event is fired. You can 
        * use the appendtodocumentbody configuration property to change this 
        * to append to document.body if required.
        * </p>
        * @method render
        * @param {String} appendToNode The element id to which the Module 
        * should be appended to prior to rendering <em>OR</em>
        * @param {HTMLElement} appendToNode The element to which the Module 
        * should be appended to prior to rendering
        * @param {HTMLElement} moduleElement OPTIONAL. The element that 
        * represents the actual Standard Module container.
        * @return {Boolean} Success or failure of the render
        */
        render: function (appendToNode, moduleElement) {

            var me = this;

            function appendTo(parentNode) {
                if (typeof parentNode == "string") {
                    parentNode = document.getElementById(parentNode);
                }

                if (parentNode) {
                    me._addToParent(parentNode, me.element);
                    me.appendEvent.fire();
                }
            }

            this.beforeRenderEvent.fire();

            if (! moduleElement) {
                moduleElement = this.element;
            }

            if (appendToNode) {
                appendTo(appendToNode);
            } else { 
                // No node was passed in. If the element is not already in the Dom, this fails
                if (! Dom.inDocument(this.element)) {
                    return false;
                }
            }

            this._renderHeader(moduleElement);
            this._renderBody(moduleElement);
            this._renderFooter(moduleElement);

            this._rendered = true;

            this.renderEvent.fire();
            return true;
        },

        /**
         * Renders the currently set header into it's proper position under the 
         * module element. If the module element is not provided, "this.element" 
         * is used.
         * 
         * @method _renderHeader
         * @protected
         * @param {HTMLElement} moduleElement Optional. A reference to the module element
         */
        _renderHeader: function(moduleElement){
            moduleElement = moduleElement || this.element;

            // Need to get everything into the DOM if it isn't already
            if (this.header && !Dom.inDocument(this.header)) {
                // There is a header, but it's not in the DOM yet. Need to add it.
                var firstChild = moduleElement.firstChild;
                if (firstChild) {
                    moduleElement.insertBefore(this.header, firstChild);
                } else {
                    moduleElement.appendChild(this.header);
                }
            }
        },

        /**
         * Renders the currently set body into it's proper position under the 
         * module element. If the module element is not provided, "this.element" 
         * is used.
         * 
         * @method _renderBody
         * @protected
         * @param {HTMLElement} moduleElement Optional. A reference to the module element.
         */
        _renderBody: function(moduleElement){
            moduleElement = moduleElement || this.element;

            if (this.body && !Dom.inDocument(this.body)) {
                // There is a body, but it's not in the DOM yet. Need to add it.
                if (this.footer && Dom.isAncestor(moduleElement, this.footer)) {
                    moduleElement.insertBefore(this.body, this.footer);
                } else {
                    moduleElement.appendChild(this.body);
                }
            }
        },

        /**
         * Renders the currently set footer into it's proper position under the 
         * module element. If the module element is not provided, "this.element" 
         * is used.
         * 
         * @method _renderFooter
         * @protected
         * @param {HTMLElement} moduleElement Optional. A reference to the module element
         */
        _renderFooter: function(moduleElement){
            moduleElement = moduleElement || this.element;

            if (this.footer && !Dom.inDocument(this.footer)) {
                // There is a footer, but it's not in the DOM yet. Need to add it.
                moduleElement.appendChild(this.footer);
            }
        },

        /**
        * Removes the Module element from the DOM, sets all child elements to null, and purges the bounding element of event listeners.
        * @method destroy
        * @param {boolean} shallowPurge If true, only the parent element's DOM event listeners are purged. If false, or not provided, all children are also purged of DOM event listeners. 
        * NOTE: The flag is a "shallowPurge" flag, as opposed to what may be a more intuitive "purgeChildren" flag to maintain backwards compatibility with behavior prior to 2.9.0.
        */
        destroy: function (shallowPurge) {

            var parent,
                purgeChildren = !(shallowPurge);

            if (this.element) {
                Event.purgeElement(this.element, purgeChildren);
                parent = this.element.parentNode;
            }

            if (parent) {
                parent.removeChild(this.element);
            }
        
            this.element = null;
            this.header = null;
            this.body = null;
            this.footer = null;

            Module.textResizeEvent.unsubscribe(this.onDomResize, this);

            this.cfg.destroy();
            this.cfg = null;

            this.destroyEvent.fire();
        },

        /**
        * Shows the Module element by setting the visible configuration 
        * property to true. Also fires two events: beforeShowEvent prior to 
        * the visibility change, and showEvent after.
        * @method show
        */
        show: function () {
            this.cfg.setProperty("visible", true);
        },

        /**
        * Hides the Module element by setting the visible configuration 
        * property to false. Also fires two events: beforeHideEvent prior to 
        * the visibility change, and hideEvent after.
        * @method hide
        */
        hide: function () {
            this.cfg.setProperty("visible", false);
        },
        
        // BUILT-IN EVENT HANDLERS FOR MODULE //
        /**
        * Default event handler for changing the visibility property of a 
        * Module. By default, this is achieved by switching the "display" style 
        * between "block" and "none".
        * This method is responsible for firing showEvent and hideEvent.
        * @param {String} type The CustomEvent type (usually the property name)
        * @param {Object[]} args The CustomEvent arguments. For configuration 
        * handlers, args[0] will equal the newly applied value for the property.
        * @param {Object} obj The scope object. For configuration handlers, 
        * this will usually equal the owner.
        * @method configVisible
        */
        configVisible: function (type, args, obj) {
            var visible = args[0];
            if (visible) {
                if(this.beforeShowEvent.fire()) {
                    Dom.setStyle(this.element, "display", "block");
                    this.showEvent.fire();
                }
            } else {
                if (this.beforeHideEvent.fire()) {
                    Dom.setStyle(this.element, "display", "none");
                    this.hideEvent.fire();
                }
            }
        },

        /**
        * Default event handler for the "effect" configuration property
        * @param {String} type The CustomEvent type (usually the property name)
        * @param {Object[]} args The CustomEvent arguments. For configuration 
        * handlers, args[0] will equal the newly applied value for the property.
        * @param {Object} obj The scope object. For configuration handlers, 
        * this will usually equal the owner.
        * @method configEffect
        */
        configEffect: function (type, args, obj) {
            this._cachedEffects = (this.cacheEffects) ? this._createEffects(args[0]) : null;
        },

        /**
         * If true, ContainerEffects (and Anim instances) are cached when "effect" is set, and reused. 
         * If false, new instances are created each time the container is hidden or shown, as was the 
         * behavior prior to 2.9.0. 
         *
         * @property cacheEffects
         * @since 2.9.0
         * @default true
         * @type boolean
         */
        cacheEffects : true,

        /**
         * Creates an array of ContainerEffect instances from the provided configs
         * 
         * @method _createEffects
         * @param {Array|Object} effectCfg An effect configuration or array of effect configurations
         * @return {Array} An array of ContainerEffect instances.
         * @protected
         */
        _createEffects: function(effectCfg) {
            var effectInstances = null,
                n, 
                i,
                eff;

            if (effectCfg) {
                if (effectCfg instanceof Array) {
                    effectInstances = [];
                    n = effectCfg.length;
                    for (i = 0; i < n; i++) {
                        eff = effectCfg[i];
                        if (eff.effect) {
                            effectInstances[effectInstances.length] = eff.effect(this, eff.duration);
                        }
                    }
                } else if (effectCfg.effect) {
                    effectInstances = [effectCfg.effect(this, effectCfg.duration)];
                }
            }

            return effectInstances;
        },

        /**
        * Default event handler for the "monitorresize" configuration property
        * @param {String} type The CustomEvent type (usually the property name)
        * @param {Object[]} args The CustomEvent arguments. For configuration 
        * handlers, args[0] will equal the newly applied value for the property.
        * @param {Object} obj The scope object. For configuration handlers, 
        * this will usually equal the owner.
        * @method configMonitorResize
        */
        configMonitorResize: function (type, args, obj) {
            var monitor = args[0];
            if (monitor) {
                this.initResizeMonitor();
            } else {
                Module.textResizeEvent.unsubscribe(this.onDomResize, this, true);
                this.resizeMonitor = null;
            }
        },

        /**
         * This method is a protected helper, used when constructing the DOM structure for the module 
         * to account for situations which may cause Operation Aborted errors in IE. It should not 
         * be used for general DOM construction.
         * <p>
         * If the parentNode is not document.body, the element is appended as the last element.
         * </p>
         * <p>
         * If the parentNode is document.body the element is added as the first child to help
         * prevent Operation Aborted errors in IE.
         * </p>
         *
         * @param {parentNode} The HTML element to which the element will be added
         * @param {element} The HTML element to be added to parentNode's children
         * @method _addToParent
         * @protected
         */
        _addToParent: function(parentNode, element) {
            if (!this.cfg.getProperty("appendtodocumentbody") && parentNode === document.body && parentNode.firstChild) {
                parentNode.insertBefore(element, parentNode.firstChild);
            } else {
                parentNode.appendChild(element);
            }
        },

        /**
        * Returns a String representation of the Object.
        * @method toString
        * @return {String} The string representation of the Module
        */
        toString: function () {
            return "Module " + this.id;
        }
    };

    YAHOO.lang.augmentProto(Module, YAHOO.util.EventProvider);

}());
(function () {

    /**
    * Overlay is a Module that is absolutely positioned above the page flow. It 
    * has convenience methods for positioning and sizing, as well as options for 
    * controlling zIndex and constraining the Overlay's position to the current 
    * visible viewport. Overlay also contains a dynamicly generated IFRAME which 
    * is placed beneath it for Internet Explorer 6 and 5.x so that it will be 
    * properly rendered above SELECT elements.
    * @namespace YAHOO.widget
    * @class Overlay
    * @extends YAHOO.widget.Module
    * @param {String} el The element ID representing the Overlay <em>OR</em>
    * @param {HTMLElement} el The element representing the Overlay
    * @param {Object} userConfig The configuration object literal containing 
    * the configuration that should be set for this Overlay. See configuration 
    * documentation for more details.
    * @constructor
    */
    YAHOO.widget.Overlay = function (el, userConfig) {
        YAHOO.widget.Overlay.superclass.constructor.call(this, el, userConfig);
    };

    var Lang = YAHOO.lang,
        CustomEvent = YAHOO.util.CustomEvent,
        Module = YAHOO.widget.Module,
        Event = YAHOO.util.Event,
        Dom = YAHOO.util.Dom,
        Config = YAHOO.util.Config,
        UA = YAHOO.env.ua,
        Overlay = YAHOO.widget.Overlay,

        _SUBSCRIBE = "subscribe",
        _UNSUBSCRIBE = "unsubscribe",
        _CONTAINED = "contained",

        m_oIFrameTemplate,

        /**
        * Constant representing the name of the Overlay's events
        * @property EVENT_TYPES
        * @private
        * @final
        * @type Object
        */
        EVENT_TYPES = {
            "BEFORE_MOVE": "beforeMove",
            "MOVE": "move"
        },

        /**
        * Constant representing the Overlay's configuration properties
        * @property DEFAULT_CONFIG
        * @private
        * @final
        * @type Object
        */
        DEFAULT_CONFIG = {

            "X": { 
                key: "x", 
                validator: Lang.isNumber, 
                suppressEvent: true, 
                supercedes: ["iframe"]
            },

            "Y": { 
                key: "y", 
                validator: Lang.isNumber, 
                suppressEvent: true, 
                supercedes: ["iframe"]
            },

            "XY": { 
                key: "xy", 
                suppressEvent: true, 
                supercedes: ["iframe"] 
            },

            "CONTEXT": { 
                key: "context", 
                suppressEvent: true, 
                supercedes: ["iframe"] 
            },

            "FIXED_CENTER": { 
                key: "fixedcenter", 
                value: false, 
                supercedes: ["iframe", "visible"] 
            },

            "WIDTH": { 
                key: "width",
                suppressEvent: true,
                supercedes: ["context", "fixedcenter", "iframe"]
            }, 

            "HEIGHT": { 
                key: "height", 
                suppressEvent: true, 
                supercedes: ["context", "fixedcenter", "iframe"] 
            },

            "AUTO_FILL_HEIGHT" : {
                key: "autofillheight",
                supercedes: ["height"],
                value:"body"
            },

            "ZINDEX": { 
                key: "zindex", 
                value: null 
            },

            "CONSTRAIN_TO_VIEWPORT": { 
                key: "constraintoviewport", 
                value: false, 
                validator: Lang.isBoolean, 
                supercedes: ["iframe", "x", "y", "xy"]
            }, 

            "IFRAME": { 
                key: "iframe", 
                value: (UA.ie == 6 ? true : false), 
                validator: Lang.isBoolean, 
                supercedes: ["zindex"] 
            },

            "PREVENT_CONTEXT_OVERLAP": {
                key: "preventcontextoverlap",
                value: false,
                validator: Lang.isBoolean,  
                supercedes: ["constraintoviewport"]
            }

        };

    /**
    * The URL that will be placed in the iframe
    * @property YAHOO.widget.Overlay.IFRAME_SRC
    * @static
    * @final
    * @type String
    */
    Overlay.IFRAME_SRC = "javascript:false;";

    /**
    * Number representing how much the iframe shim should be offset from each 
    * side of an Overlay instance, in pixels.
    * @property YAHOO.widget.Overlay.IFRAME_SRC
    * @default 3
    * @static
    * @final
    * @type Number
    */
    Overlay.IFRAME_OFFSET = 3;

    /**
    * Number representing the minimum distance an Overlay instance should be 
    * positioned relative to the boundaries of the browser's viewport, in pixels.
    * @property YAHOO.widget.Overlay.VIEWPORT_OFFSET
    * @default 10
    * @static
    * @final
    * @type Number
    */
    Overlay.VIEWPORT_OFFSET = 10;

    /**
    * Constant representing the top left corner of an element, used for 
    * configuring the context element alignment
    * @property YAHOO.widget.Overlay.TOP_LEFT
    * @static
    * @final
    * @type String
    */
    Overlay.TOP_LEFT = "tl";

    /**
    * Constant representing the top right corner of an element, used for 
    * configuring the context element alignment
    * @property YAHOO.widget.Overlay.TOP_RIGHT
    * @static
    * @final
    * @type String
    */
    Overlay.TOP_RIGHT = "tr";

    /**
    * Constant representing the top bottom left corner of an element, used for 
    * configuring the context element alignment
    * @property YAHOO.widget.Overlay.BOTTOM_LEFT
    * @static
    * @final
    * @type String
    */
    Overlay.BOTTOM_LEFT = "bl";

    /**
    * Constant representing the bottom right corner of an element, used for 
    * configuring the context element alignment
    * @property YAHOO.widget.Overlay.BOTTOM_RIGHT
    * @static
    * @final
    * @type String
    */
    Overlay.BOTTOM_RIGHT = "br";

    Overlay.PREVENT_OVERLAP_X = {
        "tltr": true,
        "blbr": true,
        "brbl": true,
        "trtl": true
    };
            
    Overlay.PREVENT_OVERLAP_Y = {
        "trbr": true,
        "tlbl": true,
        "bltl": true,
        "brtr": true
    };

    /**
    * Constant representing the default CSS class used for an Overlay
    * @property YAHOO.widget.Overlay.CSS_OVERLAY
    * @static
    * @final
    * @type String
    */
    Overlay.CSS_OVERLAY = "yui-overlay";

    /**
    * Constant representing the default hidden CSS class used for an Overlay. This class is 
    * applied to the overlay's outer DIV whenever it's hidden.
    *
    * @property YAHOO.widget.Overlay.CSS_HIDDEN
    * @static
    * @final
    * @type String
    */
    Overlay.CSS_HIDDEN = "yui-overlay-hidden";

    /**
    * Constant representing the default CSS class used for an Overlay iframe shim.
    * 
    * @property YAHOO.widget.Overlay.CSS_IFRAME
    * @static
    * @final
    * @type String
    */
    Overlay.CSS_IFRAME = "yui-overlay-iframe";

    /**
     * Constant representing the names of the standard module elements
     * used in the overlay.
     * @property YAHOO.widget.Overlay.STD_MOD_RE
     * @static
     * @final
     * @type RegExp
     */
    Overlay.STD_MOD_RE = /^\s*?(body|footer|header)\s*?$/i;

    /**
    * A singleton CustomEvent used for reacting to the DOM event for 
    * window scroll
    * @event YAHOO.widget.Overlay.windowScrollEvent
    */
    Overlay.windowScrollEvent = new CustomEvent("windowScroll");

    /**
    * A singleton CustomEvent used for reacting to the DOM event for
    * window resize
    * @event YAHOO.widget.Overlay.windowResizeEvent
    */
    Overlay.windowResizeEvent = new CustomEvent("windowResize");

    /**
    * The DOM event handler used to fire the CustomEvent for window scroll
    * @method YAHOO.widget.Overlay.windowScrollHandler
    * @static
    * @param {DOMEvent} e The DOM scroll event
    */
    Overlay.windowScrollHandler = function (e) {
        var t = Event.getTarget(e);

        // - Webkit (Safari 2/3) and Opera 9.2x bubble scroll events from elements to window
        // - FF2/3 and IE6/7, Opera 9.5x don't bubble scroll events from elements to window
        // - IE doesn't recognize scroll registered on the document.
        //
        // Also, when document view is scrolled, IE doesn't provide a target, 
        // rest of the browsers set target to window.document, apart from opera 
        // which sets target to window.
        if (!t || t === window || t === window.document) {
            if (UA.ie) {

                if (! window.scrollEnd) {
                    window.scrollEnd = -1;
                }

                clearTimeout(window.scrollEnd);
        
                window.scrollEnd = setTimeout(function () { 
                    Overlay.windowScrollEvent.fire(); 
                }, 1);
        
            } else {
                Overlay.windowScrollEvent.fire();
            }
        }
    };

    /**
    * The DOM event handler used to fire the CustomEvent for window resize
    * @method YAHOO.widget.Overlay.windowResizeHandler
    * @static
    * @param {DOMEvent} e The DOM resize event
    */
    Overlay.windowResizeHandler = function (e) {

        if (UA.ie) {
            if (! window.resizeEnd) {
                window.resizeEnd = -1;
            }

            clearTimeout(window.resizeEnd);

            window.resizeEnd = setTimeout(function () {
                Overlay.windowResizeEvent.fire(); 
            }, 100);
        } else {
            Overlay.windowResizeEvent.fire();
        }
    };

    /**
    * A boolean that indicated whether the window resize and scroll events have 
    * already been subscribed to.
    * @property YAHOO.widget.Overlay._initialized
    * @private
    * @type Boolean
    */
    Overlay._initialized = null;

    if (Overlay._initialized === null) {
        Event.on(window, "scroll", Overlay.windowScrollHandler);
        Event.on(window, "resize", Overlay.windowResizeHandler);
        Overlay._initialized = true;
    }

    /**
     * Internal map of special event types, which are provided
     * by the instance. It maps the event type to the custom event 
     * instance. Contains entries for the "windowScroll", "windowResize" and
     * "textResize" static container events.
     *
     * @property YAHOO.widget.Overlay._TRIGGER_MAP
     * @type Object
     * @static
     * @private
     */
    Overlay._TRIGGER_MAP = {
        "windowScroll" : Overlay.windowScrollEvent,
        "windowResize" : Overlay.windowResizeEvent,
        "textResize"   : Module.textResizeEvent
    };

    YAHOO.extend(Overlay, Module, {

        /**
         * <p>
         * Array of default event types which will trigger
         * context alignment for the Overlay class.
         * </p>
         * <p>The array is empty by default for Overlay,
         * but maybe populated in future releases, so classes extending
         * Overlay which need to define their own set of CONTEXT_TRIGGERS
         * should concatenate their super class's prototype.CONTEXT_TRIGGERS 
         * value with their own array of values.
         * </p>
         * <p>
         * E.g.:
         * <code>CustomOverlay.prototype.CONTEXT_TRIGGERS = YAHOO.widget.Overlay.prototype.CONTEXT_TRIGGERS.concat(["windowScroll"]);</code>
         * </p>
         * 
         * @property CONTEXT_TRIGGERS
         * @type Array
         * @final
         */
        CONTEXT_TRIGGERS : [],

        /**
        * The Overlay initialization method, which is executed for Overlay and  
        * all of its subclasses. This method is automatically called by the 
        * constructor, and  sets up all DOM references for pre-existing markup, 
        * and creates required markup if it is not already present.
        * @method init
        * @param {String} el The element ID representing the Overlay <em>OR</em>
        * @param {HTMLElement} el The element representing the Overlay
        * @param {Object} userConfig The configuration object literal 
        * containing the configuration that should be set for this Overlay. 
        * See configuration documentation for more details.
        */
        init: function (el, userConfig) {

            /*
                 Note that we don't pass the user config in here yet because we
                 only want it executed once, at the lowest subclass level
            */

            Overlay.superclass.init.call(this, el/*, userConfig*/);

            this.beforeInitEvent.fire(Overlay);

            Dom.addClass(this.element, Overlay.CSS_OVERLAY);

            if (userConfig) {
                this.cfg.applyConfig(userConfig, true);
            }

            if (this.platform == "mac" && UA.gecko) {

                if (! Config.alreadySubscribed(this.showEvent,
                    this.showMacGeckoScrollbars, this)) {

                    this.showEvent.subscribe(this.showMacGeckoScrollbars, 
                        this, true);

                }

                if (! Config.alreadySubscribed(this.hideEvent, 
                    this.hideMacGeckoScrollbars, this)) {

                    this.hideEvent.subscribe(this.hideMacGeckoScrollbars, 
                        this, true);

                }
            }

            this.initEvent.fire(Overlay);
        },
        
        /**
        * Initializes the custom events for Overlay which are fired  
        * automatically at appropriate times by the Overlay class.
        * @method initEvents
        */
        initEvents: function () {

            Overlay.superclass.initEvents.call(this);

            var SIGNATURE = CustomEvent.LIST;

            /**
            * CustomEvent fired before the Overlay is moved.
            * @event beforeMoveEvent
            * @param {Number} x x coordinate
            * @param {Number} y y coordinate
            */
            this.beforeMoveEvent = this.createEvent(EVENT_TYPES.BEFORE_MOVE);
            this.beforeMoveEvent.signature = SIGNATURE;

            /**
            * CustomEvent fired after the Overlay is moved.
            * @event moveEvent
            * @param {Number} x x coordinate
            * @param {Number} y y coordinate
            */
            this.moveEvent = this.createEvent(EVENT_TYPES.MOVE);
            this.moveEvent.signature = SIGNATURE;

        },
        
        /**
        * Initializes the class's configurable properties which can be changed 
        * using the Overlay's Config object (cfg).
        * @method initDefaultConfig
        */
        initDefaultConfig: function () {
    
            Overlay.superclass.initDefaultConfig.call(this);

            var cfg = this.cfg;

            // Add overlay config properties //
            
            /**
            * The absolute x-coordinate position of the Overlay
            * @config x
            * @type Number
            * @default null
            */
            cfg.addProperty(DEFAULT_CONFIG.X.key, { 
    
                handler: this.configX, 
                validator: DEFAULT_CONFIG.X.validator, 
                suppressEvent: DEFAULT_CONFIG.X.suppressEvent, 
                supercedes: DEFAULT_CONFIG.X.supercedes
    
            });

            /**
            * The absolute y-coordinate position of the Overlay
            * @config y
            * @type Number
            * @default null
            */
            cfg.addProperty(DEFAULT_CONFIG.Y.key, {

                handler: this.configY, 
                validator: DEFAULT_CONFIG.Y.validator, 
                suppressEvent: DEFAULT_CONFIG.Y.suppressEvent, 
                supercedes: DEFAULT_CONFIG.Y.supercedes

            });

            /**
            * An array with the absolute x and y positions of the Overlay
            * @config xy
            * @type Number[]
            * @default null
            */
            cfg.addProperty(DEFAULT_CONFIG.XY.key, {
                handler: this.configXY, 
                suppressEvent: DEFAULT_CONFIG.XY.suppressEvent, 
                supercedes: DEFAULT_CONFIG.XY.supercedes
            });

            /**
            * <p>
            * The array of context arguments for context-sensitive positioning. 
            * </p>
            *
            * <p>
            * The format of the array is: <code>[contextElementOrId, overlayCorner, contextCorner, arrayOfTriggerEvents (optional), xyOffset (optional)]</code>, the
            * the 5 array elements described in detail below:
            * </p>
            *
            * <dl>
            * <dt>contextElementOrId &#60;String|HTMLElement&#62;</dt>
            * <dd>A reference to the context element to which the overlay should be aligned (or it's id).</dd>
            * <dt>overlayCorner &#60;String&#62;</dt>
            * <dd>The corner of the overlay which is to be used for alignment. This corner will be aligned to the 
            * corner of the context element defined by the "contextCorner" entry which follows. Supported string values are: 
            * "tr" (top right), "tl" (top left), "br" (bottom right), or "bl" (bottom left).</dd>
            * <dt>contextCorner &#60;String&#62;</dt>
            * <dd>The corner of the context element which is to be used for alignment. Supported string values are the same ones listed for the "overlayCorner" entry above.</dd>
            * <dt>arrayOfTriggerEvents (optional) &#60;Array[String|CustomEvent]&#62;</dt>
            * <dd>
            * <p>
            * By default, context alignment is a one time operation, aligning the Overlay to the context element when context configuration property is set, or when the <a href="#method_align">align</a> 
            * method is invoked. However, you can use the optional "arrayOfTriggerEvents" entry to define the list of events which should force the overlay to re-align itself with the context element. 
            * This is useful in situations where the layout of the document may change, resulting in the context element's position being modified.
            * </p>
            * <p>
            * The array can contain either event type strings for events the instance publishes (e.g. "beforeShow") or CustomEvent instances. Additionally the following
            * 3 static container event types are also currently supported : <code>"windowResize", "windowScroll", "textResize"</code> (defined in <a href="#property__TRIGGER_MAP">_TRIGGER_MAP</a> private property).
            * </p>
            * </dd>
            * <dt>xyOffset &#60;Number[]&#62;</dt>
            * <dd>
            * A 2 element Array specifying the X and Y pixel amounts by which the Overlay should be offset from the aligned corner. e.g. [5,0] offsets the Overlay 5 pixels to the left, <em>after</em> aligning the given context corners.
            * NOTE: If using this property and no triggers need to be defined, the arrayOfTriggerEvents property should be set to null to maintain correct array positions for the arguments. 
            * </dd>
            * </dl>
            *
            * <p>
            * For example, setting this property to <code>["img1", "tl", "bl"]</code> will 
            * align the Overlay's top left corner to the bottom left corner of the
            * context element with id "img1".
            * </p>
            * <p>
            * Setting this property to <code>["img1", "tl", "bl", null, [0,5]</code> will 
            * align the Overlay's top left corner to the bottom left corner of the
            * context element with id "img1", and then offset it by 5 pixels on the Y axis (providing a 5 pixel gap between the bottom of the context element and top of the overlay).
            * </p>
            * <p>
            * Adding the optional trigger values: <code>["img1", "tl", "bl", ["beforeShow", "windowResize"], [0,5]]</code>,
            * will re-align the overlay position, whenever the "beforeShow" or "windowResize" events are fired.
            * </p>
            *
            * @config context
            * @type Array
            * @default null
            */
            cfg.addProperty(DEFAULT_CONFIG.CONTEXT.key, {
                handler: this.configContext, 
                suppressEvent: DEFAULT_CONFIG.CONTEXT.suppressEvent, 
                supercedes: DEFAULT_CONFIG.CONTEXT.supercedes
            });

            /**
            * Determines whether or not the Overlay should be anchored 
            * to the center of the viewport.
            * 
            * <p>This property can be set to:</p>
            * 
            * <dl>
            * <dt>true</dt>
            * <dd>
            * To enable fixed center positioning
            * <p>
            * When enabled, the overlay will 
            * be positioned in the center of viewport when initially displayed, and 
            * will remain in the center of the viewport whenever the window is 
            * scrolled or resized.
            * </p>
            * <p>
            * If the overlay is too big for the viewport, 
            * it's top left corner will be aligned with the top left corner of the viewport.
            * </p>
            * </dd>
            * <dt>false</dt>
            * <dd>
            * To disable fixed center positioning.
            * <p>In this case the overlay can still be 
            * centered as a one-off operation, by invoking the <code>center()</code> method,
            * however it will not remain centered when the window is scrolled/resized.
            * </dd>
            * <dt>"contained"<dt>
            * <dd>To enable fixed center positioning, as with the <code>true</code> option.
            * <p>However, unlike setting the property to <code>true</code>, 
            * when the property is set to <code>"contained"</code>, if the overlay is 
            * too big for the viewport, it will not get automatically centered when the 
            * user scrolls or resizes the window (until the window is large enough to contain the 
            * overlay). This is useful in cases where the Overlay has both header and footer 
            * UI controls which the user may need to access.
            * </p>
            * </dd>
            * </dl>
            *
            * @config fixedcenter
            * @type Boolean | String
            * @default false
            */
            cfg.addProperty(DEFAULT_CONFIG.FIXED_CENTER.key, {
                handler: this.configFixedCenter,
                value: DEFAULT_CONFIG.FIXED_CENTER.value, 
                validator: DEFAULT_CONFIG.FIXED_CENTER.validator, 
                supercedes: DEFAULT_CONFIG.FIXED_CENTER.supercedes
            });
    
            /**
            * CSS width of the Overlay.
            * @config width
            * @type String
            * @default null
            */
            cfg.addProperty(DEFAULT_CONFIG.WIDTH.key, {
                handler: this.configWidth, 
                suppressEvent: DEFAULT_CONFIG.WIDTH.suppressEvent, 
                supercedes: DEFAULT_CONFIG.WIDTH.supercedes
            });

            /**
            * CSS height of the Overlay.
            * @config height
            * @type String
            * @default null
            */
            cfg.addProperty(DEFAULT_CONFIG.HEIGHT.key, {
                handler: this.configHeight, 
                suppressEvent: DEFAULT_CONFIG.HEIGHT.suppressEvent, 
                supercedes: DEFAULT_CONFIG.HEIGHT.supercedes
            });

            /**
            * Standard module element which should auto fill out the height of the Overlay if the height config property is set.
            * Supported values are "header", "body", "footer".
            *
            * @config autofillheight
            * @type String
            * @default null
            */
            cfg.addProperty(DEFAULT_CONFIG.AUTO_FILL_HEIGHT.key, {
                handler: this.configAutoFillHeight, 
                value : DEFAULT_CONFIG.AUTO_FILL_HEIGHT.value,
                validator : this._validateAutoFill,
                supercedes: DEFAULT_CONFIG.AUTO_FILL_HEIGHT.supercedes
            });

            /**
            * CSS z-index of the Overlay.
            * @config zIndex
            * @type Number
            * @default null
            */
            cfg.addProperty(DEFAULT_CONFIG.ZINDEX.key, {
                handler: this.configzIndex,
                value: DEFAULT_CONFIG.ZINDEX.value
            });

            /**
            * True if the Overlay should be prevented from being positioned 
            * out of the viewport.
            * @config constraintoviewport
            * @type Boolean
            * @default false
            */
            cfg.addProperty(DEFAULT_CONFIG.CONSTRAIN_TO_VIEWPORT.key, {

                handler: this.configConstrainToViewport, 
                value: DEFAULT_CONFIG.CONSTRAIN_TO_VIEWPORT.value, 
                validator: DEFAULT_CONFIG.CONSTRAIN_TO_VIEWPORT.validator, 
                supercedes: DEFAULT_CONFIG.CONSTRAIN_TO_VIEWPORT.supercedes

            });

            /**
            * @config iframe
            * @description Boolean indicating whether or not the Overlay should 
            * have an IFRAME shim; used to prevent SELECT elements from 
            * poking through an Overlay instance in IE6.  When set to "true", 
            * the iframe shim is created when the Overlay instance is intially
            * made visible.
            * @type Boolean
            * @default true for IE6 and below, false for all other browsers.
            */
            cfg.addProperty(DEFAULT_CONFIG.IFRAME.key, {

                handler: this.configIframe, 
                value: DEFAULT_CONFIG.IFRAME.value, 
                validator: DEFAULT_CONFIG.IFRAME.validator, 
                supercedes: DEFAULT_CONFIG.IFRAME.supercedes

            });

            /**
            * @config preventcontextoverlap
            * @description Boolean indicating whether or not the Overlay should overlap its 
            * context element (defined using the "context" configuration property) when the 
            * "constraintoviewport" configuration property is set to "true".
            * @type Boolean
            * @default false
            */
            cfg.addProperty(DEFAULT_CONFIG.PREVENT_CONTEXT_OVERLAP.key, {
                value: DEFAULT_CONFIG.PREVENT_CONTEXT_OVERLAP.value, 
                validator: DEFAULT_CONFIG.PREVENT_CONTEXT_OVERLAP.validator, 
                supercedes: DEFAULT_CONFIG.PREVENT_CONTEXT_OVERLAP.supercedes
            });
        },

        /**
        * Moves the Overlay to the specified position. This function is  
        * identical to calling this.cfg.setProperty("xy", [x,y]);
        * @method moveTo
        * @param {Number} x The Overlay's new x position
        * @param {Number} y The Overlay's new y position
        */
        moveTo: function (x, y) {
            this.cfg.setProperty("xy", [x, y]);
        },

        /**
        * Adds a CSS class ("hide-scrollbars") and removes a CSS class 
        * ("show-scrollbars") to the Overlay to fix a bug in Gecko on Mac OS X 
        * (https://bugzilla.mozilla.org/show_bug.cgi?id=187435)
        * @method hideMacGeckoScrollbars
        */
        hideMacGeckoScrollbars: function () {
            Dom.replaceClass(this.element, "show-scrollbars", "hide-scrollbars");
        },

        /**
        * Adds a CSS class ("show-scrollbars") and removes a CSS class 
        * ("hide-scrollbars") to the Overlay to fix a bug in Gecko on Mac OS X 
        * (https://bugzilla.mozilla.org/show_bug.cgi?id=187435)
        * @method showMacGeckoScrollbars
        */
        showMacGeckoScrollbars: function () {
            Dom.replaceClass(this.element, "hide-scrollbars", "show-scrollbars");
        },

        /**
         * Internal implementation to set the visibility of the overlay in the DOM.
         *
         * @method _setDomVisibility
         * @param {boolean} visible Whether to show or hide the Overlay's outer element
         * @protected
         */
        _setDomVisibility : function(show) {
            Dom.setStyle(this.element, "visibility", (show) ? "visible" : "hidden");
            var hiddenClass = Overlay.CSS_HIDDEN;

            if (show) {
                Dom.removeClass(this.element, hiddenClass);
            } else {
                Dom.addClass(this.element, hiddenClass);
            }
        },

        // BEGIN BUILT-IN PROPERTY EVENT HANDLERS //
        /**
        * The default event handler fired when the "visible" property is 
        * changed.  This method is responsible for firing showEvent
        * and hideEvent.
        * @method configVisible
        * @param {String} type The CustomEvent type (usually the property name)
        * @param {Object[]} args The CustomEvent arguments. For configuration
        * handlers, args[0] will equal the newly applied value for the property.
        * @param {Object} obj The scope object. For configuration handlers, 
        * this will usually equal the owner.
        */
        configVisible: function (type, args, obj) {

            var visible = args[0],
                currentVis = Dom.getStyle(this.element, "visibility"),
                effects = this._cachedEffects || this._createEffects(this.cfg.getProperty("effect")),
                isMacGecko = (this.platform == "mac" && UA.gecko),
                alreadySubscribed = Config.alreadySubscribed,
                ei, e, j, k, h,
                nEffectInstances;

            if (currentVis == "inherit") {
                e = this.element.parentNode;

                while (e.nodeType != 9 && e.nodeType != 11) {
                    currentVis = Dom.getStyle(e, "visibility");

                    if (currentVis != "inherit") {
                        break;
                    }

                    e = e.parentNode;
                }

                if (currentVis == "inherit") {
                    currentVis = "visible";
                }
            }

            if (visible) { // Show

                if (isMacGecko) {
                    this.showMacGeckoScrollbars();
                }

                if (effects) { // Animate in
                    if (visible) { // Animate in if not showing

                         // Fading out is a bit of a hack, but didn't want to risk doing 
                         // something broader (e.g a generic this._animatingOut) for 2.9.0

                        if (currentVis != "visible" || currentVis === "" || this._fadingOut) {
                            if (this.beforeShowEvent.fire()) {

                                nEffectInstances = effects.length;

                                for (j = 0; j < nEffectInstances; j++) {
                                    ei = effects[j];
                                    if (j === 0 && !alreadySubscribed(ei.animateInCompleteEvent, this.showEvent.fire, this.showEvent)) {
                                        ei.animateInCompleteEvent.subscribe(this.showEvent.fire, this.showEvent, true);
                                    }
                                    ei.animateIn();
                                }
                            }
                        }
                    }
                } else { // Show
                    if (currentVis != "visible" || currentVis === "") {
                        if (this.beforeShowEvent.fire()) {
                            this._setDomVisibility(true);
                            this.cfg.refireEvent("iframe");
                            this.showEvent.fire();
                        }
                    } else {
                        this._setDomVisibility(true);
                    }
                }
            } else { // Hide

                if (isMacGecko) {
                    this.hideMacGeckoScrollbars();
                }

                if (effects) { // Animate out if showing
                    if (currentVis == "visible" || this._fadingIn) {
                        if (this.beforeHideEvent.fire()) {
                            nEffectInstances = effects.length;
                            for (k = 0; k < nEffectInstances; k++) {
                                h = effects[k];
        
                                if (k === 0 && !alreadySubscribed(h.animateOutCompleteEvent, this.hideEvent.fire, this.hideEvent)) {
                                    h.animateOutCompleteEvent.subscribe(this.hideEvent.fire, this.hideEvent, true);
                                }
                                h.animateOut();
                            }
                        }

                    } else if (currentVis === "") {
                        this._setDomVisibility(false);
                    }

                } else { // Simple hide

                    if (currentVis == "visible" || currentVis === "") {
                        if (this.beforeHideEvent.fire()) {
                            this._setDomVisibility(false);
                            this.hideEvent.fire();
                        }
                    } else {
                        this._setDomVisibility(false);
                    }
                }
            }
        },

        /**
        * Fixed center event handler used for centering on scroll/resize, but only if 
        * the overlay is visible and, if "fixedcenter" is set to "contained", only if 
        * the overlay fits within the viewport.
        *
        * @method doCenterOnDOMEvent
        */
        doCenterOnDOMEvent: function () {
            var cfg = this.cfg,
                fc = cfg.getProperty("fixedcenter");

            if (cfg.getProperty("visible")) {
                if (fc && (fc !== _CONTAINED || this.fitsInViewport())) {
                    this.center();
                }
            }
        },

        /**
         * Determines if the Overlay (including the offset value defined by Overlay.VIEWPORT_OFFSET) 
         * will fit entirely inside the viewport, in both dimensions - width and height.
         * 
         * @method fitsInViewport
         * @return boolean true if the Overlay will fit, false if not
         */
        fitsInViewport : function() {
            var nViewportOffset = Overlay.VIEWPORT_OFFSET,
                element = this.element,
                elementWidth = element.offsetWidth,
                elementHeight = element.offsetHeight,
                viewportWidth = Dom.getViewportWidth(),
                viewportHeight = Dom.getViewportHeight();

            return ((elementWidth + nViewportOffset < viewportWidth) && (elementHeight + nViewportOffset < viewportHeight));
        },

        /**
        * The default event handler fired when the "fixedcenter" property 
        * is changed.
        * @method configFixedCenter
        * @param {String} type The CustomEvent type (usually the property name)
        * @param {Object[]} args The CustomEvent arguments. For configuration 
        * handlers, args[0] will equal the newly applied value for the property.
        * @param {Object} obj The scope object. For configuration handlers, 
        * this will usually equal the owner.
        */
        configFixedCenter: function (type, args, obj) {

            var val = args[0],
                alreadySubscribed = Config.alreadySubscribed,
                windowResizeEvent = Overlay.windowResizeEvent,
                windowScrollEvent = Overlay.windowScrollEvent;

            if (val) {
                this.center();

                if (!alreadySubscribed(this.beforeShowEvent, this.center)) {
                    this.beforeShowEvent.subscribe(this.center);
                }

                if (!alreadySubscribed(windowResizeEvent, this.doCenterOnDOMEvent, this)) {
                    windowResizeEvent.subscribe(this.doCenterOnDOMEvent, this, true);
                }

                if (!alreadySubscribed(windowScrollEvent, this.doCenterOnDOMEvent, this)) {
                    windowScrollEvent.subscribe(this.doCenterOnDOMEvent, this, true);
                }

            } else {
                this.beforeShowEvent.unsubscribe(this.center);

                windowResizeEvent.unsubscribe(this.doCenterOnDOMEvent, this);
                windowScrollEvent.unsubscribe(this.doCenterOnDOMEvent, this);
            }
        },

        /**
        * The default event handler fired when the "height" property is changed.
        * @method configHeight
        * @param {String} type The CustomEvent type (usually the property name)
        * @param {Object[]} args The CustomEvent arguments. For configuration 
        * handlers, args[0] will equal the newly applied value for the property.
        * @param {Object} obj The scope object. For configuration handlers, 
        * this will usually equal the owner.
        */
        configHeight: function (type, args, obj) {

            var height = args[0],
                el = this.element;

            Dom.setStyle(el, "height", height);
            this.cfg.refireEvent("iframe");
        },

        /**
         * The default event handler fired when the "autofillheight" property is changed.
         * @method configAutoFillHeight
         *
         * @param {String} type The CustomEvent type (usually the property name)
         * @param {Object[]} args The CustomEvent arguments. For configuration 
         * handlers, args[0] will equal the newly applied value for the property.
         * @param {Object} obj The scope object. For configuration handlers, 
         * this will usually equal the owner.
         */
        configAutoFillHeight: function (type, args, obj) {
            var fillEl = args[0],
                cfg = this.cfg,
                autoFillHeight = "autofillheight",
                height = "height",
                currEl = cfg.getProperty(autoFillHeight),
                autoFill = this._autoFillOnHeightChange;

            cfg.unsubscribeFromConfigEvent(height, autoFill);
            Module.textResizeEvent.unsubscribe(autoFill);
            this.changeContentEvent.unsubscribe(autoFill);

            if (currEl && fillEl !== currEl && this[currEl]) {
                Dom.setStyle(this[currEl], height, "");
            }

            if (fillEl) {
                fillEl = Lang.trim(fillEl.toLowerCase());

                cfg.subscribeToConfigEvent(height, autoFill, this[fillEl], this);
                Module.textResizeEvent.subscribe(autoFill, this[fillEl], this);
                this.changeContentEvent.subscribe(autoFill, this[fillEl], this);

                cfg.setProperty(autoFillHeight, fillEl, true);
            }
        },

        /**
        * The default event handler fired when the "width" property is changed.
        * @method configWidth
        * @param {String} type The CustomEvent type (usually the property name)
        * @param {Object[]} args The CustomEvent arguments. For configuration 
        * handlers, args[0] will equal the newly applied value for the property.
        * @param {Object} obj The scope object. For configuration handlers, 
        * this will usually equal the owner.
        */
        configWidth: function (type, args, obj) {

            var width = args[0],
                el = this.element;

            Dom.setStyle(el, "width", width);
            this.cfg.refireEvent("iframe");
        },

        /**
        * The default event handler fired when the "zIndex" property is changed.
        * @method configzIndex
        * @param {String} type The CustomEvent type (usually the property name)
        * @param {Object[]} args The CustomEvent arguments. For configuration 
        * handlers, args[0] will equal the newly applied value for the property.
        * @param {Object} obj The scope object. For configuration handlers, 
        * this will usually equal the owner.
        */
        configzIndex: function (type, args, obj) {

            var zIndex = args[0],
                el = this.element;

            if (! zIndex) {
                zIndex = Dom.getStyle(el, "zIndex");
                if (! zIndex || isNaN(zIndex)) {
                    zIndex = 0;
                }
            }

            if (this.iframe || this.cfg.getProperty("iframe") === true) {
                if (zIndex <= 0) {
                    zIndex = 1;
                }
            }

            Dom.setStyle(el, "zIndex", zIndex);
            this.cfg.setProperty("zIndex", zIndex, true);

            if (this.iframe) {
                this.stackIframe();
            }
        },

        /**
        * The default event handler fired when the "xy" property is changed.
        * @method configXY
        * @param {String} type The CustomEvent type (usually the property name)
        * @param {Object[]} args The CustomEvent arguments. For configuration 
        * handlers, args[0] will equal the newly applied value for the property.
        * @param {Object} obj The scope object. For configuration handlers, 
        * this will usually equal the owner.
        */
        configXY: function (type, args, obj) {

            var pos = args[0],
                x = pos[0],
                y = pos[1];

            this.cfg.setProperty("x", x);
            this.cfg.setProperty("y", y);

            this.beforeMoveEvent.fire([x, y]);

            x = this.cfg.getProperty("x");
            y = this.cfg.getProperty("y");


            this.cfg.refireEvent("iframe");
            this.moveEvent.fire([x, y]);
        },

        /**
        * The default event handler fired when the "x" property is changed.
        * @method configX
        * @param {String} type The CustomEvent type (usually the property name)
        * @param {Object[]} args The CustomEvent arguments. For configuration 
        * handlers, args[0] will equal the newly applied value for the property.
        * @param {Object} obj The scope object. For configuration handlers, 
        * this will usually equal the owner.
        */
        configX: function (type, args, obj) {

            var x = args[0],
                y = this.cfg.getProperty("y");

            this.cfg.setProperty("x", x, true);
            this.cfg.setProperty("y", y, true);

            this.beforeMoveEvent.fire([x, y]);

            x = this.cfg.getProperty("x");
            y = this.cfg.getProperty("y");

            Dom.setX(this.element, x, true);

            this.cfg.setProperty("xy", [x, y], true);

            this.cfg.refireEvent("iframe");
            this.moveEvent.fire([x, y]);
        },

        /**
        * The default event handler fired when the "y" property is changed.
        * @method configY
        * @param {String} type The CustomEvent type (usually the property name)
        * @param {Object[]} args The CustomEvent arguments. For configuration 
        * handlers, args[0] will equal the newly applied value for the property.
        * @param {Object} obj The scope object. For configuration handlers, 
        * this will usually equal the owner.
        */
        configY: function (type, args, obj) {

            var x = this.cfg.getProperty("x"),
                y = args[0];

            this.cfg.setProperty("x", x, true);
            this.cfg.setProperty("y", y, true);

            this.beforeMoveEvent.fire([x, y]);

            x = this.cfg.getProperty("x");
            y = this.cfg.getProperty("y");

            Dom.setY(this.element, y, true);

            this.cfg.setProperty("xy", [x, y], true);

            this.cfg.refireEvent("iframe");
            this.moveEvent.fire([x, y]);
        },
        
        /**
        * Shows the iframe shim, if it has been enabled.
        * @method showIframe
        */
        showIframe: function () {

            var oIFrame = this.iframe,
                oParentNode;

            if (oIFrame) {
                oParentNode = this.element.parentNode;

                if (oParentNode != oIFrame.parentNode) {
                    this._addToParent(oParentNode, oIFrame);
                }
                oIFrame.style.display = "block";
            }
        },

        /**
        * Hides the iframe shim, if it has been enabled.
        * @method hideIframe
        */
        hideIframe: function () {
            if (this.iframe) {
                this.iframe.style.display = "none";
            }
        },

        /**
        * Syncronizes the size and position of iframe shim to that of its 
        * corresponding Overlay instance.
        * @method syncIframe
        */
        syncIframe: function () {

            var oIFrame = this.iframe,
                oElement = this.element,
                nOffset = Overlay.IFRAME_OFFSET,
                nDimensionOffset = (nOffset * 2),
                aXY;

            if (oIFrame) {
                // Size <iframe>
                oIFrame.style.width = (oElement.offsetWidth + nDimensionOffset + "px");
                oIFrame.style.height = (oElement.offsetHeight + nDimensionOffset + "px");

                // Position <iframe>
                aXY = this.cfg.getProperty("xy");

                if (!Lang.isArray(aXY) || (isNaN(aXY[0]) || isNaN(aXY[1]))) {
                    this.syncPosition();
                    aXY = this.cfg.getProperty("xy");
                }
                Dom.setXY(oIFrame, [(aXY[0] - nOffset), (aXY[1] - nOffset)]);
            }
        },

        /**
         * Sets the zindex of the iframe shim, if it exists, based on the zindex of
         * the Overlay element. The zindex of the iframe is set to be one less 
         * than the Overlay element's zindex.
         * 
         * <p>NOTE: This method will not bump up the zindex of the Overlay element
         * to ensure that the iframe shim has a non-negative zindex.
         * If you require the iframe zindex to be 0 or higher, the zindex of 
         * the Overlay element should be set to a value greater than 0, before 
         * this method is called.
         * </p>
         * @method stackIframe
         */
        stackIframe: function () {
            if (this.iframe) {
                var overlayZ = Dom.getStyle(this.element, "zIndex");
                if (!YAHOO.lang.isUndefined(overlayZ) && !isNaN(overlayZ)) {
                    Dom.setStyle(this.iframe, "zIndex", (overlayZ - 1));
                }
            }
        },

        /**
        * The default event handler fired when the "iframe" property is changed.
        * @method configIframe
        * @param {String} type The CustomEvent type (usually the property name)
        * @param {Object[]} args The CustomEvent arguments. For configuration 
        * handlers, args[0] will equal the newly applied value for the property.
        * @param {Object} obj The scope object. For configuration handlers, 
        * this will usually equal the owner.
        */
        configIframe: function (type, args, obj) {

            var bIFrame = args[0];

            function createIFrame() {

                var oIFrame = this.iframe,
                    oElement = this.element,
                    oParent;

                if (!oIFrame) {
                    if (!m_oIFrameTemplate) {
                        m_oIFrameTemplate = document.createElement("iframe");

                        if (this.isSecure) {
                            m_oIFrameTemplate.src = Overlay.IFRAME_SRC;
                        }

                        /*
                            Set the opacity of the <iframe> to 0 so that it 
                            doesn't modify the opacity of any transparent 
                            elements that may be on top of it (like a shadow).
                        */
                        if (UA.ie) {
                            m_oIFrameTemplate.style.filter = "alpha(opacity=0)";
                            /*
                                 Need to set the "frameBorder" property to 0 
                                 supress the default <iframe> border in IE.  
                                 Setting the CSS "border" property alone 
                                 doesn't supress it.
                            */
                            m_oIFrameTemplate.frameBorder = 0;
                        }
                        else {
                            m_oIFrameTemplate.style.opacity = "0";
                        }

                        m_oIFrameTemplate.style.position = "absolute";
                        m_oIFrameTemplate.style.border = "none";
                        m_oIFrameTemplate.style.margin = "0";
                        m_oIFrameTemplate.style.padding = "0";
                        m_oIFrameTemplate.style.display = "none";
                        m_oIFrameTemplate.tabIndex = -1;
                        m_oIFrameTemplate.className = Overlay.CSS_IFRAME;
                    }

                    oIFrame = m_oIFrameTemplate.cloneNode(false);
                    oIFrame.id = this.id + "_f";
                    oParent = oElement.parentNode;

                    var parentNode = oParent || document.body;

                    this._addToParent(parentNode, oIFrame);
                    this.iframe = oIFrame;
                }

                /*
                     Show the <iframe> before positioning it since the "setXY" 
                     method of DOM requires the element be in the document 
                     and visible.
                */
                this.showIframe();

                /*
                     Syncronize the size and position of the <iframe> to that 
                     of the Overlay.
                */
                this.syncIframe();
                this.stackIframe();

                // Add event listeners to update the <iframe> when necessary
                if (!this._hasIframeEventListeners) {
                    this.showEvent.subscribe(this.showIframe);
                    this.hideEvent.subscribe(this.hideIframe);
                    this.changeContentEvent.subscribe(this.syncIframe);

                    this._hasIframeEventListeners = true;
                }
            }

            function onBeforeShow() {
                createIFrame.call(this);
                this.beforeShowEvent.unsubscribe(onBeforeShow);
                this._iframeDeferred = false;
            }

            if (bIFrame) { // <iframe> shim is enabled

                if (this.cfg.getProperty("visible")) {
                    createIFrame.call(this);
                } else {
                    if (!this._iframeDeferred) {
                        this.beforeShowEvent.subscribe(onBeforeShow);
                        this._iframeDeferred = true;
                    }
                }

            } else {    // <iframe> shim is disabled
                this.hideIframe();

                if (this._hasIframeEventListeners) {
                    this.showEvent.unsubscribe(this.showIframe);
                    this.hideEvent.unsubscribe(this.hideIframe);
                    this.changeContentEvent.unsubscribe(this.syncIframe);

                    this._hasIframeEventListeners = false;
                }
            }
        },

        /**
         * Set's the container's XY value from DOM if not already set.
         * 
         * Differs from syncPosition, in that the XY value is only sync'd with DOM if 
         * not already set. The method also refire's the XY config property event, so any
         * beforeMove, Move event listeners are invoked.
         * 
         * @method _primeXYFromDOM
         * @protected
         */
        _primeXYFromDOM : function() {
            if (YAHOO.lang.isUndefined(this.cfg.getProperty("xy"))) {
                // Set CFG XY based on DOM XY
                this.syncPosition();
                // Account for XY being set silently in syncPosition (no moveTo fired/called)
                this.cfg.refireEvent("xy");
                this.beforeShowEvent.unsubscribe(this._primeXYFromDOM);
            }
        },

        /**
        * The default event handler fired when the "constraintoviewport" 
        * property is changed.
        * @method configConstrainToViewport
        * @param {String} type The CustomEvent type (usually the property name)
        * @param {Object[]} args The CustomEvent arguments. For configuration 
        * handlers, args[0] will equal the newly applied value for 
        * the property.
        * @param {Object} obj The scope object. For configuration handlers, 
        * this will usually equal the owner.
        */
        configConstrainToViewport: function (type, args, obj) {
            var val = args[0];

            if (val) {
                if (! Config.alreadySubscribed(this.beforeMoveEvent, this.enforceConstraints, this)) {
                    this.beforeMoveEvent.subscribe(this.enforceConstraints, this, true);
                }
                if (! Config.alreadySubscribed(this.beforeShowEvent, this._primeXYFromDOM)) {
                    this.beforeShowEvent.subscribe(this._primeXYFromDOM);
                }
            } else {
                this.beforeShowEvent.unsubscribe(this._primeXYFromDOM);
                this.beforeMoveEvent.unsubscribe(this.enforceConstraints, this);
            }
        },

         /**
        * The default event handler fired when the "context" property
        * is changed.
        *
        * @method configContext
        * @param {String} type The CustomEvent type (usually the property name)
        * @param {Object[]} args The CustomEvent arguments. For configuration 
        * handlers, args[0] will equal the newly applied value for the property.
        * @param {Object} obj The scope object. For configuration handlers, 
        * this will usually equal the owner.
        */
        configContext: function (type, args, obj) {

            var contextArgs = args[0],
                contextEl,
                elementMagnetCorner,
                contextMagnetCorner,
                triggers,
                offset,
                defTriggers = this.CONTEXT_TRIGGERS;

            if (contextArgs) {

                contextEl = contextArgs[0];
                elementMagnetCorner = contextArgs[1];
                contextMagnetCorner = contextArgs[2];
                triggers = contextArgs[3];
                offset = contextArgs[4];

                if (defTriggers && defTriggers.length > 0) {
                    triggers = (triggers || []).concat(defTriggers);
                }

                if (contextEl) {
                    if (typeof contextEl == "string") {
                        this.cfg.setProperty("context", [
                                document.getElementById(contextEl), 
                                elementMagnetCorner,
                                contextMagnetCorner,
                                triggers,
                                offset],
                                true);
                    }

                    if (elementMagnetCorner && contextMagnetCorner) {
                        this.align(elementMagnetCorner, contextMagnetCorner, offset);
                    }

                    if (this._contextTriggers) {
                        // Unsubscribe Old Set
                        this._processTriggers(this._contextTriggers, _UNSUBSCRIBE, this._alignOnTrigger);
                    }

                    if (triggers) {
                        // Subscribe New Set
                        this._processTriggers(triggers, _SUBSCRIBE, this._alignOnTrigger);
                        this._contextTriggers = triggers;
                    }
                }
            }
        },

        /**
         * Custom Event handler for context alignment triggers. Invokes the align method
         * 
         * @method _alignOnTrigger
         * @protected
         * 
         * @param {String} type The event type (not used by the default implementation)
         * @param {Any[]} args The array of arguments for the trigger event (not used by the default implementation)
         */
        _alignOnTrigger: function(type, args) {
            this.align();
        },

        /**
         * Helper method to locate the custom event instance for the event name string
         * passed in. As a convenience measure, any custom events passed in are returned.
         *
         * @method _findTriggerCE
         * @private
         *
         * @param {String|CustomEvent} t Either a CustomEvent, or event type (e.g. "windowScroll") for which a 
         * custom event instance needs to be looked up from the Overlay._TRIGGER_MAP.
         */
        _findTriggerCE : function(t) {
            var tce = null;
            if (t instanceof CustomEvent) {
                tce = t;
            } else if (Overlay._TRIGGER_MAP[t]) {
                tce = Overlay._TRIGGER_MAP[t];
            }
            return tce;
        },

        /**
         * Utility method that subscribes or unsubscribes the given 
         * function from the list of trigger events provided.
         *
         * @method _processTriggers
         * @protected 
         *
         * @param {Array[String|CustomEvent]} triggers An array of either CustomEvents, event type strings 
         * (e.g. "beforeShow", "windowScroll") to/from which the provided function should be 
         * subscribed/unsubscribed respectively.
         *
         * @param {String} mode Either "subscribe" or "unsubscribe", specifying whether or not
         * we are subscribing or unsubscribing trigger listeners
         * 
         * @param {Function} fn The function to be subscribed/unsubscribed to/from the trigger event.
         * Context is always set to the overlay instance, and no additional object argument 
         * get passed to the subscribed function.
         */
        _processTriggers : function(triggers, mode, fn) {
            var t, tce;

            for (var i = 0, l = triggers.length; i < l; ++i) {
                t = triggers[i];
                tce = this._findTriggerCE(t);
                if (tce) {
                    tce[mode](fn, this, true);
                } else {
                    this[mode](t, fn);
                }
            }
        },

        // END BUILT-IN PROPERTY EVENT HANDLERS //
        /**
        * Aligns the Overlay to its context element using the specified corner 
        * points (represented by the constants TOP_LEFT, TOP_RIGHT, BOTTOM_LEFT, 
        * and BOTTOM_RIGHT.
        * @method align
        * @param {String} elementAlign  The String representing the corner of 
        * the Overlay that should be aligned to the context element
        * @param {String} contextAlign  The corner of the context element 
        * that the elementAlign corner should stick to.
        * @param {Number[]} xyOffset Optional. A 2 element array specifying the x and y pixel offsets which should be applied
        * after aligning the element and context corners. For example, passing in [5, -10] for this value, would offset the 
        * Overlay by 5 pixels along the X axis (horizontally) and -10 pixels along the Y axis (vertically) after aligning the specified corners.
        */
        align: function (elementAlign, contextAlign, xyOffset) {

            var contextArgs = this.cfg.getProperty("context"),
                me = this,
                context,
                element,
                contextRegion;

            function doAlign(v, h) {

                var alignX = null, alignY = null;

                switch (elementAlign) {
    
                    case Overlay.TOP_LEFT:
                        alignX = h;
                        alignY = v;
                        break;
        
                    case Overlay.TOP_RIGHT:
                        alignX = h - element.offsetWidth;
                        alignY = v;
                        break;
        
                    case Overlay.BOTTOM_LEFT:
                        alignX = h;
                        alignY = v - element.offsetHeight;
                        break;
        
                    case Overlay.BOTTOM_RIGHT:
                        alignX = h - element.offsetWidth; 
                        alignY = v - element.offsetHeight;
                        break;
                }

                if (alignX !== null && alignY !== null) {
                    if (xyOffset) {
                        alignX += xyOffset[0];
                        alignY += xyOffset[1];
                    }
                    me.moveTo(alignX, alignY);
                }
            }

            if (contextArgs) {
                context = contextArgs[0];
                element = this.element;
                me = this;

                if (! elementAlign) {
                    elementAlign = contextArgs[1];
                }

                if (! contextAlign) {
                    contextAlign = contextArgs[2];
                }

                if (!xyOffset && contextArgs[4]) {
                    xyOffset = contextArgs[4];
                }

                if (element && context) {
                    contextRegion = Dom.getRegion(context);

                    switch (contextAlign) {
    
                        case Overlay.TOP_LEFT:
                            doAlign(contextRegion.top, contextRegion.left);
                            break;
        
                        case Overlay.TOP_RIGHT:
                            doAlign(contextRegion.top, contextRegion.right);
                            break;
        
                        case Overlay.BOTTOM_LEFT:
                            doAlign(contextRegion.bottom, contextRegion.left);
                            break;
        
                        case Overlay.BOTTOM_RIGHT:
                            doAlign(contextRegion.bottom, contextRegion.right);
                            break;
                    }
                }
            }
        },

        /**
        * The default event handler executed when the moveEvent is fired, if the 
        * "constraintoviewport" is set to true.
        * @method enforceConstraints
        * @param {String} type The CustomEvent type (usually the property name)
        * @param {Object[]} args The CustomEvent arguments. For configuration 
        * handlers, args[0] will equal the newly applied value for the property.
        * @param {Object} obj The scope object. For configuration handlers, 
        * this will usually equal the owner.
        */
        enforceConstraints: function (type, args, obj) {
            var pos = args[0];

            var cXY = this.getConstrainedXY(pos[0], pos[1]);
            this.cfg.setProperty("x", cXY[0], true);
            this.cfg.setProperty("y", cXY[1], true);
            this.cfg.setProperty("xy", cXY, true);
        },

        /**
         * Shared implementation method for getConstrainedX and getConstrainedY.
         * 
         * <p>
         * Given a coordinate value, returns the calculated coordinate required to 
         * position the Overlay if it is to be constrained to the viewport, based on the 
         * current element size, viewport dimensions, scroll values and preventoverlap 
         * settings
         * </p>
         *
         * @method _getConstrainedPos
         * @protected
         * @param {String} pos The coordinate which needs to be constrained, either "x" or "y"
         * @param {Number} The coordinate value which needs to be constrained
         * @return {Number} The constrained coordinate value
         */
        _getConstrainedPos: function(pos, val) {

            var overlayEl = this.element,

                buffer = Overlay.VIEWPORT_OFFSET,

                x = (pos == "x"),

                overlaySize      = (x) ? overlayEl.offsetWidth : overlayEl.offsetHeight,
                viewportSize     = (x) ? Dom.getViewportWidth() : Dom.getViewportHeight(),
                docScroll        = (x) ? Dom.getDocumentScrollLeft() : Dom.getDocumentScrollTop(),
                overlapPositions = (x) ? Overlay.PREVENT_OVERLAP_X : Overlay.PREVENT_OVERLAP_Y,

                context = this.cfg.getProperty("context"),

                bOverlayFitsInViewport = (overlaySize + buffer < viewportSize),
                bPreventContextOverlap = this.cfg.getProperty("preventcontextoverlap") && context && overlapPositions[(context[1] + context[2])],

                minConstraint = docScroll + buffer,
                maxConstraint = docScroll + viewportSize - overlaySize - buffer,

                constrainedVal = val;

            if (val < minConstraint || val > maxConstraint) {
                if (bPreventContextOverlap) {
                    constrainedVal = this._preventOverlap(pos, context[0], overlaySize, viewportSize, docScroll);
                } else {
                    if (bOverlayFitsInViewport) {
                        if (val < minConstraint) {
                            constrainedVal = minConstraint;
                        } else if (val > maxConstraint) {
                            constrainedVal = maxConstraint;
                        }
                    } else {
                        constrainedVal = minConstraint;
                    }
                }
            }

            return constrainedVal;
        },

        /**
         * Helper method, used to position the Overlap to prevent overlap with the 
         * context element (used when preventcontextoverlap is enabled)
         *
         * @method _preventOverlap
         * @protected
         * @param {String} pos The coordinate to prevent overlap for, either "x" or "y".
         * @param {HTMLElement} contextEl The context element
         * @param {Number} overlaySize The related overlay dimension value (for "x", the width, for "y", the height)
         * @param {Number} viewportSize The related viewport dimension value (for "x", the width, for "y", the height)
         * @param {Object} docScroll  The related document scroll value (for "x", the scrollLeft, for "y", the scrollTop)
         *
         * @return {Number} The new coordinate value which was set to prevent overlap
         */
        _preventOverlap : function(pos, contextEl, overlaySize, viewportSize, docScroll) {
            
            var x = (pos == "x"),

                buffer = Overlay.VIEWPORT_OFFSET,

                overlay = this,

                contextElPos   = ((x) ? Dom.getX(contextEl) : Dom.getY(contextEl)) - docScroll,
                contextElSize  = (x) ? contextEl.offsetWidth : contextEl.offsetHeight,

                minRegionSize = contextElPos - buffer,
                maxRegionSize = (viewportSize - (contextElPos + contextElSize)) - buffer,

                bFlipped = false,

                flip = function () {
                    var flippedVal;

                    if ((overlay.cfg.getProperty(pos) - docScroll) > contextElPos) {
                        flippedVal = (contextElPos - overlaySize);
                    } else {
                        flippedVal = (contextElPos + contextElSize);
                    }

                    overlay.cfg.setProperty(pos, (flippedVal + docScroll), true);

                    return flippedVal;
                },

                setPosition = function () {

                    var displayRegionSize = ((overlay.cfg.getProperty(pos) - docScroll) > contextElPos) ? maxRegionSize : minRegionSize,
                        position;

                    if (overlaySize > displayRegionSize) {
                        if (bFlipped) {
                            /*
                                 All possible positions and values have been 
                                 tried, but none were successful, so fall back 
                                 to the original size and position.
                            */
                            flip();
                        } else {
                            flip();
                            bFlipped = true;
                            position = setPosition();
                        }
                    }

                    return position;
                };

            setPosition();

            return this.cfg.getProperty(pos);
        },

        /**
         * Given x coordinate value, returns the calculated x coordinate required to 
         * position the Overlay if it is to be constrained to the viewport, based on the 
         * current element size, viewport dimensions and scroll values.
         *
         * @param {Number} x The X coordinate value to be constrained
         * @return {Number} The constrained x coordinate
         */		
        getConstrainedX: function (x) {
            return this._getConstrainedPos("x", x);
        },

        /**
         * Given y coordinate value, returns the calculated y coordinate required to 
         * position the Overlay if it is to be constrained to the viewport, based on the 
         * current element size, viewport dimensions and scroll values.
         *
         * @param {Number} y The Y coordinate value to be constrained
         * @return {Number} The constrained y coordinate
         */		
        getConstrainedY : function (y) {
            return this._getConstrainedPos("y", y);
        },

        /**
         * Given x, y coordinate values, returns the calculated coordinates required to 
         * position the Overlay if it is to be constrained to the viewport, based on the 
         * current element size, viewport dimensions and scroll values.
         *
         * @param {Number} x The X coordinate value to be constrained
         * @param {Number} y The Y coordinate value to be constrained
         * @return {Array} The constrained x and y coordinates at index 0 and 1 respectively;
         */
        getConstrainedXY: function(x, y) {
            return [this.getConstrainedX(x), this.getConstrainedY(y)];
        },

        /**
        * Centers the container in the viewport.
        * @method center
        */
        center: function () {

            var nViewportOffset = Overlay.VIEWPORT_OFFSET,
                elementWidth = this.element.offsetWidth,
                elementHeight = this.element.offsetHeight,
                viewPortWidth = Dom.getViewportWidth(),
                viewPortHeight = Dom.getViewportHeight(),
                x,
                y;

            if (elementWidth < viewPortWidth) {
                x = (viewPortWidth / 2) - (elementWidth / 2) + Dom.getDocumentScrollLeft();
            } else {
                x = nViewportOffset + Dom.getDocumentScrollLeft();
            }

            if (elementHeight < viewPortHeight) {
                y = (viewPortHeight / 2) - (elementHeight / 2) + Dom.getDocumentScrollTop();
            } else {
                y = nViewportOffset + Dom.getDocumentScrollTop();
            }

            this.cfg.setProperty("xy", [parseInt(x, 10), parseInt(y, 10)]);
            this.cfg.refireEvent("iframe");

            if (UA.webkit) {
                this.forceContainerRedraw();
            }
        },

        /**
        * Synchronizes the Panel's "xy", "x", and "y" properties with the 
        * Panel's position in the DOM. This is primarily used to update  
        * position information during drag & drop.
        * @method syncPosition
        */
        syncPosition: function () {

            var pos = Dom.getXY(this.element);

            this.cfg.setProperty("x", pos[0], true);
            this.cfg.setProperty("y", pos[1], true);
            this.cfg.setProperty("xy", pos, true);

        },

        /**
        * Event handler fired when the resize monitor element is resized.
        * @method onDomResize
        * @param {DOMEvent} e The resize DOM event
        * @param {Object} obj The scope object
        */
        onDomResize: function (e, obj) {

            var me = this;

            Overlay.superclass.onDomResize.call(this, e, obj);

            setTimeout(function () {
                me.syncPosition();
                me.cfg.refireEvent("iframe");
                me.cfg.refireEvent("context");
            }, 0);
        },

        /**
         * Determines the content box height of the given element (height of the element, without padding or borders) in pixels.
         *
         * @method _getComputedHeight
         * @private
         * @param {HTMLElement} el The element for which the content height needs to be determined
         * @return {Number} The content box height of the given element, or null if it could not be determined.
         */
        _getComputedHeight : (function() {

            if (document.defaultView && document.defaultView.getComputedStyle) {
                return function(el) {
                    var height = null;
                    if (el.ownerDocument && el.ownerDocument.defaultView) {
                        var computed = el.ownerDocument.defaultView.getComputedStyle(el, '');
                        if (computed) {
                            height = parseInt(computed.height, 10);
                        }
                    }
                    return (Lang.isNumber(height)) ? height : null;
                };
            } else {
                return function(el) {
                    var height = null;
                    if (el.style.pixelHeight) {
                        height = el.style.pixelHeight;
                    }
                    return (Lang.isNumber(height)) ? height : null;
                };
            }
        })(),

        /**
         * autofillheight validator. Verifies that the autofill value is either null 
         * or one of the strings : "body", "header" or "footer".
         *
         * @method _validateAutoFillHeight
         * @protected
         * @param {String} val
         * @return true, if valid, false otherwise
         */
        _validateAutoFillHeight : function(val) {
            return (!val) || (Lang.isString(val) && Overlay.STD_MOD_RE.test(val));
        },

        /**
         * The default custom event handler executed when the overlay's height is changed, 
         * if the autofillheight property has been set.
         *
         * @method _autoFillOnHeightChange
         * @protected
         * @param {String} type The event type
         * @param {Array} args The array of arguments passed to event subscribers
         * @param {HTMLElement} el The header, body or footer element which is to be resized to fill
         * out the containers height
         */
        _autoFillOnHeightChange : function(type, args, el) {
            var height = this.cfg.getProperty("height");
            if ((height && height !== "auto") || (height === 0)) {
                this.fillHeight(el);
            }
        },

        /**
         * Returns the sub-pixel height of the el, using getBoundingClientRect, if available,
         * otherwise returns the offsetHeight
         * @method _getPreciseHeight
         * @private
         * @param {HTMLElement} el
         * @return {Float} The sub-pixel height if supported by the browser, else the rounded height.
         */
        _getPreciseHeight : function(el) {
            var height = el.offsetHeight;

            if (el.getBoundingClientRect) {
                var rect = el.getBoundingClientRect();
                height = rect.bottom - rect.top;
            }

            return height;
        },

        /**
         * <p>
         * Sets the height on the provided header, body or footer element to 
         * fill out the height of the container. It determines the height of the 
         * containers content box, based on it's configured height value, and 
         * sets the height of the autofillheight element to fill out any 
         * space remaining after the other standard module element heights 
         * have been accounted for.
         * </p>
         * <p><strong>NOTE:</strong> This method is not designed to work if an explicit 
         * height has not been set on the container, since for an "auto" height container, 
         * the heights of the header/body/footer will drive the height of the container.</p>
         *
         * @method fillHeight
         * @param {HTMLElement} el The element which should be resized to fill out the height
         * of the container element.
         */
        fillHeight : function(el) {
            if (el) {
                var container = this.innerElement || this.element,
                    containerEls = [this.header, this.body, this.footer],
                    containerEl,
                    total = 0,
                    filled = 0,
                    remaining = 0,
                    validEl = false;

                for (var i = 0, l = containerEls.length; i < l; i++) {
                    containerEl = containerEls[i];
                    if (containerEl) {
                        if (el !== containerEl) {
                            filled += this._getPreciseHeight(containerEl);
                        } else {
                            validEl = true;
                        }
                    }
                }

                if (validEl) {

                    if (UA.ie || UA.opera) {
                        // Need to set height to 0, to allow height to be reduced
                        Dom.setStyle(el, 'height', 0 + 'px');
                    }

                    total = this._getComputedHeight(container);

                    // Fallback, if we can't get computed value for content height
                    if (total === null) {
                        Dom.addClass(container, "yui-override-padding");
                        total = container.clientHeight; // Content, No Border, 0 Padding (set by yui-override-padding)
                        Dom.removeClass(container, "yui-override-padding");
                    }
    
                    remaining = Math.max(total - filled, 0);
    
                    Dom.setStyle(el, "height", remaining + "px");
    
                    // Re-adjust height if required, to account for el padding and border
                    if (el.offsetHeight != remaining) {
                        remaining = Math.max(remaining - (el.offsetHeight - remaining), 0);
                    }
                    Dom.setStyle(el, "height", remaining + "px");
                }
            }
        },

        /**
        * Places the Overlay on top of all other instances of 
        * YAHOO.widget.Overlay.
        * @method bringToTop
        */
        bringToTop: function () {

            var aOverlays = [],
                oElement = this.element;

            function compareZIndexDesc(p_oOverlay1, p_oOverlay2) {

                var sZIndex1 = Dom.getStyle(p_oOverlay1, "zIndex"),
                    sZIndex2 = Dom.getStyle(p_oOverlay2, "zIndex"),

                    nZIndex1 = (!sZIndex1 || isNaN(sZIndex1)) ? 0 : parseInt(sZIndex1, 10),
                    nZIndex2 = (!sZIndex2 || isNaN(sZIndex2)) ? 0 : parseInt(sZIndex2, 10);

                if (nZIndex1 > nZIndex2) {
                    return -1;
                } else if (nZIndex1 < nZIndex2) {
                    return 1;
                } else {
                    return 0;
                }
            }

            function isOverlayElement(p_oElement) {

                var isOverlay = Dom.hasClass(p_oElement, Overlay.CSS_OVERLAY),
                    Panel = YAHOO.widget.Panel;

                if (isOverlay && !Dom.isAncestor(oElement, p_oElement)) {
                    if (Panel && Dom.hasClass(p_oElement, Panel.CSS_PANEL)) {
                        aOverlays[aOverlays.length] = p_oElement.parentNode;
                    } else {
                        aOverlays[aOverlays.length] = p_oElement;
                    }
                }
            }

            Dom.getElementsBy(isOverlayElement, "div", document.body);

            aOverlays.sort(compareZIndexDesc);

            var oTopOverlay = aOverlays[0],
                nTopZIndex;

            if (oTopOverlay) {
                nTopZIndex = Dom.getStyle(oTopOverlay, "zIndex");

                if (!isNaN(nTopZIndex)) {
                    var bRequiresBump = false;

                    if (oTopOverlay != oElement) {
                        bRequiresBump = true;
                    } else if (aOverlays.length > 1) {
                        var nNextZIndex = Dom.getStyle(aOverlays[1], "zIndex");
                        // Don't rely on DOM order to stack if 2 overlays are at the same zindex.
                        if (!isNaN(nNextZIndex) && (nTopZIndex == nNextZIndex)) {
                            bRequiresBump = true;
                        }
                    }
                    if (bRequiresBump) {
                        this.cfg.setProperty("zindex", (parseInt(nTopZIndex, 10) + 2));
                    }
                }
            }
        },

        /**
        * Removes the Overlay element from the DOM and sets all child 
        * elements to null.
        * @method destroy
        * @param {boolean} shallowPurge If true, only the parent element's DOM event listeners are purged. If false, or not provided, all children are also purged of DOM event listeners. 
        * NOTE: The flag is a "shallowPurge" flag, as opposed to what may be a more intuitive "purgeChildren" flag to maintain backwards compatibility with behavior prior to 2.9.0.
        */
        destroy: function (shallowPurge) {

            if (this.iframe) {
                this.iframe.parentNode.removeChild(this.iframe);
            }

            this.iframe = null;

            Overlay.windowResizeEvent.unsubscribe(
                this.doCenterOnDOMEvent, this);
    
            Overlay.windowScrollEvent.unsubscribe(
                this.doCenterOnDOMEvent, this);

            Module.textResizeEvent.unsubscribe(this._autoFillOnHeightChange);

            if (this._contextTriggers) {
                // Unsubscribe context triggers - to cover context triggers which listen for global
                // events such as windowResize and windowScroll. Easier just to unsubscribe all
                this._processTriggers(this._contextTriggers, _UNSUBSCRIBE, this._alignOnTrigger);
            }

            Overlay.superclass.destroy.call(this, shallowPurge);
        },

        /**
         * Can be used to force the container to repaint/redraw it's contents.
         * <p>
         * By default applies and then removes a 1px bottom margin through the 
         * application/removal of a "yui-force-redraw" class.
         * </p>
         * <p>
         * It is currently used by Overlay to force a repaint for webkit 
         * browsers, when centering.
         * </p>
         * @method forceContainerRedraw
         */
        forceContainerRedraw : function() {
            var c = this;
            Dom.addClass(c.element, "yui-force-redraw");
            setTimeout(function() {
                Dom.removeClass(c.element, "yui-force-redraw");
            }, 0);
        },

        /**
        * Returns a String representation of the object.
        * @method toString
        * @return {String} The string representation of the Overlay.
        */
        toString: function () {
            return "Overlay " + this.id;
        }

    });
}());
(function () {

    /**
    * OverlayManager is used for maintaining the focus status of 
    * multiple Overlays.
    * @namespace YAHOO.widget
    * @namespace YAHOO.widget
    * @class OverlayManager
    * @constructor
    * @param {Array} overlays Optional. A collection of Overlays to register 
    * with the manager.
    * @param {Object} userConfig  The object literal representing the user 
    * configuration of the OverlayManager
    */
    YAHOO.widget.OverlayManager = function (userConfig) {
        this.init(userConfig);
    };

    var Overlay = YAHOO.widget.Overlay,
        Event = YAHOO.util.Event,
        Dom = YAHOO.util.Dom,
        Config = YAHOO.util.Config,
        CustomEvent = YAHOO.util.CustomEvent,
        OverlayManager = YAHOO.widget.OverlayManager;

    /**
    * The CSS class representing a focused Overlay
    * @property OverlayManager.CSS_FOCUSED
    * @static
    * @final
    * @type String
    */
    OverlayManager.CSS_FOCUSED = "focused";

    OverlayManager.prototype = {

        /**
        * The class's constructor function
        * @property contructor
        * @type Function
        */
        constructor: OverlayManager,

        /**
        * The array of Overlays that are currently registered
        * @property overlays
        * @type YAHOO.widget.Overlay[]
        */
        overlays: null,

        /**
        * Initializes the default configuration of the OverlayManager
        * @method initDefaultConfig
        */
        initDefaultConfig: function () {
            /**
            * The collection of registered Overlays in use by 
            * the OverlayManager
            * @config overlays
            * @type YAHOO.widget.Overlay[]
            * @default null
            */
            this.cfg.addProperty("overlays", { suppressEvent: true } );

            /**
            * The default DOM event that should be used to focus an Overlay
            * @config focusevent
            * @type String
            * @default "mousedown"
            */
            this.cfg.addProperty("focusevent", { value: "mousedown" } );
        },

        /**
        * Initializes the OverlayManager
        * @method init
        * @param {Overlay[]} overlays Optional. A collection of Overlays to 
        * register with the manager.
        * @param {Object} userConfig  The object literal representing the user 
        * configuration of the OverlayManager
        */
        init: function (userConfig) {

            /**
            * The OverlayManager's Config object used for monitoring 
            * configuration properties.
            * @property cfg
            * @type Config
            */
            this.cfg = new Config(this);

            this.initDefaultConfig();

            if (userConfig) {
                this.cfg.applyConfig(userConfig, true);
            }
            this.cfg.fireQueue();

            /**
            * The currently activated Overlay
            * @property activeOverlay
            * @private
            * @type YAHOO.widget.Overlay
            */
            var activeOverlay = null;

            /**
            * Returns the currently focused Overlay
            * @method getActive
            * @return {Overlay} The currently focused Overlay
            */
            this.getActive = function () {
                return activeOverlay;
            };

            /**
            * Focuses the specified Overlay
            * @method focus
            * @param {Overlay} overlay The Overlay to focus
            * @param {String} overlay The id of the Overlay to focus
            */
            this.focus = function (overlay) {
                var o = this.find(overlay);
                if (o) {
                    o.focus();
                }
            };

            /**
            * Removes the specified Overlay from the manager
            * @method remove
            * @param {Overlay} overlay The Overlay to remove
            * @param {String} overlay The id of the Overlay to remove
            */
            this.remove = function (overlay) {

                var o = this.find(overlay), 
                        originalZ;

                if (o) {
                    if (activeOverlay == o) {
                        activeOverlay = null;
                    }

                    var bDestroyed = (o.element === null && o.cfg === null) ? true : false;

                    if (!bDestroyed) {
                        // Set it's zindex so that it's sorted to the end.
                        originalZ = Dom.getStyle(o.element, "zIndex");
                        o.cfg.setProperty("zIndex", -1000, true);
                    }

                    this.overlays.sort(this.compareZIndexDesc);
                    this.overlays = this.overlays.slice(0, (this.overlays.length - 1));

                    o.hideEvent.unsubscribe(o.blur);
                    o.destroyEvent.unsubscribe(this._onOverlayDestroy, o);
                    o.focusEvent.unsubscribe(this._onOverlayFocusHandler, o);
                    o.blurEvent.unsubscribe(this._onOverlayBlurHandler, o);

                    if (!bDestroyed) {
                        Event.removeListener(o.element, this.cfg.getProperty("focusevent"), this._onOverlayElementFocus);
                        o.cfg.setProperty("zIndex", originalZ, true);
                        o.cfg.setProperty("manager", null);
                    }

                    /* _managed Flag for custom or existing. Don't want to remove existing */
                    if (o.focusEvent._managed) { o.focusEvent = null; }
                    if (o.blurEvent._managed) { o.blurEvent = null; }

                    if (o.focus._managed) { o.focus = null; }
                    if (o.blur._managed) { o.blur = null; }
                }
            };

            /**
            * Removes focus from all registered Overlays in the manager
            * @method blurAll
            */
            this.blurAll = function () {

                var nOverlays = this.overlays.length,
                    i;

                if (nOverlays > 0) {
                    i = nOverlays - 1;
                    do {
                        this.overlays[i].blur();
                    }
                    while(i--);
                }
            };

            /**
             * Updates the state of the OverlayManager and overlay, as a result of the overlay
             * being blurred.
             * 
             * @method _manageBlur
             * @param {Overlay} overlay The overlay instance which got blurred.
             * @protected
             */
            this._manageBlur = function (overlay) {
                var changed = false;
                if (activeOverlay == overlay) {
                    Dom.removeClass(activeOverlay.element, OverlayManager.CSS_FOCUSED);
                    activeOverlay = null;
                    changed = true;
                }
                return changed;
            };

            /**
             * Updates the state of the OverlayManager and overlay, as a result of the overlay 
             * receiving focus.
             *
             * @method _manageFocus
             * @param {Overlay} overlay The overlay instance which got focus.
             * @protected
             */
            this._manageFocus = function(overlay) {
                var changed = false;
                if (activeOverlay != overlay) {
                    if (activeOverlay) {
                        activeOverlay.blur();
                    }
                    activeOverlay = overlay;
                    this.bringToTop(activeOverlay);
                    Dom.addClass(activeOverlay.element, OverlayManager.CSS_FOCUSED);
                    changed = true;
                }
                return changed;
            };

            var overlays = this.cfg.getProperty("overlays");

            if (! this.overlays) {
                this.overlays = [];
            }

            if (overlays) {
                this.register(overlays);
                this.overlays.sort(this.compareZIndexDesc);
            }
        },

        /**
        * @method _onOverlayElementFocus
        * @description Event handler for the DOM event that is used to focus 
        * the Overlay instance as specified by the "focusevent" 
        * configuration property.
        * @private
        * @param {Event} p_oEvent Object representing the DOM event 
        * object passed back by the event utility (Event).
        */
        _onOverlayElementFocus: function (p_oEvent) {

            var oTarget = Event.getTarget(p_oEvent),
                oClose = this.close;

            if (oClose && (oTarget == oClose || Dom.isAncestor(oClose, oTarget))) {
                this.blur();
            } else {
                this.focus();
            }
        },

        /**
        * @method _onOverlayDestroy
        * @description "destroy" event handler for the Overlay.
        * @private
        * @param {String} p_sType String representing the name of the event  
        * that was fired.
        * @param {Array} p_aArgs Array of arguments sent when the event 
        * was fired.
        * @param {Overlay} p_oOverlay Object representing the overlay that 
        * fired the event.
        */
        _onOverlayDestroy: function (p_sType, p_aArgs, p_oOverlay) {
            this.remove(p_oOverlay);
        },

        /**
        * @method _onOverlayFocusHandler
        *
        * @description focusEvent Handler, used to delegate to _manageFocus with the correct arguments.
        *
        * @private
        * @param {String} p_sType String representing the name of the event  
        * that was fired.
        * @param {Array} p_aArgs Array of arguments sent when the event 
        * was fired.
        * @param {Overlay} p_oOverlay Object representing the overlay that 
        * fired the event.
        */
        _onOverlayFocusHandler: function(p_sType, p_aArgs, p_oOverlay) {
            this._manageFocus(p_oOverlay);
        },

        /**
        * @method _onOverlayBlurHandler
        * @description blurEvent Handler, used to delegate to _manageBlur with the correct arguments.
        *
        * @private
        * @param {String} p_sType String representing the name of the event  
        * that was fired.
        * @param {Array} p_aArgs Array of arguments sent when the event 
        * was fired.
        * @param {Overlay} p_oOverlay Object representing the overlay that 
        * fired the event.
        */
        _onOverlayBlurHandler: function(p_sType, p_aArgs, p_oOverlay) {
            this._manageBlur(p_oOverlay);
        },

        /**
         * Subscribes to the Overlay based instance focusEvent, to allow the OverlayManager to
         * monitor focus state.
         * 
         * If the instance already has a focusEvent (e.g. Menu), OverlayManager will subscribe 
         * to the existing focusEvent, however if a focusEvent or focus method does not exist
         * on the instance, the _bindFocus method will add them, and the focus method will 
         * update the OverlayManager's state directly.
         * 
         * @method _bindFocus
         * @param {Overlay} overlay The overlay for which focus needs to be managed
         * @protected
         */
        _bindFocus : function(overlay) {
            var mgr = this;

            if (!overlay.focusEvent) {
                overlay.focusEvent = overlay.createEvent("focus");
                overlay.focusEvent.signature = CustomEvent.LIST;
                overlay.focusEvent._managed = true;
            } else {
                overlay.focusEvent.subscribe(mgr._onOverlayFocusHandler, overlay, mgr);
            }

            if (!overlay.focus) {
                Event.on(overlay.element, mgr.cfg.getProperty("focusevent"), mgr._onOverlayElementFocus, null, overlay);
                overlay.focus = function () {
                    if (mgr._manageFocus(this)) {
                        // For Panel/Dialog
                        if (this.cfg.getProperty("visible") && this.focusFirst) {
                            this.focusFirst();
                        }
                        this.focusEvent.fire();
                    }
                };
                overlay.focus._managed = true;
            }
        },

        /**
         * Subscribes to the Overlay based instance's blurEvent to allow the OverlayManager to
         * monitor blur state.
         *
         * If the instance already has a blurEvent (e.g. Menu), OverlayManager will subscribe 
         * to the existing blurEvent, however if a blurEvent or blur method does not exist
         * on the instance, the _bindBlur method will add them, and the blur method 
         * update the OverlayManager's state directly.
         *
         * @method _bindBlur
         * @param {Overlay} overlay The overlay for which blur needs to be managed
         * @protected
         */
        _bindBlur : function(overlay) {
            var mgr = this;

            if (!overlay.blurEvent) {
                overlay.blurEvent = overlay.createEvent("blur");
                overlay.blurEvent.signature = CustomEvent.LIST;
                overlay.focusEvent._managed = true;
            } else {
                overlay.blurEvent.subscribe(mgr._onOverlayBlurHandler, overlay, mgr);
            }

            if (!overlay.blur) {
                overlay.blur = function () {
                    if (mgr._manageBlur(this)) {
                        this.blurEvent.fire();
                    }
                };
                overlay.blur._managed = true;
            }

            overlay.hideEvent.subscribe(overlay.blur);
        },

        /**
         * Subscribes to the Overlay based instance's destroyEvent, to allow the Overlay
         * to be removed for the OverlayManager when destroyed.
         * 
         * @method _bindDestroy
         * @param {Overlay} overlay The overlay instance being managed
         * @protected
         */
        _bindDestroy : function(overlay) {
            var mgr = this;
            overlay.destroyEvent.subscribe(mgr._onOverlayDestroy, overlay, mgr);
        },

        /**
         * Ensures the zIndex configuration property on the managed overlay based instance
         * is set to the computed zIndex value from the DOM (with "auto" translating to 0).
         *
         * @method _syncZIndex
         * @param {Overlay} overlay The overlay instance being managed
         * @protected
         */
        _syncZIndex : function(overlay) {
            var zIndex = Dom.getStyle(overlay.element, "zIndex");
            if (!isNaN(zIndex)) {
                overlay.cfg.setProperty("zIndex", parseInt(zIndex, 10));
            } else {
                overlay.cfg.setProperty("zIndex", 0);
            }
        },

        /**
        * Registers an Overlay or an array of Overlays with the manager. Upon 
        * registration, the Overlay receives functions for focus and blur, 
        * along with CustomEvents for each.
        *
        * @method register
        * @param {Overlay} overlay  An Overlay to register with the manager.
        * @param {Overlay[]} overlay  An array of Overlays to register with 
        * the manager.
        * @return {boolean} true if any Overlays are registered.
        */
        register: function (overlay) {

            var registered = false,
                i,
                n;

            if (overlay instanceof Overlay) {

                overlay.cfg.addProperty("manager", { value: this } );

                this._bindFocus(overlay);
                this._bindBlur(overlay);
                this._bindDestroy(overlay);
                this._syncZIndex(overlay);

                this.overlays.push(overlay);
                this.bringToTop(overlay);

                registered = true;

            } else if (overlay instanceof Array) {

                for (i = 0, n = overlay.length; i < n; i++) {
                    registered = this.register(overlay[i]) || registered;
                }

            }

            return registered;
        },

        /**
        * Places the specified Overlay instance on top of all other 
        * Overlay instances.
        * @method bringToTop
        * @param {YAHOO.widget.Overlay} p_oOverlay Object representing an 
        * Overlay instance.
        * @param {String} p_oOverlay String representing the id of an 
        * Overlay instance.
        */        
        bringToTop: function (p_oOverlay) {

            var oOverlay = this.find(p_oOverlay),
                nTopZIndex,
                oTopOverlay,
                aOverlays;

            if (oOverlay) {

                aOverlays = this.overlays;
                aOverlays.sort(this.compareZIndexDesc);

                oTopOverlay = aOverlays[0];

                if (oTopOverlay) {
                    nTopZIndex = Dom.getStyle(oTopOverlay.element, "zIndex");

                    if (!isNaN(nTopZIndex)) {

                        var bRequiresBump = false;

                        if (oTopOverlay !== oOverlay) {
                            bRequiresBump = true;
                        } else if (aOverlays.length > 1) {
                            var nNextZIndex = Dom.getStyle(aOverlays[1].element, "zIndex");
                            // Don't rely on DOM order to stack if 2 overlays are at the same zindex.
                            if (!isNaN(nNextZIndex) && (nTopZIndex == nNextZIndex)) {
                                bRequiresBump = true;
                            }
                        }

                        if (bRequiresBump) {
                            oOverlay.cfg.setProperty("zindex", (parseInt(nTopZIndex, 10) + 2));
                        }
                    }
                    aOverlays.sort(this.compareZIndexDesc);
                }
            }
        },

        /**
        * Attempts to locate an Overlay by instance or ID.
        * @method find
        * @param {Overlay} overlay  An Overlay to locate within the manager
        * @param {String} overlay  An Overlay id to locate within the manager
        * @return {Overlay} The requested Overlay, if found, or null if it 
        * cannot be located.
        */
        find: function (overlay) {

            var isInstance = overlay instanceof Overlay,
                overlays = this.overlays,
                n = overlays.length,
                found = null,
                o,
                i;

            if (isInstance || typeof overlay == "string") {
                for (i = n-1; i >= 0; i--) {
                    o = overlays[i];
                    if ((isInstance && (o === overlay)) || (o.id == overlay)) {
                        found = o;
                        break;
                    }
                }
            }

            return found;
        },

        /**
        * Used for sorting the manager's Overlays by z-index.
        * @method compareZIndexDesc
        * @paramte
        * @pararn {Number} The  = 1 null-1 ndepen or bonn dere OverlayManauld be u     * @parfothehe manak if or bord        * @re      constreZIndexDesc
   nction (overl1 nu

                zIndex = D(!sZIo1.settrueo1.setProperty("visiex");
  ull;
  ,Set ing ehedEl)  (royed.
                    zx2 = (!sZIo2.settrueo2.setProperty("visiex");
  ull;
   Contct passthe om mar.          if (isInex = D(!sull && o.cfzx2 = (!sull) {
                    rn 0;
                se if (aOveex = D(!sull && )                  rn 0;
 1              se if (aOveex = D(!sull) {
                    rn 0;
                 se if (aOveex = D(!>fzx2 = (                    rn 0;
                 se if (aOveex = D(!<fzx2 = (                    rn 0;
                se if (                  rn 0;
                s     },

        /**
        * UsedShowl chillays by zhe manager
         * @method syncPhow                   syncPhow   nction () {
            /**
overlays = this.cfg.lays,
                n = overlays.length,
                foun            if ((i = n-1; ;
   = 0; i--) {
                    lays[i];
  .Phow            }
        },

        /**
        * @metHidel chillays by zhe manager
         * @method syncEven                   syncEven   nction () {
            /**
overlays = this.cfg.lays,
                n = overlays.length,
                foun            if ((i = n-1; ;
   = 0; i--) {
                    lays[i];
  .Even            }
        },

        /**
        * @metrns a String representation of the Overct.
        * @method toString
        * @return {String} The string representation of the Overlay.
  ger
        */
        initring: function () {
            return "Overlay " +ger
   "      },

      }));
(function () {

    /**
    * OverToole Oa "shn impnt, "zn of the lay to lo
   vior el lik OverlStriole O,  * confdispto repr the even 
   edowns laystriZInticulaement.
  d 
        nfdisappearor bonnedown the  * @namespace YAHOO.widget
    * @names reprToole O * @nameInden sOO.widget.Overlay
        onstructor
    * @param {Arrang} The he element whicIDresenting the id oToole Oa<em>OR</em> * @param {ArraElement} el The element whicesenting the id oToole O * @param {Arract} userConfig  Thecollecuration of tht literal reprainer t
        * computuration of t should be usedto tthe Ovierlays by. Semputuration of t     nfdent.boda focusEre intudeer            YAHOO.widget.OverToole Oanction () {
using nfig) {
            O.widget.OverToole Oerclass.desttructor
   l(this, shalusing nfig) {
 ;     }))     over.isSAHOO.widglisS      Event = YAHOO.util.Event,
        Dom omEvent = YAHOO.util.CustomEvent,
        Over= YAHOO.util.Dom,
        ConfToole OancO.widget.OverToole O      ConfUAancO.widgenv.ua      ConfbIEQuirkthisie || Uo ==e || U<= 6(o.iment.bodyatibilMode"striBif Ctibil")            m_oShadowTs tl          /**
        * @metConce &icesenting the id oToole Oonfigured hn properties.
       * @paramtty on tDEFAULT_CONFIG     * @paramte
        * @paral
    * @t* @para YAHOct} us     */
        initDEFAULT_CONFIG

        /**




"PREVENTRLAY),
P": {                  key: "ent,
  lays[ip"                   e: this  only                 e: or. Ve: .isStriBan} trly                 rclassedel: ["(par""headxy"]y             }               "SHOW_DELAY": {                  key: "Phowde by"                   e: this2true                 e: or. Ve: .isStrier} Thy             }                "AUTO_DISMISS_DELAY": {                  key: "" trdismissde by"                   e: this50true                 e: or. Ve: .isStrier} Thy             }                "HIDE_DELAY": {                  key: "Evende by"                   e: this25rue                 e: or. Ve: .isStrier} Thy             }                "TEXT": {                  key: "Trig"                   ressEvent: true } );             }                "CONTAINER": {                  key: "ainer to "             }               "DISABLED": {                 key: "disabled"              foune: thise,
                i,
 ressEvent: true } )             }               "XY_OFFSET": {                 key: "xyetHeig"              foune: this[rue25               nTopressEvent: true } )             }     },

        /**
        * @metConce &icesenting the id o of the evenToole Oonfts suc     * @paramtty on tEVENTRTYPES     * @paramte
        * @paral
    * @t* @para YAHOct} us     */
        initEVENTRTYPES

               "CONTEXT_MOUSERLAY)": "ainerigMdownlays"              "CONTEXT_MOUSERLUT": "ainerigMdownlug"              "CONTEXT_TRIGGY)": "ainerigger);
 "         }))         * OverConce &icesenting the id oToole O class rep * @paramtty on tO.widget.OverToole OeFOCUTOOLTIP * @paraic
    * @final
    * @type String
    */
    OverToole OeFOCUTOOLTIPfocuforctt    Overtion () {rr(oCleOnalZ, tWidth(sOnalZ, tWidth, sF-reddWidth
            overlig = YAHO.cfg.get              sCntly fWidthverlig = YProperty("visiwidth
            aOvesCntly fWidthve= sF-reddWidth
               lig = YPsoperty("visiwidth
, sOnalZ, tWidth)      },

      })                 ged;
 ent, Nont handler for  shoulall crToole Oa nce's destiwidth
         gguration property on the compe fromo 
  s root Elem          ents to ' 
  HeigWidthv blurified Oc width not been set on           Overtion () {eigWidthToO HeigWidthType, p_aArgs, p_
            aOve"_onalZ, tWidth"zhe mai
                rr(oCleOnalZ, tWidthl(this, shal._onOvenalZ, tWidth, ._onOvf-reddWidth
      },

           overlBor f=ument.body);

,             lig = YAHO.cfg.get              sOnalZ, tWidthverlig = YProperty("visiwidth
               sNewWidth,             lilone           aOve(!sOnalZ, tWidthvo.isOnalZ, tWidthve=to" tra (nTo             (lig = YProperty("visiainer to ")overlBor fo.i             lig = YProperty("visi
  u>m.getStylViewporgWidthT)fo.i             lig = YProperty("visiy  u>m.getStylViewporght(cont)

                lilonehis.element;

 se;
ne.rem();
                lilone.s(aOv.ble")y (Eve= "Eveden"              lilone.s(aOv.of a= "0px"              lilone.s(aOv.lefta= "0px"               lBor lyCoendd(thislilone                sNewWidth!sZIoilone.  HeigWidthvx");
                 lBor lveChild(thislilone               lilonehis;

            /**
lig = YPsoperty("visiwidth
, sNewWidth               lig = YPre();
t("blurxy"            this.initvenalZ, tWidthhissOnalZ, tWidthvo.i""              ._onOvf-reddWidthhissNewWidth      },

      })      / "oEvenR has"  shouly dan Oid oToolTip  Overtion () {oEvenR hasType, p_aArgs, p_oOverlt} us        this.initly dan(verlt} us ;     })      /  "Defaent handler for  are c trm
   thiyuly dan Oid oTooltip  Overtion () {oEiali
            t.on(ovevenR hasTvevenR hasis.cfg.getProperty("focuainer to ")is);
    
   })     O.widgenden (Toole O OO.widget.Overlay,
    {       /**
        * @metTd oToole O ializes tn propod toS.tTdithod will "sh trm
   thiyu     * @met thiy the "foctructor
   l ArToole Oa "sh trm
   thiyuly dany the      * @metinstanithod wild 
   ith purget to the bstanble") &the ult "mo       * alongndctructor to locaviewporgthe ult "mowindoe        * @method dest
        * @param {Overng} The he element whicIDresenting the id oToole Oa<em>OR</em> * @p* @param {OverElement} el The element whicesenting the id oToole O * @p* @param {Overct} userConfig  Thecollecuration of tht literal repr     * @met oning the focuuturation of t should be usedto tthe OvierToole Oer     * @metSemputuration of tdent.boda focusEre intudeer         * @me      init: function (userusing nfig) {
              thisToole Oerclass.dest: ful(this, shalus            this.initbesEreialit.fire();
 Toole O            thisaddClass(acti.element;

 ,rToole OeFOCUTOOLTIP            if (userConfig) {
                this.cfg.applyConfig(userConfig, true);
            }
             this.initapplq();
erty("visible") &&,se,
  )              ._onOapplq();
erty("visitructor ttoviewporg"ue);
                 ._onOto Bor (""            this.initcribe(mgr."ged;
 ent, No
, sigWidthToO HeigWidth)              ._onOcribe(mgr."Defae,{oEiali)              ._onOcribe(mgr."ly dan"is.cfg.onRy dan            this.initDefat.fire();
 Toole O       }
          /**
        * Initializes the Overom or ees for eachToole O * @p* @paraod dest
   Es suc     * @pa      init: fuEs sucnction () {

            var Toole Oerclass.dest: fuEs sucl(this, sh)              overSIGNATUREustomEvent.LIST;
                             * RemoomEvent = YAd the  the  
   edowns laystriext trigent;

 strns a focus,
  m the         * Remoascribe 
  rthe coinfts sul add ent,
   Overriole Om the g manadispto or sor         * Remocurrently fiext trigent;

 s         * Remo         * Remo@t,
   ainerigMdownlaysE
            * @typem {OverElement} el Text trigcollecu trigent;

 ch blureven 
    to uedown" lays         * @typem {Overvent, th Thgcollevent 
   ht lit a rsoci Over the managdown tays         * @ty          this.cfg =inerigMdownlaysE
   AHO.cfg.geEvent("bluEVENTRTYPES.CONTEXT_MOUSERLAY))              ._onOainerigMdownlaysE
   nature = CustSIGNATURE                           * RemoomEvent = YAd the  the even 
   edowns luta "yuiext trigent;

 s         * Remo         * Remo@t,
   ainerigMdownlutE
            * @typem {OverElement} el Text trigcollecu trigent;

 ch blureven 
    to uedown" luta "         * @typem {Overvent, th Thgcollevent 
   ht lit a rsoci Over the managdown tut         * @ty          this.cfg =inerigMdownlutE
   AHO.cfg.geEvent("bluEVENTRTYPES.CONTEXT_MOUSERLUT)              ._onOainerigMdownlutE
   nature = CustSIGNATURE                           * RemoomEvent = YAd the  to ubesEre Overriole Ominfdispto or sorocurrently fiext tris         * Remo         * It Remo Younot scribe 
   he coinfts suluseyouns tostacko tup Overrtrigsorocurr      * It Remo riole Omd insthe manaecu trigent;

 cwhich bluri usedablutahe bstdispto ors         * Remo /        * It Remo   Toinfts suldiffan O the DOM besEreShowfts sulucusE therepresifieucn /        * It Remo ol       * It Remo  <li       * It Remo   Wthe Chifocusthe onehecu trigent;

 cllownr 
   founOverriole Ominfnr       * It Remo   Evedenis.ce <code>Evende by</code>minfnr resached), DOM besEreShowfgndcShowfts susl add nr       * It Remo   bstd the  the evenriole Ominfdispto or sorocurrConfecu trigsi&& (i usedady has ble") &s         * RemooooHer if amanaecu triger);
 fts suluedadw= thd the besEre dispto reprOverriole Om or         * RemooooarConfecu tris         * Remooo</li       * It Remo  <li       * It Remo   collter);
 fts sulided, aedacTrigthe computu trigent;

 ,ow therepryoun        * ret Remo   to thverrtrigounOverriole Omd insthe ecu trigent;

 cwhich blurevenriole Omin     * ret Remo   ter);
 ors         * Remooo</li       * It Remo</ol       * It Remo         * It Remo s currproviose") &t   ent,
   Overriole Om the g manadispto or      * It Remo usreprOvinfts su. Younot sown computu triMdownlaysE
   Auseyouns tostacent,
        * It Remo rverriole Om the g manadispto ors         * Remo /        * It Remo@t,
   ainerigger);
 E
            * @typem {OverElement} el Text trigcollecu trigent;

 cwhich blurevenriole Omin ter);
 or         * @ty          this.cfg =inerigger);
 E
   AHO.cfg.geEvent("bluEVENTRTYPES.CONTEXT_TRIGGY))              ._onOainerigger);
 E
   nature = CustSIGNATURE                  /**
        * Initializes the Overo's construration) &terties.
  ch blurce used     * @met ed = trusreprOverlay.
  onfig object used(sett      * @method dest
   ultConfig
        */
        initDefaultConfig: function () {
             var Toole Oerclass.dest: fuultConfig: ful(this, sh)                           * RemoSfied Ov ch e 
   id oToole Oald be usedkeptusthe oays[ipp
            * conf  s ext trigent;

 s         * Remofig focuent,
  lays[ip         * Remof StriBan} tr         * Remofult "mow } )                        this.cfg.addProperty("focDEFAULT_CONFIG.PREVENTRLAY),
P.ke   {             thise: thisDEFAULT_CONFIG.PREVENTRLAY),
P.e: thue                 e: or. Ve: DEFAULT_CONFIG.PREVENTRLAY),
P.e: or. Vely                 rclassedel: DEFAULT_CONFIG.PREVENTRLAY),
P.rclassedel         }
               /**
            * The currnr} Thyounmaddiseig o be mwai ubesEre Phowa focuToole Oa         * The onnedownlayss         * Remofig focuPhowde by         * Remof Strier} Th         * Remofult "mow200                        this.cfg.addProperty("focDEFAULT_CONFIG.SHOW_DELAY.ke   {             thisher: func._onOainfocShowDe
                overe: this2true                 e: or. Ve: DEFAULT_CONFIG.SHOW_DELAY.e: or. Ve         }
               /**
            * The currnr} Thyounmaddiseig o be mwai ubesEre h trm
   thiyu     * @m* The dismissa focuToole Oaaft   id ogdown not  set rr(oor bonncurr      * It Remoext trigent;

 s         * Remofig focu" trdismissde by         * Remof Strier} Th         * Remofult "mow50tr                        this.cfg.addProperty("focDEFAULT_CONFIG.AUTO_DISMISS_DELAY.ke   {             thisher: func._onOainfocA trDismissDe
                overe: thisDEFAULT_CONFIG.AUTO_DISMISS_DELAY.e: thu                 e: or. Ve: DEFAULT_CONFIG.AUTO_DISMISS_DELAY.e: or. Ve         }
               /**
            * The currnr} Thyounmaddiseig o be mwai ubesEre Evea focuToole Oa         * The aft   edownlu s         * Remofig focuEvende by         * Remof Strier} Th         * Remofult "mow25r                        this.cfg.addProperty("focDEFAULT_CONFIG.HIDE_DELAY.ke   {             thisher: func._onOainfocHideDe
                overe: thisDEFAULT_CONFIG.HIDE_DELAY.e: thue                 e: or. Ve: DEFAULT_CONFIG.HIDE_DELAY.e: or. Ve         }
               /**
            * The Sfied Ov cevenToole Oonf tris collttrigi zhese to t tto DOM (witot Elemd 
   ld be usedescapy the "focimpnt, "zo a blcomfocusthe e uendernal sousse.o         * Remo@ig focuttri         * Remof StriElem         * Remofult "mow
            */
            this.cfg.addProperty("oveDEFAULT_CONFIG.TEXT.ke   {             thisher: func._onOainfocTtri              nTopressEvent: trueDEFAULT_CONFIG.TEXT.ressEvent: tr         }
               /**
            * The Sfied Ov cevenainer to gent;

 clhhe sameToole OonfmarkuOa         * The ld be usedly dany t ttos         * Remofig focuainer to          * Remof StriElement} el/ng
            * @default "moument.body);

         */
            this.cfg.addProperty("oveDEFAULT_CONFIG.CONTAINER.ke   {             thisher: func._onOainfocCiner to               overe: thisment.body);

         */
              /**
            * The Sfied Ov ch e 
   ull otrevenriole Omin disabled. Disablednriole On     * ret Remo add nr  bstdispto orsthe instriole Omin dri: tthe "foctitl theg
 bu           * @typounOverutu trigent;

 ,o"foctitl theg
 bu  l subsctsubsemoved for the      * @m* The disablednriole On,t   ent,
   ult "mow iole Omdior ioss         * Remo         * Remofig focudisabled         * Remof StriBan} tr         * Remofult "mows,
           */
            this.cfg.addProperty("oveDEFAULT_CONFIG.DISABLED.ke   {             thisher: func._onOainfocCiner to               overe: thisDEFAULT_CONFIG.DISABLED.e: thu                 resEvent: trueDEFAULT_CONFIG.DISABLED.ressEvent: tr         }
               /**
            * The Sfied Ov cevenXY
  Heigm the managdown posi, the  dere Overtoole Oald be useddispto or,cified Ove         * The al cr2gent;

 cy of O. Menu[1rue20]);o         * Rem         * Remofig focuxyetHeig         * Remof Striy) {
         * Remofult "mow[rue25          */
            this.cfg.addProperty("oveDEFAULT_CONFIG.XY_OFFSET.ke   {             thise: thisDEFAULT_CONFIG.XY_OFFSET.e: thOaincat(               nTopresEvent: trueDEFAULT_CONFIG.XY_OFFSET.ressEvent: tr          }
               /**
            * The Sfied Ov cevenent;

 co gent;

 sclhhe sameToole Oald be used         * The anchortostaconnedownlayss         * Remofig focuutu tri         * Remof StriElement} el[]/ng
   []         * Remofult "mow
            */
              /**
            * The Sg representing the id owidth he evenToole O. a<em>P} twn nr e:         * Remo /em> A 
   ayss() {2.3a blei 
   nope fromostrie fromo 
o" tran         * conf ecified by d the blurTool Oonfuainer to " gguration property on          * conf ecio the sood dor bo 
   ide u<code>ment.body);

</code>mhe      * @m* The   s "ainerig"ment whicess, aedlu s, a "focimmedi Ovls ble") &      * @m* The porgof the Overment.bod, id owidth he evenToole Ol subssed         * The  thculatack binsthe mana  HeigWidthvo 
  s root Elem 
   lo t to u         * The besEre i usedma a ble") &s  collenalZ, tie from subssed         * The rr(oClee  the evenToole Oa "sEveden.tTdithees the zIndToole Oa "s         * The rr dany tre c usableowidths  FEre intuinsErma () {eied         * The YUILibrary bug #1685496 
   YUILibrary          * The bug #1735423s         * Remofig focuwidth         * Remof String
            * @default "mou
            */
                     },                  // BEGIN BUILT-IN PROPERTYtEVENT HANDLERS //                  /       * @metTd oult "mout handler for t the  the even"Trig"perty on t "s ed = t      * @method comparnfocTtri     * @methm {Overng} The  StriTd oomEvent = YA Stri(usuthiyuevenerty on t of )     * @methm {Overct} usoveraArraTd oomEvent = YAments.
    FEreuturation of t     * @metler forn,taArr[0]m subsequthocurrConiyuyConOvere from orocurrerty.
        * @priva {Overct} userobj strincty.rct.
    FEreuturation of tler forn,t     * @priOvinf subsusuthiyuequthocurrown        * @re      constrnfocTtrinction () {
t p_aAa p_oOct.            /**
over trig=taArr[0]          if (!isN tri                this.cfg.to Bor ( tri           }
        },

                    /       * @metTd oult "mout handler for t the  the even"ainer to " erty on t     * @met "s ed = t      * @method comparnfocCiner to          ethm {Overng} The  StriTd oomEvent = YA Stri(usuthiyuevenerty on t of )     * @methm {Overct} usoveraArraTd oomEvent = YAments.
    FEre     * @metuturation of tler forn,taArr[0]m subsequthocurrConiyuyConOvere from     * @met orocurrerty.
        * @priva {Overct} userobj strincty.rct.
    FEreuturation of tler forn,     * @priOvinf subsusuthiyuequthocurrown        * @re      constrnfocCiner to nction () {
t p_aAa p_oOct.            /**
overainer to g=taArr[0]           if (!isN f overainer to g== 'ng rep'                this.cfg.applsoperty("focuainer to ",sment.bodygetent} elById(ainer to )ue);
            }
        },

                    /       * @method _syncved fot = Yener(o.e      * @description Evenves focuotherf DOM (witt handler for O the DOM Elem         *  ents to(s)clhhe ser);
 fOvermispto gounOverriole O      * @privatted
         */
       _syncved fot = Yener(o.e nction () {
                     /**
overaEnt;

 scHO.cfg._ainerig              n = oEnt;

 s              o,
  Ent;

               i;

            if (isInaEnt;

 s                thisnEnt;

 scHOaEnt;

 sgth; i <             i;

 OverEnt;

 sc {
                    i = nOverEnt;

 sc
                    do {
                        this Ent;

 cHOaEnt;

 s
                    if (((((E
   nved foener(o.e( Ent;

   "edownlays"is.cfg.onCtu triMdownlays                       }
  E
   nved foener(o.e( Ent;

   "edownd fo"is.cfg.onCtu triMdownM fo                       }
  E
   nved foener(o.e( Ent;

   "edownout"is.cfg.onCtu triMdownlui           }
                            while(i-- );
                }
            };

      },

                    /       * @metTd oult "mout handler for t the  the even"ainerig"perty on t     * @met "s ed = t      * @method comparnfocCinetri     * @methm {Overng} The  StriTd oomEvent = YA Stri(usuthiyuevenerty on t of )     * @methm {Overct} usoveraArraTd oomEvent = YAments.
    FEreuturation of t     * @metler forn,taArr[0]m subsequthocurrConiyuyConOvere from orocurrerty.
        * @priva {Overct} userobj strincty.rct.
    FEreuturation of tler forn,     * @priOvinf subsusuthiyuequthocurrown        * @re      constrnfocCinetrinction () {
t p_aAa p_oOct.             /**
overainetrig=taArr[0]              aOverEnt;

 s              o,
 oEnt;

 s              o,
  Ent;

               i;

            if (isInainetri
                    // NErmas th a {Ovet    tto rray of              i;

 Ove!Inainetritanceof Array) {

                    if (mgr. f overainetrig=tring") {
                for         .cfg.applsoperty("focuainerig"  [ment.bodygetent} elById(ainetri
]ue);
            }
          se if (  // AssumreprOvinf "shn ents to             for         .cfg.applsoperty("focuainerig"  [ainerig]ue);
            }
          s         }
          ainetrig=t.cfg.getProperty("focuainerig"               }
                     // ves fo Overting bluredownlays/edownout lner(o.e      * @d        .cfg.cved fot = Yener(o.e (                    // Addredownlays/edownout lner(o.e the utu trigent;

       * @d        .cfg.cainetrig=tainetri                   aEnt;

 scHO.cfg._ainerig               if (oTopaEnt;

 s                thisssssnEnt;

 scHOaEnt;

 sgth; i <             i;

if (oToprEnt;

 sc {
                    i = if (oOverEnt;

 sc
                    do {{{{{
                        thisssss Ent;

 cHOaEnt;

 s
                    if (((((((((t.on(overlEnt;

   "edownlays"is.cfg.onCtu triMdownlaysis);
    
               if (((((((((t.on(overlEnt;

   "edownd fo"is.cfg.onCtu triMdownM fois);
    
               if (((((((((t.on(overlEnt;

   "edownout"is.cfg.onCtu triMdownluiis);
    
               if (((((                      }

 e(i-- );
                }
  ((((                            }
        },

        /**
 / END BUILT-IN PROPERTYtEVENT HANDLERS //          // BEGIN BUILT-IN (witEVENT HANDLERS //          /       * @metTd oult "mout handler for t the  the even 
   edvhe zIndgdown e(i--      * @metoif amanaecu trigent;

 s         ethod componCtu triMdownM fo     * @priva {Overvent, th Thgcollently fi(witt han     * @priva {Overct} userobj strict usedments.
      * @re      consonCtu triMdownM fonction (useruoOct.            /**
ct..pageXcHOt.on(oropeageX(e               lt..pageYcHOt.on(oropeageY(e                   /**
        * InitTd oult "mout handler for t the  the even 
   edowns laystcurr      * Imoext trigent;

 s         ethod componCtu triMdownOvo          ethm {Overvent, th Thgcollently fi(witt han     * @priva {Overct} userobj strict usedments.
      * @re      consonCtu triMdownOvo nction (useruoOct.            /**
overainetrig=t;

            if (!oveecu tristitl                     lt.._ts tTitl t=tainetristitl               }
  ainetristitl a= ""                             or P th t tso allohonEredisablednlo the manalnerto          * ReoTopOt..();
t("blurutu triMdownlays"isainerig  e) oOves,
  m&& !Ot..getProperty("focudisabled"

                    et iof arverriole Om the g manaEvedenislo tuselao uedownout                  oTopOt..EvenertcId                    i = clearTimnoutpOt..EvenertcId               }
  ((((Ot..EvenertcIdhis;

           }
  ((((                   t.on(overainerig  "edownd fo"isOt..onCtu triMdownM foisct.                    /           * ThhhhhitTd ouniqurrertTrigtIDa rsoci Over the manathy haresifone") &      * @m* Thhhhhitsortihowa foevenToole O.     * @m* Thhhhhitf Striito             for            this((((Ot..ihowertcIdhisOt..doShowruoOainetri
          }
        },

        /**
        * @metTd oult "mout handler for t the  the even 
   edowns luta "y     * @priOvnaecu trigent;

 s         ethod componCtu triMdownOun     * @priva {Overvent, th Thgcollently fi(witt han     * @priva {Overct} userobj strict usedments.
      * @re      consonCtu triMdownOutnction (useruoOct.            /**
overelg=t;

            if (!ovelt.._ts tTitl                     elstitl a= lt.._ts tTitl           }
  ((((lt.._ts tTitl t=t;

           }
             if (!oveOt..ihowertcId                    clearTimnoutpOt..ihowertcId           }
  ((((lt..ihowertcIdhis;

           }
             if (!oveOt..EvenertcId                    clearTimnoutpOt..EvenertcId               }
  Ot..EvenertcIdhis;

           }
             overlt..();
t("blurutu triMdownlut"isusin                 Ot..EvenertcIdhislo Timnoutption () {
                    Ot..Even            }
   isOt..getProperty("focuEvende by")                   /**
 / END BUILT-IN (witEVENT HANDLERS //          /       * @metPrtTrighe specihowa fohe evenToole Olbyslo  the id otimnout de by      * alongndc  Heigmhe evenToole O.         ethod compdoShow     * @priva {Overvent, th Thgcollently fi(witt han     * @priva {OverElement} el Text trigcollently fiext trigent;

      * @privrn {Number} The ThrrertTrigtIDaounOverrimnout tion () { rsoci Over     * @pri the doShow     * @pr      consdoShownction (useruoOainetri
                overl Heigm=t.cfg.getProperty("focuxyetHeig"               nTopxO Heigm=tetHeig[0]              aOveyO Heigm=tetHeig[1]              aOvemeg=t;

            if (!oveUA.ty("am&& ainetristagNof tnTo                 ainetristagNof .toUpy("Casn  g=triA
                for yO Heigm+= 12          }
             overrn {Numlo Timnoutption () {
                 for over rig=tf .getProperty("focuerig"                    et titl a not exislays-r, a "tri         * Reif (!ovef ._ts tTitl to == rig==tri"vo.iO.widglisS.isUnult to = ri)vo.iO.widglisS.isNuhis,ri)
                    if (f .to Bor (f ._ts tTitl                }
   e if (                  if (f .getPre();
t("blurerig"               }
                     f .d foTo(f .pageXc+pxO Heig, f .pageYc+pyO Heig                    !ovef .getProperty("focuent,
  lays[ip"
                    if (f .ent,
  lay.
 p(f .pageX, f .pageY               }
                     E
   nved foener(o.e(ainerig  "edownd fo"isf .onCtu triMdownM fo                    f .ainerigger);
 E
   n();
 ainetri
                   f .Phow                     f .EvenertcIdhisf .doHven             }
   is.cfg.getProperty("focuPhowde by")                   /**
        * UsedSetsnOverrimnout torocurr" tr-dismiss de by,ch blur e ult "mowis 5r     * @priseig o isf a the fore c toole Ol subsh trm
   thiyudismiss itsel"y     * @priaft   5iseig o aoung manadispto ors         ethod compdoHven     * @pr      consdoHvennction () {
             var mgr =eg=t;

             overrn {Numlo Timnoutption () {
                 for f .Even             }
   is.cfg.getProperty("focu" trdismissde by")                    /**
        * UsedFilee  the evenToole Oa "sd for,rOvinfts sudler for infown"         * re* ent,
   OverTiole Om the oays[ipp
    the   s ext trigent;

 s         ethod compent,
  lay.
 y         etha {Overer} The pageXcThrrnfigordinwithposi, thaounOvergdown poitoo          ethm {Overer} The pageYcThrryfigordinwithposi, thaounOvergdown poitoo          e      consent,
  lay.
 pnction (p_oOvageX, pageY                     /**
overht(conhis.element;

 setHeight(con              aOvemdownPoitohis;ewOO.util.Dom,PoitoOvageX, pageY               aOveent;

 steronom.getStylsteroni.element;

            
        aOveent;

 steron.of a-= 5;
        aOveent;

 steron.lefta-= 5;
        aOveent;

 steron.r(conh+= 5;
        aOveent;

 steron.om marh+= 5;
                 
        aOve!oveent;

 steron.ainer ts(mdownPoito
                    .cfg.applsoperty("focuyparseIgeYc-rht(conh- 5            } els                  /**
        * Usedhod componRy dan     * Usedhription Even"ly dan"ut handler for tor evenToole O.         ethm {String} p_oOvee, p_ Sg representing the id o of the event handy     * @priOvre wathd the.         ethm {Striy) {
}Args, p_ay) {
a "yuents.
   ing   the evene: tr          * wathd the.         e      consonRy dannction (p_oOvee, p_aArgs, p_
       
        aOvetion () {eizeShadow(
       
        aOveeeeeoverlEnt;

 cHO.element;

 ,             }
  ((((OShadowcHO.elemu dan
 y          } el                 oTopOShadow                    o = oShadow.s(aOv.width!sZIoEnt;

 setHeigWidthvx"6)vx");
               }
  ((((OShadow.s(aOv.ht(conhisIoEnt;

 setHeight(convx"1)vx");
  l                 }         } el                            tion () { ddShadowVle") &s(acti                    addClass(acti.elemu dan
 y,cuforctt-shadow-ble") &&                    !ovee ||                     o = .find(-redU dan
 yRedraw            }
      }         } el               tion () {ved foShadowVle") &s(acti                    addCveChild(acti.elemu dan
 y,cuforctt-shadow-ble") &&           } el               tion () {geEvenShadow(
       
        aOveeeeeoverlShadowcHO.elemu dan
 y,             }
  ((((OEnt;

               i;





Modulhu                     nIEu                     m                        !ove!OShadow                     }
  ((((OEnt;

 cHO.element;

               }
  ((((ModulhancO.widget.OverModulh              }
  ((((nIEance ||               }
  ((((meg=t;

            if (        !ove!m_oShadowTs tl                       i = if (m_oShadowTs tl   f=ument.bodygeEventnts to("div                            m_oShadowTs tl   .s.desNof t=cuforctt-shadow               }
  ((((                       lShadowcHOm_oShadowTs tl   .s.
ne.rem(e,
  )               }
  ((((OEnt;

 lyCoendd(thislShadow                }
  ((((.elemu dan
 ym=teShadow               }
  ((((// BackwarmpareZIt")y (Ev,ne: t(.eouge   'srertbabiyu     * @m* Th}
  ((((// itoo dadahe bst"mte
   pari userelymarky tr  iulurie evenapiumen      * @d        ((((.elem_shadowcHO.elemu dan
 y       * @d        (((( ddShadowVle") &s(actl(this, sh)                   ((((.elemcribe(mgr."besEreShowpar ddShadowVle") &s(act                       .elemcribe(mgr."Evenparved foShadowVle") &s(act                    if (!isNbIEQuirkt                    i = if (window.so Timnoutption () {
   u     * @m* Th}
  ((((((((((((eizeShadowl(thisme);o         * Reeeeeeeeeeeee is0)                               .elemapplsribe(mgrTlig = Yt("blurwidth
, sizeShadow                           .elemapplsribe(mgrTlig = Yt("blurht(con
, sizeShadow                           .elemcribe(mgr."ged;
 ent, No
, sizeShadow                            Modulh. triResizeE
   naribe(mgr.sizeShadow,rOvinue);
            }
              .elemcribe(mgr."royed.
&,seion () {
                                Modulh. triResizeE
   nunaribe(mgr.sizeShadow,rOvin   
               if (((((                }
  ((((                            }
                 tion () {onBesEreShow
                    geEvenShadowl(this, sh)                  .elemunaribe(mgr."besEreShowparonBesEreShow           } el               mgr. cfg.getProperty("focuble") &&                     geEvenShadowl(this, sh)               e if (                  .elemcribe(mgr."besEreShowparonBesEreShow           } el      } el                 /**
        * UssedF-redsnOveru dan
 yment;

 clloemovepr ttor,rOvrouge evenapplicn of /ved f   * @t* @ptypouna forc(-red-redrawro's cthe compu dan
 yment;

 . * @t* @ptyp * @t* @ptyphod find
-redU dan
 yRedraw * @t* @pty      find:-redU dan
 yRedraw nction (p_
            /**
overtig=t;

           /**
addClass(acti.tmu dan
 y,cuforc(-red-redraw                so Timnoutption () 
   addCveChild(acti.tmu dan
 y,cuforc(-red-redraw    is0)                  /**
        * Us*nves focuOverTiole Oment;

 cwthe DOM (wito   lo cuothec(thi          * ent;

 sclos;

 s         ethod compdoyed.
     * @pr      consdoyed.
nction () {
                     /**
// ves fo Overting bluredownlays/edownout lner(o.e      * @d    .cfg.cved fot = Yener(o.e (                Toole Oerclass.destdoyed.
l(this, sh)            
                            /       * @metrns a String representation of the Overct.
        * @method toString
        * @return {String} The string representation of the OverToole O * @p* @pa      initring: function () {
            return "OverTiole Om"vx".initD       },

                ;
(function () {

    /**
    * OverPanela "shn impnt, "zn of the lay to lo
   vior el lik OverlStwindow,  * conf the a draggableoheadystr   veron Eveepralof (ac) { trevenrip r(con  * @namespace YAHOO.widget
    * @names reprPanel * @nameInden sOO.widget.Overlay
        onstructor
    * @param {Arrang} The he element whicIDresenting the id oPanela<em>OR</em> * @param {ArraElement} el The element whicesenting the id oPanel * @namem {Arract} userConfig  Thecollecuration of tht literal reprainer t
        * computuration of t should be usedto tthe OvierPanel. Semputuration of t     nfdent.boda focusEre intudeer            YAHOO.widget.OverPanelanction () {
using nfig) {
            O.widget.OverPanel.rclass.desttructor
   l(this, shalusing nfig) {
 ;     }))     over_ently fModalhis;

        over.isSAHOO.widglisS      EvenUtilAHOO.util.Dom      Over= YAHOUDom,
        ConfE
   AHOUEvent,
        Dom omEvent = YAHOUCustomEvent,
        OverKeyener(o.eAHOO.util.Dom,Keyener(o.e      Dom og = YAHOUCustog, tru     Dom lay to l=OO.widget.Overlay,
        Dom PanelancO.widget.OverPanel      ConfUAancO.widgenv.ua       ConfbIEQuirkthisie || Uo ==e || U<= 6(o.iment.bodyatibilMode"striBif Ctibil")            m_oMaskTs tl             m_oU dan
 yTs tl             m_oClof ItruTs tl          /**
        * @metConce &icesenting the id o of the evenPanelonfts suc     * @paramtty on tEVENTRTYPES     * @paramte
        * @paral
    * @t* @para YAHOct} us     */
        initEVENTRTYPES

               "BEFORE_SHOW_MASK" nc"besEreShowMask"              "BEFORE_HIDE_MASK" nc"besEreHvenMask"              "SHOW_MASK": "PhowMask"              "HIDE_MASK": "EvenMask"              "DRAG": "drag"         }       /**
        * @metConce &icesenting the id oPanelonfgguration property on
       * @paramtty on tDEFAULT_CONFIG     * @paramte
        * @paral
    * @t* @para YAHOct} us     */
        initDEFAULT_CONFIG

        /**




"CLOSE": {                  key: "alof "                   e: this  only                 e: or. Ve: .isStriBan} trly                 rclassedel: ["ble") &&]y             }               "DRAGGABLE": {                 key: "draggable"                   e: this(UDom,
D ?e } );ise,
  )ly                 e: or. Ve: .isStriBan} trly                 rclassedel: ["ble") &&]yy             }               "DRAG_ONLY" nc{                 key: "dragonly"              foune: thise,
                i,
 e: or. Ve: .isStriBan} trl                 rclassedel: ["draggable"]             }               "UNDY),
Y": {                  key: "u dan
 y"                   e: this"shadow ly                 rclassedel: ["ble") &&]y             }               "MODAL": {                  key: "modal"                   e: thise,
   y                 e: or. Ve: .isStriBan} trly                 rclassedel: ["ble") &&,cuzindex"]             }               "KEY_;
  ENERS": {                 key: "keylner(o.e "              founressEvent: true } )l                 rclassedel: ["ble") &&]             }               "STRINGS" nc{                 key: "ng rep "              founresassedel: ["alof "]              aOvee: or. Ve: .isStrict} us              foune: this                  i = clof : "Clof "                           }
        },

 ))         * OverConce &icesenting the id oult "mowclass repfown" fostriPanel * @namemtty on tO.widget.OverPanel.FOCUPANEL * @nameic
    * @final
    * @type String
    */
    OverPanel.FOCUPANELt=cuforcpanel"               * OverConce &icesenting the id oult "mowclass repfown" fostriPanel's  * conf ripp
   ainer to      amemtty on tO.widget.OverPanel.FOCUPANEL_CONTAINER * @nameic
    * @final
    * @type String
    */
    OverPanel.FOCUPANEL_CONTAINERt=cuforcpanel-ainer to "))         * OvverConce &icesenting the id oult "moweigmhe fentsableoent;

 sc * Ovverhe manaeIgeh blurModalhPanelsl add ent,
   acTrigthee  den * OvverOvergddalhmaskminfdispto or * Ovver * Ovveremtty on tO.widget.OverPanel.FOCUSABLE * Ovvereic
    * @ftype Striy) {
         OverPanel.FOCUSABLEt=c[     },

"a"          "button"          "sentcg"          rerigarea"          "inpug"          "if{Ove"     ]       // Pte
    omEvent = YAlner(o.e                   "besEreRy dan"ut handler for  shougeEven"shn empn theadystfostriPanel          anceof A mgr  s "draggable" gguration property on t ecio the " } )an         r   notheadystnot  set geEvend          Overtion () {geEvenHeadysOvee, p_aArgs, p_
           aove!.initheadysto = cfg.getProperty("focudraggable"                 .cfg.to HeadysO"&#160;                  })                 "Evenpnt handler for  shoulall crPanela nce's destiwidth
         gguration property on tbif the   s enalZ, tie frombesEre          "segWidthToO HeigWidth" wath thiy           Overtion () {rr(oCleOnalZ, tWidth(ype, p_aArgs, p_oOverlt} us             oversOnalZ, tWidthververlt} us[0]              sNewWidth!sZverlt} us[1]              lig = YAHO.cfg.get              sCntly fWidthverlig = YProperty("visiwidth
            aOvesCntly fWidthve= sNewWidth                lig = YPsoperty("visiwidth
, sOnalZ, tWidth)      },

       },

.elemunaribe(mgr."Evenparve(oCleOnalZ, tWidthoOverlt} us ;     })                 "besEreShowpnt handler for  shoulall crPanela nce's destiwidth
         gguration property on the compe fromo 
  s root Elem          ents to ' 
  HeigWidth         Overtion () {eigWidthToO HeigWidthType, p_aArgs, p_
            overlig = Y              sOnalZ, tWidth              sNewWidth           aOvebIEQuirkt                 lig = YAHO.cfg.get;             sOnalZ, tWidthverlig = YProperty("visiwidth
                            aove!sOnalZ, tWidthvo.isOnalZ, tWidthve=to" tra (                   }
  sNewWidth!sZI.element;

 setHeigWidthvx");
                     }
  lig = YPsoperty("visiwidth
, sNewWidth                }
  .elemcribe(mgr."Evenparve(oCleOnalZ, tWidthoO                 i = [(sOnalZ, tWidthvo.i""), sNewWidth]                                  },

      })     O.widgenden (Panel  lay to ,                    * @metTd olay to lializes tn propod toS,ch blurinftxecutn" fostlay to lr        * @metotherf   s cris repes.tTdithod will "sh trm
   thiyu thiy the "foc     * @mettructor
   d the ulall uptothe(witrefely edsnfostent-ting blurearkuO       * alongndcteEven"srequilee markuOamgr  currprovady has enting       * @method dest
        * @param {Overng} The he element whicIDresenting the id olay to l<em>OR</em> * @p* @param {OverElement} el The element whicesenting the id olay.
 y         etha {Overct} userConfig  Thecollecuration of tht literal repr     * @met oning the focuuturation of t should be usedto tthe Ovierlay.
 yer     * @metSemputuration of tdent.boda focusEre intudeer         * @me      init: function (userusing nfig) {
                /          * Thhhhh NoteiOvre w a nrelyps cthven 
   ig focuin dere ye  vicaown          * Thhhhh w aonly wa  Autftxecutn" o ed,{ trevenlowe(o cris rep lt h          */
             */
 Panel.rclass.dest: ful(this, shalus/*ing nfig) {
*/            this.initbesEreialit.fire();
 Panel            thisaddClass(acti.element;

 ,rPanel.FOCUPANEL            this.initbuildWrippysO            if (userConfig) {
                this.cfg.applyConfig(userConfig, true);
            }
             this.initcribe(mgr."PhowMask"  .cfg.clasFentsHer forn)              ._onOcribe(mgr."EvenMask"  .cfg.cved foFentsHer forn)              ._onOcribe(mgr."besEreRy dan",{geEvenHeadys            this.initcribe(mgr."ly dan"istion (p_
            /**
this.initcetF tsoLastFentsable            }
      .elemcribe(mgr."ged;
 ent, No
, .initcetF tsoLastFentsable           }
               this.initcribe(mgr."showpar.cfg.cfentsOnShow            this.initialit.fire();
 Panel       }
          /**
        * Ussedhod _syncorEnt;

 Fents     * Ussedhmte
        * @p       * Ussed"fents"ut handler for tor a fentableoent;

 . Uwn"    h trm
   thiy     * Ussedblur evenent;

 ch et iicesceivdsnfonts    ees th fore c Panel * @n* Ussed nce's destgddalin t ecprovatibromisors         *      * Ussedha {Overt, th Thgcollevent 
   ht lit     * Usse      _syncorEnt;

 Fents nction (p_
e){          if (us(_ently fModalhi=HO.cfg                 for over argigm=tt.on(oropTargig
e)u                     denf=ument.bodyment.bodEnt;

               i;





 ncideDenf=u( argigmoOvedenfo = argigmoOvewindow                    et maskmgndcment.bodEnt;

  checksr ddn" fostIE,ch blurfontsns le manamaskmh et iionfglickn" o d the fontsns le                  et Overment.bodEnt;

    the evenment.bod be(ollbarsr refglickn" o                  user ncideDenfo = argigmoOve.element;

 fo = argigmoOve.elemmaskm&& !addCisA eds   i.element;

 ,r argig
                    if (try               for         .cfg.cfentsF tsoModal            }
          }u ttch(err)              for         et Jto uin ctwn w afr      fents     * Uss           if (try               for             user ncideDenfo = argigmoOvement.body);

                                if (targig.blur            }
                  }         }
              }u ttch(err2   u}         }
                                      }
        },

        /**
        * UssedF-ntsns le manat tsonent;

 cuseenting ,bo 
  wisose,
lstbif the manatents meged;ismpfown" fost     * Ussedgddalin .tTdithod will not existry/ ttchatents fr   thes coll thiyr infesifone") & fost ttchthe excen Eves           ongndctakrepresmedi lsf as the. * @t* @ptyp * @t* @ptyphod findcfentsF tsoModal     * Usse      _syncfentsF tsoModal nction (p_
            /**
overelg=t;

 .t tsoEnt;

               !oveen                    elsfents            }
   e if (                  mgr. cfg._gddalF-nts                    o = .find_gddalF-ntssfents            }
  }
   e if (                  o = .findinnerEnt;

 lfents            }
  }
            }
        },

        /**
   p * @t* @ptypphod findclasFentsHer forn * @t* @ptypphatted
         */
typp      */
typp"PhowMask"nt handler for  shoulasl cr"fents"ut handler for    hll * @n* Ussed fentsableoent;

 scie evenment.bod    ee(-red crPanela nce's dest * @n* Ussed gddalin t the g manaatibromisors         *      * Usseddha {Ovevee, p_ rng} The omEvenut hand Str     * Usseddha {Oveves, p_aiy) {
}AomEvenut handuents.
       * Usse      _synclasFentsHer fornnction (p_
vee, p_aArgs, p_
               aove!.initt tsoEnt;

                     !ovee |webkiic|| UA.ty("a                    if (mgr.! cfg._gddalF-nts                    o =     .cfg._aeEvenHvedenF-ntstnts to(               }
  ((((                   e if (                  o = .findinnerEnt;

 ltabIndexg=t0          }
  }
            }
        },

    .cfg._so TabLoop(.initt tsoEnt;

 ar.cfg.lao Ent;

            }
  t.on(oveF-nts(ment.bodyment.bodEnt;

   .cfg._orEnt;

 Fents,rOvinue);
            }
  _ently fModalhis;

                   /**
        * UssedCeEven"shaEvedenifentsableoent;

 ,fown"    tents on           on   ee(-red gddalin t ostbrows.e tie h blurfontsrce exi     * UssedbeuyConOverhe computu r to gbox. * @t* @ptyp * @t* @ptyphod findcaeEvenHvedenF-ntstnts to     * Ussedhmte
        * @p        _syncaeEvenHvedenF-ntstnts to nction (p_
            /**
overef=ument.bodygeEventnts to("button"           }
  e.s(aOv.ht(conhis"1;
               e.s(aOv.width!sZ"1;
               e.s(aOv.posi, tha=to"bsolute               e.s(aOv.lefta=to-10000em               e.s(aOv.opacin t=t0          }
  eltabIndexg=t-               .findinnerEnt;

 lyCoendd(this            }
   cfg._gddalF-ntsg=te                  /**
        * Ussedthod _syncved foFentsHer forn * @t* @ptypphatted
         */
ty      */
typp"EvenMask"nt handler for  shoures focuothe"fents"ut handler forsr ddn"       */
typphe "foc"lasFentst.on(Her forn"hod wils         *      * Usseddha {Ovevee, p_ rng} The E hand Str     * Usseddha {Oveves, p_aiy) {
}AE handAents.
       * Usse      _syncved foFentsHer fornnction (p_
vee, p_aArgs, p_
               E
   nved foFentsener(o.e(ment.bodyment.bodEnt;

   .cfg._orEnt;

 Fents,rOvin            if (user_ently fModalhi=O.cfg                    _ently fModalhis;

                     },

        /**
        * UssedF-ntsdler for tor evenPhowtt han     * @p       * Ussedhod _syncfentsOnShow     * Ussedha {Overng} The  StriE handTStr     * Ussedhm {Striy) {
}AaArraE handuents.
       * Usseiva {Overct} userobj Addi Eveeprdata      * Usse      _syncfentsOnShow nction (p_
t p_aAa p_oOct.             /**
oTopaArrao =aArr[1]                    E
   natopt("bluaArr[1]           } el               mgr.!.inittentsF tso
t p_aAa p_oOct.                     !ove cfg.getProperty("focumodal"
                    if (tcfg.cfentsF tsoModal            }
                }
        },

        /**
        * UssedSetsnfonts    manat tsonent;

 cun id oPanels         *      * Ussedhod find
-ntsF tso     * Ussedhrn {StriBan} tr}s  only!ovsucTrigfuhiyufontsnd,es,
  mo 
  wisos     * Usse      _syn
-ntsF tsonction () {
t p_aAa p_oOct.            /**
overelg=t;

 .t tsoEnt;

 ,ufontsnd ves,
             if (isInaArrao =aArr[1]                    E
   natopt("bluaArr[1]           } el               mgr.en                    try               for     elsfents            }
      _syn
-ntsnd ve  on          }
  }
   e ttch(err)               for     // Ignore         }
                }
             }
  rn {Str
-ntsnd                  /**
        * UssedSetsnfonts    manalao uent;

 cun id oPanels         *      * Ussedhod find
-ntsLast     * Ussedhrn {StriBan} tr}s  only!ovsucTrigfuhiyufontsnd,es,
  mo 
  wiso     * Usse      _syn
-ntsLastnction () {
t p_aAa p_oOct.            /**
overelg=t;

 .lao Ent;

 ,ufontsnd ves,
             if (isInaArrao =aArr[1]                    E
   natopt("bluaArr[1]           } el               mgr.en                    try               for     elsfents            }
      _syn
-ntsnd ve  on          }
  }
   e ttch(err)                       // Ignore         }
                }
             }
  rn {Str
-ntsnd                  /**
        * UssedPtted
    itoornal od find
-r so TabLoop,ch blurce dbeutsnd by       */
typcris repes    jumpcun gndcmodife "focuents.
   ps c   it mgrrequilees         *      * Ussedhod find_so TabLoop     * Usseiva {OverElement} el Tt tsoEnt;

      * Usseiva {OverElement} el Tlao Ent;

      * Ussedhmtted
         */
ty      */
ty      _syncso TabLoop nction (p_
t tsoEnt;

 ,ulao Ent;

                 .cfg.to TabLoop(t tsoEnt;

 ,ulao Ent;

                    /**
        * UssedSetsnupto tab,nPhift-tab loop betweee manat tsongndclao uent;

       * Usseimttvvend. NOTE:dSetsnuptcurrert hanBif Tabngndcert hanTabOutrKeyener(o.e     * Usseianceof A erty on
  ,ch blur refntinout hryrimn Ovierod will "sinvokees         *      * Ussedhod findso TabLoop     * Usseiva {OverElement} el Tt tsoEnt;

      * Usseiva {OverElement} el Tlao Ent;

      * Usse      */
ty      _synso TabLoop nction (p_
t tsoEnt;

 ,ulao Ent;

              /**
overbif Tabn=t;

 .ert hanBif Tab,r abn=t;

 .ert hanTabOut              founrhowt = YAHO.cfg.thowt = Y,aEveet = YAHO.cfg.Eveet = Y           if (isInbif Tab)                   bif Tab.disable            }
      thowt = Ymunaribe(mgr.bif Tab.enable, bif Tab           }
      Eveet = Ymunaribe(mgr.bif Tab.disable, bif Tab           }
      bif Tabn=t;

 .ert hanBif Tabhis;

           }
             if (!ovetab)                   tab.disable            }
      thowt = Ymunaribe(mgr.tab.enable, tab           }
      Eveet = Ymunaribe(mgr.tab.disable,tab           }
       abn=t;

 .ert hanTabOuthis;

           }
             if (!ovet tsoEnt;

                     ;

 .ert hanBif Tabhis;ewrKeyener(o.e
t tsoEnt;

 ,u                     {Phift:  onlykeys:9}              i;





{fn:.inittentsLast,incty.:OvinuecotlyctScty.:O on                             }
      bif Tabn=t;

 .ert hanBif Tab                   thowt = Ymaribe(mgr.bif Tab.enable, bif Tabue);
            }
      Eveet = Ymaribe(mgr.bif Tab.disable,bif Tabue);
            }
             if (!ovelao Ent;

                     ;

 .ert hanTabOuthis;ewrKeyener(o.e
lao Ent;

 ,u                     {Phift:e,
   ykeys:9} u                     {fn:.inittentsF tso ancty.:OvinuecotlyctScty.:O on                             }
       abn=t;

 .ert hanTabOut                   thowt = Ymaribe(mgr.tab.enable, tabue);
            }
      Eveet = Ymaribe(mgr.tab.disable,tabue);
            }
        },

        /**
        * Ussedrns a Strray of the evenently fiyufontsableoitemsch bluress, af thein     * UssedPanel. strinigmhe fentsableoent;

 scmanamd findlooksnfostantudet to      * Usseian id oPanelsFOCUSABLEtic
   perty on          *      * Ussedhod findropFentsableEnt;

       * Usseiva {OverElement} el Troot ent;

 clloic
r cwthes         *       _synropFentsableEnt;

   nction (p_
root             /**
root =
root || .findinnerEnt;

 ;          /**
overfentsableo= {}, panelhis;

               fost(overit=t0 rit<oPanelsFOCUSABLEgth; i < i++                    fentsable[PanelsFOCUSABLE[i]] ve  on          }
                 or Not lookmanaby Tag, sif A we wa  Aent;

 scie evenordo          * Re         }
  rn {StrgetStylEnt;

  Byption () 
en    rn {Strpanel._tsstIfFentsable usinfentsable);e is;

 ,
root                   /**
        * UssedTdithisnOverre(o od findtsnd by ropFentsableEnt;

   allodet  m toch blurent;

 sclos     * Usseiancludeian id ofentsableoent;

 sclner. Uwnrerof th hrr, a "dithhe umEven th vior iors         *      * Ussedhod find_tsstIfFentsable     * Usseiva {Overct} userhe element whicg manatssto      * Usseiva {Overct} userfentsableoelemhashmhe knownifentsableoent;

 s,{geEvend by rray of -to-map ty("a () {onoPanelsFOCUSABLE     * Ussedhmtted
         */
ty      _synctsstIfFentsablenction (p_
esinfentsable)               aoveelsfentsao =elstStrioOve"Eveden"m&& !elsdisabledn&& fentsable[elstagNof .toLow("Casn  ]                    rn {Str  on          }
                rn {Strs,
                    /**
        * UssedSetsnOvert tsoEnt;

 ngndclao Ent;

 nanceof A erty on
       * Ussed   manat tsongndclao ufentsableoent;

 scie evenPanels         *      * Ussedhod findcetF tsoLastFentsable      */
ty      _synso F tsoLastFentsable nction (p_
                 .cfg.t tsoEnt;

 nis;

           }
  ;

 .lao Ent;

 his;

                overelt;

 scHO.cfg.ropFentsableEnt;

              }
  .inittentsableEnt;

   =relt;

 s           if (isInelt;

 sgth; i c {
                    .cfg.t tsoEnt;

 niselt;

 s[0]                  .cfg.lao Ent;

 hiselt;

 s[elt;

 sgth; i c- 1]          }
             if (!ovetcfg.getProperty("focumodal"
                    .cfg._so TabLoop(.initt tsoEnt;

 ar.cfg.lao Ent;

            }
        },

        /**
        * UssedIalizes tocuOvercmEvenut hansnfostModulhah blur reft the      * Ussedh trm
   thiyuhoulpertyri Ovrrimnsphe "focModulhas.dest      */
ty      _synialit.fir nction () {
                Panel.rclass.dest: fut.fir l(this, sh)               overSIGNATUREt=comEvent = Y.;
                 /           * Th* omEvent = YAt the aft   Overgddalin tmaskminfthown         * Th* @t handPhowMaskE han     * @p /
ty      _syn    .cfg.PhowMaskE hanAHO.cfg.geEvent("bluEVENTRTYPES.SHOW_MASK)              ._onOchowMaskE han.signat th =rSIGNATURE               /           * Th* omEvent = YAt the besEre Overgddalin tmaskminfthown. Sribe(mgrrsrce  rn {Strs,
  d   ent,
   Ove         * Th* maskm the g manathown         * Th* @t handbesEreShowMaskE han     * @p /
ty      _syn    .cfg.besEreShowMaskE hanAHO.cfg.geEvent("bluEVENTRTYPES.BEFORE_SHOW_MASK)              ._onObesEreShowMaskE han.signat th =rSIGNATURE               /           * Th* omEvent = YAt the aft   Overgddalin tmaskminfEveden         * Th* @t handEvenMaskE han     * @p /
ty      _syn    .cfg.EvenMaskE hanAHO.cfg.geEvent("bluEVENTRTYPES.HIDE_MASK)              ._onOEvenMaskE han.signat th =rSIGNATURE               /           * Th* omEvent = YAt the besEre Overgddalin tmaskminfEveden. Sribe(mgrrsrce  rn {Strs,
  d   ent,
   Ove         * Th* maskm the g manaEveden         * Th* @t handbesEreHvenMaskE han     * @p /
ty      _syn    .cfg.besEreHvenMaskE hanAHO.cfg.geEvent("bluEVENTRTYPES.BEFORE_HIDE_MASK)              ._onObesEreHvenMaskE han.signat th =rSIGNATURE               /           * Th* omEvent = YA the evenPanela "sdragg        */
t Th* @t handdragE han     * @p /
ty      _syn    .cfg.dragE hanAHO.cfg.geEvent("bluEVENTRTYPES.DRAG)              ._onOdragE han.signat th =rSIGNATURE                  /**
        * UssedIalizes tocuOverc.desonfggurationble erty on
  ch blurce dbeuged;
 e      * Ussedusthe id oPanelonfig  Theht lite(get). * @t* @ptyphod dest
   Dlt "moig  Th      */
ty      _synialiDlt "moig  Thnction () {
                Panel.rclass.dest: fuDlt "moig  Thl(this, sh)               // Addrpanelhig focuerty on
  c//              /           * Th* T} );ie evenPaneluld be udispto  cr"alof " button      */
t Th* @ig focualof       */
t Th* @tStriBan} tr      */
t Th* @ult "mow  on     * @p /
ty      _syn    .cfg.applydderty("focDEFAULT_CONFIG.CLOSE.ke ,  u                 ler for: .cfg.ag focClof                    e: thisDEFAULT_CONFIG.CLOSE.e: th y                 e: or. Ve: DEFAULT_CONFIG.CLOSE.e: or. Vely                 rclassedel: DEFAULT_CONFIG.CLOSE.rclassedel               )               /           * Th* Ban} tr specifythe ie evenPaneluld be ubeudraggable. tTd oult "mou         * Th* e from "s" } )anie evenDragngndcDrty .Domin t ecancluded,u         * Th* o 
  wisos  curr"s,
  ." <yed.ng>PLEASE NOTE:</yed.ng>tTd rom "sau         * Th* knowniissueian IE 6 (ng} iteMode"gndcQuirkthMode)"gndcIE 7u         * Th* (QuirkthMode)"wd romPanelsl shouei 
  a nrelyor esaue fromto tthe          * Th*  
 irtiwidth
 gguration property on , or eveirtiwidth
          * Th* gguration property on t ecio the "" tral add only beudraggablephe         * Th* ptocthe id ogdown le mana trighe evenPanelonfheadystent;

 . * @t* @pt Th* ToAt x "dithbug,udraggablepPanelslmissthe aue fromfor eveirt * @t* @pt Th* iwidth
 gguration property on , or whof (iwidth
 gguration prop         * Th* prty on t ecio the "" tral add or esitcio the compe fromo 
         * Th*  
 irtroot Elem ent;

 ' 
  HeigWidth besEre Oveyr refmadyu         * Th* ele") &. tTd o(thcul Over tdth isnOve {ved fodA the evenPanela "s u         * Th* Eveden. <em>Tditht x is only yConOverhe draggablepPanelslan IE 6          * Th* (ng} iteMode"gndcQuirkthMode)"gndcIE 7u(QuirkthMode)</em>.dF-r          * Th*  intuie(-rma () {onotdithissromtoe:         * Th* YUILibraryhbugs #1726972"gndc#1589210. * @t* @pt Th* @ig focudraggable      */
t Th* @tStriBan} tr      */
t Th* @ult "mow  on     * @p /
ty      _syn    .cfg.applydderty("focDEFAULT_CONFIG.DRAGGABLE.ke ,                   ler for: .cfg.ag focDraggable              foune: this(UDom,
D) ?e } );ise,
                aOvee: or. Ve: DEFAULT_CONFIG.DRAGGABLE.e: or. Vel                 rclassedel: DEFAULT_CONFIG.DRAGGABLE.rclassedel              )               /           * Th* Ban} tr specifythe ie evendraggablepPaneluld be ubeudrag only, exisitooract
    the drty          * Th*  argigsrhe manaeIge. * @t* @pt Th* <p> * @t* @pt Th* Wthe io the c onlydraggablepPanelsl add provaheckclloie);ie eveyr refh hr drty  argigs,         * Th* or t th evenDragDrty t hansnrequilee lloiupport drty  argigsitooract
) {
onDragEtoor,u         * Th* onDraglay., onDraglut, onDragDrty ttc.). * @t* @pt Th* If evenPanela "sprovdelignadahe bstdrtypn" o  Over argigselt;

 sche manaeIge,nOve {tdith * @t* @pt Th* flag ce dbeuio the c onthe  ibro eslas(-rmance. * @t* @pt Th* </p> * @t* @pt Th* <p> * @t* @pt Th* Wthe io the e,
   yothedrty  argigsrel Overt hansn add behd the.         t Th* </p> * @t* @pt Th* <p> * @t* @pt Th* Thrrerty on t ecio the s,
  d e ult "mowhe mr ttr t bif warmspareZIt")y (Ev butuld be ubeu * @t* @pt Th* io the c ontie drty  argigsitooract
) { "sprovrequilee tor evenPanel  he  ibro eslas(-rmance.</p> * @t* @pt Th*  * @t* @pt Th* @ig focudragOnle         * Th* @tStriBan} tr      */
t Th* @ult "mows,
       * @p /
ty      _syn    .cfg.applydderty("focDEFAULT_CONFIG.DRAG_ONLY.ke ,  u                 e: thisDEFAULT_CONFIG.DRAG_ONLY.e: th y                 e: or. Ve: DEFAULT_CONFIG.DRAG_ONLY.e: or. Vely                 rclassedel: DEFAULT_CONFIG.DRAG_ONLY.rclassedel               )               /           * Th* SetsnOverrStrio 
u dan
 ymhe dispto  tor evenPanel. V: or e: thth * @t* @pt Th*  ref"shadow," "ma te," gndc"none". t<yed.ng>PLEASE NOTE:</yed.ng>t * @t* @pt Th* ThrrgeEveof the Overu dan
 yment;

 c "sdefellee untilAevenPanela * @t* @pt Th*  ecanlizeslyfmadyuele") &. tF-r Gecko-basnd brows.e tf tMac * @t* @pt Th* OS X Overu dan
 ymen;

 c "salways{geEvend ass  currtsnd a"sau         * Th* shimd   ent,
   Aqua be(ollbarsrbelow crPanela nce's dm the pokmana         * Th*  
rouge    (ne);YUILibraryhbug #1723530). * @t* @pt Th* @ig focuu dan
 y         * Th* @tString
        * @r* Th* @ult "mowehadow     * @p /
ty      _syn    .cfg.applydderty("focDEFAULT_CONFIG.UNDY),
Y.ke ,  u                 ler for: .cfg.ag focU dan
 y,c                 e: thisDEFAULT_CONFIG.UNDY),
Y.e: th y                 rclassedel: DEFAULT_CONFIG.UNDY),
Y.rclassedel               )                       /           * Th* T} );ie evenPaneluld be ubstdispto orcun grgddalhfashiorly             edh trm
   thiyugeEveohe autranspaly f maskmh hr evenment.bod  sho             ed add provemoved fodAuntilAevenPanelainfdismisshe.         t Th* @ig focumodal     * Uss Th* @tStriBan} tr      */
t Th* @ult "mows,
       * @p /
ty      _syn    .cfg.applydderty("focDEFAULT_CONFIG.MODAL.ke ,  u                 ler for: .cfg.ag focModal,c                 e: thisDEFAULT_CONFIG.MODAL.e: th                  e: or. Ve: DEFAULT_CONFIG.MODAL.e: or. Vely                 rclassedel: DEFAULT_CONFIG.MODAL.rclassedel               )               /           * Th* ArKeyener(o.eA(ostanof the Keyener(o.es)l shou add behenabledy             ed the evenPanela "sthownd the disabledn the evenPanela "sEveden.         t Th* @ig focukeylner(o.e      * Uss Th* @tStriO.util.Dom,Keyener(o.e[]      */
t Th* @ult "mow;

      * @p /
ty      _syn    .cfg.applydderty("focDEFAULT_CONFIG.KEY_;
  ENERS.ke ,  u                 ler for: .cfg.ag focKeyener(o.esly                 rclsEvent: trueDEFAULT_CONFIG.KEY_;
  ENERS.rclsEvent: trly                 rclassedel: DEFAULT_CONFIG.KEY_;
  ENERS.rclassedel               )               /           * Th* UIing
   sdtsnd by evenPanel. string repsr ref nc("f   itoe comp(witos Elemd the ld be ubstescapnd by evenimpnt, "zostie areohe  the hn exoornal source. * @t* @pt Th*          t Th* @ig focung reps     * Uss Th* @tStrict} us     */
 s Th* @ult "mowA tht literal repr the the erty on
  cthownrbelow:         * Th*     <dl> * @t* @pt Th*         <dt>alof </dt><dd><em>Elem</em>;isTanamarkuOaoe own as manalabel tor evenalof (ac) . Dlt "mothhe "Clof ".</dd>         * Th*     </dl> * @t* @pt Th*      _syn    .cfg.applydderty("focDEFAULT_CONFIG.STRINGS.ke ,  u                 e: thiDEFAULT_CONFIG.STRINGS.e: th                  ler for:.cfg.ag focng
   s                  e: or. Ve:DEFAULT_CONFIG.STRINGS.e: or. Vel                 rclassedel:DEFAULT_CONFIG.STRINGS.rclassedel              )                  /**
 / BEGIN BUILT-IN PROPERTYtEVENT HANDLERSc//     /**
     /**
        * @metTd oult "mowt handler for tilee  the even"alof " erty on t ecged;
 e.     * @metTd ood destutu (ols manayCoendohe or Eveohe of evenalof (ac) { treven     * @metrip r(conghe evenPanel      * @method destag focClof      * @metha {Overng} The  StriTd oomEvent = YA Stri(usuthiyuthrrerty on t of )     * @metha {Overlt} us[]}AaArraTd oomEvent = YAuents.
  .dF-r gguration prop         *dler forsaAa p_[0]u add equalAevennewly yConOvere fromfor evererty on .         ethm {Strict} userobj strincty.rct.
   dF-r gguration propler forsaA     * @metrdith add usuthiyuequalAevenowners         *      _synag focClof nction () {
t p_aAa p_oOct.                 overvalhisa p_[0]l                 oClof AHO.cfg.glof                   rg repsrHO.cfg.getProperty("focung rep ")u                 fc           if (isInval                    !ove!oClof                  for     !ove!m_oClof ItruTs tl                       i = if (m_oClof ItruTs tl   f=ument.bodygeEventnts to("a                            m_oClof ItruTs tl   .s.desNof t=cuutu r to -alof "                          m_oClof ItruTs tl   .hreft=cu#               }
  ((((                       lClof AHOm_oClof ItruTs tl   .s.
ne.rem();
                         fcrHO.cfg.innerEnt;

 lf tsod(thi                   if (!isNfc                    o =     .cfg.innerEnt;

 l nc("fBesEre(oClof   fc           }
          }u if (                  o =     .cfg.innerEnt;

 lyCoendd(thislClof                }
  ((((                       lClof .innerElem = (rg repsr&& rg reps.glof ) ?erg reps.glof  nc"&#160;                    if (t.on(ove(oClof   "glickpar.cfg.cdoClof   Ovinue);
                     if (.cfg.glof verlilof                    }u if (                  o = lClof .s(aOv.dispto  =cublockp          }
                      e if (                  mgr.oClof                     o = lClof .s(aOv.dispto  =cunone"          }
                }
                     /**
        * UssedE handler for tor evenalof (ac)      * Ussed     * Ussedhod find_doClof      * Ussedhatted
         */
typ     * Ussedha {Stri(wit, th Th     * Usse      _syncdoClof  nction (useru
               E
   nent,
  Dlt "mos            }
   cfg.Even(                   /**
        * UsetTd oult "mowt handler for tilee  the even"draggable" erty on t     * Uset ecged;
 e.     * @method destag focDraggable      */
etha {Overng} The  StriTd oomEvent = YA Stri(usuthiyuthrrerty on t of )     * @metha {Overlt} us[]}AaArraTd oomEvent = YAuents.
  .dF-r gguration prop         *dler forsaAa p_[0]u add equalAevennewly yConOvere fromfor evererty on .         ethm {Strict} userobj strincty.rct.
   dF-r gguration propler forsaA     * @metrdith add usuthiyuequalAevenowners         *      _synag focDraggablenction () {
t p_aAa p_oOct.            /**
overvalhisa p_[0]           if (isInval                    !ove!UDom,
D)                   if (tcfg.applsoperty("focudraggable",es,
                 }
  ((((rn {St          }
                         !ove cfg.Eeadys                    if (getSsopS(aOve cfg.Eeadys  "gurson"is"d fo                         cfg.regner(rDragDrty            }
                 }
      ._onOcribe(mgr."besEreShowpareigWidthToO HeigWidth                 e if (                   !ove cfg.dd)                   if (tcfg.dd.unreg            }
                 }
      !ove cfg.Eeadys                    if (getSsopS(aOve cfg.Eeadys "gurson"io" tra           }
                 }
      ._onOunaribe(mgr."besEreShowpareigWidthToO HeigWidth       }
        }
       u            /**
        * UsetTd oult "mowt handler for tilee  the even"u dan
 y" erty on t     * Uset ecged;
 e.     * @method destag focU dan
 y         etha {Overng} The  StriTd oomEvent = YA Stri(usuthiyuthrrerty on t of )     * @metha {Overlt} us[]}AaArraTd oomEvent = YAuents.
  .dF-r gguration prop         *dler forsaAa p_[0]u add equalAevennewly yConOvere fromfor evererty on .         ethm {Strict} userobj strincty.rct.
   dF-r gguration propler forsaA     * @metrdith add usuthiyuequalAevenowners         *      _synag focU dan
 ynction () {
t p_aAa p_oOct.                 overbMacGecko!sZI.elemtl  (-rmve=tomac"m&& UAProcko)u                 sU dan
 yhisa p_[0].toLow("Casn  l                 oU dan
 yhis._onOundy,
        Dom         oEnt;

 his.element;

                tion () {geEvenU dan
 y
            /**
thisoverbNew ves,
            /**
this!ove!oU dan
 y    //{geEvens!ovprovady has ie eve              for     !ove!m_oU dan
 yTs tl                       i = if (m_oU dan
 yTs tl   f=ument.bodygeEventnts to("div                            m_oU dan
 yTs tl   .s.desNof t=cuu dan
 y"              }
  ((((                       lU dan
 yhism_oU dan
 yTs tl   .s.
ne.rem(s,
                 }
  ((((.element;

 lyCoendd(thislU dan
 y                    if (.cfg.u dan
 yhislU dan
 y                   if (!isNbIEQuirkt                            ._onOciznU dan
 y
                           tcfg.applsribe(mgrTlig = Yt("bluiwidth
, ._onOciznU dan
 y                           tcfg.applsribe(mgrTlig = Yt("bluiht(con
, ._onOciznU dan
 y                            tcfg.aed;
 ent, Not = Ymaribe(mgr.t_onOciznU dan
 y                           O.widget.OverModulh. triReciznt = Ymaribe(mgr.t_onOciznU dan
 y,rOvinue);
            }
                                 !ovee |webkiic&& UAPwebkiic< 42
                            tcfg.aed;
 ent, Not = Ymaribe(mgr.t_onO(-redU dan
 yRedraw           }
                                 bNew ve  on          }
  }
            }
                 tion () {onBesEreShow
            /**
thisoverbNew vegeEvenU dan
 yl(this, sh)                  !ove!bNew && bIEQuirkt                        ._onOciznU dan
 y
                                     .cfg.cu dan
 yDefellee ves,
            /**
this._onObesEreShowt = Ymunaribe(mgr.onBesEreShow           }
             if (tion () {deyed.yU dan
 y
            /**
thismgr. cfg._u dan
 yDefellee                        ._onObesEreShowt = Ymunaribe(mgr.onBesEreShow           }
          .cfg.cu dan
 yDefellee ves,
            /**
this           }
      !oveoU dan
 y                    if (tcfg.applunaribe(mgrFromig = Yt("bluiwidth
, ._onOciznU dan
 y                       tcfg.applunaribe(mgrFromig = Yt("bluiht(con
,._onOciznU dan
 y                       tcfg.aed;
 ent, Not = Ymunaribe(mgr.t_onOciznU dan
 y                       tcfg.aed;
 ent, Not = Ymunaribe(mgr.t_onO(-redU dan
 yRedraw           }
          O.widget.OverModulh. triReciznt = Ymunaribe(mgr.t_onOciznU dan
 y  Ovinue);
                     if (.cfg.ent;

 lved fod(thislU dan
 y                    if (.cfg.u dan
 yhis;

           }
                }
                 switcha(sU dan
 y                    ctwn "shadow :                 if (getSved fod(actioEnt;

 ar"ma te                        addClass(actioEnt;

 ar"shadow                        beEvk                  ctwn "ma te :                 if (!ove!bMacGecko                            deyed.yU dan
 yl(this, sh)                      }         }
          getSved fod(actioEnt;

 ar"shadow                        addClass(actioEnt;

 ar"ma te                        beEvk                  ult "mo:                 if (!ove!bMacGecko                            deyed.yU dan
 yl(this, sh)                      }         }
          getSved fod(actioEnt;

 ar"shadow                        addCved fod(actioEnt;

 ar"ma te                        beEvk                         if (!ove(sU dan
 yhi=r"shadow   || (bMacGecko!&& !lU dan
 y                     !ove cfg.getProperty("focuble") &&
                    if (overbNew vegeEvenU dan
 yl(this, sh)                      !ove!bNew && bIEQuirkt                            ._onOciznU dan
 y
                                          e if (                  o = mgr.! cfg._u dan
 yDefellee                            ._onObesEreShowt = Ymaribe(mgr.onBesEreShow           }
              ._onOcu dan
 yDefellee ve  on          }
  }
                              }
        }
       u              /**
        * UsetTd oult "mowt handler for tilee  the even"modal" erty on t ec     * Usetged;
 e.dTdithler for aribe(mgrs en unaribe(mgrs    manaPhowtthe Even     * Usett hansn   ler fo evenmispto  or Eveeghe evengddalin tmask.     * @method destag focModal     * Usetha {Overng} The  StriTd oomEvent = YA Stri(usuthiyuthrrerty on t of )     * @metha {Overlt} us[]}AaArraTd oomEvent = YAuents.
  .dF-r gguration prop         *dler forsaAa p_[0]u add equalAevennewly yConOvere fromfor evererty on .         ethm {Strict} userobj strincty.rct.
   dF-r gguration propler forsaA     * @metrdith add usuthiyuequalAevenowners         *      _synag focModalnction () {
t p_aAa p_oOct.                 overmodalhisa p_[0]              mgr.modal                    !ove!._onOchasMddalin t = Yener(o.es)l               for     ._onOcribe(mgr."besEreShowpar._onObuildMask                       tcfg.cribe(mgr."besEreShowpar._onOb} ThToTop                       tcfg.cribe(mgr."besEreShowpar._onOchowMask                       tcfg.cribe(mgr."Evenpar._onOEvenMask                    if (lay.
 yewindowReciznt = Ymaribe(mgr.t_onOciznMask u                         tcfgue);
                     if (.cfg.chasMddalin t = Yener(o.es ve  on          }
  }
            }
   e if (                  mgr. cfg._hasMddalin t = Yener(o.es)l               for     !ove cfg.getProperty("focuble") &&
                    if (if (.cfg.EvenMask
                           tcfg.ved foMask
                                              ._onOunaribe(mgr."besEreShowpar._onObuildMask                       tcfg.unaribe(mgr."besEreShowpar._onOb} ThToTop                       tcfg.unaribe(mgr."besEreShowpar._onOchowMask                       tcfg.unaribe(mgr."Evenpar._onOEvenMask                    if (lay.
 yewindowReciznt = Ymunaribe(mgr.t_onOciznMask u, sh)                       tcfg.chasMddalin t = Yener(o.es ves,
            /**
this      /**
this      /**
        /**
        * UsetRes focuevengddalin tmask.     * @method destved foMask         *      _synved foMasknction () {
                 overoMaskhis.elemmask      Dom         oPaly f.rem           if (isInoMask                    /          * Thhhhh    H, a "dnamaskmbesEre deyed.ythe it    ees th fore eve         * Thhhhh    t handler forsronifentsableoent;

 s gigsred fod.         * Thhhhh*      _syn        .cfg.EvenMask
        Dom         oPaly f.remhislMask.paly f.rem      Dom         isInoPaly f.rem                    o = lPaly f.remlved fod(thislMask                              }
      ._onOmaskhis;

                     },

       },

     /**
        * UsetTd oult "mowt handler for tilee  the even"keylner(o.e " erty on t     * Uset ecged;
 e.     * @method destag focKeyener(o.es     * Usetha {Overng} The  StriTd oomEvent = YA Stri(usuthiyuthrrerty on t of )     * @metha {Overlt} us[]}AaArraTd oomEvent = YAuents.
  .dF-r gguration pro         *dler forsaAa p_[0]u add equalAevennewly yConOvere fromfor evererty on .         ethm {Strict} userobj strincty.rct.
   dF-r gguration propler forsaA     * @metrdith add usuthiyuequalAevenowners         *      _synag focKeyener(o.esnction () {
t p_aAa p_oOct.                 overlner(o.es vea p_[0]l                 lner(o.el                 nener(o.esl     Dom         i                       !ovelner(o.es)l               for !ovelner(o.esa nce's dhe y) {
)l               for     nener(o.es velner(o.esgth; i <                      fost(it=t0 rit<onener(o.es< i++                             lner(o.e velner(o.es[i]                               for !ove!ig  Thlady hasSribe(mgrd(._onOchowt: trly                             lner(o.e.enable, lner(o.e)                                 ._onOchowt: trmaribe(mgr.lner(o.e.enable,                                  lner(o.eue);
                     if (((((                       for !ove!ig  Thlady hasSribe(mgrd(._onOEveet = Yly                             lner(o.e.disable, lner(o.e)                                 ._onOEveet = Ymaribe(mgr.lner(o.e.disable,                                  lner(o.eue);
                     if (((((    ._onOdeyed.yt = Ymaribe(mgr.lner(o.e.disable,                                  lner(o.eue);
                            }         }
                              e if (                   o = mgr.!ig  Thlady hasSribe(mgrd(._onOchowt: trly                         lner(o.esgenable, lner(o.es)                             ._onOchowt: trmaribe(mgr.lner(o.es.enable,                              lner(o.esly);
            }
                                 !ove!ig  Thlady hasSribe(mgrd(._onOEveet = Yly                         lner(o.es.disable, lner(o.es)                             ._onOEveet = Ymaribe(mgr.lner(o.es.disable,                              lner(o.esly);
                             ._onOdeyed.yt = Ymaribe(mgr.lner(o.es.disable,                              lner(o.esly);
                                                                              /**
        * UsetTd oult "mowler for tor evenung rep "perty on          ethod destag focSg reps     * Us*      _synag focng
   sdnction (p_
t p_aAa p_oOct.                overvalhisLd;
.mergecDEFAULT_CONFIG.STRINGS.e: th ya p_[0]           }
   cfg.applsoperty("focDEFAULT_CONFIG.STRINGS.ke , val,c);
                    /**
        * UsetTd oult "mowt handler for tilee  the even"ht(con
 erty on t ecged;
 e.     * @method destag focHt(con     * Usetha {Overng} The  StriTd oomEvent = YA Stri(usuthiyuthrrerty on t of )     * @metha {Overlt} us[]}AaArraTd oomEvent = YAuents.
  .dF-r gguration prop         *dler forsaAa p_[0]u add equalAevennewly yConOvere fromfor evererty on .         ethm {Strict} userobj strincty.rct.
   dF-r gguration propler forsaA     * @metrdith add usuthiyuequalAevenowners         *      _synag focHt(connction () {
t p_aAa p_oOct.            /**
overht(conhisa p_[0]l                 elg=t;

 .innerEnt;

 ;          /**
getSsopS(aOveel  iht(con
, ht(con           }
   cfg.applretilet("bluiif{Ste                    /**
        * UssedTd oult "mowcmEvenut handler for executedn the evenPanelonfhe(conh ecged;
 e,p     * Ussedie even" trfaddhe(conherty on thasrbehe io s         *      * Ussedhod find_" trFaddOnHt(conCed;
      * Ussedhatted
         */
typha {Overng} The  StriTd oe = YA Str      */
typha {Overy) {
}AaArraTd oanof the uents.
   ps c      e handPribe(mgrrs      */
typha {OverElement} el The elemEeadys  bod  or footystent;

 ch blurithhe emovecizn"    till * @n* Ussedoutrevenutu r to nfhe(con * @n* Usse      _syncl trFaddOnHt(conCed;
 dnction (p_
t p_aAa p_oOen                Panel.rclass.destcl trFaddOnHt(conCed;
 lyColy(tcfgueuents.
  )              mgr.bIEQuirkt                    overpanelhis;

                   so Timeout(tion (p_
            /**








panel.ciznU dan
 y
                    ,0           }
        },

        /**
        * UsetTd oult "mowt handler for tilee  the even"width
 erty on t ecged;
 e.     * @method destag focWidth     * Usetha {Overng} The  StriTd oomEvent = YA Stri(usuthiyuthrrerty on t of )     * @metha {Overlt} us[]}AaArraTd oomEvent = YAuents.
  .dF-r gguration prop         *dler forsaAa p_[0]u add equalAevennewly yConOvere fromfor evererty on .         ethm {Strict} userobj strincty.rct.
   dF-r gguration propler forsaA     * @metrdith add usuthiyuequalAevenowners         *      _synag focWidthnction () {
t p_aAa p_oOct.                 /**
overwidth!sZa p_[0]l                 elg=t;

 .innerEnt;

 ;              /**
getSsopS(aOveel  iwidth
, width       }
       cfg.applretilet("bluiif{Ste             },

       },

     /**
        * UsetTd oult "mowt handler for tilee  the even"zIndex
 erty on t ecged;
 e.     * @method destag foczIndex     * Usetha {Overng} The  StriTd oomEvent = YA Stri(usuthiyuthrrerty on t of )     * @metha {Overlt} us[]}AaArraTd oomEvent = YAuents.
  .dF-r gguration prop         *dler forsaAa p_[0]u add equalAevennewly yConOvere fromfor evererty on .         ethm {Strict} userobj strincty.rct.
   dF-r gguration propler forsaA     * @metrdith add usuthiyuequalAevenowners         *      _synag foczIndexnction () {
t p_aAa p_oOct.            /**
Panel.rclass.destag foczIndexl(this, sh, t p_aAa p_oOct.            if (isIn._onOmaskh|| .findgetProperty("focumodal"
 ==ve  on                    overpanelZg=tgetStylS(aOve cfg.ent;

 ar"zIndex
)                  !ove!panelZg|| isNaN(panelZ
                    if (panelZg=t0                             }
      !ovepanelZg==ve
                        // Regursiv o(thlhhe ug foczindexg(h blurld be ubstatopp        */
t Th        //  the goohe  urt
  abecaown panelZgld be uno lo;
 rg==ve
       */
t Th        tcfg.applsoperty("focuzIndex
, 1                    e if (                  o = ._onOctackMask
                         /**
this      /**
        /**
 / END BUILT-IN PROPERTYtEVENT HANDLERSc//     /**
        * UsetBuildcuevenwryCoohe utu r to Aa ound evenPanel fore urrtsnd the          * posi Evethe id oshadow gndcma teuu dan
 ys. striutu r to Aent;

 c "s         * designadahe a  lo(tha nce's dmoveiableo(thlediutu r to d the even     * @metent;

 c "sre nc("f   itsveeghe it.     * @method destbuildWryCoo          *      _synbuildWryCoo nction () {
                 overent;

 Paly fhis.element;

 .paly f.rem      Dom         originalEnt;

 his.element;

       Dom         wryCoo f=ument.bodygeEventnts to("div    
Dom         wryCoo .s.desNof t=cPanel.CSS_PANEL_CONTAINER;
Dom         wryCoo .ie veoriginalEnt;

 .ie + "_c                isInelt;

 Paly f                    elt;

 Paly fl nc("fBesEre(wryCoo ,eoriginalEnt;

            }
             if (wryCoo .yCoendd(thislriginalEnt;

         }
       cfg.ent;

 hiswryCoo       }
       cfg.innerEnt;

  veoriginalEnt;

 ;          /**
getSsopS(aOve cfg.innerEnt;

 ar"ble")y (Ev"is"in
  it                    /**
        * Us* AdjmEvcuevenciznghe evenshadow basnd he manaciznghe evenent;

 . * @t* @pedhod findciznU dan
 y         *      _synciznU dan
 ynction () {
                overoU dan
 yhis._onOundy,
        Dom         oEnt;

            if (isInoU dan
 y                    oEnt;

 his.element;

                   oU dan
 yls(aOv.width!sZoEnt;

 .  HeigWidth + "px"              }
  oU dan
 yls(aOv.ht(conhisoEnt;

 .  HeigHt(con + "px"                    /**
        /**
        * UsetRegner(rs evenPanelonfheadystthe drag & drty capa)y (Ev.     * @method destvegner(rDragDrty         *      _synvegner(rDragDrtynction () {
                 overmehis;

            if (isIn._onOEeadys                     !ove!UDom,
D)                   if (rn {St          }
                         overbDragOnle!sZI.elemgetProperty("focudragonly"
 ==ve  on                    /           * Th * @metstriO.util.Dom,DDa nce's d,rtsnd he  ibnt;

 clhendraggablepheadystthe manaeInela fndraggablep "senabled         * Th * @me         * Th * @met@erty on tdd         * Th * @me @tStriO.util.Dom,DD         * Th * @me      _syn        .cfg.ddhis;ewrUDom,
De cfg.ent;

 .iear._onOiear{dragOnle:rbDragOnle )                   !ove!._onOheadysOie                    if (tcfg.headysOieg=t;

 .ie + "_hp          }
                         .cfg.dd.ic
r Dragn=ction () {
                         overo HeigHt(con              i;









  HeigWidth              i;









viewPorgWidth              i;









viewPorgHt(con              i;









be(ollX              i;









be(ollY                   if (!isNO.utilenv.ua.iehi=r6                    if (if (addClass(actimement;

  udrag                                               !ovememgetProperty("focuutustrr ttoviewport")                             overnViewportO Heign=clay.
 yeVIEWPORT_OFFSE                              HeigHt(con = mement;

 .  HeigHt(con                            HeigWidth = mement;

 .  HeigWidth                           viewPorgWidthg=tgetStylViewportWidth
                           viewPorgHt(cong=tgetStylViewportHt(con
        Dom                 be(ollXg=tgetStylDent.bodSe(ollLeft
                           be(ollYg=tgetStylDent.bodSe(ollTty                             isIno HeigHt(con + nViewportO Heign< viewPorgHt(con                    if (if (((((._onOminYg=tbe(ollYg+ nViewportO Heig                          ((((._onOmaxYg=tbe(ollYg+ viewPorgHt(cong-   HeigHt(con - nViewportO Heig                          }u if (                  o =     ((((._onOminYg=tbe(ollYg+ nViewportO Heig                          ((((._onOmaxYg=tbe(ollYg+ nViewportO Heig                          }                          isIno HeigWidth + nViewportO Heign< viewPorgWidth (                  o =     ((((._onOminXg=tbe(ollXg+ nViewportO Heig                          ((((._onOmaxXg=tbe(ollXg+ viewPorgWidthg-   HeigWidth - nViewportO Heig                          }u if (                  o =     ((((._onOminXg=tbe(ollXg+ nViewportO Heig                          ((((._onOmaxXg=tbe(ollXg+ nViewportO Heig                          }                          .elemgtustrr tX ve  on          }
  }
          .elemgtustrr tY ve  on          }
  }
       u if (                  o =     .cfg.gtustrr tX ves,
            /**
this        .elemgtustrr tY ves,
            /**
this    }                      memdragE han.tilecung
r Drag"ueuents.
  )                  }                   .cfg.dd.onDragn=ction () {
                        memsyncPosi Eve
                       memgetPretilet("bluiif{Ste                        isIn._onOtl  (-rmve=tomac"m&& O.utilenv.ua.gecko                            ._onOchowMacGeckoSe(ollbars
                                              memdragE han.tilecuonDrag"ueuents.
  )                  }                   .cfg.dd.endDragn=ction () {
                         !isNO.utilenv.ua.iehi=r6                    if (if (addCved fod(actimement;

  udrag                                               memdragE han.tilecuendDrag"ueuents.
  )                      memd foE han.tilecmemgetProperty("focuxy"
                    }                   .cfg.dd.eigHer foElId(._onOheadysOie                   .cfg.dd.lasIne: orHer foTStr("INPUT                    .cfg.dd.lasIne: orHer foTStr("SELECT                    .cfg.dd.lasIne: orHer foTStr("TEXTAREA                      },

       },

     /**
        * UsetBuildcuevenmaskhfore urrlaie h hr evenment.bod  the evenPanela "s     * Usetgguratioadahe bstmodal.     * @method destbuildMask         *      _synbuildMasknction () {
                overoMaskhis.elemmask              mgr.!oMask                    !ove!m_oMaskTs tl                       i = m_oMaskTs tl   f=ument.bodygeEventnts to("div                        m_oMaskTs tl   .s.desNof t=cumask"              }
  ((((m_oMaskTs tl   .innerElem = "&#160;           }
  ((((}         }
      oMaskhism_oMaskTs tl   .s.
ne.rem();
            }
      oMaskOieg=t;

 .ie + "_mask"           }
      ment.bodybod l nc("fBesEre(oMask ument.bodybod lf tsod(thi                    ._onOmaskhisoMask                   !oveO.utilenv.ua.geckom&& ._onOtl  (-rmve=tomac"                    if (getSlass(acti.elemmask cublock-be(ollbarsa           }
                 }
      // Stackamaskmbasnd he manaent;

 hzindex                 ._onOctackMask
                     /**
        /**
        * UsetH, acuevengddalin tmask.     * @method destEvenMask         *      _synEvenMasknction () {
                !ovetcfg.getProperty("focumodal"
m&& ._onOmaskm&& ._onObesEreHvenMaskE han.tilec
                    .cfg.mask.s(aOv.dispto  =cunone"          }
      addCved fod(actiment.bodybod ,cumasked                    .cfg.hvenMaskE han.tilec
                    /**
        /**
        * UsetShowcuevengddalin tmask.     * @method destchowMask         *      _synchowMasknction () {
                !ovetcfg.getProperty("focumodal"
m&& ._onOmaskm&& ._onObesEreShowMaskE han.tilec
                    getSlass(actiment.bodybod ,cumasked                    .cfg.ciznMask
                   .cfg.mask.s(aOv.dispto  =cublockp          }
      ._onOchowMaskE han.tilec
                    /**
        /**
        * UsetSevcuevenciznghe evengddalin tmaskmhe ug hr evenhan th be(ollablep     * UsetaeEvghe evenment.bod     * @method destciznMask         *      _synciznMasknction () {
                !ovetcfg.mask             }
      // Shrinkamaskmf tso, so it doesrelyaffliteevenment.bod cizn.         * Thhhhhovermaskhis.elemmask      Dom         hhhhoiewWidthg=tgetStylViewportWidth
       Dom         hhhhoiewHt(cong=tgetStylViewportHt(con
        Dom         !ovemask.  HeigHt(con >hoiewHt(con                    i = mask.s(aOv.ht(conhisoiewHt(cong+ "px"              }
             }
      !ovemask.  HeigWidthg>hoiewWidth                    i = mask.s(aOv.width!sZoiewWidthg+ "px"              }
             }
      // Tthe iizngi the compment.bod     * @m    i = mask.s(aOv.ht(conhisgetStylDent.bodHt(con
 g+ "px"              }
  mask.s(aOv.width!sZgetStylDent.bodWidth
  + "px"                    /**
        /**
        * UssetSevcuevenzindexghe evengask cie it exists,mbasnd he manazindexghe      * UssetevenPanelaent;

 .tstrizindexghe evengaskt ecio the bn lee less      * UssetevantevenPanelaent;

 'sizindex. * @t* @ptyp * @t* @ptyp<p>NOTE:dTdithod dest add proveump uOaotrizindexghe evenPanel     * Ussete  ees th fore evengasktha"saunon-negativ ozindex. If youvrequile eve     * Ussetgasktzindexghe bn 0 or Evgh.eue)trizindexghe evenPanelp * @t* @ptypld be ubstao the aue fromEvgh.etevant0, besEre Ovithod dest ecgthled. * @t* @ptyp</p> * @t* @ptethod destctackMask * @t* @pte      _synctackMasknction (p_
                !ovetcfg.mask               }
   overpanelZg=tgetStylS(aOve cfg.ent;

 ar"zIndex
)                  !ove!O.utilld;
.isU dafined(panelZ
!&& !isNaN(panelZ
                    if (getSsopS(aOve cfg.mask cuzIndex
, panelZg- 1                                       /**
        /**
        * UsetRe dans evenPanel by  nc("fthe id oent;

 s fore  refprovady has ie      * Usetevengaie Panela nhe comir ggrrliteptoces. Op (p_thiyuyCoends even     * @metPanel f  manaPpecifOvernremhprihe m  manare dan'siexecuti) . NOTE:d     * @metF-r Panelsl a deut existthe markuOue)triyCoendTo.remhuents.
 a "s     * UsetREQUIRED. If ev "saents.
 a "sommitend ahe evencurly fhent;

 c "s         * provsEve

 c n compment.bodue)trition () { add rn {St e,
   y         * indicafthe idre evenre dan wa"saufail th.     * @method destve dan     * @metha {Overng} The yCoendTo.remhTanaent;

 hidahe h blurevenModulhy         * ld be ubstyCoendadahe prihe m  ve danthe <em>OR</em>     * @metha {OverElement} el TyCoendTo.remhTanaent;

 hhe h blurevenModulhy         * ld be ubstyCoendadahe prihe m  ve danthe     * @methrn {St {ban} tr} Success or fail thghe evenve dan     * @me      _synve dannction () {
yCoendTo.rem                rn {St Panel.rclass.destve danl(this, sh, yCoendTo.remar._onOinnerEnt;

                    /**
        * UssedRe dans evencurly fiyuao theadyst nhe i 'sierty o posi Eveuu dan even     * @msetgodulhyent;

 .tIe evengddulhyent;

 { "sprovbro vend cu._onOinnerEnt;

 "p     * Ussedirrtsnds         *      * Ussedhod find_ve danHeadys     * Ussedhatted
         */
typha {OverElement} el TgddulhEnt;

 hOp (p_th. A rnfeve ce m  managddulhyent;

  * @t* @pte      _syn_ve danHeadysnction (p_
gddulhEnt;

 )              gddulhEnt;

 h= gddulhEnt;

 h|| .findinnerEnt;

 ; 			Panel.rclass.destcve danHeadysl(this, sh, gddulhEnt;

 )                  /**
        * UssedRe dans evencurly fiyuao tbod   nhe i 'sierty o posi Eveuu dan even     * @msetgodulhyent;

 .tIe evengddulhyent;

 { "sprovbro vend cu._onOinnerEnt;

 "p     * Ussedirrtsnds         * d     * Ussedhod find_ve danBod      * Ussedhatted
         */
typha {OverElement} el TgddulhEnt;

 hOp (p_th. A rnfeve ce m  managddulhyent;

 . * @t* @pte      _syn_ve danBod nction (p_
gddulhEnt;

 )              gddulhEnt;

 h= gddulhEnt;

 h|| .findinnerEnt;

 ;             Panel.rclass.destcve danBod l(this, sh, gddulhEnt;

 )                  /**
        * UssedRe dans evencurly fiyuao tfootyst nhe i 'sierty o posi Eveuu dan even     * @msetgodulhyent;

 .tIe evengddulhyent;

 { "sprovbro vend cu._onOinnerEnt;

 "p     * Ussedirrtsnds         *      * Ussedhod find_ve danFootys     * Ussedhatted
         */
typha {OverElement} el TgddulhEnt;

 hOp (p_th. A rnfeve ce m  managddulhyent;

  * @t* @pte      _syn_ve danFootysnction (p_
gddulhEnt;

 )              gddulhEnt;

 h= gddulhEnt;

 h|| .findinnerEnt;

 ;             Panel.rclass.destcve danFootysl(this, sh, gddulhEnt;

 )                  /**
        * UsetRes focuevenPanelaent;

   the even(witohe levcuthlhc(thioent;

 s     * Useteos;

 .     * @method destdeyed.y     * @metha {Overban} tr} shthlowPurgetIe erth yonly manaeIly fhent;

 'si(witt handlner(o.esa refpurged.tIe e,
   yor provbro vend cthlhc(thily a ref,
 ofpurgedghe (witt handlner(o.es.y         * NOTE:dTdritlagc "sar"shahlowPurge"itlag, as opposedahe h af mayubsty gdref ntuitiv o"purgeC(thily "itlagahe gaie r tmbackwards compaft)y (Evr the behavihe prihe m  2.9.0s         *      _syndeyed.ynction () {
shahlowPurge                lay.
 yewindowReciznt = Ymunaribe(mgr.t_onOciznMask u, sh)              tcfg.ved foMask
               !ovetcfg.glof                     E
   neurgetnts to(tcfg.glof                      /**
    Panel.rclass.destdeyed.yl(this, sh, shahlowPurge ;       },

        /**
        * UssedF-redcuevenu dan
 yment;

 che emovepaie edah
rouge )triyColicafton/ved falp * @t* @ptyphe u yui-(-red-redraw s.des m  manau dan
 yment;

 s         *      * Ussedhod find(-redU dan
 yRedraw * @t* @pte      _syn(-redU dan
 yRedraw nction () {
                overuhis._onOundy,
                getSlass(actiu cuyui-(-red-redraw
)              so Timeout(tion (p_
 {addCved fod(actiu cuyui-(-red-redraw
) }, 0)                  /**
        * UsetRe {St"sarng} ThovepEve

 n prophe evenct.
        * @method desttong
        * @rethrn {St {ng} The string} ThovepEve

 n prophe evenPanel      * @me      _syntong
   nction () {
                rn {St "Panela" +t;

 .ie                      )   }(
   (tion () {
                 yp<p>     ypDialogc "san  ibnt;

 n prophe Panel fore canrbertsnd he arimit (-rmv     ypdata.     yp</p>     yp<p>     ypBuilt- tmtion () alin tthe buttonsl a d t handler forsrisa ncluded.t     ypIe evenop (p_th YUIpButtonndeoendancyrisa ncluded he manapageue)tributtons     ypgeEvendu add beh nce's dsphe O.widget.OverButton yot
  wismovegulverElem buttons     yp add behgeEvend.     yp</p>     yp<p>     ypF-rms canrberbrocess   it 3 ways -- viasan async
ronous Connen () {.Domin t(thi,t     ypa s ibnt (-rmvPOSTyor GET yor manuthiy.tstriYUIpConnen () {.Domin tld be ubs     yp ncluded if you'rertsthe id oult "mow"async" postod des, but{ "sprovrequiled if     ypyou'rertsthe anyphe evenct
  apostod desue fros.     yp</p>     yp@ of space O.widget.Ove     yp@s.des Dialog     yp@exoonds O.widget.OverPanel     * @ig ng}uctor     * @a {Overng} The he element;

 cIDovepEve

 the id oDialogc<em>OR</em>     ypha {OverElement} el The element;

 cvepEve

 the id oDialog     ypha {Overct} usertsnrig  Th striuturation propht literal reprutu r tthe      ypttriuturation propfore ld be ubstao tthe mais Dialog. Seriuturation prop     ypdent.bodn propthe mEre de r ls.     y      O.widget.OverDialogc=ction () {
el  tsnrig  Th            O.widget.OverDialog.rclass.destag ng}uctorl(this, sh, el  tsnrig  Th       }       overt = YA=iO.util.Dom,t = Yl         omEvent = YA=iO.util.Dom,omEvent = Yl         addA=iO.util.Dom,addl         aialogc=cO.widget.OverDialogl         Ld;
c=cO.widgld;
       /**
        * UssedConce's cvepEve

 the id o of phe evenDialog'siev

 s     * Usmet@erty on tEVENT_TYPES     * Usmet@erivven     * Usmet@final     * Usmet@tStrict} us     */
 se      _synEVENT_TYPESc=c              "BEFORE_SUBMIT": "besEreSrimit"      Dom     "SUBMIT": "srimit"      Dom     "MANUAL_SUBMIT": "manuthSrimit"      Dom     "ASYNC_SUBMIT": "asyncSrimit"      Dom     "FORM_SUBMIT": "(-rmSrimit"      Dom     "CANCEL": "c's dl"                 /**
        * UsetConce's cvepEve

 the id oDialog'siuturation property onies     * Usetharty on tDEFAULT_CONFIG     * Usetharivven     * Uset@final     * Uset@tStrict} us     */
 e      _synDEFAULT_CONFIGc=c       Dom     "POST_METHOD": {                  keync"postod des",                  e fro: "async"                     /**
    "POST_DATA" nc                  keync"postdata"      Dom         e fro: ;

                      /**
    "BUTTONS"nc                  keync"buttons"      Dom         e fro: unone"u                 sclassedey: [uble") &&]                     /**
    "HIDEAFTERSUBMIT" nc                  keync"hvenafr(rsrimit"      Dom         e fro: erth                         ;              ypConce's cvepEve

 the id oult "mowCSS s.des tsnd the aoDialog     ypharty on tO.widget.OverDialog.CSS_DIALOG     yphsdn pc     yphfinal     et@tString
        e      Dialog.CSS_DIALOG =cuyui-dialog"       tion () {ved foButtont = YHer fors
             overaButtonslis._onO_aButtons      Dom     nButtons      Dom     oButton              !       Dom !oveLd;
.isy) {
(aButtons
                nButtonslisaButtonsgth; i <              !ovenButtonsl>e
                    it=tnButtonsl- 1                  do                   o = lButtonnisaButtons[i]                   o = !oveO.utilet.OverButton!&& lButtonn nce's dhe O.utilet.OverButton                    if (if (lButtontdeyed.y
                                              if (isInoButtonttagNof .toUCoo Casn  ve=toBUTTON"                    if (((((E
   neurgetnts to(oButton                           E
   neurgetnts to(oButton,es,
                 }
  ((((                        }
          whint (i--
                    /**
      }
     O.widgexoond(Dialogl O.widget.OverPanel, {       /**
        * Usetharty on t(-rm     * Usethdeycrip (p_ict} us rnfeve ce m  manaDialog'si     * Uset<code>&#60;(-rm&#62;</code>nent;

 . * @t* @pedhult "mow;

 i     * Uset@tStri<a hrnf="http://www.w3.org/TR/2000/WD-DOM-Lev
l-1-20000929/     * Usetlev
l-one-html.html#ID-40002357">ElemF-rmtnts to</a>     * @me      _syn(-rm: ;

 ,          /**
        * UsetInitializdcuevens.des'siuturationbleperty onies h blurcanrberged;
 ei     * Usettsthe id oDialog'siig  Th ht lite(cfg)      * @method dest tt Dlt "moig  Th     * @me      _syn tt Dlt "moig  Thnction () {
                Dialog.rclass.dest tt Dlt "moig  Thl(this, sh)               /           * Thetstriie er_thiyugaie r tediuallback ht litethe own  a d even     * @ms   ypConnen () {.Domin .tstri(-rmre he evenuallback ht lite "s         s   yps iilverm  Connen () {Managan'siuallback ht liteohe  "s         s   yps ipiyups c    
rouge )  Connen () {Managan  the evenasyncs         s   yprequeye  "smadn.         * Thetharty on tuallback         * ThethtStrict} us     */
 s @me      _synnnnntcfg.gallback =c       Dom         /           * Th* Thetstrition () {e  execute up) {success of even     * @ms   s   ypConnen () {srimiss() {
 the even(-rmvdoesspro     * @ms   s   yputu r tsaufint inpufhent;

 ).         * Thhhhh*n     * @ms   s   ypharty on tuallback.success     * @ms   s   yphtStriFion ()      * @ms   s   y      _syn        success: ;

 ,      Dom         /           * Th* Thetstrition () {e  execute up) {fail thghe even     * @ms   s   ypConnen () {srimiss()      * @ms   s   ypharty on tuallback.fail th     * @ms   s   yphtStriFion ()      * @ms   s   y      _syn        fail th: ;

 ,      Dom         /           * Th* The<p> * @t* @pt Th* Thetstrition () {e  execute up) {success of even     * @ms   s   ypConnen () {srimiss() ,  the even(-rmvutu r ts     * @ms   s   ypaufint inpufhent;

 .         * Thhhhh*n</p> * @t* @pt Thhhhh*n<p> * @t* @pt Thhhhh*n<em>NOTE:</em>pConnen () {managan  add pro * @t* @pt Thhhhh*ninvoka "dnasuccess or fail thgler forsrthe manafint * @t* @pt Thhhhh*nupload own ctwn.dTdith add behevencnly uallback         * Thhhhh*nler for invokad.         * Thhhhh*n</p> * @t* @pt Thhhhh*n<p> * @t* @pt Thhhhh*nFhe mEre in(-rmre() , seeheven<a hrnf="http://dev
lty o.yahoo.com/yui/connen () /#fint"> * @t* @pt Thhhhh*nConnen () {Managan dent.bon prophnufint uploads</a>.         * Thhhhh*n</p> * @t* @pt Thhhhh*nharty on tuallback.upload     * @ms   s   yphtStriFion ()      * @ms   s   y       Dom         /           * Th* Thetstriarbitrarysaents.
 ahe aents.
   he pdes m  manaConnen () {     * @ms   s   ypuallback tion () s     * @ms   s   ypharty on tuallback.aents.
      * @ms   s   yphtStrict} us     */
 s @ms   y      */
 s @ms   aents.
 : ;

                               // Addn(-rmvdialogcuturatperty onies /      */
 s @m/           * Thetstriod destto own the postthe id oDialog'si(-rm. Pose") &ue fross         s   yp ref"async", "(-rm"d the "manuth".         * Thethuturatpeostod des         * ThethtString
        * @r* Thethult "mowasync     */
 s @me      _synnnnntcfg.gfgSlaserty("focDEFAULT_CONFIG.POST_METHOD.ke ,                   ler for: .elemgturatPostMd des,      Dom         e fro: DEFAULT_CONFIG.POST_METHOD.e: th y     Dom         e fidatonnction () {
val                        isInfalp!= "(-rm"!&& falp!= "async" && falp!= "none" &&                          falp!= "manuth"                    if (((((rn {St e,
            }
  }
       u if (                  o =     rn {St   on          }
  }
                              }
       )               /           * ThetAnysaddi (p_th eostpdata h blurneedthhe emosbod  the tsthe id o         * Thet<a hrnf="#gturat_postod des">async</a>apostod desuthe dialogcPOSTysrimiss() s.         * Thetstri(-rmre the manaeostpdata ng} Thoithdafined by Connen () {Managan'si         * Thet<a hrnf="O.util.Dom,oonnen .html#od des_asyncRequeye">asyncRequeye</a>a         * Thetod des.         * Thethuturatpeostdata         * ThethtString
        * @r* Thethult "mow;

              e      _synnnnntcfg.gfgSlaserty("focDEFAULT_CONFIG.POST_DATA.ke ,                   e fro: DEFAULT_CONFIG.POST_DATA.e fro     }
       )               /           * ThetTditherty on t ectsnd he gguratioa  tht
  aor provid o         * Thetdialogcld be ubstyutomre(cthiyuhiddy a fr(r arimit.         * Thet         * Thethuturatphvenafr(rsrimit         * ThethtStriBan} tr     * @r* Thethult "mowerth             e      _synnnnntcfg.gfgSlaserty("focDEFAULT_CONFIG.HIDEAFTERSUBMIT.ke ,                   e fro: DEFAULT_CONFIG.HIDEAFTERSUBMIT.e fro     }
       )               /           * ThetAnof the ht literal reph, ealurctu r tthe atao the erty onies          * Thetdafinthe atbuttonhhe emoyCoendada nhe comoDialog'si(-otysl         * The         * Thet<p>Ealurbuttonhht lite ne)tributtonsoanof tcanrhavehevreeperty onies:</p> * @t* @pt Thet<dl> * @t* @pt Thetttt<dt> tri:</dt> * @t* @pt Thetttt<dd> * @t* @pt Thetttttttstri tripfore  add mispto  oe even(ace of evenbutton.tstri tripcanr * @t* @pt Thettttttt ncluderElem, as lo;
 as inh ecgompli's c a d Elem ButtonnPpecifOcaftons.tstri trip "saddadahe even(witosrElem, * @t* @pt Thetttttttohe ld be ubstescaped by even ibnt;

 or isIgomohe  the anrexoor_th source.r * @t* @pt Thetttt</dd> * @t* @pt Thetttt<dt>ler for:</dt> * @t* @pt Thetttt<dd>Canrbereit
  : * @t* @pt Thetttt<ol> * @t* @pt Thettttttt<li>A rnfeve ce m  aition () {eore ld be utile  the even * @t* @pt Thetttttttbuttonh ecglickad.  (In mais ctwn ncty.rcf ev "stion () { "s         s   ypttttttolways itspDialogc nce's d.)</li>         * The         * Thettttttt<li>Aopht literal reprvepEve

 the id ocodehhe emo         * Thetttttttexecutedn the evenbuttonh ecglickad.         * Thettttttt         * Thettttttt<p>F-rmre:</p> * @t* @pt The         * Thettttttt<p>         * Thettttttt<code>{         * Thettttttt<br>         * Thettttttt<yed.ng>fn:</yed.ng>iFion () , &#47;&#47; * @t* @pt Thetttttttstriler for he gthlh the  id oe = YAtiles.         * Thettttttt<br>         * Thettttttt<yed.ng>ht :</yed.ng>ict} us, &#47;&#47;t         * ThetttttttAoppht litehe pdes back he evenler for.         * Thettttttt<br>         * Thettttttt<yed.ng>ncty.:</yed.ng>ict} us &#47;&#47;t         * ThetttttttTvenct.
  tto own the "dnascty.rcf evenler for.         * Thettttttt<br>         * Thettttttt}</code>         * Thettttttt</p> * @t* @pt Thettttttt</li>         * Thettttt</ol> * @t* @pt Thettttt</dd> * @t* @pt Thettttt<dt>isDlt "mo:</dt> * @t* @pt Thettttt<dd> * @t* @pt ThettttttttAopop (p_th ban} true fromeore lpecifOvs fore  nbuttonh * @t* @pt Thettttttttld be ubstEvghl(connd ahe fentsed by ult "mo.         * Thettttt</dd> * @t* @pt Thet</dl> * @t* @pt The * @t* @pt Thet<em>NOTE:</em>Ie evenYUIpButtonnWt.Overisa ncluded he manapageue * @t* @pt Thet)tributtonsogeEvendu add beh nce's dsphe O.widget.OverButton.r * @t* @pt ThetOt
  wism, Elem Buttons (<code>&#60;BUTTON&#62;</code>)u add beh * @t* @pt ThetgeEvend.     * @pt The * @t* @pt Thethuturatpbuttons     * @pt ThethtStriry) {
|ng} The     * @r* Thethult "mow"none"             e      _synnnnntcfg.gfgSlaserty("focDEFAULT_CONFIG.BUTTONS.ke ,                   ler for: .elemgturatButtons      Dom         e fro: DEFAULT_CONFIG.BUTTONS.e: th      Dom         sclassedey : DEFAULT_CONFIG.BUTTONS.sclassedey     }
       )        /**
        /**
        * UsetInitializdcuevensmEvenut hansrthe Dialogch blur reftilee      * Usetyutomre(cthiyure  pertyri   ftimdcuby evenDialogcs.dest     * Usethod dest tt Ev

 s     * Use      _syn tt Ev

 snction () {
                Dialog.rclass.dest tt Ev

 sl(this, sh)               overSIGNATURE = omEvent = Y.LIS                /           * ThetomEvent = YAtilee prihe m  srimiss()      * @ms   yp@e = YAbesEreSrimitt = Y             e       _synnnnntcfg.besEreSrimitt = Y =      Dom         .elemgeEvent("bluEVENT_TYPES.BEFORE_SUBMIT)              tcfg.besEreSrimitt = Y.signatioa =rSIGNATURE                           /           * ThetomEvent = YAtilee  fr(r arimiss()      * @ms   yp@e = YAsrimitt = Y             e              tcfg.srimitt = Y = .elemgeEvent("bluEVENT_TYPES.SUBMIT)              tcfg.srimitt = Y.signatioa =rSIGNATURE                       /           * ThetomEvent = YAtilee for manuth{srimiss() , besEre Ove g(o.eic arimit e = YA "stilee     * @ms   yp@e = YAmanuthSrimitt = Y             e              tcfg.manuthSrimitt = Y =      Dom         .elemgeEvent("bluEVENT_TYPES.MANUAL_SUBMIT)              tcfg.manuthSrimitt = Y.signatioa =rSIGNATURE               /           * ThetomEvent = YAtilee  fr(r async
ronous srimiss() , besEre Ove g(o.eic arimit e = YA "stilee     * @ms   y     * @ms   yp@e = YAasyncSrimitt = Y             epha {Overct} userconn striutunen () {ot} us, rn {Sted by O.util.Dom,oonnen .asyncRequeye             e              tcfg.asyncSrimitt = Y = .elemgeEvent("bluEVENT_TYPES.ASYNC_SUBMIT)              tcfg.asyncSrimitt = Y.signatioa =rSIGNATURE               /           * ThetomEvent = YAtilee  fr(r (-rm-basnd srimiss() , besEre Ove g(o.eic arimit e = YA "stilee     * @ms   yp@e = YA(-rmSrimitt = Y             e              tcfg.(-rmSrimitt = Y = .elemgeEvent("bluEVENT_TYPES.FORM_SUBMIT)              tcfg.(-rmSrimitt = Y.signatioa =rSIGNATURE               /           * ThetomEvent = YAtilee  fr(r c's dl     * @ms   yp@e = YAc's dlt = Y             e              tcfg.c's dlt = Y = .elemgeEvent("bluEVENT_TYPES.CANCEL       }
       cfg.a's dlt = Y.signatioa =rSIGNATURE                          },

     /**
        * UsetTd oDialogc nitializn propod des, h blurithexecutednthe Dialogcahe      * Usetydd he its sris.deses.dTdithod dest "sautomre(cthiyu(thlediby even     * Usetag ng}uctord the  levcuuptydd (witrnfeve cesrthe pre-existthe markuOue     * Usetyhe geEvensvrequiled markuOcie it  "sprovady has pEve

 t     * Uset     * Usethod dest tt      * Usetha {Overng} The he element;

 cIDovepEve

 the id oDialogc<em>OR</em>     * Usetha {OverElement} el The element;

 cvepEve

 the id oDialog     * Usetha {Overct} usertsnrig  Th striuturation propht literal repr     * Usetag  r tthe ttriuturation propfore ld be ubstao tthe mais Dialog.      * UsetSeriuturation propdent.bodn propthe mEre de r ls.         e           tt :ction () {
el  tsnrig  Th                 /      Dom          Notomeore wmpmerelypdes mhertsnr ggurate ne
  e yo tbecaown      Dom          wencnly w's cit executednos d,rre evenloweye sris.destlev
l             e               Dialog.rclass.dest tt l(this, sh, el/*  tsnrig  Th*/)        /**




tcfg.besEreItt Ev

 .tilecDialog)               getSlass(acti.element;

 arDialog.CSS_DIALOG        }
       cfg.applsoperty("focuble") &&,es,
          }
      isIntsnrig  Th            }
       cfg.applyColyig  Thntsnrig  Thly);
            }
             }
  //._onOchowt = Ymaribe(mgr.tcfg.(-smEF tso, , sh, );
            }
  ._onObesEreHvent = Ymaribe(mgr.tcfg.blurButtons  , sh, );
                 tcfg.sribe(mgr."ged;
 Bod ", tcfg.vegner(rF-rm                tcfg.itt Ev

 .tilecDialog)      }
          /**
        * UsetSrimits id oDialog'si(-rmndeoendthe he manae fromof even     * @m*c"postod des"iuturation property ony.tt<yed.ng>Pletwn proe:     * @m*c</yed.ng>iAsphe vers() {2.3 Ovithod dest add yutomre(cthiyuher fos         * deyncronous fint uploads ld be utd oDialogc nce's d'si(-rmnutu r ts     * @m*c<code>&#60;inpufhtStr="fint"&#62;</code>nent;

 s.dpIe aoDialogy         * ince's dm add behher fthe aeyncronous fint uploads, its      * @m*c<code>uallback</code>nerty on t add peadahe bstsopuOc a d a      * @m*c<code>upload</code>nler for on h.etevant"dnase'sdard      * @m*c<code>success</code>n'sd yor <code>fail th</code>nler fors.dpFhe mEre          * in(-rmre() , seeheven<a hrnf="http://dev
lty o.yahoo.com/yui/     * Usetag nen () /#fint">Connen () {Managan dent.bon prophnufint uploads</a>.         ethod destdoSrimit         *      _syndoSrimitnction () {
                 overConnen A=iO.util.Dom,oonnen       Dom         oF-rmve tcfg.(-rm      Dom         bUseFintUpload ves,
        Dom         bUseSegureFintUpload ves,
        Dom         aent} els      Dom         nent} els      Dom         i      Dom         (-rmAttr            if (s a luretcfg.getProperty("focupostod des")                     ctwn "async":                     aent} elshisoF-rmment;

                        nent} elsnisaent} elsgth; i <                      iovenent} elsn>e
                            it=tnent} elsn- 1                          do                   o =         ioveaent} els[i].tStrie=tofint" (                  o =     ((((((((bUseFintUpload ve  on          }
  }
          ((((((((by hk          }
  }
          ((((                      ((((                      ((((whint(i--
                  ((((                       iovebUseFintUpload && O.utilenv.ua.iem&& ._onOisSegure (                  o =     bUseSegureFintUpload ve  on          }
  }
                 }
  }
      (-rmAttr lis._onO_ropF-rmAttributes(oF-rm                        oonnen .sopF-rm(oF-rm,(bUseFintUpload, bUseSegureFintUpload                        overpostData = tcfg.getProperty("focupostdata"
                  ((((overc = oonnen .asyncRequeye((-rmAttr .od des, (-rmAttr .an () , tcfg.gallback,rpostData                        tcfg.asyncSrimitt = Y.tilecc                        by hk                   ctwn "(-rm":                     oF-rmmarimit
                       tcfg.(-rmSrimitt = Y.tilec
                      by hk                   ctwn "none":                 ctwn "manuth":                     tcfg.manuthSrimitt = Y.tilec
                      by hk                    /**
        /**
        * UssetRetriefocuimport's cattributes (curly fiyuod destyhe an () )  the     * Usseteven(-rmvent;

 araccou
 the the anyment;

 s h blurmayuhavehevnasof p of p     * Ussetas id oattributes. Dlt "mothhe "POST" the ""pthe md destyhe an () cvelpectiv l      * Ussedif id oattributeAc'sprovemovetriefods         *      * Ussedhod find_ropF-rmAttributes     * Ussedhatted
         */
typha {OverElemF-rmtnts to} oF-rmvstriElem F-rmvent;

   the h blureoovetriefo id oattributes      */
typhrn {St {ct} userOt literal rep,c a d md destyhe an () cng} Thoerty onies. * @t* @pte      _syn_ropF-rmAttributes nction () (oF-rm               overattr lis                  md dest: ;

 ,                 an () c: ;

                          if (isInoF-rm                    !oveoF-rmmropAttribute.rem                        overan () cisoF-rmmropAttribute.rem("an () "
                  ((((overmd destisoF-rmmropAttribute.rem("od des")<                      iovean () )                   o =     attr .an () nisan () .e: th                  ((((                       ioveod des)                   o =     attr .md destisod des.e: th                  ((((                    u if (                  o = attr .an () nisoF-rmmropAttribute("an () "
                  ((((attr .md destisoF-rmmropAttribute("od des")<             ((((          ((((               attr .md destiseLd;
.isng} Th(attr .md des) ? attr .md dest: "POST").toUCoo Casn  ;             attr .an () nisLd;
.isng} Th(attr .an () ) ? attr .an () n: ""           }
  rn {St attr       ((((        /**
        * UsetPvepares id oDialog'siie er_th FORM{ot} us, geEvethe he (isIhe (is     * Usetprovcurly fiyupEve

 t     * Usethod destvegner(rF-rm     * Use      _synvegner(rF-rm: tion (p_
                 overf-rmve tcfg.ent;

 .ropent} elsByTagNof ("(-rm")[0]               !ovetcfg.f-rm                    !ovetcfg.f-rmie=tf-rmi&& getSisA cestori.element;

 artcfg.f-rm )                   if (rn {St          }
       u if (                  o = E
   neurgetnts to(tcfg.f-rm                   o = tcfg.f-rmie ;

 <             ((((          ((((               !ove!f-rm                    f-rmie ment.bodygeEventnts to("(-rm")<             (((((-rm.nof t=cufrm_" +t;

 .ie              o = tcfg.bod lyCoendd(thisf-rm                              !ovef-rm                    tcfg.f-rmie f-rm              o = E
   np_
(-rm  "srimit" s._onO_srimitHer for, , sh, );
            }
        /**
        /**
        * UssetIe er_th ler for the manaf-rmiarimit e = Y         *      * Ussedhod find_srimitHer for     * Ussedhatted
         */
typha {OverDOME
    u tTd oDOMrt = YAot} us     */
 se      _syn_srimitHer for nction () (m                t = Ymatopt("blu            }
  ._onOarimit
               tcfg.f-rm.blur()      }
          /**
        * Us etSevcuupty tab, shift-tab loopvemtwehe even( tsotyhe lastoent;

 s     * Us etbro vend. NOTE:dSevcuuptmanapre = YBackTab yhe pre = YTabOut KeyLner(o.e     * Ussedince's dmerty onies,ch blur refEve
t e =rytimd Ovithod destisa nvokad.         *      * Ussedhod findso TabLoty         setha {OverElement} el T( tsoEnt;

  * @t* @ptetha {OverElement} el TlastEnt;

  * @t* @pte     */
 se      _synso TabLoty nction () (( tsoEnt;

 ,TlastEnt;

                  ( tsoEnt;

 ie f tsoEnt;

 i|| .findf tsoButton              lastEnt;

 ie lastEnt;

 i|| .findlastButton               Dialog.rclass.destso TabLotyl(this, sh, ( tsoEnt;

 ,TlastEnt;

        }
          /**
        * Us etPtted
   iie er_th od desuthe so TabLoty, h blurcanrbertsed by      * Us etsris.desesreoojump  tsahe modify evenaents.
   ps c    ts!ovrequileds         *      * Ussedhod find_so TabLoty         setha {OverElement} el T( tsoEnt;

  * @t* @ptetha {OverElement} el TlastEnt;

  * @t* @ptedhatted
         */
ty      _syn_so TabLoty nction () (( tsoEnt;

 ,TlastEnt;

                 ( tsoEnt;

 ie f tsoEnt;

 i|| .findf tsoButton              lastEnt;

 ie .findlastButtoni|| lastEnt;

                tcfg.so TabLoty(( tsoEnt;

 ,TlastEnt;

        }
          /**
        * Us etCguratioasdince's dmerty onies,cpoi
 the io even * @t* @pt*n( tsotyhe lasto(-smEnblepent;

 s  ne)triDialog'si(-rm.         *      * Ussedhod findso F tsoLastF-smEnble     */
 se      _synso F tsoLastF-smEnble : tion (p_
                 Dialog.rclass.destso F tsoLastF-smEnblel(this, sh)               overi,Tl, el  ent;

 s e tcfg.(-smEnbleent} els               tcfg.( tsoF-rmtnts toie ;

 <             .findlastF-rmtnts toie ;

 <              !ovetcfg.f-rmi&& ent;

 s && ent;

 sgth; i n>e
                    lie ent;

 sgth; i <                  the (it=t0; i < l; ++i)                   if (elie ent;

 s[i]                  if (!ovetcfg.f-rmie=e en.f-rm                            tcfg.( tsoF-rmtnts toie e <             ((((((((((((by hk          }
  }
                                           the (it=tl-1; i >=t0; --i)                   if (elie ent;

 s[i]                  if (!ovetcfg.f-rmie=e en.f-rm                            tcfg.lastF-rmtnts toie e <             ((((((((((((by hk          }
  }
                                                        /**
 / BEGIN BUILT-IN PROPERTYnEVENT HANDLERS /      */
         * UsetTd oult "mowt handler forAtilee  the even"glof "herty on t ec     * Usetged;
 e.tstriod destutu rols id oapoendthe hr Evdthe he evenulof      * Usetiuturre eventop r(conhhe evenDialogt     * Usethod destuturatClof      * Usetha {Overng} The tStristriomEvent = YAtStri(usuthiytmanaprty on tnof )     * Usetha {OverOt lit[]}naensistriomEvent = YAaents.
  .pFhe      * Usetgturation propler forsueuens[0]t add equaltmananewiyuyColOvere from     * Usetthe manaerty ony.     * Usetha {OverOt lit}Aot} strincty.rct.
   pFhe gturation propler forsue     * Usettdith add usuthiytequaltmanaownor.         e      _synuturatClof nction () {
tStrueuens,rct.                Dialog.rclass.destuturatClof lyColys, sh, yents.
  )                  /**
        * Us etE handler forAthe manaulof tiutu     * Us et     * Ussedhod find_doClof      * Ussedhatted
         */
typ      */
typha {OverDOME
    u      */
 se      _synd_doClof  nction () (m                t = Ympre = YDlt "mou            }
  ._onOa's dl()      }
          /**
        * UsetTd oult "mowt handler forAthe mana"buttons"iuturation property ony     * Usethod destuturatButtons     * @petha {Overng} The tStristriomEvent = YAtStri(usuthiytmanaprty on tnof )     * Usetha {OverOt lit[]}naensistriomEvent = YAaents.
  .pFhe uturation prop     * Usetler forsueuens[0]t add equaltmananewiyuyColOvere fromthe manaerty ony.     * Usetha {OverOt lit}Aot} strincty.rct.
   pFhe gturation propler forsue     * Usettdith add usuthiytequaltmanaownor.         e      _synuturatButtonsnction () {
tStrueuens,rct.                 overButtonnisO.widget.OverButton                  aButtonslisuens[0]      Dom         oInnerEnt;

 g=t;

 .innerEnt;

       Dom         oButton                  oButtonE ,                 oYUIButton                  nButtons      Dom         oSpan                  oFootys      Dom         i           }
  rnd foButtont = YHer forsl(this, sh)               ._onO_aButtonsie ;

 <              !oveLd;
.isy) {
(aButtons
         Dom         oSpanie ment.bodygeEventnts to("span")<             ((((oSpan.s.desNof t=cubutton-group"              }
  nButtonslisaButtonsgth; i <                  ._onO_aButtonsie []                  ._onOult "moHtmlButtonnis;

 <                  the (it=t0; i < nButtons; i++                        lButtonnisaButtons[i]                   o = !oveButton                    if (if (lYUIButtonnis;ewrButton({ label:(lButtont tri,AtStr:lButtont Stri}                           lYUIButton.yCoendTo(oSpan)<                          lButtonElie lYUIButton.Ove("ent;

 ")<                      o = !ovelButtontisDlt "mo (                  o =     ((((lYUIButton.yass(acti"ult "mo"
                  ((((((((((((._onOult "moHtmlButtonnislButtonEl                  ((((((((                       o = !oveLd;
.isFion () (oButton.ler for
         Dom         o =     ((((lYUIButton.sve("onglick", {                                  fn:(lButtonther for,                                  ht : , sh,                                  ncty.:ttdith                              )                              if (isInLd;
.isOt lit(oButton.ler for
 && Ld;
.isFion () (oButton.ler for.fn
         Dom         o =     ((((lYUIButton.sve("onglick", {                                  fn:(lButtonther for.fn,                                  ht : ((!Ld;
.isU dafined(lButtonther for.ct. ) ? lButtonther for.ct. :ttdit),                                  ncty.:t(lButtonther for.ncty.r|| .fin)h                              )                                                      ._onO_aButtons[._onO_aButtonsgth; i ]ie lYUIButton                        u if (                           lButtonElie ment.bodygeEventnts to("button"
                  ((((((((lButtonEl.sveAttribute(" Str", "button"
                       o = !ovelButtontisDlt "mo (                  o =     ((((lButtonEl.s.desNof t=cuult "mo"                  ((((((((((((._onOult "moHtmlButtonnislButtonEl                  ((((((((                       o = lButtonEl.innerElem =(lButtont tri                       o = !oveLd;
.isFion () (oButton.ler for
                    ((((((((((((E
   np_
oButtonE ,n"glick", lButtonther for, , sh, );
            }
      ((((((((   if (isInLd;
.isOt lit(oButton.ler for
 &&                  ((((((((((((Ld;
.isFion () (oButton.ler for.fn
    ((((                 ((((((((((((E
   np_
oButtonE ,n"glick",                                  hButtonther for.fn,                                  ((!Ld;
.isU dafined(lButtonther for.ct. ) ? lButtonther for.ct. :ttdit),                                  (lButtonther for.ncty.r|| .fin)           }
      ((((((((                       o = lSpan.yCoendd(thisoButtonE            }
      ((((((((._onO_aButtons[._onO_aButtonsgth; i ]ie lButtonEl                  ((((                       lButtonthtmlButtonnislButtonEl                   o = !oveiie=e 
                            .findf tsoButtonie lButtonEl                  ((((                       !oveiie=venButtonsl- 1                             tcfg.lastButtonie lButtonEl                  ((((              ((((                   tcfg.so Footys(oSpan)<                  oFootys e tcfg.(-otys<                  !ovegetSinDent.bodi.element;

 
!&& !getSisA cestorioInnerEnt;

 , oFootys                         oInnerEnt;

 .yCoendd(thisoFootys <             ((((                   tcfg.buttonSpanie oSpan<               u if ( 
 / Do c} trup                 oSpanie tcfg.buttonSpan<             ((((oFootys e tcfg.(-otys<                 !oveoSpani&& lFootys                        oFootyslved fod(thisoSpan)<                     tcfg.buttonSpanie ;

 <             ((((    tcfg.f tsoButtonie ;

 <             ((((    tcfg.lastButtonie ;

 <             ((((    tcfg.ult "moHtmlButtonnis;

 <         ((((              ((((               ._onOaed;
 Cont

 t = Y.tilec
                  /**
        * Usethod destOveButtons     * @pethdeycrip (p_iRe {St"sanoanof tcg  r tthe ealurhe evenDialog'si     * @petbuttons  by ult "mosanoanof the Elem <code>&#60;BUTTON&#62;</code>i     * @petent;

 s.dpIe evenDialog'sibuttonsow  e geEvendutsthe id o         *sO.widget.OverButton s.des (viaseven nclusprophe evencp (p_th Buttonh * @t* @p*ndeoendencyrhe manapage),sanoanof the O.widget.OverButton  nce's dsp * @t* @p*nis rn {Sted.     * Usethrn {St {y) {
          e      _synOveButtonsnction () {
                rn {St ._onO_aButtonsi|| ;

 <                 /**
        * Us et<p>         *etSevcu(-smE io even( tsot(-smEnblepent;

   ne)triDialog'si(-rm !ovfouns,      Dom  *u if ue)triult "mosbuttonh ovfouns,  if (even( tsotbuttonhdafined viaseven     Dom  *u"buttons"iuturation property onys         * d</p> * @t* @ptet<p>         *etTdithod dest "s nvokad  the evenDialogc "smadn ble") &s         * d</p> * @t* @ptethod find(-smEF tso * @t* @ptethrn {St {Ban} tr} erth y ovfontsed.es,
  y ovpro * @t* @pte      _syn(-smEF tsonction () {
tStrueuens,rct.                 overelie tcfg.( tsoF-rmtnts to,                  fentsed = e,
                 !oveaensi&& uens[1]                    E
   natopt("bluuens[1] <                   / Wthe eabbthe 
  e, own t tsoEnt;

 i nceeadghe ( tsoF-rmtnts to                 !oveuens[0]te=e 9m&& ._onOt tsoEnt;

 )                   if (elie ._onOt tsoEnt;

 <             ((((          ((((               !oveel                    try                   if (el.(-smEc
                      fentsed =   on          }
  }
  } catch(oExcep () )                   o =  / Ignoth     * @ms   s             (((( u if (                  !ovetcfg.ult "moHtmlButton)                   o = fentsed =  cfg.(-smEDlt "moButton( <             (((( u if (                  o = fentsed =  cfg.(-smEF tsoButton( <             (((( 
        (((( 
        ((((rn {St eentsed      }
          /**
        * UsetSevcu(-smE io evenlastoent;

   ne)triDialog'si(-rm he manalasto     * @petbuttonhdafined viaseven"buttons"iuturation property onys         ethod find(-smELast     * Usethrn {St {Ban} tr} erth y ovfontsed.es,
  y ovpro * @t* @pe      _syn(-smELastnction () {
tStrueuens,rct.                 overaButtonslis._onOgetProperty("focubuttons")      Dom         elie ._onOlastF-rmtnts to      Dom         (-ntsed = e,
                 !oveaensi&& uens[1]                    E
   natopt("bluuens[1] <                   / Wthe eabbthe 
  e, own lastEnt;

 i nceeadghe lastF-rmtnts to                 !oveuens[0]te=e 9m&& ._onOlastEnt;

                         elie ._onOlastEnt;

 <             ((((          ((((               !oveaButtonsl&& Ld;
.isy) {
(aButtons
                    (-ntsed =  cfg.(-smELasoButton( <              u if (                  !oveel                        try                   if (    el.(-smEc
                          (-ntsed =   on          }
  }
        catch(oExcep () )                   o =      / Ignoth     * @ms   s                     ((((          ((((               rn {St eentsed      }
          /**
        * UssetHely("iod destto n-rmrlizdtbuttonhrnfeve ces. Itreit
   rn {Stsseven     Dom  *uYUIpButtonnince's dmthe managi("boent;

   ovfouns,     Dom  *uhe manaedesesrback htriElemEnt;

 irnfeve ce ie aocorvelpondthe YUIpButton     Dom  *urnfeve ce i"sprovfounsuhe O.widget.OverButton doesspro existrhe manapages         *      * Ussedhod find_ropButton     Dom  *u@erivven     * Usmet@a {OverElement} el Tbutton     Dom  *u@rn {St {O.widget.OverButton|Element} el  * @t* @pte      _syn_ropButton nction () (button)               overButtonnisO.widget.OverButton               // Ie weuhavehanoElem buttontyhe YUIpButtonnis he manapageue * @t* @pt Th// OverevenYUIpButtonnrnfeve ce ie availa) &s         * = !oveButtonl&& buttonl&& button.nodeNof t&& button.is)                   buttonnisButton.OveButton(button.is) || button                             rn {St button                  /**
        * UsetSevcumanaf-smE io evenbuttonneore ithdasignatee  se)triult "mosvias     * Useteven"buttons"iuturation property onys By ult "mo, Ovithod destisa     * Uset(thledi the evenDialogc "smadn ble") &s         ethod find(-smEDlt "moButton     * Usethrn {St {Ban} tr} erthy ovfontsed,es,
  y ovpro * @t* @pe      _syn(-smEDlt "moButtonnction () {
                overbuttonnis._onO_ropButton(tcfg.ult "moHtmlButton),                           (-ntsed = e,
                         * = !ovebutton)               /**
                       Place manauydd io even"(-nts"hod destinsidn a try/catch                     block he pre = Y IE  the  
rowthe JavaScrip  errors if                     tlement;

 cithdiEnbled hr Evdden.         * Thhhhh*      */
 s @ms   try                   if (button.(-smEc
                      fentsed =   on          }
  }
  } catch(oExcep () )                    
        (((( 
        ((((rn {St eentsed      }
          /**
        * UsetBlurs ydd itributtonsodafined viaseven"buttons"i     * Uset(turation property onys         ethod findblurButtons * @t* @pe      _synblurButtonsnction () {
                             overaButtonslis._onOgetProperty("focubuttons")      Dom         nButtons      Dom         oButton      Dom         otnts to      Dom         i           }
  !oveaButtonsl&& Ld;
.isy) {
(aButtons
                    nButtonslisaButtonsgth; i <     Dom         iovenButtonsl>e
                        i =venButtonsl- 1                       do                   o =     lButtonnisaButtons[i]                  o =     !ovelButton (                  o =     ((((lEnt;

 g=t;

 ._ropButton(lButtonthtmlButton
                  ((((((((((((!ovelEnt;

                                                                            Place manauydd io even"blur"hod destinsidn                                       a try/catch block he pre = Y IE  the                                        
rowthe JavaScrip  errors if tlement;

 c                                     ithdiEnbled hr Evdden.         * Thhhhhhhhhhhhhhhhhhhhh*      */
 s @ms                    ry                   if (                lEnt;

 .blur()      }
                            catch(oExcep () )                   o =                 // ignoth     * @ms   s                         * @ms   s                                     ((((                      }(whint(i--
                                                  /**
        * UsetSevcumanaf-smE io even( tsotbuttonhgeEvenduviaseven"buttons"     * Uset(turation property onys         ethod find(-smEF tsoButton     * Usethrn {St {Ban} tr} erth y ovfontsed.es,
  y ovpro * @t* @pe      _syn(-smEF tsoButtonnction () {
                 overaButtonslis._onOgetProperty("focubuttons")      Dom         oButton      Dom         otnts to      Dom         (-ntsed = e,
                 !oveaButtonsl&& Ld;
.isy) {
(aButtons
                    lButtonnisaButtons[0]                  !ovelButton (                  o = lEnt;

 g=t;

 ._ropButton(lButtonthtmlButton
                  ((((!ovelEnt;

                                                            Place manauydd io even"(-nts"hod destinsidn a                              try/catch block he pre = Y IE  the  
rowthe                              JavaScrip  errors if tlement;

 cithdiEnbled                  o =     ((((lr Evdden.         * Thhhhhhhhhhhhh*      */
 s @ms            ry                   if (        lEnt;

 .(-smEc
                              (-ntsed =   on          }
  }
            catch(oExcep () )                   o =         // ignoth     * @ms   s                                 }             ((((          ((((               rn {St eentsed      }
          /**
        * UsetSevcumanaf-smE io evenlastobuttonhgeEvenduviaseven"buttons"i     * Uset(turation property onys         ethod find(-smELasoButton     * Usethrn {St {Ban} tr} erth y ovfontsed.es,
  y ovpro * @t* @pe      _syn(-smELasoButtonnction () {
                 overaButtonslis._onOgetProperty("focubuttons")      Dom         nButtons      Dom         oButton      Dom         otnts to                   fentsed = e,
                 !oveaButtonsl&& Ld;
.isy) {
(aButtons
                    nButtonslisaButtonsgth; i <     Dom         iovenButtonsl>e
                        lButtonnisaButtons[enButtonsl- 1 ]                   o = !ovelButton (                  o =     lEnt;

 g=t;

 ._ropButton(lButtonthtmlButton
                  ((((((((!ovelEnt;

                                                                    Place manauydd io even"(-nts"hod destinsidn a                                  try/catch block he pre = Y IE  the  
rowthe                                  JavaScrip  errors if tlement;

 cithdiEnbled                                 hr Evdden.         * Thhhhhhhhhhhhhhhhhe      _syn                             try                   o =             lEnt;

 .(-smEc
                                  (-ntsed =   on          }
  }
                catch(oExcep () )                   o =              / Ignoth     * @ms   s                     * @ms   s                                 }             ((((          ((((               rn {St eentsed      }
          /**
        * UsetTd oult "mowt handler forAthe mana"postod des"iuturation property ony         ethod findgturatPostMd des         etha {Overng} The tStristriomEvent = YAtStri(usuthiytmanaprty on tnof )     * Usetha {OverOt lit[]}naensistriomEvent = YAaents.
  .pFhe      * Uset(turation propler forsueuens[0]t add equaltmananewiyuyColOvere from     * Usetthe manaerty ony.     * Usetha {OverOt lit}Aot} strincty.rct.
   pFhe gturation propler forsue     * Usettdith add usuthiytequaltmanaownor.         e      _synuturatPostMd desnction () {
tStrueuens,rct.                tcfg.vegner(rF-rmc
                  /**
 / END BUILT-IN PROPERTYnEVENT HANDLERS /      */
      /**
        * UsetBuilt-inction () {hooktthe writthe ate fidatproption () {eore  add      * Usetbnauheckad the a "  on"re fromprihe m  a arimit.tTdithfion () , asp * @t* @p*niibnt;

 ed by ult "mo,tolways rn {Stsserth ysocit ld be ubst * @t* @p*noverriddy aif e fidatpropi"specessarys         ethod finde fidate         e      _syne fidatenction () {
                rn {St . on                  /**
        * UsetExecutes a arimitrhe evenDialogaif e fidatprop * @t* @p*nis srccessfuls By ult "mo evenDialogc "sEvdden * @t* @p*n fr(r arimiss() ,obut yourcanrsvereven"hvenafr(rsrimit"     * Uset(turation property ony m  s,
    he pre = Y id oDialog     * Uset the bethe 
vdden.         et     * Usethod destsrimit         *      _synsrimitnction () {
                !ovetcfg.e fidate(
                    !ovetcfg.besEreSrimitt = Y.tilec
                        tcfg.uoSrimit
                       tcfg.srimitt = Y.tilec
                       o = !ove._onOgetProperty("focuhvenafr(rsrimit"                             tcfg.hven
                       }                      o = rn {St   on          }
  }
   u if (                  o = rn {St e,
            }
  }
            (((( u if (                  rn {St e,
            }
                    /**
        * UsetExecutes manauys dlrhe evenDialogafollowed by aphvent     * Usethod destu's dl     * @me      _synu's dlnction () {
                 cfg.a's dlt = Y.tilec
              tcfg.hven
                  },

     /**
        * UsetRe {St"sa JSON-gompn pblepdata ng}uctioa vepEve

 the id odata      * Uset(urly fiyucg  r t    tsmanaf-rmt     * Usethod destropData         yphrn {St {ct} userA JSONpht litevepEe

 the id odata of even     * @m*c(urly faf-rmt     * Use      _synOveDatanction () {
                 overoF-rmve tcfg.(-rm      Dom         aent} els      Dom         nTotalent} els      Dom         oData      Dom         sNof       Dom         otnts to      Dom         nent} els      Dom         sTStru     Dom         sTagNof       Dom         aOp () s      Dom         nOp () s      Dom         aV fros      Dom         oOp ()       Dom         oRadio      Dom         oCheckbox      Dom         e froAttr      Dom         i      Dom         n;                      tion () { "F-rmtnts to(p_lEnt;

                     oversTagve p_lEnt;

 .tagNof .toUCoo Casn  ;                 rn {St ((sTagve= "INPUT" || sTagve= "TEXTAREA" ||                          sTagve= "SELECT"
!&& p_lEnt;

 .nof t== sNof                               !oveoF-rm         Dom         aent} elshisoF-rmment;

                    nTotalent} elsnisaent} elsgth; i <                 oData = {            if (    the (it=t0; i < nTotalent} els; i++                        sNof t=caent} els[i].nof                    o =                            Usthe "getSropent} elsBy" m  safeguard tsnr  the JS                          errors eore Eve"mo  the givthe at(-rm fiee u(he so  of                          fiee s) evnasof p of pa"sa on pvriod destof at(-rm                          (like "srimit") he a DOMrcollen () {(srch  se)tri"item"                         md des). OriginthiytmrOveraccessthe tiee s viaseven     Dom                 " of dItem"iod destof )tri"ent;

 "rcollen () ,obut      Dom                 dinctveradahore ie  erelyrn {St arcollen () {he ( ee s                  ((((((((!n Gecko.         * Thhhhhhhhhe                       lEnt;

 g=tgetSropent} elsBy( "F-rmtnts to, "*", oF-rm                       nent} elsnislEnt;

 .th; i <                      iovenent} elsn>e
                            iovenent} elsn== 1 (                  o =     ((((lEnt;

 g=tlEnt;

 [0]                               sTStrnislEnt;

 .tStr          }
  }
              sTagNof nislEnt;

 .tagNof .toUCoo Casn  ;          }
  }
              s a luresTagNof )                   o =             ctwn "INPUT":                                     iovesTStrni= "checkbox" (                  o =     ((((((((((((((((oData[sNof ]nislEnt;

 .uheckad                                         if (isInsTStrn!= "radio" (                  o =     ((((((((((((((((oData[sNof ]nislEnt;

 .e: th                  ((((                                  ((((                by hk                                   ctwn "TEXTAREA":                                     oData[sNof ]nislEnt;

 .e: th                  ((((                by hk                                       ctwn "SELECT":                                     aOp () snislEnt;

 .cp (p_                                       nOp () snisaOp () sgth; i <                                     aV frosie []                                           the (nt=t0; n < nOp () s; n++                                            oOp () nisaOp () s[n]                  o =                     !oveoOp () .seld
                                                   e froAttrnislOp () .attributes.e: th                  ((((                        aV fros[aV frosgth; i ]ie (e froAttrn&& e froAttr.lpeci( es) ? lOp () .e from: lOp () . tri              ((((                                          ((((                                  ((((                oData[sNof ]nisaV fros                  ((((                by hk          ((((                                   ((((             u if (                  o =         sTStrnislEnt;

 [0].tStr          }
  }
              s a luresTStr)                   o =             ctwn "radio":                                     the (nt=t0; n < nent} els; n++                                            oRadionislEnt;

 [n]                  o =                     !oveoRadio.uheckad                                                oData[sNof ]nislRadio.e: th                  ((((                        by hk          ((((                                              ((((                                  ((((                by hk                           o =             ctwn "checkbox":                                     aV frosie []                                      the (nt=t0; n < nent} els; n++                                            oCheckboxnislEnt;

 [n]                  o =                     !oveoCheckbox.uheckad                                                aV fros[aV frosgth; i ]ie  oCheckbox.e: th                  ((((                                      ((((                                  ((((                oData[sNof ]nisaV fros                  ((((                by hk          ((((                                                                }             ((((          ((((               rn {St oData                  /**
        * UsetRed fose)triPanelvent;

   the td oDOMryhe levcuydd c(thi ent;

 s      * Usetto null.         ethod destdeyed.y         etha {Overban} tr} shallowPurgepIe erth ycnly manapaly faent;

 'soDOMre = Y lner(o.esr refeurged.pIe s,
    he pro bro vend,uydd c(thily r ref,
 ofeurged{he DOMre = Y lner(o.es.      * UsetNOTE:dstriflagc "sa "shallowPurge"iflag, aspopposadahe wore mayubn a mEre intui pvri"eurgeC(thily "iflagahe maiu r tsbackwar s gompn pbilin t athubnhavihe prihe m  2.9.0t     * Use      _syndeyed.ynction () {
shallowPurge                rnd foButtont = YHer forsl(this, sh)               ._onO_aButtonsie ;

 <              overaF-rmslis._onOent;

 .ropent} elsByTagNof ("(-rm")      Dom         oF-rm               !oveaF-rmsgth; i n>e
                    oF-rmve aF-rms[0]                   isInoF-rm                        E
   neurgetnts to(oF-rm                       !oveoF-rmmpaly f.rem                            oF-rmmpaly f.remlved fod(thisoF-rm                                             tcfg.f-rmie ;

 <             ((((          ((((          ((((Dialog.rclass.destdeyed.yl(this, sh, shallowPurge                   /**
        * UsetRe {St"sa sg} ThovepEve

 a () {he manaot.
            ethod desttong} Th         ethrn {St {ng} The string} ThovepEve

 a () {he manaDialog     * Use      _syntong} Thnction () {
                rn {St "Dialoga" +t;

 .ie          }           )   }()   (tion () {
                 * SiibntDialogc "sa siibntniibnt;

 a () {he Dialogchore canrbertsed m       * arimitra singlnae fro pFhemslcanrberbrocess    ts3 ways -- viasan      * asynchronous Connen () {.Domin t(thi,sa siibntnf-rmiPOST he GET,      * he manuthlys     eth of space O.widget.Ove     eths.des SiibntDialog     ethtrien s O.widget.OverDialog     ethconsg}uctor     etha {Overng} The elvTlement;

 cID vepEve

 the id oSiibntDialogc     et<em>OR</em>     etha {OverElement} el TelvTlement;

 cvepEve

 the id oSiibntDialog     etha {Overct} usertsnrig  ThvTlem(turation propot literal reptcg  r tthe      ettlem(turation prophore ld be ubstso  the mais SiibntDialog. See      et(turation propment.bodatpropthe mEre de r ls. * @te      O.widget.OverSiibntDialogc=ction () {
el  tsnrig  Th    ((((         O.widget.OverSiibntDialog.rclass.destutusg}uctorl(this, sh,              el  tsnrig  Th                    overDom =iO.util.Dom,Dom      Dom SiibntDialogc=cO.widget.OverSiibntDialog, ((((                 * UsetConce's cvepEve

 the id oSiibntDialog'st(turation property onies     * @petharty ony DEFAULT_CONFIG     * @petharivven     * Usethfinth     * UsethtStriOt} us     */
 e      _synDEFAULT_CONFIG is                       "ICON": {                  keync"iutu",                  e fro: "none",                  rclpEvesE
   : erthy                     },

     /**
    "TEXT": {                  keync" tri",                  e fro: "",                  rclpEvesE
   : erth,                  rclassedey: ["iutu"]                    },

     /**
                * Conce's cthe manace'sdard networktiuturthe a blockthe an ()      etharty ony O.widget.OverSiibntDialog.ICON_BLOCK     ethsdatpc     ethfinth     ethtString} Th     e      SiibntDialog.ICON_BLOCKt=cublcktutu"                   * Conce's cthe manace'sdard networktiuturthe alarm     etharty ony O.widget.OverSiibntDialog.ICON_ALARM     ethsdatpc     ethfinth     ethtString} Th     e      SiibntDialog.ICON_ALARMt=cualoniutu"                   * Conce's cthe manace'sdard networktiuturthe help     etharty ony O.widget.OverSiibntDialog.ICON_HELP     ethsdatpc     ethfinth     ethtString} Th     e      SiibntDialog.ICON_HELP t=cuhlpiutu"                   * Conce's cthe manace'sdard networktiuturthe info     etharty ony O.widget.OverSiibntDialog.ICON_INFO     ethsdatpc     ethfinth     ethtString} Th     e      SiibntDialog.ICON_INFO t=cuinfoiutu"                   * Conce's cthe manace'sdard networktiuturthe war      etharty ony O.widget.OverSiibntDialog.ICON_WARN     ethsdatpc     ethfinth     ethtString} Th     e      SiibntDialog.ICON_WARN t=cuwar tutu"                   * Conce's cthe manace'sdard networktiuturthe a tip     etharty ony O.widget.OverSiibntDialog.ICON_TIP     ethsdatpc     ethfinth     ethtString} Th     e      SiibntDialog.ICON_TIP  t=cutiptutu"               * Conce's cvepEve

 the id o of phe manaCSS s.des yColOverio evenent;

 c     *hgeEvendubye)tri"iutu"t(turation property onys     etharty ony O.widget.OverSiibntDialog.ICON_CSS_CLASSNAME     ethsdatpc     ethfinth     ethtString} Th     e      SiibntDialog.ICON_CSS_CLASSNAMEt=cuyui-tutu"                   * Conce's cvepEve

 the id odlt "mo CSS s.des tsed the a SiibntDialog     etharty ony O.widget.OverSiibntDialog.CSS_SIMPLEDIALOG     ethsdatpc     ethfinth     ethtString} Th     e      SiibntDialog.CSS_SIMPLEDIALOGt=cuyui-siibnt-dialog"            O.widgtrien (SiibntDialog, O.widget.OverDialog,   ((((                 * UsetInitirlizds manau.des'st(turationbleperty onies h blurcanrberged;
 e      * Usettsthe id oSiibntDialog'stig  Thvot lite(cfg)          ethod dest ttYDlt "moig  Th     */
 e      _syn ttYDlt "moig  Thnction () {
                         SiibntDialog.rclass.dest ttYDlt "moig  Thl(this, sh)                       // Add dialogt(turatperty onies /      */
      /**
            * Us    etSevcumanainforma (p_th iuturthe id oSiibntDialog             eth(turatpiutu     * Us    ethtString} Th     * Us    ethdlt "mo "none"     * Us    e      */
 s @m._onOgetPadderty("focDEFAULT_CONFIG.ICON.key,                   ler for:m._onOgturatIc)       Dom         e fro: DEFAULT_CONFIG.ICON.e frou     Dom         sclpEvesE
   : DEFAULT_CONFIG.ICON.sclpEvesE
                 )                       /       * Us    etSevcumana trirthe id oSiibntDialog.tstri trir "s nsnr   iie o td oDOMrysoElem,ryhe ld be ubstescapndubye)triiibnt;

 he if gomthe tthe anrtrier_th sousse.             eth(turatp tri     * Us    ethtStriElem     * Us    ethdlt "mo ""     * Us    e      */
 s @m._onOgetPadderty("focDEFAULT_CONFIG.TEXT.key,                    ler for:m._onOgturatTtri,A     Dom         e fro: DEFAULT_CONFIG.TEXT.e frou                  rclpEvesE
   : DEFAULT_CONFIG.TEXT.rclpEvesE
   ,                  rclassedey: DEFAULT_CONFIG.TEXT.rclassedey               )                          },

     /**
                 * UsetTd oSiibntDialogcinitirlizn propod des, h blur "sexecuted the      * UsetSiibntDialogcyhe ydd he itstsris.deses.tTdithod dest "sautoma (cthiyt     * Uset(thledibye)triutusg}uctor,ryhe  levcuuptydd DOMrrnfeve ces the      * UsetpEv-existthe markup,ryhe geEvensvrequiled markup if it i"sprov     * Usetaloeady pEve

           ethod dest ttY         etha {Overng} The elvTlement;

 cID vepEve

 the id oSiibntDialogc         et<em>OR</em>         etha {OverElement} el TelvTlement;

 cvepEve

 the id oSiibntDialog         etha {Overct} usertsnrig  ThvTlem(turation propot literal rept     * Uset(g  r tthe tlem(turation prophore ld be ubstso  the mais      * UsetSiibntDialog. See (turation propment.bodatpropthe mEre de r ls. * @t* Use      _syn ttY:ction () {
el  tsnrig  Th                 /                  Note{eore  e derelyedes tlemtsnr (turatpin 
  e yo  becaown wen     Dom         cnly w's cit executed o ce,rre evenlowesttsris.des levdl     * @m* Use               SiibntDialog.rclass.dest ttYl(this, sh, el/*  tsnrig  Th*/)                       tcfg.besEreInitt = Y.tilecSiibntDialog)                       getSyass(acti._onOent;

 , SiibntDialog.CSS_SIMPLEDIALOG)                       tcfg.getPqueueerty("focupostod des", "manuth")                       !ovetsnrig  Th    ((((            tcfg.getPyColyig  Thetsnrig  Th, );
            }
                     ((((tcfg.besEreRenderE
   naubycribe(tion () {
                    !ove!(tcfg.body                        tcfg.so Body(""
                            (((( , , sh, );
                     ((((tcfg.initt = Y.tilecSiibntDialog)                          },

     /**
        * UsetPvepards manaSiibntDialog'stie er_th FORMpot lit, geEvethe hn (isIhn (     * Useti"sprov(urly fiyupEve

 ,ryhe yasthe tleme from
vdden ( ee           ethod destvegner(rF-rm * @t* Use      _synvegner(rF-rmnction () {
                SiibntDialog.rclass.destvegner(rF-rml(this, sh)               overmenve tcfg.(-rm.ownorDent.bod      Dom         inputie menygeEventnts to("input"
               inputt Stri=n"hveden"              inputtnof t=t;

 .ie              inputte from=n""               ._onO(-rm.yCoendd(thisinput
                  /**
 / BEGIN BUILT-IN PROPERTYnEVENT HANDLERS /      */
      /**
        * UsetFilee  the even"iutu"terty on t ecso t     * Usethod destuturatIcon     * Usetha {Overng} The tStristriomEvent = YAtStri(usuthiytmanaprty on tnof )     * Usetha {OverOt lit[]}naensistriomEvent = YAaents.
  .pFhe uturation prop     * Usetler forsueuens[0]t add equaltmananewiyuyColOvere fromthe manaerty ony.     * Usetha {OverOt lit}Aot} strincty.rct.
   pFhe gturation propler forsue     * Usettdith add usuthiytequaltmanaownor.         e      _synuturatIconnction () {
tStruuens,ct.                         oversIconlisuens[0]      Dom         oBodyie tcfg.bodyu     Dom         sCSSs(actie SiibntDialog.ICON_CSS_CLASSNAME,
				aent} els      Dom         oIc)       Dom         oIc) Paly f                       !ovesIconl&&rsIconl!= "none"         Dom         aent} elshisgetSropent} elsByC.desNof (sCSSs(act, "*" , oBody
   				!oveaent} elsgth; i te=e 1 (   					oIconlisuent} els[0]                      oIc) Paly fnislIc) mpaly f.rem                   o = !ovelIc) Paly f         Dom         o =     lIc) Paly flved fod(thisoIc) )<                          lIconlis;

 <                      }  				}                   !ovesIcon.indexOf("."  =e -1         Dom         o = lIconlisment.bodygeEventnts to("span")<             ((((o = lIcon.s.desNof t=c(sCSSs(act + "a" +tsIc) )<             ((((o = lIcon.innerElem =("&#160;"                   }u if (                       lIconlisment.bodygeEventnts to("img")<             ((((o = lIcon.srnve i._onOimageRorov+tsIc) )<             ((((o = lIcon.s.desNof t=csCSSs(act                   }                                   isInoIc) )                                ((((o = lBody. nsnr BesErenoIc) , oBodyOt tsod(thi)<             ((((                 } 
            } 
                /**
        * UsetFilee  the even" tri"terty on t ecso t     * Usethod destuturatTtri     * Usetha {Overng} The tStristriomEvent = YAtStri(usuthiytmanaprty on tnof )     * Usetha {OverOt lit[]}naensistriomEvent = YAaents.
  .pFhe uturation prop     * Usetler forsueuens[0]t add equaltmananewiyuyColOvere fromthe manaerty ony.     * Usetha {OverOt lit}Aot} strincty.rct.
   pFhe gturation propler forsue     * Usettdith add usuthiytequaltmanaownor.         e      _synuturatTtrinction () {
tStruuens,ct.                over tririsuens[0]              iove.tri    ((((            tcfg.so Body(.tri <             ((((tcfg.getPretilet("blu"iutu"           }
                   },

     /**
 / END BUILT-IN PROPERTYnEVENT HANDLERS /      */
      /**
        * UsetRe {St"sa sg} ThovepEve

 a () {he manaot.
            ethod desttong} Th         ethrn {St {ng} The string} ThovepEve

 a () {he manaSiibntDialog         e      _syntong} Thnction () {
                rn {St "SiibntDialogc" +t;

 .ie          }      /**
        * Uset<p>         etSevcumanaSiibntDialog'stbodyt(g  = YAto td oElem lpeci( es.      * UsetIovprtbodytithpEve

 ,rhn ( add bn automa (cthiytgeEvend.      * UsetAn empn tng} Thocanrberbassverio evenod destto c} tr tlem(tut elshhe manabody          et</p> * @t* @pet<p><yed.ng>NOTE:</yed.ng>oSiibntDialogcbro vencumana<a hret="#uturat_ tri"> tri</a> * @t* @petyhe <a hret="#uturat_iutu">iutu</a>t(turation property onies m  svereven(tut els * @t* @pethe it'stbodytent;

   neaccord's dm athuevenUIhdasign the a SiibntDialogveaop * @t* @p*nicontyhe messagri tri). Cthi Thoso Body he manaSiibntDialogv add pro enforce mais      * UsetUIhdasign utusg}aiu tyhe  add veplace mana

 trem(tut elshhe manaSiibntDialogvbody       * UsetIe ld be ucnly bertsed iovyourwishuevenveplace manadlt "mo icon/ trirbodytng}uctioa  * @t* @pethe aaSiibntDialogv athuyouraown cmEven markup.</p> * @t* @pet         ethod destso Body     * Usetha {OverElem}rbodyCont

  striElem tsed m  sverevenbody       * UsetAs arcon("bie ce,rnoniElemEnt;

 iot.
  slcanr,
 ofberbassverie o      * Usettdepod des, yhe  add berteEvenduastng} Thsue athuevenbodytinnerElem     * Usetsvereo eveir ult "mo eong} Thniibnt;

 a () s.         et     * Uset<p>NOTE:dMarkup bassverie o Ovithod destisaaddverio evenDOMrysoElem,ryhe ld be ubstescapndubye)triiibnt;

 he if gomthe tthe anrtrier_th sousse.</p> * @t* @pet         et<em>OR</em>         etha {OverElement} el TbodyCont

  striElemEnt;

 im  ade  se)tri( tsotyhe cnly c(thi he manabodytent;

 .         et<em>OR</em>         etha {OverDent.bodFrag} el TbodyCont

  striment.bod ttag} elt     * Uset(g  r tthe ent;

 s h blur reftofberaddverio evenbody     * Use       )   }()   (tion () {
                 * Cg  r t rEfflitee capsulvensvanimn prophransi () sneore  refexecuted  the 
* @petyh Overla t ecshown hr Evdden.     eth of space O.widget.Ove     eths.des Cg  r t rEfflit     ethsonsg}uctor     etha {OverO.widget.OverOverla }noverla tstriOverla teore evenanimn prop     etld be ubstdesocivendu ath     etha {OverOt lit}AattrIntstriot literal reptvepEve

 the id oanimn prop     etaents.
  ftofbertsed the id oanimn e-inchransi () .tstriaents.
  fthe      ettdithral rept re: attributes(ot lit, seeiO.util.Dom,Animfthe deycrip (p_),      * dion pro(Number),tyhe me des(i.e. Easthe.easeIn).     etha {OverOt lit}AattrOuttstriot literal reptvepEve

 the id oanimn prop     etaents.
  ftofbertsed the id oanimn e-outthransi () .tstriaents.
  fthe       ettdithral rept re: attributes(ot lit, seeiO.util.Dom,Animfthe deycrip (p_),      * dion pro(Number),tyhe me des(i.e. Easthe.easeIn).     etha {OverElement} el Ttaenopent} el Op (p_th.tstri aenoptent;

  eore p     etld be ubstdnimn ed diothe id ohransi () .tDlt "mo ftofoverla .ent;

 .     etha {Overs.des} Op (p_th.tstrianimn props.des tofince'stiven.tDlt "mo ftof     etO.util.Dom,Anim. Ot
   cp (p_ n ncludeiO.util.Dom,Mo () .
* Use      O.widget.OverCg  r t rEfflite=ction () {
overla ,AattrIn,AattrOut,Ttaenopent} el,ianims(act         Dom !ove!anims(act                anims(act =iO.util.Dom,Anim          }      /**
        * Usetstrioverla teotdnimn e         etharty on toverla          ethtStriO.widget.OverOverla          e      _synt

 .overla t=toverla                        * Usetstrianimn propattributesteotown wthe eransi ()  Thnie o view         etharty on tattrIn         ethtStriOt} us     */
 e      _synt

 .attrInt=tattrIn                       * Usetstrianimn propattributesteotown wthe eransi ()  Thnoutthe view         etharty on tattrOut         ethtStriOt} us     */
 e      _synt

 .attrOutie attrOut                       * Usetstri aenoptent;

  eoubstdnimn ed         etharty on ttaenopent} el         ethtStriElement} el     */
 e      _synt

 .taenopent} el =Ttaenopent} el ||foverla .ent;

                        * Usetstrianimn props.des tofown the animn phe id ooverla          etharty on tanims(act         ethtStric(act         e      _synt

 .anims(act =ianims(act              overDom =iO.util.Dom,Dom      Dom omEvent = YA=iO.util.Dom,omEvent = Y      Dom og  r t rEfflite=cO.widget.OverCg  r t rEfflit               * AtpEv-(turatioed og  r t rEffliteince's dmhore canrbertsed the fadthe      etanroverla tintyhe out.     ethod destFADE     ethsdatpc     etha {OverO.widget.OverOverla }noverla tstriOverla tot liteeotdnimn e     etha {OverNumber} dio strimion propof id oanimn pro     ethrn {St {O.widget.Overog  r t rEfflit}vTlem(turatioed og  r t rEffliteot} us     e      og  r t rEfflit.FADEe=ction () {
overla ,Amio         Dom overEastheA=iO.util.Dom,Easthe      Dom     fin is                  attributes: {opacity:{tthe:0  he:1}}      Dom         mion pro: mio      Dom         md desncEasthe.easeIn         }
         Dom     foutie                   attributes: {opacity:{he:0}}      Dom         mion pro: mio      Dom         md desncEasthe.easeOut         }
         Dom     fadn =anew og  r t rEfflit
overla ,Afin, fout,foverla .ent;

 )<          fadn.ler foUnderla Staete=ction () 
                overunderla ie tcfg.overla .underla               ioveunderla i&& O.widgtnv.ua.im                    overhasFilr(rsve iunderla .filr(rsv&& underla .filr(rsgth; i n>e
 <             ((((if(hasFilr(rs                        getSyass(actioverla .ent;

 ,cuyui-efflit-fadn"
                            ((((          }<          fadn.ler foUnderla Coibnttee=ction () 
                overunderla ie tcfg.overla .underla               ioveunderla i&& O.widgtnv.ua.im                    getSved fod(actioverla .ent;

 ,cuyui-efflit-fadn"
                        }<          fadn.ler foStaetAnimn eInt=ttion () {
tStrueuens,rct.                ct..overla ._fadtheInt=t  on               getSyass(actiot..overla .ent;

 ,cuhven-seld
 "
               iove!ot..overla .underla                     ot..overla .getPretilet("blu"underla "
                             ct..ler foUnderla Staet  ;          }
  ct..overla ._setgetVle")omin ();
            }
  getSsetStyleiot..overla .ent;

 ,cuopacity",e
 <         }<          fadn.ler foCoibntteAnimn eInt=ttion () {
tStruuens,ct.                ct..overla ._fadtheInt=te,
                         * = getSved fod(actiot..overla .ent;

 ,cuhven-seld
 "
               ioveot..overla .ent;

 .style.filr(r                    ot..overla .ent;

 .style.filr(rie ;

 <                            ct..ler foUnderla Coibntte  ;          }
  ct..overla .getPretilet("blu"if{Ovn"
              ct..animn eInCoibnttet = Y.tilec
          }<          fadn.ler foStaetAnimn eOutie tion () {
tStrueuens,rct.                ct..overla ._fadtheOutie   on          }
  getSyass(actiot..overla .ent;

 ,cuhven-seld
 "
              ct..ler foUnderla Staet  ;         }<          fadn.ler foCoibntteAnimn eOutie  tion () {
tStrueuens,rct.                ct..overla ._fadtheOutie e,
                getSved fod(actiot..overla .ent;

 ,cuhven-seld
 "
               ioveot..overla .ent;

 .style.filr(r                    ot..overla .ent;

 .style.filr(rie ;

 <                           ct..overla ._setgetVle")omin (e,
             }
  getSsetStyleiot..overla .ent;

 ,cuopacity",e1 ;          }
  ct..ler foUnderla Coibntte  ;          }
  ct..overla .getPretilet("blu"if{Ovn"
              ct..animn eOutCoibnttet = Y.tilec
          }<          fadn.initc
          rn {St e,de                               * AtpEv-(turatioed og  r t rEffliteince's dmhore canrbertsed the slidthe an      * overla tintyhe out.     ethod destSLIDE     ethsdatpc     etha {OverO.widget.OverOverla }noverla tstriOverla tot liteeotdnimn e     etha {OverNumber} dio strimion propof id oanimn pro     ethrn {St {O.widget.Overog  r t rEfflit}vTlem(turatioed og  r t rEffliteot} us     e      og  r t rEfflit.SLIDEe=ction () {
overla ,Amio            overEastheA=iO.util.Dom,Easthe               xt=toverla OgetProperty("focux" (||fgetSropXioverla .ent;

 )      Dom     yt=toverla OgetProperty("focuy" (||fgetSropYioverla .ent;

 )      Dom     cli

 Widi tefgetSropCli

 Widi ()      Dom     offsetWidi tefoverla .ent;

 .offsetWidi                sin is                    attributes: { points: { he: [x, y] }        Dom         mion pro: mio      Dom         md desncEasthe.easeIn                              soutie                   attributes: { points: { he: [(cli

 Widi t+ 25), y] }        Dom         mion pro: mio      Dom         md desncEasthe.easeOut      Dom                     slidn =anew og  r t rEfflit
overla ,Asin, sout,foverla .ent;

 ,iO.util.Dom,Mo ()  ;          slidn.ler foStaetAnimn eInt=ttion () {
tStruuens,ct.                ct..overla .ent;

 .style.leftie ((-25) - offsetWidi ) + "px"              ct..overla .ent;

 .style.top t=cy + "px"          };          slidn.ler foTweenAnimn eInt=ttion () {
tStrueuens,rct.                         overposhisgetSropXYiot..overla .ent;

 )      Dom         (urly fXve pos[0]      Dom         (urly fYve pos[1]                       iovegetSropStyleiot..overla .ent;

 ,cuvle")omin "  =e      Dom         "hveden"i&& (urly fXv< x         Dom         ct..overla ._setgetVle")omin ();
                                    }
  ct..overla .getPsoperty("focuxy",e[(urly fX, (urly fY], );
            }
  ct..overla .getPretilet("blu"if{Ovn"
          }                   slidn.ler foCoibntteAnimn eInt=ttion () {
tStrueuens,rct.                ct..overla .getPsoperty("focuxy",e[x, y], );
            }
  ct..staetXve x          }
  ct..staetYve y          }
  ct..overla .getPretilet("blu"if{Ovn"
          }
  ct..animn eInCoibnttet = Y.tilec
          }<          slidn.ler foStaetAnimn eOutie tion () {
tStrueuens,rct.                     overvwhisgetSropViewpor Widi ()      Dom         poshisgetSropXYiot..overla .ent;

 )      Dom         ysove pos[1]               }
  ct..animOut.attributes.points.toie [(vwh+ 25), yso]          }                   slidn.ler foTweenAnimn eOutie tion () {
tStrueuens,rct.                     overposhisgetSropXYiot..overla .ent;

 )      Dom         xtove pos[0]      Dom         ytove pos[1]                   }
  ct..overla .getPsoperty("focuxy",e[xto, yto], );
            }
  ct..overla .getPretilet("blu"if{Ovn"
          }                   slidn.ler foCoibntteAnimn eOutie tion () {
tStrueuens,rct.                ct..overla ._setgetVle")omin (e,
                  ct..overla .getPsoperty("focuxy",e[x, y]
              ct..animn eOutCoibnttet = Y.tilec
          }<          slidn.initc
          rn {St slidn              og  r t rEfflit.proto Stri=n       Dom         * UsetInitirlizds manaanimn props.desnsvandre = Ys          ethod dest ttY         e      _syn ttY:ction () {
         Dom     tcfg.besEreAnimn eInt = YA=itcfg.geEvent("blu"besEreAnimn eIn"
          }
  tcfg.besEreAnimn eInt = Y.signatioa = omEvent = Y.LIST                       * = tcfg.besEreAnimn eOutt = YA=itcfg.geEvent("blu"besEreAnimn eOu "
              tcfg.besEreAnimn eOutt = Y.signatioa = omEvent = Y.LIST                       tcfg.animn eInCoibnttet = YA=itcfg.geEvent("blu"animn eInCoibntte"
              tcfg.animn eInCoibnttet = Y.signatioa = omEvent = Y.LIST                       tcfg.animn eOutCoibnttet = YA=itcfg.geEvent("blu"animn eOutCoibntte"
              tcfg.animn eOutCoibnttet = Y.signatioa = omEvent = Y.LIST               tcfg.animInt=tnew t

 .anims(act(     Dom         t

 .taenopent} el,                  t

 .attrIn.attributes,                  t

 .attrIn.mion pro,                  t

 .attrIn.md des)               tcfg.animIn.onStaetnaubycribe(tcfg.her foStaetAnimn eIn, , sh
              tcfg.animIn.onTweennaubycribe(tcfg.her foTweenAnimn eIn, , sh
              tcfg.animIn.onCoibnttenaubycribe(tcfg.her foCoibntteAnimn eIn,, sh)                       tcfg.animOutie new t

 .anims(act(     Dom         t

 .taenopent} el,                  t

 .attrOut.attributes,                  t

 .attrOut.mion pro,                  t

 .attrOut.md des)               tcfg.animOut.onStaetnaubycribe(tcfg.her foStaetAnimn eOut,Tt sh
              tcfg.animOut.onTweennaubycribe(tcfg.her foTweenAnimn eOut,Tt sh
              tcfg.animOut.onCoibnttenaubycribe(tcfg.her foCoibntteAnimn eOut,Tt sh
           }       /**
        * UsetTriggercumanain-animn pro          ethod destanimn eIn         e      _synanimn eInnction () {
                 cfg._EvepAnimti._onOlastF{OvnOnStop
          }
  tcfg.besEreAnimn eInt = Y.tilec
              tcfg.animIn.animn ec
                  /**
        * UsetTriggercumanaout-animn pro          ethod destanimn eOut         e      _synanimn eOutnction () {
                 cfg._EvepAnimti._onOlastF{OvnOnStop
          }
  tcfg.besEreAnimn eOutt = Y.tilec
              tcfg.animOut.animn ec
                          /**
        * UssetFlagahe detinn wtht
   Animfld be ujumprio evenlast f{Ovn,     * Ussetwthe animn eIn he animn eOuti ecstoppe                  * Ussetharty on tlastF{OvnOnStop     * Ussethult "mo e;
      * Usseth Striban} tr     * Usse      _synlastF{OvnOnStop : erth,      /**
        * Us etStopstboth animIntyhe ynimOutiince's ds, if iopertgEves                 * Ussethod dest_EvepAnimt     * Ussetha {Overban} tr} tinishuIe erth yanimn prop add jumprio finth f{Ovn.     * Ussetharttd
        * Usse      _syn_EvepAnimt nction () (tinish                !ovetcfg.ynimOuti&& tcfg.animOut.isAnimn edc
                    tcfg.animOut.Evep(tinish <                            !ovetcfg.ynimIni&& tcfg.animIn.isAnimn edc
                    tcfg.animIn.Evep(tinish <                               /**
        * UsetTd oult "mowonStaetdler forAthe manain-animn pro          ethod desther foStaetAnimn eIn         etha {Overng} The tStristriomEvent = YAtStr         etha {OverOt lit[]}naensistriomEvent = YAaents.
           etha {OverOt lit}Aot} strincty.rct.
           e      _synher foStaetAnimn eInnction () {
tStrueuens,rct.            /**
        * UsetTd oult "mowonTweendler forAthe manain-animn pro          ethod desther foTweenAnimn eIn         etha {Overng} The tStristriomEvent = YAtStr         etha {OverOt lit[]}naensistriomEvent = YAaents.
           etha {OverOt lit}Aot} strincty.rct.
           e      _synher foTweenAnimn eInnction () {
tStrueuens,rct.            /**
        * UsetTd oult "mowonCoibntteeler forAthe manain-animn pro          ethod desther foCoibntteAnimn eIn         etha {Overng} The tStristriomEvent = YAtStr         etha {OverOt lit[]}naensistriomEvent = YAaents.
           etha {OverOt lit}Aot} strincty.rct.
           e      _synher foCoibntteAnimn eInnction () {
tStrueuens,rct.            /**
        * UsetTd oult "mowonStaetdler forAthe manaout-animn pro          ethod desther foStaetAnimn eOut         etha {Overng} The tStristriomEvent = YAtStr         etha {OverOt lit[]}naensistriomEvent = YAaents.
           etha {OverOt lit}Aot} strincty.rct.
           e      _synher foStaetAnimn eOutnction () {
tStrueuens,rct.            /**
        * UsetTd oult "mowonTweendler forAthe manaout-animn pro          ethod desther foTweenAnimn eOut         etha {Overng} The tStristriomEvent = YAtStr         etha {OverOt lit[]}naensistriomEvent = YAaents.
           etha {OverOt lit}Aot} strincty.rct.
           e      _synher foTweenAnimn eOutnction () {
tStrueuens,rct.            /**
        * UsetTd oult "mowonCoibntteeler forAthe manaout-animn pro          ethod desther foCoibntteAnimn eOut         etha {Overng} The tStristriomEvent = YAtStr         etha {OverOt lit[]}naensistriomEvent = YAaents.
           etha {OverOt lit}Aot} strincty.rct.
           e      _synher foCoibntteAnimn eOutnction () {
tStrueuens,rct.           _syn     /**
        * UsetRe {St"sa sg} ThovepEve

 a () {he manaot.
            ethod desttong} Th         ethrn {St {ng} The string} ThovepEve

 a () {he manaCg  r t rEfflit         e      _syntong} Thnction () {
                overoutputie "Cg  r t rEfflit"              iovetcfg.overla                     outputi+e " [" +t;

 .overla .tong} Th() + "]"<                           rn {St output          }     }       O.utillang.aug} elProto(Cg  r t rEfflit,iO.util.Dom,t = YPro venr)   })c
  O.utilvegner(r("cg  r t r", O.widget.OverModulrue{verspro: "2.9.0",obuild: "2800" )  