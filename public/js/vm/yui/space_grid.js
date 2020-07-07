YAHOO.util.Event.onDOMReady(function() {
    var Dom = YAHOO.util.Dom,
        Event = YAHOO.util.Event,
        DDM = YAHOO.util.DragDropMgr;

    //All DD objects on the page 
    DDM.useShim = true; 
    
    YAHOO.namespace("viame.grid");
    var Grid = YAHOO.viame.grid;
	Grid.name = 'grid';
	Grid.goingUp = false;
	Grid.lastY = 0;
	Grid.firstDrag = false;
	
	Grid.visual = document.createElement('DIV');
	Grid.visual.id = '_grid_visual';
    document.getElementById('scratch_pad').appendChild(Grid.visual);
    //document.body.appendChild(Grid.visual);
    
    Grid.proxy = document.createElement('DIV');
	Grid.proxy.id = '_grid_proxy';
	document.getElementById('scratch_pad').appendChild(Grid.proxy);
	//document.body.appendChild(Grid.proxy);
	
	// Hack
	Dom.setStyle(Grid.proxy, 'width', Dom.getStyle(Grid.proxy, 'width'));
    Dom.setStyle(Grid.proxy, 'height', Dom.getStyle(Grid.proxy, 'height'));
    
    Grid.cover = document.createElement('DIV');
	Grid.cover.id = '_grid_cover';
    document.getElementById('scratch_pad').appendChild(Grid.cover);
    //document.body.appendChild(Grid.cover);
    
    Grid.activeTarget = null;
    
    var _mouseDown = function(args) {
        if (!Grid.firstDrag) {
            var point = new YAHOO.util.Point(Event.getXY(args));
            var drag_objects = Dom.getElementsByClassName('drag_object');
            for (var i = 0; i < drag_objects.length; i++) {
                var region = YAHOO.util.Dom.getRegion(drag_objects[i]);
                if (region.contains(point)) {
                    // Minimum height'
                    for (var i = 0; i < tar.length; i++) {
                        Dom.setStyle(tar[i], 'min-height', '1em');
                    }
                    resetMinColHeights();
                    
                    Grid.firstDrag = true;
                    break;
                }
            }
        }
    }
    
    var _startDrag = function() {
        //Dom.setStyle(this.getEl(), 'opacity', '0.5');
        
        Dom.setStyle(Grid.cover, 'width', this.getEl().offsetWidth + 'px');
        Dom.setStyle(Grid.cover, 'height', this.getEl().offsetHeight + 'px');
        Dom.setStyle(Grid.cover, 'display', 'block');
        Dom.setXY(Grid.cover, Dom.getXY(this.getEl()));
    };
    
    var _dragEnd = function(args) {
        if (Grid.activeTarget) { Dom.removeClass(Grid.activeTarget, 'active'); }
        
        Dom.setStyle(this.getEl(), 'top', '');
        Dom.setStyle(this.getEl(), 'left', '');
        //Dom.setStyle(this.getEl(), 'opacity', '1');
        
        Dom.setStyle(Grid.visual, 'display', 'none');
        Dom.setStyle(Grid.cover, 'display', 'none');
        
        // Reset Min Heights
        resetMinColHeights();
    };
    
    var _dragOver = function(args) {
        var over = Dom.get(args.info);
        
        Dom.setStyle(Grid.visual, 'width', over.offsetWidth + 'px');
        
        if (!(over.id == this.getEl().id)) {
            if (Dom.hasClass(over, 'grid_target')) {
                if (Grid.activeTarget) { Dom.removeClass(Grid.activeTarget, 'active'); }
                Grid.activeTarget = over;
                Dom.addClass(over, 'active');
                
                if (!Dom.getLastChild(over)) {
                    Dom.setStyle(Grid.visual, 'display', 'block');
                    Dom.setXY(Grid.visual, Dom.getXY(over));
                }
                else {
                    if (Grid.lastY > (Dom.getY(Dom.getLastChild(over)) + Dom.getLastChild(over).offsetHeight)) {
                        Dom.setStyle(Grid.visual, 'display', 'block');
                        Dom.setXY(Grid.visual, [Dom.getX(Dom.getLastChild(over)), Dom.getY(Dom.getLastChild(over)) + Dom.getLastChild(over).offsetHeight]);
                    }
                }
            }
            else {
                Dom.setStyle(Grid.visual, 'display', 'block');
                if (Grid.goingUp) {
                    YAHOO.util.Dom.setXY(Grid.visual, [Dom.getX(over), Dom.getY(over) - Grid.visual.offsetHeight]);
                } else {
                    YAHOO.util.Dom.setXY(Grid.visual, [Dom.getX(over), Dom.getY(over) + over.offsetHeight]);
                }
            }
        }
    };
    
    var _dragOut = function(args) {
        Dom.setStyle(Grid.visual, 'display', 'none');
    };
    
    var _dragDrop = function(args) {
        var drop = Dom.get(args.info);
        var dragEl = this.getEl();        
        
        if (DDM.interactionInfo.drop.length === 1) {
            if (!DDM.interactionInfo.sourceRegion.intersect(DDM.interactionInfo.point)) {
                if ((!Dom.getLastChild(drop)) ||
                    (Grid.lastY > (Dom.getY(Dom.getLastChild(drop)) + Dom.getLastChild(drop).offsetHeight))) {
                    drop.appendChild(dragEl);
                    if (drop.id) {
                        //alert('1 Dropping ' + dragEl.id + ' on ' + drop.id + ' at position ' + YAHOO.util.Dom.getChildren(drop).length);
                        YAHOO.util.Connect.asyncRequest('GET', (typeof(vm_pre) != 'undefined' ? vm_pre : '') + '/system/grid/move/format/json/id/' + dragEl.id + '/section/' + drop.id + '/position/' + YAHOO.util.Dom.getChildren(drop).length + '/', {});
                    }
                }
            }
        }
        else {
            var counter = 0;
            if (Grid.goingUp) {
                drop.parentNode.insertBefore(dragEl, drop);
            } else {
                drop.parentNode.insertBefore(dragEl, drop.nextSibling);
            }
            
            var nodes = YAHOO.util.Dom.getChildren(drop.parentNode);
            for (var i in nodes) {
                if (YAHOO.util.Dom.hasClass(drop.parentNode, 'grid_target') && drop.parentNode.id) {
                    counter++;
                    if (dragEl.id == nodes[i].id) {
                        YAHOO.util.Connect.asyncRequest('GET', (typeof(vm_pre) != 'undefined' ? vm_pre : '') + '/system/grid/move/format/json/id/' + dragEl.id + '/section/' + drop.parentNode.id + '/position/' + counter + '/', {});
                        break;
                    }
                }
            }
        }
        
        // IE Hack
        for (var i = 0; i < mods.length; i++) {
            Dom.addClass(mods[i], 'hack');
            Dom.removeClass(mods[i], 'hack');
        }
    };
    
    var _drag = function(args) {
        var y = Event.getPageY(args.e);

        if (y < Grid.lastY) {
            Grid.goingUp = true;
        } else if (y > Grid.lastY) {
            Grid.goingUp = false;
        }

        Grid.lastY = y;
    };
    
    function resetMinColHeights() {
        var cols = new Array('cx', 'c1', 'c2', 'c3', 'c4');
        var longest = 10;
        for (i = 0; i < cols.length; i++) {
            var temp = Dom.get(cols[i]);
            if (temp) {
                Dom.setStyle(temp, 'min-height', '');
                if (temp.offsetHeight > longest) {
                    longest = temp.offsetHeight;
                }
            }
        }
        for (i = 0; i < cols.length; i++) {
            var temp = Dom.get(cols[i]);
            if (temp) {
                Dom.setStyle(temp, 'min-height', longest + 'px');
                //Dom.setStyle(temp, 'margin-bottom', '1em');
            }
        }
    }
    
    var tar = Dom.getElementsByClassName('grid_target');
    for (var i = 0; i < tar.length; i++) {
        new YAHOO.util.DDTarget(tar[i], Grid.name);
    }
    
    var mods = Dom.getElementsByClassName('widget');
    for (var i = 0; i < mods.length; i++) {
        var dd = new YAHOO.util.DDProxy(mods[i], Grid.name, { dragElId: Grid.proxy, centerFrame: true, resizeFrame: false });
        if (Dom.hasClass(Dom.getFirstChild(mods[i]), 'hd') && Dom.getFirstChild(mods[i]).offsetHeight) {
            dd.setHandleElId(Dom.getFirstChild(mods[i]));
            Dom.addClass(Dom.getFirstChild(mods[i]), 'drag_object');
            
            // IE Hack
            if (YAHOO.env.ua.ie) {
                var temp = document.createElement('DIV');
                mods[i].appendChild(temp);
            	Dom.setStyle(temp, 'position', 'absolute');
            	Dom.setStyle(temp, 'z-index', '0');
            	Dom.setStyle(temp, 'width', Dom.getFirstChild(mods[i]).offsetWidth + 'px');
            	Dom.setStyle(temp, 'height', Dom.getFirstChild(mods[i]).offsetHeight + 'px');
            	Dom.setXY(temp, Dom.getXY(Dom.getFirstChild(mods[i])));
            	dd.setHandleElId(temp);
            	Dom.addClass(temp, 'drag_object');
        	}
        }
        else {
            Dom.addClass(mods[i], 'drag_object');
        }
        dd.on('mouseDownEvent', _mouseDown);
        dd.on('startDragEvent', _startDrag);
        dd.on('endDragEvent', _dragEnd);
        dd.on('dragOverEvent', _dragOver);
        dd.on('dragOutEvent', _dragOut);
        dd.on('dragDropEvent', _dragDrop);
        dd.on('dragEvent', _drag);
        
        dd.addInvalidHandleType('input');
    }
    //resetMinColHeights(); // Race condition with other generated widgets
    
    var handleSuccess = function(o){
    	if(o.responseText !== undefined){
    		message = "Transaction id: " + o.tId + "\n";
    		message += "HTTP status: " + o.status + "\n";
    		message += "Status code message: " + o.statusText + "\n";
    		message += "HTTP headers: " + o.getAllResponseHeaders + "\n";
    		message += "Server response: " + o.responseText + "\n";
    	}
    	//alert(message);
    }
    
    var handleFailure = function(o){
    	if(o.responseText !== undefined){
    		message = "Transaction id: " + o.tId + "\n";
    		message += "HTTP status: " + o.status + "\n";
    		message += "Status code message: " + o.statusText + "\n";
    	}
    	//alert(message);
    }
    
    var callback = { success:handleSuccess, failure:handleFailure };
});