/*
Copyright (c) 2011, Yahoo! Inc. All rights reserved.
Code licensed under the BSD License:
http://developer.yahoo.com/yui/license.html
version: 2.9.0
*/
(function () {
    var Dom = YAHOO.util.Dom,
        Event = YAHOO.util.Event,
        Lang = YAHOO.lang,
        Widget = YAHOO.widget;



/**
 * The treeview widget is a generic tree building tool.
 * @module treeview
 * @title TreeView Widget
 * @requires yahoo, dom, event
 * @optional animation, json, calendar
 * @namespace YAHOO.widget
 */

/**
 * Contains the tree view state data and the root node.
 *
 * @class TreeView
 * @uses YAHOO.util.EventProvider
 * @constructor
 * @param {string|HTMLElement} id The id of the element, or the element itself that the tree will be inserted into.
 *        Existing markup in this element, if valid, will be used to build the tree
 * @param {Array|Object|String}  oConfig (optional)  If present, it will be used to build the tree via method <a href="#method_buildTreeFromObject">buildTreeFromObject</a>
 *
 */
YAHOO.widget.TreeView = function(id, oConfig) {
    if (id) { this.init(id); }
    if (oConfig) {
        this.buildTreeFromObject(oConfig);
    } else if (Lang.trim(this._el.innerHTML)) {
        this.buildTreeFromMarkup(id);
    }
};

var TV = Widget.TreeView;

TV.prototype = {

    /**
     * The id of tree container element
     * @property id
     * @type String
     */
    id: null,

    /**
     * The host element for this tree
     * @property _el
     * @private
     * @type HTMLelement
     */
    _el: null,

     /**
     * Flat collection of all nodes in this tree.  This is a sparse
     * array, so the length property can't be relied upon for a
     * node count for the tree.
     * @property _nodes
     * @type Node[]
     * @private
     */
    _nodes: null,

    /**
     * We lock the tree control while waiting for the dynamic loader to return
     * @property locked
     * @type boolean
     */
    locked: false,

    /**
     * The animation to use for expanding children, if any
     * @property _expandAnim
     * @type string
     * @private
     */
    _expandAnim: null,

    /**
     * The animation to use for collapsing children, if any
     * @property _collapseAnim
     * @type string
     * @private
     */
    _collapseAnim: null,

    /**
     * The current number of animations that are executing
     * @property _animCount
     * @type int
     * @private
     */
    _animCount: 0,

    /**
     * The maximum number of animations to run at one time.
     * @property maxAnim
     * @type int
     */
    maxAnim: 2,

    /**
     * Whether there is any subscriber to dblClickEvent
     * @property _hasDblClickSubscriber
     * @type boolean
     * @private
     */
    _hasDblClickSubscriber: false,

    /**
     * Stores the timer used to check for double clicks
     * @property _dblClickTimer
     * @type window.timer object
     * @private
     */
    _dblClickTimer: null,

  /**
     * A reference to the Node currently having the focus or null if none.
     * @property currentFocus
     * @type YAHOO.widget.Node
     */
    currentFocus: null,

    /**
    * If true, only one Node can be highlighted at a time
    * @property singleNodeHighlight
    * @type boolean
    * @default false
    */

    singleNodeHighlight: false,

    /**
    * A reference to the Node that is currently highlighted.
    * It is only meaningful if singleNodeHighlight is enabled
    * @property _currentlyHighlighted
    * @type YAHOO.widget.Node
    * @default null
    * @private
    */

    _currentlyHighlighted: null,

    /**
     * Sets up the animation for expanding children
     * @method setExpandAnim
     * @param {string} type the type of animation (acceptable values defined
     * in YAHOO.widget.TVAnim)
     */
    setExpandAnim: function(type) {
        this._expandAnim = (Widget.TVAnim.isValid(type)) ? type : null;
    },

    /**
     * Sets up the animation for collapsing children
     * @method setCollapseAnim
     * @param {string} type of animation (acceptable values defined in
     * YAHOO.widget.TVAnim)
     */
    setCollapseAnim: function(type) {
        this._collapseAnim = (Widget.TVAnim.isValid(type)) ? type : null;
    },

    /**
     * Perform the expand animation if configured, or just show the
     * element if not configured or too many animations are in progress
     * @method animateExpand
     * @param el {HTMLElement} the element to animate
     * @param node {YAHOO.util.Node} the node that was expanded
     * @return {boolean} true if animation could be invoked, false otherwise
     */
    animateExpand: function(el, node) {

        if (this._expandAnim && this._animCount < this.maxAnim) {
            // this.locked = true;
            var tree = this;
            var a = Widget.TVAnim.getAnim(this._expandAnim, el,
                            function() { tree.expandComplete(node); });
            if (a) {
                ++this._animCount;
                this.fireEvent("animStart", {
                        "node": node,
                        "type": "expand"
                    });
                a.animate();
            }

            return true;
        }

        return false;
    },

    /**
     * Perform the collapse animation if configured, or just show the
     * element if not configured or too many animations are in progress
     * @method animateCollapse
     * @param el {HTMLElement} the element to animate
     * @param node {YAHOO.util.Node} the node that was expanded
     * @return {boolean} true if animation could be invoked, false otherwise
     */
    animateCollapse: function(el, node) {

        if (this._collapseAnim && this._animCount < this.maxAnim) {
            // this.locked = true;
            var tree = this;
            var a = Widget.TVAnim.getAnim(this._collapseAnim, el,
                            function() { tree.collapseComplete(node); });
            if (a) {
                ++this._animCount;
                this.fireEvent("animStart", {
                        "node": node,
                        "type": "collapse"
                    });
                a.animate();
            }

            return true;
        }

        return false;
    },

    /**
     * Function executed when the expand animation completes
     * @method expandComplete
     */
    expandComplete: function(node) {
        --this._animCount;
        this.fireEvent("animComplete", {
                "node": node,
                "type": "expand"
            });
        // this.locked = false;
    },

    /**
     * Function executed when the collapse animation completes
     * @method collapseComplete
     */
    collapseComplete: function(node) {
        --this._animCount;
        this.fireEvent("animComplete", {
                "node": node,
                "type": "collapse"
            });
        // this.locked = false;
    },

    /**
     * Initializes the tree
     * @method init
     * @parm {string|HTMLElement} id the id of the element that will hold the tree
     * @private
     */
    init: function(id) {
        this._el = Dom.get(id);
        this.id = Dom.generateId(this._el,"yui-tv-auto-id-");

    /**
         * When animation is enabled, this event fires when the animation
         * starts
         * @event animStart
         * @type CustomEvent
         * @param {YAHOO.widget.Node} oArgs.node the node that is expanding/collapsing
         * @param {String} oArgs.type the type of animation ("expand" or "collapse")
         */
        this.createEvent("animStart", this);

        /**
         * When animation is enabled, this event fires when the animation
         * completes
         * @event animComplete
         * @type CustomEvent
         * @param {YAHOO.widget.Node} oArgs.node the node that is expanding/collapsing
         * @param {String} oArgs.type the type of animation ("expand" or "collapse")
         */
        this.createEvent("animComplete", this);

        /**
         * Fires when a node is going to be collapsed.  Return false to stop
         * the collapse.
         * @event collapse
         * @type CustomEvent
         * @param {YAHOO.widget.Node} node the node that is collapsing
         */
        this.createEvent("collapse", this);

        /**
         * Fires after a node is successfully collapsed.  This event will not fire
         * if the "collapse" event was cancelled.
         * @event collapseComplete
         * @type CustomEvent
         * @param {YAHOO.widget.Node} node the node that was collapsed
         */
        this.createEvent("collapseComplete", this);

        /**
         * Fires when a node is going to be expanded.  Return false to stop
         * the collapse.
         * @event expand
         * @type CustomEvent
         * @param {YAHOO.widget.Node} node the node that is expanding
         */
        this.createEvent("expand", this);

        /**
         * Fires after a node is successfully expanded.  This event will not fire
         * if the "expand" event was cancelled.
         * @event expandComplete
         * @type CustomEvent
         * @param {YAHOO.widget.Node} node the node that was expanded
         */
        this.createEvent("expandComplete", this);

    /**
         * Fires when the Enter key is pressed on a node that has the focus
         * @event enterKeyPressed
         * @type CustomEvent
         * @param {YAHOO.widget.Node} node the node that has the focus
         */
        this.createEvent("enterKeyPressed", this);

    /**
         * Fires when the label in a TextNode or MenuNode or content in an HTMLNode receives a Click.
    * The listener may return false to cancel toggling and focusing on the node.
         * @event clickEvent
         * @type CustomEvent
         * @param oArgs.event  {HTMLEvent} The event object
         * @param oArgs.node {YAHOO.widget.Node} node the node that was clicked
         */
        this.createEvent("clickEvent", this);

    /**
         * Fires when the focus receives the focus, when it changes from a Node
    * to another Node or when it is completely lost (blurred)
         * @event focusChanged
         * @type CustomEvent
         * @param oArgs.oldNode  {YAHOO.widget.Node} Node that had the focus or null if none
         * @param oArgs.newNode {YAHOO.widget.Node} Node that receives the focus or null if none
         */

        this.createEvent('focusChanged',this);

    /**
         * Fires when the label in a TextNode or MenuNode or content in an HTMLNode receives a double Click
         * @event dblClickEvent
         * @type CustomEvent
         * @param oArgs.event  {HTMLEvent} The event object
         * @param oArgs.node {YAHOO.widget.Node} node the node that was clicked
         */
        var self = this;
        this.createEvent("dblClickEvent", {
            scope:this,
            onSubscribeCallback: function() {
                self._hasDblClickSubscriber = true;
            }
        });

    /**
         * Custom event that is fired when the text node label is clicked.
         *  The node clicked is  provided as an argument
         *
         * @event labelClick
         * @type CustomEvent
         * @param {YAHOO.widget.Node} node the node clicked
    * @deprecated use clickEvent or dblClickEvent
         */
        this.createEvent("labelClick", this);

    /**
     * Custom event fired when the highlight of a node changes.
     * The node that triggered the change is provided as an argument:
     * The status of the highlight can be checked in
     * <a href="YAHOO.widget.Node.html#property_highlightState">nodeRef.highlightState</a>.
     * Depending on <a href="YAHOO.widget.Node.html#property_propagateHighlight">nodeRef.propagateHighlight</a>, other nodes might have changed
     * @event highlightEvent
     * @type CustomEvent
     * @param node {YAHOO.widget.Node} the node that started the change in highlighting state
    */
        this.createEvent("highlightEvent",this);


        this._nodes = [];

        // store a global reference
        TV.trees[this.id] = this;

        // Set up the root node
        this.root = new Widget.RootNode(this);

        var LW = Widget.LogWriter;



        if (this._initEditor) {
            this._initEditor();
        }

        // YAHOO.util.Event.onContentReady(this.id, this.handleAvailable, this, true);
        // YAHOO.util.Event.on(this.id, "click", this.handleClick, this, true);
    },

    //handleAvailable: function() {
        //var Event = YAHOO.util.Event;
        //Event.on(this.id,
    //},
 /**
     * Builds the TreeView from an object.
     * This is the method called by the constructor to build the tree when it has a second argument.
     *  A tree can be described by an array of objects, each object corresponding to a node.
     *  Node descriptions may contain values for any property of a node plus the following extra properties: <ul>
     * <li>type:  can be one of the following:<ul>
     *    <li> A shortname for a node type (<code>'text','menu','html'</code>) </li>
     *    <li>The name of a Node class under YAHOO.widget (<code>'TextNode', 'MenuNode', 'DateNode'</code>, etc) </li>
     *    <li>a reference to an actual class: <code>YAHOO.widget.DateNode</code></li>
     * </ul></li>
     * <li>children: an array containing further node definitions</li></ul>
     * A string instead of an object will produce a node of type 'text' with the given string as its label.
     * @method buildTreeFromObject
     * @param  oConfig {Array|Object|String}  array containing a full description of the tree.
     *        An object or a string will be turned into an array with the given object or string as its only element.
     *
     */
    buildTreeFromObject: function (oConfig) {
        var build = function (parent, oConfig) {
            var i, item, node, children, type, NodeType, ThisType;
            for (i = 0; i < oConfig.length; i++) {
                item = oConfig[i];
                if (Lang.isString(item)) {
                    node = new Widget.TextNode(item, parent);
                } else if (Lang.isObject(item)) {
                    children = item.children;
                    delete item.children;
                    type = item.type || 'text';
                    delete item.type;
                    switch (Lang.isString(type) && type.toLowerCase()) {
                        case 'text':
                            node = new Widget.TextNode(item, parent);
                            break;
                        case 'menu':
                            node = new Widget.MenuNode(item, parent);
                            break;
                        case 'html':
                            node = new Widget.HTMLNode(item, parent);
                            break;
                        default:
                            if (Lang.isString(type)) {
                                NodeType = Widget[type];
                            } else {
                                NodeType = type;
                            }
                            if (Lang.isObject(NodeType)) {
                                for (ThisType = NodeType; ThisType && ThisType !== Widget.Node; ThisType = ThisType.superclass.constructor) {}
                                if (ThisType) {
                                    node = new NodeType(item, parent);
                                } else {
                                }
                            } else {
                            }
                    }
                    if (children) {
                        build(node,children);
                    }
                } else {
                }
            }
        };
        if (!Lang.isArray(oConfig)) {
            oConfig = [oConfig];
        }


        build(this.root,oConfig);
    },
/**
     * Builds the TreeView from existing markup.   Markup should consist of &lt;UL&gt; or &lt;OL&gt; elements containing &lt;LI&gt; elements.
     * Each &lt;LI&gt; can have one element used as label and a second optional element which is to be a &lt;UL&gt; or &lt;OL&gt;
     * containing nested nodes.
     * Depending on what the first element of the &lt;LI&gt; element is, the following Nodes will be created: <ul>
     *           <li>plain text:  a regular TextNode</li>
     *           <li>anchor &lt;A&gt;: a TextNode with its <code>href</code> and <code>target</code> taken from the anchor</li>
     *           <li>anything else: an HTMLNode</li></ul>
     * Only the first  outermost (un-)ordered list in the markup and its children will be parsed.
     * Nodes will be collapsed unless  an  &lt;LI&gt;  tag has a className called 'expanded'.
     * All other className attributes will be copied over to the Node className property.
     * If the &lt;LI&gt; element contains an attribute called <code>yuiConfig</code>, its contents should be a JSON-encoded object
     * as the one used in method <a href="#method_buildTreeFromObject">buildTreeFromObject</a>.
     * @method buildTreeFromMarkup
     * @param  id {string|HTMLElement} The id of the element that contains the markup or a reference to it.
     */
    buildTreeFromMarkup: function (id) {
        var build = function (markup) {
            var el, child, branch = [], config = {}, label, yuiConfig;
            // Dom's getFirstChild and getNextSibling skip over text elements
            for (el = Dom.getFirstChild(markup); el; el = Dom.getNextSibling(el)) {
                switch (el.tagName.toUpperCase()) {
                    case 'LI':
                        label = '';
                        config = {
                            expanded: Dom.hasClass(el,'expanded'),
                            title: el.title || el.alt || null,
                            className: Lang.trim(el.className.replace(/\bexpanded\b/,'')) || null
                        };
                        // I cannot skip over text elements here because I want them for labels
                        child = el.firstChild;
                        if (child.nodeType == 3) {
                            // nodes with only whitespace, tabs and new lines don't count, they are probably just formatting.
                            label = Lang.trim(child.nodeValue.replace(/[\n\t\r]*/g,''));
                            if (label) {
                                config.type = 'text';
                                config.label = label;
                            } else {
                                child = Dom.getNextSibling(child);
                            }
                        }
                        if (!label) {
                            if (child.tagName.toUpperCase() == 'A') {
                                config.type = 'text';
                                config.label = child.innerHTML;
                                config.href = child.href;
                                config.target = child.target;
                                config.title = child.title || child.alt || config.title;
                            } else {
                                config.type = 'html';
                                var d = document.createElement('div');
                                d.appendChild(child.cloneNode(true));
                                config.html = d.innerHTML;
                                config.hasIcon = true;
                            }
                        }
                        // see if after the label it has a further list which will become children of this node.
                        child = Dom.getNextSibling(child);
                        switch (child && child.tagName.toUpperCase()) {
                            case 'UL':
                            case 'OL':
                                config.children = build(child);
                                break;
                        }
                        // if there are further elements or text, it will be ignored.

                        if (YAHOO.lang.JSON) {
                            yuiConfig = el.getAttribute('yuiConfig');
                            if (yuiConfig) {
                                yuiConfig = YAHOO.lang.JSON.parse(yuiConfig);
                                config = YAHOO.lang.merge(config,yuiConfig);
                            }
                        }

                        branch.push(config);
                        break;
                    case 'UL':
                    case 'OL':
                        config = {
                            type: 'text',
                            label: '',
                            children: build(child)
                        };
                        branch.push(config);
                        break;
                }
            }
            return branch;
        };

        var markup = Dom.getChildrenBy(Dom.get(id),function (el) {
            var tag = el.tagName.toUpperCase();
            return  tag == 'UL' || tag == 'OL';
        });
        if (markup.length) {
            this.buildTreeFromObject(build(markup[0]));
        } else {
        }
    },
  /**
     * Returns the TD element where the event has occurred
     * @method _getEventTargetTdEl
     * @private
     */
    _getEventTargetTdEl: function (ev) {
        var target = Event.getTarget(ev);
        // go up looking for a TD with a className with a ygtv prefix
        while (target && !(target.tagName.toUpperCase() == 'TD' && Dom.hasClass(target.parentNode,'ygtvrow'))) {
            target = Dom.getAncestorByTagName(target,'td');
        }
        if (Lang.isNull(target)) { return null; }
        // If it is a spacer cell, do nothing
        if (/\bygtv(blank)?depthcell/.test(target.className)) { return null;}
        // If it has an id, search for the node number and see if it belongs to a node in this tree.
        if (target.id) {
            var m = target.id.match(/\bygtv([^\d]*)(.*)/);
            if (m && m[2] && this._nodes[m[2]]) {
                return target;
            }
        }
        return null;
    },
  /**
     * Event listener for click events
     * @method _onClickEvent
     * @private
     */
    _onClickEvent: function (ev) {
        var self = this,
            td = this._getEventTargetTdEl(ev),
            node,
            target,
            toggle = function (force) {
                node.focus();
                if (force || !node.href) {
                    node.toggle();
                    try {
                        Event.preventDefault(ev);
                    } catch (e) {
                        // @TODO
                        // For some reason IE8 is providing an event object with
                        // most of the fields missing, but only when clicking on
                        // the node's label, and only when working with inline
                        // editing.  This generates a "Member not found" error
                        // in that browser.  Determine if this is a browser
                        // bug, or a problem with this code.  Already checked to
                        // see if the problem has to do with access the event
                        // in the outer scope, and that isn't the problem.
                        // Maybe the markup for inline editing is broken.
                    }
                }
            };

        if (!td) {
            return;
        }

        node = this.getNodeByElement(td);
        if (!node) {
            return;
        }

        // exception to handle deprecated event labelClick
        // @TODO take another look at this deprecation.  It is common for people to
        // only be interested in the label click, so why make them have to test
        // the node type to figure out whether the click was on the label?
        target = Event.getTarget(ev);
        if (Dom.hasClass(target, node.labelStyle) || Dom.getAncestorByClassName(target,node.labelStyle)) {
            this.fireEvent('labelClick',node);
        }
        // http://yuilibrary.com/projects/yui2/ticket/2528946
        // Ensures that any open editor is closed.
        // Since the editor is in a separate source which might not be included,
        // we first need to ensure we have the _closeEditor method available
        if (this._closeEditor) { this._closeEditor(false); }

        //  If it is a toggle cell, toggle
        if (/\bygtv[tl][mp]h?h?/.test(td.className)) {
            toggle(true);
        } else {
            if (this._dblClickTimer) {
                window.clearTimeout(this._dblClickTimer);
                this._dblClickTimer = null;
            } else {
                if (this._hasDblClickSubscriber) {
                    this._dblClickTimer = window.setTimeout(function () {
                        self._dblClickTimer = null;
                        if (self.fireEvent('clickEvent', {event:ev,node:node}) !== false) {
                            toggle();
                        }
                    }, 200);
                } else {
                    if (self.fireEvent('clickEvent', {event:ev,node:node}) !== false) {
                        toggle();
                    }
                }
            }
        }
    },

  /**
     * Event listener for double-click events
     * @method _onDblClickEvent
     * @private
     */
    _onDblClickEvent: function (ev) {
        if (!this._hasDblClickSubscriber) { return; }
        var td = this._getEventTargetTdEl(ev);
        if (!td) {return;}

        if (!(/\bygtv[tl][mp]h?h?/.test(td.className))) {
            this.fireEvent('dblClickEvent', {event:ev, node:this.getNodeByElement(td)});
            if (this._dblClickTimer) {
                window.clearTimeout(this._dblClickTimer);
                this._dblClickTimer = null;
            }
        }
    },
  /**
     * Event listener for mouse over events
     * @method _onMouseOverEvent
     * @private
     */
    _onMouseOverEvent:function (ev) {
        var target;
        if ((target = this._getEventTargetTdEl(ev)) && (target = this.getNodeByElement(target)) && (target = target.getToggleEl())) {
            target.className = target.className.replace(/\bygtv([lt])([mp])\b/gi,'ygtv$1$2h');
        }
    },
  /**
     * Event listener for mouse out events
     * @method _onMouseOutEvent
     * @private
     */
    _onMouseOutEvent: function (ev) {
        var target;
        if ((target = this._getEventTargetTdEl(ev)) && (target = this.getNodeByElement(target)) && (target = target.getToggleEl())) {
            target.className = target.className.replace(/\bygtv([lt])([mp])h\b/gi,'ygtv$1$2');
        }
    },
  /**
     * Event listener for key down events
     * @method _onKeyDownEvent
     * @private
     */
    _onKeyDownEvent: function (ev) {
        var target = Event.getTarget(ev),
            node = this.getNodeByElement(target),
            newNode = node,
            KEY = YAHOO.util.KeyListener.KEY;

        switch(ev.keyCode) {
            case KEY.UP:
                do {
                    if (newNode.previousSibling) {
                        newNode = newNode.previousSibling;
                    } else {
                        newNode = newNode.parent;
                    }
                } while (newNode && !newNode._canHaveFocus());
                if (newNode) { newNode.focus(); }
                Event.preventDefault(ev);
                break;
            case KEY.DOWN:
                do {
                    if (newNode.nextSibling) {
                        newNode = newNode.nextSibling;
                    } else {
                        newNode.expand();
                        newNode = (newNode.children.length || null) && newNode.children[0];
                    }
                } while (newNode && !newNode._canHaveFocus);
                if (newNode) { newNode.focus();}
                Event.preventDefault(ev);
                break;
            case KEY.LEFT:
                do {
                    if (newNode.parent) {
                        newNode = newNode.parent;
                    } else {
                        newNode = newNode.previousSibling;
                    }
                } while (newNode && !newNode._canHaveFocus());
                if (newNode) { newNode.focus();}
                Event.preventDefault(ev);
                break;
            case KEY.RIGHT:
                var self = this,
                    moveFocusRight,
                    focusOnExpand = function (newNode) {
                        self.unsubscribe('expandComplete',focusOnExpand);
                        moveFocusRight(newNode);
                    };
                moveFocusRight = function (newNode) {
                    do {
                        if (newNode.isDynamic() && !newNode.childrenRendered) {
                            self.subscribe('expandComplete',focusOnExpand);
                            newNode.expand();
                            newNode = null;
                            break;
                        } else {
                            newNode.expand();
                            if (newNode.children.length) {
                                newNode = newNode.children[0];
                            } else {
                                newNode = newNode.nextSibling;
                            }
                        }
                    } while (newNode && !newNode._canHaveFocus());
                    if (newNode) { newNode.focus();}
                };

                moveFocusRight(newNode);
                Event.preventDefault(ev);
                break;
            case KEY.ENTER:
                if (node.href) {
                    if (node.target) {
                        window.open(node.href,node.target);
                    } else {
                        window.location(node.href);
                    }
                } else {
                    node.toggle();
                }
                this.fireEvent('enterKeyPressed',node);
                Event.preventDefault(ev);
                break;
            case KEY.HOME:
                newNode = this.getRoot();
                if (newNode.children.length) {newNode = newNode.children[0];}
                if (newNode._canHaveFocus()) { newNode.focus(); }
                Event.preventDefault(ev);
                break;
            case KEY.END:
                newNode = newNode.parent.children;
                newNode = newNode[newNode.length -1];
                if (newNode._canHaveFocus()) { newNode.focus(); }
                Event.preventDefault(ev);
                break;
            // case KEY.PAGE_UP:
                // break;
            // case KEY.PAGE_DOWN:
                // break;
            case 107:  // plus key
            case 187:  // plus key
                if (ev.shiftKey) {
                    node.parent.expandAll();
                } else {
                    node.expand();
                }
                break;
            case 109: // minus key
            case 189: // minus key
                if (ev.shiftKey) {
                    node.parent.collapseAll();
                } else {
                    node.collapse();
                }
                break;
            default:
                break;
        }
    },
    /**
     * Renders the tree boilerplate and visible nodes
     * @method render
     */
    render: function() {
        var html = this.root.getHtml(),
            el = this.getEl();
        el.innerHTML = html;
        if (!this._hasEvents) {
            Event.on(el, 'click', this._onClickEvent, this, true);
            Event.on(el, 'dblclick', this._onDblClickEvent, this, true);
            Event.on(el, 'mouseover', this._onMouseOverEvent, this, true);
            Event.on(el, 'mouseout', this._onMouseOutEvent, this, true);
            Event.on(el, 'keydown', this._onKeyDownEvent, this, true);
        }
        this._hasEvents = true;
    },

  /**
     * Returns the tree's host element
     * @method getEl
     * @return {HTMLElement} the host element
     */
    getEl: function() {
        if (! this._el) {
            this._el = Dom.get(this.id);
        }
        return this._el;
    },

    /**
     * Nodes register themselves with the tree instance when they are created.
     * @method regNode
     * @param node {Node} the node to register
     * @private
     */
    regNode: function(node) {
        this._nodes[node.index] = node;
    },

    /**
     * Returns the root node of this tree
     * @method getRoot
     * @return {Node} the root node
     */
    getRoot: function() {
        return this.root;
    },

    /**
     * Configures this tree to dynamically load all child data
     * @method setDynamicLoad
     * @param {function} fnDataLoader the function that will be called to get the data
     * @param iconMode {int} configures the icon that is displayed when a dynamic
     * load node is expanded the first time without children.  By default, the
     * "collapse" icon will be used.  If set to 1, the leaf node icon will be
     * displayed.
     */
    setDynamicLoad: function(fnDataLoader, iconMode) {
        this.root.setDynamicLoad(fnDataLoader, iconMode);
    },

    /**
     * Expands all child nodes.  Note: this conflicts with the "multiExpand"
     * node property.  If expand all is called in a tree with nodes that
     * do not allow multiple siblings to be displayed, only the last sibling
     * will be expanded.
     * @method expandAll
     */
    expandAll: function() {
        if (!this.locked) {
            this.root.expandAll();
        }
    },

    /**
     * Collapses all expanded child nodes in the entire tree.
     * @method collapseAll
     */
    collapseAll: function() {
        if (!this.locked) {
            this.root.collapseAll();
        }
    },

    /**
     * Returns a node in the tree that has the specified index (this index
     * is created internally, so this function probably will only be used
     * in html generated for a given node.)
     * @method getNodeByIndex
     * @param {int} nodeIndex the index of the node wanted
     * @return {Node} the node with index=nodeIndex, null if no match
     */
    getNodeByIndex: function(nodeIndex) {
        var n = this._nodes[nodeIndex];
        return (n) ? n : null;
    },

    /**
     * Returns a node that has a matching property and value in the data
     * object that was passed into its constructor.
     * @method getNodeByProperty
     * @param {object} property the property to search (usually a string)
     * @param {object} value the value we want to find (usuall an int or string)
     * @return {Node} the matching node, null if no match
     */
    getNodeByProperty: function(property, value) {
        for (var i in this._nodes) {
            if (this._nodes.hasOwnProperty(i)) {
                var n = this._nodes[i];
                if ((property in n && n[property] == value) || (n.data && value == n.data[property])) {
                    return n;
                }
            }
        }

        return null;
    },

    /**
     * Returns a collection of nodes that have a matching property
     * and value in the data object that was passed into its constructor.
     * @method getNodesByProperty
     * @param {object} property the property to search (usually a string)
     * @param {object} value the value we want to find (usuall an int or string)
     * @return {Array} the matching collection of nodes, null if no match
     */
    getNodesByProperty: function(property, value) {
        var values = [];
        for (var i in this._nodes) {
            if (this._nodes.hasOwnProperty(i)) {
                var n = this._nodes[i];
                if ((property in n && n[property] == value) || (n.data && value == n.data[property])) {
                    values.push(n);
                }
            }
        }

        return (values.length) ? values : null;
    },


    /**
     * Returns a collection of nodes that have passed the test function
     * passed as its only argument.
     * The function will receive a reference to each node to be tested.
     * @method getNodesBy
     * @param {function} a boolean function that receives a Node instance and returns true to add the node to the results list
     * @return {Array} the matching collection of nodes, null if no match
     */
    getNodesBy: function(fn) {
        var values = [];
        for (var i in this._nodes) {
            if (this._nodes.hasOwnProperty(i)) {
                var n = this._nodes[i];
                if (fn(n)) {
                    values.push(n);
                }
            }
        }
        return (values.length) ? values : null;
    },
    /**
     * Returns the treeview node reference for an ancestor element
     * of the node, or null if it is not contained within any node
     * in this tree.
     * @method getNodeByElement
     * @param el {HTMLElement} the element to test
     * @return {YAHOO.widget.Node} a node reference or null
     */
    getNodeByElement: function(el) {

        var p=el, m, re=/ygtv([^\d]*)(.*)/;

        do {

            if (p && p.id) {
                m = p.id.match(re);
                if (m && m[2]) {
                    return this.getNodeByIndex(m[2]);
                }
            }

            p = p.parentNode;

            if (!p || !p.tagName) {
                break;
            }

        }
        while (p.id !== this.id && p.tagName.toLowerCase() !== "body");

        return null;
    },

    /**
     * When in singleNodeHighlight it returns the node highlighted
     * or null if none.  Returns null if singleNodeHighlight is false.
     * @method getHighlightedNode
     * @return {YAHOO.widget.Node} a node reference or null
     */
    getHighlightedNode: function() {
        return this._currentlyHighlighted;
    },


    /**
     * Removes the node and its children, and optionally refreshes the
     * branch of the tree that was affected.
     * @method removeNode
     * @param {Node} node to remove
     * @param {boolean} autoRefresh automatically refreshes branch if true
     * @return {boolean} False is there was a problem, true otherwise.
     */
    removeNode: function(node, autoRefresh) {

        // Don't delete the root node
        if (node.isRoot()) {
            return false;
        }

        // Get the branch that we may need to refresh
        var p = node.parent;
        if (p.parent) {
            p = p.parent;
        }

        // Delete the node and its children
        this._deleteNode(node);

        // Refresh the parent of the parent
        if (autoRefresh && p && p.childrenRendered) {
            p.refresh();
        }

        return true;
    },

    /**
     * wait until the animation is complete before deleting
     * to avoid javascript errors
     * @method _removeChildren_animComplete
     * @param o the custom event payload
     * @private
     */
    _removeChildren_animComplete: function(o) {
        this.unsubscribe(this._removeChildren_animComplete);
        this.removeChildren(o.node);
    },

    /**
     * Deletes this nodes child collection, recursively.  Also collapses
     * the node, and resets the dynamic load flag.  The primary use for
     * this method is to purge a node and allow it to fetch its data
     * dynamically again.
     * @method removeChildren
     * @param {Node} node the node to purge
     */
    removeChildren: function(node) {

        if (node.expanded) {
            // wait until the animation is complete before deleting to
            // avoid javascript errors
            if (this._collapseAnim) {
                this.subscribe("animComplete",
                        this._removeChildren_animComplete, this, true);
                Widget.Node.prototype.collapse.call(node);
                return;
            }

            node.collapse();
        }

        while (node.children.length) {
            this._deleteNode(node.children[0]);
        }

        if (node.isRoot()) {
            Widget.Node.prototype.expand.call(node);
        }

        node.childrenRendered = false;
        node.dynamicLoadComplete = false;

        node.updateIcon();
    },

    /**
     * Deletes the node and recurses children
     * @method _deleteNode
     * @private
     */
    _deleteNode: function(node) {
        // Remove all the child nodes first
        this.removeChildren(node);

        // Remove the node from the tree
        this.popNode(node);
    },

    /**
     * Removes the node from the tree, preserving the child collection
     * to make it possible to insert the branch into another part of the
     * tree, or another tree.
     * @method popNode
     * @param {Node} node to remove
     */
    popNode: function(node) {
        var p = node.parent;

        // Update the parent's collection of children
        var a = [];

        for (var i=0, len=p.children.length;i<len;++i) {
            if (p.children[i] != node) {
                a[a.length] = p.children[i];
            }
        }

        p.children = a;

        // reset the childrenRendered flag for the parent
        p.childrenRendered = false;

         // Update the sibling relationship
        if (node.previousSibling) {
            node.previousSibling.nextSibling = node.nextSibling;
        }

        if (node.nextSibling) {
            node.nextSibling.previousSibling = node.previousSibling;
        }

        if (this.currentFocus == node) {
            this.currentFocus = null;
        }
        if (this._currentlyHighlighted == node) {
            this._currentlyHighlighted = null;
        }

        node.parent = null;
        node.previousSibling = null;
        node.nextSibling = null;
        node.tree = null;

        // Update the tree's node collection
        delete this._nodes[node.index];
    },

    /**
    * Nulls out the entire TreeView instance and related objects, removes attached
    * event listeners, and clears out DOM elements inside the container. After
    * calling this method, the instance reference should be expliclitly nulled by
    * implementer, as in myDataTable = null. Use with caution!
    *
    * @method destroy
    */
    destroy : function() {
        // Since the label editor can be separated from the main TreeView control
        // the destroy method for it might not be there.
        if (this._destroyEditor) { this._destroyEditor(); }
        var el = this.getEl();
        Event.removeListener(el,'click');
        Event.removeListener(el,'dblclick');
        Event.removeListener(el,'mouseover'U.removeListener(etoveListener(el,'mouseover'U.removeLis        veListener(var values = 0 e;
   nodes) {
    for (i = 0; i < oConfig.lety(i)oME:
                  var n = thiling;
       ndere_destr (nendere_destror) { this._de{ this._de = this.getEl()            true);
        }
  node.childurrenhis.popNode(node)  * Nulls out the to       * tree, or anotto       * tree, s branchs  oConfwith thre the OM containing a fulram {Node} noto      */
    getHighlightedNode: fun"  * Nulls" +}
      
        return this.root;
u      apses all s the root node of thito r;
u   * tree, s branchdeByInid, se   apses all g a fulram {Node} nothito r;
u  */
    getHighlightedNode: function         .thito r;
u  

        node.updateIcon()
    },

  * A striis incthe instobjec    *ed by the conse Node clahe statusction ofoshes the
 the method callre ttead olse;
    conse Node claheent.
  sRoot()) {     is not e, ans to fetch ittancgardodes     node ty of th, aneeVi  ThigleNodeHighlight is   * Dcontainin * tree, s branch       |t()) {} y containinfreshes the
 i  ()) {     is not eO takfr nulas that is displade} nothi  * Dcontainin*/
    getHighlightedNode: function         .thito rDcontainin

        node.updateIcon()Ab mea   or anottnt} conex * t the icon not eO tode) {

leNodeHighlight oe('expae.
     * @methnot egNode
     * @par   * braode) {

leNodeHig  // except loathe
* Am) {
       ode) {")/li></ul displade} nooe('expaemove
     */
       node.updateIcon()Ab mea   or anottnt} conex * t the icon not eO ted.
     gleNodeHighlight     .
    e.
     * @methnot egNode
     * @par   * braed.
     gleNodeHig  // except loathe
* Am) {
        By defau)/li></ul displade} nooe  .
    emove
     */
       node.updateIco*s.ids  * @param >
   i];
      treet.
, aneeVapses all g a ful.. Use with cautsif no m     * @metho  * @methn    hs  oConfN</li>
 @param {object} nce ttmetho  * @methparam { isng)
     } nce ttmetho  * @methenderedch if true
    the OM   // rue furtdolean anch that wee
     * d no m     * @emove
     *</loperty:, anch thon(fnDataLoader, iconMode no m     * @ *</loy in n      var p = anch thon(fnDataLoaaLoader, iconMendered) {
                nde.updateIco*s   }
    },
  /fosh
     Highlight it r.. Use wCstatustioig nulas    },
  /fosc 'dblclick'         this   //         this.f.. Use wI in single()) { ram }
    eeView  with    her . Use with cauto'keydo) && (         metho  * @methoArgs { isngo puak },

      * pas>
    ytring|HTM     }* pahernulab@param {e, s branch()) {} AlwayropethelseeView  with    herthe childas to do wade} nooe(eydo) && (         /
    buildTrArgsIndex: function(node      var p = 'node' alloArgs    oArgs.nunction thatrin.isRoot())      if (node.nextSi+) {Args.nunc{
         arent);
  oArgs ion thatrin.isRoot())      if (node.nextSi+) {Args{
         arent)      if (node.isRoot()) {
           (node.nextSi
               ) {
        .isRoot()) {
     fig ocus/* Backwardait un cobiefeyeetiaeletadeion(PROT!== V{
        
  ak;
        }
    },
    /**
     * Renders the tree boill descriptAtiaethe cble nodes
   with cautirawleNodeHig  // excepts inble no/li></ul displadePROT.iraw!==PROT.ble nocus/* le  backwardait un cobiefeyeetiaeletade
htedNoaug nodeTV,= node,
    (eydoP For Timeohis.ro   unn@returu      et.
 /**
  x (this inode)  /*s        isro  objec   / For stoniqg prd pahfies  treet.
 boill );
       **
 /**
 doleae nodhang(node);

 ;
u  .
 @meth {objechtedNode
      * Null.;

 ;
u  
 @metest
i  
 @mesM cos dade V{;

 ;
u  sTypeohis.ro  Globalopechli>
 @ter themselvs
 @meth {objechtedNode
      * Null.  /*s
 @metest
lts l
 @mesM cos damethod _delade V{  /*sof childis.ro  Globalol
        /   n is  expandbyode aiollaUs      // oy be used
 anothell on.
 @mel
     htedNode
      * Null.thi  * 
o  * @methotheId {S  oConftid {string|HT@ter themselv
{e, s branch  * Null}ng|HT@ter themselvnblqg nce ching collecis gene.
 @mesM cos dade V{thi  *            (otheId     if  func!== V{  /*s[otheId    var     }
 t    t (values.ocusdis.ro  Globalol
        /   n is  extSi+byode aiollaUs      // oy be used
 anothell on.
 @mel
     htedNode
      * Null.thi tree.  * @methotheId {S  oConftid {string|HT@ter themselv
{e,  @methnot 
     {S  oConftid {ram {int} nodeIndallre   }
{e, s branchNode
     * @pathemselvnblqg nce ching collecis gene
 @mesM cos dade V{thiNtSi+)         (otheId,hnot 
         if  func!== V{thi  * (otheId    var     }
 t    t              renot 
      (values.ocusdis.ro Use wCrgethn    tioig nulwillers, andns a colle * lo    ro Use e.
     * h {objec  * Null.FOCUS_CLASS_NAMEe.
     *test
       * tree, sM cos d tree, ontalleNodeHig   with "    o    "
 do wade V{FOCUS_CLASS_NAMEl()     o    'cusd
})) {
Timer = window.setT funDome = node,
    Dom.subscribe);
              .subscribe this == node,
    (eydo;dis.ro   theb/ miarget  treet.
@ter  boill )T           the OM contaers becoli    /ro  thephe ndall    * M     }   
          NtSi+) {argetill dHign    }
   htedNode
    dHigarget  tree.  *uelet node,
    (eydoP For Ti
{e,  @metho  * y a stringperty to i  * A strgt; or &lt;erty
    nDataLoad
{e,nstobjec    *naram {    /**hed
    ytlete
     * All otnData, the ins
{e,ode null if     * @pa( second o the _clo     oent
f || ()l dHiallereturn allo  * ye
     * "coct} prt lqgh itsn   dram {objies all g antree.  as  ois  },

    /**doleacollesuconam {objiesand new linecisenekfr nues tod _d i  (mer = w    *s true t     // Rreturn e
     *ode nul    oent
f || (
{e,  @methoP      hNode
       /**          ntree.  * @meth/**
     h if true
tid {ritia  /**
    /ed.
     *sM cpa(  // excep,t loao  * node) {

  dHigahe method  dadehtedNode
     * @+)         (o  * ,hoP     , ode) {

        ;
  o  *   if (thi{rit(o  * ,hoP     , ode) {

 ) { ocushtedNode
     * @{
             is.popNode(node) id {ram {he chiis ion that *  or nul editglobalop
u  no/li htedNode
      * Null.e.
     * h {objeche specified etest
i  
 do wade    ;ch
   0 node.updateIcon()T     /**          // // Update t.e.
     * h {objeces the node and rtest
 * @[]
 do wade              e: el.is.popNode(node)  * s out the t     /**   //the bre.
     * h {objec s the root ntest
  * Nullram {Node} not *   e: el.is.popNode(node) rty
    linkn ofosheill beco        status  ytr A strrecarimitiparam {Noderty:,   // ity
     statusobject
         var h.e.
     * h {objecill be calledtest
should be a J  * @mell   e: el.is.popNode(node)P      ntree.
     * h {objecrenRendered d rtest
 * @ be a J  * @mrenRen  e: el.is.popNode(node) rty
    hich will beco  W *odthe ata-1the child     // D.e.
     * h {objeci    ecified etest
i  
 do wade    i    :a-1l.is.popNode(node) rty /**   /**
    /ed.
     *sM cpe.
     * h {objecode) {

leNodeHigtest
 if tru
 do wade              ()) {  return this.root;stathat
      }
     last siblin ataothe?e.
     * h {objecthis conflileNodeHigtest
 if tru
 do wade    this confli:/ rue  return this.rootS the iwinble no/  }
          *ed.
     * /**?look at ection
   h @TOD containediclitly nu e
   ject} va *naram {hligdd pmell ..o  @todo verify        containet the eChil     iclitly y of fiwindo.e.
     * h {objec *naraHgdd pleNodeHigtest
 if tru
 do wade     *naraHgdd p  ()) {  return this.root     set t   " icon  is ts with tll onl          /d{he chiis  /**  his.rootRemoves the no" icon ()) { s witnly   }
      linadling
     * w h {objeces the nparent
 leNodeHigtest
 if tru
 do wade    es the nparent
   ()) {  return this.rootDConfigures thiseeVapses ion
 e a noerty
    nD load node isd new lihis.roott sibling t     set t   " icon  is tounction(
       *be     a ning
     * w h {objeced = false;
       leNodeHigtest
 if tru
 do wade    ed = false;
         ()) {  return this.root             th = nu displayed, only* h {objecrnt = null;
   dered d rtest
 * @ be a J  * @mrnt = null;
     e: el.is.popNode(node) r          ing  displayed, only* h {objecing = null;dered d rtest
 * @ be a J  * @ming = null;  e: el.is.popNode(node)We  staldren =  /** up/foscalue weng dex
  or anottion that wes thde(node)
    to fetch itg
     * w h {objec_to      * @methotest
 if tru
 do wamethod _deleteNode
     o       ()) {  return this.rootFam {functonex * t he nodeeet the brn that    /**         ell .e.
     * h {objecill oot.se * @methotest
hat have passed  * @mell oot.se  e: el.is.popNode(node) r   i Node i**
  Configures this @returnspse();
pandv);
    OD containegurebackdallre   }.e.
     * h {objechsLhis @rleNodeHigtest
 if tru
 do wade    hsLhis @r  ()) {  return this.root  esh
    /t possibset to 1,ecis, tw that browse" icon ()) {g t    containegthe instobjful        diclitly nu ea andno colle * lthe tree; or his.roott tra
i folab@},

   p     ,    e typeeview    gh  // D.e.
     * h {objec       leNodeHigtest
 if tru
 do wade           :/ rue  return this.rootU"coct}  * @parampse a co    she icon that isyed when a dynamic
   his.roote nowow mucEventtnt} ct doleae nocolled the first time withou of this.roottx (thised b * ofsto 1,gthe icolled the fi (    /      bset)rstSendered d t the datno collect dmultip liClice used.  If i></ul.e.
     * h {objech the daecified etest
i  
 do wade    ; the da  0 node.updateIcon()Sn the ts  node tyi  This out DOMhis  x ({int} nodeInd, the instd is   his.rootno wrap.e.
     * h {objecnowrap * @methotest
 if tru
 do wame   with ()) { be a J  * @miowrap  ()) {  repdateIcon()If/ rue fed
     * @lue lwayinstrarent
  boole used.  Io        statuteIcon() "coct} Evenrears out the OM contae ico Configures this @re
    /**
 his.roottx erstSentntainer. on  is tallecdmu_clo    * the node, andcalue    OD containe// D.e.
     * h {objechsLuse * @methotest
 if tru
 do wame   with ()) { be a J  * @mhsLuse  ()) {  rn this.root  esCSSiarget  treh tll onl DOMhis  DOM elemen        . on     l onost ocontainegu  lasEvenread pm   / For statlete
   the OM conta     *in the ce// D.e.
     * h {objec DOMhisassNa * @methotest
s      * treemethod cOMhisassNa: ""rrentlyHighlighted;T/ oy be used {stnDataLoade DOM eltion(
    the data +byo    diclitly nu.e.
     * h {objec DOMhisElId * @methotest
s      * treemethod cOMhisElId  e: el.iis.ro  En_clo  Highlight it r      If/ rue fed
     *gu  lasight it rette n/recaropag cpaight it r   
 @meth {objecen_clo         methotest
 if tru
 ame   with ally rade     n_clo         :/ rue  ris.ro  Sde nsm {hligurns nulsM cp   Cstatus  ytrf:ro  <ul>ro  <li>0 -ae nocght it ret</li>ro  <li>1 -acght it ret</li>ro  <li>2 -a     d the fi cght it ret</li>ro  </ul>ro  * h {objec ght it rSM cpe.d etest
i  egTi
{e,    with 0 rade
c ght it rSM cp  0 nodis.ro   e
    node tyight it r    e
     *aropag cpd up/fos
   p     s{int} noc 'dbeeVapsero  * h {objecaropag cp         Upmethotest
 if tru
 ame   with ()) { bade
caropag cp         Up  ()) {  repdate   e
    node tyight it r    e
     *aropag cpd * Evefos
   d the fi int} noc 'dbeeVapsero  * h {objecaropag cp         l, 'methotest
 if tru
 ame   with ()) { bade
caropag cp         l, '  ()) {  repdateIotU"cr-akfr nul))) {
     } nceadlinefos
    * @ be  * h {objec )) {
   
methotest
s      * ame   with ode} a ade
c )) {
     e: el.ispNode(node) rty /** test
     * w h {objec_test
     * w hod _deleteNohotest
s      * treeig   with " * @"
ode
    test: " * @" node.updode.u }
   Pa  :a"e);
   l.yimg     a/i/ }
  .gifs.subscamic
   Tt t:a"Emic
   s.subsced.
     Tt t:a"  .
     s.subscthis @rTt t:a"Lhis @rs.subscade
clyHighlighted;Iritia iz },

    /**,rn tsa     >
 @param {objies  editor crenRendered d rmary userit
     * w @metho  * y a stringperty to i  * A strgt; or &lt;erty
    nDataLoad
{    * nstobjec    *naram {    /**
     * w @methoP      hNode
       /**          ntree.    * w @meth/**
     h if true
tid {ritia  /**
    /ed.
     *sM cp be a J  * @mhrit:         (o  * ,hoP     , ode) {

     be a  oader, 
    = {}          true)d the fi  (fn) {
         (thi{ram {{{{{{=chtedNode
      * Null.;

 ;
u  {
        ++htedNode
      * Null.;

 ;
u  {
        true)dcOMhisElId{=c"    dcOMhisel" +}
     ram           }
   );
 .is       o  *       if (node.nevar valuei];
        o  *   i  if (node.ne   }
   o  * n         if (thi];
    
                if (fn(ni<lenultiExpacharAt(0&& p '_'      );
 .isUnekfr nunot bn.data && v     );
 .isF       (ot bn.data && v :ev,node:node}) !== false) {
t bn.data && +) {D| (n.data && otype.collapse.calllllet);
                    } else {der, 
   n.data && +) {D| (n.data && otype.collapse.callllletype.collapse.calpush(n);
                }
       paren);
 .isUnekfr nunode) {

    if (thi/**
     +) /**
    ;  fig 
       ighlighteeeeee) rty      Chang(n
    eiRemov the icon        ram el {d o ppli  his.roeeeee)fos
   .  Io          objful    youet the br pplec s t-l
  lhis.roeeeee)am {objies  br  the
     *t the brco         hen a dyneoveul edihis.roeeeee)ons the
   to inser inline edihlighteeeeee)@   * @pa    Chang(lighteeeeee)@test
Cete
      * @meteeeee/
        true)dx (th      "pa    Chang("moveCh this.removeChioP      , the ineventnsting cn;
   he nodeeedx (thchild     // D.e.
      pareoP     :ev,node:node}) oP     .o    dfirst(veCh th         }
      return this.root;obj eltam {objies  treh tl    *gu troy me" icd) {
     renRendered d dynkiown.         perty. af   /**
hen a dyn* to mdata obja conse Node cl    renRen{d o llec ppli  ofosheill bec'etes the n    oraram o Node clild collection
     ion(noit possi editons the
   to inser inlined rmary us pplePenRendered d r @method popN    }

          /**          ntree.    * ws branch if true
 is t        pplic contaeasesuccessful be a J  * @m pplePenRen*/
    getNo   }

      collapseAll: fo   }

      collapseAAAAA.isRoot()) {
            return ftrue)the
  +) o   }

   )the
{
        true)
            }

             true)
    h+) o   }

   )
    h+ 1this.removeChi@todo whyaease     punocod f        sueletn     * seadline @TOD contaioveChi     l
  l   ts._c      elu becoli  any open editEl: functiothis confli        if (node.exnctiothis confli+) o   }

   )this confli;ny open edit   return ftrue)the
