/*
 * ViaMe
 *
 */

YAHOO.util.Event.onDOMReady(function() {
    /* To Print Function */
    YAHOO.namespace("viame.to_print.helper");
    YAHOO.viame.to_print.helper.tpl = YAHOO.util.Selector.query('a.to_print_link');
    if (YAHOO.viame.to_print.helper.tpl.length) {
        YAHOO.viame.to_print.helper.cb = function(e, obj) {
            YAHOO.util.Event.preventDefault(e);
            window.open(obj.href, 'Print','scrollbars=yes,width=800,height=600');
        }
        
        for (var i = 0; i < YAHOO.viame.to_print.helper.tpl.length; i++) {
            YAHOO.util.Event.addListener(YAHOO.viame.to_print.helper.tpl[i], "click", YAHOO.viame.to_print.helper.cb, YAHOO.viame.to_print.helper.tpl[i]);
        }
    }
    /* End To Print Function */
    
    
    /* ShadowBox */
    YAHOO.namespace("viame.shadowbox");
    YAHOO.viame.shadowbox.shadowto = (function(locurl, options) {
        YAHOO.viame.shadowbox.shadowto.popup = null;
        if (document.getElementById('shadowbox_popup_c')) {
            document.getElementById('shadowbox_popup_c').parentNode.removeChild(document.getElementById('shadowbox_popup_c'));
        }
        YAHOO.viame.shadowbox.shadowto.popup = new YAHOO.widget.Panel('shadowbox_popup', options);
        YAHOO.viame.shadowbox.shadowto.popup.setHeader("&nbsp;");
        
        var callback = {
            success : function(o) {
                YAHOO.viame.shadowbox.shadowto.popup.setBody(o.responseText);
                YAHOO.viame.shadowbox.shadowto.popup.render(document.body);
                YAHOO.viame.shadowbox.shadowto.popup.show();
                YAHOO.viame.shadowbox.shadowto.popup.cfg.setProperty("zIndex", 15);
                //YAHOO.util.Dom.setStyle('shadowbox_popup_c', 'position', 'fixed');
            },
            failure : function(o) {
                document.location.href = locurl;
                return true;
            }
        };

        var conn = YAHOO.util.Connect.asyncRequest("GET", locurl, callback);
        
        YAHOO.viame.shadowbox.shadowto.popup.hideEvent.subscribe(function(o) {
            //setTimeout(function() {YAHOO.viame.shadowbox.shadowto.popup.destroy();}, 0);
            YAHOO.viame.shadowbox.shadowto.popup.destroy();
        });

        return false;
    });
    /* End ShadowBox */
    
    
    /* ViaMe Profile Display Function */
    YAHOO.namespace("viame.vpd.helper");
    
    YAHOO.viame.vpd.helper.goInit = function() {
        YAHOO.viame.vpd.helper.tpl = YAHOO.util.Selector.query('a.vpd');
        if (YAHOO.viame.vpd.helper.tpl.length) {
            YAHOO.viame.vpd.helper.onShow = null;
            YAHOO.viame.vpd.helper.onShow = function(p_sType, p_aArgs) {
                if (!this.classed) {
                    YAHOO.util.Dom.addClass(this.getItem(0,0).id, 'space');
                    YAHOO.util.Dom.insertBefore(document.createElement('span'), YAHOO.util.Dom.getFirstChild(this.getItem(0,0).id));
                    YAHOO.util.Dom.addClass(this.getItem(1,0).id, 'feed');
                    YAHOO.util.Dom.insertBefore(document.createElement('span'), YAHOO.util.Dom.getFirstChild(this.getItem(1,0).id));
                    YAHOO.util.Dom.addClass(this.getItem(2,0).id, 'feed');
                    YAHOO.util.Dom.insertBefore(document.createElement('span'), YAHOO.util.Dom.getFirstChild(this.getItem(2,0).id));
                    YAHOO.util.Dom.addClass(this.getItem(0,1).id, 'contact');
                    YAHOO.util.Dom.insertBefore(document.createElement('span'), YAHOO.util.Dom.getFirstChild(this.getItem(0,1).id));
                    YAHOO.util.Dom.addClass(this.getItem(1,1).id, 'follow');
                    YAHOO.util.Dom.insertBefore(document.createElement('span'), YAHOO.util.Dom.getFirstChild(this.getItem(1,1).id));
                    YAHOO.util.Dom.addClass(this.getItem(2,1).id, 'mail');
                    YAHOO.util.Dom.insertBefore(document.createElement('span'), YAHOO.util.Dom.getFirstChild(this.getItem(2,1).id));
                    
                    this.classed = true;
                }
                
                this.getItem(0, 1).cfg.setProperty("disabled", (
                  (YAHOO.util.Dom.hasClass(this.contextEventTarget, 'vcc')) 
                  ||
                  (typeof(vm_via_id) !== "undefined" && YAHOO.util.Dom.getAttribute(this.contextEventTarget, 'via_id') == vm_via_id)
                    ? true : false));
                this.getItem(1, 1).cfg.setProperty("disabled", (
                  (typeof(vm_via_id) !== "undefined" && YAHOO.util.Dom.getAttribute(this.contextEventTarget, 'via_id') == vm_via_id)
                    ? true : false));
                
                if (YAHOO.util.Dom.hasClass(this.contextEventTarget, 'vfp')) {
                    this.getItem(1, 1).cfg.setProperty("text", 'UnFollow');
                    YAHOO.util.Dom.addClass(this.getItem(1, 1).element, 'following');
                }
                else {
                    this.getItem(1, 1).cfg.setProperty("text", 'Follow');
                    YAHOO.util.Dom.removeClass(this.getItem(1, 1).element, 'following');
                }
                    
                this.getItem(0, 0).cfg.setProperty("url", YAHOO.util.Dom.getAttribute(this.contextEventTarget, 'href'));
                this.getItem(1, 0).cfg.setProperty("url", '/via/' + YAHOO.util.Dom.getAttribute(this.contextEventTarget, 'via_id') + '/system/widget/p/format/rss/');
                this.getItem(2, 0).cfg.setProperty("url", '/via/' + YAHOO.util.Dom.getAttribute(this.contextEventTarget, 'via_id') + '/system/widget/profile/p/format/rss/');
                this.getItem(0, 1).cfg.setProperty("url", '/contact/create/p/id/' + YAHOO.util.Dom.getAttribute(this.contextEventTarget, 'via_id') + '/');
                this.getItem(1, 1).cfg.setProperty("url", '/profile/follow/p/' + (YAHOO.util.Dom.hasClass(this.contextEventTarget, 'vfp') ? 'unfollow/1/' : '') + 'id/' + YAHOO.util.Dom.getAttribute(this.contextEventTarget, 'via_id') + '/');
                this.getItem(2, 1).cfg.setProperty("url", '/mail/write/p/cid/' + YAHOO.util.Dom.getAttribute(this.contextEventTarget, 'via_id') + '/');
            };
            
            if (typeof(YAHOO.viame.vpd.helper.vcm) !== "undefined") { YAHOO.viame.vpd.helper.vcm.destroy(); }
            YAHOO.viame.vpd.helper.vcm = null;
            YAHOO.viame.vpd.helper.vcm = new YAHOO.widget.ContextMenu("vpdcontextmenu", 
    			{
    				trigger: YAHOO.viame.vpd.helper.tpl,
    				effect: { effect: YAHOO.widget.ContainerEffect.FADE, duration: 0.20 },
    				zindex: 5,
    				itemdata: [
        				[ 
        					{ text: "View Space", url: "javascript:void(null);" },
        					{ text: "Space Feed", url: "javascript:void(null);" },
        					{ text: "Profile Feed", url: "javascript:void(null);" }
        				],
        				[
        					{ text: "Add To Contacts", url: "javascript:void(null);" }, 
        					{ text: "Follow", url: "javascript:void(null);" },
        					{ text: "Send Message", url: "javascript:void(null);" }
        				]
    				],
    				lazyload: true                                    
    			} 
    		);
    		
    		YAHOO.viame.vpd.helper.vcm.render(document.body);
            YAHOO.viame.vpd.helper.vcm.subscribe("show", YAHOO.viame.vpd.helper.onShow);
            
            if (typeof(YAHOO.viame.vpd.helper.tooltip) !== "undefined") { YAHOO.viame.vpd.helper.tooltip.destroy(); }
            YAHOO.viame.vpd.helper.tooltip = null;
            YAHOO.viame.vpd.helper.tooltip = new YAHOO.widget.Tooltip("vpd_tt", { 
                context: YAHOO.viame.vpd.helper.tpl, 
                effect: { effect: YAHOO.widget.ContainerEffect.FADE, duration: 0.20 }
            });
        }
    }
    YAHOO.viame.vpd.helper.goInit();
    /* End ViaMe Profile Display Function */
    
    
    /* LiveChat */
    YAHOO.namespace("viame.livechat");
    YAHOO.viame.livechat.chat = (function() {
        if (YAHOO.viame.livechat.chat.popup && !YAHOO.viame.livechat.chat.popup.closed) {
            if (YAHOO.viame.livechat.chat.popup.focus()) { YAHOO.viame.livechat.chat.popup.focus(); }
        }
        else {
            YAHOO.viame.livechat.chat.popup = window.open(null, 'livechat','width=435,height=335,toolbar=no,menubar=no,location=no,status=no,directories=no');
            
            YAHOO.viame.livechat.chat.popup.document.write('<html><head><title>Live Chat</title></head><body bgcolor="#213338"><object id="pingbox72ia1w7xejo×W" type="application/x-shockwave-flash" data="http://wgweb.msg.yahoo.com/badge/Pingbox.swf" width="420" height="320"><param name="movie" value="http://wgweb.msg.yahoo.com/badge/Pingbox.swf" /><param name="allowScriptAccess" value="always" /><param name="flashvars" value="wid=lbUL2WWtSmPVsY_Wx_9BLKfG" /></object></body></html>');
            YAHOO.viame.livechat.chat.popup.document.close();
            
            if (YAHOO.viame.livechat.chat.popup.focus()) { YAHOO.viame.livechat.chat.popup.focus(); }
        }

        return false;
    });
    /* End LiveChat */
});


