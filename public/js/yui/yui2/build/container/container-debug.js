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

        if (!owner) {  YAHOO.log("No owner specified for Config object", "error", "Config"); }

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
            YAHOO.log("Firing Config event: " + key + "=" + value, "info", "Config");
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
            YAHOO.log("Added property: " + key, "info", "Config");
        
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
            YAHOO.log("setProperty: " + key + "=" + value, "info", "Config");
        
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
            YAHOO.log("queueProperty: " + key + "=" + value, "info", "Config");
        
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

                YAHOO.log("Config event queue: " + this.outputEventQueue(), "info", "Config");

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
            YAHOO.log("No element or element ID specified" + 
                " for Module instantiation", "error");
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
                key: "visibleX subsc = event(k:BooleansibleX subsc = event{ // v: };

    YAHis      *        }
     , = null;

    "EFFECE_CO == fn) {
              ereacgeContent",
        yObject.suppr:Booleaontent",
        yOberty/su: [   key: "]       }
     , = null;

    "MO   OR "BSIZE_CO == fn) {
              moNITof tsizeforeHide",
    = event(k:Boole       }
     , = null;

    "      _TO_DOCUM    "CHANGE == fn) {
              PEND":to 
    * b"changeBody",
    = event(k:B retu       }
            }
  }
     {

    /**
    * Constant representp   txontthe Object to non-sect.sidrogecriptions
    * @podule = YAHOO.widge.IMG_RCHAbscribed
    * @static     * @fi        ethod toStr       .widge.IMG_RCHAQueue[i] = null;
  {

    /**
    * Constant representp   txontthe Object to sect.s currhts idrogecriptions
    * @podule = YAHOO.widge.IMG_RCHA_SSLbscribed
    * @static     * @fi        ethod toStr       .widge.IMG_RCHA_SSLQueue[i] = null;
  {

    /**
    * C Object defaultsing the Cting turationstant rForming the Module
    * @podule = YAHOO.widge.ing_MODULEbscribed
    * @static     * @fi        ethod toStr       .widge.ing_MODULEr ouyui-or thigChanged";
    
    *sing the Cing tonstant represent
    * ing th. NHAN:erConfhe Cing t   /nrrhtect passed  DOMs pa@pard", "bon that shescapectbyto truow the io lofamilt in tenescribes an es * cr package.
    * @podule = YAHOO.widge.ing_"CHANGbscribed
    * @static     * @fi        ethod toStr       .widge.ing_"CHANGr ouhigChd";
    
    *sing the Cing tonstant represent
    * b"ch. NHAN:erConfhe Cing t   /nrrhtect passed  DOMs pa@pard", "bon that shescapectbyto truow the io lofamilt in tenescribes an es * cr package.
    * @podule = YAHOO.widge.ing_"CHAbscribed
    * @static     * @fi        ethod toStr       .widge.ing_"CHAEAN_TigChanged";
    
    *sing the Cing tonstant represent
    * dy, an. NHAN:erConfhe Cing t   /nrrhtect passed  DOMs pa@pard", "bon that shescapectbyto truow the io lofamilt in tenescribes an es * cr package.
    * @podule = YAHOO.widge.ing_"CHANGbscribed
    * @static     * @fi        ethod toStr       .widge.ing_"CHANGEAN_ftgChanged";
    
    *si
    * Constant representurlC Object "src" atthobut the nameifrng t
    *s can handoNITofsten for assed  browrrhe M is tf"ch sizepackage.
    * @podule = YAHOO.widge."BSIZE_MO   OR SECU   URLbscribed
    * @static     * @fi        ethod toStr       .widge."BSIZE_MO   OR SECU   URLEAN_java the s: returgChd";
    
    *si     * Constant representiureat amou* C(iiguix   )e Objectcribecurrent od toStr esentrride tsizendoNITofsoff theentomEve tsizendoNITofsropeurrent Subscribeoff theenhe que amou* Ceqaul's valueoff etHe/*
Co+esentiureat current val* package.
    * @podule = YAHOO.widge."BSIZE_MO   OR BUFFEGbscribed
    * @static     od checkNum       //    's v1ue enwork ar     uix  eoff et claIE8,vent foin 
  sebscribezomoveobject     .widge."BSIZE_MO   OR BUFFEGEAN1Chd";
    
    *sSod letfigevt The Custonot scriberue, "ch sizePropeconfig classesbrowrrhnt val* O  *ae M"zomo"Module = ais a mEvents cudoebsnt wyObjorttrridet val* sizePecksle =  package.o, domodule = YAHOO.widge.rridRtsized CustomEve      .widge.rridRtsized CusQueuewgevt The Cus("rridRtsize "Config"   
    /**Hel  *fig is a 
    *,vent fo Obc * Ap 
    *  lenab 
    /**ot rawC ObjO  *a,vent fond thel *ot    Cons    
    /**ivenga paig propf and     epreDOMsten fored event
    /**t
    * @metho= YAHOO.widge. Obc D
    * Rt raw
    /**t     * @stati      .widge. Obc D
    * Rt rawt.Module = unction () {
};

 
 Elt.M 
    * . 
    * am {HTM"error");
     
 El  if (el) {
     
 El.fhe CNng t outp"bers - 1;
       El.fhe CNng tar Lang = YA.thom(   El.fhe CNng  "error");
        }
    odule = f 
         m_oModuleunction () {

             m_oModule  if (el) {
            m_oModulet.M 
    * . = thism {HTM("div "Config");
    f (el) {
            m_oModule.inner@part.M("<divnfhe C=\"pecified" + 
        .widge.ing_"CHANGr+ "\"></div>peci"<divnfhe C=\"pecified" + 
        .widge.ing_"CHAE+ "\"></div><divnfhe C=\"pecified" + 
        .widge.ing_"CHANGE+ "\"></div>p"Config");

            m_oHeadert.M        m_oModule.onostC
   ;nfig");

            m_oBodyt.M        m_oHeader.nridSey:epr;nfig");

            m_oFootert.M        m_oBody.nridSey:epr;nfig");

        }

               m_oModule;nfig"}}
    odule = f 
         m(erConfig) {
    !        m_oHeader  if (el) {
     
         m_oModuleun"error");
       }

       (        m_oHeader.clt SNode( retusNaN(val}}
    odule = f 
        (erConfig) {
    !        m_oBody  if (el) {
     
         m_oModuleun"error");
       }

       (        m_oBody.clt SNode( retusNaN(val}}
    odule = f 
         m(erConfig) {
    !        m_oFooter  if (el) {
     
         m_oModuleun"error");
       }

       (        m_oFooter.clt SNode( retusNaN(val}}
    .widge.pConfig.protot      }

        /**
  rConfhe Ce Modu  * @con odule =      /**
        * @prop * @construc/**
   ig.prFdule =      /**
        */
     * @con: .widget.Module,
      }

        /**
  rCon * at
    * ment or  in, so   * Maintaing the header, body, an     /**
        * @pment orstruc/**
   ig.pr@param {HTM     /**
        */
ment orgedEvent      }

        /**
  rConing th
ment or,e 
    *  "thsing the Ctuhig     /**
        * @ping thstruc/**
   ig.pr@param {HTM     /**
        */
ing thgedEvent      }

        /**
  rConhead
ment or,e 
    *  "thsing the Ctubig     /**
        * @pheadstruc/**
   ig.pr@param {HTM     /**
        */
headgedEvent      }

        /**
  rCondy, andment or,e 
    *  "thsing the Ctuftg     /**
        * @pdy, an     /**
   ig.pr@param {HTM     /**
        */
dy, angedEvent      }

        /**
  rConio the endment orstruc/**
   r a new prd     /**
   ig.prethod toString
        */
 dgedEvent      }

        /**
  Aeturns a string representry, ontthee class drogec  
    ctby     /**
  a   " for Modulco execute.
   dstric   ctIle Foricommeinerse resetdrogec e even  " for shitiallyexecute.
  viasing denoted us"backgr    -droge"etting a property.
        * @prrogeRoorstruc/**
   ig.prethod toString
        */
 rogeRoor: .widge.IMG_RCHAnt      }

        /**
        * Initializ buildres the eve  " for/ calls whonot s     /**
  auuil   *re inithiti   r * a timnitbyto tr  " forthe C members.
        * @metEdule's events
        */
    Edule' destroy: function () {

        SIGNATU  ignature = CustomEvenon () {

            /**
rs.
  evt The Custonot s r oe hanthe Ct     * Iae =  packaembers.
   o, domINIT": "being fireEvent
    ule
    * the C}nthe CRefnthe Ct Object rehe namei     * Iid it being 
    *nthe C,tionhs pa     INIT": "being fgedEve  " fo)t being 
    */t being 
         INIT": "being fct.event = this.crea        EVE.       "BEF"Config");
         INIT": "being fgt.event.signSIGNATU  enon () {

            /**
rs.
  evt The Custonot spf andthe Ct     * Iae =  packaembers.
   o, domi"being fireEvent
    ule
    * the C}nthe CRefnthe Ct Object rehe namei     * Iid it being 
    *nthe C,tionhs pa     INIT": "being fgedEve  " fo)t being 
    */          
         i"being fct.event = this.crea        EVE."BEF"Config");
         i"being fgt.event.signSIGNATU  enon () {

            /**
rs.
  evt The Custonot scriberue,*
    * ModEND":subscribedDOMpackaembers.
   o, domdEND":ing fireEvent
    u/t being 
         dEND":ing fct.event = this.crea        EVE.      "Config");
         dEND":ing fgt.event.signSIGNATU  enon () {

            /**
rs.
  evt The Custonot sINIT":erue,*
    * MoNDER":lyexecute.
rs.
   o, domNDER": "befoing fireEvent
    u/t being 
         NDER": "befoing fct.event = this.crea        EVE.             "Config");
         INIT": "befoing fgt.event.signSIGNATU  en");
    on () {

            /**
rs.
  evt The Custonot spf andrue,*
    * MoNDER":lyexecute.
rs.
   o, domr"befoing fireEvent
    u/t being 
         r"befoing fct.event = this.crea        EVE.      "Config");
         r"befoing fgt.event.signSIGNATU  en");
    on () {

            /**
rs.
  evt The Custonot scriberue,ing th
 of the he name of thit being 
    *nt for  ID sexecute.
rs.
   o, domADER": "chaning fireEvent
    ule
    * ethod /@param {HTMLE of the ethod / el The element repreireEvent
    ulsentiew,ing th
 of theireEvent
    u/t being 
         ADER": "chaning fct.event = this.crea        EVE.       "CHANG"Config");
         ADER": "chaning fgt.event.signSIGNATU  en");
        on () {

            /**
rs.
  evt The Custonot scriberue,head
 of the he name of thit for  ID sexecute.
rs.
   o, domADER":    ing fireEvent
    ule
    * ethod /@param {HTMLE of the ethod / el The element repreireEvent
    ulsentiew,head
 of thet being 
    */          
         ADER":    ing fct.event = this.crea        EVE.       "CHA"Config");
         ADER":    ing fgt.event.signSIGNATU  en");
        on () {

            /**
rs.
  evt The Custonot scriberue,dy, and of the he name of thit being 
    *nt for  ID sexecute.
rs.
   o, domADER":     ming fireEvent
    ule
    * ethod /@param {HTMLE of the ethod / el The element repreireEvent
    ulsentiew,dy, and of theireEvent
    u/t being 
         ADER":     ming fct.event = this.crea        EVE.       "CHANG"Config");
         ADER":     ming fgt.event.signSIGNATU  en");
    on () {

            /**
rs.
  evt The Custonot scriberue, of the he name of thit for  ID sexecute.
rs.
   o, domADER": "changing fireEvent
    u/t being 
         ADER": "changing fct.event = this.crea        EVE.       "CHANGE"Config");
         ADER": "changing fgt.event.signSIGNATU  enon () {

            /**
rs.
  evt The Custonot scriberue,*
    * MoTROY":  sexecute.
rs.
   o, domTROY": ing fireEvent
    u/t being 
         TROY": ing fct.event = this.crea        EVE.       "Config");
         TROY": ing fgt.event.signSIGNATU  enon () {

            /**
rs.
  evt The Custonot sINIT":erue,*
    * MoSHOWnexecute.
rs.
   o, domSHOW": "being fireEvent
    u/t being 
         NDER": "being fct.event = this.crea        EVE.           "Config");
         INIT": "being fgt.event.signSIGNATU  enon () {

            /**
rs.
  evt The Custonot spf andrue,*
    * MoSHOWnexecute.
rs.
   o, doms"being fireEvent
    u/t being 
         s"being fct.event = this.crea        EVE.    "Config");
         s"being fgt.event.signSIGNATU  enon () {

            /**
rs.
  evt The Custonot sINIT":erue,*
    * Mohiddenexecute.
rs.
   o, domSHOW": "being fireEvent
    u/t being 
         NDER": "being fct.event = this.crea        EVE.           "Config");
         INIT": "being fgt.event.signSIGNATU  enon () {

            /**
rs.
  evt The Custonot spf andrue,*
    * Mohiddenexecute.
rs.
   o, domh"being fireEvent
    u/t being 
         h"being fct.event = this.crea        EVE.    "Config");
         h"being fgt.event.signSIGNATU  en");
    },       },

        /**
   urns a"be ref eprewsilent Whetrom its clat str* Mowindows andarctomEropeof the property
  mEvents cuonlya"be refinitialse 2 clat strontainer     * essful      * oproperty. 
        * @pclat strexecute.
   dstric   ctUse    UA = YAHOstruc/**
   ig.pr ethod |return {s events
        */
clat strtoString: function () {
        uaQueuavig// v.nit(Agg fgy = key.toLowerCase();

        ua i"dexOf("windows") != -1 || ua i"dexOf("win32") != -1ty.event) {
               "windows"er, obj);
               ua i"dexOf("mon, tosh") != -1ty.event) {
               "mon"er, obj);
            } else {
                return false;
            }
 ()       },
        
        /**
   urns a string representnit(-agthe he namebrowrrhexecute.
   dstric   ctUse    UA = YAHOstruc/**
        * @phrowrrhexecute.
   ig.pr ethod |return {s events
        */
hrowrrhtoString: function () {
        uaQueuavig// v.nit(Agg fgy = key.toLoweron () {
            /*
         ueueItO  *a onostor (pis te valoe vainehich tSafarimSHOW":      /*
         Gch o sit reSafari'objecr agthe turns ait luchil"like Gch o"      /*
    u/t being 
        ua i"dexOf('o  *ae) != -1ty.ified" + 
               'o  *aeer, obj);
               ua i"dexOf('msie 7') != -1ty.event) {
               'ie7eer, obj);
               ua i"dexOf('msie') != -1ty.event) {
               'ieeer, obj);
               ua i"dexOf('safari') != -1ty.ified" + 
               'safari'er, obj);
               ua i"dexOf('gch o') != -1ty.event) {
               'gch o'er, obj);
            } else {
                return false;
            }
 ()       },
        
        /**
        *  string reprewsilent to not Whetrom its hrowreprecverrideipassed as 
  sect.si(https)struc/**
        * @pisSect.sexecute.
   ig.pr      *s events
        */
isSect.stoString: function () {
        window.locae =  hrefgy = key.toLow i"dexOf("https") ===ribers > 0) {
                return true;
            } else {
                return false;
            }
 ()       },
        
        /**
        * Initializ buildres the eve  " for/ calls whonot s     /**
  auuil   *re inithiti   r * a timnitbyto tr  " forthe C members.
        */
    Default     getConfig: function () {
   
 / AddSets all pro//t being 
            /**
rs.
  Sent ID sewsilent Whet*
    * Mo  key:  modulespner packa/**
rs.
  @   this  key: packa/**
rs.
  @ig.pr      *s events
rs.
  @defaultsoole       }
    u/t being 
         Afg.addg.hasOwnP        DEFAUL.       .nctio } else {
        bscribe:         thiV key: visibleX subsc = event(k:B        DEFAUL.       .+ "=" +sibleX subsc = event{ // v:         DEFAUL.       .+ "{ // validator
    }werCase();

            /**
rs.
  <p>     /**
rs.
  uration everraer fa Confiild nodes represent 
    * CEreacgit being 
    *nthe Ce controct pat" rege evendrop,present markup c packa/**
rs.
  </p>     /**
rs.
  <p>     /**
rs.
  <OY":ng>NHAN:</OY":ng>       * Alti Module's config     /**
rs.
   silent propiur Uduct spt Whet*
    *lenabntai// Clhe namebox     /**
rs.
  uow the iconfigibsnt wyhipp be calledr  " forthe Cssedsettod toString
rs.
        *silent ponlledr  " forthe Cshabsnt ereacgtomEve  * and t being 
    *nthe C* Moduleonostothe C*to   *Even// Clhe nameboxt 
    * CEreacgit being 
    *nyObjort packa/**
rs.
  </p>     /**
rs.
  @   thisereacgpacka/**
rs.
  @ig.pr* @type Object
rs.
  @defaultsdEve       }
    u/t being 
         Afg.addg.hasOwnP        DEFAUL.EFFECE.nctio } else {
        bscribe:         thiEreacgContent",
        yObject.suppr:B        DEFAUL.EFFECE.yObject.suppr +sibleX subsc = evyOberty/su:         DEFAUL.EFFECE.yOberty/supercedes
    }werCase();

            /**
rs.
  Sent ID sewsilent Woo 
    *aement a Setsxyeifrng thandoNITofs     /**
rs.
   caljecr  "ch  tsizns aitject d
    *      /**
rs.
  @   thismoNITof tsizepacka/**
rs.
  @ig.pr      *s events
rs.
  @defaultsoole       }
    u/t being 
         Afg.addg.hasOwnP        DEFAUL.MO   OR "BSIZE.nctio } else {
        bscribe:         thiMoNITofRtsizengeBody",
    = event(k:B        DEFAUL.MO   OR "BSIZE.ent(kpercedes
    }werCase();

            /**
rs.
  Sent ID se} true,
    * on that shNDER":lys pa  leonostot
         /**
rs.
  he  
    * .head
orodEND":sub pa  lehe tot
    cribeNDER":Propeust b     /**
rs.
   "ths 
    * .head
 pa  le PEND":ToNode" packa/**
rs.
  <p>     /**
rs.
  AEND":,presoerue,head
      ed  DOMs MoStvalueseprecve  * @csubc *        }
    **leaubscrO  *aonfigAbohtecttion"s claIE hct relti Mflags MoS  's v     /**
rs.
   ssfulbytdefault packa/**
rs.
  </p>     /**
rs.
       /**
rs.
  @   thisPEND":to 
    * b"chpacka/**
rs.
  @ig.pr      *s events
rs.
  @defaults retu       }
    u/t being 
         Afg.addg.hasOwnP        DEFAUL.      _TO_DOCUM    "CHA.nctio } else {
        ent(k:B        DEFAUL.      _TO_DOCUM    "CHA.ent(kpercedes
    }werdes
    }nt      }

        /**
  rCon  " forthe C'sei     * Iaonfig
    *,vent fo Moents tobe ca     /**
   extend Modass ffvaluesubthe Ce tomErop     * @s auuil   *re inproperty
  must bebyto tr     * @conntaineoS  objpdass DOMs Object rhe eveproperty
  jec-  * any eard fontaine 
    ild    * ddard forifexecutsnt w     /**
  aair is nodes r members.
  <p>     /**
*execute. el The omEvent pudoebsnt whavnd M i*,vonee values gall    c     /**
*eto lor members.
  </p>     /**
*e      * @metstruc/**
        * @param {String} el The element ID representing the Module <em>OR<//**
        * @param {HTMLElement} el The element representing the Mod/**
        * @param {Object} userConfig The configuration Object property
  miteral con 
    * the configuration that should be set for this      /**
   odule. See config 
    * documentation for more detai/**
        */
    :Module = function (el, userCoion () {
        elI*,vc
   ;nt being 
             Edule'oweron () {
         INIT": "being fgedEve  " fo)erCase();

            /**
rs.
  rCon  " fo'seng} The Config can ntatioNITofid it being 
    *ntdule's configuration pr packa/**
rs.
  @     * @prf toString
rs.
  @ig.pronfig = YAHOO.uti       }
    u/t being 
         AfgQueuewgeO.uti     werCase();

              sSect.sbers > 0) {
              rogeRoorQue.widge.IMG_RCHA_SSLrn false;
      Case();

         g.pffvlem== "turns "bers > 0) {
        elI*   sq] = null;
        elt.M 
    * .ge am {HTMById    ibers[i];
            !     if (el) {
            elt.M( 
         m_oModuleun).clt SNode( retusibeAll();
            el.i*   sqIdibeAll();
                }
      t being 
          *   Dom.gall    Id    ibers[i];
          el The   sq] bers[i];
    t
    =       el The.onostC
   ;nCase();

        t
     if (el) {
            fndHdProgression dBdProgression dFtuplicate = false;
                do {
            // We'n ftiont in st} el Theupercedes
                1m== t
   .* chTg.pg, sKey)) {
                    !fndHdP&& Dom.habChe C t
   , .widge.ing_"CHANGig, sKey)) {
                         hng th
=vc
   ;nKey)) {
                    fndHdProicate = true;
                           !n dBdP&& Dom.habChe C t
   , .widge.ing_"CHA"g, sKey)) {
                         head
=vc
   ;nKey)) {
                    fndBdProicate = true;
                           !n dFtP&& Dom.habChe C t
   , .widge.ing_"CHANG") sKey)) {
                         dy, and=vc
   ;nKey)) {
                    fndFtuplicate = true;
                  = true;
                    }
                 (t
    = t
   .*ridSey:epr))rn false;
      Case();

             Default     gowerCase();

    Dom.addChe C       el The, .widge.ing_MODULEwerCase();

        uKey in userConfig) {
             Afg.a       appProperty(usericat)rn false;
      Case();

            /*
        Sto use subsc  leonoset, fireQ    * @feng} Thesed in, ent for any 








rrentlytdule's configten for ct pexcts tobeupobeNDER":P@feonfig) {
          nting the Mod/**




    Case();

            if (!Config.alreadySub     r"befoing f,      Afg.onoset, f,      Afg"g, sKey)) {
             r"befoing fgtvent.unsu     Afg.onoset, f,      Afgericat)rn false;
      Case();

         i"being fgedEve  " fo)erse;
     nt      }

        /**
        * Inescrimp @pIFRAMEd in, ropelact s/ Clhe name  key:  ct acribe to 
    n, so they can  Checkscttrride tsize members.
        * @metRtsizeMoNITofdetai/**
        */
    RtsizeMoNITof destroy: function () {

        isGch oWi    (UA.gch oP&&      clat str*== "windows");Case();

        isGch oWi g, sKey)) {
        // Hel  nod, domspinl conloa:,preicbscrnt fosKey)) {
        // starCase();

wa:,pre of  ct pexcts tobg fgedEve  " fo)t beisstarCase();

wa:,pre of  ct pexcts tobg fgedEve  " fo)t beisstarCase();

wa:,pre of  ct pexcts tobg fgedEve  " fo)t beisstarCase();

wa:,pre of  ct pexcts tobg fgedEve  " fo)t beisstarCase();

wa:,pre of  ct pexcts tobg fgedEvaBxcts tobg fggggggg = uself                    Afg.a   TimeoWi( m_oModule{self._edEve  " fo)t beir a}, 0at.
        */
        toString: functioissta_edEve  " fo)t beir a   */
    Rtsiz
    Rtsi   (UA.gch oP&&      clf ep"BSIZE.ndecutsnt w *s evev1ue enwork ar     edEve     .&      clf e&      clf e {
 icbsulbytdefault  // starC_edEve  " fo)t beibytdefault ts tobg fg_edEve  " fo)t bei :  m_oModule  i:,pre of  ct pexcoDoctsizepacka/**
rs.
 oIF    tsizepacka/**
rs.
   detof queued properarCase()) {
Tt     * @      DEFAUL.        ho= YAHOO.widge. Obc D() {

     */
    RtsizeMoNITof dest* Inee  r, ob* @method outputEventIF    Theupercet("_yuie  " fo)t beiparam {String} key Th = usuppr:BsCWe  " f        _suppr:BsCWe  " fr any 








rrdest* InetIF    perty's change event and IF    Thege.ing_"CHANGE+ "\"></die     param {String} key Thdest* In( 
         mt be valueoff etHe/*
Co+esentiureat c bee  ig, sKey)) {
                  IF    .src();
      ff etHe/*
Co+esentiureat              Afg.a    t for a property usdest* Inesuppr:BsCWe  " f, sKey)) {
                     an't ar     uin
rs.
  eWpre oig = ) {
ndoNITinscribe     Key)) {
                 se.onost["<html><    ><val* p ueue[i] = null;
  {{{{{{{{{{{{{{{{{{"
   =\**t  /ent val* p\">ueue[i] = null;
  {{{{{{{{{{{{{{{{{{"    /**onenwork= m_oModule{    /**paue;
.ueue[i] = null;
  {{{{{{{{{{{{{{{{{{"    epreDOMsten fored event
    /**.ueue[i] = null;
  {{{{{{{{{{{{{{{{{{") {

  };<ueue[i] = null;
  {{{{{{{{{{{{{{{{{{"\/val* p></    >ueue[i] = null;
  {{{{{{{{{{{{{{{{{{"<  = ></  = ></html>"].join(''aram {String} key Thdesttttt IF    .src();"data:*t  /html;alsr   =utf-8,

  enc_"CURICs. Also (se.onogression dFtuplicate =t for a property usdest IF    .l The"_yuie  " fo)t beipgression dFtuplicate = IF    .up conhe"Tt   e  " f o)t beipgression dFtuplicate = IF    .uabI a t   * gression dFtuplicate = IF    .   ArgChd";
("ro    """, and "ft" param {String} key Thdest
               ua iiiiiiiiNeloa:,p    "ebscribe members.
tonot sp
    * "being Key)) {
                       Tnstant re {
};

 
  uf('msie') f 
u)e Orfor (sKey in usssssssssssssssssot svrs.
ndic even    *si      g fgedEvKey in usssssssssssssssss(g} TYUILibrar
toug #1723064)Key in usssssssssssssId    ibers[i];;;;;;;;; IF    . ty bodbscribenhe"absol";
pgression dFtuplicate = IF    . ty bo)) {
* Rt r=     den"ram {String} key Thdest
    bThege.ing_"ChgedEKey in usssssssssssssssssoc();db{
     
         m_oModulllllllll* Infc, sKey)) {
                 db{
    *Bnot s(oIF    tsfc,gression dFtuplicate =t       toString: functio        db{es
    
   (oIF    ogression dFtuplicate =t for a property usdestebsnt _MO   OR B tr  " fo
rsl{HTMixo 
   issue ct peIE6/IE7,
    *for a property usdestebs     !n = event(DOM, ct pe-v/**
rgin-top fo)t bebscribezoventm     }
            }
    ect     .w(gtveney w);

    ohasOwput the namei*si   .ndecn =-v/*top    }
            }
    r* @typ), w);

  @parts.ref-v/**
rgin-top ignt sair iue,*
   *si     * C
            }
     pro     .ression dFtuplicate = IF    . ty boB tr  " foCsl{HT=  transpaue;
"ram {String} key Thdest IF    . ty boBorsizWid pe=  0pgression dFtuplicate = IF    . ty bowid pe=  2empgression dFtuplicate = IF    . ty boh";
   =  2empgression dFtuplicate = IF    . ty bolefte=  0pgression dFtuplicate = IF    . ty botop dEv-1t  (oIF    .EAN1Chd";
    
 CustomEve      .widge.rridR) stripxpgression dFtuplicate = IF    . ty bo)) {
* Rt r=      }
  ram {String} key Thdest
               ua iiiiiiiDon't bern/closent re {
};

 
       ua      w conloa:,, i"dexOit              ua iiiiiii    ck 
  uplic evec    doeb(g} TYUILibrar
toug #1721755)Key in usssssssssssssId    ibers[i];;;;;;;;;* Ine  webkioString
        * @relicate = Doc    IF    .rs.
  eWpre olet.M 
               Afg.a       a Doc r, n            this.config    a Doc close            this.config  false;
      Case(ny 








rrdest* In IF    T be IF    .rs.
  eWpre oString
        * @relicaen fored event
    /**.ers.
        * onupe b          **
      for a property usdest* Ineen fored event
   str*== "w"CHANGig, sKey)) {
  y usdest* Insuppr:BsCWe  " f, sKey)) {
                 dest* Ine  /**.dul IF    .rs.
  eWpre o, "ben-sect)) {
Tt     * @fndFtuplicate = true;
               
               ua iiiiiiiiiiiiiiiiiiiiisnt wor (sfailEFAUL. if  m_oModulet*
    
                ua iiiiiiiiiiiiiiiiiiiiis a 
  ig = w cmusxecute.
Mflags      
.nctio } else {
                          usent re IF    T* chTg.p       io } else {
                     Id    ibers[i];;;;;;;;;;;;;;;;;;;;;  /**.dul IF    , "ben-sect)) {
Tt     * @fwerCase();

        uKey in usppProperty(usericat)rnn usppProperty(usericat)rnn usen fored event
   str*== "w"fig) {
             Afg.a    ppProperty(usericat)rntrride  " fo)t bei    IF                do {
            // We     // W   (UA.gch oP&&      clf eT1ue enwork ar     uhc D
  owerCase()];
   f eD  * the  DOMs MoS   u/t usuppr:Bs enwork odulesp*
rs*si   rs.
  ev,pre of.&      clf e&      clf e {
  "EFFECE_CO =t  // starC_suppr:BsCWe  " fbytdefault ts tobg fg_suppr:BsCWe  " f :  m_oModule  if (el) {gch oP           do {
    ua 1.8.0 (FF1.5), 1.8.1.0-5 (FF2) w)n't ) {
nenwork in
rs.
  eWpre o.ression dFtuplica   ua 1.8.1.6+ (FF2 fged6+)ecute. el .MO     u/t  wor (sf {
nenwork in
rs.
  eWpre o.rression dFtuplicaWre {n't wwidg:,p re o sniff        patt bhassCustig = ) {
nd event
  s MoSs   Key)) {
         wa     . elFF2OCUMvor dBdP&& Dom.habId    ibers[i];
   bSuppr:Bw"fig) {
             A* Ine  " fo)t bee  " fo)t<= 1.8ndFtuplicate = true;bSuppr:Bw"figddSets all pro//t being 
          
 / AbSuppr:Bw" isGch oWi    (UA.gch oP&&      clatt stro successfNIT":erue,*
  enwork ar     u* chTg.p s enworkd) {
        // starConupe b     {
        /
      DOM, if the         * work odule {
        /
          var nSubscri.subsco> 0) {
 ss":lyexecut succes {
       m = this.evnupe b    packa/**
rs.
bsc && su    ibers[i];
   nTop dE-1t  (trride  " fo)t bei.EAN1Chd";
    
 CustomEve      .widge.rridR)AHOO.uti       }
   e  " fo)t bei. ty botop dEnTop tripxpgression dFtup}
   e  " fo)t bei. ty bolefte=  0pgression d      var oConfig = this.config(k:Booleansibg fct.event = tyexecuting the YAHOO.wi,laIE hct rs // Hel  nod, e{
 ss":laram {HTMexecut Gr ouhi// Hel  nod// Hel  nodIf    g fct.euaQue and bject} user// Hel  nod  auvonee values  .widge. Adows");Cent) {
spinl c
 ss":lyexecut/ star// Hel  nod,his.ear /**
rs.
  es g, sKey Gr ouh// Hel  nod// Hel  nod// starC1Chd" or,e 
    *  "tor more det}y Gr ou ing fgruc/**
g theonloa:,pCHA.nning fct.event = h// Hel  nodAn ()vendulitstr, = Y*
  a   " fo*
    *lespina anil c
 ss":lnstan// Hel  nod, e{*
  aaircute userCont.widge.asCent) {s, ct penning fct.eModule.on// Hel  nodCHA.nctts.refnt ID se}o|return </p>     /**
shi// Hel  nod// Hel  nod<pcgtomE M
g the
 ss":lnstantnt whavnd M i*,dR":lyexecute.
senturlC Object "src" atthobut the nameifrng t
    *s can handoNITofsten for assed  b  miteral con 
// Hel  nod<
    * documentation for more detai/**
    Gr ou ing fgruc/*
  a   " fo*yex hct r tan// Hel  nod<
    * documentation for more) {

   F  g**
    Gr ou ing fgruc/* {
};

 
   g**
 ) {
         INIT": "be     !n =*
rs.
  @ tnil c,dR":lyexecutnt or,e 
    *  m = this.e1Chd" or,packa/**
rs. Gr ou ing fg   ua i"dexOf('o  *am_oFoot        g fct.e|| (trrid          );
       }

beNDER":P@feonfig) { Gr ou ing fgng_"CN   perty's change eventm_oFootoModule.onost"pgression dFtuplicam_oFootoes
    
   ( Gr ou ing fg t.
        */
        toString: functiom_oFootoModule.onost Gr ou ing fg  sqIdibeAll();
                }
    _ @csubc o they can  Checkscttrrid_ @csub     }

    */
    RtsizeMoNITof destroy: nt repreireEvent
 ) {

 Gr ou ing fg t.
        */
     TROY": ing fgt.even) {

   ression d      var oConfig = this.cAhct rs , e{
 ss":laram {HTMexecut Gr ouhiIf    g fct.euaQue and bjig = this.cect} userConuvonee values  .widge.// Hel  nod// starC
       d" or,e 
    *  "tor more det   " fo*| ) {

   F  g**
   aram {HT  */
    :Motan// Hel  nod hct r tanecut Gr ouhiI  /**
rty.evena* {
};

 
   g**
       {
        *
    @c g, sKey   g**
 ) userConuoNDER":lyexecut Gr ouh// Hel  nom = this.e
       d" or,packa/**
rs.
    :M   ua i"dexOf('o  *am_oFoot        g fct.e|| (trrid          );
       }

beNDER":P@feonfim_oFootoes
    
   (
    :M AHOO.uti       }
   nt repreireEvent
 ) {


    :M AH
        */
     TROY": ing fgt.even) {

   ression d      var oConfig = this.config(k:Booleansibea        EVEyexecute.ono YAHOO.wihi// Hel  nod// Hel  nodIf    ea   uaQue and bject} user  auvonee values  .widge. // Hel  nod// Hel  nodAdows");Cent) {
spinl c
 ss":lyexecut/ stard,his.ear /**
rs.
  es g, sKeyTigCh// Hel  nod// starC1ChBndment orstruc/or more det}yTigC ing fgruc/*
  aeonloa:,pCHA.nninea        EVE// Hel  nodAn ()vendulitstr, = Y*
  a   " fo*
    *lespina anil c
 ss":lnstan// Hel  nod, e{*
  aaircute userCont.widge.asCent) {s, ct penninea   uodule.on// Hel  nodCHA.nctts.refnt ID se}o|return </p>     /**
sh// Hel  nod// Hel  nod<pcgtomE M
g the
 ss":lnstantnt whavnd M i*,dR":lyexecute.
senturlC Object "src" atthobut the nameifrng t
    *s can handoNITofsten for assed  b  miteral con 
// Hel  nod<
    * documentation for more detai/**
   TigC ing fgruc/*
  a   " fo*yex d *        }
   bjec.toL {
        *
    g, sKeyTigC/
    :M.// Hel  nod<
    * documentation for more) {

   F  g**
   TigC ing fgruc/* {
};

 
   g**
 ) {
         INIT": "be     !n =*
rs.
  @ tnil c,dR":lyexecutendment orstrum = this.e1ChBod tocka/**
rs.TigC ing fg   ua i"dexOf('o  *amB = tru       = t|| (trrid  = true
       }

beNDER":P@feonfig) {TigC ing fgng_"CN   perty's change eventm   }oModule.onost"pgression dFtuplicam   }oes
    
   (TigC ing fg t.
        */
        toString: functiom   }oModule.onostTigC ing fg  sqIdibeAll();
                }
    _ @csubc o they can  Checkscttrrid_ @csub   }

    */
    RtsizeMoNITof destroy: nt repl The elem) {

TigC ing fg t.
        */
     TROY": ing fgt.even) {

   
    Rtsi     var oConfig = this.cAhct rs , e{
 ss":laram {HTMexecutTigChaIf    ea   uaQue and bject}ig = this.c userConuvonee values  .widge.// Hel  nod// starC
       Bndment orstruc/or more det   " fo*| ) {

   F  g**
   aram {HT  */
    :Motan// Hel  nod hct r tanecutTigChaI  /**
rty.evena* {
};

 
   g**
       {
        *
    @c g, sKey   g**
 ) userConuoNDER":lyexecutTigCh// Hel  nod// Hel  nom = this.e
       Bod tocka/**
rs.
    :M   ua i"dexOf('o  *amB = tru       = t|| (trrid  = true
       }

beNdexOf('oDER":P@feonfim   }oes
    
   (
    :M AHOO.uti       }
   nt repl The elem) {


    :M AH
        */
     TROY": ing fgt.even) {

   ression d      var oConfig = this.config(k:Booleansibg fct.event = tyexecute.ono YAHOO.wi,laIE hct rs // Hel  nod, e{
 ss":laram {HTMexecutNGEAN_ftIf    g fct.euaQue and bject} user// Hel  nod  auvonee values  .widge. Adows");Cent) {
spinl c
 ss":lyexecut/ star// Hel  nod,his.ear /**
rs.
  es g, sKeyNGEAN_f// Hel  nod// starC1ChF    }

        /**r more det}yNGEAN_ ing fgruc/*
  aeonloa:,pCHA.nning fct.e// Hel  nodAn ()vendulitstr, = Y*
  a   " fo*
    *lespina anil c
 ss":lnstan// Hel  nod, e{*
  aaircute userCont.widge.asCent) {s, ct penning fct.euodule.on// Hel  nodCHA.nctts.refnt ID se}o|return </p>     /**
sh// Hel  nod// Hel  nod<pcgtomE M
g the
 ss":lnstantnt whavnd M i*,dR":lyexecute.
senturlC Object "src" atthobut the nameifrng t
    *s can handoNITofsten for assed  b  miteral con 
// Hel  nod<
    * documentation for more detai/**
   NGEAN_ ing fgruc/*
  a   " fo*yex hct r tan// Hel  nodnning fct.// Hel  nod<
    * documentation for more) {

   F  g**
   NGEAN_ ing fgruc/* {
};

 
   g**
 ) INIT": "beumentation      !n =*
rs.
  @ tnil c,dR":lyexecutg fct.// Hel  nom = this.e1ChF dstriccka/**
rs.NGEAN_ ing fge  i:,pre of  ct pexcoFge.ing_M, .widge.ing|| (trriddge.ing_MOrop * @constrbeNDER":P@feonfig) {NGEAN_ ing fgng_"CN   perty's change eventm @consoModule.onost"pgression dFtuplicam @consoes
    
   (NGEAN_ ing fget.
        */
        toString: functiom @consoModule.onostNGEAN_ ing fg  sqIdibeAll();
                }
    _ @csubc o they can  Checkscttrrid_ @csub @constr    */
    RtsizeMoNITof destroy: nt rep       "CHAN) {

NGEAN_ ing fget.
        */
     TROY": ing fgt.even) {

   
    Rtsi     var oConfig = this.cAhct rs , e{
 ss":laram {HTMexecutNGEAN_ftIf    g fct.euaQue and bjig = this.cect} userConuvonee values  .widge.// Hel  nod// starC
       F    }

        /**r more det   " fo*| ) {

   F  g**
   aram {HT  */
    :Motan// Hel  nod hct r tanecutNGEAN_ftI  /**
rty.evena* {
};

 
   g**
       {
        *
    @c g, sKey   g**
 ) userConuoNDER":lyexecutg fct.// Hel  nom = this.e
       F    }tocka/**
rs.
    :M   u:,pre of  ct pexcoFge.ing_M, .widge.ing|| (trriddge.ing_MOrop * @constrbeNDER":P@feonfim @consoes
    
   (
    :M AHOO.uti       }
   nt rep       "CHAN) {


    :M AH
        */
     TROY": ing fgt.even) {

   ression d      var oConfig = this.cbefoinig(k:Booleanthe 
    * "being      !n =t wyhipp    *    * @pig = this.c event(*
    }

     nctts.refc(kpect  isGcs. Op*ot ra@ig.hct rs // Hel  nod, e{ }

   yexecut YAHOO.wi g_"Cvent = thi*
  enfoin'es r memuil d// Hel  nod<pc// Hel  nodFer packa/s ct poWi m {String} el The*
  
       /**
hipgu**
 ) {
        uaQREQUIRED.t repr i*,pgu**
 ) i*ommitdge.a  " frn true;
 * chTg.p s  {
           *ue and = event(k:B     he*
  cka/**
rs user  
 / AddSet,pig = this.c edic e "bein 
    *   i"be pro sfailurect property
  miteral con 
<pc// Hel  nodgtomE As g, 2.3.1,DOMs MoS
       /**
h.nctio k:B     sibea     " for shitiallyetrue,*
  mth
 of the he nam*        }
    **leag, sKeyTigC/
    :M,pig = this.ca  "   * oNDER":lyexitorttravoidAfg.addg.hasOwnP        DEFAUL. erue,ig = this.ce he nge.ing_"CHAEAN_Tnot sp,pre o'  g fg ing fgtssfNIT". You }
    u/t bei* usent rees
    }nt      }

 ;

         g.pffvlem=yd,hisute.
Mfl s  {
        yex hct r tanrs.
  </p>    OMsrepresenct property
  miteral con 
    * the he n

        /**r moreould be 
       /**
h  */
    :Moir tan*
rs.
, e{ }

   // Hel  nodC "src" at oNDER":lyexent = thie he nge.i<
    * documentation for more detai/**
   
       /**
h  */
    :Motan*
rs.
, e{ }

   // Hel  nodC "src" at oNDER":lyexent = thie he nge.umentation for more detai/**
   CHAEAN   " fo*OPTIONAL.h  */
    :Motn 
 ig = this.ce ue and       actur aSta  ard* ddard fsettod toString
rsn f  
 / A{luchil"l SucGcs) {
 failureag, sKeye he n

        m = this.ee he ntocka/**
rs.
       /**
, CHAEAN   " fo   u:,pre of  ct pexcmf       of queued properarCase()
       (paue;
/**
o they can  Checksct   }
      paue;
/**
being 
          *   Dom.gall       paue;
/**
behe   sq] bers[i];
    t
 paue;
/**
o            do {
   hey can  Checksct   }paue;
/**
o they can  Checksctttttme._,dRToPaue;
}paue;
/**
,tme.
    :M AH
        */
ksctttttme.
         IN) {

     */
    Rts {
         do {
   hey can  Chec         r"befoing fgt.) {

   ression dksct   }! CHAEAN   " fo   u     */
ksctttttmHAEAN   " fo*       !fndHdP  sqIdibeAll();
                }
       /**
   u     */
kscttttt
       (
       /**
 t.
        */
        beisstarCase();

wa:,No g_"Cv pro
 ss":lns.t representing te <em>O    * @p event(Dom       fail dBdP&& Dom.habChe   }! uperin) {

    configten fofndFtuplicate = true;
   "pecifiog("befoin failT". Musxe YAHOOyt
        g_"Cv   ;nfigvent(k:B  n't     * @p event(DOM.  ""       ct pexcts tobggggggggg  
 / AddSets all pro//t bll();
sqIdibeAll();
             trrid_ @csub     }
CHAEAN   " fo AH
        */
     _ @csub   }
CHAEAN   " fo AH
        */
     _ @csub @constCHAEAN   " fo AHH
        */
     _ @csubw"fig) {
  H
        */
     refoing fgt.) {

   tobggggggggg  
 / A) {
          W   (UA.gch oP&&      clf ebefoinig(k:B fgy = keyCHA.g fct.eMoyexit'aQueuavibebscribe ufoin ing Key)) {
  * ig.pr@param {H.t represig.pr@param {HTe <em>O  bscrid ""configten fo" Key)) {
  * .rridRt.&      clf e&      clf e / starC_ @csub     }&      clf e {
 icbsulbytdefault  /or more detai/**
   CHAEAN   " fo*Op*ot ra. A evt The Cuyexecut/g.pr@param {Hbytdefault ts tobg fg_ @csub     }:  m_oModulCHAEAN   " fo Ftuplicate = tmHAEAN   " fo*  mHAEAN   " fo*||      !fndHdP  tuplicate = ta:,Neloa:,pers ingry     = eyexecute.
s Module n't     * @                }
    g fct.e&& !uperin) {

    config fct.fndFtuplicate = true;a:,T   *M i*, */
ing thWi it'aQem>O event(DOM yeH.tNeloa:,p d *it.ression dFtuplica          
   *  mHAEAN   " fo{
     
         m_oModulllllg) {N     
   o they can  ChecksctttttmHAEAN   " fo{
    *Bnot s(config fct.,       
        */
    Rts {
         toString: functio    mHAEAN   " fo{es
    
   (config fct.f            do {
            // We     // W   (UA.gch oP&&      clf ebefoinig(k:B fgy = keyCHA.ea   uoyexit'aQueuavibebscribe ufoin ing Key)) {
  * ig.pr@param {H.t represig.pr@param {HTe <em>O  bscrid ""configten fo" Key)) {
  * .rridRt.&      clf e&      clf e / starC_ @csubBndment orstrf e {
 icbsulbytdefault  /or more detai/**
   CHAEAN   " fo*Op*ot ra. A evt The Cuyexecut/g.pr@param {H.bytdefault ts tobg fg_ @csubBod tocka/**
rlCHAEAN   " fo Ftuplicate = tmHAEAN   " fo*  mHAEAN   " fo*||      !fndHdP  tuplicate = t   }
    ea   && !uperin) {

    confiea  fndFtuplicate = true;a:,T   *M i*, hgedEvhWi it'aQem>O event(DOM yeH.tNeloa:,p d *it.ression dFtuplica   }
    dge.ing);

   isAstrubeirmHAEAN   " fo,M, .widge.infndFtuplicate = true;
   mHAEAN   " fo{
    *Bnot s(confihgedEv, .widge.inf    */
    Rts {
         toString: functio    mHAEAN   " fo{es
    
   (confiea  f            do {
            // We     // W   (UA.gch oP&&      clf ebefoinig(k:B fgy = keyCHA.g fct.euoyexit'aQueuavibebscribe ufoin ing Key)) {
  * ig.pr@param {H.t represig.pr@param {HTe <em>O  bscrid ""configten fo" Key)) {
  * .rridRt.&      clf e&      clf e / starC_ @csubF    }

       f e {
 icbsulbytdefault  /or more detai/**
   CHAEAN   " fo*Op*ot ra. A evt The Cuyexecut/g.pr@param {Hbytdefault ts tobg fg_ @csubF    }tocka/**
rlCHAEAN   " fo Ftuplicate = tmHAEAN   " fo*  mHAEAN   " fo*||      !fndHdP  tuplicate = t   }
    dge.ing);
!uperin) {

    confidge.infndFtuplicate = true;a:,T   *M i*, dge.inEvhWi it'aQem>O event(DOM yeH.tNeloa:,p d *it.ression dFtuplicamHAEAN   " fo{es
    
   (confidge.inf    */
    RtsWe     // W   (UA.gch oP&&      cl ebemoveig(k:Booleantaram {HTdoNITent(DOM,     /. el **lea     !n =toperty.ca  "purgeig(k:Bb" fo "be     !nag, ing fgs      
   * the C}nthe CRefn  INIT"umentation for morebuchil"l sh. eowPurget reproNIT.toLo, e{
 ue;
 * chTg.'aQDOM ing fgs      
 hipp purged.t reddSet,p     re  bscrid ". el **le @c ipp b anipurgedag, DOM ing fgs      
  d// Hel  nodgtomE   */CUM    "ang h. eowPurge"/CUM ,*   opebser tan*
at may" at being uoyui call"purgeC**le @c"/CUM   bscaiettod B trward *ntmpat{
* Rt rct pebehavt = ent = thi2.9.0h// Hel  nom = this.e  INIT"tocka/**
rs. h. eowPurge   u:,pre of  ct pexc
 ue;
EKey in ussssssssspurgeC**le @c*  !. h. eowPurge   tuplicate = t   }
    
    :M   ua i"dexOf('ooooog fgt.purge "\"></dconfigten for purgeC**le @cf            do {
 
 ue;
        !fndHdP&paue;
/**
  sqIdibeAll();
                }paue;
   ua i"dexOf('ooooopaue;
.remove 
   (confi
    :M AH
        */
We     // H
        */
     * chTg.pg,ertyAH
        */
              ertyAH
        */
       = truertyAH
        */
     dge.ing_MertyAHH
        */
en fored event
    /**.uners.
        * onupe b           AHOO.uti       }
   nfg   INIT" 
         AfgQueuewg       rtyAHH
        */
euewg  INIT": "bei) {

   
    Rtsi     var oConfig = this.cnSIGig(k:Booleantaram {HTby r* @typg(k:B)) {
   nthe C* Moduleonostothe*pffvlem=yd,hiproN. Al = ) {
ig(wo odules:, doms"being fir ent = thionostothe*p(k:B)) {
* Rt rsute.
 Object "ng fir  its f// Hel  nod// starC1 "n// Hel  nom = this.e1 "ng.hasOwnP        DEFAUL.    }
   nfg r* dass ffva     }
  **
        */
   i     var oConfig = this.cgnSIig(k:Booleantaram {HTby r* @typg(k:B)) {
   nthe C* Moduleonostothe*pffvlem=yd,hiddSet. Al = ) {
ig(wo odules:, doms" fct.eventent = thionostothe*p(k:B)) {
* Rt rsute.
 Objec clat stro its f// Hel  nod// starC cla// Hel  nom = this.e clag.hasOwnP        DEFAUL.    }
   nfg r* dass ffva     }
  **dBdProgression d}     /**
rs.
  Sent / BUILT-IN l     HANDLERS FOR obeupo{
        bsonfig = this.ced in,  ing fg successf = sute.typg(k:B)) {
* Rt rffvlem=ydvena*ig = this.cen fore Bt(k:B             "a **ing the sct chtypg(k:B"display"  ty br// Hel  nod  tw  .w"block"Objec"non
rs.
  @   t*bsnt whavnd M i*{
ipon {
   f{HTMinge.it "ng fir  jec clat strf// Hel  nod/*r moreould be 
      */prewsilent W
    (usura@ig, e{
fvlem=ydn   p// Hel  nod/*r more    va[]}*,pgs   */prewsilent W,pgu**
   dFer nthe C* Moduleonostothe*p succesxOf(pgs[0]s userequr a, e{new@ig.hcl.wi ct rhreacgpack
fvlem=yf// Hel  nod/*r more    var nSubscri.subsco> 0)  dFer nthe C* Module succesxOfonostothe*p(kt wor (susura@igequr a, e{owd toString
rsn fhavnd Mbeing 
    *n// Hel  nom = this.ebeing 
    *ng.hasOwnP   
   Of(pgsbsc && su DEFAUL.    pexc)) {
   =f(pgs[0]             A* In)) {
  o they can  Checksct  (         r"nSIGNATU  ) {

 ndFtuplicate = true;
   uperr* Sty bdconfigten for "display",w"block" ct pexcts tobgggggggggconfisSIGNATU  ) {

             do {
            // W       toString: functio   }
    ent.signSIGNATU ) {

 ndFtuplicate = true;
   uperr* Sty bdconfigten for "display",w"non
r ct pexcts tobgggggggggconfihnSIGNATU ) {

             do {
            // We     // W   (UA.gch oP&&      cl.ced in,  ing fg successf = (k:B" subsc";

         g.pffvlem=y// Hel  nod/*r moreould be 
      */prewsilent W
    (usura@ig, e{
fvlem=ydn   p// Hel  nod/*r more    va[]}*,pgs   */prewsilent W,pgu**
   dFer nthe C* Moduleonostothe*p succesxOf(pgs[0]s userequr a, e{new@ig.hcl.wi ct rhreacgpack
fvlem=yf// Hel  nod/*r more    var nSubscri.subsco> 0)  dFer nthe C* Module succesxOfonostothe*p(kt wor (susura@igequr a, e{owd toString
rsn fhavnd Mbeing Esubsc = evyObeom = this.ebeing Esubscg.hasOwnP   
   Of(pgsbsc && su DEFAUL.         _ca *edEsubscs dEv}
   na *eEsubscs) ?      _cANGE+ subscs((pgs[0]) :uertyAH
       W   (UA.gch oP&&      clf e reproNITreacgContent",
s ( jecAniITinstastru) ipp ca *ed erue," subsc";  "CHAOf("windidRt. &      clf e reddSet,p  ! instastru ipp c.widge.eas.
,ime /**
rs.
gContowindows ap     o, ,*   w       &      clf ebehavt = ent = thi2.9.0h &      clf 

       f e {
        a *eEsubscs

       f e i"dexO2.9.0

       f e nt ID se} true,
    * ll;
    borthe C'sei     om = this.eba *eEsubscs :uproNI  (UA.gch oP&&      clf ep"BSIZ 
   spt Whet*reacgContent",
 instastru doNITent(  bscridebeing s

       f e&      clf e / starC_cANGE+ subscsbytdefault  /or moreApt W|    var  subscC   Adownt",
 nthe C* Modulect spt Whet*wnt",
 nthe C* Modulsbytdefault  /  
 / A{Apt W} Adospt Whet*reacgContent",
 instastru.

       f e {
 icbsulbytdefault ts tobg fg_cANGE+ subscstocka/**
rl subscC  & su DEFAUL.    pexc subscInstastru    rtyEKey in usssssssssntsizepacka/**
rs.
 iEKey in usssssssss su  tuplicate = t   } subscC  & su DEFAUL.     = t   } subscC   instastret*Apt WnCase();

        t
     subscInstastru   []ct pexcts tobgggggggggc*   subscC  .lengthct pexcts tobgggggggggeacg(i*  0; i < n; i++ndFtuplicate = true;
        su*   subscC  [i]ct pexcts tobggggggggg = t   } su. subscndFtuplicate = true;
            subscInstastru[ subscInstastru.length]*   su. subsc(    ** su.d* ModulfwerCase();

        uKey ippProperty(usericat)rn false;
      Case()lse;
     subscC  . subscndFtuplicate = true;
    subscInstastru   [ subscC  . subsc(    ** subscC  .d* Modulf]ct pexcts tobggggg;
sqIdibeAll();
               
 / A subscInstastru    */
   i     var oConfig = this.ced in,  ing fg successf = (k:B"E.ent(kperced";

         g.pffvlem=y// Hel  nod/*r moreould be 
      */prewsilent W
    (usura@ig, e{
fvlem=ydn   p// Hel  nod/*r more    va[]}*,pgs   */prewsilent W,pgu**
   dFer nthe C* Moduleonostothe*p succesxOf(pgs[0]s userequr a, e{new@ig.hcl.wi ct rhreacgpack
fvlem=yf// Hel  nod/*r more    var nSubscri.subsco> 0)  dFer nthe C* Module succesxOfonostothe*p(kt wor (susura@igequr a, e{owd toString
rsn fhavnd Mbeing ropeust b     = evyObeom = this.ebeing ropeust b    g.hasOwnP   
   Of(pgsbsc && su DEFAUL.    pexcm)t bei   (pgs[0]             A* Inm)t bei_oModuleun).clt SNode( retdEve  " fo)t beir a   */
    Rtsi       toString: functioen fored event
    /**.uners.
        * onupe b          **
        */
    RtsSNode( ree  " fo)t bei   ertyAH
        */
We     // W   (UA.gch oP&&      clf eTnt whavnd M i*, {
 icbsuluhc D
 ,eonloaerue,r memberstypg(k:BDOM embersureaeacgpackm}

   // Hel  n   yex ccou
 
    situModulsn*
rs.
may"causenfg.addg.hasOwnP        DEFAUL.. Iase();

 em>OR<//**
  nod  aIMG_RCHA_cedes
      r memberston.

       f eD":to 
    * nt represpaue;
/**
be <em>Oge.ing_"ChgedEepresenting te < hct relti Mflags MoSaram {H.bytdefault   _TO_DOCUM     eD":to 
    * nt represpaue;
/**
be <rs.
  </p>    presenting te < ddam*        }
    **leatouhc Dto 
    * ntof  ct pfg.addg.hasOwnP        DEFAUL..bytdefault   _TO_DOCUM     bytdefault  /or morepaue;
/**
}ruc/*
  ae
    :Motan*
rs.
, e{
    :Mo userConuddambytdefault  /or moreei/**
   uc/*
  ae
    :Motanl c,dR":lyexpaue;
/**
'sl **le @c&      clf e / starC_,dRToPaue;


       f e {
 icbsulbytdefault ts tobg fg_,dRToPaue;
tocka/**
rlpaue;
/**
,t
    :M   ua i"dexOf('o   }!}
   nfg g* dass ffva es
    }nt      }

 ")g);
paue;
/**
beihege.ing_"Chgedg);
paue;
/**
.N     
   o they can  Checksctpaue;
/**
.
    *Bnot s(gten for paue;
/**
.N     
   oa   */
    Rtsi       toString: functiopaue;
/**
.es
    
   (
    :M AH  */
    Rtsi   */
   i     var oConfig = this.cR 
 / i*, |returne ue and Modulecrepres    vaoString
rsn fhavnd M}o|returString
rsn f  
 / A{ould be scri.returne ue and Modulecrepresel, userCoion ()ts tobg fg}o|returg.hasOwnP        DEFAUL.      
 / A"ooleant;nfie( ret" isGch oWi isGch}  tupli"pecifiang.aug**
 dasto
      ,     elt.M 
  /**P bscrinf  
}trbeN(tarCase();

wa:,preonfig = *g.hasOwnP i*,  }

   y       absol";
@igebscribezovaboparts.      flow. Iasig = *g 
  vendulitstrwhavnd uc/**
ebscribeturnbject
    ,*   weser   opodulsn/**
        INIrol use zI a t  elem membT": "being .hasOwn'aQubscribe yexecut true;
        )) {
   viewpr:B g.hasOwnPb anirs.
gCoi*, dyn  ic@igfig The cCase();
fo)t beisst*     isGch big athodul/**
Inn foet Explt sr 6  ele5.xfgtvent.uiMo userConeisst* 
fvlem@ige he nam* boparSELECTa     !n .eisst* @n   spsGc     epreDOMseisst* @ {
   .hasOwneisst* @ten  rs     epreDOMsten foreisst* @that should be set for this      /**
   odule. See.hasOwnP<
    * documentn for more detai/**
        */
    :Module = function (.hasOwneisst* @
        elI*,vc
   ;nt being 
             odule'oweron () INIT": "beument* /**
rs.dEve  " fo)erCase();

            /**
r.hasOwn.ng} The Config can ument* ntatioNITofid it being 
    *ntdule'
    }embers.

on ()ts tob    epreDOMst.hasOwnP=acka/**
rs.
  @     * @prf tosGch oWi    epreDOMst.hasOwnaultsoo{
  .  }embers.
.alue(    **   @     * @prf;isGch}  tuplipexcLangP=a"pecifiangEKey in usprewsilent W=     elt.M 
 rewsilent EKey in us }

   =     epreDOMsten forEKey in uslent W=     elt.M 
lent EKey in usupeW=     elt.M 
Dom Key in usp;nt be=     elt.M 
    *  Key in usUAe=     elenv.ua Key in us.hasOwnP=a    epreDOMst.hasOwn     var oC_SUBSCRIBEost"ers.
    ueue[i] = n_UNSUBSCRIBEost"uners.
    ueue[i] = n_CONTAINEDost"rs.
gCond"     var oCm_ IF         fnd     var oConfig = this.cConstasModule = function (n   ecrepres hasOwn'aQodules// Hel  nod/*fvlem=ydl    _TYPES// Hel  nod/*f  "EFFECE_CO =od/finalFECE_CO =od/
      elI*erCoion ()ts tobg fgl    _TYPESost   DEFAUL.    "BEFOR    VE": "ent.siMopaueue[i] = null;"  VE": "move"   */
   i     var oConfig = this.cConstasModule = function ( hasOwn'aQ

         g.pffvlem== "// Hel  nod/*fvlem=yd     * @connta// Hel  nod/*f  "EFFECE_CO =od/finalFECE_CO =od/
      elI*erCoion ()ts tobg fg     * @conntaost  ue[i] = null;"X":  beisstarCase();

wkey: "x"tsizepacka/**
rs.
 ct idabeissLang.isNumb
 ,eizepacka/**
rs.
 suppe =slent :uproNIeizepacka/**
rs.
 supsooedes: [ie     p]H  */
    Rtsi, ue[i] = null;"Y":  beisstarCase();

wkey: "y"tsizepacka/**
rs.
 ct idabeissLang.isNumb
 ,eizepacka/**
rs.
 suppe =slent :uproNIeizepacka/**
rs.
 supsooedes: [ie     p]H  */
    Rtsi, ue[i] = null;"XY":  beisstarCase();

wkey: "xy"tsizepacka/**
rs.
 suppe =slent :uproNIeizepacka/**
rs.
 supsooedes: [ie     p] H  */
    Rtsi, ue[i] = null;"CONTEXT":  beisstarCase();

wkey: "rs.
 xt"tsizepacka/**
rs.
 suppe =slent :uproNIeizepacka/**
rs.
 supsooedes: [ie     p] H  */
    Rtsi, ue[i] = null;"FIXED_C   ER":  beisstarCase();

wkey: "Mixodcenn f"tsizepacka/**
rs.
 ct u g.hdSet,pig = this**
rs.
 supsooedes: [ie     p,      }
  ] H  */
    Rtsi, ue[i] = null;"WIDTH":  beisstarCase();

wkey: "wid pueue[i] = null;
  {suppe =slent :uproNIig = this**
rs.
 supsooedes: [irs.
 xt"ts"Mixodcenn f"tsie     p]H  */
    Rtsi,   /**
        "HEIGHT":  beisstarCase();

wkey: "h";
  "tsizepacka/**
rs.
 suppe =slent :uproNIeizepacka/**
rs.
 supsooedes: [irs.
 xt"ts"Mixodcenn f"tsie     p] H  */
    Rtsi, ue[i] = null;"AUTO_FILL_HEIGHT" :  toString: functiokey: "uvonfuseh";
  "tizepacka/**
rs.
 supsooedes: [ih";
  "]tizepacka/**
rs.
 ct u g"}

 "H  */
    Rtsi, ue[i] = null;"ZINDEX":  beisstarCase();

wkey: "   a t"tsizepacka/**
rs.
 ct u g.erty H  */
    Rtsi, ue[i] = null;"CONSTRAIN_TO_VIEWPORT":  beisstarCase();

wkey: "rs.embT":toviewpr:B"tsizepacka/**
rs.
 ct u g.hdSet,pig = this**
rs.
 ct idabeissLang.isluchil",pig = this**
rs.
 supsooedes: [ie     p,  t"ts"y"ts"xy"]H  */
    Rtsi,   /**
        "ase();":  beisstarCase();

wkey: "e     p, izepacka/**
rs.
 ct u g.(e  igbein6 ?  roN g.hdSet),pig = this**
rs.
 ct idabeissLang.isluchil",pig = this**
rs.
 supsooedes: [i   a t"] H  */
    Rtsi, ue[i] = null;"PRl    _CONTEXT_ VERLAP":  toString: functiokey: "of  ct rs.
 xtohasOwp"tizepacka/**
rs.
 ct u g.hdSet,ig = this**
rs.
 ct idabeissLang.isluchil",peizepacka/**
rs.
 supsooedes: [irs.embT":toviewpr:B"]H  */
    Rtsi ression d ;a:,preonfig = *ging at cerCas userCon isGch ie,*
   *si  eisst* @
fvlem=yd    epreDOMst.hasOwnaase();_SRCeisst* @ f 
iceisst* @finalFECE_od/
    |returStrin)ts tob.hasOwnaase();_SRCost"ent val* p:ddSets";a:,preonfig = *gNumb
 odule = functihow mus.
, e{s*si   shimse();

    EAN1Ch doNITeas.
ig = *gscribvenang.hasOwnP nstastr, ie,pixo*ntdule'
  
fvlem=yd    epreDOMst.hasOwnaase();_SRCeisst* @nt ID se3eisst* @ f 
iceisst* @finalFECE_od/
    Numb
 Strin)ts tob.hasOwnaase();_OFFSETost3;a:,preonfig = *gNumb
 odule = functipackm": mum distastrnang.hasOwnP nstastrse();

    ig = *gebscribezovre fnivCuyexecutb" foar= "ag, sKeyT  u/t 'aQviewpr:B, ie,pixo*ntdule'
  
fvlem=yd    epreDOMst.hasOwnaVIEWPORT_OFFSETeisst* @nt ID se10eisst* @ f 
iceisst* @finalFECE_od/
    Numb
 Strin)ts tob.hasOwnaVIEWPORT_OFFSETost10;a:,preonfig = *gConstasModule = function (top leftecorn"beingofstten for IMG_RCHA_ig = *g

      nction (rs.
 xt/
    :Mot ignm {Hbytde
  
fvlem=yd    epreDOMst.hasOwnaTOP_LEFTeisst* @ f 
iceisst* @finalFECE_od/
    |returStrin)ts tob.hasOwnaTOP_LEFTT=  tl";a:,preonfig = *gConstasModule = function (top r;
   corn"beingofstten for IMG_RCHA_ig = *g

      nction (rs.
 xt/
    :Mot ignm {Hbytde
  
fvlem=yd    epreDOMst.hasOwnaTOP_RIGHTeisst* @ f 
iceisst* @finalFECE_od/
    |returStrin)ts tob.hasOwnaTOP_RIGHTT=  tr";a:,preonfig = *gConstasModule = function (top bottNITleftecorn"beingofstten for IMG_RCHA_ig = *g

      nction (rs.
 xt/
    :Mot ignm {Hbytde
  
fvlem=yd    epreDOMst.hasOwnaBOTTOM_LEFTeisst* @ f 
iceisst* @finalFECE_od/
    |returStrin)ts tob.hasOwnaBOTTOM_LEFTT=  bl";a:,preonfig = *gConstasModule = function (bottNITr;
   corn"beingofstten for IMG_RCHA_ig = *g

      nction (rs.
 xt/
    :Mot ignm {Hbytde
  
fvlem=yd    epreDOMst.hasOwnaBOTTOM_RIGHTeisst* @ f 
iceisst* @finalFECE_od/
    |returStrin)ts tob.hasOwnaBOTTOM_RIGHTT=  br";a:,pre.hasOwnaPRl    _ VERLAP_Xost   DEFAUL. tltr":uproNIig = this blbr":uproNIig = this brbl":uproNIig = this trtl":uproNig = }AH  */
    Rts:,pre.hasOwnaPRl    _ VERLAP_Yost   DEFAUL. trbr":uproNIig = this tlbl":uproNIig = this bltl":uproNIig = this brtr":uproNig = }AH:,preonfig = *gConstasModule = function (nt ID seCSS  {
   IMG_RCHA_ang.hasOwnbytde
  
fvlem=yd    epreDOMst.hasOwnaCSS_ VERLAYeisst* @ f 
iceisst* @finalFECE_od/
    |returStrin)ts tob.hasOwnaCSS_ VERLAYT=  yui-ohasOwy";a:,preonfig = *gConstasModule = function (nt ID sedows apCSS  {
   IMG_RCHA_ang.hasOwn.eTnt w {
    s  {
  *g.hcl.wi yexecutohasOwn'aQou.ingDIVaeruesvrs.
.'aQdows atdule'
bytde
  
fvlem=yd    epreDOMst.hasOwnaCSS_HIDDENeisst* @ f 
iceisst* @finalFECE_od/
    |returStrin)ts tob.hasOwnaCSS_HIDDENT=  yui-ohasOwy-   den"ram {Stonfig = *gConstasModule = function (nt ID seCSS  {
   IMG_RCHA_ang.hasOwn{s*si   shimtdule'
 bytde
  
fvlem=yd    epreDOMst.hasOwnaCSS_ase();eisst* @ f 
iceisst* @finalFECE_od/
    |returStrin)ts tob.hasOwnaCSS_ase();
=  yui-ohasOwy-e     pram {Stonfig = s.cConstasModule = function (n   "ag, sKeysta  ard*/g.pr@param {Hsig = s.cusch ie,*
  ohasOwy.ig = s.c 
fvlem=yd    epreDOMst.hasOwnaSTD_MOD_REig = s.c  f 
iceisst=od/finalFECE_Cod/
    RegExDto 
  )ts tob.hasOwnaSTD_MOD_RE
= /^\s*?
TigC|dge.in|g fct.f\s*?$/iram {Stonfig = *gA i"dgletonsprewsilent WIMG_RCHA_reasunctioexecute.
s  ct pCHA_ig = *g,pre o valollFECE_od/  ct p    epreDOMst.hasOwna,pre oSalollEdule {
  )ts tob.hasOwna,pre oSalollEdule   eewsprewsilent (",pre oSalollparam {Stonfig = *gA i"dgletonsprewsilent WIMG_RCHA_reasunctioexecute.
s  ct pCHAig = *g,pre o rb     = evod/  ct p    epreDOMst.hasOwna,pre oent
    /** {
  )ts tob.hasOwna,pre oent
    /**   eewsprewsilent (",pre oent
  param {Stonfig = *gTcute.
s  ct p successonloa:,p) {
nd */prewsilent WCHA_,pre o valollFECE_od/havnd M    epreDOMst.hasOwna,pre oSalollHsucces {
  * @ f 
iceisst* @
      DOM, if the         valoll odule {
  )ts tob.hasOwna,pre oSalollHsuccesP=acka/**
rs.
f tosGch oWipexc*     /**.OMsTarOMs(e   tuplicate// - Webkio (f('msi 2/3)ecutefg.ad 9.2x bub
   valoll oduleu doNIT     !n =top,pre otuplicate// - FF2/3ecuteIE6/7,efg.ad 9.5xe {n't bub
   valoll oduleu doNIT     !n =top,pre otuplicate// - IEe {e n't recognork valoll reg    nam*oevent(k:B     .tuplicate//tuplicate// Al =ir iue, {
};

 
view;  "Calollid "IEe {e n't   bscri a tarOMs,pig = this// rb tag, sKeyT  u/t sdCHA.narOMs=top,pre o.k:B     heape o doNITog.ad ig = this// fo)t b    /narOMs=top,pre o.ig = this   }!}*||  beihe,pre o ||  beihe,pre o.k:B        ua i"dexOf('o   }e  ig   u:,pre of  ct this   }!e,pre o.CalollEn o they can  Checkscttttt,pre o.CalollEn  dE-1            do {
   hey can  Checkscts.earTimeou.(,pre o.CalollEn oeNdexOf('oDER":P@feonfitttt,pre o.CalollEn  dE   Timeou.(tarCase();

woDER":P@feonfitttttttt.hasOwna,pre oSalollEdule ) {

  oDER":P@feonfitttt}, 1oeNdexOf('oDER":P@feonfii       toString: functio.hasOwna,pre oSalollEdule ) {

  DER":P@feonfii
P@feonfii
P@fe ;a:,preonfig = *ging e.
s  ct p successonloa:,p) {
nd */prewsilent WCHA_,pre o rb     = evod/havnd M    epreDOMst.hasOwna,pre oent
  Hsucces {
  * @ f 
iceisst* @
      DOM, if the         * work odule {
  )ts tob.hasOwna,pre oent
  HsuccesP=acka/**
rs.
f toig = this   }e  ig   uER":P@feonfi   }!e,pre o.* workEn o they can  Checksct,pre o.* workEn  dE-1            do  hey can  Checs.earTimeou.(,pre o.* workEn o  tuplicate = t,pre o.* workEn  dE   Timeou.(tarCase();

wtoString: functio.hasOwna,pre oent
    /**.) {

  oDER":P@feonfi}, 100ogression d}       toString: fun.hasOwna,pre oent
    /**.) {

  
P@feonfii
P@fe ;a:,preonfig = *gA borthe vent.uiedic eloaeruthin ing ,pre o rb    nbjectaloll oduleu hav  ig = *g    * @pb  .wers.
    oa:,tdule'
  
fvlem=yd    epreDOMst.hasOwna_istr*== "w"dule'
  
f  "EFFECE_od/
    Borthe C'sei)ts tob.hasOwna_istr*== "w"    rtyAHH
      }.hasOwna_istr*== "w"  =   rty

wtoString:  /**.dulwpre o, "taloll",b.hasOwna,pre oSalollHsucces  
P@feonfi  /**.dulwpre o, "ben-sect).hasOwna,pre oent
  Hsucces  
P@feonfi.hasOwna_istr*== "w"   ) {
      }am {Stonfig = s.cInn foal mapag,  YAHOal oduleW
   s, c
rs.
  @   bscridig = s.che nameinstastr. Iasmap      oduleW
    yexecut tewsi oduleWig = s.cinstastr. Cs.
gCoi*uler= "af = (k:B",pre oSalollp, ",pre oent
  pnbjeig = s.c"d event
  "  t 
ic
rs.
gContooduleu.ig = s.ig = s.c 
fvlem=yd    epreDOMst.hasOwna_TRIGGER_MAPFECE_Cod/
      elI*erCoio.c  f 
iceisst=od/*f  "EFFECE_C)ts tob.hasOwna_TRIGGER_MAPost   DEFAUL. ,pre oSalollp :o.hasOwna,pre oSalollEdule,  DEFAUL. ,pre oent
  " :o.hasOwna,pre oent
    /**Iig = this t event
  "   :oen fored event
    /**
P@fe ;a:,pre    eleen  r}.hasOwn,       , toig = thisoP&&      clf eD":to 
    * ntApt Whet*dd in,  ing fg
   s c
rs.
 userer=gg }

       f ers.
 xt/t ignm {Haf = (k:B.hasOwn{o{
  .&      clf eD_TO_DOCUM     eD":    spt Wh  "ws");Cbt(k:B    af = .hasOwn  DOCUM     ehWi mayCon opul eloaie,fusurear   asetig = o{
  es een  rturString
rs *g.hasOwnPc
rs.
neloa:,pk:BCon (k:ir{owddCHA.et*rONTEXT_TRIGGERSString
rs *ge();

 rs.c eln el (k:ir{supso o{
  'aQueuto
   .rONTEXT_TRIGGERSOR<//**
  nodct rhrct penniir{owddspt Whet*ct rh .&      clf eD_TO_DOCUM     eD":_DOCUM     eE.g.:_DOCUM     eDcode>prewsi.hasOwnaueuto
   .rONTEXT_TRIGGERSO=a    epreDOMst.hasOwnaueuto
   .rONTEXT_TRIGGERS.rs.c e([ ,pre oSalollp]);</code>&      clf eD_TO_DOCUM     e_DOCUM     e 
fvlem=ydrONTEXT_TRIGGERSString
rs *g/
    Apt WString
rs *g/finalFECE_CO =()ts tobg fgrONTEXT_TRIGGERSO: []     var oConfig = this.cTk:B.hasOwn{istr*== "   g.p*
  aairc
rs.
ies r memG_RCHA_.hasOwnPbjecpig = this.ca el Moduswerso{
  es.eTnt whavnd M i*,vonee values  allidche nameig = this.c  }embers.
Of("wib    /upca el    * t The Cuc/**
ere-m {String} el Theig = this.caelem"BSIZ 
represen**
g the Module <em>O    * @ple = fuoString
rsn fhavnd MistrString
rsn fthat should be set for this      /**
   odule. See.hasOwnP<
    * documentg
rsn fthat sh detai/**
        */
    :Module = function (.hasOwneisstg
rsn fthat sh  elI*,vc
   ;nt being 
             odule'oweron ()ig = this.c  }tT": "being rs.dEve  " fo)erCase();

            /**
r.hasOwn.nig = this.cg} The Config can ntatioNITofid it being 
    *ntdule'O =()ts tobg fgistr:acka/**
rs.
  @     * @prf totoString: fun/*toString: funnnnnnNot  y    wee {n't 
 sseing     The Conaie,   *MyHA.eecausenwetoString: funnnnnn.toLowasMoits r memG_Rostr,  
    *lowb taerso{
   lingltoString: fun)tstoString: fun.hasOwnaultsoo{
  .istr.alue(    **  /* @     * @pr*/ AHOO.uti       }
   ent.siIstr  /**.) {

.hasOwn AHOO.uti       uperaddC{
  dconfigten for .hasOwnaCSS_ VERLAY   tuplicate = t   }     * @prf tosGch oWii       }
   nfg .hcly * @pr}     * @pr**
        */
    Rts;
                }
      fnt.smbeingmac"g);
e  gecko   u:,pre of  ct this   }!e * @pr.    * @Srs.
    o(confisSIGNATU ,ig = this**
rs.
     }
   sSIGMacGeckoSalollbasxOf        u:,pre of  ct this    }
   sSIG  /**.ers.
        * sSIGMacGeckoSalollbasxOf:,pre of  ct this        }
  **
                 do {
   hey can  Checksct   }!e * @pr.    * @Srs.
    o(confihnSIGNATUOf:,pre of  ct this    confihnSIMacGeckoSalollbasxOf        u:,pre of  ct this    }
   hnSIGNATU ers.
        * hnSIMacGeckoSalollbasxOf:,pre of  ct this        }
  **
                 do {
         do {
   hey can  Chece( retdEv  /**.) {

.hasOwn AH  do {
       /**
rs.
  Sent nfig = this.cIstr*== "wsxecut tewsi odulesRCHA_.hasOwnPc
rs.
  @ fNIT"cpig = this.cavonee values a * oNfvlri el (i  "ahe name.hasOwn{o{
  .&      cln fhavnd MistrEdules// Hel  nots tobg fgistrEdules:,tarCase();

wa:,preeeeeeeee.hasOwnaultsoo{
  .istrEdules.alue(                  dopexcSIGNATURE
= prewsilent .LIST  tuplicate = ta**toString: fun*/prewsilent WCNIT"cTnot spname.hasOwn{t whovRt.&      clf evod/  ct pent.siMopa  /**
P@fe  clf evod/that shNumb
 } x x coorrtu"EFFECE_CO =f evod/that shNumb
 } y y coorrtu"EFFECE_CO =f evo/hey can  Chece( reent.siMopa  /**        cANGE+ ent (l    _TYPES.BEFOR    VE
         AfgQueuewgent.siMopa  /**.signasurea=cSIGNATURE  tuplicate = ta**toString: fun*/prewsilent WCNIT"c its pname.hasOwn{t whovRt.&      clf evod/  ct pmopa  /**
P@fe  clf evod/that shNumb
 } x x coorrtu"EFFECE_CO =f evod/that shNumb
 } y y coorrtu"EFFECE_CO =f evo/hey can  Chece( remopa  /**        cANGE+ ent (l    _TYPES.  VE
         AfgQueuewgmopa  /**.signasurea=cSIGNATURE  tuplicate      /**
rs.
  Sent nfig = this.cIstr*== "wsxecut {
  'aQhe Config
   ffvlem== "Pc
rs.
spinl csute.
d   u/t bei* usnction ( hasOwn'aQ ;nt beodule'o(nfg).&      cln fhavnd Mistred in,  ;nt b// Hel  nots tobg fgistred in,  ;nt bg.hasOwnP        DEF:,preeeeeeeee.hasOwnaultsoo{
  .istred in,  ;nt b.alue(                  dopexc      }
   nfg  tuplicate = ta:,Add ohasOwyThe Conaffvlem== "P//hey can  Chectuplicate = ta**toString: fun*/    sbsol";
 x-coorrtu"EFQubscribe crepres hasOwnFECE_CO =f evod/he ConaxFECE_CO =f evod/
    Numb
 StrinCO =f evod/k:B    a rtyFECE_CO =f evo/hey can  Checnfg .dddass ffva     * @connta.X.key,
woDER":           do {
  succes: }
   ne ConX,pig = this**
rs.
 ct idabeiss     * @connta.X.ct idabeitsizepacka/**
rs.
 suppe =slent :u     * @connta.X.suppe =slent ,pig = this**
rs.
 supsooedes:      * @connta.X.supsooedesDER":           do}              doa**toString: fun*/    sbsol";
 y-coorrtu"EFQubscribe crepres hasOwnFECE_CO =f evod/he ConanFECE_CO =f evod/
    Numb
 StrinCO =f evod/k:B    a rtyFECE_CO =f evo/hey can  Checnfg .dddass ffva     * @connta.Y.key,
w            do {
  succes: }
   ne ConY,pig = this**
rs.
 ct idabeiss     * @connta.Y.ct idabeitsizepacka/**
rs.
 suppe =slent :u     * @connta.Y.suppe =slent ,pig = this**
rs.
 supsooedes:      * @connta.Y.supsooedesD           do}              doa**toString: fun*/Adospt Whct penninsbsol";
 xcaeleigebscribes crepres hasOwnFECE_CO =f evod/he ConaxnFECE_CO =f evod/
    Numb
 []StrinCO =f evod/k:B    a rtyFECE_CO =f evo/hey can  Checnfg .dddass ffva     * @connta.XY.key,
w g = this**
rs.
  succes: }
   ne ConXY,pig = this**
rs.
 suppe =slent :u     * @connta.XY.suppe =slent ,pig = this**
rs.
 supsooedes:      * @connta.XY.supsooedesDER":      do}              doa**toString: fun*/D":_DOCUM     un*/    spt Whet*rs.
 xt/tpgu**
  sf = ss.
 xt-sensi callebscribetur. toString: fun*/D/":_DOCUM     un*toString: fun*/D":_DOCUM     un*/    t.smatag, sKeyspt Wh  :eDcode>[ss.
 xtai/**
 OrIi,lahasOwnCorn"b, ss.
 xtCorn"b, spt WOfTr=gg }Edules (op*ot ra),axnOAN1Ch (op*ot ra)]</code>      {
        un*/sKey5yspt Wh     !n =des
    oain 
    *nl low:toString: fun*/D/":_DOCUM     un*toString: fun*/Ddl:_DOCUM     un*/<dt>ss.
 xtai/**
 OrIi &#60;ould b| detai/**
 &#62;</dt>_DOCUM     un*/<dd>A evt The Cuyexecutrs.
 xt/
    :Motan*
rs.
, e{ohasOwyTC "src" at  ign oa(os.
.'aQid).</dd:_DOCUM     un*/<dt>ahasOwnCorn"b &#60;ould b&#62;</dt>_DOCUM     un*/<dd>ing 
 rn"being, e{ohasOwyTc
rs.
iestanl cIMG_RCHA_a ignm {H.eTnt w  rn"be userConu ign oayexecut_DOCUM     un*/
 rn"being, e{rs.
 xt/
    :Mok:BCondche name"ss.
 xtCorn"b"*uleryTc
rs.
fo eows. SuppwnP   .returnct rh 
  @:t_DOCUM     un*/ tr" (top r;
  ),  tl" (top left),  br" (bottNITr;
  ),p    bl" (bottNITleft).</dd:_DOCUM     un*/<dt>ss.
 xtCorn"b &#60;ould b&#62;</dt>_DOCUM     un*/<dd>ing 
 rn"being, e{rs.
 xt/
    :Moc
rs.
iestanl cIMG_RCHA_a ignm {H.eSuppwnP   .returnct rh 
  @ sKeys   ecnh 
s    daf = (k:B"ahasOwnCorn"b"*uleryT bopa.</dd:_DOCUM     un*/<dt>spt WOfTr=gg }Edules (op*ot ra) &#60;Apt W[ould b|prewsilent ]&#62;</dt>_DOCUM     un*/<dd>toString: fun*/D":_DOCUM     un*/Bt(k:B      rs.
 xt/t ignm {Ha i*, ect},ime og.addg.h,/t ignule. See.hasOwnPyexecutrs.
 xt/
    :Moerue,r m
 xt/

         g.pffvlem=yd  "CHAOfHA_,rue,*
  <a hevt="#havnd _t ign">t ign</a>t_DOCUM     un*/havnd M i*invokRt. Howsvrs, you }
  usent reop*ot ra "spt WOfTr=gg }Edules"*uleryT:,pk:BCon (k:
s   ag, ing f"Pc
rs.
C "src"f =ceg, e{ohasOwyTthie -t ignoduselfhct penninrs.
 xt/
    :M. toString: fun*/T      "usefulain situModulsn*
e @ sKeyOwyoWi ing, e{ {
};

 
may"cute.
 Oe =      = eenninrs.
 xt/
    :M'aQubscribe bering}odOO.wi.&      clf evodD/":_DOCUM     un*/D":_DOCUM     un*/    spt Wh}
    }tT": eithin oduleW
    .retur sf = ing f"Pnameinstastr pubs  hh 
(e.r. "    r"nSIG")fHA_prewsilent Winstastru.,Addi*ot ra@ig     o eowturString
rs  un*/3  t 
ic
rs.
gContooduleg
   s ipp b ani fgy = keyCuppwnP   :eDcode> ,pre oent
  ",B",pre oSalollp, "t event
  "</code> (k:BCondc": <a hevt="#ffvlem=y__TRIGGER_MAP">_TRIGGER_MAP</a>t*f  "EFpffvlem=y).&      clf evodD/":_DOCUM     un*/D/dd:_DOCUM     un*/<dt>xnOAN1Ch &#60;Numb
 []&#62;</dt>_DOCUM     un*/<dd>toString: fun*/A 2/
    :MoApt Wh YAHOOyule. SeeXcaeleY,pixo* amou
 "ahe *
rs.
, e{OhasOwyTC "src" atEAN1Ch doNITsKeys ign oa
 rn"b. e.r. [5,0]tEAN1Chs
, e{OhasOwyT5,pixo*nPyexecutleft,P<
   its * doc/t ignule. Seegcale,r m
 xt/

r  
   * the C} fun*/gtomE If usnctionispffvlem=yda  "  rer=gg }s
neloa:,pbeok:BCond, sKeyspt WOfTr=gg }Edules ffvlem=yde();

        toperty  bscaiettod c(kpect spt Whebscribes f = (k:B,pgu**
   d_DOCUM     un*/D/dd:_DOCUM     un*/</dl:_DOCUM     un*_DOCUM     un*/D":_DOCUM     un*/Fer examp  , r* @typg(kispffvlem=yd bsDcode>["img1",  tl",w"bl"]</code>e user_DOCUM     un*/t ignoon ( hasOwn'aQtop leftecorn"beyexecutb"ttNITleftecorn"being    {
        un*/rs.
 xt/
    :Moet peid "img1".&      clf evodD/":_DOCUM     un*/D":_DOCUM     un*/S* @typg(kispffvlem=yd bsDcode>["img1",  tl",w"bl",perty.c[0,5]</code>e user_DOCUM     un*/t ignoon ( hasOwn'aQtop leftecorn"beyexecutb"ttNITleftecorn"being    {
        un*/rs.
 xt/
    :Moet peid "img1",.a  " frntEAN1Ch iHTby 5,pixo*nPoevent(Y axisp(  bscrturnb 5,pixo* gapd  tw  .wecutb"ttNITing, e{rs.
 xt/
    :Moa  " opeing, e{ohasOwy).&      clf evodD/":_DOCUM     un*/D":_DOCUM     un*/Addile. Seeop*ot ra er=gg }nct rh :sDcode>["img1",  tl",w"bl",p["    r"nSIG", ",pre oent
  p].c[0,5]]</code> _DOCUM     un*/ usere -t igno, e{ohasOwyTebscribe,aeruesvrs.(k:B"    r"nSIG"p    ,pre oent
  " ing f"P  @ fNIT".&      clf evodD/":_DOCUM     un*FECE_CO =f evod/he Conars.
 xtFECE_CO =f evod/
    Apt WString
rs  evod/k:B    a rtyFECE_CO =f evo/hey can  Checnfg .dddass ffva     * @connta.rONTEXT.key,
w g = this**
rs.
  succes: }
   ne ConCs.
 xt,pig = this**
rs.
 suppe =slent :u     * @connta.rONTEXT.suppe =slent ,pig = this**
rs.
 supsooedes:      * @connta.rONTEXT.supsooedesDER":      do}              doa**toString: fun*/Dets min "Pc
uthin      re, e{OhasOwyTC "src" atasthor
d   u/t bei fun*/yexecutrenn feing, e{viewpr:B   u/t bei fun*/_DOCUM     un*/D":Tkispffvlem=ydspinl c    to:D/":_DOCUM     un*/toString: fun*/Ddl:_DOCUM     un*/<dt>
   </dt>_DOCUM     un*/<dd>toString: fun*/To*ulg
   Mixodtrenn feebscribetur_DOCUM     un*/D":_DOCUM     un*/Wfrntulg
  d, sKeyohasOwyTcuser_DOCUM     un*/begebscribezov eenninrenn feingviewpr:B_,rue,istr*==keydisplayid ".nd   u/t bei fun*/ usere caiev eenninrenn feing, e{viewpr:Baeruesvrs.(k:B,pre o  s  {
   bei fun*/Calollid HA_ret
  i.&      clf evodD/":_DOCUM     un*/D":_DOCUM     un*/Ing, e{ohasOwyTiesta,pbonaf = (k:Bviewpr:B, _DOCUM     un*/
.'aQtop leftecorn"be userConu ign oact pennintop leftecorn"being, e{viewpr:B   u/t bei fun*/D/":_DOCUM     un*/D/dd:_DOCUM     un*/<dt>ddSet</dt>_DOCUM     un*/<dd>toString: fun*/To*disg
   Mixodtrenn feebscribetur   u/t bei fun*/Dp>Ing(kispcaseg, e{ohasOwyTspinstuserConeisst bei fun*/renn fam*   , ect-EAN og.addg.h,/by*invokile. SeeDcode>renn f()</code>e*
  aaieisst bei fun*/howsvrsuiMo user  ree caievrenn fam*,rue,*
  ,pre o  s Calollid/ret
  i.&      clf evodD/dd:_DOCUM     un*/<dt>"rs.
gCond"<dt>_DOCUM     un*/<dd>io*ulg
   Mixodtrenn feebscribetur,*   wt penninDcode>
   </code>eop*ot    u/t bei fun*/Dp>Howsvrs, unlike r* @typg(k:Bffvlem=yd bsDcode>
   </code>, _DOCUM     un*/,rue,*
  ffvlem=yd  "CHAd bsDcode>"rs.
gCond"</code>, ing, e{ohasOwyTies  u/t bei fun*/ye,pbonaf = (k:Bviewpr:B, iMo user  reOMs=,vonee values  enn fam*,rue,*
    u/t bei fun*/    TCalolls HA_ret
  s,*
  ,pre o (u
 il,*
  ,pre o  s larOM*uloughd bs  }tT": *
    u/t bei fun*/ohasOwy)./T      "usefulain casesn*
e @ sKeyOhasOwyT 
  b"th.g fct.e.nd dge.ing  u/t bei fun*/UI  INIrols *
rs.
, e{    Tmay"neloa:,pactru   * the C} fun*/D/":_DOCUM     un*/D/dd:_DOCUM     un*/</dl:_DOCUM     un*_DOCUM     un*//he ConaMixodcenn fFECE_CO =f evod/
    Borthe  | |returString
rsf evod/k:B    afdSetFECE_CO =f evo/hey can  Checnfg .dddass ffva     * @connta.FIXED_C   ER.key,
w g = this**
rs.
  succes: }
   ne ConFixodCenn ftizepacka/**
rs.
 ct u g.     * @connta.FIXED_C   ER.ct u ,pig = this**
rs.
 ct idabeiss     * @connta.FIXED_C   ER.ct idabeitsizepacka/**
rs.
 supsooedes:      * @connta.FIXED_C   ER.supsooedesDER":      do}     do           doa**toString: fun*/CSS wid p crepres hasOwn.&      clf evod/he Conawid pFECE_CO =f evod/
    |returString
rsf evod/k:B    a rtyFECE_CO =f evo/hey can  Checnfg .dddass ffva     * @connta.WIDTH.key,
w g = this**
rs.
  succes: }
   ne ConWid p,pig = this**
rs.
 suppe =slent :u     * @connta.WIDTH.suppe =slent ,pig = this**
rs.
 supsooedes:      * @connta.WIDTH.supsooedesDER":      do}              doa**toString: fun*/CSS h";
   crepres hasOwn.&      clf evod/he Conah";
  FECE_CO =f evod/
    |returString
rsf evod/k:B    a rtyFECE_CO =f evo/hey can  Checnfg .dddass ffva     * @connta.HEIGHT.key,
w g = this**
rs.
  succes: }
   ne ConH";
  ,pig = this**
rs.
 suppe =slent :u     * @connta.HEIGHT.suppe =slent ,pig = this**
rs.
 supsooedes:      * @connta.HEIGHT.supsooedesDER":      do}              doa**toString: fun*/Sta  ard*/g.pr@param {HPc
rs.
C "src",von fuseQou.epresh";
   crepres hasOwn ing, e{h";
   he Conaffvlem=yd  "CHA.&      clf evodSuppwnP   ct rh 
  @ "g fct.",w"b

 "ts"Mge.in".&      clf evo&      clf evod/he Conauvonfuseh";
  FECE_CO =f evod/
    |returString
rsf evod/k:B    a rtyFECE_CO =f evo/hey can  Checnfg .dddass ffva     * @connta.AUTO_FILL_HEIGHT.key,
w g = this**
rs.
  succes: }
   ne ConAvonFuseH";
  ,pig = this**
rs.
 ct rhr:      * @connta.AUTO_FILL_HEIGHT.ct u ,ig = this**
rs.
 ct idabei : }
   _ct idabeAvonFusetizepacka/**
rs.
 supsooedes:      * @connta.AUTO_FILL_HEIGHT.supsooedesDER":      do}              doa**toString: fun*/CSS z-  a t crepres hasOwn.&      clf evod/he ConazI a tFECE_CO =f evod/
    Numb
 StrinCO =f evod/k:B    a rtyFECE_CO =f evo/hey can  Checnfg .dddass ffva     * @connta.ZINDEX.key,
w g = this**
rs.
  succes: }
   ne ConzI a ttizepacka/**
rs.
 ct u g.     * @connta.ZINDEX.ct u DER":      do}              doa**toString: fun*/TroN irepres hasOwn C "src" atof  ct  dafoNITberingebscribezov  u/t bei fun*/oWi ing, e{viewpr:B   u/t bei fun*//he Conars.embT":toviewpr:BFECE_CO =f evod/
    Borthe String
rsf evod/k:B    afdSetFECE_CO =f evo/hey can  Checnfg .dddass ffva     * @connta.CONSTRAIN_TO_VIEWPORT.key,
w            do {
  succes: }
   ne ConCs.embT":ToViewpr:B, _DOCUM     uns.
 ct u g.     * @connta.CONSTRAIN_TO_VIEWPORT.ct u ,pig = this**
rs.
 ct idabeiss     * @connta.CONSTRAIN_TO_VIEWPORT.ct idabeitsizepacka/**
rs.
 supsooedes:      * @connta.CONSTRAIN_TO_VIEWPORT.supsooedesD           do}              doa**toString: fun*//he Cona *si  eisstg
rsf evod/k:val* pibe Borthe  iedic eringc
uthin      re, e{OhasOwyTC "src"eisst bei fun*/hav  e  ase();
shim;sonloa:,pof  ct pSELECTa     !n afoNITeisst bei fun*/pokile. Sroughdang.hasOwnP nstastrsFAUL.6. /WfrntCHAd bs"
   p, izepacka/**
r*/sKeys*si   shimsispc.widge.,rue,*
  .hasOwnP nstastrsFi*inr*==keizepacka/**
r*/mfct )) {
     u/t bei fun*//
    Borthe String
rsf evod/k:B    a roN /**
IE6  elel low**dBdPrRCHA_a eQothin T  u/t s.FECE_CO =f evo/hey can  Checnfg .dddass ffva     * @connta.ase();.key,
w            do {
  succes: }
   ne ConI*si  , _DOCUM     uns.
 ct u g.     * @connta.ase();.ct u ,pig = this**
rs.
 ct idabeiss     * @connta.ase();.ct idabeitsizepacka/**
rs.
 supsooedes:      * @connta.ase();.supsooedesD           do}              doa**toString: fun*//he Conaof  ct rs.
 xtohasOwpeisstg
rsf evod/k:val* pibe Borthe  iedic eringc
uthin      re, e{OhasOwyTC "src"ohasOwpodusweisst bei fun*/rs.
 xt/
    :Mo(k:BCondcusnction (irs.
 xt"/

         g.pffvlem=y)*,rue,*
    u/t bei fun*/irs.embT":toviewpr:B"/

         g.pffvlem=yd  "CHAd bs"
   p   u/t bei fun*//
    Borthe String
rsf evod/k:B    afdSetFECE_CO =f evo/hey can  Checnfg .dddass ffva     * @connta.PRl    _CONTEXT_ VERLAP.key,
w g = this**
rs.
 ct u g.     * @connta.PRl    _CONTEXT_ VERLAP.ct u ,pig = this**
rs.
 ct idabeiss     * @connta.PRl    _CONTEXT_ VERLAP.ct idabeitsizepacka/**
rs.
 supsooedes:      * @connta.PRl    _CONTEXT_ VERLAP.supsooedesDER":      do}     do  do}     var oConfig = this.cMopas
, e{OhasOwyTyexecut YAHOO.wi ebscribe./T    hasOwnP        u/t bei* id funcay  bsaluetypg(kis nfg s* dass ffva xy"ts[x,y]     do  don fhavnd MmopaTo   do  don fthat shNumb
 } x Tk:B.hasOwn's eewsx ebscribe   do  don fthat shNumb
 } y Tk:B.hasOwn's eewsy ebscribe   do  don/hey can  mopaTog.hasOwnP   x, WnCase();

      (kis nfg s* dass ffva xy"ts[x, y]     do  do}     var oConfig = this.cAdd  , CSS  {
   ("hnSI-salollbasx")f("windmopas
, CSS  {
   ig = this.c("sSIG-salollbasx")fyexecutOhasOwyTyexfix
, bu = eeGeckoPoevMac OS X ig = this.c(https://bu zusea.mozusea.org/sSIG_bu .cgi?id=187435)   do  don fhavnd MhnSIMacGeckoSalollbasx   do  don/hey can  hnSIMacGeckoSalollbasxg.hasOwnP        DEFAUL.    uperre isGcC{
  dconfigten for "sSIG-salollbasx"r "hnSI-salollbasx")    do  do}     var oConfig = this.cAdd  , CSS  {
   ("sSIG-salollbasx")f("windmopas
, CSS  {
   ig = this.c("hnSI-salollbasx")fyexecutOhasOwyTyexfix
, bu = eeGeckoPoevMac OS X ig = this.c(https://bu zusea.mozusea.org/sSIG_bu .cgi?id=187435)   do  don fhavnd MsSIGMacGeckoSalollbasx   do  don/hey can  sSIGMacGeckoSalollbasxg.hasOwnP        DEFAUL.    uperre isGcC{
  dconfigten for "hnSI-salollbasx"r "sSIG-salollbasx")    do  do}     var oConfig = this .cInn foal imp  ioNITofid yexCHAd ht )) {
ilityeing, e{ohasOwyT": *
  DOM.&      clf ig = this .c / starC_CHAupeV) {
ilityig = this .c that shborthe } )) {
   Wruthin io sSIG    hnSIoon ( hasOwn'aQou.inggten foig = this .c t
 icbsulbytdefault ts tobg fg_CHAupeV) {
ility tocka/**
rlsSIG     DEFAUL.    uperCHAStyledconfigten for ")) {
ility"r lsSIG  ?      }
   : "hnSden"
         AfgQupexchnSdenC{
   =b.hasOwnaCSS_HIDDEN  tuplicate = t   }sSIG     DEFAUL.        uperremopaC{
  dconfigten for hnSdenC{
  oa   */
    Rtsi       toString: functiouperaddC{
  dconfigten for hnSdenC{
  oa   */
    Rtsi   do  do}     var oCo/ BEGIN BUILT-IN PROPERTYgl     HANDLERSO//tuplicate/nfig = this.cTk:Bdd in,  ing fg successfifam*,rue,*
       }
   ffvlem=yd  "ig = this.csute.
d. /Tnt whavnd M i*resps.e{
   CHA_fiftypgsSIG  /**ig = this.c("wihnSIGNATU    do  don fhavnd M

    V   }
    do  don fthat should be 
      */prewsilent W
    (usura@ig, e{
fvlem=ydn   p// Hel  nod/*r more    va[]}*,pgs   */prewsilent W,pgu**
   dFer nthe C* Modul// Hel  nod succesxOf(pgs[0]s userequr a, e{new@ig.hcl.wi ct rhreacgpack
fvlem=yf// Hel  nod/*r more    var nSubscri.subsco> 0)  dFer nthe C* Module succesxOfonostothe*p(kt wor (susura@igequr a, e{owd toString
rsnm = this.ebeing V   }
 g.hasOwnP   
   Of(pgsbsc && su        AfgQupexc)) {
     (pgs[0]tizepacka/**
rs.
  fgy = V     upergHAStyledconfigten for ")) {
ility")EKey in usssssssss subscs   }
   _cached subscs ||  
   _cANGE+ subscs(}
   nfg g* dass ffva  subsc"))EKey in usssssssssisMacGecko   }
      fnt.smbeingmac"g);
e  gecko EKey in usssssssss    * @Srs.
    o
= p* @pr.    * @Srs.
    oEKey in usssssssss i,  Ofj, kr hEKey in usssssssssnEsubscInstastru  tuplicate = t   } fgy = V    inginhinic")  toString: functioe   }
   
    :M.paue;
/**
             do {
 whi   (e.n**
T    != 9g);
e.n**
T    != 11o they can  Checkscttttt fgy = V     upergHAStyleder ")) {
ility")             do {
  = t   } fgy = V   !inginhinic")  toString: functiooooooooob  *kwerCase();

        uK  hey can  Checksctctioe   e.paue;
/**
  e();

        uK  hey can  Checksct   } fgy = V    inginhinic")  toString: functiotttt fgy = V          }
  ct pexcts tobggggg;
sqIdibeAll();
                }    }
 

woo/ nSIG hey can  Checksct   }isMacGecko)  toString: functiotttt   * sSIGMacGeckoSalollbasx
  DER":P@feonfi  uK  hey can  Checksct   } subscs

woo/ Anim"EFpin           do {
  = t   }    }
 

woo/ Anim"EFpint     resSIGturStoString: functiooooooooooo/ Fadile.oWi  i*, bii ingae sckr hWi didn't wasMothieisk do "beumenting: functiooooooooooo/ sohavn "bebrofct.e(e.rgaefig Tic  
   _anim"E "bOut)reacg2.9.0StoString: functiooooooooo   } fgy = V   !ing    }
   ||  fgy = V    iing" ||  
   _fadileOut)r toString: functiooooooooooooo   }euewgent.siSSIG  /**.) {

    u:,pre of  ct this                nEsubscInstastru   esubscs.length             do {
  = ttttttttttttteacg(j   0; j <snEsubscInstastru  j++)r toString: functioooooooooooooooooooooei   esubscs[j]werCase();

        uKctiooooooooooooo   }j  iin0g);
!    * @Srs.
    o(ei.anim"EeInComp  tIGNATUOf}
   sSIG  /**.) {
Of}
   sSIG  /**))r toString: functioooooooooooooooooooooooooei.anim"EeInComp  tIGNATU.ers.
        * sSIG  /**.) {
Of}
   sSIG  /****
        */
    Rtsssssssssssssssssssssssss}toString: functioooooooooooooooooooooei.anim"EeIn
  DER":P@feonfi  uKssssssssssssssss}toString: functiooooooooooooo}toString: functiooooooooo}toString: functiooooo}toString: functioi       oo/ nSIG oString: functiooooo   } fgy = V   !ing    }
   ||  fgy = V    iing")r toString: functiooooooooo   }euewgent.siSSIG  /**.) {

    uoString: functiooooooooooooo 
   _CHAupeV) {
ility(
        */
    Rtsssssssssssssssss}
   nfg re) {
lent ("e     p     */
    Rtsssssssssssssssss}
   sSIG  /**.) {

     */
    Rtsssssssssssss}toString: functiooooo}       toString: functiooooooooo 
   _CHAupeV) {
ility(
        */
    Rtsssssssss}toString: functioi   */
    Rtsi       oo/ HnSI hey can  Checksct   }isMacGecko)  toString: functiotttt   * hnSIMacGeckoSalollbasx
  DER":P@feonfi  uK  hey can  Checksct   } subscs

woo/ Anim"EFpoWi  fesSIGturSoString: functiotttt   } fgy = V    ing    }
   ||  
   _fadileIn)r toString: functiooooooooo   }euewgent.siHnSIGNATU ) {

    uoString: functiooooooooooooonEsubscInstastru   esubscs.length  oString: functioooooooooooooeacg(k   0; k <snEsubscInstastru  k++)r toString: functioooooooooooooooooh   esubscs[k]werCase();toString: functiooooooooooooooooo   }k  iin0g);
!    * @Srs.
    o(h.anim"EeOutComp  tIGNATUOf}
   hnSIGNATU ) {
Of}
   hnSIGNATU))r toString: functioooooooooooooooooooooh.anim"EeOutComp  tIGNATU ers.
        * hnSIGNATU ) {
Of}
   hnSIGNATU**
        */
    Rtsssssssssssssssssssss}toString: functioooooooooooooooooh.anim"EeOut
  DER":P@feonfi  uKssssssssssss}toString: functiooooooooo}ttoString: functiooooo}         } fgy = V    iing")r toString: functiooooooooo 
   _CHAupeV) {
ility(hdSet)werCase();

        uK  hey can  Checkscti       oo/ nimp  ihnSI            do {
  = t   } fgy = V    ing    }
   ||  fgy = V    iing")r toString: functiooooooooo   }euewgent.siHnSIGNATU ) {

    uoString: functiooooooooooooo 
   _CHAupeV) {
ility(hdSet)werCase();

        uKoooooooo 
   hnSIGNATU ) {

     */
    Rtsssssssssssss}toString: functiooooo}       toString: functiooooooooo 
   _CHAupeV) {
ility(hdSet)werCase();

        uK  e();

        uK  e();

      i   do  do}     var oConfig = this.cFixodtrenn fe  ct p successonloaf = senn file.onTCaloll/ret
  r hWi .toLo   onostothe*p(ke{ohasOwyTies)) {
   suc, ing"Mixodcenn f"d  "CHAd bs"rs.
gCond"  .toLo   onostothe*p(ke{ohasOwyTfduswwt p": *
  viewpr:B   u/t beifig = this.cfhavnd MdoCenn fOnDOM, if String
rsnm = this.edoCenn fOnDOM, if g.hasOwnP        DEFAUL.    pexc      }
   nfgEKey in usssssssssfc   nfg g* dass ffva Mixodcenn f"   tuplicate = t   }nfg g* dass ffva     }
      uoString: functio   }fc );
}fc !iin_CONTAINEDo||  
   fdusInViewpr:B
  )  toString: functiotttt   * renn f()ct pexcts tobggggg;
sqIdibeAll();
ibeAll();     var oConfig = this .cDets min "Pirepres hasOwn (includile. SeeoAN1Ch ct rhrk:BCondche .hasOwnaVIEWPORT_OFFSET)e_DOCUM     eor (sfits nt {
lnP nsnSIoon (viewpr:B, ie,b"th.dimensibes - wid p ("wih";
  .&      clf eig = this .c havnd MfdusInViewpr:Big = this .c return borthe veroN irepres hasOwn or (sfit**dBdPrR     rig = this .m = this.efdusInViewpr:B tocka/**
rl     DEFAUL.    pexcnViewpr:BOAN1Ch = .hasOwnaVIEWPORT_OFFSETEKey in usssssssss     :Mo  }
   
    :MEKey in usssssssss     :MWid p   e    :M.oAN1ChWid p,Key in usssssssss     :MH";
     e    :M.oAN1ChH";
  ,Key in usssssssssviewpr:BWid p   upergHAViewpr:BWid p( EKey in usssssssssviewpr:BH";
     upergHAViewpr:BH";
  (   tuplicate = treturn ((     :MWid p +cnViewpr:BOAN1Ch <sviewpr:BWid p) );
}     :MH";
   +cnViewpr:BOAN1Ch <sviewpr:BH";
  ))    do  do}     var oConfig = this.cTk:Bdd in,  ing fg successfifam*,rue,*
   Mixodcenn f"d
fvlem=ydig = this.cispcute.
d.   do  don fhavnd M

    FixodCenn f   do  don fthat should be 
      */prewsilent W
    (usura@ig, e{
fvlem=ydn   p// Hel  nod/*r more    va[]}*,pgs   */prewsilent W,pgu**
   dFer nthe C* Moduldig = this.c succesxOf(pgs[0]s userequr a, e{new@ig.hcl.wi ct rhreacgpack
fvlem=yf// Hel  nod/*r more    var nSubscri.subsco> 0)  dFer nthe C* Module succesxOfonostothe*p(kt wor (susura@igequr a, e{owd toString
rsnm = this.ebeing FixodCenn fg.hasOwnP   
   Of(pgsbsc && su        AfgQupexc)r a  (pgs[0]tizepacka/**
rs.
     * @Srs.
    o
= p* @pr.    * @Srs.
    oEKey in usssssssss,pre oent
    /**   .hasOwna,pre oent
    /**Iig = thisssssssss,pre oSalollEdule   .hasOwna,pre oSalollEdule  tuplicate = t   }val)  toString: functio   * renn f()ctuoString: functio   }!    * @Srs.
    o(euewgent.siSSIG  /**,o   * renn f )  toString: functiotttt   * ent.siSSIG  /**.ers.
        * renn f  DER":P@feonfi  uK  hey can  Checksct   }!    * @Srs.
    o(,pre oent
    /**It   * doCenn fOnDOM, if Of        uoString: functiotttt,pre oent
    /**.ers.
        * doCenn fOnDOM, if Of    **
        */
    Rtsssss  hey can  Checksct   }!    * @Srs.
    o(,pre oSalollEdule,t   * doCenn fOnDOM, if Of        uoString: functiotttt,pre oSalollEdule ers.
        * doCenn fOnDOM, if Of    **
        */
    Rtsssss  hey can  Chec}       toString: functio   * ent.siSSIG  /**.uners.
        * renn f  DKey in usssssssss,pre oent
    /**.uners.
        * doCenn fOnDOM, if Of         */
    Rtsssss,pre oSalollEdule uners.
        * doCenn fOnDOM, if Of         */
    Rtsi   do  do}     var oConfig = this.cTk:Bdd in,  ing fg successfifam*,rue,*
   h";
    ffvlem=yd  "cute.
d.   do  don fhavnd M

    H";
  FECE_CO =n fthat should be 
      */prewsilent W
    (usura@ig, e{
fvlem=ydn   p// Hel  nod/*r more    va[]}*,pgs   */prewsilent W,pgu**
   dFer nthe C* Moduldig = this.c succesxOf(pgs[0]s userequr a, e{new@ig.hcl.wi ct rhreacgpack
fvlem=yf// Hel  nod/*r more    var nSubscri.subsco> 0)  dFer nthe C* Module succesxOfonostothe*p(kt wor (susura@igequr a, e{owd toString
rsnm = this.ebeing H";
  g.hasOwnP   
   Of(pgsbsc && su        AfgQupexch";
     (pgs[0]tizepacka/**
rs.
 elo  }
   
    :MAHOO.uti       uperCHAStyled
  @ h";
   ,ch";
  
         AfgQueuewgnfg re) {
lent ("e     p     */
   ;     var oConfig = this .cTk:Bdd in,  ing fg successfifam*,rue,*
   uvonfuseh";
    ffvlem=yd  "cute.
d.   do  doon fhavnd M

    AvonFuseH";
     do  doon   do  doon fthat should be 
      */prewsilent W
    (usura@ig, e{
fvlem=ydn   p// Hel  nnod/*r more    va[]}*,pgs   */prewsilent W,pgu**
   dFer nthe C* Moduldig = thiss.c succesxOf(pgs[0]s userequr a, e{new@ig.hcl.wi ct rhreacgpack
fvlem=yf// Hel  nnod/*r more    var nSubscri.subsco> 0)  dFer nthe C* Module succesxOfonostothee*p(kt wor (susura@igequr a, e{owd toString
rssnm = this.ebeing AvonFuseH";
  g.hasOwnP   
   Of(pgsbsc && su       AfgQupexcfuseE a  (pgs[0]tizepacka/**
rs.
       }
   nfgEKey in usssssssssavonFuseH";
      uvonfuseh";
   EKey in usssssssssh";
     "h";
   EKey in usssssssss fgyE a  nfg g* dass ffvaavonFuseH";
   EKey in usssssssss vonFuse   }
   _ vonFuseOnH";
  Cute.
AHOO.uti       nfg uners.
    Fromp* @prlent (h";
  ,p vonFuse
         AfgQuen fored event
    /** uners.
      vonFuse
         AfgQu}
   nute.
Cs.
 nt  /** uners.
      vonFuse
  tuplicate = t   }nfgyE a);
fuseE a!iinnfgyE a);
}
  [nfgyE ]     DEFAUL.        uperCHAStyledconf[nfgyE ],ch";
  ,ng")    */
    Rts;
                }fuseE      DEFAUL.        fuseE a  Lte..uldm}fuseE .toLowerCas

   DKey in usssssssssnfg srs.
    Top* @prlent (h";
  ,p vonFuseOf    [fuseE ]Of         */
    Rtsssssen fored event
    /** ers.
      vonFuseOf    [fuseE ]Of         */
    Rtsssss}
   nute.
Cs.
 nt  /** ers.
      vonFuseOf    [fuseE ]Of       Key in usssssssssnfg s* dass ffvaavonFuseH";
  , fuseE **
        */
    Rts;

    Rts;     var oConfig = this.cTk:Bdd in,  ing fg successfifam*,rue,*
   wid p  ffvlem=yd  "cute.
d.   do  don fhavnd M

    Wid pFECE_CO =n fthat should be 
      */prewsilent W
    (usura@ig, e{
fvlem=ydn   p// Hel  nod/*r more    va[]}*,pgs   */prewsilent W,pgu**
   dFer nthe C* Moduldig = this.c succesxOf(pgs[0]s userequr a, e{new@ig.hcl.wi ct rhreacgpack
fvlem=yf// Hel  nod/*r more    var nSubscri.subsco> 0)  dFer nthe C* Module succesxOfonostothe*p(kt wor (susura@igequr a, e{owd toString
rsnm = this.ebeing Wid pg.hasOwnP   
   Of(pgsbsc && su        AfgQupexcwid p   (pgs[0]tizepacka/**
rs.
 elo  }
   
    :MAHOO.uti       uperCHAStyled
  @ wid p ,cwid p
         AfgQueuewgnfg re) {
lent ("e     p     */
   ;     var oConfig = this.cTk:Bdd in,  ing fg successfifam*,rue,*
   zI a t  ffvlem=yd  "cute.
d.   do  don fhavnd M

    zI a tFECE_CO =n fthat should be 
      */prewsilent W
    (usura@ig, e{
fvlem=ydn   p// Hel  nod/*r more    va[]}*,pgs   */prewsilent W,pgu**
   dFer nthe C* Moduldig = this.c succesxOf(pgs[0]s userequr a, e{new@ig.hcl.wi ct rhreacgpack
fvlem=yf// Hel  nod/*r more    var nSubscri.subsco> 0)  dFer nthe C* Module succesxOfonostothe*p(kt wor (susura@igequr a, e{owd toString
rsnm = this.ebeing zI a tg.hasOwnP   
   Of(pgsbsc && su        AfgQupexczI a t   (pgs[0]tizepacka/**
rs.
 elo  }
   
    :MAHOO.uti          }!ezI a t     DEFAUL.        zI a t   upergHAStylede  @ zI a t      */
    Rtsssss   }!ezI a to|| isNaN(zI a t    uoString: functiottttzI a t   0ct pexcts tobggggg;
sqIdibeAll();
                }e( ret*si   ||  
   nfg g* dass ffva e     p   iin
      uoString: functio   }zI a t <  0   uoString: functiottttzI a t   1            do {
   sqIdibeAll();
             uperCHAStyled
  @ zI a t ,ezI a t          AfgQueuewgnfg s* dass ffva zI a t ,ezI a t**
                 do   }e( ret*si  )  toString: functio   * stackI*si  
  DER":P@feonfii
P@feonfii     var oConfig = this.cTk:Bdd in,  ing fg successfifam*,rue,*
   xy  ffvlem=yd  "cute.
d.   do  don fhavnd M

    XYFECE_CO =n fthat should be 
      */prewsilent W
    (usura@ig, e{
fvlem=ydn   p// Hel  nod/*r more    va[]}*,pgs   */prewsilent W,pgu**
   dFer nthe C* Moduldig = this.c succesxOf(pgs[0]s userequr a, e{new@ig.hcl.wi ct rhreacgpack
fvlem=yf// Hel  nod/*r more    var nSubscri.subsco> 0)  dFer nthe C* Module succesxOfonostothe*p(kt wor (susura@igequr a, e{owd toString
rsnm = this.ebeing XYg.hasOwnP   
   Of(pgsbsc && su        AfgQupexcpos   (pgs[0]tizepacka/**
rs.
 t   pos[0]tizepacka/**
rs.
 y   pos[1]AHOO.uti       }
   nfg s* dass ffva x ,et          AfgQueuewgnfg s* dass ffva y"r n AHOO.uti       euewgent.siMopa  /**.) {

[x, y]   OO.uti       x   }
   nfg g* dass ffva t      */
    Rtsy   }
   nfg g* dass ffva y")             do    eplog(a xy: " +s[x, y]  @ e     p           AfgQueuewgnfg re) {
lent ("e     p     */
   fgQueuewgmopa  /**.) {

[x, y]   
   fgQui     var oConfig = this.cTk:Bdd in,  ing fg successfifam*,rue,*
   x  ffvlem=yd  "cute.
d.   do  don fhavnd M

    XFECE_CO =n fthat should be 
      */prewsilent W
    (usura@ig, e{
fvlem=ydn   p// Hel  nod/*r more    va[]}*,pgs   */prewsilent W,pgu**
   dFer nthe C* Moduldig = this.c succesxOf(pgs[0]s userequr a, e{new@ig.hcl.wi ct rhreacgpack
fvlem=yf// Hel  nod/*r more    var nSubscri.subsco> 0)  dFer nthe C* Module succesxOfonostothe*p(kt wor (susura@igequr a, e{owd toString
rsnm = this.ebeing Xg.hasOwnP   
   Of(pgsbsc && su        AfgQupexct   (pgs[0]tizepacka/**
rs.
 y   }
   nfg g* dass ffva y")             do}
   nfg s* dass ffva x ,et**
        */
    Rtseuewgnfg s* dass ffva y"r n**
                 doeuewgent.siMopa  /**.) {

[x, y]   OO.uti       x   }
   nfg g* dass ffva t      */
    Rtsy   }
   nfg g* dass ffva y")             douperCHAXdconfigten for t**
                 do(kis nfg s* dass ffva xy"ts[x, y]**
                 do(kis nfg re) {
lent ("e     p     */
   fgQueuewgmopa  /**.) {

[x, y]   
   fgQui     var oConfig = this.cTk:Bdd in,  ing fg successfifam*,rue,*
   y  ffvlem=yd  "cute.
d.   do  don fhavnd M

    YFECE_CO =n fthat should be 
      */prewsilent W
    (usura@ig, e{
fvlem=ydn   p// Hel  nod/*r more    va[]}*,pgs   */prewsilent W,pgu**
   dFer nthe C* Moduldig = this.c succesxOf(pgs[0]s userequr a, e{new@ig.hcl.wi ct rhreacgpack
fvlem=yf// Hel  nod/*r more    var nSubscri.subsco> 0)  dFer nthe C* Module succesxOfonostothe*p(kt wor (susura@igequr a, e{owd toString
rsnm = this.ebeing Yg.hasOwnP   
   Of(pgsbsc && su        AfgQupexcx   }
   nfg g* dass ffva t  tizepacka/**
rs.
 y   (pgs[0]             do}
   nfg s* dass ffva x ,et**
        */
    Rtseuewgnfg s* dass ffva y"r n**
                 doeuewgent.siMopa  /**.) {

[x, y]   OO.uti       x   }
   nfg g* dass ffva t      */
    Rtsy   }
   nfg g* dass ffva y")             douperCHAYdconfigten for n**
                 doeuewgnfg s* dass ffva xy"ts[x, y]**
                 do(kis nfg re) {
lent ("e     p     */
   fgQueuewgmopa  /**.) {

[x, y]   
   fgQui  
   fgQu   var oConfig = this.cSSIG"Pnamei*si   shim,e Modul 
  berntulg
  d.   do  don fhavnd MsSIGI*si  eisstg
rsn/hey can  sSIGI*si  :,tarCase();

wa:,preeeeeeeeepexcoIFsi     }
   i*si  tizepacka/**
rs.
 oPaue;
/**
             do   }oIFsi  )  toString: functiooPaue;
/**
   }
   
    :M.paue;
/**
             do {
    }oPaue;
/**
 !=coIFsi  .paue;
/**
)  toString: functiotttt   * _addToPaue;
}oPaue;
/**
,coIFsi  )ct pexcts tobggggg;
sqIdibeAll()ctiooIFsi  .style.display   "block" DER":P@feonfii
P@feonfii     var oConfig = this.cHnSI"Pnamei*si   shim,e Modul 
  berntulg
  d.   do  don fhavnd MhnSII*si  eisstg
rsn/hey can  hnSII*si  g.hasOwnP        DEFAUL.       }e( ret*si  )  toString: functio   * t*si  .style.display   "non  ct pexcts tobgi
P@feonfii     var oConfig = this.cSyncron
  s,*
  t
   ("wiubscribe cres*si   shimsyexecatag, dusweisst bei* c(kpesps.dile..hasOwnP nstastr.   do  don fhavnd MsyncI*si  eisstg
rsn/hey can  syncI*si  :,tarCase();

wa:,preeeeeeeeepexcoIFsi     }
   i*si  tizepacka/**
rs.
 oE    :Mo  }
   
    :MEKey in usssssssssnOAN1Ch = .hasOwnaase();_OFFSETEKey in usssssssssnDimensibeOAN1Ch = (eOAN1Ch * 2 EKey in usssssssss XY             do   }oIFsi  )  toString: functioo/ ni   <i*si  >
sqIdibeAll()ctiooIFsi  .style.wid p   (oE    :M.oAN1ChWid p +cnDimensibeOAN1Ch + "pt      */
    RtsssssoIFsi  .style.h";
     (oE    :M.oAN1ChH";
   +cnDimensibeOAN1Ch + "pt    toString: functioo/ Pbscribe <i*si  >
sqIdibeAll()ctio XY   }
   nfg g* dass ffva ty")             do {
    }!Lte..isApt W( XY) || (isNaN( XY[0])o|| isNaN( XY[1]  )  toString: functiotttt   * syncPbscribe
     */
    Rtsssssssss XY   }
   nfg g* dass ffva ty")  
    Rtsssssssss;
sqIdibeAll()ctiouperCHAXY}oIFsi  ts[( XY[0] -snOAN1Ch  @( XY[1] -snOAN1Ch ])ct pexcts tobgi
P@feonfii     var oConfig = this */S* s,*
  z  a t crepresi*si   shim,e Modulexistsbsbanloaoevent(z  a t crig = this */pres hasOwn 
    :M. T
  z  a t crepresi*si     "CHAd bsb ecnh le   ig = this */pran/pres hasOwn 
    :M's z  a t.&      clf eig = this .c<p>gtomE Tnt whavnd M user  rebump up,*
  z  a t crepres hasOwn 
    :Mig = this */po*ulsu @ sKatepresi*si   shiml 
  ar  n-nega callz  a t.&      clf eIf you requi @ sKeyi*si   z  a t  bsb e0    hnghrs, *
  z  a t creig = this */pres hasOwn 
    :Mde();

        topa ct rhrgANGE+r/pran/0,cTnot spig = this */prt whavnd M i*alue
d.   do  doon D/":_DOCUM    n fhavnd MstackI*si  _DOCUM    n/hey can  stackI*si  g.hasOwnP        DEFAUL.       }e( ret*si  )  toString: functiopexcohasOwnZ   upergHAStyledconfigten for "zI a t      */
    Rtsssss   }!    eplte..isUnk:BCond(ohasOwnZ) );
!isNaN(ohasOwnZ))  toString: functiottttuperCHAStyledconfii*si  t@ zI a t ,e(ohasOwnZ -s1))  
    Rtsssssssss;
sqIdibeAll()i
P@feonfii     var oConfig = this.cTk:Bdd in,  ing fg successfifam*,rue,*
   e     p ffvlem=yd  "cute.
d.   do  don fhavnd M

    I*si  eisstg
rsn fthat should be 
      */prewsilent W
    (usura@ig, e{
fvlem=ydn   p// Hel  nod/*r more    va[]}*,pgs   */prewsilent W,pgu**
   dFer nthe C* Moduldig = this.c succesxOf(pgs[0]s userequr a, e{new@ig.hcl.wi ct rhreacgpack
fvlem=yf// Hel  nod/*r more    var nSubscri.subsco> 0)  dFer nthe C* Module succesxOfonostothe*p(kt wor (susura@igequr a, e{owd toString
rsnm = this.ebeing I*si  g.hasOwnP   
   Of(pgsbsc && su        AfgQupexcbIFsi     (pgs[0]             dohasOwnP  cANGE+IFsi  ;

wa:,preeeeeeeeefgQupexcoIFsi     }
   i*si  tizepacka/**
rs.
     oE    :Mo  }
   
    :MEKey in usssssssss    oPaue;
             do {
    }!oIFsi  )  toString: functio {
    }!m_oIFsi  Tem  fn )  toString: functio {
     m_oIFsi  Tem  fn o   {
};

 .cANGE+     :M( e     p           AfgQu functio {
    }}
   isSecu @   uoString: functiooooooooooooom_oIFsi  Tem  fn .src = .hasOwnaase();_SRC    */
    Rtsssssssssssss}t   */
    Rtsssssssssssss/*uoString: functioooooooooooooSHAd ht opacityeing, e{<i*si  > top0 sexecataduluoString: functiooooooooooooodoesn't }odOOyd ht opacityeinganyd ranspaue;
luoString: functiooooooooooooo     !n aecatamay"b ecn" opeingdul(like ade(adow).&      clf evvvvvvvvvvvvvo/hey can  Checcccccccccccc   }UA.i@   uoString: functiooooooooooooom_oIFsi  Tem  fn .style.filE+r/   ulpha(opacity=0) ct pexcts tobggggggggggggggggg/*uoString: functiooooooooooooooooooNeloa:,pCHAd ht "*si  Bord f"d
fvlem=ydtop0 uoString: functioooooooooooooooooosupe =sd ht dd in,  <i*si  > bord fsFAUL..);toString: functiooooooooooooooooo/S* @typg(ke CSS "bord f"d
fvlem=ydalcnh toString: functiooooooooooooooooo/doesn't supe =sdit.&      clf evvvvvvvvvvvvvvvvvo/hey can  Checccccccccccccccccm_oIFsi  Tem  fn .*si  Bord f   0ct pexcts tobggggggggggggg}toString: functiooooooooo      toString: functioooooooooccccm_oIFsi  Tem  fn .style.opacitye   0 ct pexcts tobggggggggggggg}t   */
    Rtsssssssssssssm_oIFsi  Tem  fn .style.ubscribe    ubsolut  ct pexcts tobgggggssssssssm_oIFsi  Tem  fn .style.bord f   "non  ct pexcts tobgggggssssssssm_oIFsi  Tem  fn .style.m(pgine   0 ct pexcts tobgggggggggggggm_oIFsi  Tem  fn .style.uaddile.   0 ct pexcts tobgggggggggggggm_oIFsi  Tem  fn .style.display   "non  ct pexcts tobgggggggggggggm_oIFsi  Tem  fn .tabI a t   -1            do {
 ggggggggm_oIFsi  Tem  fn . {
  Ni     .hasOwnaCSS_ase();werCase();

        uK  hey can  ChecksctctiooIFsi     m_oIFsi  Tem  fn . {on /**
(hdSet)werCase();

        uKoIFsi  .id   }
   id + "_f ct pexcts tobgggggggggoPaue;
   oE    :M.paue;
/**
             do {
     pexcpaue;
/**
   oPaue;
 ||  {
};

 .b

              do {
        * _addToPaue;
}paue;
/**
,coIFsi  )ct pexcts tobggggg       * i*si   =coIFsi      */
    Rtsssss  hey can  Checksct/*uoString: functiooooooSSIGg, e{<i*si  > Tnot spebscribeturgdulsinceg, e{"CHAXY" toString: functioooooohavnd MingDOM requi @sd ht 
    :MdbeT": *
   {
};

 
toString: functioooooo("wi)) {
     u/t bei funnnnno/hey can  Checcccc}
   sSIGI*si  
  Dhey can  Checksct/*uoString: functiooooooSyncron
  ,*
  t
   ("wiubscribe cre, e{<i*si  > topecatauoString: functioooooocrepres hasOwn.&      clf evnnnno/hey can  Checcccc}
   syncI*si  
     */
    Rtsssss   * stackI*si  
  DtoString: functioo/ Add ing fgs      
  topupdabee, e{<i*si  > ,rue,netru arytoString: functio   }!   * _ 
 I*si  lent L      
 )  toString: functiotttt   * sSIGGNATU.ers.
        * sSIGI*si  )ct pexcts tobggggg       * hnSIGNATU ers.
        * hnSII*si  )ct pexcts tobggggg       * nute.
Cs.
 nt  /** ers.
     }
   syncI*si             AfgQu functio   * _ 
 I*si  lent L      
  in
               do {
   sqIdibeAll();
             hasOwnP  onBnt.siSSIG      DEFAUL.        cANGE+IFsi  .alue(         */
    Rtsssss}
   ent.siSSIG  /**.uners.
     onBnt.siSSIG     */
    Rtsssss}
   _i*si  Deferr o
= hdSet    */
    Rts;
                }bIFsi  )  oo/ <i*si  > shimsispulg
  d            do {
    }}
   nfg g* dass ffva     }
      uoString: functio    cANGE+IFsi  .alue(         */
    Rtsssss}       toString: functiooooo   }!   * _i*si  Deferr o)r toString: functiooooooooo 
   ent.siSSIG  /**.ers.
     onBnt.siSSIG     */
    Rtsssss Rtsssss}
   _i*si  Deferr o
= 
               do {
 ssss}   */
    Rtsssss  hey can  Checi       o  oo/ <i*si  > shimsispdisg
  d   */
    Rtsssss}
   hnSII*si  ()ctuoString: functio   }   * _ 
 I*si  lent L      
 )  toString: functiotttt   * sSIGGNATU.uners.
        * sSIGI*si  )ct pexcts tobggggg       * hnSIGNATU uners.
        * hnSII*si  )ct pexcts tobggggg       * nute.
Cs.
 nt  /** uners.
        * syncI*si             AfgQu functio   * _ 
 I*si  lent L      
  inhdSet    */
    Rtsssss  
    Rtsssss  
    Rtsi     var oConfig = this */S* 'sd ht rs.
gConr'sdXY ct rhreoNITDOM      re    * @"CHA.&      clf eig = this .cDiff 
  eoNITsyncPbscribe,T": *
atepresXY ct rhris .toLosync'oact peDOM    ig = this .c  re    * @"CHA.bscrihavnd MdSeo re) {
'sd ht XY he Conaffvlem=yde if Ofeo anyig = this .cent.siMopa,cMopa ing fgs      
    @ invok
d.   do  doon _DOCUM    n fhavnd M_primeXYFromDOM_DOCUM    n ft
 icbsulbytdefault ts tobg fg_primeXYFromDOM tocka/**
rl     DEFAUL.       }    eplte..isUnk:BCond(}
   nfg g* dass ffva ty")    uoString: functioo/ nHAdCFG XY banloaoevDOM XYFECE_CO =========   * syncPbscribe
     */
    Rtssssso/ Accou fgeacgXY beringCHAdsilg flnP nTsyncPbscribe (no mopaTosfifam/alue
d)FECE_CO =========   * nfg re) {
lent ("ty")  
    Rtsssssssss   * ent.siSSIG  /**.uners.
        * _primeXYFromDOM  DER":P@feonfii
P@feonfii     var oConfig = this.cTk:Bdd in,  ing fg successfifam*,rue,*
   rs.embT":toviewpr:B"/ig = this.cffvlem=yd  "cute.
d.   do  don fhavnd M

    Cs.embT":ToViewpr:Beisstg
rsn fthat should be 
      */prewsilent W
    (usura@ig, e{
fvlem=ydn   p// Hel  nod/*r more    va[]}*,pgs   */prewsilent W,pgu**
   dFer nthe C* Moduldig = this.c succesxOf(pgs[0]s userequr a, e{new@ig.hcl.wi ct rhreacgonostothe*p(kck
fvlem=yf// Hel  nod/*r more    var nSubscri.subsco> 0)  dFer nthe C* Module succesxOfonostothe*p(kt wor (susura@igequr a, e{owd toString
rsnm = this.ebeing Cs.embT":ToViewpr:Bg.hasOwnP   
   Of(pgsbsc && su       AfgQupexc)r a  (pgs[0]  tuplicate = t   }val)  toString: functio   }!ep* @pr.    * @Srs.
    o(euewgent.siMopa  /**,t   * ent.sceCs.embT":tsbs        uoString: functiotttteuewgent.siMopa  /**.ers.
        * ent.sceCs.embT":tsbs    **
        */
    Rtsssss  oString: functio   }!ep* @pr.    * @Srs.
    o(euewgent.siSSIG  /**,o   * _primeXYFromDOM )  toString: functiotttt   * ent.siSSIG  /**.ers.
        * _primeXYFromDOM  DER":P@feonfissss  oString: fun}       toString: functio   * ent.siSSIG  /**.uners.
        * _primeXYFromDOM  DER":P@feonfisssseuewgent.siMopa  /**.uners.
        * ent.sceCs.embT":tsbs      DER":P@feonfii
P@feonfii     var oCConfig = this.cTk:Bdd in,  ing fg successfifam*,rue,*
   rs.
 xt"/
fvlem=yig = this.c  "cute.
d.   do  don   do  don fhavnd M

    Cs.
 xteisstg
rsn fthat should be 
      */prewsilent W
    (usura@ig, e{
fvlem=ydn   p// Hel  nod/*r more    va[]}*,pgs   */prewsilent W,pgu**
   dFer nthe C* Moduldig = this.c succesxOf(pgs[0]s userequr a, e{new@ig.hcl.wi ct rhreacg(kck
fvlem=yf// Hel  nod/*r more    var nSubscri.subsco> 0)  dFer nthe C* Module succesxOfonostothe*p(kt wor (susura@igequr a, e{owd toString
rsnm = this.ebeing Cs.
 xtg.hasOwnP   
   Of(pgsbsc && su        AfgQupexcrs.
 xtApgs   (pgs[0]tizepacka/**
rs.
  s.
 xtEetizepacka/**
rs.
 
    :MMagnetCorn ftizepacka/**
rs.
  s.
 xtMagnetCorn ftizepacka/**
rs.
 uldggesxOizepacka/**
rs.
 oAN1ChOizepacka/**
rs.
 dd Tldggesx   }
   CONTEXT_TRIGGERS  tuplicate = t   }ns.
 xtApgs

wa:,preeeeeeeeefgQu s.
 xtEea  ns.
 xtApgs[0]  zepacka/**
rs.
 
    :MMagnetCorn fa  ns.
 xtApgs[1]  zepacka/**
rs.
  s.
 xtMagnetCorn fa  ns.
 xtApgs[2]  zepacka/**
rs.
 tldggesx   ns.
 xtApgs[3]  zepacka/**
rs.
 oAN1Ch = ns.
 xtApgs[4]  tuplicate = t = t   }dd Tldggesx && dd Tldggesx.length > 0   uoString: functiotttttldggesx   (tldggesx || []) ne cat}dd Tldggesx     */
    Rtsssss  hey can  Checksct   } s.
 xtEe)  toString: functio {
    }
   ofu s.
 xtEea = "suld b")r toString: functiooooooooo 
   nfg s* dass ffva rs.
 xt", [toString: functiooooooooooooooooo {
};

 .g* E    :MById} s.
 xtEe)tsizepacka/**
rs.
                 
    :MMagnetCorn ftizepacka/**
rs.
                  s.
 xtMagnetCorn ftizepacka/**
rs.
  functiooooooooo ldggesxOizepacka/**
rs.
                 oAN1Ch]tizepacka/**
rs.
  functiooooooooo l       */
    Rtsssssssss}ttoString: functio {
    }
    :MMagnetCorn fa&&  s.
 xtMagnetCorn f)r toString: functiooooooooo 
   align}
    :MMagnetCorn f,  s.
 xtMagnetCorn ft oAN1Ch     */
    Rtsssssssss}ttoString: functio {
    }   * _ s.
 xtTldggesx r toString: functioooooooooo/ Uners.
     Old nHA   */
    Rtsssss Rtsssss}
   _
fvtru Tldggesx}   * _ s.
 xtTldggesx, _UNSUBSCRIBE,o   * _alignOnTldgges     */
    Rtsssssssss}ttoString: functio {
    } ldggesx r toString: functioooooooooo/ Srs.
     New nHA   */
    Rtsssss Rtsssss}
   _
fvtru Tldggesx} ldggesx, _SUBSCRIBE,o   * _alignOnTldgges     */
    Rtsssssssssssss}
   _ s.
 xtTldggesx
= 
 dggesx            do {
 ssss}   */
    Rtsssss  
    Rtsssss  
    Rtsi     var oConfig = this */prewsi Eng fg successfoxcrs.
 xt align;

 

 dggesx. Invok
sd ht alignihavnd    do  doon _DOCUM    n fhavnd M_alignOnTldgges_DOCUM    n ft
 icbsulbytdefault  _DOCUM    n fthat should be 
      */eent W
    (  reonloabyd ht dd in,  imp  ioNITofidp// Hel  nnod/*r moreAny[]}*,pgs   */apt Weingapgu**
  reacg(kck
 dgges/eent W(  reonloabyd ht dd in,  imp  ioNITofidp// Hel  nnots tobg fg_alignOnTldggestocka/**
rl
   Of(pgsnCase();

      (kis align}     */
   ;     var oConfig = this .cHels fihavnd Mto locabee, e{crewsi eent W nstastrseacg(kckeent Wni   suld big = this .cpasnloain. A  arrs.entiestrsmeasu @,ganydcrewsi eent scpasnloain   @ return
d.   do  doon_DOCUM    n fhavnd M_findTldggesCE_DOCUM    n ft
ivabe   do  doon_DOCUM    n fthat should b|prewsilent e 
 Eithin a/prewsilent ,    eent W
    (e.g.@ wire oSaloll")reacgwhich a/_DOCUM    n crewsi eent W nstastrsneeds  bsb elook
d up,eoNITpres hasOwn._TRIGGER_MAPoString
rssnm = this.e_findTldggesCE tocka/**
rlt)r toString: funpexctc  =c rty            do   } W nstastringprewsilent )  toString: functio c  =ct DER":P@feonfii         } hasOwn._TRIGGER_MAP[t]     DEFAUL.         c  =c hasOwn._TRIGGER_MAP[t] DER":P@feonfii
P@feonfiiiiireturn  c     */
   ;     var oConfig = this .cUtility havnd Mt
ateers.
    s    uners.
    sd ht gient/_DOCUM    n hasOwnP  eoNITpress   ocrep dgges/eent sk
fvvid
d.   do  doon_DOCUM    n fhavnd M_
fvtru Tldggesx_DOCUM    n ft
 icbsul    do  doon_DOCUM    n fthat shApt W[ould b|prewsilent ]}*tldggesx An/apt Weingeithin prewsilent x, eent W
    suld b  ig = this */(e.g.@ ent.siSSIG",@ wire oSaloll")rto/eoNITwhich (kck
fvvid
d hasOwnP  e();

    ig = this */ers.
    d/uners.
    d*respcbsienly.   do  doon_DOCUM    n fthat should be m**
 Eithin "ers.
    "    "uners.
    ",@ YAHOOyringc
uthin      r_DOCUM    n we   @ ers.
   ile.o  uners.
   typg( dgges/s      
 bytdefault  _DOCUM    n fthat shFasOwnP } fn   */hasOwnP   bsb eers.
    d/uners.
    d*to/eoNIT(kck
 dgges/eent .   do  doon Cs.
 xt  i*,lway "CHAd bs, e{ohasOwnP nstastr, ("wino addiwnP r ao> 0) gapgu**
  _DOCUM    n g* cpasnloayexecut rs.
    d*hasOwnP oString
rssnm = this.e_
fvtru Tldggesx tocka/**
rltldggesx, m**
,cfn)r toString: funpexct,  c   toString: funeacg(pexci   0, l
= 
 dggesx.length ci < l; ++i     DEFAUL.         
= 
 dggesx[i]  zepacka/**
rs.
 tc    }
   _findTldggesCE(h     */
    Rtsssss   } c
)  toString: functiotttt ce[m**
](fnbs    **
        */
    Rtsssss        toString: functiooooo    [m**
](t,cfn)    */
    Rtsssss  
    Rtsssss  
    Rtsi     var oCo/ END BUILT-IN PROPERTYgl     HANDLERSO//tuplicate/nfig = this.cAligns
, e{OhasOwyTyexduswrs.
 xt/
    :Mousnction ( YAHOO.wi corn faig = this.cpo":ts (repe = :Mloabyd ht rs.ema:ts TOP_LEFT, TOP_RIGHT, BOTTOM_LEFT, ig = this.c("wiBOTTOM_RIGHT.   do  don fhavnd Maligneisstg
rsn fthat should be 
    :MAlign    */ould b repe = :Mnction (corn fao  onostothe*p(ke{OhasOwyTy
atee();

    alignloayexecutrs.
 xt/
    :Meisstg
rsn fthat should be ns.
 xtAlign    */corn fao  ecutrs.
 xt/
    :M onostothe*p(katepres
    :MAlign corn fae();

 stickayef// Hel  nod/*r moreNumb
 []}*xyOAN1Ch OpwnP r . A 2/
    :M apt We YAHOOyringpresxc("wiy pixel oAN1ChsTwhich e();

    ahcl.wiig = this.c(fE+r/alignringpres
    :M andtrs.
 xt/corn f  dFer examp  ,cpasnturgdn [5, -10]seacg(kies)t rh, w);

 oAN1Ch presig = this.cOhasOwyTbyd5 pixelsdalcnngpresX axies(horizs.
glly)f("wi-10 pixelsdalcnngpresY axies(hasticglly)f(fE+r/alignringpres YAHOO.wi corn fsoString
rsnm = this.ealigng.hasOwnP   
    :MAlign, ns.
 xtAlign,*xyOAN1Ch& su        AfgQupexcrs.
 xtApgs   }
   nfg g* dass ffva rs.
 xt" EKey in usssssssss     }
  tizepacka/**
rs.
  s.
 xttizepacka/**
rs.
 
    :Mtizepacka/**
rs.
  s.
 xtRegnP              dohasOwnP  doAlign(v, h

wa:,preeeeeeeeefgQupexcalignX =c rty,calignY =c rty  :,preeeeeeeeefgQuswitch  
    :MAlign)  toStrtoString: functiooooocases hasOwn.TOP_LEFT:   */
    RtsssssssssssssalignX =ch  oString: functioooooooooalignY =cv  oString: functiooooooooob  *kwerCase();toString: functiooooocases hasOwn.TOP_RIGHT:   */
    RtsssssssssssssalignX =ch - e    :M.oAN1ChWid p  oString: functioooooooooalignY =cv  oString: functiooooooooob  *kwerCase();toString: functiooooocases hasOwn.BOTTOM_LEFT:   */
    RtsssssssssssssalignX =ch  oString: functioooooooooalignY =cv - e    :M.oAN1ChH";
    oString: functiooooooooob  *kwerCase();toString: functiooooocases hasOwn.BOTTOM_RIGHT:   */
    RtsssssssssssssalignX =ch - e    :M.oAN1ChWid p sizepacka/**
rs.
         alignY =cv - e    :M.oAN1ChH";
    oString: functiooooooooob  *kwerCase();;;;;;;;;  hey can  Checksct   }alignX !iin rtya&& alignY !iin rty)  toString: functio {
    }xyOAN1Ch& su  */
    RtsssssssssssssalignX +=*xyOAN1Ch[0]  zepacka/**
rs.
         alignY +=*xyOAN1Ch[1]  zepacka/**
rs.
 ;;;;  zepacka/**
rs.
 ;;;;me.mopaTo}alignX,calignY)    */
    Rtsssss  
    Rtsssss             do   }ns.
 xtApgs

wa  */
    Rtsssssrs.
 xt/  ns.
 xtApgs[0]  zepacka/**
rs.
 
    :Mo  }
   
    :MAHcka/**
rs.
 ;;;;meo  }
               do {
    }! 
    :MAlign)  toStrzepacka/**
rs.
 
    :MAlign   ns.
 xtApgs[1]  zepacka/**
rs.
   hey can  Checksct   }! ns.
 xtAlign   uoString: functio    cs.
 xtAlign   ns.
 xtApgs[2]  zepacka/**
rs.
   hey can  Checksct   }!xyOAN1Ch &&  s.
 xtApgs[4]   uoString: functio    xyOAN1Ch = ns.
 xtApgs[4]  zepacka/**
rs.
   hey can  Checksct   }
    :Mo&&  s.
 xt   uoString: functio    cs.
 xtRegnP    upergHARegnP }ns.
 xt           AfgQu functioswitch  ns.
 xtAlign   uoStr zepacka/**
rs.
         cases hasOwn.TOP_LEFT:   */
    RtsssssssssssssssssdoAlign(cs.
 xtRegnP .top, cs.
 xtRegnP .lefh     */
    Rtsssssssssssssssssb  *kwerCase();toString: functiooooo    cases hasOwn.TOP_RIGHT:   */
    RtsssssssssssssssssdoAlign(cs.
 xtRegnP .top, cs.
 xtRegnP .r;
  
         AfgQussssssssssssssssb  *kwerCase();toString: functiooooo    cases hasOwn.BOTTOM_LEFT:   */
    RtsssssssssssssssssdoAlign(cs.
 xtRegnP .bottom, cs.
 xtRegnP .lefh     */
    Rtsssssssssssssssssb  *kwerCase();toString: functiooooo    cases hasOwn.BOTTOM_RIGHT:   */
    RtsssssssssssssssssdoAlign(cs.
 xtRegnP .bottom, cs.
 xtRegnP .r;
  
         AfgQussssssssssssssssb  *kwerCase();;;;;;;;;;;;;  e();

        uK  e();

      i   do  do}     var oConfig = this.cTk:Bdd in,  ing fg successexecutam*,rue,*
  mopa  /**  i*fifam, irepresig = this.c rs.embT":toviewpr:B"/  "CHAd bs
   .   do  don fhavnd Ment.sceCs.embT":tseisstg
rsn fthat should be 
      */prewsilent W
    (usura@ig, e{
fvlem=ydn   p// Hel  nod/*r more    va[]}*,pgs   */prewsilent W,pgu**
   dFer nthe C* Moduldig = this.c succesxOf(pgs[0]s userequr a, e{new@ig.hcl.wi ct rhreacg(kck
fvlem=yf// Hel  nod/*r more    var nSubscri.subsco> 0)  dFer nthe C* Module succesxOfonostothe*p(kt wor (susura@igequr a, e{owd toString
rsnm = this.eent.sceCs.embT":tsg.hasOwnP   
   Of(pgsbsc && su       AfgQupexcpos   (pgs[0];u        AfgQupexcrXY   }
   getCo.embT":edXY(pos[0]t pos[1]     */
    Rtseuewgnfg s* dass ffva t ,ecXY[0]**
        */
    Rtseuewgnfg s* dass ffva y"r cXY[1]**
        */
    Rtseuewgnfg s* dass ffva xy"r cXY**
        */
   i     var oConfig = this */Shafam*imp  ioNITofid havnd MeacggetCo.embT":edXf("wigetCo.embT":edY.&      clf eig = this .c<p>ig = this .cGient/arrsordinabee)t rh, returnsd ht ralcu fn drrsordinabeerequi @oayexig = this .cubscribe (ke{OhasOwyT Modulis  bsb eco.embT":edayexecutviewpr:B, banloaoevent(ig = this .c fgy =  
    :Mde
  r viewpr:B.dimensibes,TCalolle)t rhs ("wiuring fohasOwp ig = this */e* @typ bytdefault  D/":_DOCUM    nbytdefault  fhavnd M_getCo.embT":edPox_DOCUM    n ft
 icbsul_DOCUM    n fthat should be pos   */coordinabeewhich needs  bsb eco.embT":ed,geithin  x     "y"_DOCUM    n fthat shNumb
 }   */coordinabeect rhrwhich needs  bsb eco.embT":ed_DOCUM    n freturn hNumb
 }   */co.embT":edacoordinabeect rh_DOCUM    nm = this.e_getCo.embT":edPoxtocka/**
rlpos, val)  t        AfgQupexcohasOwnElo  }
   
    :M,         AfgQu funbuff 
 = .hasOwnaVIEWPORT_OFFSETEK        AfgQu funt   (pos  =  t  ti        AfgQu funohasOwnni          (x) ?cohasOwnEl.oAN1ChWid p :cohasOwnEl.oAN1ChH";
  ,Key in usssssssssviewpr:Bni         (x) ?cupergHAViewpr:BWid p(  :cupergHAViewpr:BH";
  ( Oizepacka/**
rs.
 docSaloll**
rs.
   (x) ?cupergHAD{
};

 SalollLefh(  :cupergHAD{
};

 SalollTop( Oizepacka/**
rs.
 ohasOwpPbscribes   (x) ?c.hasOwnaPRl    _OVERLAP_X :c.hasOwnaPRl    _OVERLAP_Yti        AfgQu funrs.
 xt/  }
   nfg g* dass ffva rs.
 xt" EK        AfgQu funb.hasOwnFdusInViewpr:B =e(ohasOwnni   +nbuff 
 <sviewpr:Bni   Oizepacka/**
rs.
 bPring fCs.
 xtOhasOwp   }
   nfg g* dass ffva uring frs.
 xtohasOwp")o&&  s.
 xto&& ohasOwpPbscribes[(cs.
 xt[1] +  s.
 xt[2])]EK        AfgQu funminCs.embT":to   {
Saloll*+nbuff 
EKey in usssssssss axCs.embT":to   {
Saloll*+nviewpr:Bni   -nohasOwnni   -nbuff 
EK        AfgQu funrs.embT":edVr a  val  tuplicate = t   }val <sminCs.embT":to||c)r a>s axCs.embT":t)  toString: functio   }bPring fCs.
 xtOhasOwp   uoString: functio    cs.embT":edVr a  }
   _
fing fOhasOwplpos,  s.
 xt[0]**ohasOwnni  ,nviewpr:Bni  ,  {
Saloll     */
    Rtsssss}       toString: functiooooo   }b.hasOwnFdusInViewpr:B)r toString: functiooooooooo   }val <sminCs.embT":t   uoString: functiooooooooooooocs.embT":edVr a  minCs.embT":tct pexcts tobggggggggggggg}         })r a>s axCs.embT":t)  toString: functiooooooooooooocs.embT":edVr a  maxCs.embT":tct pexcts tobggggggggggggg}tcts tobggggggggggggg}       toString: functiooooooooocs.embT":edVr a  minCs.embT":tct pexcts tobggggggggg  e();

        uK  e();

      i  e();

      return cs.embT":edVr     */
   ;     var oConfig = this .cHels fihavnd ,eonloa bsubscribe (ke{OhasOwpa bsufing f ohasOwpact peent(ig = this .c s.
 xt/
    :M (onloa,rue,uring frs.
 xtohasOwpsispulg
  dp// Hel  nnobytdefault  fhavnd M_
fing fOhasOwp_DOCUM    n ft
 icbsul_DOCUM    n fthat should be pos   */coordinabee bsufing f ohasOwpaeac,geithin  x     "y"f// Hel  nnod/*r moreHTMLE    :Me ns.
 xtEloTcutrs.
 xt/
    :Meisstg
rs n fthat shNumb
 } ohasOwnni   Tcutre fn drohasOwnPdimensibeect rhr(eacg t ,eent(wid p,Meacg y"r ent(h";
  
eisstg
rs n fthat shNumb
 } viewpr:Bni   Tcutre fn drviewpr:B.dimensibeect rhr(eacg t ,eent(wid p,Meacg y"r ent(h";
  
eisstg
rs n fthat sh    var docSaloll**Tcutre fn dr {
};

 
Calolle)t rhr(eacg t ,eent(salollLefh,Meacg y"r ent(salollTopp// Hel  nnobytdefault  freturn hNumb
 }   */new/coordinabeect rhrwhich wa "CHAd bsufing f ohasOwp_DOCUM    nm = this.e_
fing fOhasOwp tocka/**
rlpos,  s.
 xtEet*ohasOwnni  ,nviewpr:Bni  ,  {
Saloll   toString: fun        AfgQupexct   (pos  =  t  ti        AfgQu funbuff 
 = .hasOwnaVIEWPORT_OFFSETEK        AfgQu funohasOwnP  }
  ti        AfgQu funrs.
 xtElPbs 
   ((x) ?cupergHAX} s.
 xtEe) :cupergHAY} s.
 xtEe)) -n {
Salolltizepacka/**
rs.
  s.
 xtEeni      (x) ?c s.
 xtEe.oAN1ChWid p :c s.
 xtEe.oAN1ChH";
  ,K        AfgQu funminRegnP ni   =nrs.
 xtElPbs -nbuff 
EKey in usssssssss axRegnP ni   =n(viewpr:Bni   -n(rs.
 xtElPbs +  s.
 xtEeni  )) -nbuff 
EK        AfgQu funbFlipp o
= hdSetEK        AfgQu funflip
= hasOwnP        DEFAUL.            pexcflipp oVal  tuplicate = ttttttttt   }(ohasOwn nfg g* dass ffvapos) -n {
Saloll)a>srs.
 xtElPbs)  toString: functioooooooooflipp oVal =n(rs.
 xtElPbs -nohasOwnni       */
    Rtsssssssss}       toString: functioooooooooflipp oVal =n(rs.
 xtElPbs +  s.
 xtEeni  )    */
    Rtsssssssss}ttoString: functio {
 ohasOwn nfg s* dass ffvapos,r(elipp oVal +n {
Saloll)**
                 doooooooooreturn flipp oVal  
    Rtsssssssss}EK        AfgQu funs* dbscribe   tarCase();

wa:,preeeeeeeeeeeeeeeeepexcdisplayRegnP ni   =n((ohasOwn nfg g* dass ffvapos) -n {
Saloll)a>srs.
 xtElPbs) ?s axRegnP ni   :nminRegnP ni  tizepacka/**
rs.
  functioubscribe  tuplicate = ttttttttt   }ohasOwnni   >cdisplayRegnP ni  )r toString: functiooooooooo   }bFlipp o)  toString: functiooooooooooooo/*uoString: functiooooooooooooooooooAlleubs  }
 oubscribes ("wi)t rhs have berntuoString: functiooooooooooooooooooulded,gbutr  nhrwe @ erctru fulOfeo hdSl backtuoString: functiooooooooooooooooooubs, e{origi r at
   ("wiubscribe.&      clf evvvvvvvvvvvvvvvvvo/hey can  Checccccccccccccccccflip
     */
    Rtsssssssssssss}       toString: functioooooooooccccflip
     */
    RtsssssssssssssssssbFlipp o
= 
               do {
 ssssssssssssubscribe   s* dbscribe
     */
    Rtsssssssssssss}   */
    Rtsssssssss}ttoString: functio {
 return ubscribe  
    Rtsssssssss}  tuplicate = ts* dbscribe
    e();

      return }
   nfg g* dass ffvapos)    */
   ;     var oConfig = this .cGient/xrrsordinabee)t rh, returnsd ht ralcu fn drxrrsordinabeerequi @oayexig = this .cubscribe (ke{OhasOwyT Modulis  bsb eco.embT":edayexecutviewpr:B, banloaoevent(ig = this .c fgy =  
    :Mde
  r viewpr:B.dimensibes ("wiCalolle)t rhs.   do  doon_DOCUM    n fthat shNumb
 } x   */X/coordinabeect rhr bsb eco.embT":ed_DOCUM    n freturn hNumb
 }   */co.embT":edaxrrsordinabe_DOCUM    nm		_DOCUM   getCo.embT":edXg.hasOwnP   t     DEFAUL.    return }
   _getCo.embT":edPoxa x ,et          A;     var oConfig = this .cGient/yrrsordinabee)t rh, returnsd ht ralcu fn dryrrsordinabeerequi @oayexig = this .cubscribe (ke{OhasOwyT Modulis  bsb eco.embT":edayexecutviewpr:B, banloaoevent(ig = this .c fgy =  
    :Mde
  r viewpr:B.dimensibes ("wiCalolle)t rhs.   do  doon_DOCUM    n fthat shNumb
 } y   */Y/coordinabeect rhr bsb eco.embT":ed_DOCUM    n freturn hNumb
 }   */co.embT":edayrrsordinabe_DOCUM    nm		_DOCUM   getCo.embT":edY g.hasOwnP   y     DEFAUL.    return }
   _getCo.embT":edPoxa y"r n AH       A;     var oConfig = this .cGient/x, y/coordinabeect rhs, returnsd ht ralcu fn drrsordinabeserequi @oayexig = this .cubscribe (ke{OhasOwyT Modulis  bsb eco.embT":edayexecutviewpr:B, banloaoevent(ig = this .c fgy =  
    :Mde
  r viewpr:B.dimensibes ("wiCalolle)t rhs.   do  doon_DOCUM    n fthat shNumb
 } x   */X/coordinabeect rhr bsb eco.embT":ed_DOCUM    n fthat shNumb
 } y   */Y/coordinabeect rhr bsb eco.embT":ed_DOCUM    n freturn hApt W}   */co.embT":edaxr("wiy rsordinabeseatad a t 0r("wi1*respcbsienly;_DOCUM    nm_DOCUM   getCo.embT":edXYg.hasOwnP (x, y     DEFAUL.    return [}
   getCo.embT":edX t , }
   getCo.embT":edY y ]AH       A;     var oConfig = this* C :Mlrsd ht rs.
gConrT": *
  viewpr:B.   do  don fhavnd M
 :Mlr   do  donm = this.eb :Mlr:,tarCase();

wa:,preeeeeeeeepexcnViewpr:BOAN1Ch = .hasOwnaVIEWPORT_OFFSETEK                
    :MWid p   }
   
    :M.oAN1ChWid pEK                
    :MH";
     }
   
    :M.oAN1ChH";
  ,Key in usssssssssviewPr:BWid p   upergHAViewpr:BWid p( ,Key in usssssssssviewPr:BH";
     upergHAViewpr:BH";
  ( Oizepacka/**
rs.
 xtizepacka/**
rs.
 y  tuplicate = t   }
    :MWid p <sviewPr:BWid p)  toString: functiox =n(viewPr:BWid p / 2) -n}
    :MWid p / 2) +cupergHAD{
};

 SalollLefh(  DER":P@feonfii       toString: functiox =nnViewpr:BOAN1Ch +cupergHAD{
};

 SalollLefh(  DER":P@feonfii tuplicate = t   }
    :MH";
   <sviewPr:BH";
  )  toString: functioy =n(viewPr:BH";
   / 2) -n}
    :MH";
   / 2) +cupergHAD{
};

 SalollTop(  DER":P@feonfii       toString: functioy =nnViewpr:BOAN1Ch +cupergHAD{
};

 SalollTop(  DER":P@feonfii tuplicate = teuewgnfg s* dass ffva xy"ts[thaseInt(x, 10),cpaaseInt(y, 10)]     */
    Rtseuewgnfg re) {
lent ("e     p   tuplicate = t   }UA.webki )  toString: functio uewgt.sceCs.
gConrRedraw
  DER":P@feonfii
P@feonfii     var oConfig = this.cSynchron
  s,*
  Panel's  xy"ts x ,e("wi y  ffvlem=iesact peent(ig = this* Panel's ubscribe ": *
  DOM. Tnt wisk
fimarilyeonloa bsupdabeeaig = this.cposcribe ":t.smTofid duld b drag & drop.   do  don fhavnd Msyncdbscribe   do  donm = this.esyncdbscribe:,tarCase();

wa:,preeeeeeeeepexcpos   upergHAXYdconfigten fo)             do}
   nfg s* dass ffva x ,epos[0]t 
        */
    Rtseuewgnfg s* dass ffva y"r pos[1]**
        */
    Rtseuewgnfg s* dass ffva xy"r pos,r
               i     var oConfig = this.cEng fg successfifam*,rue,*
  res
   mon
ter e    :Mdis res
  d.   do  don fhavnd MonupeRes
     do  don fthat shDOMlent e   Tcutres
   DOM ing f   do  don fthat sh    var nSubscri.subsco> 0)    do  donm = this.eonupeRes
  g.hasOwnP   
bsc && su        AfgQupexcmeo  }
               do.hasOwnasus f {
  .onupeRes
  .alue(    , 
bsc &&  tuplicate = ts* Timeout(hasOwnP        DEFAUL.          .syncPbscribe
     */
    Rtsssss  .afg re) {
lent ("e     p     */
   fgQussss  .afg re) {
lent ("rs.
 xt"  DER":P@feonfii, 0 AH       A;     var oConfig = this .cDeMlrmConsd ht rs.
 :Mdbox(h";
  ao  ecutgient/
    :M (h";
  ao  ecutgten for ct pout uaddile.er bord fs) ": pixels.   do  doon_DOCUM    n fhavnd M_getComputamH";
  _DOCUM    n ft
ivabe   do  doond/*r moreHTMLE    :Me eloTcut
    :M eacgwhich  ht rs.
 :Mdh";
  aneeds  bsb edeMlrmCond_DOCUM    n freturn hNumb
 }   */co.
 :Mdbox(h";
  ao  ecutgient/
    :M,     rtya Modulc);

   reb edeMlrmCondoString
rssnm = this.e_getComputamH";
   g.(cka/**
rl    tuplicate = t   } {
};

 .dd in, View && d{
};

 .dd in, View.getComputamStyle     DEFAUL.        return fka/**
rley)  toString: functio {
 pexch";
      rty            docate = t   }el.owd tD{
};

  && el.owd tD{
};

 .dd in, View)  toString: functiooooooooopexcrsmputam   el.owd tD{
};

 .dd in, View.getComputamStyle}el, ''     */
    Rtsssssssssssss   }nsmputam)  toString: functioooooooooooooh";
     paaseInt(nsmputam.h";
  , 10)    */
    Rtsssssssssssss}   */
    Rtsssssssss}t  */
    Rtsssssssssreturn (Lte..isNumb
 (h";
  )) ?sh";
   g. rty            docate} DER":P@feonfii       toString: functioreturn fka/**
rley)  toString: functio {
 pexch";
      rty            docate = t   }el.style.uixelH";
  )  toString: functioooooooooh";
     el.style.uixelH";
    zepacka/**
rs.
 ;;;;  zepacka/**
rs.
 ;;;;return (Lte..isNumb
 (h";
  )) ?sh";
   g. rty            docate} DER":P@feonfiiH       A;)()     var oConfig = this .cautofillh";
   ct idabor. VerOO.wsp(katepresautofill ct rhris eithin  rtyaig = this .c    nhro  ecutsuld b  : "body"ts head f"dacg fooMlr".   do  doon_DOCUM    n fhavnd M_ct idabeAutoFillH";
  _DOCUM    n ft
 icbsul_DOCUM    n fthat should be ct _DOCUM    n freturn 
   , irect id, hdSet othinwish_DOCUM    nm = this.e_ct idabeAutoFillH";
   tocka/**
rlval)  toString: funreturn (!val) || (Lte..isould blval) && .hasOwnaSTD_MOD_RE.testlval) AH       A;     var oConfig = this .cTk:Bdd in,  crewsi eent W successexecutam*,rue,*
  ohasOwn's h";
     "cute.
d,aig = this .cirepresautofillh";
   
fvlem=yd 
  berntCHA.&      clf _DOCUM    n fhavnd M_autoFillOnH";
  Cute.
_DOCUM    n ft
 icbsul_DOCUM    n fthat should be 
      */eent W
   _DOCUM    n fthat shApt W} ,pgs   */apt Weingapgu**
  rpasnloayexeent W rs.
    sx_DOCUM    n ftr moreHTMLE    :Me eloTcuthead f, bodydacgfooMlrt
    :M which is  bsb eres
  d  bsfillig = this .c utd ht rs.
gConrs h";
  _DOCUM    nm = this.e_autoFillOnH";
  Cute.
 tocka/**
rl
   Of(pgs, 
l)  toString: funpexch";
     }
   nfg g* dass ffva h";
  "  DER":P@feonfi   }(h";
   && h";
   !iin"auto") || (h";
    iin0 )  toString: functio uewgtillH";
  ley) DER":P@feonfii
P@feonfii     var oConfig = this .cReturnsd ht  rs-pixel h";
  ao  ecutgt,ousnctig* Bou.dileCli :MRcbs, ireavailg
  ,ig = this .c thinwish returnsd ht oAN1ChH";
  _DOCUM    n fhavnd M_getPrecishH";
  _DOCUM    n ft
ivabe   do  doond/*r moreHTMLE    :Me el_DOCUM    n freturn hFloat}   */ rs-pixel h";
  airesuspr:Bloabyd ht brows f,      *
  rou.dloah";
  oString
rssnm = this.e_getPrecishH";
   tocka/**
rl
l)  toString: funpexch";
     el.oAN1ChH";
    tuplicate = t   }
 .g* Bou.dileCli :MRcbs)  toString: functiopexcr0) g  el.g* Bou.dileCli :MRcbs
     */
    Rtsssssh";
     r0) .bottom - r0) .top DER":P@feonfii tuplicate = treturn h";
    zepacka/i     var oConfig = this .c<p>ig = this .cS* s,*
  h";
  aon (kck
fvvid
d head f, bodydacgfooMlrt
    :M yexig = this .cfill  utd ht h";
  ao  ecutrs.
gConr. ItedeMlrmConsd ht h";
  ao  ecutig = this .crs.
gConrs co.
 :Mdbox, banloaoevi 'sdnthe C* loah";
  e)t rh, ("wiig = this .cs* s,*
  h";
  aorepresautofillh";
   
    :M yexfill  utdanydig = this .cspach remgConcti(fE+r/ ht othin ema:dard m**ulut
    :M h";
    ig = this */have berntaccou fe Meac.   do  doon D/":_DOCUM    n <p><sulong>gtomE</sulong> Tnt whavnd Mt w  redes
gnloayexwork ireat/
xplici  _DOCUM    n h";
    
    reb rntCHAaon (kckrs.
gConr,lsincegeacgat/"auto" h";
   rs.
gConr,l_DOCUM    n *
  h";
  saorepreshead f/body/fooMlrtor (sdrcall ht h";
  ao  ecutrs.
gConr.D/":_DOCUM    nbytdefault  fhavnd MfillH";
  _DOCUM    n ftr moreHTMLE    :Me eloTcut
    :M which e();

    res
  d  bsfill  utd ht h";
  ig = this .c   ecutrs.
gConr e    :M.String
rssnm = this.efillH";
   tocka/**
rl
l)  toString: fun   }
 )  toString: functiopexcrs.
gConr   }
   inonrE    :Mo|| }
   
    :MEKey in usssssssss    rs.
gConrEls   [}
   head f, euewgeody,o uewgt.oMlr]tizepacka/**
rs.
  funrs.
gConrEltizepacka/**
rs.
  funtotal =n0tizepacka/**
rs.
  funfillam   0tizepacka/**
rs.
  funremgConcti  0tizepacka/**
rs.
  funct idElo  hdSet  izepacka/**
rs.
 eacg(pexci   0, l
= rs.
gConrEls.length ci < l; i++   uoString: functio    cs.
gConrEl
= rs.
gConrEls[i]  zepacka/**
rs.
        }ns.
gConrEl)r toString: functiooooooooo   }elo!iinns.
gConrEl)r toString: functiooooooooos.
 eillam +  }
   _getPrecishH";
  }ns.
gConrEl)    */
    Rtsssssssssssss}       toString: functioooooooooccccct idElo  
               do {
 ssssssss}   */
    Rtsssssssss}t  */
    Rtsssss  hey can  Checksct   }ct idEl

wa:,preeeeeeeeeeeeeeeee   }UA.i@o|| UA.ss fa r toString: functioooooooooo/ Neloa:,pCHAdh";
  atop0, topallowdh";
  atop   reduc d   */
    RtsssssssssssssuperCHAStyledel, 'h";
  ', 0 + 'px'     */
    Rtsssssssss}ttoString: functio {
 total =n}
   _getComputamH";
  }ns.
gConr              doooooooooo/ Fallback, irewt ran't g* crsmputam ct rhreacgrs.
 :Mdh";
  :,preeeeeeeeeeeeeeeee   }total =iin rty)  toString: functio {
 ssssuperaddC{
  (rs.
gConr,l"yui-ohasrid
-uaddilep     */
   fgQussssctio {
 total =nrs.
gConr.cli :MH";
   oo/ Cs.
 :M, No Bord f, 0 Paddile.(CHAdbydyui-ohasrid
-uaddile)FECE_CO ========= {
 ssssuperremohaC{
  (rs.
gConr,l"yui-ohasrid
-uaddilep     */
   fgQussssctio}t  */izepacka/**
rs.
  funremgConcti  Math.max}total - eillam, 0 AH    izepacka/**
rs.
  funuperCHAStyledel,  h";
  ",nremgConcti+ "pt      */           doooooooooo/ Re-adjrew h";
  airerequi @o, topaccou fgeacgelouaddile.("wibord f           docate = t   }el.oAN1ChH";
  o!inremgConct)  toString: functio {
 ssssremgConcti  Math.max}remgConcti- }el.oAN1ChH";
  o-nremgConct), 0 AH       AfgQussssctio}t  */cka/**
rs.
  funuperCHAStyledel,  h";
  ",nremgConcti+ "pt      */fgQussssctio}t  */cka/**
ri
P@feonfii     var oConfig = this.cPlachs
, e{OhasOwyTcn" opeingdSl othin  nstastrsao  onostothe*p    epwidg* . hasOwn.&      cl  fhavnd Mbld bToTop_DOCUM   nm = this.ebld bToTop:,tarCase();

wa:,preeeeeeeeepexca hasOwns   []tizepacka/**
rs.
 oE    :Mo  }
   
    :M             dohasOwnP  csmpareZI a tDesc(p_o hasOwn1, p_o hasOwn2

wa:,preeeeeeeeefgQupexcsZI a t1   upergHAStyledp_o hasOwn1,  zI a t  Oizepacka/**
rs.
     sZI a t2   upergHAStyledp_o hasOwn2,  zI a t  Oiizepacka/**
rs.
     nZI a t1   (!sZI a t1 || isNaN(sZI a t1)) ?s0 topaaseInt(sZI a t1, 10),izepacka/**
rs.
     nZI a t2   (!sZI a t2 || isNaN(sZI a t2)) ?s0 topaaseInt(sZI a t2, 10)  hey can  Checksct   }nZI a t1 > nZI a t2)  toString: functio {
 return -1            do {
 }         }nZI a t1 < nZI a t2)  toString: functio {
 return 1            do {
 }       toString: functio {
 return 0ct pexcts tobggggg  sqIdibeAll();
             hasOwnP  isOhasOwnEl   :M(p_oEl   :M

wa:,preeeeeeeeefgQupexcisOhasOwn   uper 
 C{
  (p_oEl   :M, .hasOwnaCSS_OVERLAY),izepacka/**
rs.
     Panel       epwidg* .Panel  hey can  Checksct   }isOhasOwn && !uperisAstrstor(oEl   :M, p_oEl   :M
)  toString: functio {
    }Panel && uper 
 C{
  (p_oEl   :M, PanelaCSS_PANEL
)  toString: functio {
     a hasOwns[a hasOwns.length]   p_oE    :M.paue;
/**
  ing: functio {
     }       toString: functioooooooooa hasOwns[a hasOwns.length]   p_oE    :Mct pexcts tobggggggggg  e();

        uK  e();

      i  e();

      upergHAE    :MsBy(isOhasOwnEl   :M,  div",  {
};

 .b

 )  hey can  Checa hasOwns.sort(nsmpareZI a tDesc)  hey can  ChecpexcoTopOhasOwn   a hasOwns[0]tizepacka/**
rs.
 nTopZI a t  tuplicate = t   }oTopOhasOwn)  toString: functionTopZI a t   upergHAStyledoTopOhasOwn,  zI a t               do {
    }!isNaN(nTopZI a t
)  toString: functio {
 pexcbRequi @sBumpo  hdSet  izepacka/**
rs.
  = t   }oTopOhasOwno!inoEl   :M

wazepacka/**
rs.
  = tttttbRequi @sBumpo  
               do {
 ssss}         }a hasOwns.length > 1)  toString: functiooooooooopexcnNextZI a t   upergHAStyleda hasOwns[1]** zI a t    oString: functioooooooooo/ Don't relyaoevDOM ord f :,pCtack    2 ohasOwn    @ atepressi   zd a t.&      clf evvvvvvvvvvvvv   }!isNaN(nNextZI a t) && (nTopZI a t  =cnNextZI a t
)  toString: functio {
     ttttbRequi @sBumpo  
               do {
 ssssssss}H       AfgQussssctio}t  */cka/**
rs.
  fun   }bRequi @sBump)r toString: functiooooooooo 
   nfg s* dass ffva zd a t", (paaseInt(nTopZI a t, 10)i+ 2) AH       AfgQussssctio}t  */cka/**
rs.
 }t  */cka/**
ri
P@feonfii     var oConfig = this.cRemohas
, e{OhasOwyT
    :M eoNIT(kckDOM ("wiC* s,dSl child onostothe*p
    :Ms  bs rty.&      cl  fhavnd Mdesuloyig = this.cftr moreboolean} shallowPurge If 
   , .toLo(kck
ay =  
    :M's DOM ing fgs      
    @ purged. If hdSetE      rk
fvvid
d,,dSl children   @ dSeo purgedMingDOM ing fgs      
 . onostothe*pgtomE   */hlag  i*, "shallowPurge"/hlag,,dsaopponloayexwkatemwyTb@ d mo @ intuisien "purgeChildren"/hlagayexmgCo
gCo backwards csmpatibility ct pebehavi   pri   yex2.9.0oString
rsnm = this.edesuloy:,tarCase();shallowPurge    tuplicate = t   }   * i*si  )  toString: functio uewgi*si  .paue;
/**
rremohaChild}   * i*si  ) DER":P@feonfii tuplicate = teuewgi*si   =c rty  :,preeeeeeeee.hasOwnawire oRes
    /**.uners.
     toString: functio uewgdoC :MlrOnDOMlent bs      DER"::,preeeeeeeee.hasOwnawire oSaloll  /**.uners.
     toString: functio uewgdoC :MlrOnDOMlent bs      DtoString: funM**ulu.
 xtRes
    /**.uners.
        * _autoFillOnH";
  Cute.
   tuplicate = t   }   * _ s.
 xtTldggesx r toString: functioo/ Uners.
     rs.
 xt/tldggesx - yexcohas rs.
 xt/tldggesx which s     Meacgglobt _DOCUM    functioo/ eent sksuch asactre oRes
   ("wiwire oSaloll. Easias jrew  bsuners.
     al _DOCUM    functio}
   _
fvtru Tldggesx}   * _ s.
 xtTldggesx, _UNSUBSCRIBE,o   * _alignOnTldgges     */
    Rts} :,preeeeeeeee.hasOwnasus f {
  .desuloy.alue(    , shallowPurge   zepacka/i     var oConfig = this .cCanTb@ onloa bst.sce ecutrs.
gConr to repgCo
/redrawvi 'sdntht*
   ig = this .c<p>ig = this .cByBdd in,  .hcl.ws ("witrue,remohas
a 1px bottom marg": *
rougpeent(ig = this .c.hcl.cTofid/remohr aof*, "yui-t.sce-redraw"  {
  .ig = this .c</":_DOCUM    n <p>_DOCUM    n I    "cfgy = lyeonloaby{OhasOwyTyext.sce a repgCo
 eacgwebki (ig = this .cbrows f , ,rue,b :Mlring.ig = this .c</":_DOCUM    n @havnd MeacceCs.
gConrRedraw_DOCUM    nm = this.efacceCs.
gConrRedraw tocka/**
rl     DEFAUL.    pexcro  }
     DEFAUL.    uperaddC{
  (r 
    :ME "yui-t.sce-redraw"     */
    Rtss* Timeout(hasOwnP       DEFAUL.        uperremohaC{
  (r 
    :ME "yui-t.sce-redraw"     */
    Rtsi, 0 AH       A;     var oConfig = this.cReturnsda/ould b repe = :MTofid    ecuto> 0)  &      cl  fhavnd MtoSuld big = thisn freturn hould be Tcutsuld b repe = :MTofid    ecut hasOwn.&      cl m = this.etoSuld b:,tarCase();

wa  */
    Rtsreturn "OhasOwyT"i+ euewgidAH       A;    va} AH}() AH(tarCase();

wa:,preonfig = .cOhasOwyManages/  "onloaeacgmgCo
gCoringpresf{
}spCtatusao  onost* mn, iplut hasOwn .ig = n fn   spach     epwidg* ig = n fn   spach     epwidg* ig = n f {
  cOhasOwyManagesig = n f o.embuctorig = n fthat shApt W} ohasOwn  OpwnP r . A collecofid     hasOwns to reg    r onost* ct peent(manages.ig = n fthat sh    var onlrCe Cona Tcuto> 0) gliterr arepe = :Mnction (onlr onost* nthe C* Module   ecut hasOwnManagesig = nm = th    epwidg* . hasOwnManages/  tarCase();onlrCe Con

wa  */
   }
   init;onlrCe Con
AH    }  tuplipexcOhasOwn       epwidg* . hasOwntizepacka/Eng fg      eputil.lent bizepacka/upeg      eputil.upebizepacka/Ce Cona      eputil.Ce Conbizepacka/Crewsilent W      eputil.Crewsilent ,izepacka/ hasOwnManages/      epwidg* . hasOwnManages;a:,preonfig = .c  */pSS  {
  arepe = :Mnctiasf{
}sloa hasOwnig = n ftfvlem=yd hasOwnManagesaCSS_FOCUSEDig = n fCtaticig = n ffi r ig = n f
    Suld big = nm = th hasOwnManagesaCSS_FOCUSED/  "f{
}slo";a:,pre hasOwnManagesat
 io
    =   tuplicateonfig = this.cTk:B {
  'sdnthembuctor tarCase(ig = this.cftfvlem=ydnthtbuctorig = g = n f
    FarCase(ig = this.m = this.ebeiembuctor:e hasOwnManages     var oConfig = this.cTk:Bapt Weing hasOwns tkate  @ cfgy = lyereg    rwiig = this.cftfvlem=ydohasOwn ig = g = n f
        epwidg* . hasOwn[]ig = this.m = this.eohasOwn g. rty     var oConfig = this.cInitiali  s,*
  dd in,  cthe C* Module   ecut hasOwnManagesig = g = n fhavnd MtnitDd in, Ce Conig = this.m = this.etnitDd in, Ce Con:,tarCase();

wa  */
    Rtsonfig = this his.cTk:B ollecofid    reg    rwig hasOwns ": }slaby{ig = this his.cecut hasOwnManagesig = g = g = n fcthe CdohasOwn ig = g = g = n f
        epwidg* . hasOwn[]ig = thisg = n fdd in,   rtyig = thisg = n/hey can  Chec 
   nfg adddass ffva ohasOwn ", {esuspe =slent : 
    }    tuplicate = tonfig = this his.cTk:Bdd in,  DOM ing fgy
atee();

    onloa bst.
}spanT hasOwnig = g = g = n fcthe Cdf{
}slng f   do  dog = n f
    Suld big = thisg = n fdd in,  "moonloown"ig = thisg = n/hey can  Chec 
   nfg adddass ffva f{
}slng f", {ect rh: "moonloown" }    an  Chec;     var oConfig = this.cInitiali  s,*
   hasOwnManagesig = g = n fhavnd Mtnitig = this.cftr more hasOwn[]} ohasOwn  OpwnP r . A collecofid     hasOwns to ig = this.creg    r ct peent(manages.ig = this.cftr more    var onlrCe Cona Tcuto> 0) gliterr arepe = :Mnction (onlr onostthis.ccthe C* Module   ecut hasOwnManagesig = g = nm = this.etnit: tarCase();onlrCe Con

watuplicate = tonfig = this his.cTk:B hasOwnManages'sdCe Conao> 0) gonloaeacgmon
ternctiig = this his.ccthe C* Moduleffvlem=ies.ig = thisg = n ftfvlem=ydnfbig = thisg = n f
    Ce Conig = thisg = nm = this.eeeee 
   nfg =c ew Ce Con(      DtoString: fun}
   initDd in, Ce Con
    e();

         }onlrCe Con

wa  */
   is.eeeee 
   nfg..hclyCe Con
onlrCe Con**
        */
    Rts}hey can  Chec 
   nfg ) {
Queue
    e();

      onfig = this his.cTk:B fgy = lyeabsiefn dr hasOwnig = g = g = n ftfvlem=ydabsiee hasOwnig = g = g = n ftfivabe   do  doo = n f
        epwidg* . hasOwnig = thisg = nm = this.eeeeepexcabsiee hasOwn =c rty  :,preeeeeeeeeonfig = this his.cReturnsd ht  fgy = lyef{
}sloa hasOwnig =   doo = n fhavnd Mg* Absieeig =   doo = n freturn h hasOwn}cTk:B fgy = lyef{
}sloa hasOwnig =   doo = nm = this.eeeee 
   g* Absiee
= hasOwnP        DEFAUL.        return absiee hasOwn    */
    Rts}  :,preeeeeeeeeonfig = this his.cF{
}slsd ht  YAHOO.wi  hasOwnig =   doo = n fhavnd Mf{
}sig = g = g = n ftr more hasOwn} ohasOwncTk:B hasOwna bst.
}sig = g = g = n ftr moreould be ohasOwncTk:Bide   ecut hasOwna bst.
}sig = g = g = nm = this.eeeee 
   t.
}sp= hasOwnP   ohasOwn)  toString: functiopexcoo  }
   find ohasOwn)AH       AfgQussss   }o)r toString: functioooooo t.
}s
     */
    Rtsssss}   */
    Rts}  :,preeeeeeeeeonfig = this his.cRemohas
, e{ YAHOO.wi  hasOwn eoNIT(kckmanagesig = g = g = n fhavnd Mremohaig = g = g = n ftr more hasOwn} ohasOwncTk:B hasOwna bsremohaig = g = g = n ftr moreould be ohasOwncTk:Bide   ecut hasOwna bsremohaig = g = g = nm = this.eeeee 
   remohap= hasOwnP   ohasOwn)  ttoString: functiopexcoo  }
   find ohasOwn),/           doooooooooooooorigi r Z             do {
    }o)r toString: functiooooo   }absiee hasOwn == o)r toString: functioooooooooabsiee hasOwn =c rty  ing: functiooooooooo}ttoString: functio {
 pexcbDesuloyam   (o 
    :M =iin rtyo&& o nfg =iin rty) ? 
    : hdSet  izepacka/**
rs.
  = t   }!bDesuloyam r toString: functioooooooooo/ Setvi 'sdzd a tfeo y
atei 'sdsr:Bloayexecutend.&      clf evvvvvvvvvvvvvorigi r Z   upergHAStyledo 
    :ME "zI a t    oString: functioooooooooo nfg s* dass ffva zI a t", -1000**
        */
    Rtsssssoooo}ttoString: functio {
 }
   ohasOwn .sort( 
   nsmpareZI a tDesc)  oString: functio {
 }
   ohasOwn o  }
   ohasOwn .slice(0**(}
   ohasOwn .length - 1)              doooooooooo.hid   /**.uners.
     o.blus     */
    Rtsooooooooo.desuloy  /**.uners.
        * _on hasOwnDesuloy, o)    */
    Rtsooooooooo.t.
}s  /**.uners.
        * _on hasOwnF{
}sHsucces, o)    */
    Rtsooooooooo.blus  /**.uners.
        * _on hasOwnBlusHsucces, o)  izepacka/**
rs.
  = t   }!bDesuloyam r toString: functiooooooooo  /**.remohaL      
do 
    :ME }
   nfg g* dass ffva f{
}slng f" , }
   _on hasOwnEl   :MF{
}s   oString: functioooooooooo nfg s* dass ffva zI a t", origi r Z**
        */
    Rtsssssooooooooo nfg s* dass ffva manages",  rty)    */
    Rtsssssoooo}ttoString: functio {
 /* _managed Flagaeacgrrewsi er ex   ing. Don't wa:M yexremohapex   ingvo/hey can  Checcccccccc   }o.t.
}s  /**._managed r oo.t.
}s  /** =c rty o}t  */cka/**
rs.
  fun   }o.blus  /**._managed r oo.blus  /** =c rty o}they can  Checcccccccc   }o.t.
}s._managed r oo.t.
}s =c rty o}t  */cka/**
rs.
  fun   }o.blus._managed r oo.blus =c rty o}t  */cka/**
rs.
 }   */
    Rts}  :,preeeeeeeeeonfig = this his.cRemohas
t.
}s eoNITdSl reg    rwig hasOwns ": (kckmanagesig = g = g = n fhavnd MblusAtyig = thisg = n/hey can  Chec 
   blusAty   tarCase();

wa:,preeeeeeeeeeeeepexcnOhasOwn o  }
   ohasOwn .length,izepacka/**
rs.
     i  hey can  Checksct   }nOhasOwn o> 0)r toString: functiooooo  =c OhasOwn o- 1            do {
     dor toString: functiooooooooo 
   ohasOwn [i] blus
     */
    Rtsssssssss}t  */cka/**
rs.
  funwhiledi--     */
    Rtsssss}   */
    Rts}  :,preeeeeeeeeonfig = this his n Updabes
, e{ tabee   ecut hasOwnManages ("wiohasOwn, as a resn,     ecutohasOwnig =   doo =  .cbeingvblusred.&      clf evv* &      clf evv* fhavnd M_manageBlus&      clf evv* ftr more hasOwn} ohasOwncTk:BohasOwnc nstastr which g reblusred.&      clf evv* ft
 icbsul_DOCUM     evv*/hey can  Chec 
   _manageBlusp= hasOwnP   ohasOwn)  toString: functiopexccute.
do  hdSet  oString: functio   }absiee hasOwn == ohasOwn)  toString: functiooooouperremohaC{
  (absiee hasOwn 
    :ME  hasOwnManagesaCSS_FOCUSED     */
    Rtsssssssssabsiee hasOwn =c rty  ing: functiooooooooocute.
do  
               do {
 }t  */cka/**
rs.
 return cute.
d    */
    Rts}  :,preeeeeeeeeonfig = this his n Updabes
, e{ tabee   ecut hasOwnManages ("wiohasOwn, as a resn,     ecutohasOwn &      clf evv* receivingvt.
}s.&      clf evv*&      clf evv* fhavnd M_manageF.
}sig = g = g = v* ftr more hasOwn} ohasOwncTk:BohasOwnc nstastr which g ret.
}s.&      clf evv* ft
 icbsul_DOCUM     evv*/hey can  Chec 
   _manageF.
}sp= hasOwnP  ohasOwn)  toString: functiopexccute.
do  hdSet  oString: functio   }absiee hasOwn != ohasOwn)  toString: functiooooo   }absiee hasOwn)r toString: functioooooooooabsiee hasOwn blus
     */
    Rtsssssssss}t  */cka/**
rs.
  funabsiee hasOwn =cohasOwn    */
    Rtsan  Chec 
   bld bToTop}absiee hasOwn)    */
    Rtsan  ChecuperaddC{
  (absiee hasOwn 
    :ME  hasOwnManagesaCSS_FOCUSED     */
    Rtssssssssscute.
do  
               do {
 }t  */cka/**
rs.
 return cute.
d    */
    Rts}  :,preeeeeeeeepexcohasOwns   }
   nfg g* dass ffva ohasOwn "    e();

         }!o 
   ohasOwn 

wa  */
   is.eeeee 
   ohasOwns   []  zepacka/**
ri tuplicate = t   }ohasOwn 

wa  */
   is.eeeee 
   reg    r}ohasOwn 
            do {
 }
   ohasOwn .sort( 
   nsmpareZI a tDesc)  oString: funi
P@feonfii     var oConfig = this.cfhavnd M_on hasOwnEl   :MF{
}sig = this.cfdes
  pwnP cEng fg successfacg(kckDOM ing fgy
ate  "onloa bst.
}spig = this.c(ke{OhasOwyT nstastr a "CYAHOO.wi byd ht  f{
}slng f" onostthis.ccthe C* Module
fvlem=yf// Hel  nod/*fivabe   do  do* ftr morelent e p_oEng fg    vaarepe = :Mnction (DOM ing fg   do  do* o> 0) gpasnloabacktbyd ht ing fgutility (Eng f).&      cl m = this.e_on hasOwnEl   :MF{
}s: tarCase();p_oEng f)  t        AfgQupexcoTargCh =   /**.gChTargCh;p_oEng f)tizepacka/**
rs.
 oClose   }
   nlose  tuplicate = t   }oClose && (oTargCh == oClose || uperisAstrstor(oClose,coTargCh) )  toString: functio uewgblus
     */
    Rts}       toString: functio 
   t.
}s
  DER":P@feonfii
P@feonfii     var oConfig = this.cfhavnd M_on hasOwnDesuloyig = this.cfdes
  pwnP c"desuloy" eent W successfacg(kck hasOwn.&      cl d/*fivabe   do  do* ftr moreould be p_sT    Suld barepe = :Mnction (n   ao  ecutgent Wpig = this.c(kat wa "fifam.ig = this.cftr moreApt W} p_aApgs Apt Weingapgu**
  rs :M whue,*
  ing fg   do  do* wa "fifam.ig = this.cftr more hasOwn} p_o hasOwng    vaarepe = :Mnction (ohasOwnc(kat ig = this.cfifam**
  ing f.&      cl m = this.e_on hasOwnDesuloy:,tarCase();p_sT   , p_aApgs, p_o hasOwn)  toString: fun 
   remohadp_o hasOwn   an  Chec;     var oConfig = this.cfhavnd M_on hasOwnF{
}sHsuccesig = this.ig = this.cfdes
  pwnP ct.
}s  /** Hsucces, onloa bsd
  gabee bs_manageF.
}spct peent(corr0) g,pgu**
   ig = this.ig = this.cf*fivabe   do  do* ftr moreould be p_sT    Suld barepe = :Mnction (n   ao  ecutgent Wpig = this.c(kat wa "fifam.ig = this.cftr moreApt W} p_aApgs Apt Weingapgu**
  rs :M whue,*
  ing fg   do  do* wa "fifam.ig = this.cftr more hasOwn} p_o hasOwng    vaarepe = :Mnction (ohasOwnc(kat ig = this.cfifam**
  ing f.&      cl m = this.e_on hasOwnF{
}sHsuccestocka/**
rlp_sT   , p_aApgs, p_o hasOwn)  toString: fun 
   _manageF.
}sdp_o hasOwn   an  Chec;     var oConfig = this.cfhavnd M_on hasOwnBlusHsuccesig = this.cfdes
  pwnP cblus  /** Hsucces, onloa bsd
  gabee bs_manageBluspct peent(corr0) g,pgu**
   ig = this.ig = this.cf*fivabe   do  do* ftr moreould be p_sT    Suld barepe = :Mnction (n   ao  ecutgent Wpig = this.c(kat wa "fifam.ig = this.cftr moreApt W} p_aApgs Apt Weingapgu**
  rs :M whue,*
  ing fg   do  do* wa "fifam.ig = this.cftr more hasOwn} p_o hasOwng    vaarepe = :Mnction (ohasOwnc(kat ig = this.cfifam**
  ing f.&      cl m = this.e_on hasOwnBlusHsuccestocka/**
rlp_sT   , p_aApgs, p_o hasOwn)  toString: fun 
   _manageBlusdp_o hasOwn   an  Chec;     var oConfig = thiss.cSrs.
    sayexecut hasOwngbanloa nstastr t.
}s  /**, topallowdecut hasOwnManages toig = thiss.cmon
ter f{
}spCtate.ig = this .cig = this .cI  ecut nstastr alreadyd 
  act.
}s  /** (e.g. Menu)E  hasOwnManagestor (sers.
     ig = this .cyexecutex   ingvt.
}s  /**, howingr ireact.
}s  /** er f{
}sphavnd Mdoe    reex   ig = this .con (kck nstastrr ent(_bindF{
}sphavnd Mor (sadd entm, ("witru f{
}sphavnd Mor (sig = this .cupdabeeecut hasOwnManages'spCtate dir0) ly.ig = this .cig = this .cfhavnd M_bindF{
}sig = this .cftr more hasOwn} ohasOwncTk:BohasOwnceacgwhich f{
}spneeds  bsb emanagedig = this .cft
 icbsul_DOCUM    nm = this.e_bindF{
}sp: hasOwnP  ohasOwn)  toString: funpexcmgr   }
     e();

         }!ohasOwn.t.
}s  /**)  toString: functioohasOwn.t.
}s  /** =cohasOwn.creat
lent ("t.
}s    oString: functioohasOwn.t.
}s  /**.s
gnature   Crewsilent .LIST  oString: functioohasOwn.t.
}s  /**._managed   
               do}       toString: functioohasOwn.t.
}s  /**.srs.
     mgr _on hasOwnF{
}sHsucces, ohasOwn, mgr  DER":P@feonfii tuplicate = t   }!ohasOwn.t.
}s)  toString: functio  /**.P  ohasOwn 
    :ME mgr nfg g* dass ffva f{
}slng f" , mgr _on hasOwnEl   :MF{
}s,  rty, ohasOwn   oString: functioohasOwn.t.
}s
= hasOwnP        DEFAUL.               }mgr _manageF.
}sd      r toString: functioooooooooo/ FacgPanel/DialogtoString: functiooooooooo   }   * nfg g* dass ffva vi  }
 ")o&&  
   t.
}sFirst)  toString: functiooooooooooooo 
   t.
}sFirst
     */
    Rtsssssssssssss}   */
    Rtsssssssssoooo 
   t.
}s  /**.fifa
     */
    Rtsssssssss}t  */cka/**
rs.
 }  oString: functioohasOwn.t.
}s._managed   
               do} an  Chec;     var oConfig = thiss.cSrs.
    sayexecut hasOwngbanloa nstastr'spblus  /** topallowdecut hasOwnManages toig = thiss.cmon
ter blus Ctate.ig = this .ig = this .cI  ecut nstastr alreadyd 
  acblus  /** (e.g. Menu)E  hasOwnManagestor (sers.
     ig = this .cyexecutex   ingvblus  /**, howingr ireacblus  /** er blus havnd Mdoe    reex   ig = this .con (kck nstastrr ent(_bindBlus havnd Mor (sadd entm, ("witru blus havnd Mig = this .cupdabeeecut hasOwnManages'spCtate dir0) ly.ig = this .ig = this .cfhavnd M_bindBlus&      clf.cftr more hasOwn} ohasOwncTk:BohasOwnceacgwhich blus needs  bsb emanagedig = this .cft
 icbsul_DOCUM    nm = this.e_bindBlus : hasOwnP  ohasOwn)  toString: funpexcmgr   }
     e();

         }!ohasOwn.blus  /**)  toString: functioohasOwn.blus  /** =cohasOwn.creat
lent ("blus    oString: functioohasOwn.blus  /**.s
gnature   Crewsilent .LIST  oString: functioohasOwn.t.
}s  /**._managed   
               do}       toString: functioohasOwn.blus  /**.srs.
     mgr _on hasOwnBlusHsucces, ohasOwn, mgr  DER":P@feonfii tuplicate = t   }!ohasOwn.blus   toString: functioohasOwn.blus
= hasOwnP        DEFAUL.               }mgr _manageBlusd      r toString: functiooooooooo uewgblus  /**.fifa
     */
    Rtsssssssss}t  */cka/**
rs.
 }  oString: functioohasOwn.blus._managed   
               do} 
ing: functioohasOwn.hid   /**.ers.
     ohasOwn.blus   an  Chec;     var oConfig = thiss.cSrs.
    sayexecut hasOwngbanloa nstastr'spdesuloy  /**, topallowdecut hasOwnig = this .cyex   remohadsfacg(kck hasOwnManagestohue,desuloyam.ig = this .cig = this .cfhavnd M_bindDesuloyig = thisv* ftr more hasOwn} ohasOwncTk:BohasOwnc nstastr beingvmanagedig = this .cft
 icbsul_DOCUM    nm = this.e_bindDesuloy : hasOwnP  ohasOwn)  toString: funpexcmgr   }
    ing: functioohasOwn.desuloy  /**.srs.
     mgr _on hasOwnDesuloy, ohasOwn, mgr  DER":P@fe;     var oConfig = thiss.cEnsures
, e{zI a tccthe C* Module
fvlem=ycon (kckmanaged ohasOwngbanloa nstastrig = thiss.ci "CHAd bsent(computam zI a tcct rhreoNIT(kckDOM (ct pe"auto" translaMnctioo 0).ig = this .ig = this .cfhavnd M_syncZI a tig = thisv* ftr more hasOwn} ohasOwncTk:BohasOwnc nstastr beingvmanagedig = this .cft
 icbsul_DOCUM    nm = this.e_syncZI a t : hasOwnP  ohasOwn)  toString: funpexczI a t   upergHAStyledohasOwn 
    :ME "zI a t    oString: fun   }!isNaN(zI a t
)  toString: functioohasOwn.cfg s* dass ffva zI a t", paaseInt(zI a t, 10)     */
    Rts}       toString: functioohasOwn.cfg s* dass ffva zI a t", 0 AH       AfgQui
P@feonfii     var oConfig = this.cReg    rspanT hasOwn acgat/apt Weing hasOwns ct peent(manages. Upon ig = this.creg    Modul,xecut hasOwngreceivas
tasOwnP ssfacgt.
}span Mblus, ig = this.calongvct peCrewsilent ssfacgeach ig = this.ig = this.cfhavnd Mreg    rig = this.cftr more hasOwn} ohasOwnc Ant hasOwna bsreg    r ct peent(manages.ig = this.cftr more hasOwn[]} ohasOwnc Antapt Weing hasOwns tbsreg    r ct peig = this.c(kt(manages.ig = this.cfreturn hboolean} 
    ireatyg hasOwns   @ reg    rwi.&      cl m = this.ereg    r: hasOwnP   ohasOwn)  ttoString: funpexcr0g    rwig= hdSetEKoString: functioitizepacka/**
rs.
 n  tuplicate = t   }ohasOwnc nstastring hasOwn

wa:,preeeeeeeeeeeeeohasOwn.cfg adddass ffva manages", {ect rh: }
   }    tuplicate = ttttt 
   _bindF{
}s ohasOwn)AH       AfgQussss 
   _bindBlusdohasOwn)AH       AfgQussss 
   _bindDesuloydohasOwn)AH       AfgQussss 
   _syncZI a tdohasOwn)AHH       AfgQussss 
   ohasOwn .pushdohasOwn)AH       AfgQussss 
   bld bToTop}ohasOwn)AHH       AfgQussssr0g    rwig= 
        */
    Rts}         }ohasOwnc nstastringApt W

wa:,preeeeeeeeeeeeeeacg(i   0, n =cohasOwn.length ci < n; i++   uoString: functio    r0g    rwig= 

   reg    r}ohasOwn[i]) || r0g    rwi            do {
 }t           do} 
ing: functioreturn r0g    rwi          i     var oConfig = this.cPlachs
, e{ YAHOO.wi  hasOwn  nstastr cn" opeingdSl othin ig = this.c hasOwn  nstastrs.ig = this.cfhavnd Mbld bToTop_DOCUM   ncftr more    epwidg* . hasOwn} p_o hasOwng    vaarepe = :Mnctian ig = this.c hasOwn  nstastr.ig = this.cftr moreould be p_o hasOwngSuld barepe = :Mnction (ide   an ig = this.c hasOwn  nstastr.ig = this./g = thisig = thisbld bToTop:,tarCase();p_o hasOwn)  ttoString: funpexco hasOwng  }
   find p_o hasOwn)tizepacka/**
rs.
 nTopZI a ttizepacka/**
rs.
 oTopOhasOwn,izepacka/**
rs.
 a hasOwns  tuplicate = t   }o hasOwn

wa:,preeeeeeeeeeeeea hasOwns    
   ohasOwn             do {
 a hasOwns.sort( 
   nsmpareZI a tDesc)  izepacka/**
rs.
 oTopOhasOwn   a hasOwns[0]             do {
    }oTopOhasOwn)  toString: functios.
 nTopZI a t   upergHAStyledoTopOhasOwn 
    :ME "zI a t      DEFAUL.               }!isNaN(nTopZI a t
)  ttoString: functiooooooooopexcbRequi @sBumpo  hdSet  izepacka/**
rs.
  = t {
    }oTopOhasOwno!iino hasOwn)  toString: funnnnnnnnnnnnnnnnnbRequi @sBumpo  
               do {
 ssssssss}         }a hasOwns.length > 1)  toString: functiooooooooooooopexcnNextZI a t   upergHAStyleda hasOwns[1] 
    :ME "zI a t    oString: functioooooooooooooo/ Don't relyaoevDOM ord f :,pCtack    2 ohasOwn    @ atepressi   zd a t.&      clf evvvvvvvvvvvvv {
    }!isNaN(nNextZI a t) && (nTopZI a t  =cnNextZI a t
)  toString: functio {
     ttttttttbRequi @sBumpo  
               do {
 ssssssssssss}   */
    Rtsssssssssoooo}they can  Checcccccccc {
    }bRequi @sBump)r toString: functioooooooooooooo hasOwn nfg s* dass ffva zd a t", (paaseInt(nTopZI a t, 10)i+ 2) AH       AfgQussssctiossss}   */
    Rtsssssssss}t  */cka/**
rs.
  funa hasOwns.sort( 
   nsmpareZI a tDesc)  
    Rtsssssssss}t  */cka/**
ri
P@feonfii     var oConfig = this.cAttempMs  bslocate anT hasOwn bn  nstastr cr ID.ig = this.cfhavnd Mfindig = this.cftr more hasOwn} ohasOwnc Ant hasOwna bslocate ct p": (kckmanagesig = g = n ftr moreould be ohasOwnc Ant hasOwnaida bslocate ct p": (kckmanagesig = g = n freturn h hasOwn}cTk:Brequesuwi  hasOwn, irefou.d,     rtya Modulig = g = n can  reb elocatei.&      cl m = this.efind: hasOwnP   ohasOwn)  ttoString: funpexcisInstastr =BohasOwnc nstastring hasOwntizepacka/**
rs.
 ohasOwns    
   ohasOwn tizepacka/**
rs.
 n =BohasOwn .length,izepacka/**
rs.
 fou.d =c rtytizepacka/**
rs.
 oEKoString: functioi  tuplicate = t   }isInstastr || }ypringohasOwnciin"suld b")r toString: functioeacg(i   n-1 ci >  0 ci--)r toString: functioooooo =BohasOwn [i]  zepacka/**
rs.
        }}isInstastr && (o =iinohasOwn)) || (o.idaiinohasOwn))  toString: functiooooooooofou.d =coAH       AfgQussssctiossssbreak    */
    Rtsssssssss}t  */cka/**
rs.
 }           do} 
ing: functioreturn fou.d          i     var oConfig = this.cUnloaeacgsortnction (manages'sp hasOwns bn z-d a t.&      cln fhavnd M
smpareZI a tDescig = g = n ftfivabe   do  do* freturn hNumb
 } 0, 1,    -1, dependile.entohureeecut hasOwnee();

    do  do* fdSl ": (kckCtackile.erdes.ig = this.m = this.ebempareZI a tDesc: hasOwnP   o1,  2

wa:,preeeeeeeeepexczI a t1   (o1 nfg) ? o1 nfg g* dass ffva zI a t   g. rty,oo/ Sort ":ct id (desuloyam)FECE_CO =========zI a t2   (o2 nfg) ? o2 nfg g* dass ffva zI a t   g. rty oo/ o> 0) s atebottom. tuplicate = t   }zI a t1  iin rtyo&& zI a t2  iin rty)  toString: functioreturn 0ct pexcts tobg}         }zI a t1  iin rty) toString: functioreturn 1ct pexcts tobg}         }zI a t2  iin rty)  toString: functioreturn -1            do}         }zI a t1 > zI a t2)  toString: functioreturn -1            do}         }zI a t1 < zI a t2)  toString: functioreturn 1            do}       toString: functioreturn 0ct pexcts tobg}         i     var oConfig = this.cShows,dSl  hasOwns ": (kckmanages.   do  don fhavnd MshowAtyig = thisnm = this.eshowAty:,tarCase();

wa  */
    RtspexcohasOwns   }
   ohasOwn tizepacka/**
rs.
 n =BohasOwn .length,izepacka/**
rs.
 i  tuplicate = teacg(i   no- 1 ci >  0 ci--)r toString: functioohasOwn [i].show
  DER":P@feonfii
P@feonfii     var oConfig = this.cHides,dSl  hasOwns ": (kckmanages.   do  don fhavnd Mhid Atyig = thisnm = this.ehid Aty:,tarCase();

wa  */
    RtspexcohasOwns   }
   ohasOwn tizepacka/**
rs.
 n =BohasOwn .length,izepacka/**
rs.
 i  tuplicate = teacg(i   no- 1 ci >  0 ci--)r toString: functioohasOwn [i].hid 
  DER":P@feonfii
P@feonfii     var oConfig = this.cReturnsda/suld b repe = :MTofid    ecuto> 0)  &      cl  fhavnd MtoSuld big = thisn freturn hould be Tcutsuld b repe = :MTofid    ecut hasOwnManagesig = g = nm = this.etoSuld b:,tarCase();

wa  */
    Rtsreturn "OhasOwyManages"          i     }AH}() AH(tarCase();

wa:,preonfig = .cToo, ip  i*,n imp    :MTofid     hasOwna kat behaves,like anT Setoo, ip, onost* dispOwnd b whue,*
  onlr moonlsoohasda/particular e    :M, ("wiig = * disappearile.entmoonl  ut.ig = n fn   spach     epwidg* ig = n f {
  aToo, ipig = n fextends     epwidg* . hasOwnig = n f o.embuctorig = n fthat should be eloTcut
    :M IDarepe = :Mnction (Too, ip <em>OR</em>ig = n fthat shHTMLE    :Me eloTcut
    :M repe = :Mnction (Too, ipig = n fthat sh    var onlrCe ConaTk:B ohe C* Module > 0) gliterr ars.
gConctiig = *sent(cohe C* Moduley
atee();

    CHAdfacg(kisp hasOwn. Set(cohe C* Moduleig = * d{
};

 awnP ct.r mo @ de
gCls.   donm = th    epwidg* .Too, ip = hasOwnP   gt,ouslrCe Con

wa  */
       epwidg* .Too, ipasus f {
  . o.embuctor.alue(    , gt,ouslrCe Con
;     }AH     pexcLte./      eplte.tizepacka/Eng fg      eputil.lent bizepacka/Crewsilent W      eputil.Crewsilent ,izepacka/upeg      eputil.upebizepacka/Too, ip =     epwidg* .Too, ipbizepacka/UA =     epenv.uabizepacka/bIEQuirks   }UA.i@o&& (UA.i@o<= 6 ||  {
};

 .csmpatModeciin"BackCsmpat") Oiizepacka/m_oShadowTemplabe     var oConfig = this.cConstasM repe = :Mnction (Too, ip'sdnthe C* Moduleffvlem=iesig = g = n ftfvlem=ycDEFAULT_CONFIGig = g = n ftfivabe   do  do* ffi r ig =   do* f
        vaig = g = nm = this.eDEFAULT_CONFIG =   tuplicateeeee"PREVENT_OVERLAP": {/           doooookey: "pe ent ohasOwp",/           doooooct rh: }   ,            doooooct idabor: Lte..isBoolean,            dooooosus f edes: ["t", "y"ts xy"]            do}Oiizepacka/**
r"SHOW_DELAY": {/           doooookey: "showdeOwn",/           doooooct rh: 200**           doooooct idabor: Lte..isNumb
             do}O iizepacka/**
r"AUTO_DISMISS_DELAY": {/           doooookey: "autodismissdeOwn",/           doooooct rh: 5000**           doooooct idabor: Lte..isNumb
             do}O iizepacka/**
r"HIDE_DELAY": {/           doooookey: "hid deOwn",/           doooooct rh: 250**           doooooct idabor: Lte..isNumb
             do}O iizepacka/**
r"TEXT": {/           doooookey: "
 xt",/           dooooosuspe =slent : 
               do}O iizepacka/**
r"CONTAINER": {/           doooookey: "rs.
gConr"           do}Oiizepacka/**
r"DISABLED": {           doooookey: "disabled",izepacka/**
rs.
 ct rh: hdSetEKoString: functiosuspe =slent : 
              do}Oiizepacka/**
r"XY_OFFSET": {           doooookey: "xyoAN1Ch",izepacka/**
rs.
 ct rh: [0**25]tizepacka/**
rs.
 suspe =slent : 
              do}
P@feonfii     var oConfig = this.cConstasM repe = :Mnction (n   ao  ecutToo, ip'sdeent sig = g = n ftfvlem=ycEVENT_TYPESig = g = n ftfivabe   do  do* ffi r ig =   do* f
        vaig = g = nm = this.eEVENT_TYPES =   zepacka/**
r"CONTEXT_MOUSE_OVER": "rs.
 xtMoonl has",izepacka/**
r"CONTEXT_MOUSE_OUT": "rs.
 xtMoonl uh",izepacka/**
r"CONTEXT_TRIGGER": "rs.
 xtTldgges"         }AH     onfig = .cConstasM repe = :Mnction (Too, ip/pSS  {
  ig = n ftfvlem=yc    epwidg* .Too, ipaCSS_TOOLTIPig = n fCtaticig = n ffi r ig = n f
    Suld big = nm = thToo, ipaCSS_TOOLTIP/  "yui-tt";a:,prehasOwnP  rrstoreOrigi r Width(sOrigi r Width, sF.scedWidth

wa:,preeeeepexcoCe Cona     * nfg,izepacka/**
rsCfgy = Width =BoCe Con g* dass ffva width      DEFAUL.   }sCfgy = Width == sF.scedWidth

wazepacka/**
roCe Con s* dass ffva width , sOrigi r Width)          i     }H     on/         cute.
Cs.
 :M eent W successy
atee* s,dcToo, ip  nstastr'sp width          cthe C* Module
fvlem=yc bsent(ct rhroModus root HTML/         
    :Ms'sao N1ChWidth ireac YAHOO.c width  
    reb rntCHA.   donm :,prehasOwnP  1ChWidthToO N1ChWidthlp_sT   , p_aApgs

wa:,preeeee   }"_origi r Width" ": (kis)  toString: funrrstoreOrigi r Width.alue(    , }
   _origi r Width, }
   _f.scedWidth
          i :,preeeeepexcoBodyd=  {
};

 .b

 ,azepacka/**
roCe Cona     * nfg,izepacka/**
rsOrigi r Width =BoCe Con g* dass ffva width  ,izepacka/**
rsNewWidth,azepacka/**
roClone    DEFAUL.   }(!sOrigi r Width || sOrigi r Width ==e"auto") && azepacka/**
r(oCe Con g* dass ffva rs.
gConr") != oBodyd|| azepacka/**
roCe Con g* dass ffva t   >  upergHAViewporhWidthl)d|| azepacka/**
roCe Con g* dass ffva y   >  upergHAViewporhH";
  })
)  ttoString: funoCloneo  }
   
    :M nlone/**
(
        */
    RtsoClone.style.vi  }ility = "hidden"    */
    RtsoClone.style. ope= "0px"    */
    RtsoClone.style.lefte= "0px"     */
    RtsoBody..hcendChild}oClone     DEFAUL.    sNewWidth   (oClone.o N1ChWidth + "pt       */
    RtsoBody.remohaChild}oClone   oString: funoCloneo   rty  :,preeeeeeeeeoCe Con s* dass ffva width , sNewWidth   oString: funoCe Con refifalent ("xy"  DtoString: fun}
   _origi r Widtho  sOrigi r Width || ""    */
    Rts}
   _f.scedWidtho  sNewWidth          i     }H     o/ "onDOMReady"sy
atey =d rspon (Too,Tip :,prehasOwnP  onDOMReadylp_sT   , p_aApgs, p_o    va

wa  */
   }
   y =d r(p_o    va
;     }H     o/  "init" eent W successtkate utomaticlueyey =d rspon (Too,tip :,prehasOwnP  onInit;

wa  */
     /**.P DOMReadylP DOMReadyE }
   nfg g* dass ffva rs.
gConr")bs      DER":}H         epextend(Too, ipb     epwidg* . hasOwnt {/    var oConfig = this.cTn (Too, ip/initiali Modulehavnd .cTnisphavnd M i*,utomaticlueyeig = this.ccluewi byd ht  o.embuctor. AcToo, ip  i*,utomaticlueyey =d rwi bydig = this.c(kck nitphavnd , ("wiitpdSeo i "CHAd bsbck nvi  }
  byddd in, , ig = this.cand  o.embgConda bsviewporh byddd in,  asacety.&      cl  fhavnd Mtnitig = this.cftr moreould be eloTcut
    :M IDarepe = :Mnction (Too, ip <em>OR</em>ig = this.cftr moreHTMLE    :Me eloTcut
    :M repe = :Mnction (Too, ipig = this.cftr more    var onlrCe ConaTk:B ohe C* Module > 0) gliterr aig = this.ccoo
gCoringprescohe C* Moduley
atee();

    CHAdfacg(kispToo, ipaaig = this.cSet(cohe C* Moduled{
};

 awnP ct.r mo @ de
gCls.   dothis.m = this.etnit: tarCase();gt,ouslrCe Con

wa   */
    Rts}
   logges/   ew     epwidg* .LogWriter( 
   toSuld b()              doToo, ipasus f {
  .tnit.alue(    , gt  DtoString: fun}
   bet.reInit  /**.fifa
Too, ip  DtoString: funuperaddC{
  (}
   
    :M,hToo, ipaCSS_TOOLTIP    e();

         }onlrCe Con

wa  */
   is.eeeee 
   nfg..hclyCe Con
onlrCe Con**
        */
    Rts}htoString: fun}
   nfg.queuedass ffva vi  }
 ", hdSet)    */
    Rts}
   nfg.queuedass ffva  o.embgCotoviewporh"**
         */
    Rts}
   CHABody(""  DtoString: fun}
   srs.
     "cute.
Cs.
 :M , sChWidthToO N1ChWidth)    */
    Rts}
   srs.
     "init", onInit)    */
    Rts}
   srs.
     "y =d r"E }
   onR =d r  DtoString: fun}
   init  /**.fifa
Too, ip  D
    Rts}     var oConfig = this.cInitiali  s,*
  rrewsi eent ssfacgToo, ipig = this.cfhavnd MtnitEent sig = g = nm = this.etnitEent s:,tarCase();

wa:,preeeeeeeeeToo, ipasus f {
  .tnitEent s.alue(    )    */
    RtspexcSIGNATURE   Crewsilent .LIST     */
    Rtsonfig = this his.cCrewsilent Wfifam*whue,onlr moonlsoohasda/rs.
 xt/
    :M cReturningvtdSetreoNIig = this his.casers.
    rc bsenisdeent Mor (spe ent ,*
  too, ipreoNITbeingvdispOwnloaeacig = this his.c ht  fgy = /rs.
 xt/
    :M ig = this his.cig = this his.c@ ent ,rs.
 xtMoonl hasEng f   do  dog = n ftr moreHTMLE    :Me rs.
 xt/Tk:B oh
 xt/
    :Mgwhich *
  onlr jrew moonlooohas   do  dog = n ftr moreDOMlent e e/Tk:BDOM ing fg > 0) , associfn drct peent(moonl  has   do  dog = nm = this.eeeee 
   ns.
 xtMoonl hasEng fa     * nreat
lent (EVENT_TYPES.CONTEXT_MOUSE_OVER)    */
    Rts}
   ns.
 xtMoonl hasEng f.s
gnature   SIGNATURE     */
    Rtsonfig = this his.cCrewsilent Wfifam*whue,*
  onlr moonlsooutaof*, rs.
 xt/
    :M ig = this his.cig = this his.c@ ent ,rs.
 xtMoonl utEng f   do  dog = n ftr moreHTMLE    :Me rs.
 xt/Tk:B oh
 xt/
    :Mgwhich *
  onlr jrew moonloooutaof   do  dog = n ftr moreDOMlent e e/Tk:BDOM ing fg > 0) , associfn drct peent(moonl  ut   do  dog = nm = this.eeeee 
   ns.
 xtMoonl utEng fa     * nreat
lent (EVENT_TYPES.CONTEXT_MOUSE_OUT)    */
    Rts}
   ns.
 xtMoonl utEng f.s
gnature   SIGNATURE     */
    Rtsonfig = this his.cCrewsilent Wfifam*jrew bet.re,*
  too, iprisddispOwnloaeacc ht  fgy = /rs.
 xt ig = this his.c<p>_DOCUM    his.c You cansers.
      bsenisdeent M   you need :,pCHAdup,*
  t xt/eacc ht _DOCUM    his.c too, iprbanloaon (kck oh
 xt/
    :Mgeacgwhich ite  "abouta bsbckdispOwnlo ig = this his.c</p>_DOCUM    his.c<p>Tnisdeent Mdiff rspeoNIT(kckbet.reShowdeent M  ct.llowd b re YAH s:</p>_DOCUM    his.c<ol>_DOCUM    his.c  <li>_DOCUM    his.c   Whue,mohingvtoNIToneo oh
 xt/
    :Mgtopanothin, ire*
  too, iprisdnot_DOCUM    his.c   hidden }  e <code>hid deOwn</code>risdnot reached),T(kckbet.reShowdand Showdeent sMor (snot_DOCUM    his.c   bckfifam*whue,*
  too, iprisddispOwnloaeacc ht  ew  oh
 xt/sistr ite  "alreadydvi  }
  ig = this his.ccccHowingr (kck oh
 xtTldggesdeent M  "alwwns fifam*bet.re,dispOwnd b *
  too, ipreacig = this his.cccca  ew  oh
 xt ig = this his.ccc</li>_DOCUM    his.c  <li>_DOCUM    his.c   Tk:Btldggesdeent M
fvvid
 "actru d bsent(coh
 xt/
    :M,pallowd b you to ig = this his.c   CHAd 
  t xt/ore*
  too, iprbanloaon  oh
 xt/
    :Mgeacgwhich *
  too, iprisig = this his.c   tldggeslo ig = this his.ccc</li>_DOCUM    his.c</ol>_DOCUM    his.c<p>_DOCUM    his.c I    "  rk
os  }
  to pe ent ,*
  too, ipreoNITbeingvdispOwnlo_DOCUM    his.c usd b *
isdeent . You cansonl ent(coh
 xtMoonl hasEng fa   you need :,ppe ent _DOCUM    his.c t
  too, ipreoNITbeingvdispOwnlo ig = this his.c</p>_DOCUM    his.c@ ent ,rs.
 xtTldggesEng f   do  dog = n ftr moreHTMLE    :Me rs.
 xt/Tk:B oh
 xt/
    :Mgeacgwhich *
  too, ipris tldggeslo   do  dog = nm = this.eeeee 
   ns.
 xtTldggesEng fa     * nreat
lent (EVENT_TYPES.CONTEXT_TRIGGER)    */
    Rts}
   ns.
 xtTldggesEng f.s
gnature   SIGNATURE  
    Rts}     var oConfig = this.cInitiali  s,*
  r{
  'sdnthe C* M}
  ffvlem=iesgwhich canTb@ ig = this.ccute.
dousd b *
ut hasOwn'sdCe Conao> 0) g(nfg).&      cl  fhavnd MtnitDd in, Ce Conig = this.m = this.etnitDd in, Ce Con:,tarCase();

wa:,preeeeeeeeeToo, ipasus f {
  .tnitDd in, Ce Con.alue(    )     */
    Rtsonfig = this his.cSYAHOO.wsgwhethin on (Too, ip e();

    keptvtoNITohasOwppnctiig = this his.cdus rs.
 xt/
    :M ig = this his.cfcthe Cdpe ent ohasOwpig = this his.cf
    Booleanig = this his.cfdd in,  
              don/hey can  Chec 
   nfg adddass ffvaDEFAULT_CONFIG.PREVENT_OVERLAP.kent {a  */
   is.eeeeect rh: DEFAULT_CONFIG.PREVENT_OVERLAP.ct rh**           doooooct idabor: DEFAULT_CONFIG.PREVENT_OVERLAP.ct idabor,            dooooosus f edes: DEFAULT_CONFIG.PREVENT_OVERLAP.sus f edes   */
    Rts}    e();

      onfig = this his.cTk:Bnumb
  oremr (isecthds  bswaiw bet.re,shownctiasToo, ip ig = this his.centmoonlohas ig = this his.cfcthe CdshowdeOwnig = this his.cf
    Numb
 ig = this his.cfdd in,  200           don/hey can  Chec 
   nfg adddass ffvaDEFAULT_CONFIG.SHOW_DELAY.kent {a  */
   is.eeeeehsuccesto}
   ns.e CShowDeOwntizepacka/**
rs.
 ct rh: 200**           doooooct idabor: DEFAULT_CONFIG.SHOW_DELAY.ct idabor   */
    Rts}    e();

      onfig = this his.cTk:Bnumb
  oremr (isecthds  bswaiw bet.re,,utomaticlueyeig = this his.cdismissnctiasToo, ip aftin on (moonl  
  b rntrrstile.ent ht _DOCUM    his.crs.
 xt/
    :M ig = this his.cfcthe CdautodismissdeOwnig = this his.cf
    Numb
 ig = this his.cfdd in,  5000           don/hey can  Chec 
   nfg adddass ffvaDEFAULT_CONFIG.AUTO_DISMISS_DELAY.kent {a  */
   is.eeeeehsuccesto}
   ns.e CAutoDismissDeOwntizepacka/**
rs.
 ct rh: DEFAULT_CONFIG.AUTO_DISMISS_DELAY.ct rh*           doooooct idabor: DEFAULT_CONFIG.AUTO_DISMISS_DELAY.ct idabor   */
    Rts}    e();

      onfig = this his.cTk:Bnumb
  oremr (isecthds  bswaiw bet.re,hidnctiasToo, ip ig = this his.caftin moonlouM ig = this his.cfcthe Cdhid deOwnig = this his.cf
    Numb
 ig = this his.cfdd in,  250           don/hey can  Chec 
   nfg adddass ffvaDEFAULT_CONFIG.HIDE_DELAY.kent {a  */
   is.eeeeehsuccesto}
   ns.e CHideDeOwntizepacka/**
rs.
 ct rh: DEFAULT_CONFIG.HIDE_DELAY.ct rh**           doooooct idabor: DEFAULT_CONFIG.HIDE_DELAY.ct idabor   */
    Rts}    e();

      onfig = this his.cSYAHOO.wsgecutToo, ip'sd
 xt  Tk:Bt xt/is ":se:BloaCoto (kckDOM 
  HTML, ("wie();

    escapwi byd ht imp    :Mor irecomingvtoNITanTexternal souf e.cig = this his.c@cthe Cdt xtig = this his.cf
    HTMLig = this his.cfdd in,   rtyig = thisg = n/hey can  Chec 
   nfg adddass ffvaDEFAULT_CONFIG.TEXT.kent {a  */
   is.eeeeehsuccesto}
   ns.e CT xttizepacka/**
rs.
 suspe =slent : DEFAULT_CONFIG.TEXT.suspe =slent    */
    Rts}    e();

      onfig = this his.cSYAHOO.wsgecutrs.
gConr/
    :MgthatepresToo, ip'sdmarkup ig = this his.ce();

    y =d rwi Coto ig = this his.cfcthe Cdrs.
gConrig = this his.cf
    HTMLE    :M/Suld big = thisg = n fdd in,   {
};

 .b

 ig = thisg = n/hey can  Chec 
   nfg adddass ffvaDEFAULT_CONFIG.CONTAINER.kent {a  */
   is.eeeeehsuccesto}
   ns.e CCs.
gConrtizepacka/**
rs.
 ct rh:  {
};

 .b

 ig = thisg = }    e();

      onfig = this his.cSYAHOO.wsgwhethin     ot *
  too, ipris disabled. Disabled too, ipsig = this his.cor (snotsbckdispOwnlo cI  ecuttoo, ipris drient byd ht titl@ atuldbube   do  doo = n ore*
  coh
 xt/
    :M,p ht titl@ atuldbubetor (setr (s   remohadsfacgig = this his.cdisabled too, ips, to pe ent ,dd in,  
oo, iprbehavios ig = this his.cig = this his.cfcthe Cddisabledig = this his.cf
    Booleanig = this his.cfdd in,  tdSetig = thisg = n/hey can  Chec 
   nfg adddass ffvaDEFAULT_CONFIG.DISABLED.kent {a  */
   is.eeeeehsuccesto}
   ns.e CCs.
gConrtizepacka/**
rs.
 ct rh: DEFAULT_CONFIG.DISABLED.ct rh*           dooooosuse =slent : DEFAULT_CONFIG.DISABLED.suspe =slent    */
    Rts}    e();

      onfig = this his.cSYAHOO.wsgecutXYao N1Ch eoNIT(kckmoonl posiodul,xohureeecuttoo, ip e();

    dispOwnlo,{ YAHOO.wiig = this his.cas,dc2/
    :Mgapt We(e.g. [10**20]);cig = this his.ig = this his.cfcthe CdxyoAN1Chig = this his.cf
    Apt Wig = this his.cfdd in,  [0**25]ig = thisg = n/hey can  Chec 
   nfg adddass ffvaDEFAULT_CONFIG.XY_OFFSET.kent {a  */
   is.eeeeect rh: DEFAULT_CONFIG.XY_OFFSET.ct rh ns.cat()tizepacka/**
rs.
 suse =slent : DEFAULT_CONFIG.XY_OFFSET.suspe =slent     */
    Rts}    e();

      onfig = this his.cSYAHOO.wsgecut
    :Mgor/
    :MsgthatepresToo, ip e();

    ig = this his.canchored :,pentmoonlohas ig = this his.cfcthe Cdcoh
 xtig = this his.cf
    HTMLE    :M[]/Suld b[]ig = this his.cfdd in,   rtyig = thisg = n/   e();

      onfig = this his.cSuld barepe = :Mnction (width o  ecutToo, ip.  <em>Pleanl note:ig = this his.c</em> Asao  hassse()2.3 ireeithin no(ct rhrosda/ct rhroMo"auto" ig = this his.cd "CYAHOO.wi, ("witru Too,ip'sd rs.
gConr" cthe C* Module
fvlem=yig = this his.cd "CHAd bssohavnile.ethin onanT<code> {
};

 .b

 </code>racgig = this his.cdus "rs.
 xt"t
    :M resid
 "ouMsid
d ht immedifn lydvi  }
 gig = this his.cporhfid    ecut {
};

 ,ion (width o  ecutToo, iptor (s   ig = this his.ccluculatloabanloaon (kcko N1ChWidth oModus root HTML/("wieHAdjrew ig = this his.cbet.re,ite  "mad
dvi  }
    Tk:Borigi r /ct rhror (s   ig = this his.crrstorem*whue,*
  Too, ip  i*hidden.cTnispensures
, e{Too, ip  i*ig = this his.crr=d rwi ate  usable(width   F.r mo @ int.rmawnP  1C  ig = this his.cYUILibrary bug #1685496/("wiYUILibrary ig = this his.cbug #1735423 ig = this his.cfcthe Cdwidthig = this his.cf
    Suld big = thisg = n fdd in,   rtyig = thisg = n/hey can  hey can  },hey can  hey can  // BEGIN BUILT-IN PROPERTYeEVENT HANDLERS //hey can  hey can  /nfig = this.cTn (dd in,  eent W successfifam*whue,*
  "
 xt"e
fvlem=yc i*cute.
d.&      cln fhavnd M
s.e CT xt&      cln ftr moreould be 
    Tn (Crewsilent W
    (usulueye*
  
fvlem=ycn   )&      cln ftr more    va[]} apgs Tn (Crewsilent W,pgu**
    F.r cohe C* Moduleig =   cln  success, apgs[0]ror (sequluc ht  eweye.hcl.wi ct rhreacc ht 
fvlem=yf// Hel  nod/*r more    var obj Tcutscvleto> 0)   F.r cohe C* Module success, // Hel  nod*
isdor (susulueyeequluc ht ownes.ig = this.m = this.ebe.e CT xt:,tarCase();t   , apgs, o> 

wa  */
    Rtspexc
 xt/= apgs[0]  oString: fun   }
 xt

wa  */
   is.eeeee 
   CHABody(
 xt
 DER":P@feonfii
P@feonfii  ey can  hey can  /nfig = this.cTn (dd in,  eent W successfifam*whue,*
  "rs.
gConr" 
fvlem=ycig = this.c i*cute.
d.&      cln fhavnd M
s.e CCs.
gConrig = thisn ftr moreould be 
    Tn (Crewsilent W
    (usulueye*
  
fvlem=ycn   )&      cln ftr more    va[]} apgs Tn (Crewsilent W,pgu**
    F.r &      cln cohe C* Module success, apgs[0]ror (sequluc ht  eweye.hcl.wi ct rhr&      cln eacc ht 
fvlem=yf// Hel  nod/*r more    var obj Tcutscvleto> 0)   F.r cohe C* Module success,// Hel  nod*
isdor (susulueyeequluc ht ownes.ig = this.m = this.ebe.e CCs.
gConr:,tarCase();t   , apgs, o> 

wa  */
    Rtspexcrs.
gConr/= apgs[0]   oString: fun   }
ypringrs.
gConr/== 'suld b'

wa  */
   is.eeeee 
   nfg.s* dass ffva rs.
gConr",  {
};

 .getE    :MById(rs.
gConr)**
        */
    Rts}hP@feonfii  ey can  hey can  /nfig = this.cfhavnd M_remohalent L    onrsig = this.cfdes
  pwnP cRemohas
dSl of (kckDOM eent W successpeoNIT(kckHTMLig = this*  
    :M(s)gthatepldggesdecut ispOwn/ore*
  too, ip.&      cl d/*f icbsul_DOCUM   nm = this.e_remohalent L    onrs:,tarCase();

wa  */
   a  */
    RtspexcaE    :Msg     * _rs.
 xttizepacka/**
rs.
 nE    :Mstizepacka/**
rs.
 oE    :MEKoString: functioi  tuplicate = t   }aE    :Ms

wa  */
   is.eeeeenE    :Msg  aE    :Ms.length KoString: functioi  }nE    :Msg> 0)r toString: functiooooo  =c E    :Msg- 1            do {
     dor toString: functiooooooooooE    :Mg  aE    :Ms[i]  zepacka/**
rs.
         Eng f.remohaL    onr(oE    :ME "moonlohas"E }
   onCoh
 xtMoonl has AH       AfgQussssctiossssEng f.remohaL    onr(oE    :ME "moonlmoha"E }
   onCoh
 xtMoonlMoha AH       AfgQussssctiossssEng f.remohaL    onr(oE    :ME "moonlout"E }
   onCoh
 xtMoonl ut
 DER":P@feonfiiiiiiiii}t  */cka/**
rs.
  funwhile di--     */
    Rtsssss}   */
    Rts}
P@feonfii  ey can  hey can  /nfig = this.cTn (dd in,  eent W successfifam*whue,*
  "rs.
 xt"e
fvlem=ycig = this.c i*cute.
d.&      cln fhavnd M
s.e CCs.
 xt&      cln ftr moreould be 
    Tn (Crewsilent W
    (usulueye*
  
fvlem=ycn   )&      cln ftr more    va[]} apgs Tn (Crewsilent W,pgu**
    F.r cohe C* Moduleig =   cln  success, apgs[0]ror (sequluc ht  eweye.hcl.wi ct rhreacc ht 
fvlem=yf// Hel  nod/*r more    var obj Tcutscvleto> 0)   F.r cohe C* Module success,// Hel  nod*
isdor (susulueyeequluc ht ownes.ig = this.m = this.ebe.e CCs.
 xt:,tarCase();t   , apgs, o> 

waa  */
    Rtspexcrs.
 xt/= apgs[0],izepacka/**
rs.
 aE    :Mstizepacka/**
rs.
 nE    :Mstizepacka/**
rs.
 oE    :MEKoString: functioi  tuplicate = t   }rs.
 xt)  ttoString: functio// N.rmali   *r moetin Coto at/apt WKoString: functioi  }! }rs.
 xtc nstastringApt W
     DEFAUL.               }
ypringrs.
 xt/=in"suld b")r toString: functiooooooooo 
   nfg.s* dass ffva rs.
 xt",/[ {
};

 .getE    :MById(rs.
 xt)]**
        */
    Rtsssssssss}       o// Assumd b *
isd i*,n 
    :MtoString: functiooooooooo 
   nfg.s* dass ffva rs.
 xt",/[rs.
 xt]**
        */
    Rtsssssssss}   */
    Rtsssssssssrs.
 xt/= }
   nfg g* dass ffva rs.
 xt"     */
    Rtsssss} toString: functio// Remohaeatygex   ingvmoonlohas/moonlout l    onrsig = thisoooooooo 
   _remohalent L    onrs(     DEFAUL.        // Addvmoonlohas/moonlout l    onrsd bscoh
 xt/
    :Msig = thisoooooooo 
   _rs.
 xt/= rs.
 xt    DEFAUL.        aE    :Msg     * _rs.
 xt             do {
    }aE    :Ms

wa  */
   is.eeeeeeeeenE    :Msg  aE    :Ms.length KoString: functio {
    }nE    :Msg> 0)r toString: functiooooo {
   =c E    :Msg- 1            do {
         dor toString: functiooooooooooooooE    :Mg  aE    :Ms[i]  zepacka/**
rs.
               /**.P  oE    :ME "moonlohas"E }
   onCoh
 xtMoonl hasbs      DER":cka/**
rs.
               /**.P  oE    :ME "moonlmoha"E }
   onCoh
 xtMoonlMohabs      DER":cka/**
rs.
               /**.P  oE    :ME "moonlout"E }
   onCoh
 xtMoonl utbs      DER":cka/**
rs.
         }   */
    Rtsssssssssoooowhile di--     */
    Rtsssss    }   */
    Rtsssss}t  */cka/**
ri
P@feonfii     var oCo/ END BUILT-IN PROPERTYeEVENT HANDLERS //hhey can  // BEGIN BUILT-IN DOM EVENT HANDLERS //hhey can  /nfig = this.cTn (dd in,  eent W successfifam*whue,*
  onlr moves
, e{moonl while ig = this.congr (kck oh
 xt/
    :M ig = thisn fhavnd MonCoh
 xtMoonlMoha// Hel  nod/*r moreDOMlent e e/Tk:B fgy = /DOM eent // Hel  nod/*r more    var obj Tcuto> 0) g,pgu**
 ig = this.m = this.eonCoh
 xtMoonlMoha: tarCase();g, o> 

wa  */
    Rtso> .pageXg    /**.g* dageX(e   oString: funo> .pageYg    /**.g* dageY(e   oString:}     var oConfig = this.cTn (dd in,  eent W successfifam*whue,*
  onlr moonlsoohasd ht _DOCUM   .crs.
 xt/
    :M ig = thisn fhavnd MonCoh
 xtMoonlOvnrig = thisn ftr moreDOMlent e e/Tk:B fgy = /DOM eent // Hel  nod/*r more    var obj Tcuto> 0) g,pgu**
 ig = this.m = this.eonCoh
 xtMoonlOvnr: tarCase();g, o> 

wa  */
    Rtspexcrs.
 xt/= }
     e();

         } oh
 xt titl@)r toString: functioo> ._tempTitl@ = rs.
 xt titl@    */
    Rtsssssrs.
 xt titl@e= ""    */
    Rts} toString: funo/ Fifasfifs*, tophon.r disabled eHAd": (kckl   onrig = this his   }o> .fifalent ("coh
 xtMoonl has"E rs.
 xtt e)o!iintdSetr&& !o> .nfg g* dass ffva disabled"
)  ttoString: functioo/ S opet
  too, ipreoNITbeingvhidden }eHAde()laew moonlout)FECE_CO =========   }o> .hid dascId)r toString: functioooooclearTimlout}o> .hid dascId)    */
    Rtsssss    o> .logges.log("Clearile.hid
d imlr: " + o> .hid dascIdE " iml    oString: functioooooo> .hid dascIdo   rty  ing: functiooooo} toString: functio  /**.P  rs.
 xtt "moonlmoha"E o> .onCoh
 xtMoonlMohabso> 
    DEFAUL.        /nfig = this hisssss.cTn (uniqut 
fvtru dID associfn drct peent(thread re Yon  }
 gig = this hisssss.ceacgshownctiecutToo, ip.ig = this hisssss.cf
    i:MtoString: function/hey can  Checooooo> .showdascIdo  o> .doShow;g, rs.
 xt)  ing: functioooooo> .logges.log("Set ingvshow too, iprtimlout: " + o> .showdascIdE " iml    oString: fun}
P@feonfii     var oConfig = this.cTn (dd in,  eent W successfifam*whue,*
  onlr moonlsooutaof*// Hel  nod*
ck oh
 xt/
    :M ig = thisn fhavnd MonCoh
 xtMoonlOu // Hel  nod/*r moreDOMlent e e/Tk:B fgy = /DOM eent // Hel  nod/*r more    var obj Tcuto> 0) g,pgu**
 ig = this.m = this.eonCoh
 xtMoonlOut: tarCase();g, o> 

wa  */
    Rtspexcel/= }
     e();

         }o> ._tempTitl@)r toString: functioel titl@e= o> ._tempTitl@  ing: functioooooo> ._tempTitl@ =  rty  ing: functioi tuplicate = t   }o> .showdascId)r toString: functioclearTimlout}o> .showdascId)  ing: functioooooo> .logges.log("Clearile.show timlr: " + o> .showdascIdE " iml    oString: funooooo> .showdascIdo   rty  ing: functioi tuplicate = t   }o> .hid dascId)r toString: functioclearTimlout}o> .hid dascId)    */
    Rtssssso> .logges.log("Clearile.hid
d imlr: " + o> .hid dascIdE " iml    oString: functioo> .hid dascIdo   rty  ing: functio} 
ing: functioo> .fifalent ("coh
 xtMoonl ut"E gt,o       */
    Rtso> .hid dascIdo  eHATimlout}hasOwnP        DEFAUL.        o> .hid 
  DER":P@feonfiiE o> .nfg g* dass ffva hid deOwn")   oString:}     var oCo/ END BUILT-IN DOM EVENT HANDLERS //hhey can  /nfig = this.cPfvtru hs
, e{ hownctio  ecutToo, iptby eHAMnction (timlout deOwn ig = this.cand o N1Ch o  ecutToo, ip.ig = thisn fhavnd MdoShow// Hel  nod/*r moreDOMlent e e/Tk:B fgy = /DOM eent // Hel  nod/*r moreHTMLE    :Me rs.
 xt/Tk:B fgy = /rs.
 xt/
    :M// Hel  nod/return hNumb
 } Tht 
fvtru dID ore*
  timlout hasOwnP  associfn dr// Hel  nodct pedoShow// Hel  nom = this.edoShow: tarCase();g, rs.
 xt)  ttoString: funpexco N1Ch = }
   nfg g* dass ffva xyoAN1Ch")tizepacka/**
rs.
 xO N1Ch = oAN1Ch[0],izepacka/**
rs.
 yO N1Ch = oAN1Ch[1],izepacka/**
rs.
 me/= }
     e();

         }UA.ss far&& rs.
 xt tagN   a&& azepacka/**
rrrrrrs.
 xt tagN   .toUps fCas 
 /=in"A")r toString: functioyO N1Ch += 12  ing: functio} 
ing: functioreturn eHATimlout}hasOwnP       toString: functiopexc
xt/=   .nfg g* dass ffva 
 xt"   toString: functioo/ titl@edoe    reohas-rid
d  xtig = this his       }  ._tempTitl@ && (
xt/==in"" ||     eplte..isUndd Cond(
xt) ||     eplte..isNuue( xt)
     DEFAUL.              .CHABody(  ._tempTitl@     */
    Rtsssss}       toString: functio      .nfg refifalent ("
 xt"     */
    Rtsssss} toString: functio  .logges.log("Show too, ip"E " iml    oString: functio  .mohaTo(  .pageXg+ xO N1Ch,   .pageYg+ yO N1Ch   toString: functio   }  .nfg g* dass ffva pe ent ohasOwp"
     DEFAUL.              .pe ent  hasOwp(  .pageX,   .pageY     */
    Rtsssss} toString: functioEng f.remohaL    onr(rs.
 xtt "moonlmoha"E   .onCoh
 xtMoonlMoha AH oString: functio  .ns.
 xtTldggesEng f.fifa
rs.
 xt)   oString: functio  .show
  D oString: functio  .hid dascIdo    .doHid 
  DER":P@feonfictio  .logges.log("Hid
d oo, iprtiml aOwnha: " +   .hid dascIdE " iml    DER":P@feonfiiE }
   nfg g* dass ffva showdeOwn")   oString:}     var oConfig = this.cSetse*
  timlout hacc ht auto-dismiss deOwn,gwhich byddd in,  is 5r// Hel  nodsecthdsE   aoringprate  too, iptor (s,utomaticlueyedismiss itself*// Hel  nodaftin 5dsecthds orebeingvdispOwnlo ig = thisn fhavnd MdoHid
// Hel  nom = this.edoHid
:,tarCase();

wa:,preeeeeeeeepexcme/= }
     e();

        .logges.log("SHAMnctihid
d oo, iprtimlout"E " iml    DER":P@feonfireturn eHATimlout}hasOwnP       toString: functio  .logges.log("Hid
d oo, ip"E " iml    oString: functio  .hid 
  DDER":P@feonfiiE }
   nfg g* dass ffva autodismissdeOwn")            }     var oConfig = this.cFirem*whue,*
  Too, ip  i*mohad, *
isdeent W successisdonlooto ig = this* pe ent ,*
  Too, ipreoNITohasOwppnctict pedus rs.
 xt/
    :M ig = thisn fhavnd Mpe ent  hasOwyig = thisn f*r moreNumb
 } pageXgTht tcctordinate posiodul ore*
  moonl poi:Mnrig = thisn ftr moreNumb
 } pageYgTht ycctordinate posiodul ore*
  moonl poi:Mnrig = thisnm = this.epe ent  hasOwp:,tarCase();pageX, pageY 
wa  */
   a  */
    Rtspexch";
  o  }
   
    :M oAN1ChH";
  ,izepacka/**
rs.
 moonlPoi:M/   ew     eputil.Poi:M;pageX, pageY ,izepacka/**
rs.
 
    :MReg on   upergHAReg on(}
   
    :M   oString:
cka/**
rs.
 
    :MReg on. ope-= 5;
cka/**
rs.
 
    :MReg on.lefte-= 5;
cka/**
rs.
 
    :MReg on.r;
  o+= 5;
cka/**
rs.
 
    :MReg on.bottomo+= 5;
cka/**
r
cka/**
rs.
 }
   logges.log("rs.
 xt/" + 
    :MReg onE "  ip")    */
    Rts}
   logges.log("moonl " +  oonlPoi:ME "  ip")    */
    e();

         }
    :MReg on.rs.
gCos( oonlPoi:M
     DEFAUL.        }
   logges.log("OVERLAP"E "warn    oString: functio 
   nfg.s* dass ffva y", (pageYg-ch";
  o- 5) AH       AfgQu}
P@feonfii      var oConfig = this.cfhavnd MonR =d rig = this.cfdes
  pwnP c"y =d r" eent W successfor ecutToo, ip.ig = thisn ftr moreould be p_sT   cSuld barepe = :Mnction (n   ao  ecuteent W*// Hel  nod*
atewas fifam.ig = thisn ftr moreApt W} p_aApgsgApt Waof*,pgu**
   = :M*whue,*
  eent     */
   *ewas fifam.ig = thisnm = this.eonR =d r:,tarCase();p_sT   , p_aApgs

wa
    e();

      hasOwnP  1izeShadow(

wa
    e();

          pexcoE    :Mg  }
   
    :M,  DEFAUL.            oShadowg  }
   u=d rOwyAH       AfgQutoString: functio   }oShadow)r toString: functiooooooShadow.style.width   (oE    :M oAN1ChWidth + 6) + "pt   oString: functiooooooShadow.style.h";
  o  (oE    :M oAN1ChH";
   + 1) + "pt  utoString: functio}H       AfgQutoString: fun} toString: funhasOwnP  addShadowVi  }
 C{
  ()r toString: functiouperaddC{
  (}
   u=d rOwy, "yui-tt-shadow-vi  }
 "   toString: functio   }UA.i@)r toString: functiooooo}
   f.sceU=d rOwyRedraw
  DER":P@feonfictio}H       AfgQu} toString: funhasOwnP  remohaShadowVi  }
 C{
  ()r toString: functiouperremohaC{
  (}
   u=d rOwy, "yui-tt-shadow-vi  }
 "          AfgQu} toString: funhasOwnP  nreat
Shadow(

wa
    e();

          pexcoShadowg  }
   u=d rOwy,  DEFAUL.            oE    :MEKoString: functioooooModulh*           dooooooooonIE*           dooooooooom@  ing:toString: functio   }!oShadow)r toStr  DEFAUL.            oE    :Mg  }
   
    :M  oString: functioooooModulh =     epwidg* .Modulh  oString: functiooooonIE = UA.i@  oString: functiooooome/= }
     e();

       functio   }!m_oShadowTemplabe)r toString: functiooooo {
 m_oShadowTemplabed=  {
};

 .nreat
l    :M("div    oString: functiooooooooom_oShadowTemplabe. {
  N   a= "yui-tt-shadow   oString: functiooooo}they can  ChecccccccccoShadowg  m_oShadowTemplabe. {one/**
(hdSet)    DEFAUL.            oE    :M..hcendChild}oShadow)    DEFAUL.            }
   u=d rOwy = oShadow    DEFAUL.            // Backwar M
smpat }ility, eent }
ougpedu's 
fvbabeyeig = this his        // i:Mn=d da bsbck"tfivabe", ite  n't markwi a  =uch ie,*
  api  {
sig = thisoooooooo    }
   _shadowg  }
   u=d rOwyAHig = thisoooooooo    addShadowVi  }
 C{
  .alue(    )     */
    Rtsoooo    }
   srs.
     "bet.reShow", addShadowVi  }
 C{
     oString: functiooooo}
   srs.
     "hid ", remohaShadowVi  }
 C{
  )     */
    Rtsoooo       }bIEQuirks)r toString: functiooooo {
 window.sHATimlout}hasOwnP      eig = this his                1izeShadow.alue(me);cig = this hisssssssssssssiE 0)  ing:toString: functioctiooooo}
   nfg.srs.
    ToCe Conlent ("width , sizeShadow   oString: functiooooooooo}
   nfg.srs.
    ToCe Conlent ("h";
   , sizeShadow   oString: functiooooooooo}
   srs.
     "cute.
Cs.
 :M , sizeShadow    oString: functioooooooooModulh.
 xtResizeEng f.srs.
     sizeShadow, *
is**
        */
    Rtsssssssssoooo}
   srs.
     "desuloy", hasOwnP        DEFAUL.        ctioooooooooModulh.
 xtResizeEng f.unsrs.
     sizeShadow, *
is  DER":cka/**
rs.
         }     */
    Rtsssss    }   */
    Rtsssss}t  */cka/**
ri
toString: funhasOwnP  onBet.reShow      DEFAUL.        nreat
Shadow.alue(    )    */
    Rtsoooo}
   unsrs.
     "bet.reShow", onBet.reShow          AfgQu} toString: fun   }

   nfg g* dass ffva vi  }
 "      DEFAUL.        nreat
Shadow.alue(    )    */
    Rts}       toString: functio}
   srs.
     "bet.reShow", onBet.reShow          AfgQu}    AfgQu         }     var oConfig = thiss.cF.scese*
  u=d rOwy 
    :Mgtop   repgCotad, *
rougpe*
  applicModul/remohr ig =   do n orea yui-f.sce-redraw r{
  d bsent(u=d rOwy 
    :M.ig =   do n ig =   do n fhavnd Mf.sceU=d rOwyRedrawig =   do nm = this.ef.sceU=d rOwyRedraw :,tarCase(;

wa  */
    Rtspexctt/= }
      */
    RtsuperaddC{
  (}t u=d rOwy, "yui-f.sce-redraw    oString: funsHATimlout}hasOwnP ;

wuperremohaC{
  (}t u=d rOwy, "yui-f.sce-redraw   iE 0)  ing:    }     var oConfig = this*cRemohas
*
  Too, ipr
    :MgeoNIT(kckDOM 
"wieHAs
dSl child    */
   *e
    :Msgto  rty ig = thisn fhavnd Mdesuloyig = thisnm = this.edesuloy:,tarCase();

wa  */
   a  */
    Rts// Remohaeatygex   ingvmoonlohas/moonlout l    onrsig = thisoooo 
   _remohalent L    onrs(     DEFAUL.    Too, ipasus f {
  .desuloy.alue(    )   a  */
    ing:    }    */
    ing:    onfig = this.cReturnsda/suld b repe = :MTofid    ecuto> 0)  &      cl  fhavnd MtoSuld big = thisn freturn hould be Tcutsuld b repe = :MTofid    ecutToo, ipig = this.m = this.etoSuld b:,tarCase();

wa  */
    Rtsreturn "Too, ipr" + }
   id  ing:    } ing:toStr}    }() AH(tarCase();

wa:,preonfig = .cPanel  i*,n imp    :MTofid     hasOwna kat behaves,like anT Sewindow, onost* ct pea draggable(headasda"wianTopwnP r arlo    cP  at *
  top r;
  .ig = n fn   spach     epwidg* ig = n f {
  aPanelig = n fextends     epwidg* . hasOwnig = n f o.embuctorig = n fthat should be eloTcut
    :M IDarepe = :Mnction (Panel <em>OR</em>ig = n fthat shHTMLE    :Me eloTcut
    :M repe = :Mnction (Panelig = n fthat sh    var onlrCe ConaTk:B ohe C* Module > 0) gliterr ars.
gConctiig = *sent(cohe C* Moduley
atee();

    CHAdfacg(kispPanel. Set(cohe C* Moduleig = * d{
};

 awnP ct.r mo @ de
gCls.   donm = th    epwidg* .Panel = hasOwnP   gt,ouslrCe Con

wa  */
       epwidg* .Panel.sus f {
  . o.embuctor.alue(    , gt,ouslrCe Con
;     }AH     pexc_ fgy = Modalo   rty  :,prepexcLte./      eplte.tizepacka/Utilg      eputil,izepacka/upeg  Util.upebizepacka/Eng fa  Util.lent bizepacka/Crewsilent W  Util.Crewsilent ,izepacka/KeyL    onrg      eputil.KeyL    onrbizepacka/Ce Cona  Util.Ce Con*izepacka/ hasOwna=     epwidg* . hasOwntizepacka/Panel =     epwidg* .Panelbizepacka/UA =     epenv.uabiizepacka/bIEQuirks   }UA.i@o&& (UA.i@o<= 6 ||  {
};

 .csmpatModeciin"BackCsmpat") Oiizepacka/m_oMaskTemplabe  zepacka/m_oU=d rOwyTemplabe  zepacka/m_oClo  I o.Templabe     var oConfig = this.cConstasM repe = :Mnction (n   ao  ecutPanel'sdeent sig = g = n ftfvlem=ycEVENT_TYPESig = g = n ftfivabe   do  do* ffi r ig =   do* f
        vaig = g = nm = this.eEVENT_TYPES =   zepacka/**
r"BEFORE_SHOW_MASK" :,"bet.reShowMask",izepacka/**
r"BEFORE_HIDE_MASK" :,"bet.reHid
Mask",izepacka/**
r"SHOW_MASK": "showMask",izepacka/**
r"HIDE_MASK": "hid Mask",izepacka/**
r"DRAG": "drag"         }     var oConfig = this.cConstasM repe = :Mnction (Panel'sdcthe C* Module
fvlem=iesig = g = n ftfvlem=ycDEFAULT_CONFIGig = g = n ftfivabe   do  do* ffi r ig =   do* f
        vaig = g = nm = this.eDEFAULT_CONFIG =   tuplicateeeee"CLOSE": {/           doooookey: "rlo  ",/           doooooct rh: }   ,            doooooct idabor: Lte..isBoolean,            dooooosus f edes: ["vi  }
 "]            do}Oiizepacka/**
r"DRAGGABLE": {           doooookey: "draggable",/           doooooct rh: (Util.uD ? 
    : hdSet),            doooooct idabor: Lte..isBoolean,            dooooosus f edes: ["vi  }
 "]             do}Oiizepacka/**
r"DRAG_ONLY" :,{           doooookey: "dragonly",izepacka/**
rs.
 ct rh: hdSetEKoString: functioct idabor: Lte..isBoolean,           dooooosus f edes: ["draggable"]           do}Oiizepacka/**
r"UNDERLAY": {/           doooookey: "u=d rOwy",/           doooooct rh: "shadow ,            dooooosus f edes: ["vi  }
 "]            do}Oiizepacka/**
r"MODAL": {/           doooookey: "modal",/           doooooct rh: hdSetE            doooooct idabor: Lte..isBoolean,            dooooosus f edes: ["vi  }
 ", "zindex"]           do}Oiizepacka/**
r"KEY_LISTENERS": {           doooookey: "keyl    onrs",izepacka/**
rs.
 suspe =slent : 
   ,           dooooosus f edes: ["vi  }
 "]           do}Oiizepacka/**
r"STRINGS" :,{           doooookey: "suld bs",izepacka/**
rs.
 sus f edes: ["rlo  "],izepacka/**
rs.
 ct idabor: Lte..is    va,izepacka/**
rs.
 ct rh:  toString: functioooooclo  : "Clo  "toString: functio}t  */cka/**
ri
P@feonfiiAH     onfig = .cConstasM repe = :Mnction (dd in,  pSS  {
  donloofosda/Panelig = n ftfvlem=yc    epwidg* .Panel.CSS_PANELig = n fCtaticig = n ffi r ig = n f
    Suld big = nm = thPanel.CSS_PANELa= "yui-panel"  ing:toStronfig = .cConstasM repe = :Mnction (dd in,  pSS  {
  donloofosda/Panel's onost* crwppnctirs.
gConrig = n ftfvlem=yc    epwidg* .Panel.CSS_PANEL_CONTAINERig = n fCtaticig = n ffi r ig = n f
    Suld big = nm = thPanel.CSS_PANEL_CONTAINERa= "yui-panel-rs.
gConr"AH     onfig =  .cConstasM repe = :Mnction (dd in,  1Ch o  f{
}sable(
    :Msgig =  .con (kckpagewhich ModaloPanelsMor (spe ent ,actru d b,xohunig =  .c*
  modalomaskrisddispOwnloig =  .cig =  .cftfvlem=yc    epwidg* .Panel.FOCUSABLEig =  .cfCtaticig =  n f
    Apt Wig = tnm = thPanel.FOCUSABLEa= [
P@feonfi"a",izepacka/"button",izepacka/"s
  ch",izepacka/"
 xtarea",izepacka/"inpuh",izepacka/"if moe"toStr]   oStr// Pfivabe/Crewsilent Wl    onrsi     on izepacka/"bet.reR =d r" eent W successy
atenreat
i*,n 
mp=ycheadasdfosda/Panel izepacka/ nstastrn   dus "draggable" cthe C* Module
fvlem=ycd "CHAd bs"
   " ig = thisa"winocheadasd 
  b rntnreat
d.   donm :,prehasOwnP  nreat
Headas;p_sT   , p_aApgs

wa
   cka/   }!}
   headasd&& 

   nfg g* dass ffva draggable"      DEFAUL.     
   CHAHeadas;"&#160;    oString:i     }H     on/         "hid " eent W successy
atee* s,dcPanel  nstastr'sp width          cthe C* Module
fvlem=ycbackd bsdus origi r /ct rhrbet.re,izepacka/"s
hWidthToO N1ChWidth"ewas cluewi.   donm :,prehasOwnP  rrstoreOrigi r Widthlp_sT   , p_aApgs, p_o    va

waizepacka/pexcsOrigi r Width =Bp_o    va[0],izepacka/**
rsNewWidth   p_o    va[1],izepacka/**
roCe Cona     * nfg,izepacka/**
rsCfgy = Width =BoCe Con g* dass ffva width      DEFAUL.   }sCfgy = Width =  sNewWidth

wa  */
    RtsoCe Con s* dass ffva width , sOrigi r Width
          i :,preeeee}
   unsrs.
     "hid ", restoreOrigi r Width, p_o    va
;     }H     on/         "bet.reShow" eent W successy
atee* s,dcPanel  nstastr'sp width          cthe C* Module
fvlem=yc bsent(ct rhroModus root HTML/         
    :Ms'sko N1ChWidth   donm :,prehasOwnP  s
hWidthToO N1ChWidth;p_sT   , p_aApgs

waizepacka/pexcoCe Con*izepacka/**
rsOrigi r Width,izepacka/**
rsNewWidth    DEFAUL.   }bIEQuirks)r tizepacka/**
roCe Cona     * nfg;izepacka/**
rsOrigi r Width =BoCe Con g* dass ffva width    zepacka/**
r zepacka/**
r   }!sOrigi r Width || sOrigi r Width ==o"auto")r toStr  DEFAUL.        sNewWidth   (}
   
    :M oAN1ChWidth + "pt    oStr  DEFAUL.        oCe Con s* dass ffva width , sNewWidth     DEFAUL.        }
   srs.
     "hid ", restoreOrigi r Width, toString: functiooooo[(sOrigi r Width || ""), sNewWidth]   zepacka/**
r zepacka/**
ri
P@feonfii     }H         epextend(Panelb/ hasOwn,r tizepacka/onfig = this.cTn ( hasOwnainitiali Modulehavnd ,gwhich isdexecutloofosd hasOwnaa"wiig = this.cdSl of dus srs {
  es.cTnisphavnd M i*,utomaticlueyecluewi byd ht ig = this.c o.embuctor, ("wiee* s,upcdSl DOM refey =cesefosdpe -ex   ingvmarkup, ig = this.cand  reat
i*requirem*markup    du   "  rkalreadydpe = :M.&      cl  fhavnd Mtnitig = this.cftr moreould be eloTcut
    :M IDarepe = :Mnction ( hasOwna<em>OR</em>ig = this.cftr moreHTMLE    :Me eloTcut
    :M repe = :Mnction ( hasOwyig = thisn f*r more    var onlrCe ConaTk:B ohe C* Module > 0) gliterr aig = this.ccoo
gCoringprescohe C* Moduley
atee();

    CHAdfacg(kisp hasOwyaaig = this.cSet(cohe C* Moduled{
};

 awnP ct.r mo @ de
gCls.   dothis.m = this.etnit: tarCase();gt,ouslrCe Con

wazepacka/**
r/fig = this hisssss Noted*
atew@edon't p
  d 
  onlr cthe Cdin hureeyet becaonl ig = this hisssss w@eonlyewa fa tdexecutlooo=ce, at *
  lowest srs {
   leenyig = thisg = n/hig = thisg = Panel.sus f {
  .tnit.alue(    , gt/*,ouslrCe Con*/  DtoString: fun}
   bet.reInit  /**.fifa
Panel  DtoString: funuperaddC{
  (}
   
    :M,hPanel.CSS_PANEL  DtoString: fun}
   buildWrwppas;    e();

         }onlrCe Con

wa  */
   is.eeeee 
   nfg..hclyCe Con
onlrCe Con**
        */
    Rts}htoString: fun}
   srs.
     "showMask",o 
   _addF{
}sHsuccess)    */
    Rts}
   srs.
     "hid Mask",o 
   _remohaF{
}sHsuccess)    */
    Rts}
   srs.
     "bet.reR =d r", nreat
Headas  DtoString: fun}
   srs.
     "y =d r"E tarCase(;

wa  */
    Rts fun}
   setFifs*LastF{
}sable
  DER":P@feonfictio}
   srs.
     "cute.
Cs.
 :M , }
   setFifs*LastF{
}sable     */
    Rts}  DtoString: fun}
   srs.
     "show",  
   _f{
}sOnShow   toString: fun}
   init  /**.fifa
Panel  D
    Rts}     var oConfig = thiss.cfhavnd M_o E    :MF{
}sig = thiss.cftfivabe   do  do fig = thiss.c"f{
}s" eent W successfor a f{
}able(
    :M. Unlooto ,utomaticlueyig = thiss.cblur ecut
    :MgwhrntiM receivesefo
}soto ensuregprate  Panelig = thiss.c nstastr'spmodali=ycd "  rkcsmpromislo ig = this fig = thiss.cf*r morelent e e/Tk:BDOM ing fg > 0) ig = thiss.m = this.e_o E    :MF{
}s :,tarCase(;e){  e();

        (_ fgy = Modalo =     *    toString: functiopexc
argCh =   /**.g* TargCh;e)*           doooooooood{
d=  {
};

 . {
};

 E    :MEKoString: functiooooo nsideD{
d= (
argCh !iind{
d&& 
argCh !iinwindow   toString: functioo/ maskrand  {
};

 E    :M checks addloofosdIE,gwhich fo
}slsoon (kckmaskrwhrntiM'sdclicklooo=, ("wifo
}slsoon toString: functioo/ ecut {
};

 E    :ME whue,*
   {
};

  .
 ollbars aredclicklooo=toString: functio   } nsideD{
d&& 
argCh !iin}
   
    :Md&& 
argCh !iin}
   maskr&& !uperisA=cestor(}
   
    :M,h
argCh
     DEFAUL.            tryr toString: functiooooooooo 
   _f{
}sFifs*Modal
  DER":P@feonfictiooooo}ecltch(err) toString: functioooooooooo/ Jrew in canl w@efgCloto f{
}sig = thissUL.            tryr toString: functioooooooooctio   } nsideD{
d&& 
argCh !iin {
};

 .b

      DEFAUL.        ctiooooooooo    targCh.blur
  DER":P@feonfictioooooctiooooo}DER":P@feonfictioooooctio}ecltch(err2   e}DER":P@feonfictiooooo}   */
    Rtsssss}t  */cka/**
ri
P@feonfii     var oConfig = thiss.cF.
}slsoon (kckfifs*t
    :Mg   pe = :M,.ethinwisl hdSlscbackd bs(kckf{
}s mecuteism donloofosdig = thiss.cmodali=y.cTnisphavnd Mdoe    retry/cltchkf{
}s fgClures  Tk:Bcluewssisdre Yon  }
 gfosdcltchringexcepwnP stizepacka/*.cand takd b remedifl   asures.ig =   do n ig =   do n fhavnd M_f{
}sFifs*Modalig = thiss.m = this.e_f{
}sFifs*Modal :,tarCase(;

wa  */
    Rtspexcel/= }
  .fifs*E    :M  oString: fun   }
 )r toString: functioel f{
}s
  DER":P@feonfii       toString: functio   }

   _modalF.
}s)r toString: functiooooo}
   _modalF.
}s f{
}s
  DER":P@feonfionfii       toString: functiooooo}
   innerE    :M.f{
}s
  DER":P@feonfionfiit  */cka/**
ri
P@feonfii     var oConf ig =   do n  fhavnd M_addF{
}sHsuccessig =   do n  f*f icbsul_DOCUM    n  _DOCUM    n  "showMask" eent W successy
ateadds,dc"f{
}s" eent W successto ,llig = thiss.c f{
}sable(
    :Msgie,*
   {
};

  to enf.sce,dcPanel  nstastr'spig = thiss.c modali=yceoNITbeingvcsmpromislo ig = this fig = thiss.ccf*r morp_sT   ceould be Crewsi eent W
   ig = thiss.ccf*r morp_aApgsgeApt W} Crewsi eent W,pgu**
  ig = thiss.m = this.e_addF{
}sHsuccess:,tarCase(;p_sT   , p_aApgs

wa
   cka/////   }!}
   fifs*E    :M)r toString: functio   }UA.webkiM || UA.ss fa     DEFAUL.               }!

   _modalF.
}s)r toString: functiooooooooo   * _rreat
HiddenF.
}sl    :M(     */
    Rtsssss    }   */
    Rtsssss}       toString: functiooooo}
   innerE    :M.tabIndex/= 0 DER":P@feonfionfiit  */cka/**
ri
P@feonfioooo   * _sHATabLoop(}
   fifs*E    :M,  
   laewE    :M) DER":P@feonfi  /**.P F.
}s( {
};

 . {
};

 E    :MEo   * _o E    :MF{
}s, *
is**
        */
    Rts_ fgy = Modalo  }
      */
   i     var oConfig = thiss.cCreat
i*,vhidden f{
}sable(
    :M,donlooto f{
}s ontizepacka/*.cto enf.sce,modali=yceosdbrowsnrsdie,which fo
}s can  rig = thiss.cbee.hcl.wi  bs(kckrs.
gConr/box.ig =   do n ig =   do n fhavnd M_rreat
HiddenF.
}sl    :Mig = thiss.cftfivabe   do  do fm = this.e_rreat
HiddenF.
}sl    :M :,tarCase(;

wa  */
    Rtspexced=  {
};

 .nreat
l    :M("button"     */
    Rtse.style.h";
  o  "1pt   oString: fune.style.width   "1pt   oString: fune.style.posiodul =o"absolute   oString: fune.style.lefte=o"-10000em   oString: fune.style.opaci=yc= 0 DER":P@feonfie.tabIndex/= -1            do}
   innerE    :M..hcendChild}      */
    Rts

   _modalF.
}s/= e    */
   i     var oConfig = thiss.ccfhavnd M_remohaF{
}sHsuccessig =   do n  f*f icbsul_DOCUM    n_DOCUM    n  "hid Mask" eent W successy
ateremohas
dSl "f{
}s" eent W success addloo_DOCUM    n  byd ht "addF{
}s  /**Hsuccess"phavnd  ig = this fig = thiss.ccf*r morp_sT   ceould be Eent W
   ig = thiss.ccf*r morp_aApgsgeApt W} Eent WApgu**
  ig = thiss.m = this.e_remohaF{
}sHsuccess:,tarCase(;p_sT   , p_aApgs

wa
   cka/////Eng f.remohaF{
}sL    onr( {
};

 . {
};

 E    :MEo   * _o E    :MF{
}s, *
is    e();

         }_ fgy = Modalo =    *                    _ fgy = Modalo   rty              i
P@feonfii     var oConfig = thiss.cF.
}sW successfor ecutshow eent // Hel  n fig = thiss.cfhavnd M_f{
}sOnShowig = thiss.cf*r moreould be 
    Eent WT   ig = thiss.cftr moreApt W} apgs Eent W,pgu**
  ig = thiss.d/*r more    var obj AddiwnP r adata ig = thiss.m = this.e_f{
}sOnShow :,tarCase(;t   , apgs, o> 

waa  */
    Rts   }apgs && apgs[1]                    Eng f.stoplent (apgs[1]          AfgQu} toString: fun   }!}
   f{
}sFifs*;t   , apgs, o> 
)r toString: functio   }

   nfg g* dass ffva modal"
     DEFAUL.            t
   _f{
}sFifs*Modal
  DER":P@feonfictio}t  */cka/**
ri
P@feonfii     var oConfig = thiss.cSetsefo
}soto (kckfifs*t
    :Mg nion (Panel ig = this fig = thiss.cfhavnd Mf.
}sFifs*ig = thiss.cfreturn hBoolean} }   ,    suctru fuueyefo
}sld,ntdSetrethinwisl ig = thiss.m = this.ef.
}sFifs*:,tarCase();t   , apgs, o> 

wa  */
    Rtspexcel/= }
  .fifs*E    :M,efo
}sld intdSet  tuplicate = t   }apgs && apgs[1]                    Eng f.stoplent (apgs[1]          AfgQu} toString: fun   }
 )r toString: functiotryr toString: functioooooel f{
}s
  DER":P@feonfi= this.ef.
}sld in}    DER":P@feonfionfii cltch(err)r toString: functiooooo// IgnoreDER":P@feonfictio}t  */cka/**
ri
t  */cka/**
rreturn f.
}sld    */
   i     var oConfig = thiss.cSetsefo
}soto (kcklaew 
    :Mg nion (Panel ig = this fig = thiss.cfhavnd Mf.
}sLastig = thiss.cfreturn hBoolean} }   ,    suctru fuueyefo
}sld,ntdSetrethinwislig = thiss.m = this.ef.
}sLast:,tarCase();t   , apgs, o> 

wa  */
    Rtspexcel/= }
  .laewE    :M,efo
}sld intdSet  tuplicate = t   }apgs && apgs[1]                    Eng f.stoplent (apgs[1]          AfgQu} toString: fun   }
 )r toString: functiotryr toString: functioooooel f{
}s
  DER":P@feonfi= this.ef.
}sld in}    DER":P@feonfionfii cltch(err)r toStttttttttttttttttt// IgnoreDER":P@feonfictio}t  */cka/**
ri
t  */cka/**
rreturn f.
}sld    */
   i     var oConfig = thiss.cPf icbsul i:Mnrnfl   vnd Mf.s sHATabLoop,gwhich cancbee}sld byo_DOCUM    n srs {
  esoto jumpg niand modifyd ht ,pgu**
   p
  ul i:n   requirem ig = this fig = thiss.cfhavnd M_sHATabLoopig = thiss.d/*r moreHTMLE    :Me fifs*E    :Mig = thiss.d/*r moreHTMLE    :Me laewE    :Mig = thiss.cftf icbsul_DOCUM    n_DOCUM    nm = this.e_sHATabLoop :,tarCase(;fifs*E    :M,elaewE    :M)    DEFAUL.     
   CHATabLoop(fifs*E    :M,elaewE    :M)    */
   i     var oConfig = thiss.cSetseupcd tab,tshift-tab loop between (kckfifs*tand laew 
    :M ig = thiss.dtf vid d. NOTE:cSetseupc ht 
feent BackTabtand 
feent TabOut/KeyL    onrig = thiss.d nstastrn
fvlem=ies,gwhich arede =   eenrytiml tnisphavnd M i*invokem ig = this fig = thiss.cfhavnd MsHATabLoopig = thiss.d/*r moreHTMLE    :Me fifs*E    :Mig = thiss.d/*r moreHTMLE    :Me laewE    :Mig = thiss._DOCUM    nm = this.esHATabLoop :,tarCase(;fifs*E    :M,elaewE    :M)   a  */
    RtspexcbackTabt= }
  .
feent BackTab,h
abt= }
  .
feent TabOut,izepacka/**
rs.
 showlent W   
   Chowlent ,ihid
lent W   
   hid
lent   tuplicate = t   }backTab)r toSttttttttttttttbackTab.disable
  DER":P@feonfi= thChowlent  unsrs.
     backTab.enable,tbackTab  DER":P@feonfi= thhid
lent  unsrs.
     backTab.disable,tbackTab  DER":P@feonfi= thbackTabt= }
  .
feent BackTabo   rty  ing: functioi tuplicate = t   }tab)r toStttttttttttttttab.disable
  DER":P@feonfi= thChowlent  unsrs.
     tab.enable,ttab  DER":P@feonfi= thhid
lent  unsrs.
     tab.disable,tab  DER":P@feonfi= th
abt= }
  .
feent TabOuto   rty  ing: functioi tuplicate = t   }fifs*E    :M)r toString: functio}
  .
feent BackTabo   ew/KeyL    onr;fifs*E    :M,etoStttttttttttttttttt{shift:}   , keys:9}EKoString: functiooooo{fn:}
   f{
}sLast,tscvle:*
is**cogy ctScvle:*   }   */
    Rtsssss  DER":P@feonfi= thbackTabt= }
  .
feent BackTab  toString: functioChowlent  srs.
     backTab.enable,tbackTab**
        */
    Rtssssshid
lent  srs.
     backTab.disable,backTab**
        */
    Rtsi tuplicate = t   }laewE    :M)    DEFAUL.    ctio}
  .
feent TabOuto   ew/KeyL    onr;laewE    :M,etoStttttttttttttttttt{shift:hdSetE keys:9}EetoStttttttttttttttttt{fn:}
   f{
}sFifs*, scvle:*
is**cogy ctScvle:*   }   */
    Rtsssss  DER":P@feonfi= th
abt= }
  .
feent TabOut  toString: functioChowlent  srs.
     tab.enable,ttab**
        */
    Rtssssshid
lent  srs.
     tab.disable,tab**
        */
    Rts}hP@feonfii     var oConfig = thiss.cReturnsdat/apt Wao  ecut fgy = eyefo
}sable(itemsgwhich e =id
dct pinig = thiss.cPanel. TcutsCh o  f{
}sable(
    :Msg(kckmavnd Mlooksefosda @ de Condig = thiss.d nion (Panel FOCUSABLEaCtatice
fvlem=yig = this fig = thiss.cfhavnd Mg* F{
}sableE    :M ig = thiss.d/*r moreHTMLE    :Me root 
    :MgtopCtarMgeoNI ig = this fm = this.eg* F{
}sableE    :M  :,tarCase(;root)   a  */
    Rtsroot =sroot ||o}
   innerE    :M; a  */
    Rtspexcf{
}sable(= {}, panelo  }
      */
       fosd(pexcic= 0 cic<(Panel FOCUSABLE.length  i++)    DEFAUL.    ctiof{
}sable[Panel FOCUSABLE[i]] in}    DER":P@feonfi} toString: funo/ Not lookingvbyoTag, sistrnweewa fa
    :Msgie,DOM ordnrig = this hist  */cka/**
rreturn upergHAE    :M By}hasOwnP ;
 )r rreturn panel._testIfF{
}sable
gt,of{
}sable);siE  rty,sroot)    */
   i     var oConfig = thiss.cTnispise*
  test havnd M}sld byog* F{
}sableE    :M , topdetinmCongwhich 
    :Msgto ig = thiss.d ncluded nion (f{
}sable(
    :Msgl   . Unlrsph Waoenrrid
d nisp bscrewsii   behavior ig = this fig = thiss.cfhavnd M_testIfF{
}sableig = thiss.d/*r more    var eloTcut
    :M beingvtestndig = thiss.d/*r more    var f{
}sable(Tcuthash o  known f{
}sable(
    :Ms, nreat
d byoat/apt W-to-map ss fawnP  on(Panel FOCUSABLEig = thiss.cftf icbsul_DOCUM    nm = this.e_testIfF{
}sable:,tarCase(;et,of{
}sable)
wa
   cka/////   }el f{
}s && el t    !iin"hidden"r&& !el disabled && f{
}sable[el tagN   .toLow fCas 
 ]                    return }    DER":P@feonfi}             return tdSet    */
   i     var oConfig = thiss.cSetse*
  fifs*E    :Mtand laewE    :Mt nstastrn
fvlem=iesig = thiss.cto (kckfifs*tand laew f{
}sable(
    :Msgie,*
  Panel ig = this fig = thiss.cfhavnd MsetFifs*LastF{
}sable_DOCUM    nm = this.esHAFifs*LastF{
}sable :,tarCase(;

wa  DEFAUL.     
   fifs*E    :Mt   rty  ing: functio}
  .laewE    :Mo   rty  :,preeeeeeeeepexcel   :Msg     * g* F{
}sableE    :M 
  DER":P@feonfi}
   f{
}sableE    :M  =cel   :Ms  tuplicate = t   }el   :Ms.lengthg> 0)r toString: functio 
   fifs*E    :Mt  el   :Ms[0]  zepacka/**
rs.
 }
  .laewE    :Mo  el   :Ms[el   :Ms.lengthg- 1]  ing: functioi tuplicate = t   }t
   nfg g* dass ffva modal"
     DEFAUL.           * _sHATabLoop(}
   fifs*E    :M,  
   laewE    :M) DER":P@feonfi}hP@feonfii     var oConfig = thiss.cInitiali as
*
  crewsi eent sefosdModulh which aredfifam*ig = thiss.c,utomaticlueyeateap
fvlrifn rtimls byd ht Modulh  {
  ._DOCUM    nm = this.einit  /**s:,tarCase();

wa  */
    RtsPanel.sus f {
  .tnit  /**s.alue(    )     */
    RtspexcSIGNATUREa= Crewsilent .LIST     */
    Rts/nfig = this his*/Crewsilent Wfifam*aftin *
  modali=ycmaskrisdChownig = this his*/@eent WshowMaskEent // Hel  n    nm = this.e       * showMaskEent a     * nreat
lent (EVENT_TYPES.SHOW_MASK)    */
    Rts}
   showMaskEent .signatureg=cSIGNATURE     */
    Rts/nfig = this his*/Crewsilent Wfifam*bet.re,*
  modali=ycmaskrisdChown. Srs.
    rs can return tdSetcto pe ent ,*
 ig = this his*/maskreoNITbeingvChownig = this his*/@eent Wbet.reShowMaskEent // Hel  n    nm = this.e       * bet.reShowMaskEent a     * nreat
lent (EVENT_TYPES.BEFORE_SHOW_MASK)    */
    Rts}
   bet.reShowMaskEent .signatureg=cSIGNATURE     */
    Rts/nfig = this his*/Crewsilent Wfifam*aftin *
  modali=ycmaskrisdhiddenig = this his*/@eent Whid MaskEent // Hel  n    nm = this.e       * hid MaskEent a     * nreat
lent (EVENT_TYPES.HIDE_MASK)    */
    Rts}
   hid MaskEent .signatureg=cSIGNATURE     */
    Rts/nfig = this his*/Crewsilent Wfifam*bet.re,*
  modali=ycmaskrisdhidden. Srs.
    rs can return tdSetcto pe ent ,*
 ig = this his*/maskreoNITbeingvhiddenig = this his*/@eent Wbet.reHid
MaskEent // Hel  n    nm = this.e       * bet.reHid MaskEent a     * nreat
lent (EVENT_TYPES.BEFORE_HIDE_MASK)    */
    Rts}
   bet.reHid MaskEent .signatureg=cSIGNATURE     */
    Rts/nfig = this his*/Crewsilent Wwhue,*
  Panel  i*draggul_DOCUM    his*/@eent WdragEent // Hel  n    nm = this.e       * dragEent a     * nreat
lent (EVENT_TYPES.DRAG)    */
    Rts}
   dragEent .signatureg=cSIGNATURE  
    Rtsi     var oConfig = thiss.cInitiali as
*
  c{
  'sdcthe C* Mble 
fvlem=iesgwhich cancbeecute.
m*ig = thiss.cusnction (Panel'sdCe Cona > 0) g(nfg).ig =   do n fhavnd MtnitDd in, Ce Con_DOCUM    nm = this.einitDd in, Ce Con:,tarCase();

wa  */
    RtsPanel.sus f {
  .tnitDd in, Ce Con.alue(    )     */
    Rtso/ Add panelocthe Cd
fvlem=iesg//    */
    Rts/nfig = this his*/T    i  ecutPanelee();

 dispOwn,dc"rlo  " button_DOCUM    his*/@cthe Cdrlo  _DOCUM    his*/@t    Boolean_DOCUM    his*/@dd in,  }   // Hel  n    nm = this.e       * nfg..dddass ffvaDEFAULT_CONFIG.CLOSE.ken,r etoStttttttttttttt succes:    * nthe CClo  ,/           doooooct rh: DEFAULT_CONFIG.CLOSE.ct rhE            doooooct idabor: DEFAULT_CONFIG.CLOSE.ct idabor,            dooooosus f edes: DEFAULT_CONFIG.CLOSE.sus f edesr zepacka/**
ri)     */
    Rtsonfig = this his*/Boolean specifynctii  ecutPanelee();

 beedraggable. cTn (dd in,  ig = this his*/ct rhr i*"
   " i  ecutDragtand Dass utili=ycd " ncluded, ig = this his*/ethinwisl du   ""tdSet." <sulong>PLEASE NOTE:</sulong>cTn rhr i*a ig = this his*/known issued niIE 6 (ould) gModecand Quirks Mode)cand IE 7 ig = this his*/(Quirks Mode)cwn rhrPanelsMy
ateeithinedon't have*a ct rhrCHAdfacgig = this his*/thiirp width  cthe C* Module
fvlem=y, or ecuirp width  ig = this his*/cthe C* Module
fvlem=ycd "CHAd bs"auto"Mor (sonlyebeedraggable byig = this his*/pOwcnction (moonl on (kck
 xt/o  ecutPanel'sdheadasd
    :M.ig =   do his*/ToWfixd nispbug,edraggable PanelsMmissnctia ct rhrfor ecuirpig =   do his*/ width  cthe C* Module
fvlem=y, or who    width  cthe C* Moduleig = this his*/pfvlem=ycd "CHAd bs"auto"Mor (shave*it"CHAd bsent(ct rhroMoig = this his*/thiirproot HTML/
    :M'sko N1ChWidth*bet.re,*
 y aredmada ig = this his*/ci  }
 . cTn (aluculfn drctdth*ise*
   remohadWwhue,*
  Panel  i*  ig = this his*/hidden. <em>Tnispfixdissonlye.hcl.wi  bsdraggable PanelsM niIE 6 ig = this his*/(ould) gModecand Quirks Mode)cand IE 7 (Quirks Mode)</em>.cF.s ig = this his*/mo @ inf.smawnP  on(tnispissrhrCHe:ig = this his*/YUILibrarypbugs #1726972cand #1589210.ig =   do his*/@cthe Cddraggable_DOCUM    his*/@t    Boolean_DOCUM    his*/@dd in,  }   // Hel  n    nm = this.e       * nfg..dddass ffvaDEFAULT_CONFIG.DRAGGABLE.ken,r toStttttttttttttt succes:    * nthe CDraggable,izepacka/**
rs.
 ct rh: (Util.uD) ? 
    : hdSet,izepacka/**
rs.
 ct idabor: DEFAULT_CONFIG.DRAGGABLE.ct idabor,           dooooosus f edes: DEFAULT_CONFIG.DRAGGABLE.sus f edes zepacka/**
ri)     */
    Rtsonfig = this his*/Boolean specifynctii  ecutdraggable Panelee();

 beedragsonly,   rei:MnraOwnctict pedass ig = this his*/targChscon (kckpage.ig =   do his*/<p>ig =   do his*/Whue,CHAd bse   , draggable PanelsMor (s  rkcheckgtopCe  i  ecuy aredoenredass targChs,ig = this his*/essfifa ecutDragDass eent serequirem*topCupportedass targChei:MnraOwne();onDragE:Mnr, ig = this his*/enDrag has,/enDrag ut,/enDragDass etc.).ig =   do his*/If,*
  Panel  i*  rkdesign da bsbckdassplooo=eatygtargCheel   :Msgon (kckpage,e*
   tnispig =   do his*/flagscancbeeCHAd bse   d bsdmprove*s ff.smance.ig =   do his*/</p>ig =   do his*/<p>ig =   do his*/Whue,CHAd bshdSetE dSl dass targCherelfn dreent seor (sbe fifam.ig = this his*/</p>ig =   do his*/<p>ig =   do his*/Tht 
fvlem=ycd "CHAd bstdSetcbyddd in,   bsmgCotgCotbackwar sM
smpat }ility butee();

 beeig =   do his*/CHAd bse   di  dass targChei:MnraOwne() i*  rkrequirem*for ecutPanelb/ bsdmprove*s ff.smance.</p>ig =   do his*/ig =   do his*/@cthe CddragOnlyig = this his*/@t    Boolean_DOCUM    his*/@dd in,  tdSet// Hel  n    nm = this.e       * nfg..dddass ffvaDEFAULT_CONFIG.DRAG_ONLY.ken,r etoSttttttttttttttct rh: DEFAULT_CONFIG.DRAG_ONLY.ct rhE            doooooct idabor: DEFAULT_CONFIG.DRAG_ONLY.ct idabor,            dooooosus f edes: DEFAULT_CONFIG.DRAG_ONLY.sus f edesr zepacka/**
ri)     */
    Rtsonfig = this his*/Setse*
  t    oMou=d rOwy  bsdispOwn,for ecutPanel. Vt idtct rhspig =   do his*/ared"shadow," "mawte," and "none". c<sulong>PLEASE NOTE:</sulong>cig =   do his*/Tht nreatfid    ecutu=d rOwy 
    :Mg i*defeyrem*untilg*
  Panel ig =   do his*/d " nitiallydmada ci  }
 . cF.s Gecko-basld browsnrsdid Macig =   do his*/OS X ecutu=d rOwy 
   :Mg i*always nreat
d as du   "}sld ai*a ig = this his*/shimcto pe ent ,Aqua .
 ollbars below,dcPanel  nstastrreoNITpokingvig = this his*/througpedu/(oe  YUILibrarypbug #1723530).ig =   do his*/@cthe Cdu=d rOwyig = this his*/@t    Suld big = this his*/@dd in,  1hadow// Hel  n    nm = this.e       * nfg..dddass ffvaDEFAULT_CONFIG.UNDERLAY.ken,r etoStttttttttttttt succes:    * nthe CU=d rOwy, toSttttttttttttttct rh: DEFAULT_CONFIG.UNDERLAY.ct rhE            dooooosus f edes: DEFAULT_CONFIG.UNDERLAY.sus f edesr zepacka/**
ri)  cka/**
r   */
    Rts/nfig = this his*/T    i  ecutPanelee();

 bckdispOwnlog nia modalofashion,            do.c,utomaticlueyenreatfctia transpay = /maskroenre*
   {
};

  t
at           do.cor (s  rk   remohadWuntilg*
  Panel isddismissam.ig = this his*/@cthe Cdmodalig = thiss is*/@t    Boolean_DOCUM    his*/@dd in,  tdSet// Hel  n    nm = this.e       * nfg..dddass ffvaDEFAULT_CONFIG.MODAL.ken,r etoStttttttttttttt succes:    * nthe CModal, toSttttttttttttttct rh: DEFAULT_CONFIG.MODAL.ct rhE           doooooct idabor: DEFAULT_CONFIG.MODAL.ct idabor,            dooooosus f edes: DEFAULT_CONFIG.MODAL.sus f edesr zepacka/**
ri)     */
    Rtsonfig = this his*/A/KeyL    onrg(osda t Wao  KeyL    onrs)My
ateor (sbe enabled            do.cwhue,*
  Panel  i*Chown, ("widisabled whue,*
  Panel  i*hidden.ig = this his*/@cthe Cdkeyl    onrsig = thiss is*/@t        eputil.KeyL    onr[]_DOCUM    his*/@dd in,   rty// Hel  n    nm = this.e       * nfg..dddass ffvaDEFAULT_CONFIG.KEY_LISTENERS.ken,r etoStttttttttttttt succes:    * nthe CKeyL    onrs,            dooooosuspe =slent : DEFAULT_CONFIG.KEY_LISTENERS.suspe =slent ,            dooooosus f edes: DEFAULT_CONFIG.KEY_LISTENERS.sus f edesr zepacka/**
ri)     */
    Rtsonfig = this his*/UI Suld bsM}sld byoecutPanel. Tcutsuld bs ared ns fful i:Mbsent(DOM 
s HTML, ("wie();

 bckescapld byoecutimp    :Mosdi  
smfctieoNIT,n 
xMnrnfl source.ig =   do his*/ig = this his*/@cthe Cdsuld bsig = thiss is*/@t        vaig = g = s is*/@dd in,  Ale > 0) gliterr act pethe 
fvlem=iesgChown below:ig = this his*/////<dl>ig =   do his*/////////<dt>rlo  </dt><dd><em>HTML</em> : Tkckmarkup Mbsonl as (kcklabel,for ecutrlo    cP . Dd in, sp bs"Clo  ".</dd>ig = this his*/////</dl>ig =   do his*m = this.e       * nfg..dddass ffvaDEFAULT_CONFIG.STRINGS.ken,r etoSttttttttttttttct rh:DEFAULT_CONFIG.STRINGS.ct rhE           dooooo succes:   * nthe CSuld bsE           doooooct idabor:DEFAULT_CONFIG.STRINGS.ct idabor,           dooooosus f edes:DEFAULT_CONFIG.STRINGS.sus f edes zepacka/**
ri)  cka/**
ri     var oCo/ BEGIN BUILT-IN PROPERTYeEVENT HANDLERSg//   var oC   var oConfig = this.cTn (dd in,  eent W successfirem*whue,*
  "rlo  " 
fvlem=ycd "cute.
m.ig = this.cTn (havnd Mrs.
 ols (kck.hcendfctior hidfctiof ecutrlo    cP  at *
  ig = this.ctop r;
  /o  ecutPanel.&      cl  fhavnd Mnthe CClo  &      cl  f*r moreould be 
    Tn (Crewsilent W
    (usulueyetht 
fvlem=ycn   )&      cl  f*r more    va[]} apgs Tn (Crewsilent W,pgu**
  .cF.s cthe C* Moduleig = this*W success, apgs[0]eor (sequalg*
  newlye.hcl.wi ct rhrfor ecu 
fvlem=y.ig = thisn ftr more    var obj Tcutscvleto> 0)  cF.s cthe C* Module success, ig = this.ctnispor (susulueyeequalg*
  owner ig = this*m = this.enthe CClo  :,tarCase();t   , apgs, o> 

wa zepacka/**
rpexcvalo  apgs[0],           dooooooClo  a     * nlo  ,           dooooosuld bs      * nfg g* dass ffva suld bs")*           dooooofc  tuplicate = t   }val)r toString: functio   }!oClo      toString: functioctio   }!m_oClo  I o.Templabe)r toString: functiooooo {
 m_oClo  I o.Templabed=  {
};

 .nreat
l    :M("a    oString: functiooooooooom_oClo  I o.Templabe. {
  N   a= "rs.
gConr-rlo  "  oString: functiooooooooom_oClo  I o.Templabe.hrefa= "#   oString: functiooooo}they can  ChecccccccccoClo  a  m_oClo  I o.Templabe. {one/**
(
      hey can  Checccccccccfc      * innerE    :M.fifs*Child     */
    Rtsoooo       }fc)r toString: functiooooooooo   * innerE    :M. ns ffBet.re(oClo  ,/fc) DER":P@feonfictiooooo}e      toString: functiooooooooo   * innerE    :M..hcendChild}oClo     oString: functiooooo}they can  ChecccccccccoClo   innerHTML/= (suld bs &&osuld bs nlo  ) ? suld bs nlo   :,"&#160;      */
    Rtsoooo      /**.P (oClo  ,/"click",  
   _doClo  ,/*
is**
         */
    Rtsoooo       * nlo   =BoClo       */
    Rtsoooo}e      toString: functiooooooClo   style.dispOwn,= "block" DER":P@feonfictio}t zepacka/**
ri       toString: functio   }oClo      oString: functiooooooClo   style.dispOwn,= "none" DER":P@feonfictio}t  */cka/**
ri
 cka/**
ri     var oConfig = thiss.cEent W successfor ecutrlo    cP ig = thiss.cig = thiss.cfhavnd M_doClo  ig = thiss.cf*f icbsul_DOCUM    n ig = thiss.cf*r moreDOMlent e eig = thiss.m = this.e_doClo   : tarCase();g

wa
   cka/////Eng f.pe ent Dd in, }      */
    Rts

   hid ()    */
   i     var oConfig = this.cTn (dd in,  eent W successfirem*whue,*
  "draggable" 
fvlem=ycig = this.cd "cute.
m.ig = this.cfhavnd Mnthe CDraggable_DOCUM     f*r moreould be 
    Tn (Crewsilent W
    (usulueyetht 
fvlem=ycn   )&      cl  f*r more    va[]} apgs Tn (Crewsilent W,pgu**
  .cF.s cthe C* Moduleig = this*W success, apgs[0]eor (sequalg*
  newlye.hcl.wi ct rhrfor ecu 
fvlem=y.ig = thisn ftr more    var obj Tcutscvleto> 0)  cF.s cthe C* Module success, ig = this.ctnispor (susulueyeequalg*
  owner ig = this*m = this.enthe CDraggable:,tarCase();t   , apgs, o> 

wa  */
    Rtspexcvalo  apgs[0]  tuplicate = t   }val)r toString: functio   }!Util.uD)   oString: functiooooo    eplog("DD(ddcendencys  rkhav.", "error    oString: functiooooo   * nfg s* dass ffva draggable",ntdSet   oString: functioooooreturn DER":P@feonfictio}t zepacka/**
rctio   }

   headas    oString: functioooooupers* Style}

   headas,/"cursor"E "moha    oString: functiooooo   * reg    rDragDass
  DER":P@feonfictio}tDER":P@feonfictio}
   srs.
     "bet.reShow", s
hWidthToO N1ChWidth      */
    Rtsi       t zepacka/**
rctio   }

   dd     DEFAUL.            t
   dd.unreg
  DER":P@feonfictio}tDER":P@feonfictio   }

   headas    oString: functioooooupers* Style}

   headas,"cursor"E"auto") DER":P@feonfictio}tDER":P@feonfictio}
   unsrs.
     "bet.reShow", s
hWidthToO N1ChWidth   P@feonfictio}tonfictio}*          var oConfig = this.cTn (dd in,  eent W successfirem*whue,*
  "u=d rOwy" 
fvlem=ycig = this.cd "cute.
m.ig = this.cfhavnd Mnthe CU=d rOwyig = this  f*r moreould be 
    Tn (Crewsilent W
    (usulueyetht 
fvlem=ycn   )&      cl  f*r more    va[]} apgs Tn (Crewsilent W,pgu**
  .cF.s cthe C* Moduleig = this*W success, apgs[0]eor (sequalg*
  newlye.hcl.wi ct rhrfor ecu 
fvlem=y.ig = thisn ftr more    var obj Tcutscvleto> 0)  cF.s cthe C* Module success, ig = this.ctnispor (susulueyeequalg*
  owner ig = this*m = this.enthe CU=d rOwy:,tarCase();t   , apgs, o> 

wa zepacka/**
rpexcbMacGecko   (}
   plabf.sm ==o"mac"r&& UA g*cko)*           dooooosU=d rOwyo  apgs[0].toLow fCas 
 ,           dooooooU=d rOwyo  }
   undasOwntizepacka/  dooooooE    :Mo  }
   
    :M     */
    RtshasOwnP  nreat
U=d rOwy;

wa  */
    Rts funpexcbNew intdSet    */
    Rts fun   }!oU=d rOwy)r r// nreat
n     rkalreadydie,DOM toString: functioctio   }!m_oU=d rOwyTemplabe)r toString: functiooooo {
 m_oU=d rOwyTemplabed=  {
};

 .nreat
l    :M("div    oString: functiooooooooom_oU=d rOwyTemplabe. {
  N   a= "u=d rOwy"  oString: functiooooo}they can  ChecccccccccoU=d rOwyo  m_oU=d rOwyTemplabe. {one/**
(tdSet   oString: functiooooo}
   
    :M..hcendChild}oU=d rOwy)     */
    Rtsoooo       * u=d rOwyo  oU=d rOwy     */
    Rtsoooo       }bIEQuirks)r toString: functiooooooooo}
   siz
U=d rOwy;
  oString: functiooooooooo   * nfg srs.
    ToCe Conlent ( width , }
   siz
U=d rOwy
  oString: functiooooooooo   * nfg srs.
    ToCe Conlent ( h";
   , }
   siz
U=d rOwy
   oString: functiooooooooo   * nute.
Cs.
 :Mlent  srs.
     t
   siz
U=d rOwy
  oString: functiooooooooo    epwidg* .Modulh.
 xtResiz
lent  srs.
     t
   siz
U=d rOwy, *
is**
        */
    Rtsssssssss}they can  Checcccccccc   }UA.webkiM && UA webkiM < 420)r toString: functiooooooooo   * nute.
Cs.
 :Mlent  srs.
     t
   f.sceU=d rOwyRedraw     */
    Rtsssssssss}they can  ChecccccccccbNew in}    DER":P@feonfionfiit  */cka/**
ri
 cka/**
rrrrrhasOwnP  onBet.reShow;

wa  */
    Rts funpexcbNew innreat
U=d rOwy.alue(    )  an  Checcccccccc   }!bNew && bIEQuirks)r toString: functiooooo}
   siz
U=d rOwy;
  oString: functio}   */
    Rtsssss 
   _u=d rOwyDefeyrem*intdSet    */
    Rts fun}
   bet.reShowlent  unsrs.
     onBet.reShow     */
    Rtsi tuplicate = thasOwnP  desuloyU=d rOwy;

wa  */
    Rts fun   }

   _u=d rOwyDefeyrem)r toString: functiooooo}
   bet.reShowlent  unsrs.
     onBet.reShow     */
    Rts Rtsssss 
   _u=d rOwyDefeyrem*intdSet    */
    Rts fun}tDER":P@feonfictio   }oU=d rOwy)r  oString: functiooooo   * nfg unsrs.
    FromCe Conlent ( width , }
   siz
U=d rOwy
  oString: functiooooo   * nfg unsrs.
    FromCe Conlent ( h";
   ,}
   siz
U=d rOwy
  oString: functiooooo   * nute.
Cs.
 :Mlent  unsrs.
     t
   siz
U=d rOwy
  oString: functiooooo   * nute.
Cs.
 :Mlent  unsrs.
     t
   f.sceU=d rOwyRedraw     */
    Rtsssssssss    epwidg* .Modulh.
 xtResiz
lent  unsrs.
     t
   siz
U=d rOwy,/*
is**
         */
    Rtsoooo       * 
    :M.remohaChild}oU=d rOwy)     */
    Rtsoooo       * u=d rOwyo   rty  ing: functioooooit  */cka/**
ri
 cka/**
rrrrrswitchk(sU=d rOwy)r  oString: functiocanl "shadow :   */
    Rtsoooo    uperremohaC{
  (oE    :M, "mawte    oString: functiooooouperaddC{
  (oE    :M, "shadow    oString: functiooooobreak  oString: functiocanl "mawte :   */
    Rtsoooo       }!bMacGecko)r toString: functiooooooooodesuloyU=d rOwy.alue(    )  an  Checcccccccccccc}DER":P@feonfictiooooouperremohaC{
  (oE    :M, "shadow    oString: functiooooouperaddC{
  (oE    :M, "mawte    oString: functiooooobreak  oString: functiodd in, :   */
    Rtsoooo       }!bMacGecko)r toString: functiooooooooodesuloyU=d rOwy.alue(    )  an  Checcccccccccccc}DER":P@feonfictiooooouperremohaC{
  (oE    :M, "shadow    oString: functiooooouperremohaC{
  (oE    :M, "mawte    oString: functiooooobreak  oString: funi tuplicate = t   }(sU=d rOwyo = "shadow   ||o(bMacGecko && !oU=d rOwy))r toString: functio   }

   nfg g* dass ffva vi  }
 "
     DEFAUL.            pexcbNew innreat
U=d rOwy.alue(    )  an  Checccccccccctio   }!bNew && bIEQuirks)r toString: functiooooooooo}
   siz
U=d rOwy;
  oString: functiooooo}   */
    Rtsssss}       toString: functiooooo   }!

   _u=d rOwyDefeyrem)r toString: functiooooooooo}
   bet.reShowlent  srs.
     onBet.reShow     */
    Rts Rtsssssoooo}
   _u=d rOwyDefeyrem*in}    DER":P@feonfionfioooo}   */
    Rtsssss} P@feonfictio}tonfictio}*            var oConfig = this.cTn (dd in,  eent W successfirem*whue,*
  "modal" 
fvlem=ycd "ig = this.ccute.
m.cTnisp successsrs.
    s or unsrs.
    soto (kckshow ("wihid ig = this.ceent seto  succee*
   ispOwn,or hide/o  ecutmodali=ycmask.ig = this.cfhavnd Mnthe CModalig = this  f*r moreould be 
    Tn (Crewsilent W
    (usulueyetht 
fvlem=ycn   )&      cl  f*r more    va[]} apgs Tn (Crewsilent W,pgu**
  .cF.s cthe C* Moduleig = this*W success, apgs[0]eor (sequalg*
  newlye.hcl.wi ct rhrfor ecu 
fvlem=y.ig = thisn ftr more    var obj Tcutscvleto> 0)  cF.s cthe C* Module success, ig = this.ctnispor (susulueyeequalg*
  owner ig = this*m = this.enthe CModal:,tarCase();t   , apgs, o> 

wa zepacka/**
rpexcmodalo  apgs[0]  zepacka/**
r   }modal)r toString: functio   }!}
   _hasModali=ylent L    onrs)M  toString: functioctio}
   srs.
     "bet.reShow", }
   buildMask
  oString: functiooooo   * srs.
     "bet.reShow", }
   bld bToTop
  oString: functiooooo   * srs.
     "bet.reShow", }
   showMask
  oString: functiooooo   * srs.
     "hid ", }
   hid Mask)     */
    Rtsoooo     hasOwyawindowResiz
lent  srs.
     t
   siz
MaskEetoSttttttttttttttttttoooo   ***
         */
    Rtsoooo       * _hasModali=ylent L    onrs in}    DER":P@feonfionfiit  */cka/**
ri       toString: functio   }

   _hasModali=ylent L    onrs)M  toString: functioctio   }

   nfg g* dass ffva vi  }
 "
     DEFAUL.                   * hid Mask;
  oString: functiooooooooo   * remohaMask;
  oString: functiooooo}they can  Checcccccccc}
   unsrs.
     "bet.reShow", }
   buildMask
  oString: functiooooo   * unsrs.
     "bet.reShow", }
   bld bToTop
  oString: functiooooo   * unsrs.
     "bet.reShow", }
   showMask
  oString: functiooooo   * unsrs.
     "hid ", }
   hid Mask)     */
    Rtsoooo     hasOwyawindowResiz
lent  unsrs.
     t
   siz
MaskEe    )     */
    Rtsctiooooo   * _hasModali=ylent L    onrs intdSet    */
    Rts fun}t
    Rts fun}t
    Rtsi     var oConfig = this.cRemohas
ecutmodali=ycmask.ig = this.cfhavnd MremohaMaskig = this*m = this.eremohaMask:,tarCase();

wa zepacka/**
rpexcoMasko  }
   masktizepacka/  dooooooPay = /**
  tuplicate = t   }oMask     DEFAUL.        /fig = this hisssss    Hid
d nckmaskrbet.re,desuloynctiitoto ensuregprateDOM g = this hisssss    eent W success on f{
}sable(
    :Ms gCheremohad. g = this hisssss*m = this.e           * hid Mask;
  izepacka/  dooooooPay = /**
o  oMask.pay = /**
  zepacka/  dooooo   }oPay = /**
    oString: functiooooooPay = /**
.remohaChild}oMask
  oString: functio}tDER":P@feonfictio}
   masko   rty              i
P@feonfii  P@feonfi   var oConfig = this.cTn (dd in,  eent W successfirem*whue,*
  "keyl    onrs" 
fvlem=ycig = this.cd "cute.
m.ig = this.cfhavnd Mnthe CKeyL    onrsig = this  f*r moreould be 
    Tn (Crewsilent W
    (usulueyetht 
fvlem=ycn   )&      cl  f*r more    va[]} apgs Tn (Crewsilent W,pgu**
  .cF.s cthe C* Modulig = this*W success, apgs[0]eor (sequalg*
  newlye.hcl.wi ct rhrfor ecu 
fvlem=y.ig = thisn ftr more    var obj Tcutscvleto> 0)  cF.s cthe C* Module success, ig = this.ctnispor (susulueyeequalg*
  owner ig = this*m = this.enthe CKeyL    onrs:,tarCase();t   , apgs, o> 

wa zepacka/**
rpexcl    onrs inapgs[0],           doooool    onr,           dooooonL    onrs, zepacka/  dooooo   cka/**
r   */
    Rts   }l    onrs)M  toString: functio   }l    onrs  nstastro  Apt W)M  toString: functioctionL    onrs inl    onrs.length  hey can  Checccccccccfosd(ic= 0 cic<(nL    onrs  i++)    oString: functioooooooool    onr inl    onrs[i]  cka/**
r   */
    Rtsing: functio   }!Ce Con.alreadySrs.
    d(}
   showlent ,            doooooctioooooooool    onr.enable,tl    onr))    oString: functiooooooooooooo}
   showlent  srs.
     l    onr.enable,t oString: functioooooooooooooooool    onr**
         */
    Rtsoooo        }they can  Checccccccccctio   }!Ce Con.alreadySrs.
    d(}
   hid
lent ,            doooooctioooooooool    onr.disable,tl    onr))    oString: functiooooooooooooo}
   hid
lent  srs.
     l    onr.disable,t oString: functioooooooooooooooool    onr**
         */
    Rtsoooo        oooo}
   desuloylent  srs.
     l    onr.disable,t oString: functioooooooooooooooool    onr**
       functiooooooooooooooooo}DER":P@feonfictiooooo}    */
    Rtsssss}       ttoString: functiooooo   }!Ce Con.alreadySrs.
    d(}
   showlent ,            doooooctioooool    onrs.enable,tl    onrs))    oString: functiooooooooo}
   showlent  srs.
     l    onrs.enable,t oString: functioooooooooooool    onrs, 
        */
    Rtsssssssss}they can  Checcccccccc   }!Ce Con.alreadySrs.
    d(}
   hid
lent ,            doooooctioooool    onrs.disable,tl    onrs))    oString: functiooooooooo}
   hid
lent  srs.
     l    onrs.disable,t oString: functioooooooooooool    onrs, 
       oString: functiooooooooo}
   desuloylent  srs.
     l    onrs.disable,t oString: functioooooooooooool    onrs, 
       oString: functiooooo}    */
    Rtsssss}    */
    Rtsi
 cka/**
ri     var oConfig = this.cTn (dd in,   successfor ecut suld bs"e
fvlem=yig = this.cfhavnd Mnthe CSuld bsig = this*m = this.enthe CSuld bsM:,tarCase(;t   , apgs, o> 

wa  */
    Rtspexcvalo  Lte..mergeaDEFAULT_CONFIG.STRINGS.ct rhE apgs[0]     */
    Rts

   nfg s* dass ffvaDEFAULT_CONFIG.STRINGS.ken,rval, 
        */
   i     var oConfig = this.cTn (dd in,  eent W successfirem*whue,*
  "h";
    
fvlem=ycd "cute.
m.ig = this.cfhavnd Mnthe CH";
  ig = this  f*r moreould be 
    Tn (Crewsilent W
    (usulueyetht 
fvlem=ycn   )&      cl  f*r more    va[]} apgs Tn (Crewsilent W,pgu**
  .cF.s cthe C* Moduleig = this*W success, apgs[0]eor (sequalg*
  newlye.hcl.wi ct rhrfor ecu 
fvlem=y.ig = thisn ftr more    var obj Tcutscvleto> 0)  cF.s cthe C* Module success, ig = this.ctnispor (susulueyeequalg*
  owner ig = this*m = this.enthe CH";
  :,tarCase();t   , apgs, o> 

wa  */
    Rtspexch";
  o  apgs[0],           doooooel/= }
  .innerE    :M; a  */
    Rtsupers* Style}elb/ h";
   , h";
       */
    Rts

   nfg refirelent ( if moe    oString:i     var oConfig = thiss.cTn (dd in,  crewsi eent W successexecuted whue,*
  Panel'sdhe;
  od "cute.
m, ig = thiss.ci  ecutautofr (he;
  o
fvlem=ychas beue,CHA ig = this fig = thiss.cfhavnd M_autoFr (OnH";
  Cute.
ig = thiss.cf*f icbsul_DOCUM    n f*r moreould be 
    Tn (eent W
   _DOCUM    n f*r moreApt W} apgs Tn (a t Wao  ,pgu**
   p
  ul to eent Wsrs.
    rs_DOCUM    n f*r moreHTMLE    :Me eloTcutheadas,/bodn,or footasd
    :Mgwhich isp bs   resiz
ooto fillig = thiss.cout *
  rs.
gConrsdhe;
  ig = thiss.m = this.e_autoFr (OnH";
  Cute.
M:,tarCase(;t   , apgs, 
 )r toString: funPanel.sus f {
  ._autoFr (OnH";
  Cute.
..hcly(   ***,pgu**
  )  zepacka/**
r   }bIEQuirks)r toString: functiopexcpanelo  }
      */
           sHATimeout(tarCase(;

wa  */
    Rtssssssssspanel.siz
U=d rOwy;
  oString: functio},0) DER":P@feonfi}hP@feonfii     var oConfig = this.cTn (dd in,  eent W successfirem*whue,*
  "width  
fvlem=ycd "cute.
m.ig = this.cfhavnd Mnthe CWidthig = this  f*r moreould be 
    Tn (Crewsilent W
    (usulueyetht 
fvlem=ycn   )&      cl  f*r more    va[]} apgs Tn (Crewsilent W,pgu**
  .cF.s cthe C* Moduleig = this*W success, apgs[0]eor (sequalg*
  newlye.hcl.wi ct rhrfor ecu 
fvlem=y.ig = thisn ftr more    var obj Tcutscvleto> 0)  cF.s cthe C* Module success, ig = this.ctnispor (susulueyeequalg*
  owner ig = this*m = this.enthe CWidth:,tarCase();t   , apgs, o> 

wa  */a  */
    Rtspexcwidth   apgs[0],           doooooel/= }
  .innerE    :M;   */a  */
    Rtsupers* Style}elb/ width , width   P@feonfictio

   nfg refirelent ( if moe    oStr
P@feonfii  P@feonfi   var oConfig = this.cTn (dd in,  eent W successfirem*whue,*
  "zIndex  
fvlem=ycd "cute.
m.ig = this.cfhavnd Mnthe CzIndexig = this  f*r moreould be 
    Tn (Crewsilent W
    (usulueyetht 
fvlem=ycn   )&      cl  f*r more    va[]} apgs Tn (Crewsilent W,pgu**
  .cF.s cthe C* Moduleig = this*W success, apgs[0]eor (sequalg*
  newlye.hcl.wi ct rhrfor ecu 
fvlem=y.ig = thisn ftr more    var obj Tcutscvleto> 0)  cF.s cthe C* Module success, ig = this.ctnispor (susulueyeequalg*
  owner ig = this*m = this.enthe CzIndex:,tarCase();t   , apgs, o> 

wa  */
    RtsPanel.sus f {
  .nthe CzIndex.alue(    , t   , apgs, o> 
  tuplicate = t   }}
   masko||o}
   nfg g* dass ffva modal"
 ==in}   )r toString: functiopexcpanelZ/= upergHAStyle}

   e    :M, "zIndex )  an  Checcccccccc   }!panelZ/||oisNaN(panelZ
     DEFAUL.            panelZ/= 0  oString: functio}tDER":P@feonfictio   }panelZ/==in0)r toString: functiooooo// Recursiv (alulp bscthe Czindex (which e();

 bckstoppul_DOCUM    hisctiooooo// eoNITgofctieurthinebecaonl panelZ/e();

 no loe.
r/==in0)_DOCUM    hisctiooooo   * nfg s* dass ffva zIndex , 1
  oString: functio}       toString: functiooooo}
   stackMask;
  oString: functio}t
    Rts fun}t
    Rtsi     var oCo/ END BUILT-IN PROPERTYeEVENT HANDLERSg//   var oConfig = this.cBuilds
ecutwr.hcfctirs.
gConr apound,*
  Panelgprate  "}sld facgig = this* posiwnP nction (shadowiand mawtedu=d rOwys. Tcutrs.
gConr 
    :Mg i*ig = this* 
  ign da bsa  loalu  nstastrrpexiable(aluledtrs.
gConr, ("wi*
  ig = this.c
    :Mg i*re ns fful i:side/o  it.ig = this.cfhavnd MbuildWr.hcnrig = this*m = this.ebuildWr.hcnr:,tarCase();

wa zepacka/**
rpexc
    :MPay = o  }
   
    :M.pay = /**
tizepacka/  dooooooriginalE    :Mo  }
   
    :Mtizepacka/  dooooowr.hcnrd=  {
};

 .nreat
l    :M("div    
cka/  dooooowr.hcnr. {
  N   a= Panel.CSS_PANEL_CONTAINER;
cka/  dooooowr.hcnr.im*inoriginalE    :M.im*+ "_c      */
    Rts   }el   :MPay = )r toString: functioel   :MPay = . ns ffBet.re(wr.hcnr,noriginalE    :M     */
    Rtsi tuplicate = twr.hcnr..hcendChild}originalE    :M    P@feonfictio

   e    :Mo  wr.hcnr  P@feonfictio

   innerE    :M*inoriginalE    :M; a  */
    Rtsupers* Style}

   innerE    :M, "vi  }ility"E "inhinit    oString:i     var oConfig = this* Adjrews
ecutsiz
/o  ecutshadowibasld on (kcksiz
/o  ecut
    :M.ig =   do.cfhavnd Msiz
U=d rOwyig = this*m = this.esiz
U=d rOwy:,tarCase();

wa  */
    RtspexcoU=d rOwyo  }
   undasOwntizepacka/  dooooooE    :M  tuplicate = t   }oU=d rOwy)r  oString: functiooE    :Mo  }
   
    :M  oString: functiooU=d rOwy.style.width   oE    :M.o N1ChWidth*+ "px"  oString: functiooU=d rOwy.style.h";
  o  oE    :M.o N1ChH";
  *+ "px"  oString: fun}t
    Rtsi     var oConfig = this.cReg    rs,*
  Panel'sdheadasdfacgdrags& dass capa}ility.ig = this.cfhavnd Mreg    rDragDassig = this*m = this.ereg    rDragDass:,tarCase();

wa zepacka/**
rpexcmeo  }
    tuplicate = t   }}
   headas     an  Checcccccccc   }!Util.uD)   oString: functiooooo    eplog("DD(ddcendencys  rkhav.", "error    oString: functioooooreturn DER":P@feonfictio}t zepacka/**
rctiopexcbDragOnly   (}
   nfg g* dass ffva dragonly"
 ==in}   )   oString: functioonfig = this hissssss.cTn (    eputil.DD( nstastr,"}sld  bsdmp    :Mgthutdraggable headasdfacg(kckpanel  ftdraggable  i*enabledig = this hissssss.ig = this hissssss.c@
fvlem=ycddig = this hissssss./@t        eputil.DDig = this hissssss.m = this.e           * ddo   ew/Util.uD}

   e    :M.im, }
   im, {dragOnly:cbDragOnlyi)     */
    Rtsssss   }!}
   headas im)r  oString: functiooooo   * headas im/= }
  .im*+ "_h" DER":P@feonfictio}t zepacka/**
r       * dd.CtarMDragt=,tarCase();

wa zepacka/**
rrrrrrrrrpexco N1ChH";
  EKoString: functioooooooooo N1ChWidthEKoString: functioooooooooviewPorhWidthEKoString: functioooooooooviewPorhH";
  EKoString: functiooooooooo.
 ollXEKoString: functiooooooooo.
 ollY     */
    Rtsoooo       }    epenv.ua.ieo = 6     DEFAUL.                uperaddC{
  (me 
    :Mt drag    oString: functiooooo}they can  Checcccccccc   }me nfg g* dass ffva rs.strgCotoviewport"))    oString: functiooooooooopexcnViewportO N1Cht=, hasOwyaVIEWPORT_OFFSET     */
    Rtssssssssssssso N1ChH";
  *= me 
    :M.o N1ChH";
     functioooooooooooooooooo N1ChWidth*= me 
    :M.o N1ChWidth     */
    RtsssssssssssssviewPorhWidth/= upergHAViewportWidth;
  oString: functioooooooooviewPorhH";
  /= upergHAViewportH";
  ;
  izepacka/  dooooooooooooo.
 ollX/= upergHAD{
};

 S
 ollLeft;
  oString: functiooooooooo.
 ollY/= upergHAD{
};

 S
 ollTss
  D oString: functiooooooooo   }o N1ChH";
  *+ nViewportO N1Cht<oviewPorhH";
       DEFAUL.                    }
   minY/= .
 ollY/+ nViewportO N1Ch  oString: functiooooooooo    }
   maxY/= .
 ollY/+ viewPorhH";
  /-so N1ChH";
  *- nViewportO N1Ch  oString: functiooooooooo}e      toString: functiooooooooo    }
   minY/= .
 ollY/+ nViewportO N1Ch  oString: functiooooooooo    }
   maxY/= .
 ollY/+ nViewportO N1Ch  oString: functiooooooooo}D oString: functiooooooooo   }o N1ChWidth*+ nViewportO N1Cht<oviewPorhWidth   toString: functiooooooooo    }
   minX/= .
 ollX/+ nViewportO N1Ch  oString: functiooooooooo    }
   maxX/= .
 ollX/+ viewPorhWidth/-oo N1ChWidth*- nViewportO N1Ch  oString: functiooooooooo}e      toString: functiooooooooo    }
   minX/= .
 ollX/+ nViewportO N1Ch  oString: functiooooooooo    }
   maxX/= .
 ollX/+ nViewportO N1Ch  oString: functiooooooooo}D oString: functiooooooooo}
   ns.strgCoX*in}    DER":P@feonfionfioooooooo}
   ns.strgCoY*in}    DER":P@feonfionfioooo}e      toString: functiooooooooo   * ns.strgCoX*intdSet    */
    Rts funoooooooo}
   ns.strgCoY*intdSet    */
    Rts funoooo}D oString: functiooooome dragEent .firea suarMDrag"**,pgu**
  )  zepacka/**
roooo} D oString: functio   * dd.enDragt=,tarCase();

waoString: functiooooome syncPosiwnP ;
  oString: functiooooome nfg refirelent ( if moe    oString:::::::::::::   }}
   plabf.sm ==o"mac"r&&     epenv.ua.gecko)r toString: functiooooooooo}
   showMacGeckoS
 ollbars;
  oString: functiooooo}they can  Checccccccccme dragEent .firea enDrag"**,pgu**
  )  zepacka/**
roooo} D oString: functio   * dd.endDragt=,tarCase();

wa zepacka/**
rrrrrrrrr   }    epenv.ua.ieo = 6     DEFAUL.                uperremohaC{
  (me 
    :Mt drag    oString: functiooooo}they can  Checccccccccme dragEent .firea endDrag"**,pgu**
  )  zepacka/**
rooooccccme mohaEent .fireame nfg g* dass ffva xy"
  D oString: functio} D oString: functio   * dd.1ChHsucceElId(}
   headas im)  zepacka/**
roooo   * dd.addInct idHsucceT   ("INPUT    oString: functio   * dd.addInct idHsucceT   ("SELECT    oString: functio   * dd.addInct idHsucceT   ("TEXTAREA    oString: funi
P@feonfii  P@feonfi   var oConfig = this.cBuilds
ecutmaskoprate  "laim*oenre*
   {
};

  whue,*
  Panel  i*ig = this.ccthe C*  da bsbckmodal.ig = this.cfhavnd MbuildMaskig = this*m = this.ebuildMask:,tarCase();

wa  */
    RtspexcoMasko  }
   mask  zepacka/**
r   }!oMask     DEFAUL.           }!m_oMaskTemplabe)r toString: functiooooom_oMaskTemplabed=  {
};

 .nreat
l    :M("div    oString: functiooooom_oMaskTemplabe. {
  N   a= "mask"  oString: functiooooom_oMaskTemplabe.innerHTML/= "&#160;   ing: functiooooo}DER":P@feonfictiooMasko  m_oMaskTemplabe. {one/**
(
      ER":P@feonfictiooMask im/= }
  .im*+ "_mask"   ER":P@feonfictio {
};

 .bodn. ns ffBet.re(oMaskEe {
};

 .bodn.fifs*Child  D oString: functio}
   masko  oMask     */
    Rtsssss   }    epenv.ua.geckor&& }
   plabf.sm ==o"mac"    oString: functiooooouperaddC{
  (}
   maskt "block-.
 ollbars") DER":P@feonfictio}tDER":P@feonfictio// Stackkmaskrbasld on (kcke    :Mozindex oString: functio}
   stackMask;
  oString: fun}t
    Rtsi     var oConfig = this.cHid
s
ecutmodali=ycmask.ig = this.cfhavnd Mhid Maskig = this*m = this.ehid Mask:,tarCase();

wa  */
    Rts   }t
   nfg g* dass ffva modal"
r&& }
   maskr&& }
   bet.reHid MaskEent .firea
     DEFAUL.           * mask.style.dispOwn,= "none" DER":P@feonfictiouperremohaC{
  ( {
};

 .bodn, "masked    oString: functio   * hid MaskEent .firea
  oString: fun}t
    Rtsi     var oConfig = this.cShows
ecutmodali=ycmask.ig = this.cfhavnd MshowMaskig = this*m = this.eshowMask:,tarCase();

wa  */
    Rts   }t
   nfg g* dass ffva modal"
r&& }
   maskr&& }
   bet.reShowMaskEent .firea
     DEFAUL.        uperaddC{
  ( {
};

 .bodn, "masked    oString: functio   * siz
Mask;
  oString: functio   * mask.style.dispOwn,= "block" DER":P@feonfictio}
   showMaskEent .firea
  oString: fun}t
    Rtsi     var oConfig = this.cSews
ecutsiz
/o  ecutmodali=ycmaskr bsctenre*
  nt ifa .
 ollable ig = this.carea/o  ecut {
};

 ig = this.cfhavnd Msiz
Maskig = this*m = this.esiz
Mask:,tarCase();

wa  */
    Rts   }t
   mask    DER":P@feonfictio// Shrinkkmaskrfifs*, soiitodoesn't aff0) g*
   {
};

  siz
. g = this hissssspexcmasko  }
   masktizepacka/  dooooosssspiewWidth/= upergHAViewportWidth;
tizepacka/  dooooosssspiewH";
  /= upergHAViewportH";
  ;
  izepacka/  dooooo   }mask.o N1ChH";
  *>spiewH";
  )r toString: functiooooomask.style.h";
  o  piewH";
  /+ "px"  oString: functio}tDER":P@feonfictio   }mask.o N1ChWidth/>spiewWidth)r toString: functiooooomask.style.width   piewWidth/+ "px"  oString: functio}tDER":P@feonfictio// Thue,Ciz
/iAd bsent( {
};

 ig = thisctiooooomask.style.h";
  o  upergHAD{
};

 H";
  ;
/+ "px"  oString: functiomask.style.width   upergHAD{
};

 Width;
*+ "px"  oString: fun}t
    Rtsi     var oConfig = thiss.cSews
ecutzindex o  ecutmaskt i  it exists,rbasld on (kckzindex o  ig = thiss.c*
  Panel 
    :M.cTn (zindex o  ecutmaskcd "CHAd bsbl one less ig = thiss.c*
anc*
  Panel 
    :M's(zindex.ig =   do n ig =   do n <p>NOTE:cTnisphavnd Mor (s  rk ump up Mn (zindex o  ecutPanelig = thiss.c*o ensuregprateecutmaskchai*a non-negativ (zindex./If,youkrequireeecuig = thiss.cmaskczindex  bsbl 0,or highnr**
n (zindex o  ecutPanel ig =   do n e();

 bcksHAd bsa ct rhrhighnrc*
anc0,*bet.re,*
isphavnd Md "cluled.ig =   do n </p>ig =   do .cfhavnd MstackMaskig =   do .m = this.estackMask:,tarCase(;

wa  */
    Rts   }t
   mask    String: functioopexcpanelZ/= upergHAStyle}

   e    :M, "zIndex )  an  Checcccccccc   }!    eplte..isU=d fined(panelZ
 && !isNaN(panelZ
     DEFAUL.            upers* Style}

   maskt "zIndex , panelZ/- 1
  oString: functio} oString: fun}t
    Rtsi     var oConfig = this.cRenders,*
  Panel byo ns ffnction (
    :Ms prateared  rkalreadydie,ig = this.cecutmaie,Panel  n bsentis ctrr0) gpOwces. Opase(lueye.hcendsi*
  ig = this.cPanelgpo (kckspecif.wi n**
opriacg(o (kckrender's(executiP . NOTE:cig = this.cF.s PanelsMorvndut existnctimarkup**
n (.hcendTo/**
o,pgu**
   i*ig = this.cREQUIRED./If,*
 i*apgu**
   i*ommitt
d a"wi*
  cury = o
    :Mg i*ig = this*   rkpe = :Mg nsent( {
};

 **
n (tarCase()or (sreturnshdSetE ig = this* indicafnctionateecutrender wai*a failure.ig = this.cfhavnd Mrenderig = this.cf*r moreould be .hcendTo/**
oTkcke    :Moida bswhich ecutModulh ig = this* e();

 bck.hcend da bspriacg(o renderncti<em>OR</em>ig = this.cf*r moreHTMLE    :Me .hcendTo/**
oTkcke    :Mo bswhich ecutModulh ig = this* e();

 bck.hcend da bspriacg(o rendernctig = this.cfreturns{boolean} Success or failure o  ecutrenderig = this.m = this.erender:,tarCase();.hcendTo/**
     DEFAUL.    returnsPanel.sus f {
  .render.alue(    , .hcendTo/**
, }
   innerE    :M   oString:i     var oConfig = thiss.cRenders,*
  cury = eyesHAdheadasd n bsiM's(
fvlem posiwnP du=d ri*
  ig = thiss.cmodulh 
    :M.cI  ecutmodulh 
    :M) i*  rkprovid dt "}
   innerE    :M" ig = thiss.ci "}sld ig = this fig = thiss.cfhavnd M_renderHeadasig = thiss.cf*f icbsul_DOCUM    n f*r moreHTMLE    :Me modulhE    :MoOpase(lu. A referenceg(o (kckmodulh 
    :Mig =   do .m = this.e_renderHeadas:,tarCase(;modulhE    :M)   DEFAUL.    modulhE    :Mo= modulhE    :Mo||o}
   innerE    :M; 			Panel.sus f {
  ._renderHeadas.alue(    , modulhE    :M)  oString:i     var oConfig = thiss.cRenders,*
  cury = eyesHAdbodn, n bsiM's(
fvlem posiwnP du=d ri*
  ig = thiss.cmodulh 
    :M.cI  ecutmodulh 
    :M) i*  rkprovid dt "}
   innerE    :M" ig = thiss.ci "}sld ig = this fcig = thiss.cfhavnd M_renderBodnig = thiss.cf*f icbsul_DOCUM    n f*r moreHTMLE    :Me modulhE    :MoOpase(lu. A referenceg(o (kckmodulh 
    :M.ig =   do .m = this.e_renderBodn:,tarCase(;modulhE    :M)   DEFAUL.    modulhE    :Mo= modulhE    :Mo||o}
   innerE    :M;  DEFAUL.    Panel.sus f {
  ._renderBodn.alue(    , modulhE    :M)  oString:i     var oConfig = thiss.cRenders,*
  cury = eyesHAdfootasd n bsiM's(
fvlem posiwnP du=d ri*
  ig = thiss.cmodulh 
    :M.cI  ecutmodulh 
    :M) i*  rkprovid dt "}
   innerE    :M" ig = thiss.ci "}sld ig = this fig = thiss.cfhavnd M_renderFootasig = thiss.cf*f icbsul_DOCUM    n f*r moreHTMLE    :Me modulhE    :MoOpase(lu. A referenceg(o (kckmodulh 
    :Mig =   do .m = this.e_renderFootas:,tarCase(;modulhE    :M)   DEFAUL.    modulhE    :Mo= modulhE    :Mo||o}
   innerE    :M;  DEFAUL.    Panel.sus f {
  ._renderFootas.alue(    , modulhE    :M)  oString:i     var oConfig = this.cRemohas
ecutPanel 
    :M eoNITecutDOM 
"wieews
lulpchild(
    :Msig = this.ceo  rty.ig = this.cfhavnd Mdesuloyig = this.cf*r moreboolean} shlulowPurgecI  errhE onlyg(kckpay = o
    :M's(DOM eent Wl    onrs aredpurged.cI  hdSetE or   rkprovid dt lulpchildy = areddSeodpurged o  DOM eent Wl    onrs. ig = this* NOTE:cTn (tlagg i*a "shaulowPurge"(tlag, as opposeda bswha /may bck. mored ntuitiv ("purgeChildy ="(tlaga bsmaie
gCorbackwardsicompafn}ilityact pebehaviacgpriacg(o 2.9.0 ig = this*m = this.edesuloy:,tarCase();shaulowPurge     DEFAUL.     hasOwyawindowResiz
lent  unsrs.
     t
   siz
MaskEe    )   DEFAUL.       * remohaMask;
  oString: fun   }t
   nlo      oString: functioEng f.purgel    :M(t
   nlo     oString: fun}t
    Rts    Panel.sus f {
  .desuloy.alue(    , shaulowPurge ;tr
P@feonfii     var oConfig = thiss.cF.sces
ecutu=d rOwy 
    :Mg bs   repaie
eda hrougpe
n (.hclicafnon/remohal ig =   do n o  , yui-f.sce-redraw  {
  g(o (kcku=d rOwy 
    :M ig = this fig = thiss.cfhavnd Mf.sceU=d rOwyRedrawig =   do .m = this.ef.sceU=d rOwyRedraw :,tarCase();

wa  */
    Rtspexcuo  }
   undasOwn  oString: funuperaddC{
  (ut "yui-f.sce-redraw )  an  ChecccccsHATimeout(tarCase(;
{uperremohaC{
  (ut "yui-f.sce-redraw ) }, 0)  oString:i     var oConfig = this.cReturni*a ould b repe = :MModuleo  ecuto> 0)  ig = this.cfhavnd MtoSuld big = this.cfreturns{ould be Tcutsuld b repe = :MModuleo  ecutPanel.&      cl m = this.etoSuld b:,tarCase();

wa  */
    Rtsreturns"Panel " + }
  .im  oString:i oStr oStri)   }(
  D(tarCase();

wa zepaonfig = n <p>ig = n Dialogg i*ansdmp    :MModuleo  Panelgpratecan be"}sld  bssrsmit f.sm ig = n data.ig = n </p>ig = n <p>ig = n Built-CortarCase(ali=ycfacgbuttonsMorvn eent W success is  ncluded.cig = n I  ecutopase(lu YUI Button(ddcendancysis  ncluded on (kckpage**
n (buttonsig = n nreat
deor (sbe  nstastrseo      epwidg* .ButtonE othinwis  regulexcHTML/buttonsig = n or (sbe nreat
d.ig = n </p>ig = n <p>ig = n F.smsecan be"processul i: 3 ways -- via*ansasynchronous ConneCase()utili=ycalue,cig = n a sdmp   f.sm POST or GETE or manuluey.cTn (YUI ConneCase()utili=yce();

 bcig = n  ncluded if,you're"}snction (dd in,  "async" posthavnd ,/but) i*  rkrequired ifig = n you're"}snctianyeo  ecutothineposthavnd  ct rhs.ig = n </p>ig = n @n   space     epwidg* ig = n @ {
  gDialogig = n @
xMnndsi    epwidg* .Panelig = */@cthsuluctorig = */@*r moreould be eloTcut
    :MgID repe = :Mnction (Dialogg<em>OR</em>ig = n f*r moreHTMLE    :Me eloTcut
    :Mgrepe = :Mnction (Dialogig = n f*r more    var }slrCe Con Tcutrs.e C* Module > 0) gliterr ars.
gConctiig = n tcutrs.e C* Modulepratee();

 bcksHAdfacg(ki gDialog. Seutrs.e C* Moduleig = n d{
};

 Modulefacgm.re,de
gCls.ig = nm = th    epwidg* .Dialogg=,tarCase();elb/}slrCe Con

wa  */
       epwidg* .Dialog.sus f {
  .nthsuluctor.alue(    , elb/}slrCe Con
  oStr} D oStrpexclent W=     eputil.lent ,a  */
   Crewsilent W=     eputil.Crewsilent ,a  */
   upeW=     eputil.upe,a  */
   uialogg=,    epwidg* .Dialog,a  */
   Lte.g=,    eplte.     var oConfig = thiss.cConstasMgrepe = :Mnction (n   eo  ecutDialog's(ev :Msig = thiss.c@
fvlem=ycEVENT_TYPESig = thiss.c@
fivat
ig = thiss.c@finalig = thiss.c@t        vaig =   do .m = this.eEVENT_TYPESg=,wa  */
    Rts"BEFORE_SUBMIT": "bet.reSrsmit"tizepacka/  do"SUBMIT": "srsmit"tizepacka/  do"MANUAL_SUBMIT": "manuluSrsmit"tizepacka/  do"ASYNC_SUBMIT": "asyncSrsmit"tizepacka/  do"FORM_SUBMIT": "f.smSrsmit"tizepacka/  do"CANCEL": "castrl" oString:i     var oConfig = this.cConstasMgrepe = :Mnction (Dialog's(rs.e C* Module
fvlem=iesig = this  f*fvlem=ycDEFAULT_CONFIGig = this  f*fivat
ig = this.c@finalig = this.c@t        vaig =   do.m = this.eDEFAULT_CONFIGg=,waizepacka/  do"POST_METHOD": {t oString: functiokey:,"posthavnd ",t oString: functioct rh: "async" oString: funi     var oC  do"POST_DATA" :,  oString: functiokey:,"postdata"tizepacka/  doooooct rh:  rty oString: funi     var oC  do"BUTTONS":,  oString: functiokey:,"buttons"tizepacka/  doooooct rh: "none"*           dooooosus f edes: [ vi  }
 "] oString: funi     var oC  do"HIDEAFTERSUBMIT" :,  oString: functiokey:,"hid af  rsrsmit"tizepacka/  doooooct rh: errh oString: fun}t oString:i;a zepaonfig = n ConstasMgrepe = :Mnction (dd in,  CSS  {
  g}sld facga(Dialogig = n f*fvlem=yc    epwidg* .Dialog.CSS_DIALOGig = n fs Modcig = n ffinalig = .c@t    Suld big = .m = thDialog.CSS_DIALOG,= "yui-dialog"   ER":tarCase()remohaButtonlent Hsuccess;

wa zepacka/pexcaButtonsM  }
   _aButtonstizepacka/  donButtonstizepacka/  dooButtonE oString: fun   izepacka/   }Lte..isApt W(aButtons
     DEFAUL.    nButtonsM  aButtons.length  hey can  Chec   }nButtonsM>n0)r toString: functioic= nButtonsM- 1  oString: functiodo   oString: functiooooooButton(  aButtons[i]   oString: functiooooo   }    epwidg* .Button && oButton( nstastro      epwidg* .Button     DEFAUL.                oButton.desuloy;
  oString: functiooooo}   */
    Rtsssssssss        }oButton.tagN   .toUhcnrCas 
  ==o"BUTTON"    oString: functioooooooooEng f.purgel    :M(oButton
  oString: functioooooooooEng f.purgel    :M(oButton,ntdSet   oString: functiooooo}   */
    Rtsssss} P@feonfictiooooowhi   (i--
  oString: fun}t
    Rtsi oStr}
 = th    ep
xMnnd(Dialog,i    epwidg* .Panel, {t    var oConfig = this.cf*fvlem=ycf.smig = this.cfdescripase(     va referenceg(o (kckDialog's(ig = this.c<code>&#60;f.sm&#62;</code>t
    :M.ig =   do.cfdd in,   rty(ig = this.c@t    <a href="http://www.w3.org/TR/2000/WD-DOM-Lev l-1-20000929/ig = this.clev l-one-html.html#ID-40002357">HTMLF.sml    :M</a>ig = this.m = this.ef.sm:  rty, oStr oStrr oConfig = this.cInitializes
ecut {
  's(rs.e C* Mble 
fvlem=iesswhich can be"cute.
m(ig = this.c}snction (Dialog's(Ce Con  > 0) g(cfg) ig = this.cfhavnd MCon Dd in, Ce Conig = this.m = this.eCon Dd in, Ce Con:,tarCase();

wa  */
    RtsDialog.sus f {
  .Con Dd in, Ce Con.alue(    )  a  */
    Rtsonfig = this his.cTn (ie
er(lueyemaie
gCoedtrallback  > 0) gfacgonl orvn *
  ig = thiss = n ConneCase()utili=y.cTn (f.smateo  ecutrallback  > 0) g i*ig = thiss = n sdmilexc(o ConneCase()Manager's(rallback  > 0) g
"wi i*ig = thiss = n sdmpeyep
  ul througpe
o ConneCase()Manager whue,*
  async*ig = thiss = n requesui i*mad
. g = this his.cf*fvlem=ycrallback g = this his.cft        vaig =   do his.m = this.eeeeet
   nallback =,waizepacka/  do Rtsonfig = this his his.cTn (tarCase()*o execute upe()success of *
  ig = thiss = s = n ConneCase()srsmissse();whue,*
  f.sm does*  rig = thiss = s = n rs.
gCo*a fi   inpu o
    :M). g = this hisssss* ig = thiss = s = n f*fvlem=ycrallback.successig = thiss = s = n ft    FarCase(ig = thiss = s = nm = this.e        success:  rty, izepacka/  do Rtsonfig = this his his.cTn (tarCase()*o execute upe()failure o  ecutig = thiss = s = n ConneCase()srsmissse(ig = thiss = s = n f*fvlem=ycrallback.failureig = thiss = s = n ft    FarCase(ig = thiss = s = nm = this.e        failure:  rty, izepacka/  do Rtsonfig = this his his.<p>ig =   do his his.cTn (tarCase()*o execute upe()success of *
  ig = thiss = s = n ConneCase()srsmissse(, whue,*
  f.sm rs.
gCosig = thiss = s = n a fi   inpu o
    :M. g = this hisssss* </p>ig =   do hisssss* <p>ig =   do hisssss* <em>NOTE:</em> ConneCase()manager wr (s  rig =   do hisssss* invok
d ncksuccess or failure  success facg(kckfi  ig =   do hisssss* uploadgonl canl.cTnispor (sbe ecutonlygrallback g = this hisssss*  successinvok
d. g = this hisssss* </p>ig =   do hisssss* <p>ig =   do hisssss* Facgm.re,inf.smatse(, see ecut<a href="http://dev lvlem.yahoo.com/yui/conneCase(/#fi  ">ig =   do hisssss* ConneCase()Manager d{
};

Module n fi   uploads</a>. g = this hisssss* </p>ig =   do hisssss* f*fvlem=ycrallback.uploadig = thiss = s = n ft    FarCase(ig = thiss = s = nm izepacka/  do Rtsonfig = this his his.cTn (arbitrary*apgu**
  acgapgu**
    bsp
  g(o (kckConneCase()ig = thiss = s = n rallback tarCase(sig = thiss = s = n f*fvlem=ycrallback.apgu**
 ig = thiss = s = n ft        vaig =   do hiss = nm g =   do hiss = apgu**
 :  rty  oString: fun}  a  */
    Rtso/ Add f.sm dialoggrs.e C 
fvlem=iess/m g =   do hisonfig = this his.cTn (havnd Mtogonl facgpostnction (Dialog's(f.sm. Pos  }
  ct rhs*ig = thiss = n ared"async", "f.sm", ("wi"manulu". g = this his.cfrs.e C 
osthavnd  g = this his.cft    Suld big = this his.cfdd in,  asyncig =   do his.m = this.eeeeet
   nfgradddass ffvaDEFAULT_CONFIG.POST_METHOD.ken,r  oString: functio succes: }
   ns.e CPostMavnd ,/izepacka/  doooooct rh: DEFAULT_CONFIG.POST_METHOD.ct rhE izepacka/  doooooct idator:,tarCase();val)r toString: functiooooo   }hal != "f.sm" && hal != "async" && hal != "none" &&  oString: functiooooooooohal != "manulu"    oString: functioooooooooreturnshdSet DER":P@feonfionfioooo}e      toString: functioooooooooreturns}    DER":P@feonfionfioooo}   */
    Rtsssss} P@feonfictio})  a  */
    Rtsonfig = this his.cAny*addiase(lu 
ost dataswhich needsp bs   s

  whue,}snction (ig = this his.c<a href="#ns.e C_posthavnd ">async</a>eposthavnd  facgdialoggPOST srsmissse(s. g = this his.cTn (f.smatefacg(kckpost datassuld b ispd fined byoConneCase()Manager's(ig = this his.c<a href="    eputil.ConneCa.html#havnd _asyncRequesu">asyncRequesu</a>eig = this his.chavnd . g = this his.cfrs.e C 
ostdata g = this his.cft    Suld big = this his.cfdd in,   rty oString: fun.m = this.eeeeet
   nfgradddass ffvaDEFAULT_CONFIG.POST_DATA.ken,r  oString: functioct rh: DEFAULT_CONFIG.POST_DATA.ct rh P@feonfictio})  a  */
    Rtsonfig = this his.cTnisp
fvlem=ycd "}sld  bscthe C*   whuthineor   rkon (ig = this his.cdialogge();

 bck.utomatsclueyehidd = af  rssrsmit. g = this his.c g = this his.cfrs.e C hid af  rsrsmit g = this his.cft    Booleanig = this his.cfdd in,  errh oString: fun.m = this.eeeeet
   nfgradddass ffvaDEFAULT_CONFIG.HIDEAFTERSUBMIT.ken,r  oString: functioct rh: DEFAULT_CONFIG.HIDEAFTERSUBMIT.ct rh P@feonfictio})  a  */
    Rtsonfig = this his.cA t Wao   > 0) gliterr  , each cs.
gConctiaksHAdo  
fvlem=iessig = this his.cd finnctiakbuttonp bs   .hcend da n bsent(Dialog's(f.otas.ig = this his.ig = this his.c<p>Each buttonp > 0) g n*
n (buttons(a t Wacan have ecree 
fvlem=ies:</p>ig =   do his.c<dl>ig =   do his.cccc<dt>
 xt:</dt>ig =   do his.cccc<dd>ig =   do his.cccccccTn (
 xteprateor (s ispOwn,oe,*
  face of *
  button.cTn (
 xtecan ig =   do his.ccccccc ncludecHTML, as loe. as i od "compliasMgorvn HTML/Button(specif.cafnons.cTn (
 xte i*add da bsecutDOM 
scHTML,ig =   do his.ccccccc
"wie();

 bckescaped byoecutdmp    :Mor    comfctieoNITan 
xMnr(lu source. ig =   do his.cccc</dd>ig =   do his.cccc<dt> succes:</dt>ig =   do his.cccc<dd>Can be"eithin:ig =   do his.cccc<ol>ig =   do his.ccccccc<li>A referenceg(o a(tarCase()*ratee();

 fire whue,*
  ig =   do his.cccccccbuttonpd "click
d.  (Ing(ki gcanltscvletof,*
 i*tarCase() i*ig = thiss = n cccccc
lways its Dialogg nstastr.)</li>ig = this his.ig = this his.ccccccc<li>Ale > 0) gliterr arepe = :Mnction (codep bs   ig = this his.cccccccexecuted whue,*
  buttonpd "click
d.ig = this his.cccccccig = this his.ccccccc<p>F.smat:</p>ig =   do his.ig = this his.ccccccc<p>ig = this his.ccccccc<code>{ig = this his.ccccccc<br>ig = this his.ccccccc<sulong>fn:</sulong> FarCase(, &#47;&#47;ig =   do his.cccccccTn ( success bsclulpwhue,ion (
ent Wfires. g = this his.ccccccc<br>ig = this his.ccccccc<sulong> > :</sulong>     va, &#47;&#47;cig = this his.cccccccAlee > 0) g bsp
  gback  bsecut succes. g = this his.ccccccc<br>ig = this his.ccccccc<sulong>scvle:</sulong>     va &#47;&#47;cig = this his.cccccccTcuto> 0) Mtogonl facg nckscvletof,*
ut succes. g = this his.ccccccc<br>ig = this his.ccccccc}</code> g = this his.ccccccc</p>ig =   do his.ccccccc</li>ig = this his.ccccc</ol>ig =   do his.ccccc</dd>ig =   do his.ccccc<dt>isDd in, :</dt>ig =   do his.ccccc<dd>ig =   do his.ccccccccAleopase(lu boolean ct rhr*rateepecif.ws pratea buttonpig =   do his.cccccccce();

 bckhighl;
  
d a"wif{
}sed byodd in, . g = this his.ccccc</dd>ig =   do his.c</dl>ig =   do his.ig =   do his.c<em>NOTE:</em>I  ecutYUI Button(Widg* sis  ncluded on (kckpage**ig =   do his.c
n (buttons(nreat
deor (sbe  nstastrseo      epwidg* .Button. ig =   do his.cOthinwis , HTML/Buttons (<code>&#60;BUTTON&#62;</code>)eor (sbe ig =   do his.cnreat
d.ig =   do his.ig =   do his.cfrs.e C buttonsig =   do his.cft    eApt W|ould beig = this his.cfdd in,  "none" oString: fun.m = this.eeeeet
   nfgradddass ffvaDEFAULT_CONFIG.BUTTONS.ken,r  oString: functio succes: }
   ns.e CButtonstizepacka/  doooooct rh: DEFAULT_CONFIG.BUTTONS.ct rhEizepacka/  dooooosus f edes : DEFAULT_CONFIG.BUTTONS.sus f edes P@feonfictio}) t    var oCi     var oConfig = this.cInitializes
ecut rewsi eent s facgDialoggwhich aredfirem*ig = this.c.utomatsclueyeateap
fvlriabedtimes
byoecutDialogg {
  .ig = this.cfhavnd MCon Ev :Msig = this.m = this.eCon Ev :Ms:,tarCase();

wa  */
    RtsDialog.sus f {
  .Con Ev :Ms.alue(    )  a  */
    RtspexcSIGNATURE = Crewsilent .LIST     */
    Rtsonfig = this his.cCrewsilent Wfirem*priacg(o srsmissse(ig = thiss = n @
ent Wbet.reSrsmitlent  oString: fun.m  = this.eeeeet
   bet.reSrsmitlent  = izepacka/  dooooo}
   nreat
lent (EVENT_TYPES.BEFORE_SUBMIT)   DEFAUL.       * bet.reSrsmitlent . ignat*   =cSIGNATURE   DEFAUL.       */
    Rtsonfig = this his.cCrewsilent Wfirem*af  rssrsmissse(ig = thiss = n @
ent Wsrsmitlent  oString: fun.m  DEFAUL.       * srsmitlent  = }
   nreat
lent (EVENT_TYPES.SUBMIT)   DEFAUL.       * srsmitlent . ignat*   =cSIGNATURE   DEFAUL.   */
    Rtsonfig = this his.cCrewsilent Wfirem*for manulu)srsmissse(, bet.re,*
e g onricssrsmit 
ent W i*tiremig = thiss = n @
ent WmanuluSrsmitlent  oString: fun.m  DEFAUL.       * manuluSrsmitlent  = izepacka/  dooooo}
   nreat
lent (EVENT_TYPES.MANUAL_SUBMIT)   DEFAUL.       * manuluSrsmitlent . ignat*   =cSIGNATURE     */
    Rtsonfig = this his.cCrewsilent Wfirem*af  rsasynchronous srsmissse(, bet.re,*
e g onricssrsmit 
ent W i*tiremig = thiss = nig = thiss = n @
ent WasyncSrsmitlent  oString: fun. f*r more    var conn Tcutrs.neCase()o   va, returned byo    eputil.ConneCa.asyncRequesu oString: fun.m  DEFAUL.       * asyncSrsmitlent  = }
   nreat
lent (EVENT_TYPES.ASYNC_SUBMIT)   DEFAUL.       * asyncSrsmitlent . ignat*   =cSIGNATURE     */
    Rtsonfig = this his.cCrewsilent Wfirem*af  rsf.sm-basld srsmissse(, bet.re,*
e g onricssrsmit 
ent W i*tiremig = thiss = n @
ent Wf.smSrsmitlent  oString: fun.m  DEFAUL.       * f.smSrsmitlent  = }
   nreat
lent (EVENT_TYPES.FORM_SUBMIT)   DEFAUL.       * f.smSrsmitlent . ignat*   =cSIGNATURE     */
    Rtsonfig = this his.cCrewsilent Wfirem*af  rscastrlig = thiss = n @
ent Wcastrllent  oString: fun.m  DEFAUL.       * castrllent  = }
   nreat
lent (EVENT_TYPES.CANCEL   P@feonfictio

   nastrllent . ignat*   =cSIGNATURE   DEFAUL.   */
   i  P@feonfi   var oConfig = this.cTn (Dialogg nitializModulehavnd ,/which ispexecuted facgDialogga"wiig = this.c. (so  its srs {
  es.cTnisphavnd M i*automatsclueyealuledtbyoecutig = this.cnthsuluctor, ("wiieews
upc. (sDOM references facgpre-existnctimarkup**ig = this.c."winreat
skrequired markup i  it  i*  rkalreadydpe = :M.ig = this.cig = this.cfhavnd MCon ig = this.cf*r moreould be eloTcut
    :MgID repe = :Mnction (Dialogg<em>OR</em>ig = this.cf*r moreHTMLE    :Me eloTcut
    :Mgrepe = :Mnction (Dialogig = this.cf*r more    var }slrCe Con Tcutrs.e C* Module > 0) gliterr aig = this.cnth
gConctitcutrs.e C* Modulepratee();

 bcksHAdfacg(ki gDialog. ig = this.cSeutrs.e C* Moduled{
};

 Modulefacgm.re,de
gCls.ig =  fun.m  DEFAUL.Con :,tarCase();elb/}slrCe Con

wa   */
    Rtsonizepacka/  dooooo Nothr*ratewt( {n't p
  g(he"}sls cthe Cg n*hine yHAdbecaonl izepacka/  dooooo wutonlygwasMgit executed ostr,"ateecutlowesuisrs {
  clev l oString: fun.m a  */
    RtsDialog.sus f {
  .Con .alue(    , el/*b/}slrCe Con*/) t    var oCCCCC   * bet.reIon Ev :M.fireaDialog)  a  */
    RtsuperaddC{
  (}
   e    :M, Dialog.CSS_DIALOG    P@feonfictio

   nfg s* dass ffva vi  }
 ",ntdSet    P@feonfictio   }}slrCe Con

wa  */
   onfictio

   nfg .hclyCe Con}}slrCe Con, 
        */
    Rts}tDER":P@feonfi//}
   showlent  srs.
        * f. reFifs*,     , 
        */
    Rts}
   bet.reHid lent  srs.
        * blurButtonst     , 
        DEFAUL.       * srs.
     "cute.
Bodn",    * reg    rF.sm     DEFAUL.       * ion Ev :M.fireaDialog)  
    Rts}     var oConfig = this.cSrsmitsion (Dialog's(f.sm(ddcendnction (kckct rhrof *
  ig = this*,"posthavnd "(rs.e C* Module
fvlem=y.cc<sulong>Pleanlt  re:ig = this*,</sulong> Aseo  versse()2.3 *
isphavnd Mor (s.utomatsclueyehsucce*ig = this* 
 yncronous fi   uploadsee();

 tn (Dialogg nstastr's(f.sm(rs.
gCo*ig = this*,<code>&#60;inpu ot   ="fi  "&#62;</code>t
    :Ms.c I  a(Dialog ig = this* instastrror (sbe hsuccnctia yncronous fi   uploads, its ig = this*,<code>rallback</code>t
fvlem=ycwr (s e da bsbcks* up orvn a ig = this*,<code>upload</code>t success Mohnrc*
anc nckstasdard ig = this*,<code>success</code>tasdE or <code>failure</code>t success.c Facgm.re,ig = this* inf.smatse(, see ecut<a href="http://dev lvlem.yahoo.com/yui/ig = this.cnthneCase(/#fi  ">ConneCase()Manager d{
};

Module n fi   uploads</a>. g = this.cfhavnd MdoSrsmit g = this*m = this.edoSrsmit:,tarCase();

wa zepacka/**
rpexcConneCaW=     eputil.ConneCatizepacka/  dooooooF.sm =    * f.smtizepacka/  dooooobUseFi  UploadgintdSettizepacka/  dooooobUseSecureFi  UploadgintdSettizepacka/  doooooaE    :Mstizepacka/  dooooonE    :Mstizepacka/  doooooitizepacka/  dooooof.smAttr   tuplicate = tsorvch }t
   nfg g* dass ffva posthavnd "))    oString: functiocanlt"async": oString: functioooooaE    :Mso  oF.sm e    :M     */
           oooonE    :Ms(  aE    :Ms.length  hey can  Chec  doooooi  }nE    :Ms(>n0)r toString: functio  doooooic= nE    :Ms(- 1  oString: functiooooooooodo   oString: functiooooo  doooooi  }aE    :Ms[i].t    ==o"fi  "   toString: functiooooooooo        bUseFi  Uploadgin}    DER":P@feonfionfioooooooo        break DER":P@feonfionfioooooooo    }   */
    Rtsssssssss    }   */
    Rtsssssssss    whi  (i--
  oString: funssss    } hey can  Chec  doooooi  }bUseFi  Uploadg&&     epenv.ua.ier&& }
   isSecure   toString: functiooooooooobUseSecureFi  Uploadgin}    DER":P@feonfionfioooo} DER":P@feonfionfioooof.smAttr M  }
   _g* F.smAttributes(oF.sm     DEFAUL.            ConneCa.s* F.sm(oF.sm, bUseFi  Upload,obUseSecureFi  Upload     DEFAUL.            pexcpostDatas= t
   nfg g* dass ffva postdata"
  oString: funssss    pexcc = ConneCa.asyncRequesu(f.smAttr .havnd ,/f.smAttr .aCase(, t
   nallback,cpostData     DEFAUL.               * asyncSrsmitlent .fireac     DEFAUL.            break D oString: functiocanlt"f.sm": oString: functiooooooF.sm srsmit;
  oString: functiooooo   * f.smSrsmitlent .firea
  oString: fun        break D oString: functiocanlt"none": oString: functiocanlt"manulu": oString: functiooooo   * manuluSrsmitlent .firea
  oString: fun        break D fun        }t
    Rtsi     var oConfig = thiss.cRetriehas
importasMgattributes (cury = eyehavnd M."wiaCase()ieoNIig = thiss.c*
  f.sm e    :M, accou:Mnctifacgany 
    :Msswhich may have eccks   en   eig = thiss.casion (attributes. Dd in, sp bs"POST" ("wi""efacgmavnd M."wiaCase(greepectiv lnig = thiss.cifion (attributeWcas  rk   retriehad ig = this fig = thiss.cfhavnd M_g* F.smAttributesig = thiss.cf*f icbsul_DOCUM    n f*r moreHTMLF.sml    :M}ooF.sm TcutHTML/F.sm e    :MieoNITwhich eo retriehaion (attributes_DOCUM    n freturns{    var O> 0) gliterr , orvn mavnd M."wiaCase(gould b 
fvlem=ies.ig =   do .m = this.e_g* F.smAttributes :,tarCase((oF.sm wa  */
    Rtspexcattr M   toString: functiomavnd M:  rty, oStrrrrrrrrrrrrraCase(g:  rty oString: funi  tuplicate = t   }oF.sm     DEFAUL.           }oF.sm g* Attribute/**
     DEFAUL.    
    RtspexcaCase(g  oF.sm g* Attribute/**
("aCase("
  oString: funssss    pexcmavnd M  oF.sm g* Attribute/**
("havnd ")  hey can  Chec  doooooi  }aCase()i toString: functioooooooooattr .aCase((  aCase(.ct rh  oString: funssss    } hey can  Chec  doooooi  }havnd )i toString: functioooooooooattr .mavnd M  havnd .ct rh  oString: funssss    } hey can  Chec  do}e      toString: functioooooattr .aCase((  oF.sm g* Attribute("aCase("
  oString: funssss    attr .mavnd M  oF.sm g* Attribute("havnd ")  ing: funssss    }  funssss    } hey can  Checattr .mavnd M  }Lte..isould b(attr .mavnd ) ?cattr .mavnd M:s"POST").toUhcnrCas 
 ;hey can  Checattr .aCase((  Lte..isould b(attr .aCase()i?cattr .aCase((:s""   ER":P@feonfireturnsattr   ssss    }     var oConfig = this.cPreparesion (Dialog's(ie
er(lu FORM)o   va, nreatnction     on   sig = this    rkcury = eyepe = :M.ig = this.cfhavnd Mreg    rF.smig = this.m = this.ereg    rF.sm: tarCase(;

waa  */
    Rtspexcf.sm =    * 
    :M.g* E    :MsByTagN   ("f.sm")[0]   oString: fun   }t
   f.sm     DEFAUL.           }t
   f.sm ==of.sm && uperisAncestor(}
   e    :M, t
   f.sm )i toString: functioooooreturn DER":P@feonfictio}e      toString: functioooooEng f.purgel    :M(t
   f.sm   oString: functiooooot
   f.sm =  rty  ing: funssss    }  funssss    } hey can  Chec   }!f.sm     DEFAUL.        f.sm =  {
};

 .nreat
l    :M("f.sm")  ing: funssss    f.sm.n   a= "frm_" + }
  .im  oString:ctiooooot
   bodn..hcendChild}f.sm   oString: fun} hey can  Chec   }f.sm     DEFAUL.        t
   f.sm = f.sm  oString:ctioooooEng f.e(;f.smt "srsmit"t }
   _srsmitHsucces,     , 
        */
    Rts}t
    Rtsi     var oConfig = thiss.cIe
er(lu  successfacg(kckf.sm srsmit 
ent ig = this fig = thiss.cfhavnd M_srsmitHsuccesig = thiss.cf*f icbsul_DOCUM    n f*r moreDOMEng f}e cTn (DOMclent Wo   vaig =   do .m = this.e_srsmitHsucces :,tarCase((
     DEFAUL.    lent  stoplent (      */
    Rts}
   srsmit;
  oString: funt
   f.sm blur()  
    Rts}     var oConfig = this .cSews
upc. tab, shift-tab loopk  tweue,*
  fifs*M."wilast(
    :Msig = this .cprovid d. NOTE:cSews
upc(kckpr
ent BackTab ."wipr
ent TabOut KeyL    onrig = thiss.cinstastrr
fvlem=ies,gwhich arede = t 
enrytime *
isphavnd Mis  nvok
d. g = this fig = thiss.cfhavnd MsHATabLossig = thiss.cf*r moreHTMLE    :Me fifs*E    :Mig =   do .cf*r moreHTMLE    :Me lastE    :Mig =   do .ig =   do .m = this.esHATabLoss :,tarCase((fifs*E    :M, lastE    :M

waa  */
    Rtsfifs*E    :M = fifs*E    :M ||o}
   fifs*Button  oString: funlastE    :M = lastE    :M ||o}
   lastButton   oString: funDialog.sus f {
  .sHATabLoss.alue(    , fifs*E    :M, lastE    :M
  
    Rts}     var oConfig = this .cPf icbsul(ie
er(lu havnd  facgsHATabLoss,swhich can be"}sed byoig = this .csrs {
  es eo jump Co*a"wimodifyoecutapgu**
   p
  ul Co*   required ig = this fig = thiss.cfhavnd M_sHATabLossig = thiss.cf*r moreHTMLE    :Me fifs*E    :Mig =   do .cf*r moreHTMLE    :Me lastE    :Mig =   do .cf*f icbsul_DOCUM    nm = this.e_sHATabLoss :,tarCase((fifs*E    :M, lastE    :M

wa  */
    Rtsfifs*E    :M = fifs*E    :M ||o}
   fifs*Button  oString: funlastE    :M = }
   lastButton ||olastE    :M    DEFAUL.       * sHATabLoss(fifs*E    :M, lastE    :M
  
    Rts}     var oConfig = this .cCthe C*  scinstastrr
fvlem=ies,gpoi:Mnctioo,*
  ig =   do * fifs*M."wilast(f. reMble 
    :Mss n*
n (Dialog's(f.sm. g = this fig = thiss.cfhavnd MsHAFifs*LastF. reMbleig =   do .m = this.esHAFifs*LastF. reMble : tarCase(;

waa  */
    RtsDialog.sus f {
  .sHAFifs*LastF. reMble.alue(    )  a  */
    Rtspexci, l, elb/
    :Mss=    * f. reMbleE    :Ms    DEFAUL.       * fifs*F.sml    :M =  rty  ing: funssss}
   lastF.sml    :M =  rty   oString: fun   }t
   f.sm && 
    :Mss&& 
    :Ms.length(>n0)r toString: functiol = 
    :Ms.length  hey can  Chec  dofacg(ic= 0; i < l; ++i)i toString: functioooooel = 
    :Ms[i]  oString: functiooooo   }t
   f.sm === 
  f.sm     DEFAUL.        AUL.       * fifs*F.sml    :M = ey  ing: funssss            break DER":P@feonfionfioooo}   */
    Rtsssss} hey can  Chec  dofacg(ic= l-1; i >= 0; --i)i toString: functioooooel = 
    :Ms[i]  oString: functiooooo   }t
   f.sm === 
  f.sm     DEFAUL.        AUL.       * lastF.sml    :M = ey  ing: funssss            break DER":P@feonfionfioooo}   */
    Rtsssss} 
    Rtsssss} 
    Rts}     var oCo/ BEGIN BUILT-IN PROPERTYeEVENT HANDLERSs/m g =   doonfig = this.cTn (dd in,  eent W succesWfirem*whue,*
  "nlo  "p
fvlem=ycd "ig = this.ccute.
m.cTn (havnd Mrs.
rolsion (apcendnctior hidnctio  ecutrlo  ig = this.cirs."ateecuttop r;
  oo  ecutDialog.ig = this.cfhavnd Mrs.e CClo  ig = this.cf*r moreould be t    Tn (Crewsilent Wt    (usulueyc(kckprvlem=ycn   )ig = this.cf*r moreO> 0) []}tapgs Tn (Crewsilent Wapgu**
  . Facgig = this.ccs.e C* Module success**,pgs[0]cwr (sequalc(kckneweye.hcl.wi ct rhrig = this.cfacg(kckpfvlem=y.ig = this.cf*r moreO> 0) }Wo   Tcutscvleto> 0)   Facgcs.e C* Module success**ig = this.ctnispor (susulueycequalc(kckownes. g = this.m = this.ers.e CClo  :,tarCase();t   **,pgs,to> 

wa  */
    RtsDialog.sus f {
  .rs.e CClo   .hcly(    , .pgu**
  )  zepacka/}     var oConfig = this .cEent W succesWfacg(kckrlo  cirs.ig = this .cig = thiss.cfhavnd M_doClo  ig = thiss.cf*f icbsul_DOCUM    n _DOCUM    n f*r moreDOMEng f}e ig =   do .m = this.eM_doClo   :,tarCase((
     DEFAUL.    lent  pr
ent Dd in, (      */
    Rts}
   nastrl()  
    Rts}     var oConfig = this.cTn (dd in,  eent W succesWfacg(kck"buttons"(rs.e C* Module
fvlem=yig = this.cfhavnd Mrs.e CButtonsig =   do.cf*r moreould be t    Tn (Crewsilent Wt    (usulueyc(kckprvlem=ycn   )ig = this.cf*r moreO> 0) []}tapgs Tn (Crewsilent Wapgu**
  . Facgrs.e C* Moduleig = this.c success**,pgs[0]cwr (sequalc(kckneweye.hcl.wi ct rhrfacg(kckpfvlem=y.ig = this.cf*r moreO> 0) }Wo   Tcutscvleto> 0)   Facgcs.e C* Module success**ig = this.ctnispor (susulueycequalc(kckownes. g = this.m = this.ers.e CButtons:,tarCase();t   **,pgs,to> 

waa  */
    RtspexcButton(      epwidg* .ButtonE   */
    RtsssssaButtonsM  ,pgs[0]tizepacka/  dooooooInnerE    :M/= }
  .innerE    :Mtizepacka/  dooooooButtonE   */
    RtsssssoButtonEy, oStrrrrrrrrrrrrroYUIButtonE   */
    RtsssssnButtonstizepacka/  dorrrroSpanE   */
    RtsssssoFootastizepacka/  doooooi   ER":P@feonfiremohaButtonlent Hsuccess.alue(    )  a  */
    Rts}
   _aButtons =  rty   oString: fun   }Lte..isApt W(aButtons
    izepacka/  dorrrroSpan =  {
};

 .nreat
l    :M("span")  ing: funssss    oSpan. {
  N   a= "button-group"  oString: functionButtonsM  aButtons.length  hey can  Chec Rts}
   _aButtons = []  oString: functio}
   dd in, HtmlButton(   rty   oString: funnnnnfacg(ic= 0; i < nButtons; i++     DEFAUL.        AUL.oButton(  aButtons[i]   oString: functiooooo   }Button     DEFAUL.                oYUIButton(   ewcButton({ilabel: oButton.
 xt,Wt   :oButton.
    }
  oString: functiooooooooooYUIButton..hcendTo(oSpan)  hey can  Chec  doooooAUL.oButtonEl = oYUIButton.g* ("
    :M")  hey can  Chec  dooooooooo   }oButton.isDd in,    toString: functiooooooooo    oYUIButton..ddC{
  ("dd in, "
  oString: funssss            }
   dd in, HtmlButton(  oButtonEl  oString: funssss        } hey can  Chec  dooooooooo   }Lte..isFarCase((oButton. succes
    izepacka/  dorrrroooooooo    oYUIButton.s* ("onclick", {t oString: functiooooooooooooooooofn: oButton.hsucces,  oString: functiooooooooooooooooo > :     ,  oString: functioooooooooooooooooscvle:ctnisp oString: functiooooooooooooo})  a  */
    Rtsoooooooooooo}s        }Lte..isO> 0) (oButton. succes
s&& Lte..isFarCase((oButton. succes.fn
    izepacka/  dorrrroooooooo    oYUIButton.s* ("onclick", {t oString: functiooooooooooooooooofn: oButton.hsucces.fn,  oString: functiooooooooooooooooo > : ((!Lte..isU=d fined(oButton.hsucces.o> 
)i?coButton.hsucces.o>  :ctnis),  oString: functioooooooooooooooooscvle:c(oButton.hsucces.scvlet||o}
  )p oString: functiooooooooooooo})  a  */
    Rtsoooooooooooo} a  */
    Rtsoooooooooooo}
   _aButtons[}
   _aButtons.length] = oYUIButton  a  */
    Rtsoooooooo}e      they can  Chec  doooooAUL.oButtonEl =  {
};

 .nreat
l    :M("button"
  oString: funssss        oButtonEl.s* Attribute("
   ", "button"
  hey can  Chec  dooooooooo   }oButton.isDd in,    toString: functiooooooooo    oButtonEl. {
  N   a= "dd in, "  oString: funssss            }
   dd in, HtmlButton(  oButtonEl  oString: funssss        } hey can  Chec  dooooooooooButtonEl.innerHTML/= oButton.
 xt  hey can  Chec  dooooooooo   }Lte..isFarCase((oButton. succes
    oString: funssss            Eng f.e(;oButtonEy, "nlick", oButton.hsucces,     , 
        */
    Rtsssss        }s        }Lte..isO> 0) (oButton. succes
s&&  oString: funssss            Lte..isFarCase((oButton. succes.fn
         oString: funssss            Eng f.e(;oButtonEy, "nlick",  oString: functiooooooooooooooooo Button.hsucces.fn,  oString: functiooooooooooooooooo((!Lte..isU=d fined(oButton.hsucces.o> 
)i?coButton.hsucces.o>  :ctnis),  oString: functiooooooooooooooooo(oButton.hsucces.scvlet||o}
  )     */
    Rtsssss        } hey can  Chec  dooooooooooSpan..hcendChild}oButtonEy     */
    Rtsssss        }
   _aButtons[}
   _aButtons.length] = oButtonEl  oString: funssss    } hey can  Chec  dooooooButton.htmlButton(  oButtonEl   oString: functiooooo   }i === 0)r toString: functio  dooooo}
   fifs*Button = oButtonEl  oString: funssss    } hey can  Chec  dooooo   }i == }nButtonsM- 1
     DEFAUL.        AUL.       * lastButton = oButtonEl  oString: funssss    } ing: funssss    } hey can  Chec  do   * sHAFootas(oSpan)  hey can  Chec  dooFootass=    * f.otas  hey can  Chec  do   }uperinD{
};

 (}
   e    :M
 && !uperisAncestor(oInnerE    :M,ooFootas
     DEFAUL.        AUL.oInnerE    :M..hcendChild}oFootas
  ing: funssss    } hey can  Chec  do   * buttonSpan = oSpan  hey can  Chec}e      Co/ Do cleanuphey can  Chec  dooSpan =    * buttonSpan  ing: funssss    oFootass=    * f.otas  ey can  Chec  do   }oSpan && oFootas
    DEFAUL.        AUL.oFootas.remohaChild}oSpan)   DEFAUL.        AUL.   * buttonSpan =  rty  ing: funssss    AUL.   * fifs*Button =  rty  ing: funssss    AUL.   * lastButton =  rty  ing: funssss    AUL.   * dd in, HtmlButton(   rty   funssss    AUL.}  funssss    } hey can  Chec}
   nute.
Cont :Mlent .firea
  oString:}     var oConfig = this.cfhavnd Mg* Buttonsig =   do.cfdescripase( Returni*an(a t Wacth
gConctieach o  ecutDialog's(ig =   do.cbuttonst byodd in, *an(a t Wao  HTML/<code>&#60;BUTTON&#62;</code>(ig =   do.c
    :Ms.c I  ecutDialog's(buttons(wine nreat
de}snction (ig = this*     epwidg* .Button  {
  g(via*ecutdnclusduleo  ecutopase(lu Buttonpig =   do*(ddcendencyson (kckpage),*an(a t Wao      epwidg* .Button  nstastrseig =   do*(is returned.ig = this.cfreturns{Apt W}  funssss.m = this.eg* Buttons:,tarCase();

wa  */
    Rtsreturns}
   _aButtons ||o rty   funssss}     var oConfig = this .c<p>ig = this .cSews
f. reioo,*
  fifs*Mf. reMble 
    :Ms n*
n (Dialog's(f.smo   foun ,/izepacka/ *e    **
n (dd in, *buttonpd  foun ,/     *
  fifs*Mbuttonpd fined via*ecutizepacka/ *e"buttons"(rs.e C* Module
fvlem=y ig = this fc</p>ig =   do .c<p>ig = this .cTnisphavnd M i* nvok
d*whue,*
  Dialogg i*mad
 vi  }
  ig = this fc</p>ig =   do .cfhavnd Mf. reFifs*ig =   do .cfreturns{Boolean} errhE d  fo
}sed.ntdSet d    rig =   do .m = this.ef. reFifs*:,tarCase();t   **,pgs,to> 

waa  */
    Rtspexcel =    * fifs*F.sml    :M,  oString: functiof{
}sed =shdSet D oString: fun   }apgs && ,pgs[1]    oString: functioEng f.stoplent (,pgs[1]   hey can  Chec  doo/ Whue,*abbnctihine,gonl fifs*E    :M  nstead o  fifs*F.sml    :M ey can  Chec  do   },pgs[0]c=== 9r&& }
   fifs*E    :M)i toString: functioooooel = }
   fifs*E    :M  ing: funssss    }  funssss    } hey can  Chec   }el)r toString: functiotryi toString: functioooooel f. rea
  oString: fun        f{
}sed =s}    DER":P@feonfionfi} catch(oExcepase()i toString: functioooooo/ Ignoreig = thiss = s = }  funssss    }e      toString: functio   }t
   dd in, HtmlButton)i toString: functiooooof{
}sed =s}  * f. reDd in, Button(
  ing: funssss    }e      toString: functiooooof{
}sed =s}  * f. reFifs*Button(
  ing: funssss    }
 funssss    }
 funssss    returnsh{
}sed  
    Rts}     var oConfig = this.cSews
f. reioo,*
  last(
    :Ms n*
n (Dialog's(f.smoacg(kcklast(ig =   do.cbuttonpd fined via*ecut"buttons"(rs.e C* Module
fvlem=y ig = this.cfhavnd Mf. reLastig = this.cfreturns{Boolean} errhE d  fo
}sed.ntdSet d    rig =   do.m = this.ef. reLast:,tarCase();t   **,pgs,to> 

waa  */
    RtspexcaButtonsM  }
   nfg g* dass ffva buttons")tizepacka/  doooooel = }
   lastF.sml    :Mtizepacka/  dooooof.
}sed =shdSet D oString: fun   }apgs && ,pgs[1]    oString: functioEng f.stoplent (,pgs[1]   hey can  Chec  doo/ Whue,*abbnctihine,gonl lastE    :M  nstead o  lastF.sml    :M ey can  Chec  do   },pgs[0]c=== 9r&& }
   lastE    :M

wa  */
    Rts  doooooel = }
   lastE    :M  ing: funssss    }  funssss    } hey can  Chec   }aButtonsM&& Lte..isApt W(aButtons
     DEFAUL.    oooof.
}sed =s}  * f. reLas*Button(
  ing: funssss}e      toString: functio   }el)r toString: functioooootryi toString: functioooooooooel f. rea
  oString: fun        oooof.
}sed =s}    DER":P@feonfionfioooo} catch(oExcepase()i toString: functioooooooooo/ Ignoreig = thiss = s = oooo} ing: funssss    }  funssss    } hey can  Checreturnsh{
}sed  
    Rts}     var oConfig = thiss.cHels f(havnd Mtogn.smalizecbuttonpreferences. It"eithincreturns*ecutizepacka/ *eYUI Button(instastrrfacg(kckgient(
    :Ms   foun ,izepacka/ *eacg(kckp
  es back  cutHTMLE    :M referencegi  a(correepondnctiYUI Buttonizepacka/ *ereferencegii*  rkfoun eacg    epwidg* .Button does*  r existson (kckpage ig = this fig = thiss.cfhavnd M_g* Buttonizepacka/ *e@
fivat
ig = thiss.c@*r moreHTMLE    :Me buttonizepacka/ *e@returns{    epwidg* .Button|HTMLE    :Meig =   do .m = this.e_g* Button :,tarCase((button)i toString: funpexcButton(      epwidg* .Button  a  */
    Rtso/ I  we have an(HTML/buttonM."wiYUI Button(is on (kckpage**ig =   do hiso/ g* secutYUI Button(referencegi  availa}
  ig = this  oo   }ButtonM&& buttonM&& button.nodeN   a&& button.i )i toString: functiobutton(  Button.g* Button(button.i )i||obutton  ing: functio} hey can  Checreturnsbutton  ing: fun}     var oConfig = this.cSews
(kckf. reioo,*
  button(*rateispd  ignatem*as*
n (dd in, *via*ig = this.cecut"buttons"(rs.e C* Module
fvlem=y  Byodd in, , *
isphavnd Mis ig = this.caluledtwhue,*
  Dialogg i*mad
 vi  }
  ig = this.cfhavnd Mf. reDd in, Buttonig = this.cfreturns{Boolean} errh d  fo
}sed,ntdSet d    rig =   do.m = this.ef. reDd in, Button:,tarCase();

wa  */
    Rtspexcbutton(  }
   _g* Button(t
   dd in, HtmlButton),  oString: functioooooooooof.
}sed =shdSet Dtiooooooooooig = this  oo   }button)i toString: funr oCon oString: functioooooPlace (kckr. (soo,*
  "f.
}s"phavnd Minsid
 aotry/catch oString: functioooooblock  bspr
ent  IEieoNITthrownctiJavaScripa errors ifig =                 tcut
    :MgispdieMbledior hidden. g = this hisssss*m g =   do hiss = tryi toString: functiooooobutton.f. rea
  oString: fun        f{
}sed =s}    DER":P@feonfionfi} catch(oExcepase()i toString: functio}
 funssss    }
 funssss    returnsh{
}sed  
    Rts}     var oConfig = this.cBlurs . (son (buttons(d fined via*ecut"buttons"(ig = this.cas.e C* Module
fvlem=y ig = this.cfhavnd MblurButtonsig =   do.m = this.eblurButtons:,tarCase();

wa  */
    Rtsa  */
    RtspexcaButtonsM  }
   nfg g* dass ffva buttons")tizepacka/  dooooonButtonstizepacka/  dorrrroButtontizepacka/  dorrrrol    :Mtizepacka/  doooooi   ER":P@feonfi   }aButtonsM&& Lte..isApt W(aButtons
     DEFAUL.    oooonButtonsM  aButtons.length  zepacka/  doooooi  }nButtonsM>n0)r toString: functioooooi = }nButtonsM- 1
  oString: fun        do   oString: functiooooo  dooButton(  aButtons[i]  oString: functiooooo  do   }oButton   toString: functiooooooooo    oE    :M/= }
  ._g* Button(oButton.htmlButton
  oString: funssss               }oE    :M

wa  */
    Rts  doooooooooooooooooon oString: functioooooooooooooooooooooPlace (kckr. (soo,*
  "blur"phavnd Minsid
   oString: functioooooooooooooooooooooaotry/catchoblock  bspr
ent  IEieoNIT  oString: functiooooooooooooooooooooothrownctiJavaScripa errors if tcut
    :Mg oString: functioooooooooooooooooooooispdieMbledior hidden. g = this hisssssssssssssssssssss*m g =   do hiss = ooooooooooooooootryi toString: functiooooooooooooooooooooooE    :M blur()  
    Rtsssssssssssssssssssssssss} catch(oExcepase()i toString: functioooooooooooooooooooooo/ ignoreig = thiss = s = oooossssssssssss}ig = thiss = s = oooossssssss}   */
    Rtsssssssss    }   */
    Rtsssssssss} whi  (i--
  oString: funssss} 
    Rtsssss} 
    Rts}     var oConfig = this.cSews
(kckf. reioo,*
  fifs*Mbuttonpnreat
devia*ecut"buttons"ig = this.cas.e C* Module
fvlem=y ig = this.cfhavnd Mf. reFifs*Buttonig = this.cfreturns{Boolean} errhE d  fo
}sed.ntdSet d    rig =   do.m = this.ef. reFifs*Button:,tarCase();

wa zepacka/**
rpexcaButtonsM  }
   nfg g* dass ffva buttons")tizepacka/  dooooooButtontizepacka/  dorrrrol    :Mtizepacka/  dooooof.
}sed =shdSet D oString: fun   }aButtonsM&& Lte..isApt W(aButtons
     DEFAUL.    oooooButton(  aButtons[0]  oString: functio   }oButton   toString: functiooooooE    :M/= }
  ._g* Button(oButton.htmlButton
  oString: funssss       }oE    :M

wa  */
    Rts  doooooooooon oString: functioooooooooooooPlace (kckr. (soo,*
  "f.
}s"phavnd Minsid
 ao oString: functioooooooooooootry/catchoblock  bspr
ent  IEieoNITthrowncti oString: functioooooooooooooJavaScripa errors if tcut
    :MgispdieMbleditoString: functiooooooooo    or hidden. g = this hisssssssssssss*m g =   do hiss = ooooooootryi toString: functiooooooooooooooE    :M f. rea
  oString: fun        oooooooof.
}sed =s}    DER":P@feonfionfioooooooo} catch(oExcepase()i toString: functioooooooooooooo/ ignoreig = thiss = s = oooossss}   */
    Rtsssssssss} ing: funssss    }  funssss    } hey can  Checreturnsh{
}sed  
    Rts}     var oConfig = this.cSews
(kckf. reioo,*
  last(buttonpnreat
devia*ecut"buttons"(ig = this.cas.e C* Module
fvlem=y ig = this.cfhavnd Mf. reLas*Buttonig = this.cfreturns{Boolean} errhE d  fo
}sed.ntdSet d    rig =   do.m = this.ef. reLas*Button:,tarCase();

wa zepacka/**
rpexcaButtonsM  }
   nfg g* dass ffva buttons")tizepacka/  dooooonButtonstizepacka/  dorrrroButtontizepacka/  dorrrrol    :Mt  oString: functiof{
}sed =shdSet D oString: fun   }aButtonsM&& Lte..isApt W(aButtons
     DEFAUL.    oooonButtonsM  aButtons.length  zepacka/  doooooi  }nButtonsM>n0)r toString: functiooooooButton(  aButtons[}nButtonsM- 1
]   oString: functiooooo   }oButton   toString: functiooooooooooE    :M/= }
  ._g* Button(oButton.htmlButton
  oString: funssss           }oE    :M

wa  */
    Rts  doooooooooooooon oString: functioooooooooooooooooPlace (kckr. (soo,*
  "f.
}s"phavnd Minsid
 ao oString: functioooooooooooooooootry/catchoblock  bspr
ent  IEieoNITthrowncti oString: functioooooooooooooooooJavaScripa errors if tcut
    :MgispdieMbled oString: functioooooooooooooooooor hidden. g = this hisssssssssssssssss.m = this.e oString: functioooooooooooootryi toString: functiooooooooooooooooooE    :M f. rea
  oString: fun        oooooooooooof.
}sed =s}    DER":P@feonfionfioooooooooooo} catch(oExcepase()i toString: functioooooooooooooooooo/ Ignoreig = thiss = s = oooooooooooo}ig = thiss = s = oooossss}   */
    Rtsssssssss} ing: funssss    }  funssss    } hey can  Checreturnsh{
}sed  
    Rts}     var oConfig = this.cTn (dd in,  eent W succesWfacg(kck"posthavnd "(rs.e C* Module
fvlem=yig = this.cfhavnd Mns.e CPostMavnd ig = this.cf*r moreould be t    Tn (Crewsilent Wt    (usulueyc(kckprvlem=ycn   )ig = this.cf*r moreO> 0) []}tapgs Tn (Crewsilent Wapgu**
  . Facgig = this.cas.e C* Module success**,pgs[0]cwr (sequalc(kckneweye.hcl.wi ct rhrig = this.cfacg(kckpfvlem=y.ig = this.cf*r moreO> 0) }Wo   Tcutscvleto> 0)   Facgcs.e C* Module success**ig = this.ctnispor (susulueycequalc(kckownes. g = this.m = this.ers.e CPostMavnd :,tarCase();t   **,pgs,to> 

wa  */
    Rts   * reg    rF.sma
  oString:}     var oCo/ END BUILT-IN PROPERTYeEVENT HANDLERSs/m g =   do   var oConfig = this.cBuilt-in,tarCase()hookcfacgwritnctiakct idatdulefarCase()*rateor (sig = this.cbckrheck
difacga "}   " ct rhrpriacg(o assrsmit.cTnispfarCase(, aseig =   do*(imp    :Med byodd in, ,c
lways returns*errhE sogit e();

 bckig =   do*(overridd = if ct idatduleii* ecessary ig = this.cfhavnd Mct idate g = this.m = this.ect idate:,tarCase();

wa  */
    Rtsreturns}    DER":P@fe}     var oConfig = this.cExecutes assrsmit o  ecutDialog if ct idatduleig =   do*(is srccessful  Byodd in, ,*
  Dialogg i*hiddenig =   do*(af  rssrsmissse(,(but you can s* secut"hid af  rsrsmit"ig = this.cas.e C* Module
fvlem=yg(o tdSett  bspr
ent  on (Dialogig = this.ceoNITbenctihidden. g = this.cig = this.cfhavnd Msrsmit g = this*m = this.esrsmit:,tarCase();

wa  */
    Rts   }t
   ct idate(
     DEFAUL.    oooo   }t
   bet.reSrsmitlent .firea
)r toString: functiooooot
   doSrsmit;
  oString: functiooooo   * srsmitlent .firea
  oStr oString: functiooooo   }}
   nfg g* dass ffva hid af  rsrsmit"
     DEFAUL.        AUL.       * hid ;
  oString: functiooooo} oStr oString: functioooooreturns}    DER":P@feonfionfi}e      toString: functioooooreturnshdSet DER":P@feonfionfi}  funssss    }e      toString: functioreturnshdSet DER":P@feonfi} 
    Rts}     var oConfig = this.cExecutes (kckr.strl o  ecutDialog followed byoa hid .ig = this.cfhavnd Mrastrlig = this.m = this.erastrl:,tarCase();

wa  */
    Rts

   nastrllent .firea
  oString: fun   * hid ;
  oString:i  P@feonfi   var oConfig = this.cReturni*a JSON-compModble datassuluct*   repe = :Mnction (datasig = this.caury = eyecth
gCoul Co*(kckf.sm.ig = this.cfhavnd Mg* Data g = thisn freturns{    var A JSONe > 0) grepe= :Mnction (datasof *
  ig = this*,aury = kf.sm.ig = this.m = this.eg* Data:,tarCase();

wa zepacka/**
rpexcoF.sm =    * f.smtizepacka/  doooooaE    :Mstizepacka/  dooooonTotalE    :Mstizepacka/  dooooooDataEizepacka/  dooooosN   tizepacka/  dorrrrol    :Mtizepacka/  dooooonE    :Mstizepacka/  dooooosT   *izepacka/  dooooosTagN   tizepacka/  doooooaOpase(stizepacka/  dooooonOpase(stizepacka/  doooooaVt rhstizepacka/  dooooooOpase(tizepacka/  dooooooRadiotizepacka/  dooooooCheckboxtizepacka/  doooooct rhAttrtizepacka/  doooooitizepacka/  dooooon;oooo oStr oString: funtarCase() iF.sml    :M(p_oE    :M

wa  */
    Rts  dopexcsTag = p_oE    :M.tagN   .toUhcnrCas 
 ;hey can  Checccccreturns((sTag == "INPUT"i||osTag == "TEXTAREA"i||o  DEFAUL.        AUL.    sTag == "SELECT"
 && p_oE    :M.n   a==osN      oString: fun} hey can  Chec   }oF.sm    izepacka/  doooooaE    :Mso  oF.sm e    :M     */
           nTotalE    :Ms(  aE    :Ms.length    */
           oDatas= {i  tuplicate = t funtacg(ic= 0; i < nTotalE    :Ms; i++     DEFAUL.        AUL. N   a= aE    :Ms[i].n      oString: functioooooon oString: functioooooooooUsncti"uperg* E    :MsBy"g(o safeguard }sls eoNITJSo  DEFAUL.        AUL.    errors *ratee =n, ,eoNITgivnctiakf.smofie

 (acgsHAsof   DEFAUL.        AUL.    fie

s) eccks   en   eai*a 
Modv (havnd Mofiakf.smo  DEFAUL.        AUL.    (like "srsmit") acga DOMccolleCase()(srch*as*
n ("item"  DEFAUL.        AUL.    mavnd ). Originlueyc(r.wi accessnctifie

s via*ecutizepacka/                "n   dItem"(havnd Mofi
n ("
    :M"ccolleCase(,(but izepacka/                discvver da rateiteo{n't returnsaccolleCase()o  fie

s  oString: funssss         n Gecko. g = this hisssssssss.m a  */
    RtsssssssssoE    :M/= uperg* E    :MsBy( iF.sml    :M, "*", oF.sm     */
    RtsssssssssnE    :Ms(  oE    :M.length  hey can  Chec  doooooi  }nE    :Ms(>n0)r toString: functio  doooooi  }nE    :Ms(==o1   toString: functiooooooooo    oE    :M/= oE    :M[0]   oString: funnnnnnnnnnnnnnnnnsT   (  oE    :M.t    DER":P@feonfionfioooooooooooosTagN   (  oE    :M.tagN   .toUhcnrCas 
 ;hDER":P@feonfionfioooooooooooosorvch }sTagN   )i toString: functiooooooooooooooooocanlt"INPUT": oString: functioooooooooooooooooooooi  }sT   ( = "checkbox"   toString: functiooooooooo                oData[ N   ](  oE    :M.rheck
d  oString: fun        oooooooooooooooo}s        }sT   (!= "radio"   toString: functiooooooooo                oData[ N   ](  oE    :M.ct rh  oString: funssss    oooooooooooooooo} oString: funssss    oooooooooooooooobreak D oString: functiooooooooooooooooocanlt"TEXTAREA": oString: functiooooooooooooooooooooooData[ N   ](  oE    :M.ct rh  oString: funssss    oooooooooooooooobreak DER": oString: functiooooooooooooooooocanlt"SELECT": oString: functioooooooooooooooooooooaOpase(s(  oE    :M.opase(     */
           oooooooooooooooooooonOpase(s(  aOpase(s.length    */
           ooooooooooooooooooooaVt rhs = []  oStr   */
           ooooooooooooooooooootacg(nc= 0; n < nOpase(s; n++     DEFAUL.        AUL.....................oOpase((  aOpase(s[n]  oString: functiooooo  dooooooooooooooooo   }oOpase(.selcbsul     DEFAUL.        AUL.........................ct rhAttr(  oOpase(.attributes.ct rh  oString: funssss    ooooooooooooooooooooooooaVt rhs[aVt rhs.length] = (ct rhAttr(&& ct rhAttr.epecifie ) ?coOpase(.ct rhr:coOpase(.
 xt  ing: funssss    oooooooooooooooooooooooo} oString: funssss    oooooooooooooooo} oString: funssss    oooooooooooooooooData[ N   ](  aVt rhs  oString: funssss    oooooooooooooooobreak DER":ssss    oooooooooooooooo} oString:DER":ssss    oooooooooooo}e      toString: functiooooooooooooosT   (  oE    :M[0].t    DER":P@feonfionfioooooooooooosorvch }sT   )i toString: functiooooooooooooooooocanlt"radio": oString: functioooooooooooooooooooootacg(nc= 0; n < nE    :Ms; n++     DEFAUL.        AUL.....................oRadio(  oE    :M[n]  oString: functiooooo  dooooooooooooooooo   }oRadio.rheck
d     DEFAUL.        AUL.........................oData[ N   ](  oRadio.ct rh  oString: funssss    oooooooooooooooooooooooobreak DER":ssss    oooooooooooooooooooooooooooo} oString: funssss    oooooooooooooooo} oString: funssss    oooooooooooooooobreak DER":sssstoString: functiooooooooooooooooocanlt"checkbox": oString: functioooooooooooooooooooooaVt rhs = []  oStrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrtacg(nc= 0; n < nE    :Ms; n++     DEFAUL.        AUL.....................oCheckbox(  oE    :M[n]  oString: functiooooo  dooooooooooooooooo   }oCheckbox.rheck
d     DEFAUL.        AUL.........................aVt rhs[aVt rhs.length] = .oCheckbox.ct rh  oString: funssss    oooooooooooooooooooo} oString: funssss    oooooooooooooooo} oString: funssss    oooooooooooooooooData[ N   ](  aVt rhs  oString: funssss    oooooooooooooooobreak DER":ssss    oooooooooooooooo} oString:oooooooooooooooo} oString:oooooooooooo} ing: funssss    }  funssss    } hey can  ChecreturnsoData DER":P@fe}     var oConfig = this.cRemohas*
n (Panel e    :MieoNITtn (DOMc."wieews
. (schild 
    :Mssig = this.ctognull. g = this.cfhavnd Mdesuloyig = this.cf*r moreboolean} shallowPurge I  errhE onlyg(kckpay = k
    :M's(DOMc
ent  l    onrs aredpurged. I  tdSett acg  r provid d,
. (schildy = areddSeodpurged)o  DOMc
ent  l    onrs. ig = this.cNOTE:cTcutflagg i*a "shallowPurge"tflag, aseoppos da bswratemay b
 aom.re,intuiodv ("purgeChildy ="tflaga bsmai.
gCo*backwar
s compModbili=ycwrth b
haviacgpriacg(o 2.9.0.ig = this.m = this.edesuloy:,tarCase();shallowPurge

wa  */
    RtsremohaButtonlent Hsuccess.alue(    )  a  */
    Rts}
   _aButtons =  rty   oString: funpexcaF.smsM  }
   
    :M.g* E    :MsByTagN   ("f.sm")tizepacka/  dooooooF.sm D oString: fun   }aF.sms.length(>n0)r toString: functiooF.sm = aF.sms[0]   oString: funnnnn   }oF.sm     DEFAUL.            Eng f.purgel    :M(oF.sm     */
    Rtsssssssss   }oF.sm pay = /**
     DEFAUL.    
    RtsctiooF.sm pay = /**
.remohaChild}oF.sm     */
    Rtsssssssss} oString:oooooooooooot
   f.sm =  rty  ing: funssss    }  funssss    }  funssss    Dialog.sus f {
  .desuloy.alue(    , shallowPurge
 DER":P@fe}     var oConfig = this.cReturni*a suld b repe = :Maase()o  (kcko> 0)   g = this.cfhavnd Mtoould b g = this.cfreturns{ould be Tcutsuld b repe = :Maase()o  (kckDialogig = this.m = this.etoould b:,tarCase();

wa  */
    Rtsreturns"Dialog " + }
  .im  oString:} oStr oStr})  a}()   (tarCase();

wa zepaonfig = * Simp  Dialogg i*a simp  (imp    :Maase()o  Dialogg ratecan be"}sed (o ig = * srsmit a singlckct rh  FacmsMcan be"processul Co*3 ways -- via*an ig = * asynchronous ConneCase()utili=ycalue,*a simp  (f.sm POST acgGET, ig = * acgmanululy ig = .cfn   space     epwidg* ig = .cf {
  gSimp  Dialogig = .cf xten
s     epwidg* .Dialogig = .cfconsuluctorig = .cf*r moreould be el Tcut
    :MgID repe = :Mnction (Simp  Dialoggig = .c<em>OR</em>ig = .cf*r moreHTMLE    :Me el Tcut
    :Mgrepe = :Mnction (Simp  Dialogig = .cf*r more    var }slrCe Con Tcutas.e C* Moduleo> 0) gliterr acth
gConctiig = .ctcutas.e C* Module ratee();

 bcksHAsfacg(ki gSimp  Dialog. Seeiig = .cas.e C* Module {
};

 atdulefacgm.re,de
gCls.ig = .m = th    epwidg* .Simp  Dialogg=,tarCase();elb/}slrCe Con         oString:    epwidg* .Simp  Dialog.sus f {
  .rs.suluctor.alue(    , a  */
    Rtselb/}slrCe Con   oStr   */i  tuplipexcDom =     eputil.Domtizepacka/Simp  Dialogg=,    epwidg* .Simp  Dialog,      oString:onfig = this.cConstasMgrepe = :Mnction (Simp  Dialog'scas.e C* Module
fvlem=iesig =   do.cf*fvlem=ygDEFAULT_CONFIGig =   do.cf*fivat
ig = this.cffinluig = this.cft    O   vaig =   do.m = this.eDEFAULT_CONFIG    toString:a  */
    Rts"ICON": {t oString: functiokey:,"irs.",  oString: functioct rh:t"none",  oString: functiosuspe =sEng f: errh   oString: funi  P@feonfi   var oC Rts"TEXT": {t oString: functiokey:,"
 xt",  oString: functioct rh:t"",  oString: functiosuspe =sEng f: errh,  oString: functiosus f edes: ["irs."]  oString: funi P@feonfi   var oCi  tuplionfig = * ConstasMgfacg(kckstasdard networkcirs."facga blocknctiaCase(ig = .cf*fvlem=yg    epwidg* .Simp  Dialog.ICON_BLOCKig = .cfs atdcig = .cffinluig = .cft    ould b g = .m = thSimp  Dialog.ICON_BLOCKa= "blcknrs."  oStr   */onfig = * ConstasMgfacg(kckstasdard networkcirs."facgalasmig = .cf*fvlem=yg    epwidg* .Simp  Dialog.ICON_ALARMig = .cfs atdcig = .cffinluig = .cft    ould b g = .m = thSimp  Dialog.ICON_ALARMa= "alm=irs."  oStr   */onfig = * ConstasMgfacg(kckstasdard networkcirs."facghelpig = .cf*fvlem=yg    epwidg* .Simp  Dialog.ICON_HELPig = .cfs atdcig = .cffinluig = .cft    ould b g = .m = thSimp  Dialog.ICON_HELP a= "hlpirs."  oStr   */onfig = * ConstasMgfacg(kckstasdard networkcirs."facginfoig = .cf*fvlem=yg    epwidg* .Simp  Dialog.ICON_INFOig = .cfs atdcig = .cffinluig = .cft    ould b g = .m = thSimp  Dialog.ICON_INFO a= "infoirs."  oStr   */onfig = * ConstasMgfacg(kckstasdard networkcirs."facgwar(ig = .cf*fvlem=yg    epwidg* .Simp  Dialog.ICON_WARNig = .cfs atdcig = .cffinluig = .cft    ould b g = .m = thSimp  Dialog.ICON_WARN a= "war(nrs."  oStr   */onfig = * ConstasMgfacg(kckstasdard networkcirs."facga tipig = .cf*fvlem=yg    epwidg* .Simp  Dialog.ICON_TIPig = .cfs atdcig = .cffinluig = .cft    ould b g = .m = thSimp  Dialog.ICON_TIP  a= "tipnrs."     */onfig = * ConstasMgrepe = :Mnction (n   eo  (kckCSS  {
  g.hcl.wi oo,*
  
    :Mg oStr*pnreat
deby*
n ("irs."cas.e C* Module
fvlem=y ig = .cf*fvlem=yg    epwidg* .Simp  Dialog.ICON_CSS_CLASSNAMEig = .cfs atdcig = .cffinluig = .cft    ould b g = .m = thSimp  Dialog.ICON_CSS_CLASSNAMEa= "yui-nrs."  oStr   */onfig = * ConstasMgrepe = :Mnction (dd in, ,CSS  {
  g}sed facga Simp  Dialogig = .cf*fvlem=yg    epwidg* .Simp  Dialog.CSS_SIMPLEDIALOGig = .cfs atdcig = .cffinluig = .cft    ould b g = .m = thSimp  Dialog.CSS_SIMPLEDIALOGa= "yui-simp  -dialog"     */ = th    ep xten
(Simp  Dialog,     epwidg* .Dialog,        oString:onfig = this.cInitializes (kckr{
  'scas.e C* Mble 
fvlem=iesswhich can be"cute.
m ig = this.c}snction (Simp  Dialog'scCe Con o> 0) g(cfg)  g = this.cfhavnd MCon Dd in, Ce Conig =   do.m = this.eCon Dd in, Ce Con:,tarCase();

wa  */
    oString: funSimp  Dialog.sus f {
  .Con Dd in, Ce Con.alue(    )    */
    oString: fun// Add dialogcas.e C 
fvlem=iess/m g =   do   var oCing:onfig = thisthis.cSews
(kckinformaase(lu irs."facgon (Simp  Dialogig = g = this.cfas.e C irs.ig = this  = .cft    ould b g = this  = .cfdd in, ,"none" g = this  = .m g =   do his}
   nfg adddass ffvaDEFAULT_CONFIG.ICON.key,    DEFAUL.    
    succes:s}
   ns.e CIce(tizepacka/  doooooct rh:tDEFAULT_CONFIG.ICON.ct rh*izepacka/  dooooosuspe =sEng f: DEFAULT_CONFIG.ICON.suspe =sEng f oString: funi)    */
    oString: fun/nfig = thisthis.cSews
(kck
 xt"facgon (Simp  Dialog.cTn (
 xt" i* nslrsul(ie
oTtn (DOMc.s(HTML,c."wie();

 bckescap
deby*
n (imp    :Macgif comnctifoNITan  xter(lu souf e.ig = g = this.cfas.e C 
 xtig = this  = .cft    HTML g = this  = .cfdd in, ,"" g = this  = .m g =   do his}
   nfg adddass ffvaDEFAULT_CONFIG.TEXT.key,    oString: functio succes:s}
   ns.e CT xt,Wizepacka/  doooooct rh:tDEFAULT_CONFIG.TEXT.ct rh*  oString: functiosuspe =sEng f: DEFAULT_CONFIG.TEXT.suspe =sEng f,  oString: functiosus f edes: DEFAULT_CONFIG.TEXT.sus f edes  oString: funi)    */
    oString:i  P@feonfi   var oC oString:onfig = this.cTn (Simp  DialogginitializModulehavnd ,swhich  i*executed facgig = this.cSimp  Dialogg."wi. (so  itscsrs {
  es.cTnisphavnd M i*automaasclueycig = this.caluledtby*
n (rs.suluctor,c."wiieews
upc. (sDOMcreferences facgig = this.cpe -existnctimarkup,c."winreat
s requiredimarkupgif itgii*  rkig = this.calmeady pe = :M  g = this.cfhavnd MCon  g = this.cf*r moreould be el Tcut
    :MgID repe = :Mnction (Simp  Dialoggig = this.c<em>OR</em>ig = this.cf*r moreHTMLE    :Me el Tcut
    :Mgrepe = :Mnction (Simp  Dialogig = this.cf*r more    var }slrCe Con Tcutas.e C* Moduleo> 0) gliterr aig = this.cath
gConctitcutas.e C* Module ratee();

 bcksHAsfacg(ki gig = this.cSimp  Dialog. Seeias.e C* Module {
};

 atdulefacgm.re,de
gCls.ig = this.m = this.eCon :,tarCase();elb/}slrCe Con     oString: fun/n oString: functioNote)*rateoe,d{n't p
  itcut}sls as.e C inihine yHAsbecaonl wutizepacka/        onlygwasMgit 
xecuted once,"ateecutlowestcsrs {
   levrlig = thisthis.m  oString: funSimp  Dialog.sus f {
  .Con .alue(    , el/*b/}slrCe Con*/)    */
    oString: funt
   bet.reInitlent .fireaSimp  Dialog)    */
    oString: funuper.ddC{
  (}
   
    :M,hSimp  Dialog.CSS_SIMPLEDIALOG)    */
    oString: funt
   nfg queuedass ffva posthavnd ", "manulu")    */
    oString: fun   }}slrCe Con        oString: funt
   nfg .hclyCe Con}}slrCe Con, 
        */
    Rts} oString:DER":ssss    t
   bet.reRenderEng f.subscribe(tarCase();

wa  */
    Rts fun   }! t
   body)r toString: functiooooot
   sHABody(""
  oString: funssss}  funssss    },     , 
        */
   DER":ssss    t
   initlent .fireaSimp  Dialog)    */
    oString:i  P@feonfi   var oConfig = this.cPrepares (kckSimp  Dialog'scie
er(lu FORMeo> 0) ,inreatnction     on  ig = this.cii*  rkaury = eyepe = :M,c."wi.ddnctitcutct rhrhidden fie

  g = this.cfhavnd Mreg    rF.smig = this.m = this.ereg    rF.sm:,tarCase();

wa  */
    RtsSimp  Dialog.sus f {
  .reg    rF.sm.alue(    )  a  */
    Rtspexc {
 =    * f.sm.ownesD{
};

 tizepacka/  doooooinput =  {
.nreat
l    :M("input"
  hey can  Checinput.
    =t"hidden"  oString: funinput.n   a= }
  .im  oString: funinput.ct rhr=t""  a  */
    Rts}
   f.sm..hcendChild}input
  oString:}     var oCo/ BEGIN BUILT-IN PROPERTYeEVENT HANDLERSs/m g =   do   var oConfig = this.cFirem*whue,*
  "irs."c
fvlem=ycd "sHA.ig = this.cfhavnd Mrs.e CIconig = this.cf*r moreould be t    Tn (Crewsilent Wt    (usulueyc(kckprvlem=ycn   )ig = this.cf*r moreO> 0) []}tapgs Tn (Crewsilent Wapgu**
  . Facgrs.e C* Moduleig = this.c success**,pgs[0]cwr (sequalc(kckneweye.hcl.wi ct rhrfacg(kckpfvlem=y.ig = this.cf*r moreO> 0) }Wo   Tcutscvleto> 0)   Facgcs.e C* Module success**ig = this.ctnispor (susulueycequalc(kckownes. g = this.m = this.ers.e CIcon:,tarCase();t   *,pgs,o> 

wa  */
   a  */
    RtspexcsIconM  ,pgs[0]tizepacka/  dooooooBody =    * body*izepacka/  dooooosCSSC{
   = Simp  Dialog.ICON_CSS_CLASSNAME,
				aE    :Mstizepacka/  dooooooIce(tizepacka/  dooooooIce(Pay =     */
    oString: fun   }sIconM&&csIconM!= "none"    izepacka/  doooooaE    :Mso  uperg* E    :MsByC{
  N   (sCSSC{
  , "*" ,ooBody
  h				   }aE    :Ms.lengthc=== 1   th					oIconM  ,E    :Ms[0]  oString: functiooooooIce(Pay = (  oIce( pay = /**
   oString: functiooooo   }oIce(Pay =     izepacka/  dorrrroooooooooIce(Pay = .remohaChild}oIce()  hey can  Chec  doooooAUL.oIconM   rty   oString: funnnnnnnnn}th				}tha  */
    Rts fun   }sIcon indexOf("."  == -1    izepacka/  dorrrroooooIconM   {
};

 .nreat
l    :M("span")  ing: funssss    oooooIcon. {
  N   a= (sCSSC{
   + " " + sIce()  ing: funssss    oooooIcon.innerHTML/= "&#160;"  a  */
    Rtsnnnn}e      they can  Chec  dooooooIconM   {
};

 .nreat
l    :M("img")  ing: funssss    oooooIcon.sr
 = (}
   imageRo rk+ sIce()  ing: funssss    oooooIcon. {
  N   a= sCSSC{
    a  */
    Rtsnnnn}a  */
    Rtsnnnn  oString: funnnnn   }oIce()r toString: functio ing: funssss    oooooBody. nslrsBet.re}oIce(,ooBody fifs*Child)  ing: funssss    a  */
    Rtsnnnn}a

    Rtsnnnn}a

    Rts}     var oConfig = this.cFirem*whue,*
  "
 xt"c
fvlem=ycd "sHA.ig = this.cfhavnd Mrs.e CT xtig = this.cf*r moreould be t    Tn (Crewsilent Wt    (usulueyc(kckprvlem=ycn   )ig = this.cf*r moreO> 0) []}tapgs Tn (Crewsilent Wapgu**
  . Facgrs.e C* Moduleig = this.c success**,pgs[0]cwr (sequalc(kckneweye.hcl.wi ct rhrfacg(kckpfvlem=y.ig = this.cf*r moreO> 0) }Wo   Tcutscvleto> 0)   Facgcs.e C* Module success**ig = this.ctnispor (susulueycequalc(kckownes. g = this.m = this.ers.e CT xt:,tarCase();t   *,pgs,o> 

wa  */
       pexc
 xt"  ,pgs[0]  oString: funi  }} xt        oString: funt
   sHABody(} xt   ing: funssss    t
   nfg refirelent ("irs."     */
    Rts} oString:i  P@feonfi   var oCo/ END BUILT-IN PROPERTYeEVENT HANDLERSs/m g =   do   var oConfig = this.cReturni*a suld b repe = :Maase()o  (kcko> 0)   g = this.cfhavnd Mtoould b g = this.cfreturns{ould be Tcutsuld b repe = :Maase()o  (kckSimp  Dialogig = this.m = this.etoould b:,tarCase();

wa  */
    Rtsreturns"Simp  Dialogg" + }
  .im  oString:}    var oConfig = this.c<p>ig = this.cSews
(kckSimp  Dialog'scbodycath
nt WtoTtn (HTML/epecifie . ig = this.cI    cbodycisppe = :M,con  or (sb
 automaasclueycnreat
d. ig = this.cAn emp=ycsuld b can be"passwi oo,*
  havnd Mtogclearitcutas.t :Msoo  (kckbody  g = this.c</p>ig =   do.c<p><sulong>NOTE:</sulong>(Simp  Dialoggprovid s
(kck<a href="#rs.e C_
 xt">
 xt</a>ig =   do.c."wi<a href="#rs.e C_irs.">irs.</a>cas.e C* Module
fvlem=iesg(o s* secutas.t :Msig =   do.co  it'scbodyc
    :Ms n*accordastrrwrth ecutUIpd  ign facga Simp  Dialog }aleig =   do*(iconM."wimessag (
 xt). Clued b sHABody on (kckSimp  Dialog or (s  r enforce (ki gig = this.cUIpd  ign rs.sulai.
M."wior (sreplace (kck :Mnrutas.t :Msoo  (kckSimp  Dialog body  ig = this.cItee();

 onlygbe"}sed i  you wish ecutreplace (kckdd in, ,icon/
 xt"bodycsuluct*   ig =   do.co  akSimp  Dialog orth yourkown crewsiimarkup.</p>ig =   do.c g = this.cfhavnd MsHABodyig = this.cf*r moreHTML}"bodyCont :M TcutHTML/}sed (o s* secutbody  ig = this.cAssacconentience,"nontHTMLE    :M o> 0) sMcan dSeodbe"passwi ie
oTig = this.ctneehavnd ,s."wior (sbe"treat
deascsuld bs**wrth ecutbodycinnerHTMLig = this.cs* seo,*
 irodd in, ,*oould b(imp    :Maase(s. g = this.cig = this.c<p>NOTE:cMarkupgpasswi ie
oT*
isphavnd Mis addwi oo,*
  DOMc.s(HTML,c."wie();

 bckescap
deby*
n (imp    :Macgif comnctifoNITan  xter(lu souf e.</p>ig =   do.c g = this.c<em>OR</em>ig = this.cf*r moreHTMLE    :Me bodyCont :M TcutHTMLE    :M (o adm*as*
n (fifs*M."wionlygchild o  (kckbodyc
    :M. g = this.c<em>OR</em>ig = this.cf*r moreD{
};

 Frag  :Me bodyCont :M Tcut {
};

 ifoag  :Maig = this.cath
gConcti
    :Msswhich aredtodbe"addwi oo,*
  bodyig = this.m = th})  a}()   (tarCase();

wa zepaonfig = * Cth
gCourEff0) gencapsulat
s animModule ransiase(s(*rateared
xecuted whue,
  do.c." Overlaycd "shown or hidden. g = .cfn   space     epwidg* ig = .cf {
  gCth
gCourEff0) ig = .cf onsuluctorig = .cf*r more    epwidg* .Overlay}(overlaycTcutOverlayc*rate*
  animModuleig = .ce();

 bck
  ociat
dewrthig = .cf*r moreO> 0) }WattrIncTcuto> 0) gliterr arepe = :Mnction (animModuleig = .capgu**
  dtodbe"}sed facgon (animMoe-in, ransiase(.cTn (apgu**
  dfacgig = .ctnispliterr aare: attributes(o> 0) ,isee     eputil.Animdfacgdescripase(), ig = * d* Modul(Number),M."wimevnd (i.e. Easnct.easeIn).ig = .cf*r moreO> 0) }WattrOutcTcuto> 0) gliterr arepe = :Mnction (animModuleig = .capgu**
  dtodbe"}sed facgon (animMoe-outc ransiase(.cTn (apgu**
  dfacggig = .ctnispliterr aare: attributes(o> 0) ,isee     eputil.Animdfacgdescripase(), ig = * d* Modul(Number),M."wimevnd (i.e. Easnct.easeIn).ig = .cf*r moreHTMLE    :Me tapg* E    :M Opase(lu.cTn (
apg* c
    :Ms*rateeig = .ce();

 bck
nimMoed d* nction ( ransiase(.cDd in,  dtodoverlay.
    :M. g = .cf*r more {
  } Opase(lu.cTn (animModule {
  gtodinstastiat
.cDd in,  dtod g = .c    eputil.Anim. Othincopase( tdnclude     eputil.Moase(.
this.m = th    epwidg* .Cth
gCourEff0) g=,tarCase();overlay,WattrIn,WattrOut, tapg* E    :M,(animC{
      izepacka/   }!animC{
                  animC{
   =     eputil.Anim  oString:}    var oConfig = this.cTcutoverlayc*ok
nimMoeig = this.cf*rvlem=ycoverlayig = this.cft        epwidg* .Overlayig = this.m = this.et
  .overlayc=coverlay  oStr   */
   onfig = this.cTcutanimModuleattributesc*okonl whue,*ransiase(d b(ie
oTviewig = this.cf*rvlem=ycattrInig = this.cft    O   vaig =   do.m = this.et
  .attrInc=cattrIn  oStr   */
   onfig = this.cTcutanimModuleattributesc*okonl whue,*ransiase(d b(outco  viewig = this.cf*rvlem=ycattrOutig = this.cft    O   vaig =   do.m = this.et
  .attrOut = attrOut  oStr   */
   onfig = this.cTcut
apg* c
    :Ms*o bck
nimMoedig = this.cf*rvlem=yctapg* E    :Mig = this.cft    HTMLE    :Mig =   do.m = this.et
  .tapg* E    :M = tapg* E    :M ||doverlay.
    :M  oStr   */
   onfig = this.cTcutanimModule {
  gtodonl facganimModction (overlayig = this.cf*rvlem=ycanimC{
  ig = this.cft    c{
  ig = this.m = this.et
  .animC{
   = animC{
    oStri  tuplipexcDom =     eputil.Domtizepacka/Crewsilent W=     eputil.Crewsilent tizepacka/Cth
gCourEff0) g=,    epwidg* .Cth
gCourEff0)      */onfig = * Acpe -as.e C* ed Cth
gCourEff0) ginstastrr ratecan be"}sed facgfadnctiig = .can overlaycinM."wiout. g = .cfhavnd MFADEig = .cfs atdcig = .cf*r more    epwidg* .Overlay}(overlaycTcutOverlayco> 0) g*ok
nimMoeig = .cf*r moreNumber} d*  Tcut * Moduleofion (animModulig = .cfreturns{    epwidg* .Cth
gCourEff0) } Tcutas.e C* ed Cth
gCourEff0) go   vaig = .m = thCth
gCourEff0) .FADEg=,tarCase();overlay,W *     izepacka/pexcEasnctW=     eputil.Easncttizepacka/  dofin    toString:::::::::attributes: {opacity:{foNI:0t  b:1}}tizepacka/  dooooo * Modul:o * tizepacka/  dooooomavnd :,Easnct.easeIn   */
    Rts}tizepacka/  dofout =  toString:::::::::attributes: {opacity:{ b:0}}tizepacka/  dooooo * Modul:o * tizepacka/  dooooomavnd :,Easnct.easeOutig = this Rts}tizepacka/  dofad
 =knewhCth
gCourEff0) ;overlay,Wfin,ofout,doverlay.
    :M)  hey can  fad
. succeUpirslayStap g=,tarCase(;

wa  */
    Rtspexcupirslay =    * overlay.upirslay  oString: funi  }upirslay &&h    ep nv.ua.i
     DEFAUL.    
   pexchasFil  rs = (upirslay.fil  rs &&hupirslay.fil  rs.length(>n0)  ing: funssss    if(hasFil  rs)r toString: functiooooouper.ddC{
  (overlay.
    :M, "yui-eff0) -fad
"
  oString: funssss}  funssss    } oString:}  hey can  fad
. succeUpirslayComp  teg=,tarCase(;

wa  */
    Rtspexcupirslay =    * overlay.upirslay  oString: funi  }upirslay &&h    ep nv.ua.i
     DEFAUL.    
   uperremohaC{
  (overlay.
    :M, "yui-eff0) -fad
"
  oString: fun} oString:}  hey can  fad
. succeStap AnimMoeInc=ctarCase();t   **,pgs,to> 

wa  */
    Rtso>  overlay._fadnctInc=c}    Da  */
    Rtsuper.ddC{
  (o>  overlay.
    :M, "hid -selcbs"
  hey can  Checi  }!o>  overlay.upirslay)r toString: functioo>  overlay.nfg refirelent ("upirslay"
  oString: fun} a  */
    Rtso>   succeUpirslayStap 
 ;hDER":P@feonfio>  overlay._setupeVi  }ili=y(
        */
    RtsupersetStyle(o>  overlay.
    :M, "opacity",n0)  ing: fun}  hey can  fad
. succeComp  teAnimMoeInc=ctarCase();t   *,pgs,o> 

wa  */
       o>  overlay._fadnctInc=chdSet Dtiooooooooooig = this  oouperremohaC{
  (o>  overlay.
    :M, "hid -selcbs"
  hey can  Checi  }o>  overlay.
    :M.style.fil  r)r toString: functioo>  overlay.
    :M.style.fil  r =  rty  ing: funssss} a  */
    Rtso>   succeUpirslayComp  te
 ;hDER":P@feonfio>  overlay.nfg refirelent ("if mo
"
  oString: funo>  animMoeInComp  telent .firea
  oString:}  hey can  fad
. succeStap AnimMoeOut = tarCase();t   **,pgs,to> 

wa  */
    Rtso>  overlay._fadnctOut = }    DER":P@feonfiuper.ddC{
  (o>  overlay.
    :M, "hid -selcbs"
    */
    Rtso>   succeUpirslayStap 
 ;hoString:}  hey can  fad
. succeComp  teAnimMoeOut =  tarCase();t   **,pgs,to> 

wa  */
    Rtso>  overlay._fadnctOut = hdSet DtioooooooooouperremohaC{
  (o>  overlay.
    :M, "hid -selcbs"
  hey can  Checi  }o>  overlay.
    :M.style.fil  r)r toString: functioo>  overlay.
    :M.style.fil  r =  rty  ing: funssss}   */
    Rtso>  overlay._setupeVi  }ili=y(hdSet     */
    RtsupersetStyle(o>  overlay.
    :M, "opacity",n1 ;hDER":P@feonfio>   succeUpirslayComp  te
 ;hDER":P@feonfio>  overlay.nfg refirelent ("if mo
"
  oString: funo>  animMoeOutComp  telent .firea
  oString:}  hey can  fad
.inita
  oString:returnshdde  oStri  oStr oStr oStronfig = * Acpe -as.e C* ed Cth
gCourEff0) ginstastrr ratecan be"}sed facgslidnctian ig = * overlaycinM."wiout. g = .cfhavnd MSLIDEig = .cfs atdcig = .cf*r more    epwidg* .Overlay}(overlaycTcutOverlayco> 0) g*ok
nimMoeig = .cf*r moreNumber} d*  Tcut * Moduleofion (animModulig = .cfreturns{    epwidg* .Cth
gCourEff0) } Tcutas.e C* ed Cth
gCourEff0) go   vaig = .m = thCth
gCourEff0) .SLIDEg=,tarCase();overlay,W *     oString:pexcEasnctW=     eputil.Easnctti oString: funxc=coverlay nfg g* dass ffva x"  ||duperg* X(overlay.
    :M)tizepacka/  doyc=coverlay nfg g* dass ffva y"  ||duperg* Y(overlay.
    :M)tizepacka/  docli :MWidthc=duperg* Cli :MWidth()tizepacka/  dooffsetWidthc=doverlay.
    :M.offsetWidthti oString: funsin       oString: functioattributes: { points: {  b: [x, y] }s}tizepacka/  dooooo * Modul:o * tizepacka/  dooooomavnd :,Easnct.easeIn  oString: funi   oString: funsout =  toString:::::::::attributes: { points: {  b: [(cli :MWidthc+ 25), y] }s}tizepacka/  dooooo * Modul:o * tizepacka/  dooooomavnd :,Easnct.easeOut izepacka/    i   oString: funslid
 =knewhCth
gCourEff0) ;overlay,Wsin,osout,doverlay.
    :M,     eputil.Moase( ;hDER":P@feslid
. succeStap AnimMoeInc=ctarCase();t   *,pgs,o> 

wa  */
       o>  overlay.
    :M.style.left = ((-25) -ooffsetWidth) + "px"  oString: funo>  overlay.
    :M.style.top a= y + "px"  oString:};hDER":P@feslid
. succeTweenAnimMoeInc=ctarCase();t   **,pgs,to> 

wa  */
   a  */
    Rtspexcposo  uperg* XY(o>  overlay.
    :M)tizepacka/  doooooaury = X = pos[0]tizepacka/  doooooaury = Y = pos[1]  oString:hey can  Checi  }uperg* Style(o>  overlay.
    :M, "vi  }ili=y"  == izepacka/  dooooo"hidden" &&haury = X < x    izepacka/  dorrrro>  overlay._setupeVi  }ili=y(
       ing: funssss}   */
   DER":P@feonfio>  overlay.nfg s* dass ffva xy",n[aury = X,oaury = Y], 
        */
    Rtso>  overlay.nfg refirelent ("if mo
"
  oString:}  oString:hey can  slid
. succeComp  teAnimMoeInc=ctarCase();t   **,pgs,to> 

wa  */
    Rtso>  overlay.nfg s* dass ffva xy",n[x, y], 
        */
    Rtso>  stap X = x    */
    Rtso>  stap Y = y    */
    Rtso>  overlay.nfg refirelent ("if mo
"
  oString: Rtso>  animMoeInComp  telent .firea
  oString:}  hey can  slid
. succeStap AnimMoeOut = tarCase();t   **,pgs,to> 

wa  */a  */
    Rtspexcvwo  uperg* ViewporMWidth()tizepacka/  doooooposo  uperg* XY(o>  overlay.
    :M)tizepacka/  doooooyso = pos[1]  oStr oString: Rtso>  animOut.attributes.points.to = [(vwo+ 25), yso]  oString:}  oString:hey can  slid
. succeTweenAnimMoeOut = tarCase();t   **,pgs,to> 

wa  */a  */
    Rtspexcposo  uperg* XY(o>  overlay.
    :M)tizepacka/  doooooxto = pos[0]tizepacka/  doooooyto = pos[1]  oStroStr oString: Rtso>  overlay.nfg s* dass ffva xy",n[xto,oyto], 
        */
    Rtso>  overlay.nfg refirelent ("if mo
"
  oString:}  oString:hey can  slid
. succeComp  teAnimMoeOut = tarCase();t   **,pgs,to> 

wa  */
    Rtso>  overlay._setupeVi  }ili=y(hdSet   a  */
    Rtso>  overlay.nfg s* dass ffva xy",n[x, y]
  oString: funo>  animMoeOutComp  telent .firea
  oString:}  hey can  slid
.inita
  oString:returnsslid
  oStri  tupliCth
gCourEff0) .proto
    =t  izepacka/onfig = this.cInitializes (kckanimModule {
  
s andc
ent s  g = this.cfhavnd MCon  g = this.m = this.eCon :,tarCase();    izepacka/  dot
   bet.reAnimMoeInlent W= t
   nreat
lent ("bet.reAnimMoeIn"
  oString: Rtst
   bet.reAnimMoeInlent . ignat*   =/Crewsilent .LIST Dtiooooooooooig = this  oot
   bet.reAnimMoeOutlent W= t
   nreat
lent ("bet.reAnimMoeOus"
    */
    Rtst
   bet.reAnimMoeOutlent . ignat*   =/Crewsilent .LIST Dtioooooo   */
    Rtst
   animMoeInComp  telent W= t
   nreat
lent ("animMoeInComp  te"
    */
    Rtst
   animMoeInComp  telent . ignat*   =/Crewsilent .LIST Dtioooooo   */
    Rtst
   animMoeOutComp  telent W= t
   nreat
lent ("animMoeOutComp  te"
    */
    Rtst
   animMoeOutComp  telent . ignat*   =/Crewsilent .LIST D   */
    Rtst
   animInc=cnewht
  .animC{
  (izepacka/  dooooot
  .tapg* E    :M,  oString: functiot
  .attrIn.attributes,  oString: functiot
  .attrIn. * Modul,  oString: functiot
  .attrIn.mavnd ) D   */
    Rtst
   animIn.onStap .subscribe(   * hsucceStap AnimMoeIn,     
    */
    Rtst
   animIn.onTween.subscribe(   * hsucceTweenAnimMoeIn,     
    */
    Rtst
   animIn.onComp  te.subscribe(   * hsucceComp  teAnimMoeIn,    )    */
    oString: funt
   animOut = newht
  .animC{
  (izepacka/  dooooot
  .tapg* E    :M,  oString: functiot
  .attrOut.attributes,  oString: functiot
  .attrOut. * Modul,  oString: functiot
  .attrOut.mavnd ) D   */
    Rtst
   animOut.onStap .subscribe(   * hsucceStap AnimMoeOut, t   
    */
    Rtst
   animOut.onTween.subscribe(   * hsucceTweenAnimMoeOut, t   
    */
    Rtst
   animOut.onComp  te.subscribe(   * hsucceComp  teAnimMoeOut, t   
   oString:}     var oConfig = this.cTriggers
(kckin-animModul  g = this.cfhavnd ManimMoeIn g = this.m = this.eanimMoeIn:,tarCase();

wa  */
    Rts

   _ewspAnim (}
   lastF mo
OnStop
  oString: Rtst
   bet.reAnimMoeInlent .firea
  oString: fun   * animIn.animMoea
  oString:}     var oConfig = this.cTriggers
(kckout-animModul  g = this.cfhavnd ManimMoeOutig = this.m = this.eanimMoeOut:,tarCase();

wa  */
    Rts

   _ewspAnim (}
   lastF mo
OnStop
  oString: Rtst
   bet.reAnimMoeOutlent .firea
  oString: fun   * animOut.animMoea
  oString:}  oString:   var oConfig = thiss.cFlaga bsdefinl whuthincAnimde();

 jump oo,*
  last f mo
,ig = thiss.cwhue,animMoeIn acganimMoeOut d "stoppe
  g = this fig = thiss.cf*rvlem=yclastF mo
OnStopig = thiss.cfdd in, ,*   ig = thiss.cf
    booleanig = thiss.m = this.elastF mo
OnStop : errh,    var oConfig = thiss.cStopscboth animInc."wi.nimOut instastrs, if ile
fvge =s  g = this fig = thiss.cfhavnd M_ewspAnim ig = thiss.cf*r moreboolean} finish I  errhE animModuleor (sjump oo,finlu f mo
.ig = thiss.cf*rvtcbsulig = thiss.m = this.e_ewspAnim  :,tarCase((finish

wa  */
    Rts   }t
   .nimOut &&n   * animOut.isAnimMoeda
)r toString: functio   * animOut.ewsp(finish
  ing: funssss} a  */
    Rts   }t
   .nimIn &&n   * animIn.isAnimMoeda
)r toString: functio   * animIn.ewsp(finish
  ing: funssss}  funssss}     var oConfig = this.cTn (dd in,  onStap W succesWfacg(kckin-animModul  g = this.cfhavnd MhsucceStap AnimMoeIn g = this.cf*r moreould be t    Tn (Crewsilent Wt    g = this.cf*r moreO> 0) []}tapgs Tn (Crewsilent Wapgu**
   g = this.cf*r moreO> 0) }Wo   Tcutscvleto> 0) ig = this.m = this.ehsucceStap AnimMoeIn:,tarCase();t   **,pgs,to> 

ws}     var oConfig = this.cTn (dd in,  onTweenW succesWfacg(kckin-animModul  g = this.cfhavnd MhsucceTweenAnimMoeIn g = this.cf*r moreould be t    Tn (Crewsilent Wt    g = this.cf*r moreO> 0) []}tapgs Tn (Crewsilent Wapgu**
   g = this.cf*r moreO> 0) }Wo   Tcutscvleto> 0) ig = this.m = this.ehsucceTweenAnimMoeIn:,tarCase();t   **,pgs,to> 

ws}     var oConfig = this.cTn (dd in,  onComp  teg succesWfacg(kckin-animModul  g = this.cfhavnd MhsucceComp  teAnimMoeIn g = this.cf*r moreould be t    Tn (Crewsilent Wt    g = this.cf*r moreO> 0) []}tapgs Tn (Crewsilent Wapgu**
   g = this.cf*r moreO> 0) }Wo   Tcutscvleto> 0) ig = this.m = this.ehsucceComp  teAnimMoeIn:,tarCase();t   **,pgs,to> 

ws}     var oConfig = this.cTn (dd in,  onStap W succesWfacg(kckout-animModul  g = this.cfhavnd MhsucceStap AnimMoeOut g = this.cf*r moreould be t    Tn (Crewsilent Wt    g = this.cf*r moreO> 0) []}tapgs Tn (Crewsilent Wapgu**
   g = this.cf*r moreO> 0) }Wo   Tcutscvleto> 0) ig = this.m = this.ehsucceStap AnimMoeOut:,tarCase();t   **,pgs,to> 

ws}     var oConfig = this.cTn (dd in,  onTweenW succesWfacg(kckout-animModul  g = this.cfhavnd MhsucceTweenAnimMoeOut g = this.cf*r moreould be t    Tn (Crewsilent Wt    g = this.cf*r moreO> 0) []}tapgs Tn (Crewsilent Wapgu**
   g = this.cf*r moreO> 0) }Wo   Tcutscvleto> 0) ig = this.m = this.ehsucceTweenAnimMoeOut:,tarCase();t   **,pgs,to> 

ws}     var oConfig = this.cTn (dd in,  onComp  teg succesWfacg(kckout-animModul  g = this.cfhavnd MhsucceComp  teAnimMoeOut g = this.cf*r moreould be t    Tn (Crewsilent Wt    g = this.cf*r moreO> 0) []}tapgs Tn (Crewsilent Wapgu**
   g = this.cf*r moreO> 0) }Wo   Tcutscvleto> 0) ig = this.m = this.ehsucceComp  teAnimMoeOut:,tarCase();t   **,pgs,to> 

ws}  = this.e   var oConfig = this.cReturni*a suld b repe = :Maase()o  (kcko> 0)   g = this.cfhavnd Mtoould b g = this.cfreturns{ould be Tcutsuld b repe = :Maase()o  (kckCth
gCourEff0) ig = this.m = this.etoould b:,tarCase();

wa  */
    Rtspexcoutput = "Cth
gCourEff0) "  oString: funi  }t
   overlay)r toString: functiooutput += " [" + }
  .overlay.toould b() + "]"  ing: funssss}  funssssssssreturnsoutput  oString:} ing:}   oStr    eplang.aug  :MProto(Cth
gCourEff0) ,     eputil.lent Provid r)  a})a
      epreg    r("cth
gCour",     epwidg* .Modul **{versdul:o"2.9.0",(build:o"2800"})  