.  * @pr(veCh th        o   }

   )for the parent
        p.childrenRe   // ca** upee = exisr    e parent's collec    var a = [];

 true)d the fi (var i=0, len=p.children.length;true)d the fi[i]. pplePenRen(veCh th         } return ftrue)  }
      "pa    Chang(".toLowerCase() !==        }

        return trueA    d},

    /*os
   d the// Update t.e.
     *mary us pp  dfirstdered d r @methd theNot egNode
     *     * e.    * ws branchNode
            // 
     * w hod _deleteNo  * @m pp  dfirst*/
    getNd theNot    collapseAll: true)   first
   
                Y.RIGib:
      d the fi[true)d the fi (var i -  newNode[newNodeGibling = null;
  d theNot ewNode[newNoded theNot rent = null;
      Gib
           (node.ne     d the fi[true)d the fi (var i]
  d theNot ewNode[newd theNot r pplePenRen(veCh thhildrenRe    the branch IE dmultip isss tsorkargene. If/       // RhildrenRe    linadlin af   /**
h{ritia   *nara,   // ity    * aRhildrenRe    out ttiatnull if /**
     =/ rue feeet the br, tw OD contaioveChies the n divmiow  h @TOD  Highliboolees thfor it might not befor the parent
      (thi/**
    children.length;true)   first
      .sssNa.dmultip =c""{
            p.refresh();
 d theNot ewNode        return trueA    d},t     /** *os
   suppli  o /**         ee, preserving the*mary us pp  dTodered d r @meth    }

    gNode
     * @param pp  dara.e.    * ws branchNode
 T    ppende   // 
     *  * @m pp  dTo*/
    getNo   }

      collapseAs branco   }

   )o    dfirst(veCh th      node.updateIco*sI to m},t     /** he animt    suppli  o /**
ng the*mary us* to mBe ani
red d r @methn    gNode
     * @param* to make    /** he ani
    * ws branchNode
   * calo mdat /**
ng th  * @mhrto mBe aniemove
     */
    popNode: function(node) {
    r it might np     be a  oa might not bethe
   if (this._collapseAnimthe
.om the tveCh th                p.refrede: funcref
     on(nodei firstOfnp th                   for splice(ref
    , 0moveCh thbe a  oa might nnot rent = null;
      if (this._collapsnot rent = null;
   ling = null;
  veCha.length] = p.children[ilapseAnim        node.nextSibling.previousSibling = node.lapseAnim    node.previousSng = node.lapsibling.previousSibli
  veCha.g = node.lapseAnim pplePenRen(p th         } return fde: functioth      node.updateIco*sI to m},t     /** af   /**
hsuppli  o /**
ng the*mary us* to mts inside thr @methn    gNode
     * @param* to maas inside thrs branchNode
   * calo mdat /**
ng th  * @mhrto mts inemove
     */
    popNode: function(node) {
    r it might np     be a  oa might not bethe
   if (this._collapseAnimthe
.om the tveCh th                p.refrede: funcref
     on(nodei firstOfnp th be a  oa might n!(node             if (newNode.nextSieAnim    node.previo              newNodede: function pp  dTonp th                       }
         for splice(ref
    h+ 1, 0moveCh thg = node.lapsiblinling) {
            node.nextSveCha.length] = p.eAnim        node.nextSibling = node.lapseAnim    node.previousS        newNode = newNode.ousS        newN
  veCha.g = node.lapseAnim pplePenRen(p th         } return fde: functioth      node.updateIco*sl;
    },is t       Nn a dynlees th brasuppli  oN/**
ng the*mary us* firstOfside thr @meth    }

    gNode
     N @paramcheckside thrs branch if true
 rty /** {ram {that broNn a dynlees th brside thhhhhhhhhhhhhhhhhhhsuppli  oN/**,t);
  -1 . Use wi hod _deleteJ  * @mhsfirstOf*/
    getNo   }

      collapseAll:     }

           }

   )for the      if (node.nevar value = [];

     }

   )for the   for (i =, le ; =p.children.length;pseAll:     }

   ;++i) {
     =  }
               if (m && m[2]) {
  i           newNodepush(n);
                }
            }
 -1        node.updateIcon()
    },

  /** ats lhich will bec'u displaytching collecnngleNodeHighlight is    newNto avoid j    }
 
   []
 do wade    is    newNtrender
     */
    render: Gib:
  true)
           for slice(0veListener(var values=0;
   Gibl(var i    Gib       ctiot= 0; i          Giblsplice(i,1n      var p = Gibl(var i  if    }
 Gib
  }
            }
"body");

        return nullS twsh will bec'u es the node and recurses, twin.
     * @met
     * twin.
    l
     */
    collapseAll: functithe
.      econfli(true)   first
      moveCh for (var i in this._nodes   first
   
                    true)   first
      .sssNa.dmultip =c""{
                       });

        return nullHidesh will bec'u es the node and recurseshidein.
     * @met
     hidein.
    l
     */
    ccollapseAll: functithe
.      e  .
    (true)   first
      moveCh for (var i in thtrue)   first
      .sssNa.dmultip =c"ecnn"{
          );

        return nulll;
    },
  id{he chiis  /**    DOM elem divleNodeHighlight is ElId * @methos branchs  oConf
    am el {dd
 do wade    is ElId  
    getHighlightedNode: fun"    " +}
     ram   );

        return nulll;
    },
  id{he chiis  /**    s the n divleNodeHighlight is first
    Id * @methos branchs  oConf
    am el {dd{he chiis  /**    s the n divleNodeHde    is first
    Id  
    getHighlightedNode: fun"    c" +}
     ram   );

        return nulll;
    },
  id{he chiis  /**   h
      Returns the tree's host e) && (  Id * @methos branchs  oConf
   h
  
   am el {dd
 do wade    is ) && (  Id  
    getHighlightedNode: fun"    t" +}
     ram   );

         reurn nulll;
    },
  id{he chiis  /**    }
       gel )T    }
     t ectiahernuurn nullEventtnesh
       /// For ss feedbackdhe cscre   ret.sesgleNodeHighlight is  }
   Id * @methos branchs  oConf
   id{he chie  }
       ge
 do wade    reurn nis  }
   Id  
    getHighlightedNode: fun"     }
   " +}
     ram   );

   do wade     return nulll;
    },
is  /**    DOM elem l onl Returns the tree's host element
     * @method getEl
     * @ DOM elem l onl Returns the t} the host element
     */
    get * @met{
           is ElId 
   );

        return nulll;
    },
  divmr   * bra       /d{he chiis  /**   es the node and recursesis first
    ment
     * @method getEl
     *is  /**    s the n divleNodeHde    is first
    element
     */
    get * @met{
           is first
    Id 
   );

        return nulll;
    },
  ram el {Hnt} conbeewN
objeche chiis  /**   h
    gleNodeHighlight is   && (  ment
     * @method getEl
     *is  /**   h
     l onl Returns the t} the hos  && (  element
     */
    get * @met{
           is ) && (  Id 
   );

       return nlll;
    },
  outem l onl Return{he chiis  /**    DOM to do wa recursesis fcOMhisElside thrs branchlement
     * @param el 
the t} the hosfcOMhisElelement
     */
    get * @met{
           dcOMhisElId)  );

         reurn nulll;
    },
  ram el {Hnt} conbeewN