/*
function rssCallback(obj) {
    document.write('<div class="feed">');
    for (var x = 0; x < obj.count ; x++) {
        if (obj.value.items[x].title && obj.value.items[x].link) {
            var buildstring;
            var d = new Date(obj.value.items[x].pubDate);
    		
            buildstring = '<div class="entry ' + ((x % 2) ? 'even' : 'odd') + ' ' + ((x == 0) ? 'firstitem' : ((x == (obj.count - 1)) ? 'lastitem' : '')) + '">';
            buildstring += '<div class="title"><a href="' + obj.value.items[x].link + '" target="_blank" rel="nofollow">' + obj.value.items[x].title + '</a></div>';
            buildstring += '<div class="datetime">' + d.toLocaleDateString() + ' <span class="time">' + d.toLocaleTimeString() + '</span></div>';
            if (obj.value.items[x].description) {
                buildstring += '<div class="summary">' + obj.value.items[x].description.replace(/(<([^>]+)>)/ig,"") + '</div>';
            }
    		buildstring += '</div>';
    		
            document.write(buildstring);
        }
    }
    document.write('</div>');
}
*/
function rssCallBack(obj) {
    document.write('<div class="feed">');
    if (obj.entries) {
        for (var x = 0; x < obj.entries.length ; x++) {
            if (obj.entries[x].title && obj.entries[x].link) {
                var buildstring;
                var d = new Date(obj.entries[x].published);
        		
                buildstring = '<div class="entry ' + ((x % 2) ? 'even' : 'odd') + ' ' + ((x == 0) ? 'firstitem' : ((x == (obj.count - 1)) ? 'lastitem' : '')) + '">';
                buildstring += '<div class="title"><a href="' + obj.entries[x].link + '" target="_blank" rel="nofollow">' + obj.entries[x].title + '</a></div>';
                buildstring += '<div class="datetime">' + d.toLocaleDateString() + ' <span class="time">' + d.toLocaleTimeString() + '</span></div>';
                if (obj.entries[x].description) {
                    buildstring += '<div class="summary">' + obj.entries[x].description.replace(/(<([^>]+)>)/ig,"") + '</div>';
                }
        		buildstring += '</div>';
        		
                document.write(buildstring);
            }
        }
    }
    document.write('</div>');
}


