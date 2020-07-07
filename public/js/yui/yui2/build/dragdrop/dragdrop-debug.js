/*
Copyright (c) 2011, Yahoo! Inc. All rights reserved.
Code licensed under the BSD License:
http://developer.yahoo.com/yui/license.html
version: 2.9.0
*/
/**
 * The drag and drop utility provides a framework for building drag and drop
 * applications.  In addition to enabling drag and drop for specific elements,
 * the drag and drop elements are tracked by the manager class, and the
 * interactions between the various elements are tracked during the drag and
 * the implementing code is notified about these important moments.
 * @module dragdrop
 * @title Drag and Drop
 * @requires yahoo,dom,event
 * @namespace YAHOO.util
 */

// Only load the library once.  Rewriting the manager class would orphan 
// existing drag and drop instances.
if (!YAHOO.util.DragDropMgr) {

/**
 * DragDropMgr is a singleton that tracks the element interaction for 
 * all DragDrop items in the window.  Generally, you will not call 
 * this class directly, but it does have helper methods that could 
 * be useful in your DragDrop implementations.
 * @class DragDropMgr
 * @static
 */
YAHOO.util.DragDropMgr = function() {

    var Event = YAHOO.util.Event,
        Dom = YAHOO.util.Dom;

    return {
        /**
        * This property is used to turn on global use of the shim element on all DragDrop instances, defaults to false for backcompat. (Use: YAHOO.util.DDM.useShim = true)
        * @property useShim
        * @type Boolean
        * @static
        */
        useShim: false,
        /**
        * This property is used to determine if the shim is active over the screen, default false.
        * @private
        * @property _shimActive
        * @type Boolean
        * @static
        */
        _shimActive: false,
        /**
        * This property is used when useShim is set on a DragDrop object to store the current state of DDM.useShim so it can be reset when a drag operation is done.
        * @private
        * @property _shimState
        * @type Boolean
        * @static
        */
        _shimState: false,
        /**
        * This property is used when useShim is set to true, it will set the opacity on the shim to .5 for debugging. Use: (YAHOO.util.DDM._debugShim = true;)
        * @private
        * @property _debugShim
        * @type Boolean
        * @static
        */
        _debugShim: false,
        /**
        * This method will create a shim element (giving it the id of yui-ddm-shim), it also attaches the mousemove and mouseup listeners to it and attaches a scroll listener on the window
        * @private
        * @method _sizeShim
        * @static
        */
        _createShim: function() {
            YAHOO.log('Creating Shim Element', 'info', 'DragDropMgr');
            var s = document.createElement('div');
            s.id = 'yui-ddm-shim';
            if (document.body.firstChild) {
                document.body.insertBefore(s, document.body.firstChild);
            } else {
                document.body.appendChild(s);
            }
            s.style.display = 'none';
            s.style.backgroundColor = 'red';
            s.style.position = 'absolute';
            s.style.zIndex = '99999';
            Dom.setStyle(s, 'opacity', '0');
            this._shim = s;
            Event.on(s, "mouseup",   this.handleMouseUp, this, true);
            Event.on(s, "mousemove", this.handleMouseMove, this, true);
            Event.on(window, 'scroll', this._sizeShim, this, true);
        },
        /**
        * This method will size the shim, called from activate and on window scroll event
        * @private
        * @method _sizeShim
        * @static
        */
        _sizeShim: function() {
            if (this._shimActive) {
                YAHOO.log('Sizing Shim', 'info', 'DragDropMgr');
                var s = this._shim;
                s.style.height = Dom.getDocumentHeight() + 'px';
                s.style.width = Dom.getDocumentWidth() + 'px';
                s.style.top = '0';
                s.style.left = '0';
            }
        },
        /**
        * This method will create the shim element if needed, then show the shim element, size the element and set the _shimActive property to true
        * @private
        * @method _activateShim
        * @static
        */
        _activateShim: function() {
            if (this.useShim) {
                YAHOO.log('Activating Shim', 'info', 'DragDropMgr');
                if (!this._shim) {
                    this._createShim();
                }
                this._shimActive = true;
                var s = this._shim,
                    o = '0';
                if (this._debugShim) {
                    o = '.5';
                }
                Dom.setStyle(s, 'opacity', o);
                this._sizeShim();
                s.style.display = 'block';
            }
        },
        /**
        * This method will hide the shim element and set the _shimActive property to false
        * @private
        * @method _deactivateShim
        * @static
        */
        _deactivateShim: function() {
            YAHOO.log('Deactivating Shim', 'info', 'DragDropMgr');
            this._shim.style.display = 'none';
            this._shimActive = false;
        },
        /**
        * The HTML element created to use as a shim over the document to track mouse movements
        * @private
        * @property _shim
        * @type HTMLElement
        * @static
        */
        _shim: null,
        /**
         * Two dimensional Array of registered DragDrop objects.  The first 
         * dimension is the DragDrop item group, the second the DragDrop 
         * object.
         * @property ids
         * @type {string: string}
         * @private
         * @static
         */
        ids: {},

        /**
         * Array of element ids defined as drag handles.  Used to determine 
         * if the element that generated the mousedown event is actually the 
         * handle and not the html element itself.
         * @property handleIds
         * @type {string: string}
         * @private
         * @static
         */
        handleIds: {},

        /**
         * the DragDrop object that is currently being dragged
         * @property dragCurrent
         * @type DragDrop
         * @private
         * @static
         **/
        dragCurrent: null,

        /**
         * the DragDrop object(s) that are being hovered over
         * @property dragOvers
         * @type Array
         * @private
         * @static
         */
        dragOvers: {},

        /**
         * the X distance between the cursor and the object being dragged
         * @property deltaX
         * @type int
         * @private
         * @static
         */
        deltaX: 0,

        /**
         * the Y distance between the cursor and the object being dragged
         * @property deltaY
         * @type int
         * @private
         * @static
         */
        deltaY: 0,

        /**
         * Flag to determine if we should prevent the default behavior of the
         * events we define. By default this is true, but this can be set to 
         * false if you need the default behavior (not recommended)
         * @property preventDefault
         * @type boolean
         * @static
         */
        preventDefault: true,

        /**
         * Flag to determine if we should stop the propagation of the events 
         * we generate. This is true by default but you may want to set it to
         * false if the html element contains other features that require the
         * mouse click.
         * @property stopPropagation
         * @type boolean
         * @static
         */
        stopPropagation: true,

        /**
         * Internal flag that is set to true when drag and drop has been
         * initialized
         * @property initialized
         * @private
         * @static
         */
        initialized: false,

        /**
         * All drag and drop can be disabled.
         * @property locked
         * @private
         * @static
         */
        locked: false,

        /**
         * Provides additional information about the the current set of
         * interactions.  Can be accessed from the event handlers. It
         * contains the following properties:
         *
         *       out:       onDragOut interactions
         *       enter:     onDragEnter interactions
         *       over:      onDragOver interactions
         *       drop:      onDragDrop interactions
         *       point:     The location of the cursor
         *       draggedRegion: The location of dragged element at the time
         *                      of the interaction
         *       sourceRegion: The location of the source elemtn at the time
         *                     of the interaction
         *       validDrop: boolean
         * @property interactionInfo
         * @type object
         * @static
         */
        interactionInfo: null,

        /**
         * Called the first time an element is registered.
         * @method init
         * @private
         * @static
         */
        init: function() {
            this.initialized = true;
        },

        /**
         * In point mode, drag and drop interaction is defined by the 
         * location of the cursor during the drag/drop
         * @property POINT
         * @type int
         * @static
         * @final
         */
        POINT: 0,

        /**
         * In intersect mode, drag and drop interaction is defined by the 
         * cursor position or the amount of overlap of two or more drag and 
         * drop objects.
         * @property INTERSECT
         * @type int
         * @static
         * @final
         */
        INTERSECT: 1,

        /**
         * In intersect mode, drag and drop interaction is defined only by the 
         * overlap of two or more drag and drop objects.
         * @property STRICT_INTERSECT
         * @type int
         * @static
         * @final
         */
        STRICT_INTERSECT: 2,

        /**
         * The current drag and drop mode.  Default: POINT
         * @property mode
         * @type int
         * @static
         */
        mode: 0,

        /**
         * Runs method on all drag and drop objects
         * @method _execOnAll
         * @private
         * @static
         */
        _execOnAll: function(sMethod, args) {
            for (var i in this.ids) {
                for (var j in this.ids[i]) {
                    var oDD = this.ids[i][j];
                    if (! this.isTypeOfDD(oDD)) {
                        continue;
                    }
                    oDD[sMethod].apply(oDD, args);
                }
            }
        },

        /**
         * Drag and drop initialization.  Sets up the global event handlers
         * @method _onLoad
         * @private
         * @static
         */
        _onLoad: function() {

            this.init();

            YAHOO.log("DragDropMgr onload", "info", "DragDropMgr");
            Event.on(document, "mouseup",   this.handleMouseUp, this, true);
            Event.on(document, "mousemove", this.handleMouseMove, this, true);
            Event.on(window,   "unload",    this._onUnload, this, true);
            Event.on(window,   "resize",    this._onResize, this, true);
            // Event.on(window,   "mouseout",    this._test);

        },

        /**
         * Reset constraints on all drag and drop objs
         * @method _onResize
         * @private
         * @static
         */
        _onResize: function(e) {
            YAHOO.log("window resize", "info", "DragDropMgr");
            this._execOnAll("resetConstraints", []);
        },

        /**
         * Lock all drag and drop functionality
         * @method lock
         * @static
         */
        lock: function() { this.locked = true; },

        /**
         * Unlock all drag and drop functionality
         * @method unlock
         * @static
         */
        unlock: function() { this.locked = false; },

        /**
         * Is drag and drop locked?
         * @method isLocked
         * @return {boolean} True if drag and drop is locked, false otherwise.
         * @static
         */
        isLocked: function() { return this.locked; },

        /**
         * Location cache that is set for all drag drop objects when a drag is
         * initiated, cleared when the drag is finished.
         * @property locationCache
         * @private
         * @static
         */
        locationCache: {},

        /**
         * Set useCache to false if you want to force object the lookup of each
         * drag and drop linked element constantly during a drag.
         * @property useCache
         * @type boolean
         * @static
         */
        useCache: true,

        /**
         * The number of pixels that the mouse needs to move after the 
         * mousedown before the drag is initiated.  Default=3;
         * @property clickPixelThresh
         * @type int
         * @static
         */
        clickPixelThresh: 3,

        /**
         * The number of milliseconds after the mousedown event to initiate the
         * drag if we don't get a mouseup event. Default=1000
         * @property clickTimeThresh
         * @type int
         * @static
         */
        clickTimeThresh: 1000,

        /**
         * Flag that indicates that either the drag pixel threshold or the 
         * mousdown time threshold has been met
         * @property dragThreshMet
         * @type boolean
         * @private
         * @static
         */
        dragThreshMet: false,

        /**
         * Timeout used for the click time threshold
         * @property clickTimeout
         * @type Object
         * @private
         * @static
         */
        clickTimeout: null,

        /**
         * The X position of the mousedown event stored for later use when a 
         * drag threshold is met.
         * @property startX
         * @type int
         * @private
         * @static
         */
        startX: 0,

        /**
         * The Y position of the mousedown event stored for later use when a 
         * drag threshold is met.
         * @property startY
         * @type int
         * @private
         * @static
         */
        startY: 0,

        /**
         * Flag to determine if the drag event was fired from the click timeout and
         * not the mouse move threshold.
         * @property fromTimeout
         * @type boolean
         * @private
         * @static
         */
        fromTimeout: false,

        /**
         * Each DragDrop instance must be registered with the DragDropMgr.  
         * This is executed in DragDrop.init()
         * @method regDragDrop
         * @param {DragDrop} oDD the DragDrop object to register
         * @param {String} sGroup the name of the group this element belongs to
         * @static
         */
        regDragDrop: function(oDD, sGroup) {
            if (!this.initialized) { this.init(); }
            
            if (!this.ids[sGroup]) {
                this.ids[sGroup] = {};
            }
            this.ids[sGroup][oDD.id] = oDD;
        },

        /**
         * Removes the supplied dd instance from the supplied group. Executed
         * by DragDrop.removeFromGroup, so don't call this function directly.
         * @method removeDDFromGroup
         * @private
         * @static
         */
        removeDDFromGroup: function(oDD, sGroup) {
            if (!this.ids[sGroup]) {
                this.ids[sGroup] = {};
            }

            var obj = this.ids[sGroup];
            if (obj && obj[oDD.id]) {
                delete obj[oDD.id];
            }
        },

        /**
         * Unregisters a drag and drop item.  This is executed in 
         * DragDrop.unreg, use that method instead of calling this directly.
         * @method _remove
         * @private
         * @static
         */
        _remove: function(oDD) {
            for (var g in oDD.groups) {
                if (g) {
                    var item = this.ids[g];
                    if (item && item[oDD.id]) {
                        delete item[oDD.id];
                    }
                }
                
            }
            delete this.handleIds[oDD.id];
        },

        /**
         * Each DragDrop handle element must be registered.  This is done
         * automatically when executing DragDrop.setHandleElId()
         * @method regHandle
         * @param {String} sDDId the DragDrop id this element is a handle for
         * @param {String} sHandleId the id of the element that is the drag 
         * handle
         * @static
         */
        regHandle: function(sDDId, sHandleId) {
            if (!this.handleIds[sDDId]) {
                this.handleIds[sDDId] = {};
            }
            this.handleIds[sDDId][sHandleId] = sHandleId;
        },

        /**
         * Utility function to determine if a given element has been 
         * registered as a drag drop item.
         * @method isDragDrop
         * @param {String} id the element id to check
         * @return {boolean} true if this element is a DragDrop item, 
         * false otherwise
         * @static
         */
        isDragDrop: function(id) {
            return ( this.getDDById(id) ) ? true : false;
        },

        /**
         * Returns the drag and drop instances that are in all groups the
         * passed in instance belongs to.
         * @method getRelated
         * @param {DragDrop} p_oDD the obj to get related data for
         * @param {boolean} bTargetsOnly if true, only return targetable objs
         * @return {DragDrop[]} the related instances
         * @static
         */
        getRelated: function(p_oDD, bTargetsOnly) {
            var oDDs = [];
            for (var i in p_oDD.groups) {
                for (var j in this.ids[i]) {
                    var dd = this.ids[i][j];
                    if (! this.isTypeOfDD(dd)) {
                        continue;
                    }
                    if (!bTargetsOnly || dd.isTarget) {
                        oDDs[oDDs.length] = dd;
                    }
                }
            }

            return oDDs;
        },

        /**
         * Returns true if the specified dd target is a legal target for 
         * the specifice drag obj
         * @method isLegalTarget
         * @param {DragDrop} the drag obj
         * @param {DragDrop} the target
         * @return {boolean} true if the target is a legal target for the 
         * dd obj
         * @static
         */
        isLegalTarget: function (oDD, oTargetDD) {
            var targets = this.getRelated(oDD, true);
            for (var i=0, len=targets.length;i<len;++i) {
                if (targets[i].id == oTargetDD.id) {
                    return true;
                }
            }

            return false;
        },

        /**
         * My goal is to be able to transparently determine if an object is
         * typeof DragDrop, and the exact subclass of DragDrop.  typeof 
         * returns "object", oDD.constructor.toString() always returns
         * "DragDrop" and not the name of the subclass.  So for now it just
         * evaluates a well-known variable in DragDrop.
         * @method isTypeOfDD
         * @param {Object} the object to evaluate
         * @return {boolean} true if typeof oDD = DragDrop
         * @static
         */
        isTypeOfDD: function (oDD) {
            return (oDD && oDD.__ygDragDrop);
        },

        /**
         * Utility function to determine if a given element has been 
         * registered as a drag drop handle for the given Drag Drop object.
         * @method isHandle
         * @param {String} id the element id to check
         * @return {boolean} true if this element is a DragDrop handle, false 
         * otherwise
         * @static
         */
        isHandle: function(sDDId, sHandleId) {
            return ( this.handleIds[sDDId] && 
                            this.handleIds[sDDId][sHandleId] );
        },

        /**
         * Returns the DragDrop instance for a given id
         * @method getDDById
         * @param {String} id the id of the DragDrop object
         * @return {DragDrop} the drag drop object, null if it is not found
         * @static
         */
        getDDById: function(id) {
            for (var i in this.ids) {
                if (this.ids[i][id]) {
                    return this.ids[i][id];
                }
            }
            return null;
        },

        /**
         * Fired after a registered DragDrop object gets the mousedown event.
         * Sets up the events required to track the object being dragged
         * @method handleMouseDown
         * @param {Event} e the event
         * @param oDD the DragDrop object being dragged
         * @private
         * @static
         */
        handleMouseDown: function(e, oDD) {
            //this._activateShim();

            this.currentTarget = YAHOO.util.Event.getTarget(e);

            this.dragCurrent = oDD;

            var el = oDD.getEl();

            // track start position
            this.startX = YAHOO.util.Event.getPageX(e);
            this.startY = YAHOO.util.Event.getPageY(e);

            this.deltaX = this.startX - el.offsetLeft;
            this.deltaY = this.startY - el.offsetTop;

            this.dragThreshMet = false;

            this.clickTimeout = setTimeout( 
                    function() { 
                        var DDM = YAHOO.util.DDM;
                        DDM.startDrag(DDM.startX, DDM.startY);
                        DDM.fromTimeout = true;
                    }, 
                    this.clickTimeThresh );
        },

        /**
         * Fired when either the drag pixel threshold or the mousedown hold 
         * time threshold has been met.
         * @method startDrag
         * @param x {int} the X position of the original mousedown
         * @param y {int} the Y position of the original mousedown
         * @static
         */
        startDrag: function(x, y) {
            if (this.dragCurrent && this.dragCurrent.useShim) {
                this._shimState = this.useShim;
                this.useShim = true;
            }
            this._activateShim();
            YAHOO.log("firing drag start events", "info", "DragDropMgr");
            clearTimeout(this.clickTimeout);
            var dc = this.dragCurrent;
            if (dc && dc.events.b4StartDrag) {
                dc.b4StartDrag(x, y);
                dc.fireEvent('b4StartDragEvent', { x: x, y: y });
            }
            if (dc && dc.events.startDrag) {
                dc.startDrag(x, y);
                dc.fireEvent('startDragEvent', { x: x, y: y });
            }
            this.dragThreshMet = true;
        },

        /**
         * Internal function to handle the mouseup event.  Will be invoked 
         * from the context of the document.
         * @method handleMouseUp
         * @param {Event} e the event
         * @private
         * @static
         */
        handleMouseUp: function(e) {
            if (this.dragCurrent) {
                clearTimeout(this.clickTimeout);

                if (this.dragThreshMet) {
                    YAHOO.log("mouseup detected - completing drag", "info", "DragDropMgr");
                    if (this.fromTimeout) {
                        YAHOO.log('fromTimeout is true (mouse didn\'t move), call handleMouseMove so we can get the dragOver event', 'info', 'DragDropMgr');
                        this.fromTimeout = false;
                        this.handleMouseMove(e);
                    }
                    this.fromTimeout = false;
                    this.fireEvents(e, true);
                } else {
                    YAHOO.log("drag threshold not met", "info", "DragDropMgr");
                }

                this.stopDrag(e);

                this.stopEvent(e);
            }
        },

        /**
         * Utility to stop event propagation and event default, if these 
         * features are turned on.
         * @method stopEvent
         * @param {Event} e the event as returned by this.getEvent()
         * @static
         */
        stopEvent: function(e) {
            if (this.stopPropagation) {
                YAHOO.util.Event.stopPropagation(e);
            }

            if (this.preventDefault) {
                YAHOO.util.Event.preventDefault(e);
            }
        },

        /** 
         * Ends the current drag, cleans up the state, and fires the endDrag
         * and mouseUp events.  Called internally when a mouseup is detected
         * during the drag.  Can be fired manually during the drag by passing
         * either another event (such as the mousemove event received in onDrag)
         * or a fake event with pageX and pageY defined (so that endDrag and
         * onMouseUp have usable position data.).  Alternatively, pass true
         * for the silent parameter so that the endDrag and onMouseUp events
         * are skipped (so no event data is needed.)
         *
         * @method stopDrag
         * @param {Event} e the mouseup event, another event (or a fake event) 
         *                  with pageX and pageY defined, or nothing if the 
         *                  silent parameter is true
         * @param {boolean} silent skips the enddrag and mouseup events if true
         * @static
         */
        stopDrag: function(e, silent) {
            // YAHOO.log("mouseup - removing event handlers");
            var dc = this.dragCurrent;
            // Fire the drag end event for the item that was dragged
            if (dc && !silent) {
                if (this.dragThreshMet) {
                    YAHOO.log("firing endDrag events", "info", "DragDropMgr");
                    if (dc.events.b4EndDrag) {
                        dc.b4EndDrag(e);
                        dc.fireEvent('b4EndDragEvent', { e: e });
                    }
                    if (dc.events.endDrag) {
                        dc.endDrag(e);
                        dc.fireEvent('endDragEvent', { e: e });
                    }
                }
                if (dc.events.mouseUp) {
                    YAHOO.log("firing dragdrop onMouseUp event", "info", "DragDropMgr");
                    dc.onMouseUp(e);
                    dc.fireEvent('mouseUpEvent', { e: e });
                }
            }

            if (this._shimActive) {
                this._deactivateShim();
                if (this.dragCurrent && this.dragCurrent.useShim) {
                    this.useShim = this._shimState;
                    this._shimState = false;
                }
            }

            this.dragCurrent = null;
            this.dragOvers = {};
        },

        /** 
         * Internal function to handle the mousemove event.  Will be invoked 
         * from the context of the html element.
         *
         * @TODO figure out what we can do about mouse events lost when the 
         * user drags objects beyond the window boundary.  Currently we can 
         * detect this in internet explorer by verifying that the mouse is 
         * down during the mousemove event.  Firefox doesn't give us the 
         * button state on the mousemove event.
         * @method handleMouseMove
         * @param {Event} e the event
         * @private
         * @static
         */
        handleMouseMove: function(e) {
            //YAHOO.log("handlemousemove");

            var dc = this.dragCurrent;
            if (dc) {
                // YAHOO.log("no current drag obj");

                // var button = e.which || e.button;
                // YAHOO.log("which: " + e.which + ", button: "+ e.button);

                // check for IE < 9 mouseup outside of page boundary
                if (YAHOO.env.ua.ie && (YAHOO.env.ua.ie < 9) && !e.button) {
                    YAHOO.log("button failure", "info", "DragDropMgr");
                    this.stopEvent(e);
                    return this.handleMouseUp(e);
                } else {
                    if (e.clientX < 0 || e.clientY < 0) {
                        //This will stop the element from leaving the viewport in FF, Opera & Safari
                        //Not turned on yet
                        //YAHOO.log("Either clientX or clientY is negative, stop the event.", "info", "DragDropMgr");
                        //this.stopEvent(e);
                        //return false;
                    }
                }

                if (!this.dragThreshMet) {
                    var diffX = Math.abs(this.startX - YAHOO.util.Event.getPageX(e));
                    var diffY = Math.abs(this.startY - YAHOO.util.Event.getPageY(e));
                    // YAHOO.log("diffX: " + diffX + "diffY: " + diffY);
                    if (diffX > this.clickPixelThresh || 
                                diffY > this.clickPixelThresh) {
                        YAHOO.log("pixel threshold met", "info", "DragDropMgr");
                        this.startDrag(this.startX, this.startY);
                    }
                }

                if (this.dragThreshMet) {
                    if (dc && dc.events.b4Drag) {
                        dc.b4Drag(e);
                        dc.fireEvent('b4DragEvent', { e: e});
                    }
                    if (dc && dc.events.drag) {
                        dc.onDrag(e);
                        dc.fireEvent('dragEvent', { e: e});
                    }
                    if (dc) {
                        this.fireEvents(e, false);
                    }
                }

                this.stopEvent(e);
            }
        },
        
        /**
         * Iterates over all of the DragDrop elements to find ones we are 
         * hovering over or dropping on
         * @method fireEvents
         * @param {Event} e the event
         * @param {boolean} isDrop is this a drop op or a mouseover op?
         * @private
         * @static
         */
        fireEvents: function(e, isDrop) {
            var dc = this.dragCurrent;

            // If the user did the mouse up outside of the window, we could 
            // get here even though we have ended the drag.
            // If the config option dragOnly is true, bail out and don't fire the events
            if (!dc || dc.isLocked() || dc.dragOnly) {
                return;
            }

            var x = YAHOO.util.Event.getPageX(e),
                y = YAHOO.util.Event.getPageY(e),
                pt = new YAHOO.util.Point(x,y),
                pos = dc.getTargetCoord(pt.x, pt.y),
                el = dc.getDragEl(),
                events = ['out', 'over', 'drop', 'enter'],
                curRegion = new YAHOO.util.Region( pos.y, 
                                               pos.x + el.offsetWidth,
                                               pos.y + el.offsetHeight, 
                                               pos.x ),
            
                oldOvers = [], // cache the previous dragOver array
                inGroupsObj  = {},
                b4Results = {},
                inGroups  = [],
                data = {
                    outEvts: [],
                    overEvts: [],
                    dropEvts: [],
                    enterEvts: []
                };


            // Check to see if the object(s) we were hovering over is no longer 
            // being hovered over so we can fire the onDragOut event
            for (var i in this.dragOvers) {

                var ddo = this.dragOvers[i];

                if (! this.isTypeOfDD(ddo)) {
                    continue;
                }
                if (! this.isOverTarget(pt, ddo, this.mode, curRegion)) {
                    data.outEvts.push( ddo );
                }

                oldOvers[i] = true;
                delete this.dragOvers[i];
            }

            for (var sGroup in dc.groups) {
                // YAHOO.log("Processing group " + sGroup);
                
                if ("string" != typeof sGroup) {
                    continue;
                }

                for (i in this.ids[sGroup]) {
                    var oDD = this.ids[sGroup][i];
                    if (! this.isTypeOfDD(oDD)) {
                        continue;
                    }

                    if (oDD.isTarget && !oDD.isLocked() && oDD != dc) {
                        if (this.isOverTarget(pt, oDD, this.mode, curRegion)) {
                            inGroupsObj[sGroup] = true;
                            // look for drop interactions
                            if (isDrop) {
                                data.dropEvts.push( oDD );
                            // look for drag enter and drag over interactions
                            } else {

                                // initial drag over: dragEnter fires
                                if (!oldOvers[oDD.id]) {
                                    data.enterEvts.push( oDD );
                                // subsequent drag overs: dragOver fires
                                } else {
                                    data.overEvts.push( oDD );
                                }

                                this.dragOvers[oDD.id] = oDD;
                            }
                        }
                    }
                }
            }

            this.interactionInfo = {
                out:       data.outEvts,
                enter:     data.enterEvts,
                over:      data.overEvts,
                drop:      data.dropEvts,
                point:     pt,
                draggedRegion:    curRegion,
                sourceRegion: this.locationCache[dc.id],
                validDrop: isDrop
            };

            
            for (var inG in inGroupsObj) {
                inGroups.push(inG);
            }

            // notify about a drop that did not find a target
            if (isDrop && !data.dropEvts.length) {
                YAHOO.log(dc.id + " dropped, but not on a target", "info", "DragDropMgr");
                this.interactionInfo.validDrop = false;
                if (dc.events.invalidDrop) {
                    dc.onInvalidDrop(e);
                    dc.fireEvent('invalidDropEvent', { e: e });
                }
            }
            for (i = 0; i < events.length; i++) {
                var tmp = null;
                if (data[events[i] + 'Evts']) {
                    tmp = data[events[i] + 'Evts'];
                }
                if (tmp && tmp.length) {
                    var type = events[i].charAt(0).toUpperCase() + events[i].substr(1),
                        ev = 'onDrag' + type,
                        b4 = 'b4Drag' + type,
                        cev = 'drag' + type + 'Event',
                        check = 'drag' + type;
                    if (this.mode) {
                        YAHOO.log(dc.id + ' ' + ev + ': ' + tmp, "info", "DragDropMgr");
                        if (dc.events[b4]) {
                            dc[b4](e, tmp, inGroups);
                            b4Results[ev] = dc.fireEvent(b4 + 'Event', { event: e, info: tmp, group: inGroups });
                            
                        }
                        if (dc.events[check] && (b4Results[ev] !== false)) {
                            dc[ev](e, tmp, inGroups);
                            dc.fireEvent(cev, { event: e, info: tmp, group: inGroups });
                        }
                    } else {
                        for (var b = 0, len = tmp.length; b < len; ++b) {
                            YAHOO.log(dc.id + ' ' + ev + ': ' + tmp[b].id, "info", "DragDropMgr");
                            if (dc.events[b4]) {
                                dc[b4](e, tmp[b].id, inGroups[0]);
                                b4Results[ev] = dc.fireEvent(b4 + 'Event', { event: e, info: tmp[b].id, group: inGroups[0] });
                            }
                            if (dc.events[check] && (b4Results[ev] !== false)) {
                                dc[ev](e, tmp[b].id, inGroups[0]);
                                dc.fireEvent(cev, { event: e, info: tmp[b].id, group: inGroups[0] });
                            }
                        }
                    }
                }
            }
        },

        /**
         * Helper function for getting the best match from the list of drag 
         * and drop objects returned by the drag and drop events when we are 
         * in INTERSECT mode.  It returns either the first object that the 
         * cursor is over, or the object that has the greatest overlap with 
         * the dragged element.
         * @method getBestMatch
         * @param  {DragDrop[]} dds The array of drag and drop objects 
         * targeted
         * @return {DragDrop}       The best single match
         * @static
         */
        getBestMatch: function(dds) {
            var winner = null;

            var len = dds.length;

            if (len == 1) {
                winner = dds[0];
            } else {
                // Loop through the targeted items
                for (var i=0; i<len; ++i) {
                    var dd = dds[i];
                    // If the cursor is over the object, it wins.  If the 
                    // cursor is over multiple matches, the first one we come
                    // to wins.
                    if (this.mode == this.INTERSECT && dd.cursorIsOver) {
                        winner = dd;
                        break;
                    // Otherwise the object with the most overlap wins
                    } else {
                        if (!winner || !winner.overlap || (dd.overlap &&
                            winner.overlap.getArea() < dd.overlap.getArea())) {
                            winner = dd;
                        }
                    }
                }
            }

            return winner;
        },

        /**
         * Refreshes the cache of the top-left and bottom-right points of the 
         * drag and drop objects in the specified group(s).  This is in the
         * format that is stored in the drag and drop instance, so typical 
         * usage is:
         * <code>
         * YAHOO.util.DragDropMgr.refreshCache(ddinstance.groups);
         * </code>
         * Alternatively:
         * <code>
         * YAHOO.util.DragDropMgr.refreshCache({group1:true, group2:true});
         * </code>
         * @TODO this really should be an indexed array.  Alternatively this
         * method could accept both.
         * @method refreshCache
         * @param {Object} groups an associative array of groups to refresh
         * @static
         */
        refreshCache: function(groups) {
            YAHOO.log("refreshing element location cache", "info", "DragDropMgr");

            // refresh everything if group array is not provided
            var g = groups || this.ids;

            for (var sGroup in g) {
                if ("string" != typeof sGroup) {
                    continue;
                }
                for (var i in this.ids[sGroup]) {
                    var oDD = this.ids[sGroup][i];

                    if (this.isTypeOfDD(oDD)) {
                        var loc = this.getLocation(oDD);
                        if (loc) {
                            this.locationCache[oDD.id] = loc;
                        } else {
                            delete this.locationCache[oDD.id];
YAHOO.log("Could not get the loc for " + oDD.id, "warn", "DragDropMgr");
                        }
                    }
                }
            }
        },

        /**
         * This checks to make sure an element exists and is in the DOM.  The
         * main purpose is to handle cases where innerHTML is used to remove
         * drag and drop objects from the DOM.  IE provides an 'unspecified
         * error' when trying to access the offsetParent of such an element
         * @method verifyEl
         * @param {HTMLElement} el the element to check
         * @return {boolean} true if the element looks usable
         * @static
         */
        verifyEl: function(el) {
            try {
                if (el) {
                    var parent = el.offsetParent;
                    if (parent) {
                        return true;
                    }
                }
            } catch(e) {
                YAHOO.log("detected problem with an element", "info", "DragDropMgr");
            }

            return false;
        },
        
        /**
         * Returns a Region object containing the drag and drop element's position
         * and size, including the padding configured for it
         * @method getLocation
         * @param {DragDrop} oDD the drag and drop object to get the 
         *                       location for
         * @return {YAHOO.util.Region} a Region object representing the total area
         *                             the element occupies, including any padding
         *                             the instance is configured for.
         * @static
         */
        getLocation: function(oDD) {
            if (! this.isTypeOfDD(oDD)) {
                YAHOO.log(oDD + " is not a DD obj", "info", "DragDropMgr");
                return null;
            }

            var el = oDD.getEl(), pos, x1, x2, y1, y2, t, r, b, l;

            try {
                pos= YAHOO.util.Dom.getXY(el);
            } catch (e) { }

            if (!pos) {
                YAHOO.log("getXY failed", "info", "DragDropMgr");
                return null;
            }

            x1 = pos[0];
            x2 = x1 + el.offsetWidth;
            y1 = pos[1];
            y2 = y1 + el.offsetHeight;

            t = y1 - oDD.padding[0];
            r = x2 + oDD.padding[1];
            b = y2 + oDD.padding[2];
            l = x1 - oDD.padding[3];

            return new YAHOO.util.Region( t, r, b, l );
        },

        /**
         * Checks the cursor location to see if it over the target
         * @method isOverTarget
         * @param {YAHOO.util.Point} pt The point to evaluate
         * @param {DragDrop} oTarget the DragDrop object we are inspecting
         * @param {boolean} intersect true if we are in intersect mode
         * @param {YAHOO.util.Region} pre-cached location of the dragged element
         * @return {boolean} true if the mouse is over the target
         * @private
         * @static
         */
        isOverTarget: function(pt, oTarget, intersect, curRegion) {
            // use cache if available
            var loc = this.locationCache[oTarget.id];
            if (!loc || !this.useCache) {
                YAHOO.log("cache not populated", "info", "DragDropMgr");
                loc = this.getLocation(oTarget);
                this.locationCache[oTarget.id] = loc;

                YAHOO.log("cache: " + loc, "info", "DragDropMgr");
            }

            if (!loc) {
                YAHOO.log("could not get the location of the element", "info", "DragDropMgr");
                  turn false;
              
            // nHOO.log("cocat" + loc,  ", bupt" + lop;
              arget.idrsorIsOver)  loc;
.ntainins(t Th

            // reagDrop ob usedg this di a drsany toeck for IEe instial drusedown
         *  // inithis dises e are inne
  IE n POINmode. if theserag obj")as thno        *  // inntaiints",,e are inne
  Iherwise thwneeds o evaluate
 he 
               refron obe target is thcupies, o determine if the drag ed element
         *     reerlap.gsith anit                         var lo = this.dragCurrent;
            if (dcc || dc(!tersect mo !datc.nstraintsXo !datc.nstraintsY){

                va// (oDDrget.idrsorIsOver) {
                       nHOO.log("coer th+ oDDrget th", bu+ loc,  ", bu+ lop;"warn", 
                  //                }
turn oDDrget.idrsorIsOver)               
            //Drget.iderlap &&null;

          *     reG the lorrent drcation of the draggelement
 ,his in the
         *  // looktion of the druse events  ls the ofltaY hat ispresentins        *  // loere ine original mousedown
 as pped on yee element", Wil         *  // loeds o evnstrid clistraints and isc
 kdi a ll-k.            if (!lorRegion) {
            //////r par = dc.getTargetCoord(pt.x, pt.y),
                  r el = oD.getDragEl(),
                  rRegion = new YAHOO.util.Region( pos.y, 
                                               pooooos.x + el.offsetWidth,
                                               pooooos.x + el.offsetHeight, 
                                               pooooos.x + 
            }

            ifr oDDrlap &&nurRegion =.tersect moc) {
            if (leDrlap &{
            //////Drget.iderlap &&nuerlap &                  turn fa(tersect m? true : faDrget.idrsorIsOver)               
lse {
                  turn false;
              
         

        /**
         * Chunloadvent handlers"        * @method is_onUnload        * @private
         * @static
         */
        is_onUnloadfunction(e, ism {
              is.useeg, Al),
          

        /**
         * Cheans up the stag and drop events whd onMects f         * @method reeeg, Al)        * @private
         * @static
         */
        iseeg, Al):unction() { 
             HOO.log("couegisters ll o "info", "DragDropMgr");

            // (this.dragCurrent) {
                clis.stopDrag(e)
                this.loagCurrent = null;
            th
            this.in_ecutOnAl),ouegis "D[]

            // rr (var i in this.idement",che) {
                YA//lete this.loement",che) ];
              //              this.stement",che)  {};
             this.inid= {};
        },

        /** 
        * @sAache of thDOMlements t        * @priverty frement",che)         * @private
         * @static
         */
 @dreseced inements toe int geched lonow        */
        isement",che) :},
                 /** 
        * @sG the lowr pperor IEe inDOMlements  ecified
         * erethod getLoElWr pper        * @param {YAring} id the id of the Drement to cht
         * @return {boHOO.util.DDM;
.ement} Wr pper}he lowr ppeelement
         * @reivate
         * @stdreseced inis wilr pperoi't giat isedoful        * @static
         */
        istLoElWr pperfunction(id) {
            for oDDWr pper this.drement",che) ];;
            if (!loDWr pper  !thDWr pper.) {
                  DWr pper this.drement",che) ];;
 th                     w YAis.drement} Wr pper(HOO.util.Dom.getXYd) {
            }
            thturn oDDWr pper        },

        /**
         * Refrrns the Dractu moDOMlements         * erethod getLoElent
         * @reiam {YAring} id the id of the Dremnt to cht
         * @return {boject} grT Drement t        * @stdreseced ine evHOO.util.Dom.getXYnstead o        * @static
         */
        istLoElent tfunction(id) {
            return ( HOO.util.Dom.getXYd) {        },
        
        /**
         * Returns a e staty posverty frr IEe inDOMlements  (i.e.,         * downment.
 tLoElId(id) ).aty p         * @method retCoos         * @param {Evring} id the id of the Dremnt to cht
         * @return {boject} grT Draty posverty fr the Drement t        * @stdreseced ine evHOO.util.Dom.gnstead o        * @static
         */
        istLoos function(id) {
            for oD = oDHOO.util.Dom.getXYd) {        },  return ( l) {
?l.ofaty po:ull;
        },

        /**
         * FiIer = ass ofr IEched loements t        * @prass ofagDropMgr.reement} Wr pper        * @prr IEagDropMgr.r        * @reivate
         * @stdreseced i        */
        isement} Wr pperfunction(el) {
            tr  /**
         *       * @sT Drement t        *       * @reiverty frem        *       * @/        *       *is.drem el.o  !tll;
                if*
         *       * @sT Drement td
         *       * @reiverty fr
         *       * @/        *       *is.drid this.dremo !d.ofi
                  *
         *       * @sAefree ie is tra Draty posverty f        *       * @reiverty frcs                   @/        *       *is.drcs  this.dremo !d.ofaty p            }
 
        /**
         * Refrrns the Drposition of thantml element.
        * @method retCoPosX        * @param {E the element tor IEich +  get the 
 sition
         * aneturn {bot} the X pocrd(pine
         * @str IEagDropMgr.r        * @redreseced ine evHOO.util.Dom.getXYXnstead o        * @static
         */
        istLoPosXfunction(el) {
            trturn ( HOO.util.Dom.getXYXl);
           
        /**
         * Refrrns the DrYosition of thantml element.
        * @method retCoPosY        * @param {E the element tor IEich +  get the 
 sition
         * aneturn {bot} the X Yocrd(pine
         * @stdreseced ine evHOO.util.Dom.getXYYnstead o        * @static
         */
        istLoPosYfunction(el) {
            trturn ( HOO.util.Dom.getXYel);
            
        /**
         * ReSwathewno edes IE n IE,e aru the obtivelyethod c,or IEher ewe ar        * Reemated"he obIE behavio"        * @method isswatNe
         * @param {Yn1he first on edeo seewat        * @param {Yn2ne orier th edeo seewat        * @paatic
         */
        stowatNe
 function(eln1,Yn2{
            if (! n1.owatNe
 {
            tr  /*n1.owatNe
 (n2{              
lse {
                  r par nul2.rent) Ne
                   r els nul2.nextSiblg} 
                if (! s= thn {
                wiiiiip.stety Ber Ieln1,Yn2{                }
 lse {
 (! n2= thn .nextSiblg} {
                wiiiiip.stety Ber Ieln2,hn {                }
 lse {
                wiiiiin .rent) Ne
 .replaceChildln2,hn {                }
iiiip.stety Ber Ieln1,Y;
                              }
        },

        /**
         * Thfrrns the Drrrent drscroll sition
         * anethod retCoScroll        * @private
         * @static
         */
        istLoScrollfunction (oD{
            var tar, lddo,e=wnment.
 wnment.
Ement
 ,hdb=wnment.
 body            if (!lo,e& (b4o,e.scrollTop| dd.ie.scrollft;
 {
                YAt dd;
e.scrollTop                loc dd;
e.scrollft;
            th lse {
 (! d {
                  t dd;b.scrollTop                loc dd;b.scrollft;
            th lse {
                YAHOO.log("could not get thscroll sverty f "info", "DragDropMgr");
            }

           }
turn {boop-l:ar, lt;
:oc 
        },

        /** 
        * @sturns a e stacified grements  ety posverty f        * anethod retCoSty p        * @param {EvMLElement} el th         t Drement t        * @stram {Evsing} iddddddety popag   t Drety posverty f        * aneturn {bosing} idT Drluatof the subty posverty f        * anedreseced ine evHOO.util.Dom.getXYSty p        * @paatic
         */
        istLoSty pfunction(el) ,dety popag{
            trturn ( HOO.util.Dom.getXYSty pl) ,dety popag{        },

        /** 
        * @sGs the moscrollTop        * anethod retCoScrollTop        * aneturn {bot} the X wnment.
'soscrollTop        * aneatic
         */
        istLoScrollTopfunction (oD{
 eturn this.hatLoScroll(toUpp;,

        /** 
        * @sGs the moscrollft;
        * anethod retCoScrollft;
        * aneturn {bot} the X wnment.
'soscrollTop        * aneatic
         */
        istLoScrollLt;
:onction (oD{
 eturn this.hatLoScroll(tolt;
;,

        /** 
        * @sSs the mox/yosition of thantement to che location of the e        * @srget isement.
         * @method geve
 To
         * @param {HTMLElement} elve
 Eth     T Drement td make
         * @param {EvMLElement} elrget iEth   T 
 sition
 efree ie isement t        * @static
         */
        isve
 To
 :onction (oDve
 Et,lrget iEt{
            var taaord(p oDHOO.util.Dom.getXYXY(rget iEt{            }
HOO.log("mous
 To
 :o+ loaord(p"info", "DragDropMgr");
            }
HOO.util.Dom.gesXYXY(ve
 Et,laord(p{        },

        /** 
        * @sGs the moientY  hght,         * anethod retCoCentY Hght,         * aneturn {bot} thientY  hght, n thpx        * anedreseced ine evHOO.util.Dom.getXYVwport iHght, n tead o        * @static
         */
        istLooentY Hght, :unction() { 
             turn ( HOO.util.Dom.getXYVwport iHght, ,
          

        /**
         * ChGs the moientY  wth,
        * anethod retCoCentY Wth,
        * aneturn {bot} thientY  wth,
n thpx        * anedreseced ine evHOO.util.Dom.getXYVwport iWth,
n tead o        * @static
         */
        istLooentY Wth,
:unction() { 
             turn ( HOO.util.Dom.getXYVwport iWth,
,
          

        /**
         * ChNentricrray isst innction()        * anethod renentricSt i        * @static
         */
        isnentricSt i:unction() a, b{
 eturn th(a - b{;,

        /** 
        * @sternal fuulderna        * @priverty fr_teout =Clder        * @private
         * @static
         */
        is_teout =Clder: 0
        /**
         * Thiing to acke sue locaaor td clls thimrt ia.  Willoughthis in art
         * @ranteor' f the  infilis ovcaao by er Ieue loent(ctility t         * @method ge_addLtersn ew        * @private
         * @static
         */
        is_addLtersn ew:unction() { 
             r DDM = YAHOO.util.DDM;
               (! AHOO.util.Event.g& dd.nment.
 {
                  M;
._onLaao)
               lse {
                YA (! M;
._teout =Clder > 200 {
                      HOO.log("moagDrop obreques the enent(ctility t "Dreor'  "DragDropMgr");
                }

lse {
                wiiiiitTimeout( 
M;
._addLtersn ew, 10
                    if (difnment.
  dd.nment.
 body{
                        reM;
._teout =Clder += 1                    }
                }
            } c       },

        /** 
        * @stursorely thsearcs the caimmedied"hrent = d onl ofchildo edesor IE        * @srheandle caement td
nr td cl determine ifwher is nothit wina
         * taickPied         * @method handleMoWasCckPied        * @param {E edeo hhtml element.
l despectin        * @static
         */
        isndleMoWasCckPiedfunction(elne. if  {
            fo (this.isTyHdleMo(, "wne. .) {

                YAHOO.log("couckPiedE edeo a drndleMo "info", "DragDropMgr");
                  turn faue;
            }
 lse {
                // Locck to see if the  in tha xt of edeochildo the ori we cowa t        *       *r par nule. .rent) Ne
                    YAHOO.log("Prp" + lop

                //whilis( {
                    co (this.isTyHdleMo(, "wp.) {

                YA        turn faue;
            }
      }
 lse {
                //////////HOO.log("Ppd + " dr not a DDndleMo "info", "DragDropMgr");
                          r nup.rent) Ne
                     }
                }
            } c              turn ( lse;
        },
      
   })
  
 subht is ll iaw, se ena few bytes
HOO.util.DDM;
 YAHOO.util.DDMgDropMgr");
HOO.util.DDM;
._addLtersn ew)
  

  (nction() { 
 
r paent(c=HOO.util.Event.g; 
r pam.g=HOO.util.Dom.g  
  
  * Dined,the caiernafacend botes eertyion of thems
  at the atan fibar  *rag ed el IEchfibarop thaget is It res draesigns o evbart oded t,uerlaring
   @srheaent handlers")or IEartDrag(t,nDragOu,nDragOuOr, or ragOut e.  @sUpo che rehtml element.
sEchfibarsociativs oth an eagDrop ob tance i:  @s<ul>  @s<li>link grements :he Drement to t is stss t interche loistraiuct.
   *his is ine Drement toich + dined,the caundary
iesor IEteractionInith 
   *rier thagDrop object ws.</li>  @s<li>ndle caement t(s): T 
 ag objrtyion of y iscupirsf the element lo t is  @ss druckPiedEtches,  DDndleMolement", WiByefault, his in the
  link gr  @sement
 ,hbhthise ine tureou  at thyouill stwa tf y isa rt in of the dr  @slink grements l despial tthe drag enjrtyion oand fie mosetHdleMoElI) &&  @sthod haovides an twayl detened,his.ha</li>  @s<li>aggelement
 this.lspresentinsrante element lo t iswld be anve
 onl o
   @sth the morsor loring tharag enjrtyion o WiByefault, ,his in the
  link g  @sement
 hemselfi a 
nr{@linkAHOO.util.DDM;}.iitTiagEl()I) &&ls thyouitened,  @saitTram telement lo t iswld be anve
 o,i a 
nr{@linkAHOO.util.DDM;Proxy}  @s</li>  @s</ul>  @sis chess ofould bet a binstancettivs oderilhe ori loadvent ha evanre an t i  @srheasociativs oements toe inailable
    *hiseor llowg thwld betened,hahagDrop objeco t iswld beteractioith an ey   *rier thagDrop objecn the DO"oup1:t"roup: i  @s<ese>  @sd = ddw YAHOO.util.ReagDrop odiffv1 "Droup1:t"
   @s</ese>  @sSie isni we the Drent handlers")ove enbeenhimrments e "wneing ifwld 
   *ractu m iss ppedf thyouile ineo runhe loisdeoabe
 .iiNmat m isyouilld 
   *rerlarinehis disss of IEh we the Drfault, himrments ion os,hbhthyouin 
   *ralsorerlarinehisyethod cthyouiwa tf yn indeance is the Drsss o...  @s<ese>  @sd =nDrag(eop = falction(e, isD {
   @sd&nbsp;&nbsp;ars"tdifdas dragped, f yn+ lo) {   *}
   @s</ese>  @s@naou pacenHOO.util.R
 @prass ofagDropMg
 @prastraiuct.

 @param {Evring} id th the Drement to t is stlink grrche  in tance i
 @param {Evring} idroup ine Droup ar threted",hagDrop object ws
 @param {Evoect} grnfig opaobject containing thnfiguredle poatingbhtes
 @ppppppppppppppppVidDrosverty iesor IEagDrop o: 
 @pppppppppppppppp    rding[3isDrrget, inin pininOsetHe"wprimaryBton fly) ,
/
  HOO.util.ReagDrop o falction(e,, "wroup i,hnfigur

       (isD {
          is.inteit,, "wroup i,hnfigur
       }

   HOO.util.ReagDrop o.svetope = ev      * 
      * An ject}  Liractlontaining the drents whtt we cawl be inedg t:ousedoDowoanb4MsedoDowoanuseUpEvanb4SrtDrag(t,nartDrag(t,nEndDragEv,ndDrag eanuseUpEvandg(t,nEnag eanvalidDropEv,nEnag et eandg(tt eandg(tEerac,nEnag etr, ordg etr, orEnag eopEv,ndgDropMg
     * Bthseing th ey  the Dsineo lse;
the fnrent hawl bet a binreEvd       @reiverty frents
       @repe = ject c      @      ents: full;
,     * 
     @method ha()     anedrscriion drSht icutor IEent.gPvides rubstrcrib so eht<DDnref="HOO.util.Event.gPvides ruml e#bstrcrib ">HOO.util.Event.gPvides rubstrcrib </a      @      : function(oD{
          is.inbstrcrib . pplyhis.i,i rgent.
;
      },     * 
      * T id of the Drement tosociativs oth ane  inject c This is inat we ca      * free o accthe
  "link grements " bicau the obze, nd fisition of th      * e  inement
 heused to retermine ifwhethe drag and drop injects frve en      * teractiovd       @reiverty fr
       @repe = ring}       @      idfull;
,      * 
      * Cfiguredlon ofatingbhtestss t interche loistraiuct.
      @reiverty frnfigur      @repe = ject c      @      nfigurfull;
,      * 
      * T id of the Drement tott we l be inag ed e WiByefault, his in thsaoun      * athe
  link grement to,hbhthcld be ancndlg to reanier thement", WEx:n      * HOO.util.DDM;Proxy      @reiverty frdgEl()I)      @repe = ring}       @private
       @      dgEl()I)full;
,       * 
      * e id of the Drement to t is pial ttshe drag enjrtyion o WiByefault, h      * e  inithe
  link grement t,hbhthcld be ancndlg to rebarsochildo the i       @rement", Wiis isls th drache  ngstlikef y isartDrg the drag anen the 
       * head thement",oth a the DOlink grml element.
lisaickPied       @reiverty frhdleMoElI)      @repe = ring}       @private
       @      hdleMoElI)full;
,       * 
      * Aassociative array of grML ists obtt we l be inigned in faickPied       @reiverty frvalidDrHdleMopeOf       @repe = osing} : sing} i      @      ialidDrHdleMopeOf full;
,       * 
      * Aassociative array of grid= r IEements to t we l be inigned in faickPied      @reiverty frvalidDrHdleMoId       @repe = osing} : sing} i      @      ialidDrHdleMoId full;
,       * 
      * Aasdexedt array. f grcs  ass ofnaou  r IEements to t we l be inigned i      * tfaickPied       @reiverty frvalidDrHdleMoCss of       @repe = sing} 
       @      ialidDrHdleMoCss of full;
,       * 
      * T
  link grement t'toebsolhterposition of the 
 reouhe drag anea
       *sartDred      @reiverty frartDrgeX(e      @repe = i t      @private
       @      artDrgeX(e: 0
      * 
      * T
  link grement t'toebsolhterposition of the 
 reouhe drag anea
       *sartDred      @reiverty frartDrgeX(Y      @repe = i t      @private
       @      artDrgeX(Y: 0
      * 
      * T
  oup ardined,tha logal 
 c llecon of thagDrop object wso t wee 
       *hreted", IE nance isf y ist isents when weteraction ifwh anier th      *hagDrop object we the speaounoup a Wiis isls th draened,hltiple ma      *houps ||edg thaingle maagDrop obbstass of we arwa t       @reiverty froups |      @repe = osing} : sing} i      @      oups |full;
,      * 
      * Indidesu drag o/op instance, sEchfibarlked() Wiis is l beeviot.
l      * onusedown
 aartDrrag.
       @reiverty frlked()      @repe = olean}       @private
       @      lked(): lse;
t      * 
      * ckedhe  in tance i
     @prthod halked      @      lkedfunction(oD{
  is.locatk grtrue;
 ,

      * 
      * Unloedhe  in tanc i
     @prthod haunlked      @      unlkedfunction(oD{
  is.locatk grtrlse;
 ,

      * 
      * Byefault, ,hl ofstance, sEchfibararop thaget i Wiis ischfibaroible
 by t      *saeing thTarget &&eo lse;
       @reiverty frvsrget
       @repe = olean}       @      isrget: fuue, g      * 
      * T
  dding configured for ite  inag and drop object tor IEchlcatedg}       @pe drag obzh wetersect mnInith 
 e  inject c       @reiverty frdding
       @repe = i t
       @      dding
 full;
,     * 
      * Ithe  inflags true, badoot findrerop events w.sT Drement td
sharag enjy isements  (r IEve
 nts  t fiopping o)      @reiverty frdgElly)       @repe = Blean}       @      dgElly) : lse;
t      * 
      * Ithe  inflags true, baainhime l be inplacedver the tarcreen/viewle poarearrchectikruse events w.sSuld behelwith 
 ag edg element lover thifraou  d onMer thntaiiols       @reiverty fre eShim      @repe = Blean}       @      e eShim: lse;
t      * 
      * Ched lofree ie is tra Drlink grement t      @reiverty fr_domRef      @private
       @      _domReffull;
,      * 
      * Inrnal fupeof sGflag      @reiverty fr__ygDgDropMg
     * rivate
       @      __ygDgDropMgfuue, g      * 
      * S &&eo ue : en wehoriztainlnntaiints",oe inapplied      @reiverty frnstraintsX      @repe = olean}       @private
       @      nstraintsX: lse;
t      * 
      * S &&eo ue : en wevty icnlnntaiints",oe inapplied      @reiverty frnstraintsY      @repe = olean}       @private
       @      nstraintsY: lse;
t      * 
      * T
  lt anistraints       @reiverty frmtsX      @repe = i t      @private
       @      mtsX: 0
      * 
      * T
  ght poistraints       @reiverty frmaxX      @repe = i t      @private
       @      maxX: 0
      * 
      * T
   aristraints        @reiverty frmtsY      @repe = i t      @prpe = i t      @private
       @      mtsY: 0
      * 
      * T
  wn
 aistraints        @reiverty frmaxY      @repe = i t      @private
       @      maxY: 0
      * 
      * T
  wifee ie isbetwe the 
 ickPiosition of  fie mosrceRerement t'tolation
       @reiverty frdtaY X      @repe = i t      @private
       @      dtaY X: 0
      * 
      * T
  wifee ie isbetwe the 
 ickPiosition of  fie mosrceRerement t'tolation
       @reiverty frdtaY Y      @repe = i t      @private
       @      dtaY Y: 0
      * 
      * Mn pininffsetPawhen we arsenttistraints a WiS &&eo ue : en weyouiwa t      @pe drsition of the Drement toretede ar destshrent =  seetayhe speaou      @pen the 
 pe iscndlg |      @      @reiverty frma pininOsetHe      @repe = olean}       @      ma pininOsetHe: lse;
t      * 
      * Aay. f grpixelolation
 ine Drement toil besnapo ch we aracified gra       * horiztainlngrasu on
 /terselid Wiis isray is nogsn eivs oautoma icnl)       @ren weyouitened,hahc
 k terselid       @reiverty frxT
 kd      @repe = i t
       @      xT
 kdfull;
,      * 
      * Aay. f grpixelolation
 ine Drement toil besnapo ch we aracified gra       * vty icnlngrasu on
 /terselid Wiis isray is nogsn eivs oautoma icnl)        @ren weyouitened,hahc
 k terselid       @reiverty fryT
 kd      @repe = i t
       @      yT
 kdfull;
,      * 
      * Byefault, hisdrag and drop instance, oil bejy issenponds tra Drprimary      * bton f ickPio(lt anbton f r IEa ght p-hdleedEtse e) WiS &&eo ue : to      * allowrag and drop in seetaroith an ey use evickPio t is stsp iogared      @r the drbrowse
      @reiverty frprimaryBton fly)       @repe = olean}       @      primaryBton fly) fuue, g      * 
      * T
  ailableposverty fr infse;
oderilhe orlink grdomrement td
shaess tie
        @reiverty frailable
       @repe = olean}       @      ailable
 : lse;
t      * 
      * Byefault, ,hag aischfijy isbes pial ttdf the mouse iwn
 acupirsf the
       *efron obe talink grement tois This is indh wetehren&&eo worksrandar a      * btgetehsomdrbrowse
so t wemis-rert ihe mouse iwn
 a the moevioise       * use i ars pped on utrid f the Drwinwn
 This issverty fr ins &&eo ue :      *  th ut thhdleMo,oe intened,d       @      @reiverty frhasOut tHdleMos      @repe = olean}       @nedrult, hlse;
      @      hasOut tHdleMos: lse;
t      * 
      * Pverty fr t is stassigns o evanag and drop object toen theesrg theo      * e if it ov stbeg thegeted it thanier thddnject c This issverty f      *schfibarut intentersect mode
 o hanelwitermine ife firocuof t      @pe druse is eractionIn.reM;
.tBestMatch: rut she  insverty frrst oneo      * termine ife ficlost match fr INTERSECT mode. oen thltiple maaget is      * arehren&& the subaoun eractionIn.      @reiverty frnsorIsOver)       @repe = olean}       @      nsorIsOver) : lse;
t      * 
      * Pverty fr t is stassigns o evanag and drop object toen theesrg theo      * e if it ov stbeg thegeted it thanier thddnject c This is
sharfron o      @pe  ispresentinssrheasrearrsdrag agle poement occrlap.gsie  inaget i       @pM;
.tBestMatch: rut she  insverty fr evnsmparthe obze, n the orirlap.g      @peope  is thier thaget isd
nr td cl determine ife ficlost match fr I      @pTERSECT mode. oen thltiple maaget is arehren&& the subaoun eractionIn.      @reiverty frerlap &&      @repe = HOO.util.Region(       @      erlap &full;
,      * 
      * Cfdan t i ecuthtestimmedied" isber Ieue loartDrag(tvents       @rethod hab4SrtDrag(t      @private
       @      b4SrtDrag(tfunction(oDx, y{ }

,      * 
      * Atr(1tioithod coualls oafis llrag o/op inject we saickPied      @r  fie moag enjrouse iwn
 areouhe sh eold)ove enbee thl i       @pethod issrtDrag(t      @priam {Evt} thXvickPiolation
       @reiam {Evt} thYvickPiolation
       @      artDrag(tfunction(oDx, y{ }
/*rerlarinehis di*/

,      * 
      * Cfdan t i ecuthtestimmedied" isber Ieue loDrag(events       @rethod hab4ag(t      @private
       @      b4ag(tfunction(oDe{ }

,      * 
      * Atr(1tioithod coualls oring the loDrMsedoMerlrent hawhilisag edg el 
       * ject c       @rethod ha()ag(t      @priam {Event.g} epe druse imerlrent h      @      enag(tfunction(oDe{ }
/*rerlarinehis di*/

,      * 
      * Atr(1tioithod coualls oen the  inement
 hft ofbion inherlag eler th      @r  ier thagDrop objec      @rethod ha()ag(tEerna      @priam {Event.g} epe druse imerlrent h      @param {Evring} |agDrop[]} ddidE n POINmode. ife Drement t      *  d e  inithherlag eler t IE n IERSECT mode. ,n assay. f grh we rous 
       *hag eop insms
  beg thherlaedver t.      @      enag(tEerna:alction(e, isD {
 
/*rerlarinehis di*/

,      * 
      * Cfdan t i ecuthtestimmedied" isber Ieue loDrag(eer)  ents       @rethod hab4ag(ter)       @reivate
       @      b4ag(ter) : lction(oDe{ }

,      * 
      * Atr(1tioithod coualls oen the  inement
 hithherlag eler tr  ier th      @pMgDrop objec      @rethod ha()ag(ter)       @reiam {Event.g} epe druse imerlrent h      @param {Evring} |agDrop[]} ddidE n POINmode. ife Drement t      *  d e  inithherlag eler t IE n IERSECT mode. ,n assay. f grditems
 h      @pbeg thherlaedver t.      @      enag(ter) : lction(oDeisD {
 
/*rerlarinehis di*/

,      * 
      * Cfdan t i ecuthtestimmedied" isber Ieue loDrag(eeut ents       @rethod hab4ag(teut      @private
       @      b4ag(teut: lction(oDe{ }

,      * 
      * Atr(1tioithod coualls oen th are inno  o
  thherlag eler tr  rement t      @rethod ha()ag(teuh      @param {Event.g} epe druse imerlrent h      @param {Evring} |agDrop[]} ddidE n POINmode. ife Drement t      *  d e  inea
 herlag eler t IE n IERSECT mode. ,n assay. f grditems
 h      @pat the 
 use is ovno  o
  ther t.      @      enag(teut: lction(oDeisD {
 
/*rerlarinehis di*/

,      * 
      * Cfdan t i ecuthtestimmedied" isber Ieue loDrag(eDp events       @rethod hab4ag(topMg
     * rivate
       @      b4ag(topMg: lction(oDe{ }

,      * 
      * Atr(1tioithod coualls oen the  inems
s indgped, f yn  ier thagDrop ob      * jec      @rethod ha()ag(topMg
     * riam {Event.g} epe druse iuevents       @reram {Evring} |agDrop[]} ddidE n POINmode. ife Drement t      *  d e  inea
 dgped, f y IE n IERSECT mode. ,n assay. f grditems
 he  in      * ea
 dgped, f y       @      enag(topMg: lction(oDeisD {
 
/*rerlarinehis di*/

,      * 
      * Atr(1tioithod coualls oen the  inems
s indgped, f yn  asrearth anno      * tp thaget i      @rethod ha()IalidDropEv
     * riam {Event.g} epe druse iuevents       @      enIalidDropEvfunction(oDe{ }
/*rerlarinehis di*/

,      * 
      * Cfdan t i ecuthtestimmedied" isber Ieue lodDrag events       @rethod hab4dDragEv
     * rivate
       @      b4dDragEv: lction(oDe{ }

,      * 
      * FeEvdoen th are indh weag edg ele loDect c      @rethod haeDragEv
     * riam {Event.g} epe druse iuevents       @      eDragEv: lction(oDe{ }
/*rerlarinehis di*/

,      * 
      * Cfdanecuthtedtimmedied" isber Ieue loDrMsedoDowovents       @rethod hab4MsedoDowo
     * riam {Event.g} epe druse idowovents       @reivate
       @      b4MsedoDowo: lction(oDe{ }


,      * 
      * Ent handlers"n t i fes then thlrag o/op injec t is arusedown
       @rethod ha()MsedoDowo
     * riam {Event.g} epe druse idowovents       @      enMsedoDowo: lction(oDe{ }
/*rerlarinehis di*/

,      * 
      * Ent handlers"n t i fes then thlrag o/op injec t is arusedoup      @rethod ha()MsedoUv
     * riam {Event.g} epe druse iuevents       @      enMsedoUv: lction(oDe{ }
/*rerlarinehis di*/

,  
      * 
      * OrlarinehisyeonAilable
 ithod co detonat we ovneeds oafis le id tial d      * sition ofs draermine id       @rethod ha()Ailable
       @      enAilable
 : lction (oD{
 e         this.stlog  tog("coenAilable
 i(tes )"
       },      * 
      * turns a Refree ie is tra Drlink grement t      @rethod getLoEl      @return {boMLElement} elrhhtml element.
l      @      tLoElfunction(oD{
            (!lois.in_domRef{
              is.us_domRef = m.getXYdis.drid
                      turn this.ha_domRef;     },      * 
      * turns a Refree ie is tra Dractu moements l deag.
 WiByefault, his in t      @patsubaounathe
  ml element.
,hbhthitEchfibarsocigns o eva ier th      @pement", WAovexamrmeo the i Echfibarfndar in HOO.util.DDM;Proxy      @rethod getLoagEl()      @return {boMLElement} elrhhtml element.
l      @      tLoagEl()function(oD{
          turn {bm.getXYdis.drdgEl()I));     },      * 
      * Ss th ine DragDrop object w WiMu ofbioualls oinhe loistraiuct.
f thany      @rHOO.util.ReagDrop o bstass o      @rethod ge tia
     * riam {E the id of the Drlink grement t      @reiam {Evring} idroup ine Droup ar threted",hems
       @reiam {Evoect} grnfig opcfiguredlon ofatingbhtes      @       tia:alction(e,, "wroup i,hnfigur

          is.us tiarget: ,, "wroup i,hnfigur
          ent.g.(e,is.us_domRef  this.ids;, "use idowo"
                          is.idndlersMsedoDowoanis.i,iue :

          // ent.g.(e,is.uss;, "selecoartDr"
 ent.g.eviot.
Dault, 
          r (var i in this.idents w{
              is.uscreateent.g(i + 'ent.g'
          
              },      * 
      * Itial diz thrget: g ellction(eaty tijy i...le loDect cetoenot a      * t is ousedown
 as lers"       @rethod ha tiarget: 
     * riam {E the id of the Drlink grement t      @reiam {Evring} idroup ine Droup ar threted",hems
       @reiam {Evoect} grnfig opcfiguredlon ofatingbhtes      @       tiarget: function(pt, "wroup i,hnfigur

           // cfiguredlon ofatingbhtes          is.usnfig op=rnfig op th;
           is.idents w {};
           // createha locnlnfree ie is tra Drag and drop obmanagna        *is.idM = YAHOO.util.DDM;
           // itial diz ne Droup asoDect c        *is.idoup aso{};
           // s ouman t i weove enantement tofree ie is tead of thantidf the m         // iam {er is not a DDsing}        co (thieof sGidf!== "sing} "{ 
             HOO.log("coidf not a DDsing} , s oumg thTis stan MLElement} ;
              is.us_domRef = i
              id thm.getXn eivs(id) )        },
          // s the 
 
         *is.drid thi
           // sd o eva EteractionInioup a        *is.drsd Tooup i((roup i{
?lroup in: "fault, "

          // Windh 'tiwa tfeo risters lis di a rhhtmdleMolth the momanagna        *// so weoju ofs the 
 
  eivhs"n t nouallg ele loaeine
         *is.idndlers()I) thi
           ent.g.(eAilable
 t, "wis.idndlersOeAilable
 anis.i,iue :

          // createha log  tn tance i
      this.locag  tn= (HOO.utwidt i LogWrirac{
?l                 w YAHOO.utwidt i LogWrirac,is.ustoring} ())n: HOO.u
          // e talink grement toisfe Drement tn t i t is ag ed elbyefault, 
      this.lotTiagEl()I) id
            // byefault, ,huckPiedEce horsawl bet a artDrrag.
njrtyion os.e         th @TODOnat wese {
ould bebiose i?  Pveble
frr Im feelds         *is.idialidDrHdleMopeOf o{}; A: "A" 
        },is.idialidDrHdleMoId= {};
        },is.idialidDrHdleMoCss of  {}[]           is.id pplyCfigur();     },      * 
      * Applieshe loistguredlon ofiam {er iwhtt we crehre t interche loistraiuct.
       * is is
shsupsits o evs ppedf wesach lentlhe up ghle id tse ince, ochaiy IESo      * a M;Proxyhimrmes ion oawl beecuthtenapplfrnfigurf ynM;Proxy,nM;and fi      * agDrop ob tr td cl det is  bejthe moeam {er iwhtt wee inailable
 r I      @psach ject c       @rethod ha pplyCfigur      @       pplyCfigurfunction(oD{
          is.inents w {};             msedoDowo: ue, g             b4MsedoDowo: ue, g             msedoUv: ue, g             b4SrtDrag(tfuue, g             srtDrag(tfuue, g             b4dDragEv: ue, g             eDragEv: ue, g             dg(tfuue, g             b4agEv: ue, g             ialidDropEvfuue, g             b4agEveut: ue, g             dg(teut: ue, g             dg(tEerna:aue, g             b4agEvevna:aue, g             dgEvevna:aue, g             b4ag(topMg: ue, g             dgEvopMg: ue,        },
        },       co (this.usnfig odents w{
              r (var i in this.idnfig odents w{
                co (this.usnfig odents w[i] ==trlse;


                YA    is.inents w[i] =alse;
                },

          },

        }
          // cfiguredlb posverty ios:          //    rding[3isDrrget, inin pininOsetHe"wprimaryBton fly)          is.indding cooooooooooo=his.idnfig oddding co th[0, 0, 0, 0]        },is.idiarget &&ooooooooo=hhis.usnfig odiarget &&!=trlse;

        },is.idin pininOsetHeoooo=hhis.usnfig odin pininOsetHe
        },is.idprimaryBton fly) o=hhis.usnfig odprimaryBton fly) o!=trlse;

        },is.iddgElly) o=hhhis.usnfig oddgElly) o==true;
? true : false;

        },is.ide eShimo=hhhis.usnfig ode eShimo==true;
? true : false;

      
,      * 
      * Ecuthtedten the 
 link grement toisfailable
       @rethod handleMoO)Ailable
       @private
       @      hdleMoOnAilable
 : lction (D{
          this.stlog  tog("cohdleMoOnAilable
 "
        },is.idailable
 r=aue;
          is.idsenttCstraints a(
        },is.id(eAilable
 t
      
,       * 
      * Cfiguredeshe lodding cor IEe inrget iszh wetehrx IEEfft mnly thexpdle       @r( IEreducew{
e invirtu moDect ceze, nr IEeget: g elchlcatedg os.ei      * Supsir
sEcss-bty pobht ihdle;  th y iscne iam {er is nore t i,hl ofrid        @rwl beve entt werding[3isd fi th y isewnoarehre t iife Drt obd botoon m      @rwl beve enttfirst oniam {ife Drlt and boght poe loaenfid       @rethod hanttPding
       @reiam {Evt} thiTag    Tag pad      @reiam {Evt} thiRht po Rht popad      @reiam {Evt} thiBoeooooBoeopad      @reiam {Evt} thiLt;
   Lt;
 pad      @      attPding
 function(pt,TEv,niRht p,hiBoe,hiLt;
{
          th is.indding co{}[iLt;
,niRht p,hiTEv,niBoe]        }, (!loiRht po&& 0o!=triRht p{
              is.usdding co{}[iTEv,niTEv,niTEv,niTEv]        }, lse {
 (! !iBoeo&& 0o!=triBoe{
              is.usdding co{}[iTEv,niRht p,hiTEv,niRht p]        }, lse {
              is.usdding co{}[iTEv,niRht p,hiBoe,hiLt;
]        },      
,      * 
      * St.
,the caieial dnplacent occthe Drlink grement t       @rethod hanttItial dPition
       @reiam {Evt} thwifeX   t DrXffsetPa,efault, h0      @reiam {Evt} thwifeY   t DrYffsetPa,efault, h0      @reivate
       @      attItiaPition
 function(ptwifeX,hwifeY{
          r oD = oDis.hatLo(),
         }, (!lois.idM =.rlagfy(),el{

               (!lemo !d.ofaty p& (b4.ofaty p.displa o== 'ni w' {
                YAts.stlog  tog("cis.drid " drchfit get thieial dnpition
 ,rements  ety po indispla :sni w;
            }

lse {
                //ts.stlog  tog("cis.drid " drement toisfbroken;
            }

           }
turn {        },
          r oDdx dd;ifeX  th0;         r oDdy dd;ifeY  th0;          r oDp = m.getXYXY(D = )           is.idieiageX(e nup[0] -Ddx        },is.idiaiageX(Y nup[1] -Ddy           is.idss ageX(e nup[0];         is.idss ageX(Y nup[1]           is.idsog  tog("cis.drid " drieial dnpition
 " + lois.idieiageX(e +l                  "Dr lois.idieiageX(Y)   
      this.lotTiSrtDrPition
 (p);     },      * 
      * Ss the loartDrrsition of the Drement t This is
shtHeoen the 
 jec      @r in tial diz iife Drsentthen thlrag os
shtrtDred       @rethod hanttSrtDrPition
       @reiam {Esitrrrent drsition of(fromoevioise  lookup)      @reivate
       @      attSrtDrPition
 function(ptsit{
          r oDr nupitr thm.getXYXY(rs.hatLo(),
)           is.iddtaY SXYXY null;
  
      this.lotrtDrgeX(e nup[0];         is.idartDrgeX(Y nup[1]      },      * 
      * Add e  initance, o evanoup ar threted",hag o/op inject ws.eiA be      @r tance, sEbe o
 o evatrlts ascne gup i,hd bochfiba o
 o evasoman        @roup asoaovneeds        @rethod ha d Tooup i      @reiam {Eroup inosing} ide Drnaouf the Droup a      @       d Tooup ifunction(ptroup i{
          is.inoup as[roup i]r=aue;
          is.idM =.regDgDropMghis.i,iroup i{;     },      * 
      * tumerl's e  initance, ofromoe loauppliedEteractionInioup a      @rethod harumerlFromoup i      @reiam {Evsing} iddroup in T
  oup ar deagoa      @      rumerlFromoup ifunction(ptroup i{
          is.inlog  tog("cotumerg corromooup: iDr loroup i{;          (this.usoup as[roup i]{
              dtaete is.inoup as[roup i]        },
          is.idM =.remerlDDFromoup ihis.i,iroup i{;     },      * 
      * Allowthyoui seecifiefr t isantement toovhs"n t noe 
 link grement to      @rwl be anve
 onth the morsor loring tharag e      @rethod hanttagEl()I)      @reiam {Eid osing} ide Dr of the Drement tott we l be ined to respial tthe drag e      @      attagEl()I)function(id) {
          is.drdgEl()I) = i
      },      * 
      * Allowthyoui seecifiefraochildo the orlink grements l t weould bebio      * ed to respial tthe drag enjrtyion o WiAovexamrmeo the i Ewld be anith      * youive enanntaits ldivnth thet ofd bolinks.eiCckPig th eywse inithe 
       * ntaits lsreartld bet at m isartDrre drag enjrtyion o WiUsehis dithod h      *  seecifiefr t isantement toitaid f the Drntaits ldivnisfe Drement tn      *  t weortDrshe drag enjrtyion o       @rethod hanttHdleMoElI)      @reiam {Eid osing} ide Dr of the Drement tott we l be ined to re      @r tial tthe drag e.      @      attHdleMoElI)function(id) {
           (thieof sGidf!== "sing} "{ 
             HOO.log("coidf not a DDsing} , s oumg thTis stan MLElement} ;
              id thm.getXn eivs(id) )        },
         is.drndlers()I) thi
          is.idM =.regHdleMo(is.uss;, i));     },      * 
      * Allowthyoui seeeisantement tooutrid f the Drlink grements lasharag en      * ndlers      @rethod hanttOut tHdleMoElI)      @reiam {Eid e Dr of the Drement tott we l be ined to respial tthe drag e      @      attOut tHdleMoElI)function(id) {
           (thieof sGidf!== "sing} "{ 
             HOO.log("coidf not a DDsing} , s oumg thTis stan MLElement} ;
              id thm.getXn eivs(id) )        },
         is.drlog  tog("coAing co ut thhdleMovents :n+ lo) {          ent.g.(e,s;, "use idowo"
                  is.idndlersMsedoDowoanis.i,iue :

         is.idsetHdleMoElI) ) {           is.drndsOut tHdleMosr=aue;
      },      * 
      * tumerlhl ofag and drop obhooksor ite  inement t      @rethod geunreg      @      unregfunction(oD{
          is.inlog  tog("coagDrop objec can}  arr lois.idi {          ent.g.remerlLtersn e,is.uss;, "use idowo"
                  is.idndlersMsedoDowo

         is.id_domRef = ll;
          is.idM;
._remerl,is.u{;     },      * 
      * turns a ee :  the  in tance, o ovcaPied,  IEe inag anop obmgro ovcaPied      * (mn} g ele athl ofag a/op inssroible
 byothe 
 pe i.)      @rethod geisLked()      @return {boolean} } ee :  the  injec  IEa ofag a/op inssrcaPied, se {
      @rlse;
      @      isLked()function(oD{
          turn {b(is.idM;
.isLked()D{
 this.idcaPied{;     },      * 
      * FeEvdoen the  inject we saickPied      @rethod handleMoMsedoDowo
     * riam {Event.g} ep
     * riam {EvHOO.util.ReagDrop o} oDDhe DrnckPiedEddnject cb(is.iEddnjec)      @reivate
       @      ndleMoMsedoDowo: lction(oDeisoDD

           r oDbton f = e.ich +  the.bton f;         is.idsog  tog("cobton f:n+ lobton f
         }, (!lis.idprimaryBton fly) o&&Dbton f >  {
              is.idsog  tog("coMsedown
 as drt a producedr the drprimaryDbton f;
              turn {        },
           (this.isTyLked()D{{
              is.idsog  tog("coDg and drop inssroible
 b,oabeDrg t;
              turn {        },
          is.idsog  tog("cousedown
 ar lois.idi {           is.idsog  tog("cofiag elerMsedoDowovents s"

          // fiag ele druse idowovents insviocl dechlcatedg} rsition os         r oDb4turns  oDis.hab4MsedoDowo(e)g         b4turns 2r=aue;
            (this.isents w.b4MsedoDowo{
              b4turns 2r=aus.isfes ent.g('b4MsedoDowoent.g', s)        },
         r oDmDowoturns  oDis.haerMsedoDowo(e)g             mDowoturns 2r=aue;
           (this.isents w.msedoDowo{
               (thmDowoturns  o=trlse;


                YA//Fixosr#2528759 - Msedown
 alction (oturn { forse;
thdh 'tindrere Drent had bochfcelrentryt  ng.               YA mDowoturns 2r=alse;
              
lse {
                //mDowoturns 2r=aus.isfes ent.g('msedoDowoent.g', s)        },  },
         
           (th(b4turns  o=trlse;


 thhmDowoturns  o=trlse;


 thhb4turns 2r==trlse;


 thhmDowoturns 2r==trlse;

{
              is.idsog  tog("c'b4MsedoDowof IEh MsedoDowofturn { forse;
thexidg} rag a'
              turn {        },
          is.idM =.refsh eChed his.usoup as
          // r oDselfi=aus.i          // tTimeout( 
unction(oD{
  selfdM =.refsh eChed hselfdoup as
  }, 0

          // ly) opross tre Drent ha we arream isnckPiedEth a the DOlink gr         // ement t Thisarreasoth arke sue  saick toiwhtt weinhe loiasentt we         // anier thement",as drve
 onbetwe the 
 ickPi grements la fie mo         // rsor loinhe loreouhbetwe the 
 use idowova fiuse iuevents s.eWn th         // e  sas ppedsife Drement t t is e Drnt ofuse idowovents h         // regardls th sGwse inothe 
 rcreenhTiss pped o.},       cor oDrt ddw YAHOO.util.RePoi.g(ent.g.gergeX(e(e)g ent.g.gergeX(Y(e){;          (th!is.drndsOut tHdleMosr&&D!is.idM;
.isevnarget: ,p ,his i) ) 
                //ts.stlog  tog("c"CckPias drt a er the taement
 thr lois.idi {          
lse {
               (this.usnckPiVidDrator(

{
                 //ts.stlog  tog("c"cckPias dra lidDrDndleMo 

                //// s the 
 
eial dnement t pition
                //ts.sttTiSrtDrPition
 (

                //// srtDrrectikg} ruse imerlroibnce, oa fiuse iwn
 areouheo               //// termine ifwhetheosartDrre dractu moag e               //ts.stM;
.ndleMoMsedoDowo( anis.i

                //// is ditse iwn
 a s mtse               //ts.stM;
.stopent.g(s)        },  },
lse {
  
ts.stlog  tog("c"cckPiVidDratoroturn { forse;
thdg anthit pial ttd 

              
         
     
,      * 
      * ethod hacckPiVidDrator      * edrscriion drMhod havidDratu  at the 
 ickPi grements       * w a 
ndeefie mohdleMov IEa lidDrDchildo the orndlers      @reiam {Event.g} ep
     *      nckPiVidDrator: lction(oDe{ }       cor oDrget is=AHOO.util.Event.g.gerrget: ,s)        },turn {b(ois.idisVidDrHdleMoChild(rget i)r&&               YA    cis.drid == is.drndlers()I)  th               YA      //ts.stM;
.ndleMoWasCckPied(rget i,ois.idi {) {;     },      * 
      * Fendthe
  lation
 fe Drement t ould bebioplacedv we arwa to ackerl      @r &&eo wse ine 
 use islation
 fls tre DrcckPiafsetPartld beplace us       @rethod hagerrget: Coord      @reiam {Evt} thigeX(e t DrXfcoordin tth the Drssied      @reiam {Evt} thigeX(Y t DrYfcoordin tth the Drssied      @return {baobject coe atantaininshe loisordin tts (ject} .xoa fiject} .y)      @reivate
       @      gerrget: Coordfunction(id)geX(e,higeX(Y

           // ts.stlog  tog("c"gerrget: Coordfu+ lo)geX(e +l "Dr loigeX(Y)         cor oDx dd)geX(e - is.iddtaY X;         r oDy dd)geX(Y - is.iddtaY Y            (this.isnstraintsX{
               (thx < is.idmtsX{
 Dx ddis.idmtsX;

           }
 (thx >dis.idmaxX{
 Dx ddis.idmaxX;,
         
           (this.isnstraintsY{
               (thy < is.idmtsY{
 Dy ddis.idmtsY;

           }
 (thy >dis.idmaxY{
 Dy ddis.idmaxY;,
         
          x ddis.idgerriedDx, is.idxT
 kd)        },y ddis.idgerriedDy, is.idyT
 kd)           // ts.stlog  tog("c"gerrget: CoordDr lo               //// "d)geX(efu+ lo)geX(e +               //// "d)geX(Yfu+ lo)geX(Y +               //// "dxfu+ lox +l "Dyfu+ loy)           turn {box:x, y:y}      },      * 
      * Allowthyoui seecifiefraot antaman t i ould bet a artDrrarag enjrtyion o      * whethickPied This is indesigns o evfacilit telembeing colinksEth a tha      * ag enhdleMov t i dohsomdt  ngoovhs"n t noartDrre drag e       @rethod ha d IalidDrHdleMopeOf      @reiam {Evsing} ideagNaouhe drpe = jfoements l deexclud       @       d IalidDrHdleMopeOffunction(ideagNaou{ }       cor oDre = eveagNaou.toUpperCase(
        },is.idialidDrHdleMopeOf [re =]r=aue =      },      * 
      * Ls thyoui seecifiefrantement toid r IEa childo tha ag enhdleMo      *  t i ould bet a spial ttharag e      @rethod ha d IalidDrHdleMoI)      @reiam {Evsing} idid e Drement toid  the Drement toyouillsho resgned       @       d IalidDrHdleMoI)function(id) {
           (thieof sGidf!== "sing} "{ 
             HOO.log("coidf not a DDsing} , s oumg thTis stan MLElement} ;
              id thm.getXn eivs(id) )        },
         is.drialidDrHdleMoId=[id] = i
      },       * 
      * Ls thyouiecifiefraocs  ass ofjfoements to t we l bet a spial ttharag e      @rethod ha d IalidDrHdleMoCss o      @reiam {Evsing} idcs Css ohe Drsss ofjfoe Drement tsoyouillsho resgned       @       d IalidDrHdleMoCss ofunction(idcs Css o

          is.us tlidDrHdleMoCss of .pushdcs Css o
      },      * 
      * UntPawhantexclud dot antamantPar thad IalidDrHdleMopeOf      @rethod harumerlIalidDrHdleMopeOf      @reiam {Evsing} ideagNaouhe drpe = jfoements l deunexclud       @      rumerlIalidDrHdleMopeOffunction(ideagNaou{ }       cor oDre = eveagNaou.toUpperCase(
        },// ts.stialidDrHdleMopeOf [re =]r=all;
          dtaete is.inialidDrHdleMopeOf [re =]      },          * 
      * UntPawhantialidDrnhdleMovid      @rethod harumerlIalidDrHdleMoI)      @reiam {Evsing} idid e Dr of the Drement toto ri-enle
       @      rumerlIalidDrHdleMoI)function(id) {
           (thieof sGidf!== "sing} "{ 
             HOO.log("coidf not a DDsing} , s oumg thTis stan MLElement} ;
              id thm.getXn eivs(id) )        },
         dtaete is.inialidDrHdleMoId=[id]      },      * 
      * UntPawhantialidDrncs  ass o      @rethod harumerlIalidDrHdleMoCss o      @reiam {Evsing} idcs Css ohe Drsss ofjfoe Drement t(o

youillsho re      @rri-enle
       @      rumerlIalidDrHdleMoCss ofunction(idcs Css o

          r (var i i=0,flsn=is.us tlidDrHdleMoCss of .lsngth; i<lsn; ++i{
               (this.us tlidDrHdleMoCss of [i] == cs Css o

                  dtaete is.inialidDrHdleMoCss of [i]        },  },
         
     },      * 
      * Cck tohe Drt anexclusn
 flis=  seee:  the  incckPiaould bebioigned i      * ethod geisVidDrHdleMoChild      @reiam {EvMLElement} elnonehisyeMLElement} l deelidue
       @return {boolean} } ee :  the  inidra lidDrDt anieof,nfse;
o tht a      *      isVidDrHdleMoChildfunction(idnone

           r oDlidDrD=aue;
          // r oD  oDdnone.noneNaouh== "#et o"? trnone.rent =Noneh:rnone;         r oDnoneNaou        },iry
              noneNaouh= none.noneNaou.toUpperCase(
        },idcch f(


              noneNaouh= none.noneNaou        },
         r dDrD=ar dDrD&&D!is.idialidDrHdleMopeOf [noneNaou];         r dDrD=ar dDrD&&D!is.idialidDrHdleMoId=[none.id]           r (var i i=0,flsn=is.us tlidDrHdleMoCss of .lsngth; r dDrD&&Di<lsn; ++i{
              r dDrD=a!m.geha Css odnone, is.inialidDrHdleMoCss of [i])        },
          ts.stlog  tog("c"VidDrnhdleMo? ...l+ lolidDr)           turn {blidDr       },      * 
      * Createhe draay. f grhoriztainlnc
 k markof wea Eteraclid w a acified g      @r nntPaXCstraints ()       @rethod hanttXT
 kd      @reivate
       @      attXT
 kdfunction(id)SrtDre,hiT
 kSize

          is.usxT
 kd {}[]          is.usxT
 kSize = iT
 kSize                 cor oDr
 kMapo{};
           r (var i in= is.iniaiageX(e; i >ddis.idmtsX;
in= i -hiT
 kSize

               (th!i
 kMap[i])
                //ts.stxT
 kd[ts.stxT
 kd.lsngth] = i;               //t
 kMap[i]D=aue;
            },
       },
          r (vain= is.iniaiageX(e; i <ddis.idmaxX;,in= i +hiT
 kSize

               (th!i
 kMap[i])
                //ts.stxT
 kd[ts.stxT
 kd.lsngth] = i;               //t
 kMap[i]D=aue;
            },
       },
          ts.stxT
 kd.sort(is.idM;
.numericSort) ;         is.idsog  tog("coxT
 kdfur lois.idxT
 kd.join()
      },      * 
      * Createhe draay. f grvty icnlnc
 k markof wea Eteraclid w a acified gr nn      * attYCstraints ()       @rethod hanttYT
 kd      @reivate
       @      attYT
 kdfunction(id)SrtDrY,hiT
 kSize

          // ts.stlog  tog("c"attYT
 kdfu+ lo)SrtDrY +l "Dr loiT
 kSize               /// +  "Dr lois.idieiageX(Y +  "Dr lois.idmtsY +  "Dr lois.idmaxY 
        },is.idyT
 kd {}[]          is.usyT
 kSize = iT
 kSize         cor oDr
 kMapo{};
           r (var i in= is.iniaiageX(Y; i >ddis.idmtsY;
in= i -hiT
 kSize

               (th!i
 kMap[i])
                //ts.styT
 kd[ts.styT
 kd.lsngth] = i;               //t
 kMap[i]D=aue;
            },
       },
          r (vain= is.iniaiageX(Y; i <ddis.idmaxY;,in= i +hiT
 kSize

               (th!i
 kMap[i])
                //ts.styT
 kd[ts.styT
 kd.lsngth] = i;               //t
 kMap[i]D=aue;
            },
       },
          ts.styT
 kd.sort(is.idM;
.numericSort) ;         is.idsog  tog("coyT
 kdfur lois.idyT
 kd.join()
      },      * 
      * Byefault, ,he Drement tochfibarog ed elan  place othe 
 rcreen WiUseh      * ts dithod hl delimirre drhoriztainlncrantlh the Drement t ThPs of nn      * 0,0or IEe ineam {er iwh (tyouiwa tl deloedhe inag anrche loy axis       @rethod hatPaXCstraints       @reiam {Evt} thiLt;
 e Drnumberf grpixelshe Drement tochfimerlr tra Drlt;
      @reiam {Evt} thiRht poe lonumberf grpixelshe Drement tochfimerlr tra Dr      @rrht p      @reiam {Evt} thiT
 kSize opon(eat iam {er isr IEecifiefg ele atha Dr      @rements       * ould bemerlriT
 kSize pixelshathlareou.      @      attXCstraints function(id)Lt;
,niRht p,hiT
 kSize

          is.uslt;
Cstraints  nuparslIatd)Lt;
,n10
        },is.idrht pCstraints  nuparslIatd)Rht p,h10

          is.idmtsXn= is.iniaiageX(e - is.idlt;
Cstraints         },is.idmaxXn= is.iniaiageX(e +,is.idrht pCstraints ;          (thiT
 kSize

 /ts.sttTiXT
 kd(is.iniaiageX(e,hiT
 kSize
;,
          ts.stnstraintsXr=aue;
          is.idsog  tog("coiaiageX(e:+ lois.idieiageX(e +l" mtsX:r lois.idmtse +l                   maxX:r lois.idmaxX
      },      * 
      * Can}rslan  istraints anappliedr tra  in tance, .eiA so can}iwht
 kd      @rsie, o heyochf'i ecis= 
ndepende occthaaistraints  a his inreou.      @ ethod haccn}iCstraints a      @      ccn}iCstraints afunction(oD{
          is.inlog  tog("coCan}rg confiraints a"
        },is.idnstraintsXr=alse;
          is.isnstraintsYr=alse;
          is.isnan}rT
 kd(
      },      * 
      * Can}rslan  c
 k terselidntened,dor ite  in tance i
     @prthod hanan}rT
 kd      @      ccn}iT
 kdfunction(id{
          is.inlog  tog("coCan}rg cot
 kd"
        },is.idxT
 kd {}ll;
          is.idyT
 kd {}ll;
          is.idxT
 kSize = 0          is.usyT
 kSize = 0      },      * 
      * Byefault, ,he Drement tochfibarog ed elan  place othe 
 rcreen WiS &&      * ts di delimirre drvty icnlncrantlh the Drement t ThPs of nn0,0or IEe i      * eam {er iwh (tyouiwa tl deloedhe inag anrche lox axis       @rethod hatPaYCstraints       @reiam {Evt} thiUpoe lonumberf grpixelshe Drement tochfimerlr i      @reiam {Evt} thiDowofe lonumberf grpixelshe Drement tochfimerlrdowo
     * riam {Evt} thiT
 kSize opon(eat iam {er isr IEecifiefg ele atha Dr      @rements  ould bemerlriT
 kSize pixelshathlareou.      @      attYCstraints function(id)Uv,niDowoaniT
 kSize

          is.uslog  tog("c"attYCstraints fur loiUpo+  "r loiDowof+  "r loiT
 kSize
;         is.ustopCstraints  nuparslIatd)Uv,n10
        },is.idtoon mCstraints  nuparslIatd)Dowoan10

          is.idmtsYn= is.iniaiageX(Y - is.idtopCstraints         },is.idmaxYn= is.iniaiageX(Y +,is.idtoon mCstraints ;          (thiT
 kSize

 /ts.sttTiYT
 kd(is.iniaiageX(Y,hiT
 kSize
;,
          ts.stnstraintsYr=aue;
                   is.idsog  tog("coiaiageX(Y:+ lois.idieiageX(Y +l" mtsY:r lois.idmtsY +                    maxY:r lois.idmaxY
      },      * 
      * senttCstraints a mu ofbioualls oi(tyouimanuam isrerttion of Eddnement t       @rethod hasenttCstraints a      @      senttCstraints afunction(id{
           //is.idsog  tog("cosenttCstraints a"

          // Mn pininffsetPawh thtess tary          (this.us tiageX(e  this.idseiageX(e n== 0)
              //is.idsog  tog("coseia pe ixy" + lois.idieiageX(e +l "Dr lo                                //is.idieiageX(Y)              //is.idsog  tog("coss a pe ixy" + lois.idss ageX(e +l "Dr lo                                //is.idss ageX(Y)              // guredeo utrhow much ts di   ngoh drve
 o             r oDdx dd(is.idma pininOsetHe
 trus.idss ageX(e -ois.idieiageX(e : 0              r oDdy dd(is.idma pininOsetHe
 trus.idss ageX(Y -ois.idieiageX(Y : 0               ts.sttTiItiaPition
 (dx,Ddy

          // is is inttfirst onreouhweove entermcted e Drement t's pition
           lse {
              is.ustTiItiaPition
 ()        },
           (this.isnstraintsX{
              ts.sttTiXCstraints ( is.idlt;
Cstraints 
                                   ts.strht pCstraints 
                                   ts.stxT
 kSize        )        },
           (this.isnstraintsY{
              ts.sttTiYCstraints ( is.idtopCstraints 
                                   ts.sttoon mCstraints 
                                   ts.styT
 kSize         )        },
     },      * 
      * N at m ise inag anement toisfve
 onpixelobynpixel,hbhthweochfiecifiefr      * tsathitEmerlhlonumberf grpixelshathlareou.This isthod hasenolv,the ca      * lation
 fen th arve eniofs thupeli sue  s       @rethod hagerried      @reiam {Evt} thlid wse in arwa to acplace e 
 ject c      @reiam {Evt} } ddt
 kAay. fsort elaay. f grvidDrnpots a      @return {boi} elrhhtclost matied      @reivate
       @      gerriedfunction(idvid,dt
 kAay. {
            (th!i
 kAay. {
              // If c
 k terselidn not a tened,d,hTis stefft mnly th1npixel,h             // so weoturn thiserviduehre t in deus.             turn {blid            lse {
 (! i
 kAay. [0] >=ar d{
              // Tserviduehssrcaws"n t noe 
 rst onreck, so weoturn thiserrst o             // reck.             turn {bi
 kAay. [0]          
lse {
              r (var i i=0,flsn=i
 kAay. .lsngth; i<lsn; ++i{
                  r oDnt of= i +h1;               // (! i
 kAay. [nt o]D&&Di
 kAay. [nt o]D>=ar d{
                      r oD;ife1D=ar d -oi
 kAay. [i]        },  },        r oD;ife2r=au
 kAay. [nt o]D-blid        },  },        turn {b(;ife2r>D;ife1
 tru
 kAay. [i] : u
 kAay. [nt o]        },  },    

          },

             // Tserviduehssrcget:"n t noe 
 la onreck, so weoturn thiserla o             // reck.             turn {bi
 kAay. [i
 kAay. .lsngthD-b1]        },      
,      * 
      * toring} ithod h      * @thod hl dSing}       @return {bosing} idsing} spresentinaon of the Drddnjec      @       dSing} function(oD{
          turn {b(oagDrop obr lois.idi {      

 };
HOO.loaugnt t(HOO.util.ReagDrop o,AHOO.util.Event.gProvider

  * 
 @reents hmsedoDowoent.g
* edrscriion drProvideshacss treche louse idowovents . Tseruse idowovtoenot a alwaysasent, hinrarag enjrtyion o.
* epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hb4MsedoDowoent.g
* edrscriion drProvideshacss treche louse idowovents ,sber Ieue lomsedoDowoent.g t is fes d. turns g corse;
o l bechfcelre drag e  * epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hmsedoUvent.g
* edrscriion drFeEvdorromoitaid fagDrop oMgroen the 
 ag enjrtyion ohssrnedishs   * epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hb4SrtDrag(tent.g
* edrscriion drFeEvssber Ieue loartDrag(tEnts ,srurns g corse;
o l bechfcelre drartDrag(tvEnts .
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hsrtDrag(tent.g
* edrscriion drOcrsoroafis llruse isdowova fie inag anr sh eoldoh drbe thl i  Tserag anr sh eoldofault, h steivhs"n3rpixelsh thuse ismerlnt toorh1nfu ofrenfidf grholdg ele druse idowo. 
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hb4dDragEvent.g
* edrscriion drFeEvssber Ieue loeDragEvent.g. turns g corse;
o l bechfcel.
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents heDragEvent.g
* edrscriion drFeEvssothe 
 use iuevents oafis llrag enhddrbe th pial ttd (artDrag(tvfes d).
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hdg(tent.g
* edrscriion drOcrsoroentryruse imerlrent hoenilweag edg e.
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hb4agEvent.g
* edrscriion drFeEvssber Ieue lodg(tent.g.
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hialidDropEvent.g
* edrscriion drFeEvssen the 
 ag ed elject wss indgped, finrarlation
 fe atantaininshno tp thaget is.
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hb4agEveutent.g
* edrscriion drFeEvssber Ieue lodg(teutent.g
* epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hdg(teutent.g
* edrscriion drFeEvssen tha ag ed elject ws ovno  o
  ther tbaobject coe atahafie inenag(tEnr isreEv. 
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hdg(tenr ient.g
* edrscriion drOcrsoroen the 
 ag ed elject w rst onteractiosEth a anier thaget itle
 rag and drop object c  @ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hb4agEvevnaent.g
* edrscriion drFeEvssber Ieue lodg(tevnaent.g  @ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hdgEvevnaent.g
* edrscriion drFeEvssentryruse imerlrent hoenilweer tbarag and drop object c  @ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hb4agEvopEvent.g 
* edrscriion drFeEvssber Ieue lodg(topEvent.g
* epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hdg(topEvent.g
* edrscriion drFeEvssen the 
 ag ed elject wss indgped, f of nier t  @ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  })()  * 
  * A agDrop ob mrmemtinaon ofwse ine 
 link grements lfollowthe ca  *huse isrsor loring tharag e.  *h@sss ofDD  *h@ex=endsrHOO.util.ReagDrop o  *h@sstraiuct.

 @reiam {EvSing} idid e Dr of the Drlink grement to  @reiam {EvSing} idroup ine Droup ar threted",hagDrop ob ms
   @reiam {Evoect} grnfig opaobject containing configuredlb poatingbhtes  *      },  },    VidDrnsverty iosor IEDD:   *      },  },        scroll
 @  HOO.util.DDM;r=alction(e,, "wroup i,hnfigur

       (thi {
          is.dr pia,, "wroup i,hnfigur
      } };
 HOO.utex=end(HOO.util.ReaD,rHOO.util.ReagDrop o,
       * 
      * Wn ths theo ue, gne Dril.Ry tiauooma icnl iseriesheo scrollne Drbrowter      * windowhen thlrag osd drop obement toisfag ed elneaIEe inviewsir
 bndarary       @rDault, sheo ue,        @resverty y scroll
     @repe = olean}       @      acroll: ue, g       * 
      * Ss the lopots erf gfs theo uhlroibnce, obetwe the 
 link grement t's e ob      * lt;
 corn tbaod e Drlation
 fe Drement t w a ickPied      @rethod haauooOsetHe
     * riam {Evt} thigeX(e t DrXfcoordin tth the Drssied      @reiam {Evt} thigeX(Y t DrYfcoordin tth the Drssied      @       uooOsetHefunction(id)geX(e,higeX(Y

          r oDx dd)geX(e - is.idtrtDrgeX(e;         r oDy dd)geX(Y - is.idartDrgeX(Y        },is.idattataY Dx, y
        },// ts.stsog  tog("co uooOsetHeD = podfur loaCoordD+l "DdtaY fu+ lox +l "+ loy)      },      * 
       * Ss the lopots erf gfs t.  Youochficnl Ee  stdeEvct iseoor Ice e 
       *  gfs theo befinrariam icul oDlation
 f(e.g.,hre tf nn0,0o seeeisi&&      * tche loies erf g e 
 ject c, s hdh efinrHOO.utwidt i Slider
      @rethod hatPaataY       @reiam {Evt} thiDtaY X uhlroibnce, ofromoe lolt;
      @reiam {Evt} thiDtaY Y uhlroibnce, ofromoe lotoa      @      tPaataY function(id)DtaY X,hiDtaY Y{
          is.drdtaY X =hiDtaY X;         is.drdtaY Y =hiDtaY Y        },is.idsog  tog("codtaY X:r lois.iddtaY X +l "DdtaY Y:r lois.iddtaY Y
      },      * 
      * Ss the loag anement to tra Drlation
 f g e 
 use idowov IEcckPiaents ,s      * ma pining ele drrsor lolation
 fretedirlr tra Drlation
 f  fe Drement t       * tsathw a ickPied.  evnaridsue  sa (tyouiwa tl deplace e 
 ement toitrar      * lation
 fovhs"n t nowse ine 
 rsor lois       @rethod hatPaagEl()Poo      @reiam {Evt} thigeX(e t DrXfcoordin tth the Druse idowov IEag anent.g
     @reiam {Evt} thigeX(Y t DrYfcoordin tth the Druse idowov IEag anent.g
     @      attagEl()Posfunction(id)geX(e,higeX(Y

          // e tarst onreouhweodtra  i,hweoarehgoi
 o evick to acke susure         // e taement tohddrcs  pition
 g}           r oD = oDis.hatLoagEl()(
        },is.ididDgnElWh aMsedo(el,h)geX(e,higeX(Y
      },      * 
      * Ss the loement to tra Drlation
 f g e 
 use idowov IEcckPiaents ,s      * ma pining ele drrsor lolation
 fretedirlr tra Drlation
 f  fe Drement t       * tsathw a ickPied.  evnaridsue  sa (tyouiwa tl deplace e 
 ement toitrar      * lation
 fovhs"n t nowse ine 
 rsor lois       @rethod haidDgnElWh aMsedo
     @reiam {EvMLElement} elelre drement to trkerl      @reiam {Evt} thigeX(e t DrXfcoordin tth the Druse idowov IEag anent.g
     @reiam {Evt} thigeX(Y t DrYfcoordin tth the Druse idowov IEag anent.g
     @      idDgnElWh aMsedo: lction(oDel,h)geX(e,higeX(Y

          r oDoCoordDddis.idgerrget: Coordd)geX(e,higeX(Y
        },// ts.stsog  tog("co****idDgnElWh aMsedo fu+ loelrid " d,ur loaCoordD+l "D+ loelraty p.displa 
         }, (!lois.iddtaY SXYXY{
              r roaCoordD= [oCoord.x, oCoord.y]        },  },HOO.util.ReaomttTiXYDel,haCoord

              r oDntwLt;
 nuparslIatd,HOO.util.ReaomtgTiSry pDel,h"lt;
")an10 )              r oDntwTag  nuparslIatd,HOO.util.ReaomtgTiSry pDel,h"toa" )an10 )               ts.stdtaY SXYXY nu[DntwLt;
 - oCoord.x, ntwTag - oCoord.y ]          
lse {
              HOO.util.ReaomttTiSry pDel,h"lt;
", (oCoord.x lois.iddtaY SXYXY[0])D+l px;
              HOO.util.ReaomttTiSry pDel,h"toa",  (oCoord.y lois.iddtaY SXYXY[1])D+l px;
          

               },is.idched Pition
 (oCoord.x, oCoord.y);         r oDselfi=aus.i          tTimeout( 
nction(oD{
              tTlf. uooScroll.cnl hself, oCoord.x, oCoord.y, se. gfs tHeht p,hse. gfs tWidth
          
, 0

     
,      * 
      * Sav,the camo onEvce drsition ofso tsathweochfisentthe 
 rstraints ananh      *  
 k markofon-demanh.  Wevneedo trknowhe  saso tsathweochfichlcatedine 
      * numberf grpixelshe Drement to injgfs thrromoi anorigin dnpition
 .      @ ethod hached Pition
 
     @reiam {EigeX(e t Drrrent drxrsition of(opon(eat, is.i ju ofke ssniofso we
     @rdh 'tive ento lookniofup agnts)
     @reiam {EigeX(Y t Drrrent dryrsition of(opon(eat, is.i ju ofke ssniofso we
     @rdh 'tive ento lookniofup agnts)
     @      ched Pition
 function(id)geX(e,higeX(Y

           (thigeX(e{
              is.idss ageX(e nuigeX(e;             is.idss ageX(Y dd)geX(Y          
lse {
              r roaCoordD= HOO.util.ReaomtgTiXY(rs.hatLo(),
)              is.idss ageX(e nuaCoord[0]              is.idss ageX(Y ddaCoord[1]        },      
,      * 
      * Auoo-scrollne Drwindowhifhe 
 ag ed elject w h drbe thle
 onbeyo fie mo      * visib powindowhbndarary       @rethod haauooScroll
     @reiam {Evt} thxhe loag anement t'srxrsition o
     @reiam {Evt} thise inag anement t'sryrsition o
     @reiam {Evt} ththe moheht pf the Drdg anement t
     @reiam {Evt} thwne Drwidthf the Drdg anement t
     @reivate
       @      auooScrollfunction(idx, y, h,hw{
            (this.idacroll{
              // Tserickt toheht p             r roickt tHDddis.idM;
.t: Cckt tHeht p(

              // Tserickt towidth             r roickt tWDddis.idM;
.t: Cckt tWidth(

              // Tseramt acrolledEdowo
            r rostDddis.idM;
.t: ScrollTag(

              // Tseramt acrolledErht p             r roslDddis.idM;
.t: ScrollLt;
(

              // Lation
 f g e 
 toon mh the Drement t             r rotooDddh loy
              // Lation
 f g e 
 rht pf the Drement t             r rorht pf=hwn+ x
              // Tseroibnce, ofromoe lorsor lo tra Drtoon mh the Drvisib posrea,h             // adju oedEso tsathweodh 'tiscrollnifhe 
 rsor loisnbeyo fie m             // ement todg ancstraints a             r rotoBooDdd(ickt tHD+ostD-ryr- is.iddtaY Y

              // Tseroibnce, ofromoe lorsor lo tra Drrht pf the Drvisib posrea             r rotoRht pf=h(ickt tWD+oslD-rxr- is.iddtaY X

              // ts.stsog  tog("c "dxfu+ lox +l Dyfu+ loy +l Dhfu+ loh lo             // "dickt tHfu+ loickt tHD+o"dickt tWfu+ loickt tW lo             // "ds fur lostD+o"dslfur loslD+o"dtoo:n+ lobot lo             // "drht p:n+ lorht pf+o"dtoBoo" + loioBooD+o"dtoRht p:n+ lotoRht p

              // Howhclosto tra Dredgine 
 rsor lomu ofbiober Ieuwe scroll
            // r rot sh ef=h(docunt t all{
?n100 fu40              r oDt sh ef=h40               // Howhman  pixelsheo scrollnp tbauooscrollnop.This ishelpsoto riduceo             // clunky scrollg e. IEoisfverantPntionrlhlb utris.i ...liofneeds e  in
            // r dueheo befhht  t              r oDscrAmtf=h(docunt t all{
?n80 fu30               // Scrollsdowov we ararehneaIEe intoon mh the Drvisib pope ila fie mo             // jec ex=endsrba owne Drcreasm              (thobot >oickt tHD&&DioBooD<Dt sh ef

 /                 windowdacrollTo(st, stD+oscrAmt);/             

             // Scrollsup ifhe 
 windowhis acrolledEdowola fie moe ob g e 
 ject c             // goenolb veie moe obborder              (thoy < stD&&Dst >o0D&&Dyr- soD<Dt sh ef

 /                 windowdacrollTo(st, stD-oscrAmt);/             

             // Scrollsrht pfig e 
 jecoisnbeyo fie msrht pfborderla fie morsor lois             // neaIEe intorder.              (thorht pf>oickt tW &&DioRht pf<Dt sh ef

 /                 windowdacrollTo(stD+oscrAmt, st);/             

             // Scrollslt;
 ifhe 
 windowhh drbe thacrolledE tra Drrht pfa fie mojec             // ex=endsrpa onr lolt;
bborder              (thox < stD&&Dsl >o0D&&Dxr- sl <Dt sh ef

 /                 windowdacrollTo(stD-oscrAmt, st);             

        

    
,      *       * Ss thup nfig opopon(ea acifiedcr tra  insss o. evnaridso      @rHOO.util.ReagDrop o,
bhthnl Evnasn(ea  the i Ethod hl hroughie mo      * in  tince, ochninfarehualls       @      applyCfig ofunction(oD{
          HOO.util.ReaD.sup tsss o.applyCfig o.cnl he i 

         is.idscrolls=this.isnstg o.scrolls!=trlse;


     
,      *       * Ent hatsathfeEvsssviocl dee inenMsedoDowovents .  evnaridss       @rHOO.util.ReagDrop o.      @      b4MsedoDowo: lction(oDe{ }       cois.idattSrtDrPition
 (

         // ts.strenttCstraints a(
        },is.idiuooOsetHe(HOO.util.Reent.g.gergeX(e(e)g                              HOO.util.Reent.g.gergeX(Y(e){;     
,      *       * Ent hatsathfeEvsssviocl dee inenDg anent.g.  evnaridss       @rHOO.util.ReagDrop o.      @      b4agDr: lction(oDe{ }       cois.idattagEl()Pos(HOO.util.Reent.g.gergeX(e(e)g                              HOO.util.Reent.g.gergeX(Y(e){;     
,       dSing} function(oD{
          turn {b(oaDbr lois.idi {      

     //////////////////////////////////////////////////////////////////////////     // Debuedg e ygagDrop obents ine atanhfibarovnaridden     //////////////////////////////////////////////////////////////////////////     /      artDrag(tfunction(idx, y

          is.uslog  tog("cis.idi . dSing} D{
D+o"dsrtDrag(t"{;     
,      enDg a: lction(oDe{ }       cois.idlog  tog("cis.idi . dSing} D{
+o"denDg a"{;     
,      enDg aenr i: lction(oDeisi {
          is.drlog  tog("cis.idi . dSing} D{
+o"denDg aenr i: + lo) {      
,      enDg aevna: lction(oDeisi {
          is.drlog  tog("cis.idi . dSing} D{
+o"denDg aevna: + lo) {      
,      enDg aeut: lction(oDeisi {
          is.drlog  tog("cis.idi . dSing} D{
+o"denDg aeu :n+ lo) {      
,      enDg aop o: lction(oDeisi {
          is.drlog  tog("cis.idi . dSing} D{
+o"denDg aop o: + lo) {      
,      eDragEv: lction(oDe{ }       cois.idlog  tog("cis.idi . dSing} D{
+o"deDragEv"{      

     @   * 
 @reents hmsedoDowoent.g
* edrscriion drProvideshacss treche louse idowovents . Tseruse idowovtoenot a alwaysasent, hinrarag enjrtyion o.
* epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hb4MsedoDowoent.g
* edrscriion drProvideshacss treche louse idowovents ,sber Ieue lomsedoDowoent.g t is fes d. turns g corse;
o l bechfcelre drag e  * epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hmsedoUvent.g
* edrscriion drFeEvdorromoitaid fagDrop oMgroen the 
 ag enjrtyion ohssrnedishs   * epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hb4SrtDrag(tent.g
* edrscriion drFeEvssber Ieue loartDrag(tEnts ,srurns g corse;
o l bechfcelre drartDrag(tvEnts .
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hsrtDrag(tent.g
* edrscriion drOcrsoroafis llruse isdowova fie inag anr sh eoldoh drbe thl i  Tserag anr sh eoldofault, h steivhs"n3rpixelsh thuse ismerlnt toorh1nfu ofrenfidf grholdg ele druse idowo. 
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hb4dDragEvent.g
* edrscriion drFeEvssber Ieue loeDragEvent.g. turns g corse;
o l bechfcel.
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents heDragEvent.g
* edrscriion drFeEvssothe 
 use iuevents oafis llrag enhddrbe th pial ttd (artDrag(tvfes d).
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hdg(tent.g
* edrscriion drOcrsoroentryruse imerlrent hoenilweag edg e.
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hb4agEvent.g
* edrscriion drFeEvssber Ieue lodg(tent.g.
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hialidDropEvent.g
* edrscriion drFeEvssen the 
 ag ed elject wss indgped, finrarlation
 fe atantaininshno tp thaget is.
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hb4agEveutent.g
* edrscriion drFeEvssber Ieue lodg(teutent.g
* epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hdg(teutent.g
* edrscriion drFeEvssen tha ag ed elject ws ovno  o
  ther tbaobject coe atahafie inenag(tEnr isreEv. 
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hdg(tenr ient.g
* edrscriion drOcrsoroen the 
 ag ed elject w rst onteractiosEth a anier thaget itle
 rag and drop object c  @ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hb4agEvevnaent.g
* edrscriion drFeEvssber Ieue lodg(tevnaent.g  @ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hdgEvevnaent.g
* edrscriion drFeEvssentryruse imerlrent hoenilweer tbarag and drop object c  @ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hb4agEvopEvent.g 
* edrscriion drFeEvssber Ieue lodg(topEvent.g
* epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hdg(topEvent.g
* edrscriion drFeEvssen the 
 ag ed elject wss indgped, f of nier t  @ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  })  * 
  * A agDrop ob mrmemtinaon oftsathinserawhantempty,bborderedEdivntero
 * tsisdocunt tatsathfollowthe carsor loring thag enjrtyion os.eiAonr loreouhof
 * tsisickPigne Drfm {eEdivntsasenizedE tra Drdeounsn(ea  the 
 link grml e
 @rements ,nd drle
 on tra Drextiorlation
 f g e 
 link grement t.  *
 @rReferefcetreche lo"fm {e" ement toreferreche losg t poproxyrement to sat
 @rw a ireatedheo befag ed elinrplace ofhnl EDDProxyrement tssothe 

 * eage.  *
 @r@sss ofDDProxy  *h@ex=endsrHOO.util.ReaD  *h@sstraiuct.

 @reiam {EvSing} idid e Dr of the Drlink grml erement t  @reiam {EvSing} idroup ine Droup ar threted",hagDrop object ws  @reiam {Evoect} grnfig opaobject containing configuredlb poatingbhtes  *      },  },    VidDrnsverty iosor IEDDProxyrinraddion ofeche ostoinrDg aop o:   *      },  },       senizeFm {e,oies erFm {e,odg(telI)  @  HOO.util.DDM;Proxyr=alction(e,, "wroup i,hnfigur

       (thi {
          is.dr pia,, "wroup i,hnfigur
          is.dr piaFm {e();/     } };
 * 
  * Tseraault, hag enfm {eEdivntd
 @resverty y HOO.util.DDM;Proxy.dg(telI)  @ epe = Sing}   @ esnaonc  @  HOO.util.DDM;Proxy.dg(telI)r=a"ygddfdiv";
 HOO.utex=end(HOO.util.ReaDProxy,rHOO.util.ReaD,
       * 
      * Byefault,  weotunizeie inag anfm {eEeo befe losamantizeiashe Drement t      *  arwa to acag an(ts is into t ine Drfm {eEefft m).  Wevnhfirn {bipf tf      * if  arwa toaD;ifeent drbehavior       @resverty y senizeFm {e      @repe = olean}       @      senizeFm {e: ue, g      * 
      * Byefault,  e Drfm {eEi  pition
  grextiolyowse ine 
 ag anement tois, so      *  are ise carsor lojgfs thprovidedobynHOO.util.ReaD.eiAnier thopon(e tsathworkofonlyoif      * youidoot a ve enrstraints anothe 
 jecoisnto ve ent inag anfm {eEies ered      @rarndarse carsor l WiS &&ies erFm {eheo ue, or IEe  stefft m       @resverty y ies erFm {e      @repe = olean}       @      ies erFm {e:orse;
,      * 
      * Createthe loproxyrement toif itvtoenot a yei ecis=      @rethod hacreateFm {e      @      ireateFm {efunction(oD{
          r oDself=a  i,hbody=docunt t body         }, (!lobody  thobody.rst oChild{
              tTimeout( 
unction(oD{
  tTlf.ireateFm {e();/}, 50 )              rurns         },
          r oD;iv=is.hatLoagEl()(
,hm.g=HOO.util.Reaom         }, (!lo;iv{
              divn   nudocunt t ireateement t("div")              div.idDddis.iddg(telI)              r oDs  nudiv.aty p               s.sition of  nu"absolhte";             s.visib.Ry tinu"hidden";             s.rsor lon   nu"le
 ";             s.borderln   nu"2px sodDrn#aaa";             s.zIndexln   nu999;             s.heht pfn   nu"25px";             s.widthffn   nu"25px";              r oD_data nudocunt t ireateement t('div')              aomttTiSry pD_data, 'heht p', '100%')              aomttTiSry pD_data, 'width', '100%')              * 
             * If c loproxyrement tohddrno backgrndar-colorgne DnhTis strstrideredEeche lo"ainnsrent ="obynIs ernei Explorer.             * Sie, oTis st"ainnsrent ="otn the 
 ents inps ohe roughiito tra Drifm {eEba ow.             * So ireatg thar"fake" divntesinehisyeproxyrement tod drgivg thTisa backgrndar-colorgne DnhtTitg thTis tran             * opacy ti th0,hTisaed,}iwhtoot a befe lre,ohowentr IEostil Ee  nkine ataTis stsohe 
 ents innentr ps ohe rough.             */             aomttTiSry pD_data, 'backgrndar-color', '#ccc')              aomttTiSry pD_data, 'opacy t', '0')              div.aed,ndChildD_data

              // aed,ndChildanhfib own arIEoifhialok grsviocl dee inwindowhloadnent.g
            // enilwerendeng thartle
  WiItEi  pitsib poe lrefarehier thsiesavioin
            // tsathwouldanhe ise isnto veed,niashwell.             body.inseraBer Ie(div,hbody.rst oChild{        },      
,      * 
      * Ipial lizion
 fr IEe inag anfm {eEement t ThMu ofbioualls oithe 

     * sstraiuct.
 ofhnl Esubcss of       @rethod ha piaFm {e      @       piaFm {efunction(id{
          is.inireateFm {e();     
,      applyCfig ofunction(oD{
          //is.idsog  tog("coM;ProxyrapplyCfig o")          HOO.util.DDM;Proxy.sup tsss o.applyCfig o.cnl he i 

          is.insenizeFm {es=this.isnstg o.senizeFm {es!=trlse;


         is.inies erFm {eh=this.isnstg o.ies erFm {e

         is.idsLoagEl()Idhis.isnstg o.dg(telI)r thHOO.util.DDM;Proxy.dg(telI)

     
,      * 
      * RenizesEe inag anfm {eE tra Drdeounsn(ea  the 
 ickPied ject c, sition os       @ripf verhe 
 ject c, s drfin dlyodispla snio      @rethod hashowFm {e      @reiam {Evt} thigeX(e XEcckPiasition o
     @reiam {Evt} thigeX(Y YEcckPiasition o
     @reivate
       @      ahowFm {efunction(id)geX(e,higeX(Y

          r oD = oDis.hatLo()(
        },r oD;g(tel oDis.hatLoagEl()(
        },r oDs =D;g(tel.aty p           is.ha_senizeProxy(

           (this.isnes erFm {e

              ts.sttTiataY D Math.rndar(parslIatds.width, n10
/2)g                             Math.rndar(parslIatds.heht p,h10
/2) )        },
          is.idattagEl()Pos()geX(e,higeX(Y
           HOO.util.DDMomttTiSry pD;g(tel, "visib.Ry t", "visible");/     },      * 
      * Tsyeproxyrisnauooma icnl issenizedE tra Drdeounsn(ea  the 
 link g      * ement t wn thlrag os in tial ttd, unls trsenizeFm {es sts theo lse;
      @rethod ha_senizeProxy
     @reivate
       @      _senizeProxyfunction(oD{
           (this.issenizeFm {e{
              r roDOM    = HOO.util.Reaom              r oD =     = is.hatLo()(
        },    r oD;g(tel oDis.hatLoagEl()(
         },    r oDb
 nuparslIatd,DOMtgTiSry pD;g(tel, "borderTopWidth"    ),n10
        },    r oDbr nuparslIatd,DOMtgTiSry pD;g(tel, "borderRht pWidth"  ),n10
        },    r oDbb nuparslIatd,DOMtgTiSry pD;g(tel, "borderBoon mWidth" ),n10
        },    r oDbl nuparslIatd,DOMtgTiSry pD;g(tel, "borderLt;
Width"   )an10

               (thisNaN(bt){
  b
 nu0; 

          }, (thisNaN(br){
  br nu0; 

          }, (thisNaN(bb){
  bb nu0; 

          }, (thisNaN(bl){
  bl nu0; 

             ts.stsog  tog("coproxyrnize:n+ loboD+o"dn+ lobrD+o"d+ lobbD+o"d+ lobl

              r oDntwWidthff= Math.max(0,hse. gfs tWidth  -obrD-obl

                                                                                                        r oDntwHeht pf= Math.max(0,hse. gfs tHeht pf-oboD-obb)               ts.stsog  tog("coRenizg thproxyrement t")               DOMttTiSry pD ;g(tel, "width",  ntwWidthff+l px; )              DOMttTiSry pD ;g(tel, "heht p",DntwHeht pf+l px; )                
,      */rovnaridesrHOO.util.ReagDrop o     b4MsedoDowo: lction(oDe{ }       cois.idattSrtDrPition
 (

         r oDx ddHOO.util.Reent.g.gergeX(e(e);         r oDy ddHOO.util.Reent.g.gergeX(Y(e)        },is.idiuooOsetHe(x, y
           //his isnhe isEe inauooscrollncodeE trkkPia gf, enich mennsnauooscrollncan         //hveed,nisviocl dee inick tor IEarvidDrnag enhdndle.         //his.idattagEl()Pos(x, y
      
,      */rovnaridesrHOO.util.ReagDrop o     b4SrtDrag(tfunction(idx, y

          // showne Drag anfm {e       },is.idlog  tog("c"artDrrag anshownfm {e,oxfu+ lox +l ,Dyfu+ loy

         is.idshowFm {e(x, y
      
,      */rovnaridesrHOO.util.ReagDrop o     b4EDragEv: lction(oDe{ }       cois.idlog  tog("cis.idi D+o"dt4EDragEv")          HOO.util.DDMomttTiSry pDis.hatLoagEl()(
,h"visib.Ry t", "hidden");/     },      */rovnaridesrHOO.util.ReagDrop o     */rByefault,  weotryo trkerlhe Drement tr tra Drla onlation
 f g e 
 fm {e.       // is is inso tsathtseraault, hbehavior mirrorine ata g HOO.util.ReaD.ei     eDragEv: lction(oDe{ }       cor roDOM = HOO.util.Reaom          is.idlog  tog("cis.idi D+o"deDragEv")          r rol = oDis.hatLo()(
        },r oD; = oDis.hatLoagEl()(
           // Showne Drag anfm {eobriefly so weonhfigeisi&s pition
          // delraty p.visib.Ry tinu"";         DOMttTiSry pDdel, "visib.Ry t", "");/          // Hinehisyelink grement tober Ieue loms ento geisarndarsa Safari          // rendeng thbug.         //lelraty p.visib.Ry tinu"hidden";         DOMttTiSry pDlel, "visib.Ry t", "hidden");/         HOO.util.DDMDM.ms eTo()(lel, del

         //delraty p.visib.Ry tinu"hidden";         DOMttTiSry pDdel, "visib.Ry t", "hidden");/         //lelraty p.visib.Ry tinu"";         DOMttTiSry pDlel, "visib.Ry t", "");/     },       dSing} function(oD{
          turn {b(oaDProxyrr lois.idi {      

* 
 @reents hmsedoDowoent.g
* edrscriion drProvideshacss treche louse idowovents . Tseruse idowovtoenot a alwaysasent, hinrarag enjrtyion o.
* epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hb4MsedoDowoent.g
* edrscriion drProvideshacss treche louse idowovents ,sber Ieue lomsedoDowoent.g t is fes d. turns g corse;
o l bechfcelre drag e  * epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hmsedoUvent.g
* edrscriion drFeEvdorromoitaid fagDrop oMgroen the 
 ag enjrtyion ohssrnedishs   * epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hb4SrtDrag(tent.g
* edrscriion drFeEvssber Ieue loartDrag(tEnts ,srurns g corse;
o l bechfcelre drartDrag(tvEnts .
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hsrtDrag(tent.g
* edrscriion drOcrsoroafis llruse isdowova fie inag anr sh eoldoh drbe thl i  Tserag anr sh eoldofault, h steivhs"n3rpixelsh thuse ismerlnt toorh1nfu ofrenfidf grholdg ele druse idowo. 
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hb4dDragEvent.g
* edrscriion drFeEvssber Ieue loeDragEvent.g. turns g corse;
o l bechfcel.
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reenes heDragEvent.g
* edrscriion drFeEvssothe 
 use iuevents oafis llrag enhddrbe th pial ttd (artDrag(tvfes d).
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   * 
 @reents hdg(tent.g
* edrscriion drOcrsoroentryruse imerlrent hoenilweag edg e.
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hb4agEvent.g
* edrscriion drFeEvssber Ieue lodg(tent.g.
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hialidDropEvent.g
* edrscriion drFeEvssen the 
 ag ed elject wss indgped, finrarlation
 fe atantaininshno tp thaget is.
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hb4agEveutent.g
* edrscriion drFeEvssber Ieue lodg(teutent.g
* epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hdg(teutent.g
* edrscriion drFeEvssen tha ag ed elject ws ovno  o
  ther tbaobject coe atahafie inenag(tEnr isreEv. 
@ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hdg(tenr ient.g
* edrscriion drOcrsoroen the 
 ag ed elject w rst onteractiosEth a anier thaget itle
 rag and drop object c  @ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hb4agEvevnaent.g
* edrscriion drFeEvssber Ieue lodg(tevnaent.g  @ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hdg(tevnaent.g
* edrscriion drFeEvssentryruse imerlrent hoenilweer tbarag and drop object c  @ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hb4agEvopEvent.g 
* edrscriion drFeEvssber Ieue lodg(topEvent.g
* epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@  * 
 @reents hdg(topEvent.g
* edrscriion drFeEvssen the 
 ag ed elject wss indgped, f of nier t  @ epe = HOO.util.ReCu oomEnt haSee <a href="HOO.util.Evement t ml e#addLtersn e">ement t addLtersn e</a> r (vus 
 inr Imaon of  flis=eng cor IEe  stents .
@   })  * 
  * A agDrop ob mrmemtinaon oftsathtoenot a merl,
bhthnhfibararop ob
 * tget i.  Youowouldat ine Drsamansent, hbyrnimrmy omiitg thTmrmemtinaon of
 * r IEe inents hcnl backs,
bhthe  stway weotuduceoisyepross tg confspf the Dr
 @rents hltersn ela fie mornl backs.  *h@sss ofDDrget:   *h@ex=endsrHOO.util.ReagDrop o   *h@sstraiuct.

 @reiam {EvSing} idid e Dr of the Drement to satrisna tp thaget i  @reiam {EvSing} idroup ine Droup ar threted",hagDrop object ws  @reiam {Evoect} grnfig opaobject containing configuredlb poatingbhtes  *      },  },     VidDrnsverty iosor IEDDrget: rinraddion ofeche ostoinr  *      },  },     Dg aop o:   *      },  },       ot ne  @  HOO.util.DDM;rget: r=alction(e,, "wroup i,hnfigur

       (thi {
          is.dr piarget: ,, "wroup i,hnfigur
      } };
 // HOO.util.DDM;rget: .svetope = =DntwrHOO.util.ReagDrop o(
  HOO.utex=end(HOO.util.ReaDrget: ,rHOO.util.ReagDrop o,
       dSing} function(oD{
          turn {b(oaDrget: rr lois.idi {      

}
  HOO.utregtersr("ag etp t",rHOO.util.ReagDrop oMgr,
 vnasn(e: "2.9.0",
bhild: "2800"}
  