objeche chiis  /**    }
   gleNodeHighlight is  }
   ment
     * @method getEl
     *is  /**    }
    l onl Returns the t} the reurn nis  }
   element
     */
    get * @metdoc * pasis EleturnById htrue)    }
   Id(    || {}         do wade     reurn nis  M cpTt t:a     */
    collapseAll: 
     sLhis @r   collapseAAAAA.isRoot
    this @rTt t{
         arent);
  nodes   first
   dren_for (var i in this._nodes/**
    children.length;AAAA.isRoot
    amic
   Tt t{
             t);
                    .isRoot
    ed.
     Tt t{
                       arent)      if (node.isRoot""{
          );

   do wade   return nullHidesh will bec   s the n (dx (t&lt;ertmcolleecessary modhang(sttnesh
     sssNa.leNodeHighlight c .
    e.
    method collapsstroy
    */
    destroy :Oon
  collapscollode) {
  namic
   his.ro eAll: functi/**
    chill(node)    p = p.parenfed chhe  collapsc   * @
     r
    render: l(n:
      the
.oe  .
    (veCh thhildrenReodes[)) { =  }l(n   collapseAAAAA.isRooth         } return fde::
      the
.  }
      " By defaumoveCh thg = node.odes[)) { =  }l(n   collapseAAAAA.isRooth         } his.ro eAll: functi }
    for (var i in thtrue)/**
     =/()) {
          arent)      if (noderenhears out      eivleNodei in thtrue)hidein.
    ( th            true)/**
     =/()) {
 h            true)e = false;

           } return f.exnctio    }
     .titlE:
      is  M cpTt t(.toLowerCase() :
      the
.  }
      " By defa     this.oveCh thg = n        return nullS twsh will bec   s the n (dx (t&lt;ertmcolleecessary modhang(sttneurn nullh
     sssNahed
   
    },

de a * do notollthis confli+ the noseigleNodeHighlight eonflileNodeHde          stroy
    *lazySourc_deleteNode: funOon
     * nollode) {
  ned.
     gleNode eAll: 
     sLhis @r && ntrue)/**
         lazySourc_d   collapseAAAAA.isRooth         } return fer: l(n:
       eteNode: fun;
   .isRoov);
 editor clazye, and
     r,     * no  perty. agor his.ro Re    o oraram oa *naram {hlnly   }
    l )T    ode) {"c   * @alret.y p = p.parenfed d he animflig&lt;ertynly d * ,hsofeeet the br,kipcollnowgleNode eAll: !lazySourc_deleteNode: p.parenfed },
  r   * n   * @
     r
    rendCase() :
      the
.oe('expa(veCh thhildrenRee eAll: [)) { =  }l(n   collapseAAAAAAAAA.isRooth             } return fCase() :
      the
.  }
      "ode) {"moveCh thbe a  oa}hhildrenReodes[)) { =  }l(n   collapseAAAAA.isRooth         } return fll: functi }
    for (var i in thtrue)/**
     =/       }

pseAAAAA.isRooth         } return fll: functif (autoRefresh && p && p.childretrue)   first
      .this.getEl()true) *narain.
    ( th         arent)      if ( } return ftrue)/**
     =/       return ftrue)e = false;

   return f.exnctio    }
     .titlE:
      is  M cpTt t(.toLowerCasefun;indoe weng ramcheckche cd the fi curn be sueltor clazyLowerCasefun, and  (trampc wengp._c  bec  ns a colleno   }
    loLowerCasefunll: functi   first
   
            ll: 
     sLhis @r   collapseAAAAAtrue)/**
     =/()) {
         AAAA.isRooth         } return fll: f nctiothis confli        if (nodeer: Gibs:
      is  * do no( th            var values=0; Gibs:   i<Gibs  for (i =p.children.length;pseAll: Gibs       ctio    Gibs   s/**
    children.length;AAAAAAAAGibs   s     }

                     }             }         } return ftrue)* twin.
    (.toLowerCase() :
      the
.  }
      "/**
       this.oveCh th            e = false;:a     */
    collapseAll: 
                   if (nodeer: yEditor(); }
) && (                  ll: tNodeB                el.))) {
    = el.))) {
   ) *pl
  (/\b     ([tl][pmn]h?)|(this @r))\b/gi,     is  MsNa 
   );

         }         }        el = {
           t_cloel' +}
     ram n      var p = tNodeB            is._nodes/**
    children.length;AAAA{
   *pl
  CrgetveLis    -ed.
     'is    -/**
    '   );

         t);
                    {
   *pl
  CrgetveLis    -/**
    'is    -ed.
     '   );

         }         }            return nulll;
    },
  css sssNahn    he chie h
    leNodeHighlight is  ssNa * @methos branchs  oConf
   css arget  treh is  /**   h
    leNodeHde    is  ssNa:a     */
    collapseAll: 
     sLhis @r   collapseAAAAA.isRoot"    this @r"th         arent)      if (    run, c contatop i  bottom, middree boilo llecn that wtop sssNa * @meeeeeeeeer: , c = (eAnim    node.pr    "t" : "l"thhildrenRee eA.exnest
p=    node) { mom=     (ed.
  ase mon=ecnn(no   }
    ) * @meeeeeeeeer:        "n"              ll: nodes   first
   dren_ && ntrue)isDConfig(v          is I the da 
 deleteNode: p.paren;
  nodes   first
   dren_for (var i in thhhhh       _nodes/**
    ch  "m" : "p"{
              collapseAAAAA.isRoot"    " +}, c +     
                       return nulll;
    },
  hEventsssNahhe chie icin * tree, s branchs  oConf
   css arget hEventss _deleteNohohlight is fEven ssNa * @met referenceEven ssNarender
     */
    render: G:
      is  MsNa 
;collapseAll: 
       first
   dren_           sLhis @r   collapseAAAAAs +  "h"{
          );

AAAA.isRoototh      node.updateIcoulll;odes chil r   * seet.
ich will bec'u   }
    loleteNohohlight r   * Ae} a node referr   * Ae}render
     */
    render: Editor();for the   for (i
    rendvar values=0;
<ln=p.children.length;aluec:
      d the fi[     var n = thilingc)isDConfig(v     if (!p || !p.tagName) {
           arent);
  ! cothis confli        if (nodep.tagName) {
           arent)      if (nodep.tacs/**
                      cs/**
  Ae}                 }         }            return nulll;odes chil  
    },

et.
ich will bec'u   }
    loleteNohohlight rs
      e} a node referrs
      e}render
     */
    rendvar values=0;
<or();for the   for (i=p.children.length;true)d the fi[i].     }

                true)d the fi[i].     }

Ae}             }
      return this.root;* @param},t     /** **
  Configures *  or de from the trill be calles with tlen a dynoad not sibling tC contaiitll if@},

   gurebackbe calles
   sRootoff the node, and treh tl    loleteNohohlight setDd = false;oleteNoho @methfmDll oot.se {nder
   nf
   imer = winDataLoadenstobjec   n that well .e.
     * @metht the dathdeByIc* @param},t e icin{Hnt} condmultip the icon that is displayed when a dynamic
    nD load node isl if@},
d the first time withoutneurn null" By defauibset to 1,nstobje   If/" icon 1 fed
  used.  If set to 1,nsurn nulldmultip ta problem, trusetDd = false;render
    fnDll oot.se,ht the da   collapseAll: fnDll oot.sechildren.length;true)ell oot.se =/(nDll oot.se              true)  o      =/       }

pseA arent)      if (nodetrue)ell oot.se =/o              newtrue)  o      =/()) {
            return fll: t the da   collapseA newtrue)t the dat=ht the da           }
      return this.rootEertyal otich will bec* @retud     // Daining a fulram {Noe*mary us*      * tree, s branch if true
 is t        i Noh       // Don't dJ  * @mhs    element
     */
    get * @met 
    =
      the