/* Adding YUI3 stickyElement Plugin */
YUI.add('stickyElement', function (Y) {
	/* START PLUGIN */
	stuckAdjust = 0;
	
	var StickyElement = function(config) {
		StickyElement.superclass.constructor.apply(this, arguments);
	};

	StickyElement.NS = 'sticky';
	StickyElement.NAME = 'stickyElement';
	
	StickyElement.ATTRS = {
		/*
         * Default duration. Used by the default animation implementations
         */
        container : {
            valueFn : function() {
				return this.get("host").ancestor();
			}
        },
		curb : {
			value : undefined
		},
		offset : {
			value : 0
		},
		stuckHeightAdjust : {
			value : 0
		},
		holdspace : {
			value : false
		},
		delay : {
		    value : 100
		},
		zindex : {
		    value : 4
		},
		active : {
			value : true
		}
	};
	
	Y.extend(StickyElement, Y.Plugin.Base, {
		_scrollEvent : undefined,
		_resizeEvent : undefined,
		
		_initialPosition : undefined,
		_initialTop : undefined,
		_initialLeft : undefined,
		_initialRight : undefined,
		_initialBottom : undefined,
		_initialWidth : undefined,
		_initialHeight : undefined,
		_initialWidthCSS : undefined,
		_initialHeightCSS : undefined,
		_initialY : undefined,
		
		_stuck : false,
		_repos : false,
		_running : false,
		
		_drift : 0,
		_clonedNode : undefined,
		
		_node : undefined,
		_container : undefined,
		_curb : undefined,
		
        initializer : function(config) {			
			this._node = this.get("host");
			this._node._fullHeight = (
				parseFloat(this._node.getComputedStyle("height")) +
				parseFloat(this._node.getComputedStyle("borderTopWidth")) +
				parseFloat(this._node.getComputedStyle("paddingTop")) +
				parseFloat(this._node.getComputedStyle("paddingBottom")) +
				parseFloat(this._node.getComputedStyle("borderBottomWidth"))
			);
			this._node.setStyle("zIndex", this.get("zindex"));
			
			if (this.get("container").nodeType) { this._container = this.get("container"); }
			else { this._container = Y.one(this.get("container")) || this._node.ancestor(); }
			
			if (this.get("curb")) {
				if (this.get("curb").nodeType) { this._curb = this.get("curb"); }
				else if (Y.one(this.get("curb"))) { this._curb = Y.one(this.get("curb")); }
			}
			
			// Save Relevant Initial Values
			this._setInitialValues();
            
			this._scrollEvent = Y.one(window).on('scroll', function(e) {
			    if (!this._running) {
    				this._checkPosition();
    				this._running = true;
    				var _this = this;
    			    setTimeout(function() { _this._running = false; _this._checkPosition(); }, this.get("delay"));
    			}
			}, this);
			this._resizeEvent = Y.one(window).on('windowresize', function(e) {
				//if (!this._running) {
    				this._checkPosition();
    				//this._running = true;
    				var _this = this;
					
					this.reset();
					this._drift = 0;
					stuckAdjust = 0;
					this._node.setStyle('width', this._initialWidthCSS);
					this._node.setStyle('height', this._initialHeightCSS);
					this._setInitialValues();
					this.reset();
					
    			    //setTimeout(function() { _this._running = false; _this._checkPosition(); }, this.get("delay"));
    			    setTimeout(function() { _this._checkPosition(); }, this.get("delay"));
    			//}
			}, this);
			
			// Check Once On Startup
			this.reset();
			this._checkPosition();
        },
		
		// Set the Initial Values
		_setInitialValues : function() {
			this._initialPosition = this._node.getComputedStyle('position');
			this._initialTop = this._node.getComputedStyle('top');
			this._initialLeft = this._node.getComputedStyle('left');
			this._initialRight = this._node.getComputedStyle('right');
			this._initialBottom = this._node.getComputedStyle('bottom');
			this._initialWidth = this._node.getComputedStyle('width');
			this._initialHeight = this._node.getComputedStyle('height');
			this._initialWidthCSS = (this._node._node.style.width ? this._node._node.style.width : 'auto');
			this._initialHeightCSS = (this._node._node.style.height ? this._node._node.style.height : 'auto');
			this._initialY = this._node.getY();
		},
		
		// Compute Major Inflection Points
		_getInflection1 : function() {
			return parseFloat(
				this.get("offset") +
				(
					(this._curb ?	this._curb.getY() - Y.one(document.body).get('docScrollY') +
									parseFloat(this._curb.getComputedStyle("height")) +
									parseFloat(this._curb.getComputedStyle("borderTopWidth")) +
									parseFloat(this._curb.getComputedStyle("paddingTop")) +
									parseFloat(this._curb.getComputedStyle("paddingBottom")) +
									parseFloat(this._curb.getComputedStyle("borderBottomWidth"))
								:
									0)
				) -
				(parseFloat(this._node.getComputedStyle("marginTop")))
			);
		},
		_getInflection2 : function() {
			return parseFloat(
				this._container.getY() +
				parseFloat(this._container.getComputedStyle("height")) +
				parseFloat(this._container.getComputedStyle("borderTopWidth")) +
				parseFloat(this._container.getComputedStyle("paddingTop")) +
				parseFloat(this._container.getComputedStyle("paddingBottom"))
			);
		},
		_getAdjust1 : function() {
			return parseFloat(
				Math.max(
					(parseFloat(this._node.getComputedStyle("marginTop"))),
					(parseFloat(
						(this._curb	?	this._curb.getComputedStyle("marginBottom")
									:
										0)
					))
				)
			);
		},
		_getAdjust2 : function() {
			return parseFloat(
				Math.max(
					(parseFloat(this._container.getComputedStyle("paddingBottom"))),
					(parseFloat(this._node.getComputedStyle("marginBottom")))
				)
			);
		},
		
		getOffset : function() {
			return this.get("offset");
		},
		setOffset : function(newOffset) {
			this.set("offset", newOffset);
			return newOffset;
		},
		
        destructor : function() {
			this._scrollEvent.detach();
			this._resizeEvent.detach();
			this.reset();
        },
		
		destroy: function() {
			this.destructor();
		},
		
		start : function() {
			this.set("active", true);
			this._checkPosition();
		},
		
		stop : function() {
			this.set("active", false);
		},
		
		toggle : function() {
			this.set("active", !this.get("active"));
			if (this.get("active")) { this._checkPosition(); }
		},
		
		reset : function() {
			if (this._clonedNode) {
				this._clonedNode.remove(true);
			}
			
			this._node.removeClass('stickElement-repo');
			this._node.removeClass('stickElement-stuck');
					
			this._stuck = false;
			this._repos = false;
			
			// Reset to initial position
			this._node.setStyle('position', this._initialPosition);
			this._node.setStyle('top', this._initialTop);
			this._node.setStyle('left', this._initialLeft);
			this._node.setStyle('right', this._initialRight);
			this._node.setStyle('bottom', this._initialBottom);
			this._node.setStyle('width', this._initialWidth);
			this._node.setStyle('height', this._initialHeight);
			
			// Is this needed?  It breaks the placement on page resize...
            //this._node.setY(this._initialY - stuckAdjust - this._drift);
		},
		
		_checkPosition : function() {
			if (this.get("active")) {
				
				var topTrigger = Y.one(document.body).get('docScrollY') + this._getInflection1() + this._getAdjust1();
				var bottomTrigger = this._getInflection2() - this._getAdjust2();
				
				if (this._stuck && this._repos && (topTrigger <= this._node.getY())) {
				    // Undo Container Reposition and Stick It
					this._stuck = true;
					this._repos = false;
					
					this._node.setStyle('position', 'fixed');
					this._node.setStyle('top', this._getInflection1() + this._getAdjust1());
					
					this._node.removeClass('stickElement-repo');
					this._node.addClass('stickElement-stuck');
				}
				else if (this._stuck && !this._repos && (this._node.getY() + this._node._fullHeight > bottomTrigger)) {
				    // Reposition to Container
					this._repos = true;
					
					this._node.setStyle('position', 'relative');
					this._node.setY(bottomTrigger - this._node._fullHeight);
					
					this._node.addClass('stickElement-repo');
				}
				else if (!this._stuck && !this._repos && this._initialY < topTrigger + stuckAdjust - this._drift) {
				    // Stick It
					this._stuck = true;
					this._repos = false;
					if (this.get("holdspace")) {
						this._clonedNode = this._node.cloneNode();
						this._clonedNode._node.id = this._clonedNode._yuid;
						this._clonedNode.addClass("stickyClonedNode");
						
						this._node.insert(this._clonedNode, 'before');
						this._node.setStyle('position', 'fixed');
						this._node.setStyle('top', this._getInflection1() + this._getAdjust1());
					}
					else {
						var tempHeight = parseFloat(Y.one(document.body).getComputedStyle("height"));
						this._node.setStyle('position', 'fixed');
						this._node.setStyle('top', this._getInflection1() + this._getAdjust1());
						this._drift = (tempHeight - parseFloat(Y.one(document.body).getComputedStyle("height")));
						stuckAdjust += this._drift;
					}
					
					// stuck Height Adjustment
					if (this._clonedNode) {
    					this._clonedNode.setStyle('height', parseFloat(this._clonedNode.getComputedStyle('height')) + this.get("stuckHeightAdjust") + 'px');
    				}
					this._node.setStyle('height', parseFloat(this._node.getComputedStyle('height')) + this.get("stuckHeightAdjust") + 'px');
					
					this._node.addClass('stickElement-stuck');
				}
				else if (this._stuck && !this._repos && this._initialY >= topTrigger + stuckAdjust - this._drift) {
				    // Reset Back to Initial Position
					this._stuck = false;
					this._repos = false;
			
					if (this.get("holdspace")) {
						this._clonedNode.remove(true);
					}
					else {
						stuckAdjust -= this._drift;
						this._drift = 0;
					}
					
					this.reset();
				}

			}
		}
	});
	
	Y.namespace('Plugin');
	Y.Plugin.StickyElement = StickyElement;
	
	/* END PLUGIN */
}, '0.0.1', { requires: ['node', 'event'] });