.       );

        return nullEertyal otich will bec    s the n , the instthiseeVto fetch itg  Looksad flag.  The aram {objecboth all gis ion that   // ity     // D.  If/ hHT@ter tto avoid akfr nul  ts.ahod i  s the n do fetch ittaerty
    gurebackdimer = witto avoid akfr nuleltion(     // Don't dJe*mary us* Dhat is display s branch if true
 is t         bec    s the n  lin } ncethiseeVto fetch iton't dJ  * @mhsDhat is:a     */
    collapseAll: 
     sLuse   collapseAAAAA.isRoot()) {
          arent)      if (node.isRoot(       s          (true)  o      &&      the
.    )  o     
   );

        p.chisRootlazy
                       return nulll;
    },
  ode) {
f set mo Io       his ms *os
   wayi s thodes that is displayed when a o ppelue 
    comes al   /tip ion
 af   /**
h{ritia  that is displayed whblqg nc  ttead deno   }
    )gleNodeHighlight is I the da display s branchdeByI0che cdcollapscsssNahe1the c used.  IfsssNa * @met referencI the daelement
     */
    get * @met 
   )t the dat&&      the
.    )t the da   );

        return nullCheckotich will bec*ibood the firstIch will bec* @rlazy-this @re  // iturn nulld the fi collenot*be   rarent
 ,iwindoenot*kiow  node tyi  This ou
 his.root lina  gh  d the firstIt moshe ssesaneeet the brgetum   h @TOD linarturn nulld the fi (   //on that,ttnesh
     t thse br, tw OD namic
 _clourn null the OM contass _d)rstIt of the
imes we ject} vakiow       linart rarent
 urn nulld the firstFtreh tllat null"checkFtrLazy    " , the inst()) {gleNodeHighlight    first
  e.
     * @methcheckFtrLazy    ch if true
s the iwincheckche cunthiseeVd the fi? display s branch if true
 is t        ibood the fiyi  b * ofhe dese nowowarturn nulld eckv);
    OD   condite t.e.
    de       in.
    l
     */
 checkFtrLazy       collapseAll: 
     sLuse   collapseAAAAA.isRoot()) {
          arent)      if (node.isRoot(tor();for the   for ( >I0c||                  checkFtrLazy         (thiisDConfig(v          ed = false;
       ) * @meeeeeeee            }
      return this.rootE   * seifl bec* @red.
     ,  
    },

of thwi {gleNodeHighlight h
    leNodeHde    h
    l
     */
    collapseAll: functithe
.lodbeeV   ( nodes   first
   dren_ &&  (thiisDConfig(vv :ev,node:node}) is._nodes/**
    chit
    ed.
    or) {arent) Atrue)/**
  or) { this._de{ this        return nulll;
    },
  markup  treh is  /**l     tu   }
    loleteNohohlight encetm ment
     * @meths  oConf
   markup  treh is  /**l     tu amic
      }
    loleteNo referencetm l
     */
    ccollapseAunctif (autoRefresh & =/()) {
 h         * @met['<divmarget="    itemuibd="' ,      is   Id 
 , '">' ,     is 
    var h ,      is first
   var h ,'</div>'].join(""   );

        return nullCerty. e icooad norarentde from tx erstWee lways ed by the divmr   * oad
{    *  DOM eltioill bec   s the nost oiwindon'norarentfrom the trwith tmselveto avoid untdes  will bec* @rt siblingoleteNohohlight encfirst
   varment
     * @meths  oConf
   the trwit DOM elem div l onld
    ytamic
      }
    e.
     * hod _deleteNo  * @mencfirst
   varl
     */
    cc
    render: sb(fn) {
        sb[sb (var i]
  '<divmarget="      }
    uibd="' +etrue)   first
    Id 
 + '"'toLowerCasefunT    i Natsorkargenea     n IE rarentde fisss ,s out      eiv ibootipoutLowerCasefunin IE, dx (t&lt;ng ram }
       h used.  If 
  x (thisl if     amic
   his.ro eA    m {objec" icon  is .collapseAll: functiamic
    && functi   first
   
                sb[sb (var i]
  'fsssNa="dmultip:ecnn;"'           }
       sb[sb (var i]
  '>'cusdis.ro eA   Don'norarentfrom a  gh  d thed.  IfgetEluntdes  will bec* @rt siblingoleteNseAll: : 
       first
   dren_    nodes/**
    ch||                  true) *naraHgdd p           sDConfig(vv :ev,node:node}) sb[sb (var i]
  true) *narain.
    ( th         

        sb[sb (var i]
  '</div>'
 h         * @metsb join(""   );

        return nullG      / },
  markup  treh          // s        is Thisdons d) {
     // Don't dJe @rt siblingoleteNohohlight  *narain.
    ment
     * @meths  oConf
   l onlhe chiis  /**   es the node and r hod _deleteNo  * @m *narain.
    l
     */
    cc
    render: noME:
     thg = node.odes (thiisDConfig(v          ed = false;
       )  collapseA newtrue)tsLhis @r =/       }

pseAAAAAunctithe
.lodbeeV=/       return fode.odes (thidll oot.sechil                 " iTimeout(                          */
    collapseAAAAAAAAAAAAAAAAA// D.dll oot.se */
 ,collapseAAAAAAAAAAAAAAAAA         */
    collapseAAAAAAAAAAAAAAAAAAAAAAAAA// D.lse;
                                       }                       }he10 thhildrenRee eA arent);
  nodesthe
.    )dll oot.sechil                 " iTimeout(                          */
    collapseAAAAAAAAAAAAAAAAA// D.the
.    )dll oot.se */
 ,collapseAAAAAAAAAAAAAAAAA         */
    collapseAAAAAAAAAAAAAAAAAAAAAAAAA// D.lse;
                                       }                       }he10 thhildrenRee eA arent)                  .isRoot"Error:y
    lot.se ecis geneyi  Thisin the ing"{
              collapseAAAAA.isRoot""thhildrenRe arent)      if (node.isRoot
    ed      efresh             }
      return this.root;erty. e icowinkiow  e colleet.
@om the trill loleteNohohlight rs      efreshment
     * @meths  oConfd the fi cvarment
   referrs      efreshrender
     */
    render: Gb(fn) {

    rendvar values=0; 
   or();for the   for (i =p.children.length;.exnctiod the fi[i].  (autoRefresh & =/()) {
 dren.length;sb[sb (var i]
  true)d the fi[i].encetm ( th         

        unctif (autoRefresh & =/       return f * @metsb join(""   );

        return null     rs       i Noh  gurebackdimer = wiwusctio *os
   
    t For Ti
{n nullin the node, andsi gh = w loleteNohohlight lse;
       ment
   referlse;
       render
     */
    rendtrue)   first
      .this.getEl()true)ed      efresh            ;
  nodesaropag cp         l, 'for (var i in this._nodes ght it rSM cp =  }1          the
.soCole
    ght it r   collapseAAAAAAAAAvar valuessType 
   or();for the   for (i = 0; icollapseAAAAAAAAAtrue)d the fi[i]. ght it r dren_               }             arent);
  nodes ght it rSM cp =  }0t&&      the
.soCole
    ght it r   collapseAAAAAAAAAvar vssType 
   or();for the   for (i = 0; icollapseAAAAAAAAAAAAAtrue)d the fi[i].un ght it r dren_                   }             aren;
   ght it SM cp =  2)h usv         // ssl if  secoeventigurns nulsM cps
  ew lin ttmetho     }
    rendtrue)ed = false;
        =/       }

pseAtrue)tsLhis @r =/()) {
 dren.lentrue)/**
  odren_          unctithe
.lodbeeV=/()) {
     f       return nulll;
    },
is  /**   selvst    that win the in i    goleteNohohlight encAelvst  ode and r @methodeByI
    h
   
    hich we selvst  .e.    * ws branchNode
  we selvst  eleteNo  * @mencAelvst  render
    
       collapseAll: 
    h>()true)
    h&& 
    h< 0) )      if (node.isRooto              } return fer: pl()true)) {
     return fse();
(p)
    h> 
       collapseAAAAApl()pe) {
    r it mig }
            }
 p
     f       return nulll;
    },
  css arget  treh e  }
     that win the in i    ad flag.  The ill beco  If},
is  /**   selvst    that win the in i    lag.  Thibooleing  displayhe aramhe OM contacondmfferel {Hntn b * olag.  Thdoleae nocollea ing  displayed, only*hlight encD     ssNa * @metho @methodeByI
    h
   
    hich we selvst  .e.    * ws branchs  oConf
   css arget  treh e  }
   eleteNo  * @mencD     ssNarender
    
       collapseA * @met 
   )encAelvst   
     m    node.pr   collapseAAAAA"    
    cell" : "    blank
    cell"  );

        return nullG t},
  markup  treh   no Io       mayinstEvenreardhsof       pc wurn nullsupportndmfferel {Hypes{int // s ed, only*hlight enc
    vare.    * ws branchs  oConfT   getElnDataLoaderarentfroill becoeleteNo  * @menc
    varrender
     */
    render: Gb(fn) {

    rendsb[sb (var i]
  '<t_clo  d="    t_cloel' +}
     ram  + '" borara="0" cellpads @r="0" cell }
  @r="0" crget="    t_clo     
    ' +}
    
    {
        sb[sb (var i]
  '     -' +}_nodes/**
    ?'/**
    ':'ed.
     '   );

    is._nodes/n_clo                         sb[sb (var i]
  'f    -igurns nu' +}
     ght it rSM cp           }
       ht not bef)) {
                   sb[sb (var i]
  'f' +}
    f)) {
              }
       sb[sb (var i]
  '"><tr crget="    row">'
 h        var values=0;
<or();
    {=p.children.length;sb[sb (var i]
  '<tdmarget="     ell ' +etrue)   D     ssNa(i
 + '"><divmarget="     }
   "></div></td>'  r it mig }
        ll: 
                   if (nodesb[sb (var i]
  '<tdmbd="' +etrue)   ) && (  Id 

 dren.length;sb[sb (var i]
  '"marget="     ell '
 dren.length;sb[sb (var i]
  true)is  MsNa 
 
 dren.length;sb[sb (var i]
  '"><a href="#"marget="     }
   ">&#160;</a></td>'  r it mig }
        sb[sb (var i]
  '<tdmbd="' +etrue)dcOMhisElId{
        sb[sb (var i]
  '"marget="     ell '
 dren.lensb[sb (var i]
  true)dcOMhisassNa  + '     dcOMhis" '
 dren.lensb[sb (var i]
  (eAnim owrapch  '  owrap=" owrap" ' : ''
 dren.lensb[sb (var i]
  ' >'
 dren.lensb[sb (var i]
  true)hosfcOMhisetm ( th        sb[sb (var i]
  '</td></tr></t_clo>'
 h         * @metsb join(""    );

   );

return nullG t},
  markup  treh   dcOMhiss{int} nodeIn        is / sig nulwilnstEvenreardhsof       pc wurn nullsupportndmfferel {Hypes{int // s ed, only*hlight encfcOMhisetm e.    * ws branchs  oConfT   getElnDataLoaderarentfrout DOMhis ich will becoeleteNo  * @mencfcOMhisetm render
       */
    get * @met""  );

        return nullReg      / },
  l onlhe chiis  /**l     tu   }
    l   oenstobjecs with turn nullen a dynamic
        nly   }
     collebe   adling
     * whlight  *fmheheleteNo  * @m *fmhehstroy
    */
    destroy :true)lse;
                   true)   first
      .this.getEl()true)ed      efresh    
        ll: 
                   if (nodeer: yEditor(); }
) && (                  ll: tNodeB                el.))) {
    = el.))) {
   ) *pl
  (/\b    [lt][nmp]h*\b/gi,     is  MsNa 
   );

         }         }            return nullN @paraS      * treeighlight h
S      * treeigs branchs  oConfrty to reamhe OM containt} nodeIneleteNo  * @mh
S     element
     */
    get * @mettrue)      + " (" +}
     ram  + ")"  );

       return n* ats lhichitem  ns a co  nD loocuse" icoith tmurn n* sof     
  ewgu  lasc truy. e icooocuse @rloso do wa r h {objec_oocus         edItem  do wa r     Ats lhichDOMl Returns do wa r hod _deleteJ  * @m_oocus         edItem :n)       return n* DOMl Returnf     a  gh ly ghis outbrowsntfoocus do wa r h {objec_oocusedItem do wa r     DOMl Return do wa r hod _deleteJ  * @m_oocusedItem  e: el.is.popNode(no*sl;
    },is t      linart   ytaReturnsleltion(* @par    c wurn n* ac
   hion( eal a  gh  browsntfoocus do wa rhlight _c wHollFocus do wa rs branch if true
success do wa r hod _deleteJ  * @m_c wHollFocuselement
     */
    get * @mettrue) }
    sis EleturnsByTag
   ('a')  for ( >I0  );

       return n* Reion(s nD loocuseich        lec"  pre  oN/**
ng the*mary us_reion(Focus do wa r hod _deleteJ  * @m_reion(Focus:nder
       */
    getll: 
    _oocusedItemodeB                 .reion(List     
    _oocusedItem,'blur'   );

        
    _oocusedItem =/o              }        er: yE          se();
((yEditor();_oocus         edItem .shift 
 delroy :yesan of t meect}boolnrgetig m   ,  eh iton't dddddddd{
   *ion(CrgetveLihtedNode
      * Null.FOCUS_CLASS_NAMEe            }
          return n* Sets nD loocuseiltion(* @paaReturn . Use wItaLoadeion
 ncean
     ldren = oocuseilt bec  ns a collepossortaReturnsleltin . Use w) && ( i  b possibsets collepossorsed
   u  lasoocused  t.e.
  ()If/Loadefaillelt bec  ns a colleno possor
ng the*mary usfocus do wa rs branch if true
success do wa  * @mfocuselement
     */
    render: oocused =/()) {,c"  f:
     thg = node.odes (thithe
.ode) {
Focus)  collapseA newtrue)the
.ode) {
Focus._reion(Focus( th         

        er: namic
 P        ement
    */
    popNode: ode.odes(node) {
  odeB                emic
 P     s(node) {
  o                  (node) {
  s/**
                   }                   emic
 P     strue   
        {
     EleturnsBy  (             ement
    tNodeB                 * @met /     ([tl][pmn]h?)|( DOMhis))/). / t(el.))) {
      );

         t,collapseAAAAA'td't,collapseAAAAA"  f) }
    soad nfirstt,collapseAAAAAement
    tNodeB                {
  adlCrgetveLichtedNode
      * Null.FOCUS_CLASS_NAMEe                   ll: foocused; icollapseAAAAAAAAAAAAAer: aEl = el.is EleturnsByTag
   ('a')                      ll: aEll(var i  i                         aEl = aEl[0 otype.collapse.calllll    aEl.focus( th                        "  f)_oocusedItem =/aElth                             .   aEl,'blur',ement
     */
    rend                    "  f)the
.  }
      'oocusChang(d',{oheNot :"  f)the
.ode) {
Focus,nlyNot :o   }                               "  f)the
.ode) {
Focusevio              newNode            "  f)_reion(Focus( th                        }                           oocused =/       }

pseAAAAA        }  }

pseAAAAA    }  }

pseAAAAA    "  f)_oocus         edItem .push tNo               }           );

    is._oocused; icollapseAAAAAtrue)the
.  }
      'oocusChang(d',{oheNot :true)the
.ode) {
Focus,nlyNot :true}               true)the
.ode) {
FocusxtSveCha.length]  arent)      if (nodetrue)the
.  }
      'oocusChang(d',{oheNot :"  f)the
.ode) {
Focus,nlyNot :o   }               true)the
.ode) {
FocusxtSo              newtrue) reion(Focus( th         
        .isRoot(ocused  );

      n this.root;*uis ich bec  innoit possed, only*hlight encNot ;*uis * treeigs branchdeByInumb tyich bec  inn outbrpossed, onl  * @menc
   ;*uisrender
     */
    rendvar valuessTyp,  
u     0;
< or();for the   for (i= 0; icollapseAAAAA 
u   +  true)d the fi[i].enc
   ;*uis( th         
        .isRoot 
u   + 1        node.u.updateIcon()
    },

n * A str seco,gthe instobjec   ed by   the
 @},
ich will becl     tu   }
    loleteNohIt  u  lasthe dat*os
   the
 sets   ct    oa * ttead h will becl sja conse Node clItaLoaderasRoot()) {t       // Dair   yt  }
     lse;s do fetch ittaregarhodes ich node ty of t thiseeVi  Thi ed, only*hlight enc
   Dkfr ieserving the*s branch       |t()) {}  akfr icontaint} nothe
 @rt()) {t       // Dair   yt  }
     is / fr nulas that is displa  * @menc
   Dkfr ieserl
     */
    ccollapseAll:       sDConfig(vv {erasRoot()) {;  

        er: / f, / fsxtS);
 .merges (thidll  modh}
     fn) {

ccollapseAll:      /**
    chi/ fs)/**
     =/     /**
    ;  }
       ht n      this confli    / fsothis confli+)      this confli;  }
       ht ntrue) *naraHgdd p    / fso *naraHgdd p   true) *naraHgdd p;  }
       ht n                 / fso          true)       ;  }
       ht ntrue) owrapch  / fso owrap   true) owrap;  }
       ht ntrue)f)) {
       / fso))) {
    = 
    f)) {
      }
       ht ntrue)edit_clo    / fsoedit_clo =/     /dit_clo;  }
       ht n      /n_clo             / fsoen_clo          =/     /n_clo            }
       ht ntrue) ght it rSM cp    / fso ght it rSM cp =}
     ght it rSM cp   }
       ht ntrue)aropag cp         Up    / fsoaropag cp         Upl()true))ropag cp         Up   }
       ht ntrue)aropag cp         l, 'for / fsoaropag cp         l, 'l()true))ropag cp         l, '   }
       / fso       true)     {

ccollapseAvar valuessType 
   or();for the   for (i= 0; icollapseAAAAA/ f
  true)d the fi[i].enc
   Dkfr ieser                ll: / f
 ==/()) {v {erasRoot()) {;}  }

pseAAAAAfor the  push / f th         
        lingcor the   for (    / fso)h}
     fn)h}
    
  }
            }
/ fs        no     return nullG      / },
  linklnDataLoadeinvok        /**   h
     hlighted, only*hlight enc) && (Linkving the*s branchs  oConf
   javascript urnlhe ch && layhe     /**
     *} the hos  && (Linkelement
     */
    get * @met'rasRoot()) {;'  );

        return n* Sets nD lertyeeich   {objeche chiis  /**l    et.
thiseeVtesc*naarns.e.
  ()Oon
 publicl    / fr nulam {objies  u  lasset,ae nohlights.e.
  ()Vrtyesche cunkiownlam {objies to 1,nstgetig dat*os
   refN/ D.dll  * A st
ng the*mary ussnc
   s   if (t do wa r @methn    hs  oConf
    >
 @param {objylwilnst ttmethoa r @methertyee{  y}hertyeewilnst ttmethoa r @meth *fmhehch if true
iframhe OMe  // rue fithdoleaa  *fmheheleteem, truset
   s   if (temove
     *   ,hertye,  *fmheh  */
    getll: *   acharAt(0&& p '_'      );
 .isUnekfr nunot bn*    v     );
 .isF       (ot bn*    v ; icollapseAAAAAtruen*     fnertyea.length]  arent)      if (nodetrue)
   n*     fnertyea.length]  collapseAvar valuessType 
   or();for the   for (i= 0; icollapseAAAAAtrue)d the fi[i].set
   s   if (t *   ,ertye th         
        ling *fmheh  */
    getAAAAtrue) *fmheh             }
          return n*   && ( },
  l        edlsM cpso   hN/**
ng the*mary ush
             eleteem, truh
             :a     */
    collapseAll: 
    /n_clo                         // un ght it rseion
 ll:fu ly  ght it,
 drstFtree notre theia ly  ght it  edlitaLoadeh        eleteollapseAll: 
     ght it rSM cp =  1; icollapseAAAAAAAAAtrue)un ght it r    );

         t);
                    
     ght it r                 }         }            return n*    },
 ght it rlayhilt bec.
ng the*mary ush        eletea r @meth_sil OMeh if true
op */
al,ndon'nofed },
  h             * @met
     hi       :a     */
 _sil OM   collapseAll: 
    /n_clo                         odes (thithe
.soCole
    ght it r   collapseAAAAAAAAAodes (thithe
._ode) {
           ed; icollapseAAAAAAAAAAAAAtrue)the
._ode) {
           ed)un ght it r _sil OM                    }            AAAAtrue)the
._ode) {
           ed
  veCha.length] = p.children[ilapseAnim ght it rSM cp =}1           newtrue) set         C)) {
                   ll:       the
.soCole
    ght it r   collapseAAAAAAAAA;
  nodesaropag cp         l, 'for (var i in thhhhhhhhhvar valuessType
   or();for the   for (i= 0; icollapseAAAAA         newtrue)d the fi[i]. ght it r dren_                       }            AAAA }            AAAAht ntrue)aropag cp         Up                        ht ntrue)a {
  odeB                     newtrue)) {
  s_d the fi         ed( th                     }            AAAA }            }             ll:  _sil OM   collapseA     newtrue)the
.  }
      'h             ',true               }          }
          return n*    },
 ght it rlayhiff

  /**.
ng the*mary usun ght it reletea r @meth_sil OMeh if true
op */
al,ndon'nofed },
  h             * @met
     un ght it r:a     */
 _sil OM   collapseAll: 
    /n_clo                         // he deshsv    edbeeVsoCole
    ght it rst oiitaLthe n'noraa ly mat nu eide tyway          newtrue)the
._ode) {
           ed
  o              newtrue) ght it rSM cp =}0           newtrue) set         C)) {
                   ll:       the
.soCole
    ght it r   collapseAAAAAAAAA;
  nodesaropag cp         l, 'for (var i in thhhhhhhhhvar valuessType
   or();for the   for (i= 0; icollapseAAAAA         newtrue)d the fi[i].un ght it r dren_                       }            AAAA }            AAAAht ntrue)aropag cp         Up                        ht ntrue)a {
  odeB                     newtrue)) {
  s_d the fi         ed( th                     }            AAAA }            }             ll:  _sil OM   collapseA     newtrue)the
.  }
      'h             ',true               }          }
          return n* Checkot node tyet.
ir  the branch d the fiyif

  /** at  l        edlflileNod*usets     // Daigurns nulwilfu l,lecnnotre theia aigurns nu.e.
  ()If/" icon aropag cplitaLoadefurde tycet.
@om ) {
  
ng the*mary us_d the fi         edeletea r hod _deleteJ  * @m_c the fi         edrender
     */
    render: yes =/()) {,cno =/()) {
 dren.lenll: 
    /n_clo                         var valuessType
   or();for the   for (i= 0; icollapseAAAAA    switch(true)d the fi[i]. ght it rSM cp   h                     sse 0:B                     newno =/       }

pseAAAAA            gName) {
                   sse 1:B                     newyes =/       }

pseAAAAA            gName) {
                   sse 2:B                     newyes =/no =/       }

pseAAAAA            gName) {
               }            }             ll: yes    no   collapseA     newtrue) ght it rSM cp =}2  );

         t);
  ll: yes   collapseA     newtrue) ght it rSM cp =}1  );

         t);
   collapseA     newtrue) ght it rSM cp =}0              }          newtrue) set         C)) {
                   ll: true)aropag cp         Up                    ht ntrue)a {
  odeB                    true)) {
  s_d the fi         ed( th                 }             }         }            return n* Chang(sttnesf)) {
   seiltion(h
       // DOMhis  DOM elems  oa *f pre ,
  ode) {
f ght it rlay
ng the*mary us_set         C)) {
   eletea r hod _deleteJ  * @m_set         C)) {
   render
     */
    render: el = {
           t_cloel' +}
     ram n      var p = tNodeB            el.))) {
    = el.))) {
   ) *pl
  (/\b    -igurns nu\d\b/gi,'    -igurns nu' +}
     ght it rSM cp            }
    

};

htedNoaugturn(htedNode
    N/**,thtedNoutil.     P For Ti   })( thretur* A odstom htedNode
    N/** ns a co    sttnesuniquahn trampbrsiThe arvir gh ,ramhe OM contodes      // D.siTh@*    }
   htedNode
   siTh@arget     N/**
nTh@exMhids htedNode
    N/**
ea r @metho  *  {htedNode
      * NullnfT   @ter ton that hiis  /**lbelo nottosiTh@aets   ct  
eJ  htedNode
        N/**   ement
  (o  * odeB    // Iritia ize     // Dal if ing c @metsl )T        // Dai NaB    // in thh  dsse w  lin    // Daias  /ramhe OM contl )So  e collB    //  brgl   /**
hn thdarulam {objies a bin . Use
     rit(nu l,leu l,ldren_       re      *tFtreh tl     // Daneeen that wtter his mehat  edit sja  @met      *t*os
   sets   ct   ton eah bra editor c) {
  aaReturn . Useeem, truhrue)the
   o  * ;
};

htedNoexMhid(htedNode
        N/**, htedNode
    N/**,  ccollreturn nullTon(* @parypa * @metho h {objec_rypa * @mewa r     s      * treeig hod _deleteNo @me with "    N/**". Useeem, tru_rypa: "    N/**"       r/tEvenrears htedNode
    N/**
e @menc
    varrender
     */
    rend * @met""  );

        h
S     element
     */
    get * @mettrue)       );

        lse;
       render
     */
    rendtrue)the
.draw( th           n this.root;*uis ich bec  innconse NodeclItaEvenrears 
   s.enc
   ;*uis be sueltor c     // Das the inot*bet 
u  ingoleteNohohlight encNot ;*uis * treeigs branchdeByInumb tyich bec  inn out fulram {No  * @menc
   ;*uisrender
     */
    rendvar valuessTyp,  
u     0;
< or();for the   for (i= 0; icollapseAAAAA 
u   +  true)d the fi[i].enc
   ;*uis( th         
        .isRoot 
u    );

      n this.root
    },

n * A str seco,gthe instobjec   ed by   the
 @},
ich will becl     tu   }
    loleteNohIt  u  lasthe dat*os
   the
 sets   ct    oa * ttead h will becl sja conse Node clSihat hie     N/**ai Nautom cogures  x (thisby consNull, Node cl tu ownlakfr icontadynamcluseeV editor c.isRooeed.  Ifdkfr ieserving the seco,oon
  cOM els  tu   }
    loleteNohohlight enc
   Dkfr ieserving the*s branch       |t()) {}  akfr icontaint} nothe
 @rt()) {t     yt  }
  // Dai N/ fr nulas that is displa  * @menc
   Dkfr ieserl
     */
    ccollapseAvar value/ f, / fsxtS[],essType 
   or();for the   for (i= 0; icollapseAAAAA/ f
  true)d the fi[i].enc
   Dkfr ieser                ll: / f
 ==/()) {v {erasRoot()) {;}  }

pseAAAAA/ fsoaush / f th         
             }
/ fs        noAAAA 
ollapsstroy
    */
              stroy
    */
        is  * do nostroy
    */
 e.isRooto            focuselement
     */ 

} th(ement
     */
    alue{
 
  htedNoutil.{
 ,collapseA);
 
  htedNol;
 ,collapseA     
  htedNoutil.     thretur* T   
  with // Daamhe OM contl )TD load no @mete   /, the insur* eide tyafrty to nDataLoadenstobjecas     // D'ootibeLic    n * A st
n* ns a cos  thlea noafrty to  h {objecperty. tibeLrst time withousf)ickv);
h tur* tibeLes
   s
         amic
   /ed.
     lsM cpso  } nodeIn   By
d*usett&lt;ert href  h {objeco  } no/on that,ttnconbecolie cdu  la
d*udhang(d sof     
   tibeLes
   got*os
   in the in href.siTh@*    }
   htedNode
   siTh@arget Tt tN/**
nTh@exMhids htedNode
    N/**
ea raets   ct  
eJ r @methoDll  {* A st}oafrty to e c* A str cOM el&lt;ert dll  r   * oad
{*instobjec   rarentfroill becoel* P For  @re frty to i Noh  s    os p For  @re n * A str  if aVsoCole  h {objec*   . tibeLrur* A   vrtyescinn outoDll  Loadenstobjec   " iceqgh ly *   . am {objies eltion(* @pur* booto@re s     // Dadoleahsv  suco,am {objies,s ouew linnot*unekfr nu,ramod _d @rt(ment
  srur* A   atty bu / } linmaecl vail_clo  lt becref.d * ,h seco
d*udu  lasobjec   "tanimodstom atty bu / l )T * Null.enc
   (s)By   if (t d*udu  lasobjec   .isriellea i/**lbp iopso  } noatty bu / l
eJ r @methoP      {htedNode
    N/**   *is  /**   ) {
  a* @pur* r @meth/**
     h if true
 *
h{ritia  amic
   /ed.
     lsM cps 
  re st  ; ueltoDll  /**
    c
eJ  htedNode
    Tt tN/**   ement
  (oD * ,hoP     , /**
    chinoAAAAll: oD *    collapseAll: );
 .isS      oD *  ; icollapseAAAAAoDll  =
 etibeL:AoDll  }th         
        
     rit(oD * ,hoP     , /**
    c;
        
    " iUpLibeL oD *  ;}
    

};

htedNoexMhid(htedNode
    Tt tN/**, htedNode
    N/**,  ccolllreturn nullTon(CSS arget  treh e tibeLehref.  De withs  oa    tibeLict oidu  la
dn nullEvenread p on aroor Tea odstom amhe OM conta      in the cl    loleteNoho h {objectibeL ssNa * @metho     s      * tree referlibeL ssNa: "    libeL"       return nullT   
 mod  l Returnfidso  } notibeLehe chiis  /**oleteNoho h {objectibeLElId * @metho     s      * tree referlibeLElId  e: el.is.popNode(noullT   tng   treh e tibeLo  Iof t getum df     
   oDll   @mete   / oad
{    * eide tynstgfrty to nDataLoadenstobjecas     tibeLic    n * A st nDat
{    * iboole h {objecperty. "libeL"        pLoadeus loleteNoho h {objectibeL * @metho     s      * tree referlibeL  e: el.is.popNode(noullT   tng   treh e titlE:(tif tip)  treh e tibeLe Return do wNoho h {objectitlE * @metho     s      * tree refertitlE  e: el.is.popNode(noullT   href  treh   no I'ootibeLo  If}iops the nosn the in,;ert href  oad
{    * nst tt sof     it s
    s     // DloleteNoho h {objechref * @metho     s      * tree referhref  e: el.is.popNode(noullT   tibeLehref target,ade withs  oaode) {
fwindow do wNoho h {objectarget * @metho     s      * tree refertarget: "_"  f"       return nullT   * @parypa * @metho h {objec_rypa * @meeig hod _deleteNo @     s      * treeigme with "Tt tN/**". Useeem, tru_rypa: "Tt tN/**" no     return nullSets up     // DatibeL * @methomary ussncUpLibeL * @meeig @methoDll  rty to  cOM el&lt;ert tibeLic    n * A st   if aVtibeLep  if (t do weem, trusetUpLibeL: ement
  (oD *    ccollapseAll: );
 .isS      oD *  ; icollapseAAAAAoDll  =
 collapseAAAAAeferlibeL  oDll collapseAAAAA}th         t);
   collapseA    ll: oD * .sssNa   collapseA     newtrue)libeL ssNa   oD * .sssNa              }          }
     newtrue)libeL   oD * .libeL   return ftrue)libeLElId   "    libeLeL" +}
     ram thg = n        return nulll;
    },
  tibeLe Return do wNoho trehtedNode
    Tt tN/**oleteNohohlight encLibeLElving the*s branch* A st}o    aReturn do wNo  * @mencLibeLElelement
     */
    get * @met{
      true)libeLElId   );

        r/tEvenrears htedNode
    N/**
e @mencfcOMhisetm render
     */
    render: Gb(fn) {
    rendsb[sb (var i]
  true)href ? '<a' : '< }
n'
 dren.lensb[sb (var i]
  ' bd="' +e);
 .escapegetE true)libeLElId  + '"'todren.lensb[sb (var i]
  ' arget="' +e);
 .escapegetE true)libeLSssNa   + '"'todren.lenll: 
     ref                sb[sb (var i]
  'fhref="' +e);
 .escapegetE true) ref  + '"'todren.len    sb[sb (var i]
  'ftarget="' +e);
 .escapegetE true)target  + '"'todren.len }
       ht ntrue)titlE                sb[sb (var i]
  'ftitlE="' +e);
 .escapegetE true)titlE  + '"'todren.len }
       sb[sb (var i]
  ' >'
 dren.lensb[sb (var i]
  );
 .escapegetE true)libeL th        sb[sb (var i]
  true) ref?'</a>':'</ }
n>'
 dren.len * @metsb join(""   );

        n this.root
    },

n * A str seco,gthe instobjec   ed by   the
 @},
ich will becl     tu   }
    loleteNohIt  u  lasthe dat*os
   the
 sets   ct    oa * ttead h will becl sja conse Node clItaLoaderasRoot()) {t       // Dair   yttesc*naarn lse;s do fetch ittaregarhodes ich node ty of t thiseeVi  Thi ed, only*hlight enc
   Dkfr ieserving the*s branch       |t()) {}  akfr icontaint} nothe
 @rt()) {t     ill beclir   yttesc*naarn i N/ fr nulas that is displa  * @menc
   Dkfr ieserl
     */
    c    render: / f
  htedNode
    Tt tN/**.suif arget.enc
   Dkfr ieser.ch istrue   
       ht n/ f
 ==/()) {v {erasRoot()) {;  }
     newr/tN/** in the clam {objies
     new/ f)libeL   true)libeLtodren.lenll: 
    libeL ssNa  p '    libeL'    / f.sssNa   true)libeL ssNa   }
       ht ntrue)titlE    / f.titlE:
      titlE   }
       ht ntrue) ref    / f.href   true) ref   }
       ht ntrue)target& p '_"  f'    / f.target&
      target;  }
     new     }
/ fthg = n        h
S     element
     */
    get * @methtedNode
    Tt tN/**.suif arget.h
S     .ch istrue  + ": " +}
    libeLtodren        r/t
  re st       onLibeLC)ickelement
     */
    get * @met()) {
     f   get *fmhehstroy
    */
    destrohtedNode
    Tt tN/**.suif arget. *fmheh.ch istrue   
       er: EibeL   true)encLibeLEl            tibeLothis.getEl()true)libeLtodren.lenll: tibeLotag
   .h
Upif Ca  or =  'A'                tibeLohref   true) ref              tibeLotarget&
      target;odren.len }
   }    
} th})( thretur* A turu-in the cli    m OM conta     dmffersV editTt tN/** ita     oon
ur* iopsdisplayh u  lasamic
     noaf
ime.siTh@*    }
   htedNode
   siTh@arget MuruN/**
nTh@exMhids htedNode
    Tt tN/**
nTh@ @methoDll  {* A st}oafrty to e c* A str cOM el&lt;ert dll  r   * oad
{*instobjec   rarentfroill becoel* P For  @re frty to i Noh  s    os p For  @re n * A str  if aVsoCole  h {objec*   . tibeLrur* A   vrtyescinn outoDll  Loadenstobjec   " iceqgh ly *   . am {objies eltion(* @pur* booto@re s     // Dadoleahsv  suco,am {objies,s ouew linnot*unekfr nu,ramod _d @rt(ment
  srur* A   atty bu / } linmaecl vail_clo  lt becref.d * ,h seco
d*udu  lasobjec   "tanimodstom atty bu / l )T * Null.enc
   (s)By   if (t d*udu  lasobjec   .isriellea i/**lbp iopso  } noatty bu / l
eJ r @methoP      {htedNode
    N/**   *is  /**   ) {
  a* @pur* r @meth/**
     h if true
 *
h{ritia  amic
   /ed.
     lsM cps 
  re st  ; ueltoDll  /**
    c
eJh@aets   ct  
eJ  htedNode
    MuruN/**   ement
  (oD * ,hoP     , /**
    chinstrohtedNode
    MuruN/**.suif arget.aets   ct  .ch istrue,oD * ,oP     ,/**
    c;
nstrre      *tMurus usgh ly h low,oon
 iopsb possiwilnstEp   anoaf
ime.si    *m, truhrue)this confli+) ()) {
 h};

htedNoexMhid(htedNode
    MuruN/**ichtedNode
     t tN/**,  ccolllreturn nullTon(* @parypa * @metho h {objec_rypa * @meeig hod _deleteeigme with "MuruN/**". Useeem, tru_rypa: "MuruN/**".
} th(ement
     */
    alue{
 
  htedNoutil.{
 ,collapseA);
 
  htedNol;
 ,collapseA     
  htedNoutil.     thhretur* T    i    m OM conta ak/ }eide tyafrty to e c* A str treh  ur* iDll  arguturn   If}   inoafrty to,litaLoadeuelti   treh e dmultipur* i    ill becl(     tudu   DOM elt  ytl onlcbec).  If/ hHT @mete   ur* i,

n * A st,in lsoksad f    @mete   /perty. "l on" nDataLoadensur* objeche chiis  /**   dmultip.siTh@*    }
   htedNode
   siTh@arget getEN/**
nTh@exMhids htedNode
    N/**
ea raets   ct  
eJ r @methoDll  {* A st}oafrty to e c* A str cOM el&lt;ert dll  r   * oad
{*instobjec   rarentfroill becoel* P For  @re frty to i Noh  s    os p For  @re n * A str  if aVsoCole  h {objec*   . l onrur* A   vrtyescinn outoDll  Loadenstobjec   " iceqgh ly *   . am {objies eltion(* @pur* booto@re s     // Dadoleahsv  suco,am {objies,s ouew linnot*unekfr nu,ramod _d @rt(ment
  srur* A   of theatty bu / } linmaecl vail_clo  lt becref.d * ,h seco
d*udu  lasobjec   "tanimodstom atty bu / l )T * Null.enc
   (s)By   if (t d*udu  lasobjec   .isriellea i/**lbp iopso  } noatty bu / l
eJ r @methoP      {htedNode
    N/**   *is  /**   ) {
  a* @pur* r @meth/**
     h if true
 *
h{ritia  amic
   /ed.
     lsM cps 
  re st  ; ueltoDll  /**
    c
eJh@ @meth        h if true
sn the is  node tyi  This used.  Is/, the 
{*instrfresh &   if or   if@},
a horizcOM l lincl   /e ch && eibset. If/ hHTbsetur* i,
Thisdmultipin,;ert  DOMhis foad Noh  s}
    taLthe ahsv  occupiingol* T    op */
tEp    / } rampfres{
  so  } notused.  Ifamhe OM contalogis d*che cthe node.  Isgol*  
  re st  ; ueltoDll          
eJ  alueHN
   ement
  (oD * ,hoP     , /**
    ,                ll: oD *    collapseA
     rit(oD * ,hoP     , /**
    c;
        
     ritfcOMhis(oD * ,h        ;}
    
};

 htedNode
    getEN/**
  HN;
htedNoexMhid(HN, htedNode
    N/**,  ccolllreturn nullTon(CSS arget  treh e l onlcbOMhis  DOM elem.  De withs  oa    l onict ourn nulldu  lasEvenread p on aroor Tea odstom amhe OM conta      in the cl    loleteNoho h {objecdcOMhisassNa * @metho     s      * tree referdcOMhisassNa: "    l on" no     return nullT   getElcbOMhis on uelthe chiis  /**   dmultipoleteNoho h {objechtm e.    * w     s      * tree referhtm ree: el.ireturn nullT   * @parypa * @metho h {objec_rypa * @meeig hod _deleteNo @     s      * treeigme with "getEN/**". Useeem, tru_rypa: "getEN/**"       return nullSets up     // DatibeL * @methomary us ritfcOMhis * @meeig @methoDll  {* A st}oAi cvarfrty to e c* A str cOM el&lt;ai cvarfp  if (t do weeh@ @meth        h if true
de   melest       // DaLoadenstrfresh &   if  wurn nullbsetyi  Thi. Useeem, tru ritfcOMhis: ement
  (oD * ,                    
    sncetm (oD *  ;}
       
    dcOMhisElId   "    dcOMhiseL" +}
     ram th
       ll:  );
 .isUnekfr nun        )) Atrue)                  ;  }g = n        return nullSyossroniz s     // Dll onic  //    no I'ooccOMhis * @meeigmary ussncetm e.    * w @metho {* A st |rty to | getEEleturn }oAi cvarfrty to,e n * A str cOM el&lt;ai cvarfp  if (tc    n getElaReturn do wNo  * @msncetm l
     */
 o            
    cvarf=: );
 .is       o  && 'cvar'  ltoch  o cvarf: o   return fer: yEditor(); }
CcOMhisEl            ;
  tNodeB            ll: o.no IT    && o.no IT      }1    ootag
   odeB                el.this.getEl()""  );









 t);
   collapseA     neweLothis.getEl()true)cvar              }          }
            r/tEvenrears htedNode
    N/**
e @m// If  h {objeccvarfi Natrty to,litasets     this.getEl treh   no I
e @m// If  of t  n getEEleturn fithdefersVappfre to itt*os
   the
 d) {
     getElbas cls   ctrampis ed bt
e @mencfcOMhisetm render
     */
    rendht nt   i    il cvarf=  }"rty to"odeB             * @mettrue)cvar           t);
   cB            HN._deferh &
   s.aush true               ll:  HN._
imerodeB                HN._
imerl()window." iTimeout(ement
     */
    rend            er: n   }

pseAAAAA        se();((n
  HN._deferh &
   s.aop 
 del  }

pseAAAAA            n; }
CcOMhisEl  .appfrefirst(n)cvar th                     }            AAAA    HN._
imerl()o              newNode},0_               }             * @met""  );

     }      node.u.updateIcon()
    },

n * A str seco,gthe instobjec   ed by   the
 @},
ich will becl     tu   }
    loleteNohIt  u  lasthe dat*os
   the
 sets   ct    oa * ttead h will becl sja conse Node clItaLoaderasRoot()) {t     yt// Datse;s do fetch ittaregarhodes ich node ty of t thiseeVi  Thi ed, only*hlight enc
   Dkfr ieserving the*s branch       |t()) {}  akfr icontaint} nothe
 @rt()) {t     yt// Dai N/ fr nulas that is displa  * @menc
   Dkfr ieserl
     */
    c    render: / f
  HN.suif arget.enc
   Dkfr ieser.ch istrue   
       ht n/ f
 ==/()) {v {erasRoot()) {;  }
       / f.hvarf=:true)cvar               }
/ fthg = n 
} th     return n*oAi ats lhichgetEN/**
  x (thisl if getElEleturns ns a co  nD ir rarentde urn n*odeferh & d) {
     bas clthe
 s   ctrampis rfresh &.
ng the* h {objec_deferh &
   s
ng the*     htedNode
    getEN/**[]eleteeigme with []eleteeig hod _deleteeigsM cis dispa  HN._deferh &
   s(fn) {
    return n*oA system 
imerlertyeeobjec   markt node tyeodeferh & Ep    ontadynpfre to.
ng the* h {objec_
imer
ng the*     System Timer
ng the*
  with /uad
{   eig hod _deleteeigsM cis dispa  HN._
imerl()o     })( th(ement
     */
    alue{
 
  htedNoutil.{
 ,collapseA);
 
  htedNol;
 ,collapseA     
  htedNoutil.     ,collapseACal*naar
  htedNode
    Cal*naarthhretur* A D *e-in the cli    m OM conta     dmffersV editTt tN/** ita      ofobjsur* htedNode
    Cal*naar}boolnrin-lincl/ditor,t    vail_clour* If Cal*naar}i,
This vail_clo fithbecol/ } oole l eltTt tN/**.siTh@*    }
   htedNode
   siTh@arget D *eN/**
nTh@exMhids htedNode
    Tt tN/**
nTh@ @methoDll  {* A st}oafrty to e c* A str cOM el&lt;ert dll  r   * oad
{*instobjec   rarentfroill becoel* P For  @re frty to i Noh  s    os p For  @re n * A str  if aVsoCole  h {objec*   . tibeLrur* A   vrtyescinn outoDll  Loadenstobjec   " iceqgh ly *   . am {objies eltion(* @pur* booto@re s     // Dadoleahsv  suco,am {objies,s ouew linnot*unekfr nu,ramod _d n@rt(ment
  srur* A   atty bu / } linmaecl vail_clo  lt becref.d * ,h seco
d*udu  lasobjec   "tanimodstom atty bu / l )T * Null.enc
   (s)By   if (t d*udu  lasobjec   .isriellea i/**lbp iopso  } noatty bu / l
eJ r @methoP      {htedNode
    N/**   *is  /**   ) {
  a* @pur* r @meth/**
     h if true
 *
h{ritia  amic
   /ed.
     lsM cps 
  re st  ; ueltoDll  /**
    c
eJh@aets   ct  
eJ  htedNode
    D *eN/**   ement
  (oD * ,hoP     , /**
    chinstrohtedNode
    D *eN/**.suif arget.aets   ct  .ch istrue,oD * ,hoP     , /**
    c;
};

htedNoexMhid(htedNode
    D *eN/**ichtedNode
     t tN/**,  ccolllreturn nullTon(* @parypa * @metho h {objec_rypa * @meeig     s      * treeig hod _deleteNo @me with  "D *eN/**". Useeem, tru_rypa: "D *eN/**"       return n* Configu   onta* A str treh   Cal*naar}/ditor,t   obje.
ng theSe
 <a href="http://delll {ob.yahoo)ed /yui/cal*naar/#iOMhrna */
aliza */
">http://delll {ob.yahoo)ed /yui/cal*naar/#iOMhrna */
aliza */
</a>
ng the* h {objeccal*naarConfig
Useeem, trucal*naarConfigree: el.iccolllreturn null If htedNode
    Cal*naar} t  vail_clo fithLoadeaop up a Cal*naar}   hiserea inw dlln   Ode twiso fith())ls eackd brge l elt&lt;input&gt;  tng boxed, only*hlight foadEditorCDOM eleme.    * w @meth/ditorDll  {htedNode
      * Null./ditorDll }  a/, trtcutt*os
   sM cisa* A strhol  @re/dit to in trmaeserving the*s brancvoid. Useeem, trufoadEditorCDOM elemelement
    /ditorDll    ccollapseAaluech ,r DOM elem = editorDll .inputCDOM elemthg = node.odes);
 .isUnekfr nunCal*naar del  }

pseAAAAA{
   *pl
  CrgetveditorDll .editorPanel,'    -edit-D *eN/**','    -edit- t tN/**')              htedNode
    D *eN/**.suif arget.foadEditorCDOM elem.ch istrue, editorDll )              s bran  );

     }         ;
  tditorDll .no IT    !  true)     odeB            editorDll .no IT      true)     {
            editorDll .ssv OnEisere) ()) {
 h            editorDll .no I.  stroyEditorCDOMurns(editorDll )  h            editorDll .input       =ech l()onw Cal*naar( DOM elem.appfrefirst(docuturn  x (thEleturn('div')
   );

        ht ntrue)fal*naarConfigdel  }

pseAAAAA    fal.cfg.applyConfigntrue)fal*naarConfig,dren_                  fal.cfg.  }
Queu                 }  }

pseAAAAAfal."  pre     .subscribe(ement
     */
    rend        true)the
._oloseEditor dren_              },true,dren_           t);
   collapseA    ch l()editorDll .input        );

     }         editorDll .ol Vrtyel()true)libeLtodren.lenfal.cfg." i   if (t ""  pre  ",true)libeL,/()) {v   return fer: delim =ech .cfg.g i   if (t 'DATE_FIELD_DELIMITER'   
       er: pageD cp =}
    tibeLrsplit(delim)todren.lenfal.cfg." i   if (t 'pagedlln',pageD cp[ch .cfg.g i   if (t 'MDY_MONTH_POSITION') -1] + delim + pageD cp[ch .cfg.g i   if (t 'MDY_YEAR_POSITION') -1])todren.lenfal.cfg.  }
Queu     odren.lenfal.rfresh    dren.lenfal.o{
 CDOM elem.focus( th    f   getopNode(no*sl;
    },D lertyee editor cinputaaReturn . Use wOvenrears 
   .is EditorVrtye.
ng the*mary usis EditorVrtyee.    * w @meth/ditorDll  {htedNode
      * Null./ditorDll }  a/, trtcutt*os
   sM cisa* A strhol  @re/dit to in trmaeserving the*s branchs  oConfdlln hisered. Useeem, * @mencEditorVrtyeelement
    /ditorDll    cdren.lenodes);
 .isUnekfr nunCal*naar del  }

pseAAAAAs branceditorDll .inputEleturn.ertyea.length]  arent)      if (nodealuech l()editorDll .input      ,
    rend        d cp =}fal.is    pre  D cps( [0],
    rend        dd fn) {

rend        dd[ch .cfg.g i   if (t 'MDY_DAY_POSITION') -1] = dlln    Dat                 dd[ch .cfg.g i   if (t 'MDY_MONTH_POSITION') -1] = dlln    MDOMh() + 1              dd[ch .cfg.g i   if (t 'MDY_YEAR_POSITION') -1] = dlln    F: eYear                     }
/d join(ch .cfg.g i   if (t 'DATE_FIELD_DELIMITER'             }
      colllreturn nullFinh ly dmultips     /ewly hisered d cp inn out ful ed, onlyOvenrears 
   .dmultipEditedVrtye.
ng tthe*mary usdmultipEditedVrtyee.    * w @methertyee{getEnfdlln wilnstdmultipinl    "tanid eltion(* @p.
ng ttheT    dll   t  dlint*os
   * @paunescaped via     this.getEl h {obje.e.    * w @meth/ditorDll  {htedNode
      * Null./ditorDll }  a/, trtcutt*os
   sM cisa* A strhol  @re/dit to in trmaeserving thm, trudmultipEditedVrtyeelement
    ertye,/ditorDll    cdren.lener: n/**   editorDll .no I          * @p.EibeL   ertyea.length] n   .is LibeLEl  othis.getEl()ertyea.leng       n this.root
    },

n * A str seco,gthe instobjec   ed by   the
 @},
ich will becl     tu   }
    loleteNohIt  u  lasthe dat*os
   the
 sets   ct    oa * ttead h will becl sja conse Node clItaLoaderasRoot()) {t       // Dair   yttesc*naarn lse;s do fetch ittaregarhodes ich node ty of t thiseeVi  Thi ed, only*hlight enc
   Dkfr ieserving the*s branch       |t()) {}  akfr icontaint} no// Dair ()) {t     ill beclir   yttesc*naarn i N/ fr nulas that is displa  * @menc
   Dkfr ieserl
     */
    c    render: / f
  htedNode
    D *eN/**.suif arget.enc
   Dkfr ieser.ch istrue   
       ht n/ f
 ==/()) {v {erasRoot()) {;  }
       ht ntrue)fal*naarConfigdel / f.fal*naarConfig
  true)dal*naarConfig
  }
            }
/ f;}
    


} th})( th(ement
     */
    alue{
 
  htedNoutil.{
 ,collapseA);
 
  htedNol;
 ,collapseA     
  htedNoutil.     ,collapseATV
  htedNode
    TonsNull, Node seATV ttto =/TV. ttto    {

olllreturn nullAn * A st n  "tanimin trmaeser objeche cin-lincl/dit    * treei     ade
   s(if

adeTonsNulls.hIt  cOM els: * treei<ul>
ng the<li>ant
ve h if true,h node tyt  lin t  n ant
ve ceadeeditor </li>
ng the<li>whoHasI  {htedNode
      * NullnfT * Null ton that hian i Node) {
   ob&lt;ert editor</li>
ng the<li>no IT    hs  oConfertyeeichsM cisaN/**.       h {obje, h lows rfueltofcinputaaReturn ht // Dai Nint} nos        .</li>
ng the<li>editorPanele{getEaReturn (&lt;div&gt;)}aaReturn hol  @re    th-lincl/ditor</li>
ng the<li>inputCDOM eleme{getEaReturn (&lt;div&gt;)}aaReturn  seco,Loadehol s
   t   -in the clinputaaReturn(s) wilnstfoadhisby cheufoadEditorCDOM elemohlight</li>
ng the<li>buttetsCDOM eleme{getEaReturn (&lt;div&gt;)}aaReturn  seco,hol s     &lt;buttet&gt; aReturnsl    Ok/CthatLo  If}youndon'nowect}bn so  } nobuttets, hr Te ofvia CSS sssNas,ndon'no  stroye o</li>
ng the<li>no I {htedNode
    N/**  his mehat *os
   N/** be @re/ditet</li>
ng the<li>ssv OnEisereh if true,h node tyt   Eiserekey/, the ins anceptnulas a Ssv   omm    (Esc.n t  lwips  ak   as CthatL),udmu_clo     this -linclinputaaReturns </li>
ng the<li>ol Vrtyel{  y}hfertyeebefanim/dit   </li>
ng the</ul>
ng the Editor } linfhe
 on uelt  ill* A st n  "tanim dli */
al dll loleteNoho h {objeceditorDll oleteNohosM cis disp oho trehtedNode
    T * Null displa  * @mTV./ditorDll  =
 collapseAant
ve:()) {,collapseAwhoHasI :eu l,l//  seco,T * Null ibooi eleteollano IT   :eu l,         editorPanel:eu l,         inputCDOM elem:eu l,         buttetsCDOM elem:eu l,         no I:eu l,l//  seco,N/ Dai Nbe @re/ditet         ssv OnEiser: rue          ol Vrtye:unekfr nu         // Eaco,* @parypaai Nfhe
 on  dll tu ownlam {objies *os
  t gelitaseeschin . Use}th     return n*oVrtidll@rt(ment
       /ditet d * ,hperty.  editor cT * Null ton that sc {o,urn n*oreceiv s     arguturns (/ewVrtye, ol Vrtye,,* @pIon that)urn n*o    r    },
eide ty,D lertidlly. (       - cOven ed; ertyeeir*unekfr nu.
ng theAn unekfr nu      }
Loadeare    
ert editor  editoloslay
ng the* h {objecvrtidll@r
ng the*rypaa(ment
  
ng the*
  with /uad
{    oho trehtedNode
    T * Null displa  * @mTV ttto.vrtidll@rl()o          return n*oEisry pointche cinitia iz&lt;ert edit to  lug-in.
ng theT * Null Loadecet.
@o t hlight ntadnitia iz&lt;if  ofexists
ng the*mary us_dnitEditor
{    oho trehtedNode
    T * Null displa r hod _deleteJ   * @mTV ttto._dnitEditor   ement
     */
    rendreturn nu nullFiris  noltion(uelrsf)ickseiltion(ok buttetyif

  /** editor
{    spla re    
editorSsv      * @meng the*rypaaCdstom     * @meng the* @methoArgs./ewVrtye {mixed}     /ew ertyeejdst hisered. Useeg the* @methoArgs.ol Vrtyel{mixed}     ertyeeiriginh ly inn out fulram {Ng the* @methoArgs./o I {htedNode
    N/**  } no// Dans a cos cheufocus             oho trehtedNode
    T * Null displspla  * @mmmmmtrue)dx (thE     "editorSsv      ",ttnco   odren.lenreturn nu nullFiris  noltion(uelrsf)ickseiltion(cthatL buttetyif

  /** editor
{    spla re    
editorCthatL     * @meng the*rypaaCdstom     * @meng the* @meth{htedNode
    N/**  // Dansno// Dans a cos cheufocus             oho trehtedNode
    T * Null displspla  * @mmmmmtrue)dx (thE     "editorCthatL     ",ttnco   odren}th     return n*oEisry pointco  } noedit to  lug-in.
ng theT * Null Loadecet.
@o t hlight if  ofexists  nolta // DatibeL i No)ickedeletea rmary us_// DEdit    * trhe* @meth/o I {htedNode
    N/**  } no// Danilnst/ditet     he*s branchBif true
 ryeewilindiclln w    
   // Dai Nedit_clo     are    
bn sfurde tybubsplayhbranch d)ick.
{    oho trehtedNode
    T * Null displa r hod _deleteJ     * @mTV ttto._// DEdit      ement
    // D  */
    getll: */**.foadEditorCDOM elemo   no**.edit_clo        if (nodealuein,;eopLeft,obuttets, buttet, editorDll  =/TV.editorDll {
            editorDll .ant
ve =/       }

pseAAAAAeditorDll .whoHasI    veCha.length] = p.ll:  editorDll .no IT    */
    rend        // Fixes: http://yuilibrary)ed / ttA sts/yui2/ticket/2528945
    rend        editorDll .editorPanel   edditor(); }
El  .appfrefirst(docuturn  x (thEleturn('div')
                  {
   dlCrgetved,'    -tibeL-editor')                  ed.t_cIram  =}0                   buttets   editorDll .buttetsCDOM eleme  ed.appfrefirst(docuturn  x (thEleturn('div')
                  {
   dlCrgetvbuttets,'    -buttet- DOM elem')                  buttety= buttets.appfrefirst(docuturn  x (thEleturn('buttet')
                  {
   dlCrgetvbuttet,'    ok')                  buttetothis.getEl()' '                  buttety= buttets.appfrefirst(docuturn  x (thEleturn('buttet')
                  {
   dlCrgetvbuttet,'    cthatL')                  buttetothis.getEl()' '                       .onvbuttets, 'd)ick',lement
    /v */
    rend            er: target&
      .hos arget /v ,
    rend                editorDll  =/TV.editorDll ,
    rend                n/**   editorDll .no I,
    rend                "  f   editorDll .whoHasI th                    ll: {
  cosCrgetvtarget,'    ok')del  }

pseAAAAA                 .seopE     /v th                        "  f._oloseEditor dren_                       }            AAAA    ll: {
  cosCrgetvtarget,'    cthatL')del  }

pseAAAAA                 .seopE     /v th                        "  f._oloseEditor ()) {v                       }            AAAA})                   editorDll .inputCDOM eleme  ed.appfrefirst(docuturn  x (thEleturn('div')
                  {
   dlCrgetveditorDll .inputCDOM elem,'    -input')                        .onved,'keydown',ement
    /v */
    rend            er: editorDll  =/TV.editorDll ,
    rend                KEY
  htedNoutil.KeyList    .KEY,
    rend                "  f   editorDll .whoHasI th                    switch  /v.keyC/ D  */
    getttttttttttttttttdsse KEY.ENTER:B                     new         .seopE     /v th                            ;
  tditorDll .ssv OnEiser  */
    gettttttttttttttttt        "  f._oloseEditor dren_                               }            AAAA            gName) {
                      dsse KEY.ESCAPE:B                     new         .seopE     /v th                            "  f._oloseEditor ()) {v                              gName) {
                   }            AAAA})                  t);
   collapseA     newed   editorDll .editorPanel              }  }

pseAAAAAeditorDll .no I =/nod    }

pseAAAAA;
  tditorDll .no IT     */
    gettttttttt{
   *moveCrgetved,'    -edit-' +etditorDll .no IT                  }  }

pseAAAAA{
   dlCrgetved,'     -edit-' +en/**.                    // Fixes: http://yuilibrary)ed / ttA sts/yui2/ticket/2528945
    rend    {
  sesassNaved,'dmultip','block')              {
  sesXYved,{
     XYvn   .is CcOMhisEl  )
              // upewil  li  }

pseAAAAAed.focus( th            */**.foadEditorCDOM elem(editorDll )  h             * @mett    @m// If  nlincl/ditor  vail_clo fdon'no o
bn or(@re/ls loleteN   }  }

}th     return n*oMary ush
ins associ(thisl if an e    
(d)ick     , dbLC)ick     
   /iserKeyPrissed; on aop up ert  DOMhiss editor
{   * hIt  uad Noh  corrispore to  /** editN/ Damary u.
ng the*mary uson     EditN/ D * trhe* @methoArgs {* A st}o       the datas arguturns on T * Null e    
list    s
ng the* trehtedNode
    T * Null dispJ   * @mTV ttto.on     EditN/ D   ement
    oArgs  */
    getll: oArgs ton thatof htedNode
    N/**; icollapseAAAAAoArgs.editN/ D( th         t);
  ll: oArgs./o I ton thatof htedNode
    N/**; icollapseAAAAAoArgs.no**.editN/ D( th         h         * @met()) {
     fth     return n*oMary ush
ins perty.  noltion( nlincl/dit to i Nfr ishinl    ert editor issh
ins plosedeletea rmary us_oloseEditor * trhe* @methssv chBif true
 ryeei  } noedited vrtyeeissh
ins ssv d, ()) {t   dmucardedeletea r hod _deletethe* trehtedNode
    T * Null dispJ   * @mTV ttto._oloseEditor   ement
    ssv  */
    render: ed =/TV.editorDll ,
    rend    n/**   ed.no I,
    rend    olose =/       }

pseA// http://yuilibrary)ed / ttA sts/yui2/ticket/2528946  }

pseA// _oloseEditor he desnowins perty. a 
bn s
ime, e     noltionrps the  tibeLe ditor Ep    }

pseA// so  e nedat*osensulin   rps thon loleteN   ll:  n/** || !ed.ant
vev {erasRoo;  }
       ht nssv  */
    rend    olose =/ed.no I.ssv EditorVrtye(ed; !==/()) {
 dren.len t);
   collapseA    or();  }
       'editorCthatL     ',,* @p th         h}
       ht nolosedel  }

pseAAAAA{
  sesassNaved.editorPanel,'dmultip','ecnn')              ed.ant
ve =/()) {
 dren.len    n/**.focus( th        }  }

}th     return n*ooEisry pointche cT * Null   d stroyemary ush
id stroyewh _dv ty,D ledit to  lug-in cos  x (thieletea rmary us_  stroyEditoreletea r hod _deletethe* trehtedNode
    T * Null dispJ  dispTV ttto._  stroyEditor   ement
  ( */
    render: ed =/TV.editorDll           ;
  tdo   ed.no IT    && (!ed.ant
ve || ed.whoHasI   =itor())del  }

pseAAAAA     . *moveList    ved.editorPanel,'keydown')                   . *moveList    ved.buttetCDOM elem,'d)ick')              ed.no I.  stroyEditorCDOMurns(ed               docuturn body  *moveCirst(ed.editorPanel)              ed.no IT      ed.editorPanel   ed.inputCDOM eleme  ed.buttetsCDOM eleme  ed.whoHasI    ed.no Il()o              newed.ant
ve =/()) {
 dren.len}  }

}th     er: N ttto =/htedNode
    N/**. ttto    {

olllreturn nclSign)) ei  } notibeL i Nedit_clo.  (Ignanid oltTt tN/**ssl if href ses.)eletea r h {objecedit_clour  the*rypaa if tru          he* trehtedNode
    N/ D * trh  dispN ttto.edit_cloe) ()) {
 h    return nclaops up ert  DOMhiss editor,ei  } nrps thon c  //    no I i N/ clanid edit_clour  the*mary useditN/ D * tr he* trehtedNode
    N/ D * trh   dispN ttto.editN/ D   ement
     */
    rendtrue)the
._// DEdit   strue   
   };

     ret Pl
  hol eme      ement
   w    , the iaroor Teion( nlincl// DatibeL editor.
{    oh  Leav to itt" icto ing cLoadeindiclln w    
 ill beclrypaai Nnot*edit_clo.
{    ohIt/, the ins Evenread p byd.  Is/w    aroor Te nlincl/dit to.
{    oh T   N/**-in the cledit to aReturn (inputabox, tng ania or  h _dv t)/, the ins tonen ed to*oseditorDll .inputCDOM elem ed, only*hlight foadEditorCDOM eleme.    * w @meth/ditorDll  {htedNode
      * Null./ditorDll }  a/, trtcutt*os
   sM cisa* A strhol  @re/dit to in trmaeserving the*s brancvoid. Useeee* trehtedNode
    N/ D * trrh  dispN ttto.foadEditorCDOM elemo()o      h    return nclN/**-in the cld stroyeement
   wosempty ert  DOMhiss o  } no/olincl/ditor panel.
ng theT illement
   i Noh  wod nodsse gl   na *ve r   * oad purge

adeposdispe e    so    r move ert editor  DOMhiss.
ng theMary us     .purgeEleturn i Nsome h _  Ds
   so if  of u  las *pl
  isby in thec      . *moveList    s,y of t bet   /*oo o
so.
ng the*mary us  stroyEditorCDOMurnse.    * w @meth/ditorDll  {htedNode
      * Null./ditorDll }  a/, trtcutt*os
   sM cisa* A strhol  @re/dit to in trmaeserving the* trehtedNode
    N/ D * trrh  dispN ttto.  stroyEditorCDOMurns   ement
    /ditorDll    cdren.len// IrNoh  wod nodsse,ei  } nlinputaaditor (suco,os cheuCal*naar aias  /rd stroyemary ucdren.len// wn(cth,oon
 sry  oa *move 
adeposdispe e    so   itloleteN        .purgeEleturnveditorDll .inputCDOM elem,dren_          editorDll .inputCDOM elem this.getEl()''
     fth     return n*oSav s     vrtyeehisered to*osert editor.
ng the*mary usssv EditorVrtyee.    * w @meth/ditorDll  {htedNode
      * Null./ditorDll }  a/, trtcutt*os
   sM cisa* A strhol  @re/dit to in trmaeserving the*s branch()) {ti  Thne}oafs branco  exantn
 ()) {tLoadeare    
ert editor  editoloslay
ng tthe* trehtedNode
    N/ D * trrh  dispN ttto.ssv EditorVrtye   ement
    /ditorDll    cdren.lener: n/**   editorDll .no I,
    rend    ertye,
    rend    ertidll@rl()o/**.the
.ertidll@r   return fertyel()true)encEditorVrtye(editorDll )  h        odes);
 .isFment
  (ertidll@r)del  }

pseAAAAAertyel()ertidll@r ertye,/ditorDll .ol Vrtye,* @p th            odes);
 .isUnekfr nunertye)del  }

pseAAAAA     * @met()) {
             }          }
     newht ntrue)the
.  }
       'editorSsv      ',el  }

pseAAAAA/ewVrtye:ertye,
    rend    ol Vrtye:/ditorDll .ol Vrtye, dren.len    n/**:n/ D * trr    ; !==/()) {del  }

pseAAAAAtrue)dmultipEditedVrtye ertye,/ditorDll  
 dren.len}  }

}th      return no*sl;
    },D lertye(s)  editor cinputaaReturn(s) e Node clS the ins Evenread p bydeaco,* @parypa ed, only*hlight encEditorVrtyee.    * w @meth/ditorDll  {htedNode
      * Null./ditorDll }  a/, trtcutt*os
   sM cisa* A strhol  @re/dit to in trmaeserving the*s branch  y}hvrtyeehisered
ng tthe* trehtedNode
    N/ D * trrh   * trrN ttto.encEditorVrtye   ement
    /ditorDll    cdrenfth     return nullFinh ly dmultips     /ewly hdited vrtye(s) inn out ful ed, onlyS the ins Evenread p bydeaco,* @parypa ed, only*hlight dmultipEditedVrtyee.    * w @methertyee{getEnfertyeewilnstdmultipinl    "tanid eltion(* @pe.    * T    dll   t  dlint*os
   * @paunescaped via     this.getEl h {obje.e.    * w @meth/ditorDll  {htedNode
      * Null./ditorDll }  a/, trtcutt*os
   sM cisa* A strhol  @re/dit to in trmaeserving the* trehtedNode
    N/ D * trrh  dispN ttto. multipEditedVrtye   ement
    ertye,/ditorDll    cdren}th     er: TN ttto =/htedNode
    Tt tN/**. ttto    {

ccolllreturn null Pl
  t  n &lt;input&gt;  tng box eltion(inputa DOM elem     lse;s } notibeL tng  to*osii ed, only*hlight foadEditorCDOM eleme.    * w @meth/ditorDll  {htedNode
      * Null./ditorDll }  a/, trtcutt*os
   sM cisa* A strhol  @re/dit to in trmaeserving the*s brancvoid. Useeee* trehtedNode
    Tt tN/**oleteNo  dispTN ttto.foadEditorCDOM elemo()ement
    /ditorDll    ccollapseAalueinput   }

pseA// Ifotist  /** editid e Nnot*int} nos        ,os ch thon ,adeletTe of    foadeitr  if ou: editor         ;
  tditorDll .no IT    !  true)     odeB            editorDll .no IT      true)     {
            editorDll .ssv OnEisere)        }

pseAAAAAeditorDll .no I.  stroyEditorCDOMurns(editorDll )  h            editorDll .inputEleturn =(inputa= editorDll .inputCDOM elem.appfrefirst(docuturn  x (thEleturn('input'))  h         t);
   collapseA    // i  } notist  /** editid wa Nint} nos     ime, rfueltor cinputaaReturn . Use        ;nputa= editorDll .inputEleturnth         h        /ditorDll .ol Vrtyel()true)libeLtodren.lenlnput.vrtyel()true)libeLtodren.lenlnput.focus( th        lnput."  pre()
     fth     return no*sl;
    },D lertyee editor cinputaaReturn . Usee wOvenrears 
   .is EditorVrtye.
ng tnly*hlight encEditorVrtyee.    * w @meth/ditorDll  {htedNode
      * Null./ditorDll }  a/, trtcutt*os
   sM cisa* A strhol  @re/dit to in trmaeserving the*s branchs  oConfertyeehisered
ng tthe* trehtedNode
    Tt tN/**oleteNo   dispTN ttto.encEditorVrtye   ement
    /ditorDll    cdrennnnns branceditorDll .inputEleturn.ertyea.lengfth     return nullFinh ly dmultips     /ewly hdited vrtye inn out ful ed, onlyOvenrears 
   .dmultipEditedVrtye.
ng tthe*mary usdmultipEditedVrtyee.    * w @methertyee{s  oConfertyeewilnstdmultipinl    "tanid eltion(* @pe.    * w @meth/ditorDll  {htedNode
      * Null./ditorDll }  a/, trtcutt*os
   sM cisa* A strhol  @re/dit to in trmaeserving the* trehtedNode
    Tt tN/**oleteNo  dispTN ttto. multipEditedVrtye   ement
    ertye,/ditorDll    cdrennnnner: n/**   editorDll .no I          * @p.EibeL   ertyea.length] n   .is LibeLEl  othis.getEl()ertyea.leng th     return n*oD stroy Noh  coOMhiss o  } no/olincl/ditor panel.
ng theOvenrears 
   .d stroyEditorCDOMurn.
ng theSihat wstdmdn'no" icbn se    
list    seiltio   inlincl/ditor,t of t manim/fficieis on avoid } nog    isamary us r 
   .
ng the*mary us  stroyEditorCDOMurnse.    * w @meth/ditorDll  {htedNode
      * Null./ditorDll }  a/, trtcutt*os
   sM cisa* A strhol  @re/dit to in trmaeserving the* trehtedNode
    Tt tN/**oleteNo  dispTN ttto.  stroyEditorCDOMurns   ement
    /ditorDll    cdren.leneditorDll .inputCDOM elem this.getEl()''
     fth})( thretur* A sM cisafact  y arget  trehhe
 vull e**
  /ed.
    cbnimaeserssiTh@arget TVAnim
NohosM cis dJ  htedNode
    TVAnim   ement
  ( */
    s branch  }

pseA/eturn nu nu * Conn thtr treh   fa** itabnimaeserurn nu nu * r h {objecFADE_INurn nu nu * r     s      * tre nu * rsM cis disp eteNo  dispppppFADE_IN: "TVFa@pIo",
  }

pseA/eturn nu nu * Conn thtr treh   fa** @},
animaeserurn nu nu * r h {objecFADE_OUTurn nu nu * r     s      * tre nu * rsM cis disp eteNo  dispppppFADE_OUT: "TVFa@pOut",
  }

pseA/eturn nu nu * 
    },

 ygAnim ton that o  } nogi    rypa * @me nu * rhlight encAnim
Nn nu nu * r @metht    hs  oConf
   t   yif

nimaeserurn nu nu * r @meth/le{getEEleturn}o    aReturn woseReturn ( h bably ert   }
    tdmv)urn nu nu * r @methperteackd{ement
  }eement
   wosinvoke  noltion(
nimaeser i N/on loleteN   the*s branchhtedNoutil.Animaeser}tion(
nimaeser ion that * tre nu * rsM cis disp eteNo  dispppppencAniml
     */
 t   , eL,/perteackodeB            ll: htedNode
   [t   ]del  }

pseAAAAA     * @met/ew htedNode
   [t   ](eL,/perteacko
             }t);
   collapseA     new * @met/              new}          ,
  }

pseA/eturn nu nu * 
    },
 ryeei  } nosn the id(
nimaeser arget  t  vail_clo * @me nu * rhlight isVrtid
Nn nu nu * r @metht    hs  oConf
   t   yif

nimaeserurn nu nu * rs branchbif true
 ryeei  ertid, ()) {t   Thi. Usee nu * rsM cis disp eteNo  dispppppisVrtidl
     */
 t   del  }

pseAAAAAs branc htedNode
   [t   ]d
 dren.len}  }

}th} ( thretur* A 1/2o" coOd fa**-itabnimaeser.siTh@arget TVFa@pIo
ea raets   ct  
eJ r @meth/le{getEEleturn}o    aReturn wosbnimaepur* r @methperteackd{ement
  }eement
   wosinvoke  noltion(
nimaeser i Nfr ishin dJ  htedNode
    TVFa@pIo   ement
  (eL,/perteackodeB    return nullTon(aReturn wosbnimaepur nu * r h {objece e.    * w     getEEleturnoleteNo  disptrue)el   e        return n * ion(ctrteackdwosinvoke  noltion(
nimaeser i Nco    epur nu * r h {objecctrteacke.    * w     (ment
  
ng tNo  disptrue)ctrteackd=cctrteack
 h};

htedNode
    TVFa@pIo. ttto     =
 collareturn n * Per trmstion(
nimaeser
ng tthe*mary usbnimaepur nu *  dispbnimaepl
     */
    c    render: tvanim   veCha.c    render: sl()true)eLrsssNa c    rends.opacijec= 0.1 c    rends.filsere) "alpha(opacije=10)" c    rends. multipl()""  c    render: durc= 0.4; c    render: al()onw htedNoutil.Anim(true)eL, {opacije:d{eedi: 0.1,;eo: 1,;unit:""}}, durd
 dren.len .onCo    ep.subscribe(
     */
     tvanim.onCo    ep( t }td
 dren.len .bnimaep( th    f  collareturn n * C tru up a    nvoke ctrteacke.    * wmary usonCo    epur nu *  disponCo    epl
     */
    c    rendtrue)ctrteack( th    f  collareturn n * h
S     e.    * wmary ush
S     e.    * ws branchs  oConf
   sMy to  * the OM contao  } no/on that * treo  dispt
S     element
     */
    get * @met"TVFa@pIo";}
    
}thretur* A 1/2o" coOd fa** @},
animaeser.siTh@arget TVFa@pOut
ea raets   ct  
eJ r @meth/le{getEEleturn}o    aReturn wosbnimaepur* r @methperteackd{Fment
  }eement
   wosinvoke  noltion(
nimaeser i Nfr ishin dJ  htedNode
    TVFa@pOuta= ement
  (eL,/perteackodeB    return nullTon(aReturn wosbnimaepur nu * r h {objece e.    * w     getEEleturnoleteNo  disptrue)el   e        return n * ion(ctrteackdwosinvoke  noltion(
nimaeser i Nco    epur nu * r h {objecctrteacke.    * w     (ment
  
ng tNo  disptrue)ctrteackd=cctrteack
 h};

htedNode
    TVFa@pOut. ttto     =
 collareturn n * Per trmstion(
nimaeser
ng tthe*mary usbnimaepur nu *  dispbnimaepl
     */
    c    render: tvanim   veCha.    render: durc= 0.4;c    render: al()onw htedNoutil.Anim(true)eL, {opacije:d{eedi: 1,;eo: 0.1,;unit:""}}, durd
 dren.len .onCo    ep.subscribe(
     */
     tvanim.onCo    ep( t }td
 dren.len .bnimaep( th    f  collareturn n * C tru up a    nvoke ctrteacke.    * wmary usonCo    epur nu *  disponCo    epl
     */
    c    render: sl()true)eLrsssNa c    rends. multipl()"Thne" c    rends.opacijec= 1 c    rends.filsere) "alpha(opacije=100)" c    rendtrue)ctrteack( th    f  collareturn n * h
S     e.    * wmary ush
S     e.    * ws branchs  oConf
   sMy to  * the OM contao  } no/on that * treo  dispt
S     element
     */
    get * @met"TVFa@pOut";}
    
}thhtedNoregist r("hhe
vull"ichtedNode
     onsNull, {versserl
"2.9.0", ed byl
"2800"})  