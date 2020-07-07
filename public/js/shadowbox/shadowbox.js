/**
 * The Shadowbox class.
 *
 * This file is part of Shadowbox.
 *
 * Shadowbox is an online media viewer application that supports all of the
 * web's most popular media publishing formats. Shadowbox is written entirely
 * in JavaScript and CSS and is highly customizable. Using Shadowbox, website
 * authors can showcase a wide assortment of media in all major browsers without
 * navigating users away from the linking page.
 *
 * You should have received a license with this distribution explaining the terms
 * under which Shadowbox may be used. If you did not, you may obtain a copy of the
 * license at http://shadowbox-js.com/LICENSE
 *
 * @author      Michael J. I. Jackson <michael@mjijackson.com>
 * @copyright   2007-2009 Michael J. I. Jackson
 */

/**
 * The Shadowbox class. Used to display different media on a web page using a
 * Lightbox-like effect.
 *
 * Known issues:
 *
 * - Location.toString exception in FF3 when loading Flash content into an
 *   iframe (such as a YouTube video). Known Flash bug, will not be fixed.
 *   http://bugs.adobe.com/jira/browse/FP-561
 * - In some situations audio keeps on playing after Shadowbox is closed
 *   when using Windows Media Player or QuickTime. For this reason, it is
 *   recommended to convert to Flash video instead.
 *
 * Useful resources:
 *
 * - http://www.alistapart.com/articles/byebyeembed
 * - http://www.w3.org/TR/html401/struct/objects.html
 * - http://www.dyn-web.com/dhtml/iframes/
 * - http://www.apple.com/quicktime/player/specs.html
 * - http://www.apple.com/quicktime/tutorials/embed2.html
 * - http://www.howtocreate.co.uk/wrongWithIE/?chapter=navigator.plugins
 * - http://msdn.microsoft.com/en-us/library/ms532969.aspx
 * - http://support.microsoft.com/kb/316992
 * - http://www.alistapart.com/articles/flashembedcagematch
 *
 * Todo:
 *
 * - Remove user-agent sniffing (and consequently Shadowbox.client) in
 *   favor of feature support model of client detection
 */
(function(){

    var ua = navigator.userAgent.toLowerCase(),

    // the Shadowbox object
    S = {

        /**
         * The current version of Shadowbox.
         *
         * @var     String
         * @public
         */
        version: "3.0rc1",

        /**
         * The name of the adapter currently being used.
         *
         * @var     String
         * @public
         */
        adapter: null,

        /**
         * A cache of options for links that have been set up for use with
         * Shadowbox.
         *
         * @var     Array
         * @public
         */
        cache: [],

        /**
         * Some simple browser detection variables.
         *
         * @var     Object
         * @public
         */
        client: {
            isIE:       ua.indexOf('msie') > -1,
            isIE6:      ua.indexOf('msie 6') > -1,
            isIE7:      ua.indexOf('msie 7') > -1,
            isGecko:    ua.indexOf('gecko') > -1 && ua.indexOf('safari') == -1,
            isWebkit:   ua.indexOf('applewebkit/') > -1,
            isWindows:  ua.indexOf('windows') > -1 || ua.indexOf('win32') > -1,
            isMac:      ua.indexOf('macintosh') > -1 || ua.indexOf('mac os x') > -1,
            isLinux:    ua.indexOf('linux') > -1
        },

        /**
         * The current content object.
         *
         * @var     Object
         * @public
         */
        content: null,

        /**
         * The array index of the current gallery that is currently being viewed.
         *
         * @var     Number
         * @public
         */
        current: -1,

        /**
         * Holds the current dimensions of Shadowbox as calculated by its skin.
         * Contains the following properties:
         *
         * - height: The total height of #sb-wrapper (including title & info bars)
         * - width: The total width of #sb-wrapper
         * - inner_h: The height of #sb-body
         * - inner_w: The width of #sb-body
         * - top: The top to use for #sb-wrapper
         * - left: The left to use for #sb-wrapper
         * - oversized: True if the content is oversized (too large for the viewport)
         * - resize_h: The height to use for resizable content
         * - resize_w: The width to use for resizable content
         *
         * @var     Object
         * @public
         */
        dimensions: null,

        /**
         * An array containing the gallery objects currently being viewed. In the
         * case of non-gallery items, this will only hold one object.
         *
         * @var     Array
         * @public
         */
        gallery: [],

        /**
         * The name of the expando property that will be added to HTML elements
         * when they're added to the cache.
         *
         * @var     String
         * @public
         */
        expando: 'shadowboxCacheKey',

        /**
         * A map of library object names to their corresponding Shadowbox adapter
         * names.
         *
         * @var     Object
         * @public
         */
        libraries: {
            Prototype:  'prototype',
            jQuery:     'jquery',
            MooTools:   'mootools',
            YAHOO:      'yui',
            dojo:       'dojo',
            Ext:        'ext'
        },

        /**
         * Contains the default options for Shadowbox.
         *
         * @var     Object
         * @public
         */
        options: {
            adapter: null,              // the library adapter to use
            animate: true,              // enable all animations, except for fades
            animateFade: true,          // enable fade animations
            autoplayMovies: true,       // automatically play movies
            continuous: false,          // enables continuous galleries. When enabled,
                                        // user will be able to skip to the first
                                        // gallery item from the last using next and
                                        // vice versa

            /**
             * Easing function used for animations. Based on a cubic polynomial.
             *
             * @param   Number      x       The state of the animation (% complete)
             * @return  Number              The adjusted easing value
             */
            ease: function(x){
                return 1 + Math.pow(x - 1, 3);
            },

            enableKeys: true,           // enable keyboard navigation

            /**
             * An object containing names of plugins and links to their respective
             * download pages.
             */
            errors: {
                fla: {
                    name:   'Flash',
                    url:    'http://www.adobe.com/products/flashplayer/'
                },
                qt: {
                    name:   'QuickTime',
                    url:    'http://www.apple.com/quicktime/download/'
                },
                wmp: {
                    name:   'Windows Media Player',
                    url:    'http://www.microsoft.com/windows/windowsmedia/'
                },
                f4m: {
                    name:   'Flip4Mac',
                    url:    'http://www.flip4mac.com/wmv_download.htm'
                }
            },

            /**
             * A map of players to the file extensions they support. Each member of
             * this object is the name of a player (with one exception), whose value
             * is an array of file extensions that player will "play". The one
             * exception to this rule is the "qtwmp" member, which contains extensions
             * that may be played using either QuickTime or Windows Media Player.
             *
             * - img: Image file extensions
             * - swf: Flash SWF file extensions
             * - flv: Flash video file extensions (will be played by JW FLV player)
             * - qt: Movie file extensions supported by QuickTime
             * - wmp: Movie file extensions supported by Windows Media Player
             * - qtwmp: Movie file extensions supported by both QuickTime and Windows Media Player
             *
             * IMPORTANT: If this object is to be modified, it must be copied in its
             * entirety and tweaked because it is not merged recursively with the
             * default. Also, any modifications must be passed into Shadowbox.init
             * for speed reasons.
             */
            ext: {
                img:        ['png', 'jpg', 'jpeg', 'gif', 'bmp'],
                swf:        ['swf'],
                flv:        ['flv', 'm4v'],
                qt:         ['dv', 'mov', 'moov', 'movie', 'mp4'],
                wmp:        ['asf', 'wm', 'wmv'],
                qtwmp:      ['avi', 'mpg', 'mpeg']
            },

            /**
             * Parameters to pass to flash <object>'s.
             */
            flashParams: {
                bgcolor:            '#000000',
                allowfullscreen:    true
            },

            flashVars: {},              // flash vars
            flashVersion: '9.0.115',    // minimum required flash version suggested
                                        // by JW FLV player

            /**
             * How to handle content that is too large to display in its entirety
             * (and is resizable). A value of 'resize' will resize the content while
             * preserving aspect ratio and display it at the smaller resolution. If
             * the content is an image, a value of 'drag' will display the image at
             * its original resolution but it will be draggable within Shadowbox. A
             * value of 'none' will display the content at its original resolution
             * but it may be cropped.
             */
            handleOversize: 'resize',

            /**
             * The mode to use when handling unsupported media. May be either
             * 'remove' or 'link'. If it is 'remove', the unsupported gallery item
             * will merely be removed from the gallery. If it is the only item in
             * the gallery, the link will simply be followed. If it is 'link', a
             * link will be provided to the appropriate plugin page in place of the
             * gallery element.
             */
            handleUnsupported: 'link',

            language: 'en',             // the language to use
            onChange: null,             // hook function to be fired when changing
                                        // from one item to the next. Is passed the
                                        // item that is about to be displayed
            onClose: null,              // hook function to be fired when closing.
                                        // is passed the most recent item
            onFinish: null,             // hook function to be fired when finished
                                        // loading content. Is passed current
                                        // gallery item
            onOpen: null,               // hook function to be fired when opening.
                                        // is passed the current gallery item
            players: ['img'],           // the players to load
            showMovieControls: true,    // enable movie controls on movie players
            skipSetup: false,           // skip calling Shadowbox.setup() during
                                        // shadowbox.init()
            slideshowDelay: 0,          // delay to use for slideshows (seconds). If
                                        // set to any duration other than 0, is interval
                                        // at which slideshow will advance
            useSizzle: true,            // use sizzle.js to support css selectors
            viewportPadding: 20         // amount of padding to maintain around the
                                        // edge of the viewport at all times (pixels)
        },

        /**
         * Contains the base path of the Shadowbox script.
         *
         * Note: This property will automatically be populated in Shadowbox.load.
         *
         * @var     String
         * @public
         */
        path: '',

        /**
         * Contains plugin support information. Each property of this object is a
         * boolean indicating whether that plugin is supported.
         *
         * - fla: Flash player
         * - qt: QuickTime player
         * - wmp: Windows Media player
         * - f4m: Flip4Mac plugin
         *
         * @var     Object
         * @public
         */
        plugins: null,

        /**
         * Tells whether or not the DOM is ready to be manipulated.
         *
         * @var     Boolean
         * @public
         */
        ready: false,

        /**
         * An object containing some regular expressions we'll need later. Compiled
         * up front for speed.
         *
         * @var     Object
         * @public
         */
        regex: {
            domain:         /:\/\/(.*?)[:\/]/,              // domain prefix
            inline:         /#(.+)$/,                       // inline element id
            rel:            /^(light|shadow)box/i,          // rel attribute format
            gallery:        /^(light|shadow)box\[(.*?)\]/i, // rel attribute format for gallery link
            unsupported:    /^unsupported-(\w+)/,           // unsupported media type
            param:          /\s*([a-z_]*?)\s*=\s*(.+)\s*/   // rel string parameter
        },

        /**
         * Applies the given set of options to those currently in use.
         *
         * Note: Options will be reset on Shadowbox.open() so this function is
         * only useful after it has already been called (while Shadowbox is
         * open).
         *
         * @param   Object      opts        The options to apply
         * @return  void
         * @public
         */
        applyOptions: function(opts){
            if(opts){
                // store defaults, use apply to break reference
                default_options = apply({}, S.options);
                apply(S.options, opts);
            }
        },

        /**
         * Reverts Shadowbox' options to the last default set in use before
         * Shadowbox.applyOptions() was called.
         *
         * @return  void
         * @public
         */
        revertOptions: function(){
            apply(S.options, default_options);
        },

        /**
         * Jumps to the piece in the current gallery with the given index.
         *
         * @param   Number      index   The gallery index to view
         * @return  void
         * @public
         */
        change: function(index){
            if(!S.gallery) return; // no current gallery
            if(!S.gallery[index]){ // index does not exist
                if(!S.options.continuous)
                    return;
                else
                    index = index < 0 ? S.gallery.length - 1 : 0; // loop
            }

            // update current
            S.current = index;

            if(typeof slide_timer == 'number'){
                clearTimeout(slide_timer);
                slide_timer = null;
                slide_delay = slide_start = 0; // reset slideshow variables
            }

            if(S.options.onChange)
                S.options.onChange();

            loadContent();
        },

        /**
         * Deactivates Shadowbox.
         *
         * @return  void
         * @public
         */
        close: function(){
            if(!active) return; // already closed
            active = false;

            listenKeys(false);

            // remove the content
            if(S.content){
                S.content.remove();
                S.content = null;
            }

            // clear slideshow variables
            if(typeof slide_timer == 'number')
                clearTimeout(slide_timer);
            slide_timer = null;
            slide_delay = 0;

            if(S.options.onClose)
                S.options.onClose();

            S.skin.onClose();

            S.revertOptions();
        },

        /**
         * Gets the id that should be used for content elements.
         *
         * @return  String          The content element id
         * @public
         */
        contentId: function(){
            return content_id;
        },

        /**
         * Reports an error. Mainly needed because there are quite a few web developers out
         * there who think that all exceptions are errors in the code instead of helpful
         * messages.
         *
         * @param   String  msg     The error message
         * @return  void
         * @public
         */
        error: function(msg){
            if(!S.debug) return;

            if(typeof window['console'] != 'undefined' && typeof console.log == 'function')
                console.log(msg);
            else
                alert(msg);
        },

        /**
         * Gets the current gallery object.
         *
         * @return  Object          The current gallery item
         * @public
         */
        getCurrent: function(){
            return S.current > -1 ? S.gallery[S.current] : null;
        },

        /**
         * Determines if there is a next piece to display in the current
         * gallery.
         *
         * @return  Boolean         True if there is another piece
         * @public
         */
        hasNext: function(){
            return S.gallery.length > 1 &&
                (S.current != S.gallery.length - 1 || S.options.continuous);
        },

        /**
         * Initializes the Shadowbox environment. Should be called by the user in
         * the <head> of the HTML document.
         *
         * Note: This function attempts to load all Shadowbox dependencies
         * dynamically. However, if these dependencies are already included on the
         * page they won't be loaded again.
         *
         * @param   Object      opts    (optional) The default options to use
         * @return  void
         * @public
         */
        init: function(opts){
            if(initialized) return; // don't initialize twice
            initialized = true;

            opts = opts || {};
            init_options = opts;

            // apply options
            if(opts)
                apply(S.options, opts);

            // compile regular expressions here for speed
            for(var e in S.options.ext)
                S.regex[e] = new RegExp('\.(' + S.options.ext[e].join('|') + ')\s*$', 'i');

            if(!S.path){
                // determine script path automatically
                var pathre = /(.+\/)shadowbox\.js/i, path;
                each(document.getElementsByTagName('script'), function(s){
                    path = pathre.exec(s.src);
                    if(path){
                        S.path = path[1];
                        return false;
                    }
                });
            }

            // determine adapter
            if(S.options.adapter)
                S.adapter = S.options.adapter.toLowerCase();
            else{
                // automatically detect adapter based on loaded libraries
                for(var lib in S.libraries){
                    if(typeof window[lib] != 'undefined'){
                        S.adapter = S.libraries[lib];
                        break;
                    }
                }
                if(!S.adapter)
                    S.adapter = 'base';
            }

            // load dependencies
            if(S.options.useSizzle && !window['Sizzle'])
                // jQuery 1.3.2 doesn't expose Sizzle to the global namespace... why?
                if(window['jQuery'])
                    window['Sizzle'] = jQuery.find;
                else
                    U.include(S.path + 'libraries/sizzle/sizzle.js');
            if(!S.lang)
                U.include(S.path + 'languages/shadowbox-' + S.options.language + '.js');
            each(S.options.players, function(p){
                if((p == 'swf' || p == 'flv') && !window['swfobject'])
                    U.include(S.path + 'libraries/swfobject/swfobject.js');
                if(!S[p])
                    U.include(S.path + 'players/shadowbox-' + p + '.js');
            });
            if(!S.lib)
                U.include(S.path + 'adapters/shadowbox-' + S.adapter + '.js');

            waitDom(waitLibs);
        },

        /**
         * Tells whether or not Shadowbox is currently activated.
         *
         * @return  Boolean         True if activated, false otherwise
         * @public
         */
        isActive: function(){
            return active;
        },

        /**
         * Tells whether or not Shadowbox is currently in the middle of a
         * slideshow in a paused state.
         *
         * @return  Boolean         True if paused, false otherwise
         * @public
         */
        isPaused: function(){
            return slide_timer == 'paused';
        },

        /**
         * Loads Shadowbox into the DOM. Is called automatically by each adapter
         * as soon as the DOM is ready.
         *
         * @return  void
         * @public
         */
        load: function(){
            if(S.ready) return;
            S.ready = true;

            // apply skin options, re-apply user init options in case they overwrite
            if(S.skin.options){
                apply(S.options, S.skin.options);
                apply(S.options, init_options);
            }

            S.skin.init();

            if(!S.options.skipSetup)
                S.setup();
        },

        /**
         * Jumps to the next piece in the gallery.
         *
         * @return  void
         * @public
         */
        next: function(){
            S.change(S.current + 1);
        },

        /**
         * Opens the given object in Shadowbox. This object may be either an
         * anchor/area element, or an object similar to the one created by
         * Shadowbox.buildCacheObj().
         *
         * @param   mixed       obj         The object or link element that defines
         *                                  what to display
         * @return  void
         * @public
         */
        open: function(obj){
            if(U.isLink(obj)){
                if(S.inCache(obj))
                    obj = S.cache[obj[S.expando]];
                else
                    obj = S.buildCacheObj(obj); // non-cached link, build an object on the fly
            }

            // set up the gallery
            if(obj.constructor == Array){
                S.gallery = obj;
                S.current = 0;
            }else{
                if(!obj.gallery){
                    // single item, no gallery
                    S.gallery = [obj];
                    S.current = 0;
                }else{
                    // gallery item, build gallery from cached gallery elements
                    S.current = null;
                    S.gallery = [];
                    each(S.cache, function(c){
                        if(c.gallery && c.gallery == obj.gallery){
                            if(S.current == null && c.content == obj.content && c.title == obj.title)
                                S.current = S.gallery.length;
                            S.gallery.push(c);
                        }
                    });

                    // if not found in cache, prepend to front of gallery
                    if(S.current == null){
                        S.gallery.unshift(obj);
                        S.current = 0;
                    }
                }
            }

            obj = S.getCurrent();
            if(obj.options){
                S.revertOptions();
                S.applyOptions(obj.options);
            }

            // filter gallery for unsupported elements
            var item, remove, m, format, replace, oe = S.options.errors, msg, el;
            for(var i = 0; i < S.gallery.length; ++i){
                // use apply to break the reference to the original object here
                // because we'll be modifying the properties of the gallery objects
                // directly and we don't want to taint them in case they are used
                // again in a future call
                item = S.gallery[i] = apply({}, S.gallery[i]);

                remove = false; // remove the element?

                if(m = S.regex.unsupported.exec(item.player)){
                    // handle unsupported elements
                    if(S.options.handleUnsupported == 'link'){
                        item.player = 'html';
                        // generate a link to the appropriate plugin download page(s)
                        switch(m[1]){
                            case 'qtwmp':
                                format = 'either';
                                replace = [oe.qt.url, oe.qt.name, oe.wmp.url, oe.wmp.name];
                            break;
                            case 'qtf4m':
                                format = 'shared';
                                replace = [oe.qt.url, oe.qt.name, oe.f4m.url, oe.f4m.name];
                            break;
                            default:
                                format = 'single';
                                if(m[1] == 'swf' || m[1] == 'flv') m[1] = 'fla';
                                replace = [oe[m[1]].url, oe[m[1]].name];
                        }
                        msg = S.lang.errors[format].replace(/\{(\d+)\}/g, function(m, n){
                            return replace[n];
                        });
                        item.content = '<div class="sb-message">' + msg + '</div>';
                    }else
                        remove = true;
                }else if(item.player == 'inline'){
                    // inline element, retrieve innerHTML
                    m = S.regex.inline.exec(item.content);
                    if(m){
                        var el = U.get(m[1]);
                        if(el)
                            item.content = el.innerHTML;
                        else
                            S.error('Cannot find element with id ' + m[1]);
                    }else
                        S.error('Cannot find element id for inline content');
                }else if(item.player == 'swf' || item.player == 'flv'){
                    var version = (item.options && item.options.flashVersion) || S.options.flashVersion;
                    if(!swfobject.hasFlashPlayerVersion(version)){
                        // express install will be triggered because the client
                        // does not have the minimum required version of flash
                        // installed, set height and width to those of express
                        // install swf
                        item.width = 310;
                        // minimum height is 127, but +20 pixels on top and bottom
                        // looks better
                        item.height = 177;
                    }
                }
                if(remove){
                    S.gallery.splice(i, 1); // remove from gallery
                    if(i < S.current)
                        --S.current; // maintain integrity of S.current
                    else if(i == S.current)
                        S.current = i > 0 ? i - 1 : i; // look for supported neighbor
                    --i; // decrement index for next loop
                }
            }

            // anything left to display?
            if(S.gallery.length){
                if(!active){
                    if(typeof S.options.onOpen == 'function' && S.options.onOpen(obj) === false)
                        return;

                    S.skin.onOpen(obj, loadContent);
                }else
                    loadContent();

                active = true;
            }
        },

        /**
         * Pauses the current slideshow.
         *
         * @return  void
         * @public
         */
        pause: function(){
            if(typeof slide_timer != 'number') return;

            var time = new Date().getTime();
            slide_delay = Math.max(0, slide_delay - (time - slide_start));

            // if there's any time left on current slide, pause the timer
            if(slide_delay){
                clearTimeout(slide_timer);
                slide_timer = 'paused';

                if(S.skin.onPause)
                    S.skin.onPause();
            }
        },

        /**
         * Sets the timer for the next image in the slideshow to be displayed.
         *
         * @return  void
         * @public
         */
        play: function(){
            if(!S.hasNext()) return;
            if(!slide_delay) slide_delay = S.options.slideshowDelay * 1000;
            if(slide_delay){
                slide_start = new Date().getTime();
                slide_timer = setTimeout(function(){
                    slide_delay = slide_start = 0; // reset slideshow
                    S.next();
                }, slide_delay);

                if(S.skin.onPlay)
                    S.skin.onPlay();
            }
        },

        /**
         * Jumps to the previous piece in the gallery.
         *
         * @return  void
         * @public
         */
        previous: function(){
            S.change(S.current - 1);
        },

        /**
         * Calculates the dimensions for Shadowbox according to the given
         * parameters. Will determine if content is oversized (too large for the
         * viewport) and will automatically constrain resizable content
         * according to user preference.
         *
         * @param   Number      height      The content height
         * @param   Number      width       The content width
         * @param   Number      max_h       The maximum height available (should
         *                                  be the height of the viewport)
         * @param   Number      max_w       The maximum width available (should
         *                                  be the width of the viewport)
         * @param   Number      tb          The extra top/bottom pixels that are
         *                                  required for borders/toolbars
         * @param   Number      lr          The extra left/right pixels that are
         *                                  required for borders/toolbars
         * @param   Boolean     resizable   True if the content is able to be
         *                                  resized. Defaults to false
         * @return  void
         * @public
         */
        setDimensions: function(height, width, max_h, max_w, tb, lr, resizable){
            var h = height = parseInt(height),
                w = width = parseInt(width),
                pad = parseInt(S.options.viewportPadding) || 0;

            // calculate the max height/width
            var extra_h = 2 * pad + tb;
            if(h + extra_h >= max_h) h = max_h - extra_h;
            var extra_w = 2 * pad + lr;
            if(w + extra_w >= max_w) w = max_w - extra_w;

            // handle oversized content
            var resize_h = height,
                resize_w = width,
                change_h = (height - h) / height,
                change_w = (width - w) / width,
                oversized = (change_h > 0 || change_w > 0);
            if(resizable && oversized && S.options.handleOversize == 'resize'){
                // adjust resized height/width, preserve original aspect ratio
                if(change_h > change_w)
                    w = Math.round((width / height) * h);
                else if(change_w > change_h)
                    h = Math.round((height / width) * w);
                resize_w = w;
                resize_h = h;
            }

            // update Shadowbox.dimensions
            S.dimensions = {
                height:     h + tb,
                width:      w + lr,
                inner_h:    h,
                inner_w:    w,
                top:        (max_h - (h + extra_h)) / 2 + pad,
                left:       (max_w - (w + extra_w)) / 2 + pad,
                oversized:  oversized,
                resize_h:   resize_h,
                resize_w:   resize_w
            };
        },

        /**
         * Sets up listeners on the given links that will trigger Shadowbox. If no
         * links are given, this method will set up every anchor element on the page
         * with rel="shadowbox". It is important to note that any options given here
         * are applied to all link elements. Multiple calls to this method may be
         * needed if different options are desired.
         *
         * Note: Because AREA elements do not support the rel attribute, they must
         * be explicitly passed to this method.
         *
         * @param   mixed       links       A link selector (see findLinks)
         * @param   Object      opts        Some options to use for the given links
         * @return  void
         * @public
         */
        setup: function(links, opts){
            each(S.findLinks(links), function(link){
                S.addCache(link, opts);
            });
        },

        /**
         * Remove the given link elements from the cache, remove event listeners
         * and expandos as well.
         *
         * @param   mixed       links       A link selector (see findLinks)
         * @return  void
         * @public
         */
        teardown: function(links){
            each(S.findLinks(links), S.removeCache);
        },

        /**
         * Resolves a link selector. The selector may be void to select all
         * anchor elements on the page with rel="shadowbox" or, if the Sizzle
         * library is loaded, it may be a single CSS seletor or an array of
         * [selector, context].
         *
         * @param   mixed   links       The links selector (or selector + context)
         * @return  Array               An array of matching link elements
         * @public
         */
        findLinks: function(links){
            if(!links){
                var links = [], rel;
                each(document.getElementsByTagName('a'), function(a){
                    rel = a.getAttribute('rel');
                    if(rel && S.regex.rel.test(rel))
                        links.push(a);
                });
            }else{
                var len = links.length;
                if(len){
                    if(window['Sizzle']){
                        if(typeof links == 'string')
                            links = Sizzle(links); // lone selector
                        else if(len == 2 && links.push && typeof links[0] == 'string' && links[1].nodeType)
                            links = Sizzle(links[0], links[1]); // selector + context
                    }
                }else
                    links = [links]; // single link
            }

            return links;
        },

        /**
         * Tells if the given link element is already in the cache.
         *
         * @param   HTMLElement     link    The link element
         * @return  Boolean                 True if in the cache, false otherwise
         * @public
         */
        inCache: function(link){
            return typeof link[S.expando] == 'number' && S.cache[link[S.expando]];
        },

        /**
         * Adds the given link element to the cache with the given options.
         *
         * @param   HTMLElement     link    The link element
         * @return  void
         * @public
         */
        addCache: function(link, opts){
            if(!S.inCache(link)){
                // assign cache key expando, use integer primitive to avoid
                // memory leak in IE
                link[S.expando] = S.cache.length;
                // add onclick listener
                S.lib.addEvent(link, 'click', handleClick);
            }
            S.cache[link[S.expando]] = S.buildCacheObj(link, opts);
        },

        /**
         * Removes the given link element from the cache.
         *
         * @param   HTMLElement     link    The link element
         * @return  void
         * @public
         */
        removeCache: function(link){
            S.lib.removeEvent(link, 'click', handleClick);
            S.cache[link[S.expando]] = null;
            delete link[S.expando];
        },

        /**
         * Removes all onclick listeners from elements that have been setup with
         * Shadowbox and clears all objects from cache.
         *
         * @return  void
         * @public
         */
        clearCache: function(){
            each(S.cache, function(obj){
                S.removeCache(obj.link);
            });
            S.cache = [];
        },

        /**
         * Builds an object from the original link element data to store in cache.
         * These objects contain (most of) the following keys:
         *
         * - link: the link element
         * - title: the object's title
         * - player: the player to use for the object
         * - content: the object's URL
         * - gallery: the gallery the object belongs to (optional)
         * - height: the height of the object (only necessary for movies)
         * - width: the width of the object (only necessary for movies)
         * - options: custom options to use (optional)
         *
         * A custom set of options may be passed in here that will be applied when
         * this object is displayed. However, any options that are specified in
         * the link's HTML markup will trump options given here.
         *
         * @param   HTMLElement     link    The link element to process
         * @param   Object          opts    A set of options to use for the object
         * @return  Object                  An object representing the link
         * @public
         */
        buildCacheObj: function(link, opts){
            var obj = {
                link:       link,
                title:      link.getAttribute('title'),
                options:    apply({}, opts || {}),
                content:    link.href // don't use getAttribute here
            };

            // remove link-level options from top-level options
            if(opts) each(['player', 'title', 'height', 'width', 'gallery'], function(option){
                if(typeof obj.options[option] != 'undefined'){
                    obj[option] = obj.options[option];
                    delete obj.options[option];
                }
            });

            if(!obj.player)
                obj.player = S.getPlayer(obj.content);

            // HTML options always trump JavaScript options, so do these last
            var rel = link.getAttribute('rel');
            if(rel){
                // extract gallery name from shadowbox[name] format
                var match = rel.match(S.regex.gallery);
                if(match)
                    obj.gallery = escape(match[2]);

                // other parameters
                each(rel.split(';'), function(parameter){
                    match = parameter.match(S.regex.param);
                    if(match){
                        if(match[1] == 'options')
                            eval('apply(obj.options,' + match[2] + ')');
                        else
                            obj[match[1]] = match[2];
                    }
                });
            }

            return obj;
        },

        /**
         * Attempts to automatically determine the correct player to use based on the
         * given content attribute. If the content type can be detected but is not
         * supported, the return value will be 'unsupported-*' where * will be the
         * player abbreviation (e.g. 'qt' = QuickTime). Defaults to 'iframe' where the
         * content type cannot automatically be determined.
         *
         * @param   String  content     The content attribute of the item
         * @return  String              The name of the player to use
         * @public
         */
        getPlayer: function(content){
            var r = S.regex,
                p = S.plugins,
                m = content.match(r.domain),
                same_domain = m && document.domain == m[1];

            if(content.indexOf('#') > -1 && same_domain) return 'inline';

            // strip query string for player detection purposes
            var q = content.indexOf('?');
            if(q > -1) content = content.substring(0, q);

            if(r.img.test(content)) return 'img';
            if(r.swf.test(content)) return p.fla ? 'swf' : 'unsupported-swf';
            if(r.flv.test(content)) return p.fla ? 'flv' : 'unsupported-flv';
            if(r.qt.test(content)) return p.qt ? 'qt' : 'unsupported-qt';
            if(r.wmp.test(content)){
                if(p.wmp) return 'wmp';
                if(p.f4m) return 'qt';
                if(S.client.isMac) return p.qt ? 'unsupported-f4m' : 'unsupported-qtf4m';
                return 'unsupported-wmp';
            }
            if(r.qtwmp.test(content)){
                if(p.qt) return 'qt';
                if(p.wmp) return 'wmp';
                return S.client.isMac ? 'unsupported-qt' : 'unsupported-qtwmp';
            }

            return 'iframe';
        }

    },

    U = S.util = {

        /**
         * Animates any numeric (not color) style of the given element from its
         * current state to the given value. Defaults to using pixel-based
         * measurements.
         *
         * @param   HTMLElement     el      The element to animate
         * @param   String          p       The property to animate (in camelCase)
         * @param   mixed           to      The value to animate to
         * @param   Number          d       The duration of the animation (in
         *                                  seconds)
         * @param   Function        cb      A callback function to call when the
         *                                  animation completes
         * @return  void
         * @public
         */
        animate: function(el, p, to, d, cb){
            var from = parseFloat(S.lib.getStyle(el, p));
            if(isNaN(from)) from = 0;

            var delta = to - from;
            if(delta == 0){
                if(cb) cb();
                return; // nothing to animate
            }

            var op = p == 'opacity';

            function fn(ease){
                var to = from + ease * delta;
                if(op)
                    U.setOpacity(el, to);
                else
                    el.style[p] = to + 'px'; // default unit is px
            }

            // cancel the animation here if duration is 0 or if set in the options
            if(!d || (!op && !S.options.animate) || (op && !S.options.animateFade)){
                fn(1);
                if(cb) cb();
                return;
            }

            d *= 1000; // convert to milliseconds

            var begin = new Date().getTime(),
            ease = S.options.ease,
            end = begin + d,
            time,
            timer = setInterval(function(){
                time = new Date().getTime();
                if(time >= end){ // end of animation
                    clearInterval(timer);
                    fn(1);
                    if(cb) cb();
                }else
                    fn(ease((time - begin) / d));
            }, 10); // 10 ms interval is minimum on webkit
        },

        /**
         * Applies all properties of e to o.
         *
         * @param   Object      o       The original object
         * @param   Object      e       The extension object
         * @return  Object              The original object with all properties
         *                              of the extension object applied
         * @public
         */
        apply: function(o, e){
            for(var p in e)
                o[p] = e[p];

            return o;
        },

        /**
         * A utility function used by the fade functions to clear the opacity
         * style setting of the given element. Required in some cases for IE.
         *
         * @param   HTMLElement     el      The element
         * @return  void
         * @public
         */
        clearOpacity: function(el){
            var s = el.style;
            if(window.ActiveXObject){
                // be careful not to overwrite other filters!
                if(typeof s.filter == 'string' && (/alpha/i).test(s.filter))
                    s.filter = s.filter.replace(/[\w\.]*alpha\(.*?\);?/i, '');
            }else
                s.opacity = '';
        },

        /**
         * Calls the given function for each element of obj. The obj element must
         * be array-like (meaning it must have a length property and be able to
         * be accessed using the array square bracket syntax). If scope is not
         * explicitly given, the callback will be called with a scope of the
         * current item. Will stop execution if a callback returns false.
         *
         * @param   mixed       obj     An array-like object containing the
         *                              elements
         * @param   Function    fn      The callback function
         * @param   mixed       scope   The scope of the callback
         * @return  void
         * @public
         */
        each: function(obj, fn, scope){
            for(var i = 0, len = obj.length; i < len; ++i)
                if(fn.call(scope || obj[i], obj[i], i, obj) === false) return;
        },

        /**
         * Gets an element by its id.
         *
         * @param   String      id      The element id
         * @return  HTMLElement         A reference to the element with the
         *                              given id
         * @public
         */
        get: function(id){
            return document.getElementById(id);
        },

        /**
         * Dynamically includes a JavaScript file in the current page.
         *
         * @param   String      file    The name of the js file to include
         * @return  void
         * @public
         */
        include: function(){
            var includes = {};
            return function(file){
                if(includes[file]) return; // don't include the same file twice
                includes[file] = true;
                var head = document.getElementsByTagName('head')[0],
                    script = document.createElement('script');
                script.src = file;
                head.appendChild(script);
            }
        }(),

        /**
         * Determines if the given object is an anchor/area element.
         *
         * @param   mixed       obj     The object to check
         * @return  Boolean             True if the object is a link element
         * @public
         */
        isLink: function(obj){
            if(!obj || !obj.tagName) return false;
            var up = obj.tagName.toUpperCase();
            return up == 'A' || up == 'AREA';
        },

        /**
         * Removes all child nodes from the given element.
         *
         * @param   HTMLElement     el      The element
         * @return  void
         * @public
         */
        removeChildren: function(el){
            while(el.firstChild)
                el.removeChild(el.firstChild);
        },

        /**
         * Sets the opacity of the given element to the specified level.
         *
         * @param   HTMLElement     el      The element
         * @param   Number          o       The opacity to use
         * @return  void
         * @public
         */
        setOpacity: function(el, o){
            var s = el.style;
            if(window.ActiveXObject){
                s.zoom = 1; // trigger hasLayout
                s.filter = (s.filter || '').replace(/\s*alpha\([^\)]*\)/gi, '') +
                    (o == 1 ? '' : ' alpha(opacity=' + (o * 100) + ')');
            }else
                s.opacity = o;
        }

    },

    // shorthand
    apply = U.apply,
    each = U.each,

    /**
     * The initial options object that was given by the user.
     *
     * @var     Object
     * @private
     */
    init_options,

    /**
     * Keeps track of whether or not Shadowbox.init has been called.
     *
     * @var     Boolean
     * @private
     */
    initialized = false,

    /**
     * Stores the default set of options in case a custom set of options is used
     * on a link-by-link basis so we can restore them later.
     *
     * @var     Object
     * @private
     */
    default_options = {},

    /**
     * The id to use for content objects.
     *
     * @var     String
     * @private
     */
    content_id = 'sb-content',

    /**
     * Keeps track of whether or not Shadowbox is activated.
     *
     * @var     Boolean
     * @private
     */
    active = false,

    /**
     * The timeout id for the slideshow transition function.
     *
     * @var     Number
     * @private
     */
    slide_timer,

    /**
     * Keeps track of the time at which the current slideshow frame was
     * displayed.
     *
     * @var     Number
     * @private
     */
    slide_start,

    /**
     * The delay on which the next slide will display.
     *
     * @var     Number
     * @private
     */
    slide_delay = 0;

    // detect plugin support
    if(navigator.plugins && navigator.plugins.length){
        var names = [];
        each(navigator.plugins, function(p){
            names.push(p.name);
        });
        names = names.join();

        var f4m = names.indexOf('Flip4Mac') > -1;
        S.plugins = {
            fla:    names.indexOf('Shockwave Flash') > -1,
            qt:     names.indexOf('QuickTime') > -1,
            wmp:    !f4m && names.indexOf('Windows Media') > -1, // if it's Flip4Mac, it's not really WMP
            f4m:    f4m
        }
    }else{
        function detectPlugin(n){
            try{
                var axo = new ActiveXObject(n);
            }catch(e){}
            return !!axo;
        }

        S.plugins = {
            fla:    detectPlugin('ShockwaveFlash.ShockwaveFlash'),
            qt:     detectPlugin('QuickTime.QuickTime'),
            wmp:    detectPlugin('wmplayer.ocx'),
            f4m:    false
        }
    }

    /**
     * Waits for the DOM to be ready before firing the given callback
     * function. This function adapted from the jQuery framework.
     *
     * @param   Function    cb      The callback function
     * @return  void
     * @private
     */
    function waitDom(cb){
        // mozilla, opera and webkit nightlies currently support this event
        if(document.addEventListener){
            // use the handy event callback
            document.addEventListener( "DOMContentLoaded", function(){
                document.removeEventListener("DOMContentLoaded", arguments.callee, false);
                cb();
            }, false);

        // if IE event model is used
        }else if(document.attachEvent){
            // ensure firing before onload, maybe late but safe also for iframes
            document.attachEvent("onreadystatechange", function(){
                if(document.readyState === "complete"){
                    document.detachEvent("onreadystatechange", arguments.callee);
                    cb();
                }
            });

            // if IE and not an iframe, continually check to see if the document is ready
            if(document.documentElement.doScroll && window == window.top) (function(){
                if(S.ready) return;

                try{
                    // if IE is used, use the trick by Diego Perini
                    // http://javascript.nwbox.com/IEContentLoaded/
                    document.documentElement.doScroll("left");
                }catch(error){
                    setTimeout(arguments.callee, 0);
                    return;
                }

                cb();
            })();
        }

        // a fallback to window.onload, that will always work
        if(typeof window.onload == 'function'){
            var oldonload = window.onload;
            window.onload = function(){
                oldonload();
                cb();
            }
        }else
            window.onload = cb;
    }

    /**
     * Waits for all necessary libraries to load before calling Shadowbox.load.
     * This is necessary because some browsers (Safari) will fire the DOM ready
     * event before dynamically included scripts are loaded.
     *
     * @return  void
     * @private
     */
    function waitLibs(){
        if(S.lib && S.lang)
            S.load(); // ready to go!
        else
            setTimeout(waitLibs, 0);
    }

    /**
     * Handles all clicks on links that have been set up to work with Shadowbox
     * and cancels the default event behavior when appropriate.
     *
     * @param   HTMLEvent   e           The click event object
     * @return  void
     * @private
     */
    function handleClick(e){
        var link;
        if(U.isLink(this)){
            link = this; // jQuery, Prototype, YUI
        }else{
            link = S.lib.getTarget(e); // Ext, standalone
            while(!U.isLink(link) && link.parentNode)
                link = link.parentNode;
        }

        S.lib.preventDefault(e); // good for debugging

        if(link){
            S.open(link);

            if(S.gallery.length)
                S.lib.preventDefault(e);
        }
    }

    /**
     * Sets up a listener on the document for keystrokes.
     *
     * @param   Boolean     on      True to enable the listener, false to disable
     * @return  void
     * @private
     */
    function listenKeys(on){
        if(!S.options.enableKeys) return;
        S.lib[(on ? 'add' : 'remove') + 'Event'](document, 'keydown', handleKey);
    }

    /**
     * A listener function that is fired when a key is pressed.
     *
     * @param   mixed       e       The event object
     * @return  void
     * @private
     */
    function handleKey(e){
        var code = S.lib.keyCode(e), handler;

        switch(code){
            case 81: // q
            case 88: // x
            case 27: // esc
                handler = S.close;
                break;
            case 37: // left
                handler = S.previous;
                break;
            case 39: // right
                handler = S.next;
                break;
            case 32: // space
                handler = typeof slide_timer == 'number' ? S.pause : S.play;
        }

        if(handler){
            // attempt to prevent default key action
            S.lib.preventDefault(e);
            handler();
        }
    }

    /**
     * Loads the Shadowbox with the current piece.
     *
     * @return  void
     * @private
     */
    function loadContent(){
        var obj = S.getCurrent();
        if(!obj) return;

        // determine player, inline is really just HTML
        var p = obj.player == 'inline' ? 'html' : obj.player;
        if(typeof S[p] != 'function')
            S.error('Unknown player: ' + p);

        var change = false;
        if(S.content){
            // changing from some previous content
            S.content.remove(); // remove old content
            change = true;

            S.revertOptions();
            if(obj.options)
                S.applyOptions(obj.options);
        }

        // make sure the body element doesn't have any children, just in case
        U.removeChildren(S.skin.bodyEl());

        // load the content
        S.content = new S[p](obj);
        listenKeys(false); // disable the keyboard while content is loading

        S.skin.onLoad(S.content, change, function(){
            if(!S.content) return;

            if(typeof S.content.ready != 'undefined'){
                // if content object has a ready property, wait for it to be
                // ready before loading
                var id = setInterval(function(){
                    if(S.content){
                        if(S.content.ready){
                            clearInterval(id);
                            id = null;
                            S.skin.onReady(contentReady);
                        }
                    }else{ // content has been removed
                        clearInterval(id);
                        id = null;
                    }
                }, 100);
            }else
                S.skin.onReady(contentReady);
        });

        // preload neighboring gallery images
        if(S.gallery.length > 1){
            var next = S.gallery[S.current + 1] || S.gallery[0];
            if(next.player == 'img'){
                var a = new Image();
                a.src = next.content;
            }
            var prev = S.gallery[S.current - 1] || S.gallery[S.gallery.length - 1];
            if(prev.player == 'img'){
                var b = new Image();
                b.src = prev.content;
            }
        }
    }

    /**
     * Callback that should be called with the content is ready to be loaded.
     *
     * @return  void
     * @private
     */
    function contentReady(){
        if(!S.content) return;
        S.content.append(S.skin.bodyEl(), content_id, S.dimensions);
        S.skin.onFinish(finishContent);
    }

    /**
     * Callback that should be called when the content is finished loading.
     *
     * @return  void
     * @private
     */
    function finishContent(){
        if(!S.content) return;

        if(S.content.onLoad)
            S.content.onLoad();
        if(S.options.onFinish)
            S.options.onFinish();
        if(!S.isPaused())
            S.play(); // kick off next slide

        listenKeys(true); // re-enable keyboard when finished
    }

    // expose
    window['Shadowbox'] = S;

})();

/**
 * The default skin for Shadowbox. Separated out into its own class so that it may
 * be customized more easily by skin developers.
 */
(function(){

    var S = Shadowbox,
    U = S.util,

    /**
     * Keeps track of whether or not the overlay is activated.
     *
     * @var     Boolean
     * @private
     */
    overlay_on = false,

    /**
     * A cache of elements that are troublesome for modal overlays.
     *
     * @var     Array
     * @private
     */
    visibility_cache = [],

    /**
     * Id's of elements that need transparent PNG support in IE6.
     *
     * @var     Array
     * @private
     */
    png = [
        'sb-nav-close',
        'sb-nav-next',
        'sb-nav-play',
        'sb-nav-pause',
        'sb-nav-previous'
    ],

    // the Shadowbox.skin object
    K = {

        /**
         * The HTML markup to use.
         *
         * @var     String
         * @public
         */
        markup: '<div id="sb-container">' +
                    '<div id="sb-overlay"></div>' +
                    '<div id="sb-wrapper">' +
                        '<div id="sb-title">' +
                            '<div id="sb-title-inner"></div>' +
                        '</div>' +
                        '<div id="sb-body">' +
                            '<div id="sb-body-inner"></div>' +
                            '<div id="sb-loading">' +
                                '<a onclick="Shadowbox.close()">{cancel}</a>' +
                            '</div>' +
                        '</div>' +
                        '<div id="sb-info">' +
                            '<div id="sb-info-inner">' +
                                '<div id="sb-counter"></div>' +
                                '<div id="sb-nav">' +
                                    '<a id="sb-nav-close" title="{close}" onclick="Shadowbox.close()"></a>' +
                                    '<a id="sb-nav-next" title="{next}" onclick="Shadowbox.next()"></a>' +
                                    '<a id="sb-nav-play" title="{play}" onclick="Shadowbox.play()"></a>' +
                                    '<a id="sb-nav-pause" title="{pause}" onclick="Shadowbox.pause()"></a>' +
                                    '<a id="sb-nav-previous" title="{previous}" onclick="Shadowbox.previous()"></a>' +
                                '</div>' +
                                '<div style="clear:both"></div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>',

        /**
         * Options that are specific to the skin.
         *
         * @var     Object
         * @public
         */
        options: {

            animSequence: 'sync',   // the sequence of the resizing animations. "hw" will resize
                                    // height, then width. "wh" resizes width, then height. "sync"
                                    // resizes both simultaneously
            autoDimensions: false,  // use the dimensions of the first piece as the initial dimensions
                                    // if they are available
            counterLimit: 10,       // limit to the number of counter links that
                                    // are displayed in a "skip" style counter
            counterType: 'default', // counter type. May be either "default" or
                                    // "skip". Skip counter displays a link for
                                    // each item in gallery
            displayCounter: true,   // display the gallery counter
            displayNav: true,       // show the navigation controls
            fadeDuration: 0.35,     // duration of the fade animations (in seconds)
            initialHeight: 160,     // initial height (pixels)
            initialWidth: 320,      // initial width (pixels)
            modal: false,           // trigger Shadowbox.close() when overlay is
                                    // clicked
            overlayColor: '#000',   // color to use for modal overlay
            overlayOpacity: 0.8,    // opacity to use for modal overlay
            resizeDuration: 0.35,   // duration of the resizing animations (in seconds)
            showOverlay: true,      // show the overlay
            troubleElements: ['select', 'object', 'embed', 'canvas']  // names of elements that are
                                                                      // troublesome for modal overlays

        },

        /**
         * Initialization function. Called immediately after this skin's markup
         * has been appended to the document with all of the necessary language
         * replacements done.
         *
         * @return  void
         * @public
         */
        init: function(){
            // append markup to body
            var markup = K.markup.replace(/\{(\w+)\}/g, function(m, p){
                return S.lang[p];
            });
            S.lib.append(document.body, markup);

            // several fixes for IE6
            if(S.client.isIE6){
                // trigger hasLayout on sb-body
                U.get('sb-body').style.zoom = 1;

                // support transparent PNG's via AlphaImageLoader
                var el, m, re = /url\("(.*\.png)"\)/;
                U.each(png, function(id){
                    el = U.get(id);
                    if(el){
                        m = S.lib.getStyle(el, 'backgroundImage').match(re);
                        if(m){
                            el.style.backgroundImage = 'none';
                            el.style.filter = 'progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true,src=' +
                                m[1] + ',sizingMethod=scale);';
                        }
                    }
                });
            }

            // set up window resize event handler
            var id;
            S.lib.addEvent(window, 'resize', function(){
                // use 50 ms event buffering to prevent jerky window resizing
                if(id){
                    clearTimeout(id);
                    id = null;
                }
                // check if activated because IE7 fires window resize event
                // when container display is set to block
                if(S.isActive()){
                    id = setTimeout(function(){
                        K.onWindowResize();
                        var c = S.content;
                        if(c && c.onWindowResize)
                            c.onWindowResize();
                    }, 50);
                }
            });
        },

        /**
         * Gets the element that content should be appended to.
         *
         * @return  HTMLElement     The body element
         * @public
         */
        bodyEl: function(){
            return U.get('sb-body-inner');
        },

        /**
         * Called when Shadowbox opens from an inactive state.
         *
         * @param   Object      obj     The object to open
         * @param   Function    cb      The function to call when finished
         * @return  void
         * @public
         */
        onOpen: function(obj, cb){
            toggleTroubleElements(false);

            var h = S.options.autoDimensions && 'height' in obj
                ? obj.height
                : S.options.initialHeight,
                w = S.options.autoDimensions && 'width' in obj
                ? obj.width
                : S.options.initialWidth;

            U.get('sb-container').style.display = 'block';

            var d = setDimensions(h, w);
            adjustHeight(d.inner_h, d.top, false);
            adjustWidth(d.width, d.left, false);
            toggleVisible(cb);
        },

        /**
         * Called when a new piece of content is being loaded.
         *
         * @param   mixed       content     The content object
         * @param   Boolean     change      True if the content is changing
         *                                  from some previous content
         * @param   Function    cb          A callback that should be fired when
         *                                  this function is finished
         * @return  void
         * @public
         */
        onLoad: function(content, change, cb){
            toggleLoading(true);

            hideBars(change, function(){ // if changing, animate the bars transition
                if(!content) return;

                // if opening, clear #sb-wrapper display
                if(!change) U.get('sb-wrapper').style.display = '';

                cb();
            });
        },

        /**
         * Called when the content is ready to be loaded (e.g. when the image
         * has finished loading). Should resize the content box and make any
         * other necessary adjustments.
         *
         * @param   Function    cb          A callback that should be fired when
         *                                  this function is finished
         * @return  void
         * @public
         */
        onReady: function(cb){
            var c = S.content;
            if(!c) return;

            // set new dimensions
            var d = setDimensions(c.height, c.width, c.resizable);

            K.resizeContent(d.inner_h, d.width, d.top, d.left, true, function(){
                showBars(cb);
            });
        },

        /**
         * Called when the content is loaded into the box and is ready to be
         * displayed.
         *
         * @param   Function    cb          A callback that should be fired when
         *                                  this function is finished
         * @return  void
         * @public
         */
        onFinish: function(cb){
            toggleLoading(false, cb);
        },

        /**
         * Called when Shadowbox is closed.
         *
         * @return  void
         * @public
         */
        onClose: function(){
            toggleVisible();
            toggleTroubleElements(true);
        },

        /**
         * Called in Shadowbox.play().
         *
         * @return  void
         * @public
         */
        onPlay: function(){
            toggleNav('play', false);
            toggleNav('pause', true);
        },

        /**
         * Called in Shadowbox.pause().
         *
         * @return  void
         * @public
         */
        onPause: function(){
            toggleNav('pause', false);
            toggleNav('play', true);
        },

        /**
         * Called when the window is resized.
         *
         * @return  void
         * @public
         */
        onWindowResize: function(){
            var c = S.content;
            if(!c) return;

            // set new dimensions
            var d = setDimensions(c.height, c.width, c.resizable);

            adjustWidth(d.width, d.left, false);
            adjustHeight(d.inner_h, d.top, false);

            var el = U.get(S.contentId());
            if(el){
                // resize resizable content when in resize mode
                if(c.resizable && S.options.handleOversize == 'resize'){
                    el.height = d.resize_h;
                    el.width = d.resize_w;
                }
            }
        },

        /**
         * Resizes Shadowbox to the appropriate height and width for the current
         * content.
         *
         * @param   Number      height  The new height to use
         * @param   Number      width   The new width to use
         * @param   Number      top     The new top to use
         * @param   Number      left    The new left to use
         * @param   Boolean     anim    True to animate the transition
         * @param   Function    cb      A callback function to execute after the
         *                              resize completes
         * @return  void
         * @public
         */
        resizeContent: function(height, width, top, left, anim, cb){
            var c = S.content;
            if(!c) return;

            // set new dimensions
            var d = setDimensions(c.height, c.width, c.resizable);

            switch(S.options.animSequence){
                case 'hw':
                    adjustHeight(d.inner_h, d.top, anim, function(){
                        adjustWidth(d.width, d.left, anim, cb);
                    });
                break;
                case 'wh':
                    adjustWidth(d.width, d.left, anim, function(){
                        adjustHeight(d.inner_h, d.top, anim, cb);
                    });
                break;
                default: // sync
                    adjustWidth(d.width, d.left, anim);
                    adjustHeight(d.inner_h, d.top, anim, cb);
            }
        }

    };

    /**
     * Sets the top of the container element. This is only necessary in IE6
     * where the container uses absolute positioning instead of fixed.
     *
     * @return  void
     * @private
     */
    function fixTop(){
        U.get('sb-container').style.top = document.documentElement.scrollTop + 'px';
    }

    /**
     * Toggles the visibility of elements that are troublesome for modal
     * overlays.
     *
     * @return  void
     * @private
     */
    function toggleTroubleElements(on){
        if(on){
            U.each(visibility_cache, function(c){
                c[0].style.visibility = c[1] || '';
            });
        }else{
            visibility_cache = [];
            U.each(S.options.troubleElements, function(tag){
                U.each(document.getElementsByTagName(tag), function(el){
                    visibility_cache.push([el, el.style.visibility]);
                    el.style.visibility = 'hidden';
                });
            });
        }
    }

    /**
     * Toggles the visibility of #sb-container and sets its size (if on
     * IE6). Also toggles the visibility of elements (<select>, <object>, and
     * <embed>) that are troublesome for semi-transparent modal overlays. IE has
     * problems with <select> elements, while Firefox has trouble with
     * <object>s.
     *
     * @param   Function    cb      A callback to call after toggling on, absent
     *                              when toggling off
     * @return  void
     * @private
     */
    function toggleVisible(cb){
        var so = U.get('sb-overlay'),
            sc = U.get('sb-container'),
            sb = U.get('sb-wrapper');

        if(cb){
            if(S.client.isIE6){
                // fix container top before showing
                fixTop();
                S.lib.addEvent(window, 'scroll', fixTop);
            }
            if(S.options.showOverlay){
                overlay_on = true;

                // set overlay color/opacity
                so.style.backgroundColor = S.options.overlayColor;
                U.setOpacity(so, 0);
                if(!S.options.modal) S.lib.addEvent(so, 'click', S.close);

                sb.style.display = 'none'; // cleared in onLoad
            }
            sc.style.visibility = 'visible';
            if(overlay_on){
                // fade in effect
                var op = parseFloat(S.options.overlayOpacity);
                U.animate(so, 'opacity', op, S.options.fadeDuration, cb);
            }else
                cb();
        }else{
            if(S.client.isIE6)
                S.lib.removeEvent(window, 'scroll', fixTop);
            S.lib.removeEvent(so, 'click', S.close);
            if(overlay_on){
                // fade out effect
                sb.style.display = 'none';
                U.animate(so, 'opacity', 0, S.options.fadeDuration, function(){
                    // the following is commented because it causes the overlay to
                    // be hidden on consecutive activations in IE8, even though we
                    // set the visibility to "visible" when we reactivate
                    //sc.style.visibility = 'hidden';
                    sc.style.display = '';
                    sb.style.display = '';
                    U.clearOpacity(so);
                });
            }else
                sc.style.visibility = 'hidden';
        }
    }

    /**
     * Toggles the display of the nav control with the given id.
     *
     * @param   String      id      The id of the navigation control
     * @param   Boolean     on      True to toggle on, false to toggle off
     * @return  void
     * @private
     */
    function toggleNav(id, on){
        var el = U.get('sb-nav-' + id);
        if(el) el.style.display = on ? '' : 'none';
    }

    /**
     * Toggles the visibility of the "loading" layer.
     *
     * @param   Boolean     on      True to toggle on, false to toggle off
     * @param   Function    cb      The callback function to call when toggling
     *                              completes
     * @return  void
     * @private
     */
    function toggleLoading(on, cb){
        var ld = U.get('sb-loading'),
            p = S.getCurrent().player,
            anim = (p == 'img' || p == 'html'); // fade on images & html

        if(on){
            function fn(){
                U.clearOpacity(ld);
                if(cb) cb();
            }

            U.setOpacity(ld, 0);
            ld.style.display = '';

            if(anim)
                U.animate(ld, 'opacity', 1, S.options.fadeDuration, fn);
            else
                fn();
        }else{
            function fn(){
                ld.style.display = 'none';
                U.clearOpacity(ld);
                if(cb) cb();
            }

            if(anim)
                U.animate(ld, 'opacity', 0, S.options.fadeDuration, fn);
            else
                fn();
        }
    }

    /**
     * Builds the content for the title and information bars.
     *
     * @param   Function    cb      A callback function to execute after the
     *                              bars are built
     * @return  void
     * @private
     */
    function buildBars(cb){
        var obj = S.getCurrent();

        // build the title, if present
        U.get('sb-title-inner').innerHTML = obj.title || '';

        // build the nav
        var c, n, pl, pa, p;
        if(S.options.displayNav){
            c = true;
            // next & previous links
            var len = S.gallery.length;
            if(len > 1){
                if(S.options.continuous)
                    n = p = true; // show both
                else{
                    n = (len - 1) > S.current; // not last in gallery, show next
                    p = S.current > 0; // not first in gallery, show previous
                }
            }
            // in a slideshow?
            if(S.options.slideshowDelay > 0 && S.hasNext()){
                pa = !S.isPaused();
                pl = !pa;
            }
        }else{
            c = n = pl = pa = p = false;
        }
        toggleNav('close', c);
        toggleNav('next', n);
        toggleNav('play', pl);
        toggleNav('pause', pa);
        toggleNav('previous', p);

        // build the counter
        var counter = '';
        if(S.options.displayCounter && S.gallery.length > 1){
            var len = S.gallery.length;

            if(S.options.counterType == 'skip'){
                // limit the counter?
                var i = 0,
                    end = len,
                    limit = parseInt(S.options.counterLimit) || 0;

                if(limit < len && limit > 2){ // support large galleries
                    var h = Math.floor(limit / 2);
                    i = S.current - h;
                    if(i < 0) i += len;
                    end = S.current + (limit - h);
                    if(end > len) end -= len;
                }
                while(i != end){
                    if(i == len) i = 0;
                    counter += '<a onclick="Shadowbox.change(' + i + ');"'
                    if(i == S.current) counter += ' class="sb-counter-current"';
                    counter += '>' + (i++) + '</a>';
                }
            }else
                var counter = (S.current + 1) + ' ' + S.lang.of + ' ' + len;
        }

        U.get('sb-counter').innerHTML = counter;

        cb();
    }

    /**
     * Hides the title and info bars.
     *
     * @param   Boolean     anim    True to animate the transition
     * @param   Function    cb      A callback function to execute after the
     *                              animation completes
     * @return  void
     * @private
     */
    function hideBars(anim, cb){
        var sw = U.get('sb-wrapper'),
            st = U.get('sb-title'),
            si = U.get('sb-info'),
            ti = U.get('sb-title-inner'),
            ii = U.get('sb-info-inner'),
            t = parseInt(S.lib.getStyle(ti, 'height')) || 0,
            b = parseInt(S.lib.getStyle(ii, 'height')) || 0;

        var fn = function(){
            // hide bars here in case of overflow, build after hidden
            ti.style.visibility = ii.style.visibility = 'hidden';
            buildBars(cb);
        }

        if(anim){
            U.animate(st, 'height', 0, 0.35);
            U.animate(si, 'height', 0, 0.35);
            U.animate(sw, 'paddingTop', t, 0.35);
            U.animate(sw, 'paddingBottom', b, 0.35, fn);
        }else{
            st.style.height = si.style.height = '0px';
            sw.style.paddingTop = t + 'px';
            sw.style.paddingBottom = b + 'px';
            fn();
        }
    }

    /**
     * Shows the title and info bars.
     *
     * @param   Function    cb      A callback function to execute after the
     *                              animation completes
     * @return  void
     * @private
     */
    function showBars(cb){
        var sw = U.get('sb-wrapper'),
            st = U.get('sb-title'),
            si = U.get('sb-info'),
            ti = U.get('sb-title-inner'),
            ii = U.get('sb-info-inner'),
            t = parseInt(S.lib.getStyle(ti, 'height')) || 0,
            b = parseInt(S.lib.getStyle(ii, 'height')) || 0;

        // clear visibility before animating into view
        ti.style.visibility = ii.style.visibility = '';

        // show title?
        if(ti.innerHTML != ''){
            U.animate(st, 'height', t, 0.35);
            U.animate(sw, 'paddingTop', 0, 0.35);
        }
        U.animate(si, 'height', b, 0.35);
        U.animate(sw, 'paddingBottom', 0, 0.35, cb);
    }

    /**
     * Adjusts the height of #sb-body and centers #sb-wrapper vertically
     * in the viewport.
     *
     * @param   Number      height      The height to use for #sb-body
     * @param   Number      top         The top to use for #sb-wrapper
     * @param   Boolean     anim        True to animate the transition
     * @param   Function    cb          A callback to use when the animation
     *                                  completes
     * @return  void
     * @private
     */
    function adjustHeight(height, top, anim, cb){
        var sb = U.get('sb-body'),
            s = U.get('sb-wrapper'),
            h = parseInt(height),
            t = parseInt(top);

        if(anim){
            U.animate(sb, 'height', h, S.options.resizeDuration);
            U.animate(s, 'top', t, S.options.resizeDuration, cb);
        }else{
            sb.style.height = h + 'px';
            s.style.top = t + 'px';
            if(cb) cb();
        }
    }

    /**
     * Adjusts the width and left of #sb-wrapper.
     *
     * @param   Number      width       The width to use for #sb-wrapper
     * @param   Number      left        The left to use for #sb-wrapper
     * @param   Boolean     anim        True to animate the transition
     * @param   Function    cb          A callback to use when the animation
     *                                  completes
     * @return  void
     * @private
     */
    function adjustWidth(width, left, anim, cb){
        var s = U.get('sb-wrapper'),
            w = parseInt(width),
            l = parseInt(left);

        if(anim){
            U.animate(s, 'width', w, S.options.resizeDuration);
            U.animate(s, 'left', l, S.options.resizeDuration, cb);
        }else{
            s.style.width = w + 'px';
            s.style.left = l + 'px';
            if(cb) cb();
        }
    }

    /**
     * Calculates the dimensions for Shadowbox, taking into account the borders
     * and surrounding elements of #sb-body.
     *
     * @param   Number      height      The content height
     * @param   Number      width       The content width
     * @param   Boolean     resizable   True if the content is able to be
     *                                  resized. Defaults to false
     * @return  Object                  The new dimensions object
     * @private
     */
    function setDimensions(height, width, resizable){
        var sbi = U.get('sb-body-inner')
            sw = U.get('sb-wrapper'),
            so = U.get('sb-overlay'),
            tb = sw.offsetHeight - sbi.offsetHeight,
            lr = sw.offsetWidth - sbi.offsetWidth,
            max_h = so.offsetHeight, // measure overlay to get viewport size for IE6
            max_w = so.offsetWidth;

        S.setDimensions(height, width, max_h, max_w, tb, lr, resizable);

        return S.dimensions;
    }

    // expose
    S.skin = K;

})();

/**
 * An adapter for Shadowbox and the Yahoo! User Interface (YUI) library.
 *
 * This file is part of Shadowbox.
 *
 * Shadowbox is an online media viewer application that supports all of the
 * web's most popular media publishing formats. Shadowbox is written entirely
 * in JavaScript and CSS and is highly customizable. Using Shadowbox, website
 * authors can showcase a wide assortment of media in all major browsers without
 * navigating users away from the linking page.
 *
 * You should have received a license with this distribution explaining the terms
 * under which Shadowbox may be used. If you did not, you may obtain a copy of the
 * license at http://shadowbox-js.com/LICENSE
 *
 * @author      Michael J. I. Jackson <michael@mjijackson.com>
 * @copyright   2007-2009 Michael J. I. Jackson
 */

// requires yahoo-dom-event.js
if(typeof YAHOO == 'undefined')
    throw 'Unable to load Shadowbox adapter, YAHOO not found';

if(typeof Shadowbox == 'undefined')
    throw 'Unable to load Shadowbox adapter, Shadowbox not found';

(function(S){

    var E = YAHOO.util.Event,
        D = YAHOO.util.Dom;

    S.lib = {

        /**
         * Gets the value of the style on the given element.
         *
         * @param   HTMLElement     el          The DOM element
         * @param   String          style       The script name of the style
         *                                      (e.g. marginTop, not margin-top)
         * @return  mixed                       The value of the given style
         * @public
         */
        getStyle: function(el, style){
            return D.getStyle(el, style);
        },

        /**
         * Removes an element from the DOM.
         *
         * @param   HTMLElement     el          The element to remove
         * @return  void
         * @public
         */
        remove: function(el){
            el.parentNode.removeChild(el);
        },

        /**
         * Gets the target of the given event. The event object passed will be
         * the same object that is passed to listeners registered with
         * addEvent().
         *
         * @param   mixed           e           The event object
         * @return  HTMLElement                 The event's target element
         * @public
         */
        getTarget: function(e){
            return E.getTarget(e);
        },

        /**
         * Gets the page X/Y coordinates of the mouse event in an [x, y] array.
         * The page coordinates should be relative to the document, and not the
         * viewport. The event object provided here will be the same object that
         * is passed to listeners registered with addEvent().
         *
         * @param   mixed           e           The event object
         * @return  Array                       The page X/Y coordinates
         * @public
         */
        getPageXY: function(e){
            return [E.getPageX(e), E.getPageY(e)];
        },

        /**
         * Prevents the event's default behavior. The event object passed will
         * be the same object that is passed to listeners registered with
         * addEvent().
         *
         * @param   mixed           e           The event object
         * @return  void
         * @public
         */
        preventDefault: function(e){
            E.preventDefault(e);
        },

        /**
         * Gets the key code of the given event object (keydown). The event
         * object here will be the same object that is passed to listeners
         * registered with addEvent().
         *
         * @param   mixed           e           The event object
         * @return  Number                      The key code of the event
         * @public
         */
        keyCode: function(e){
            return e.keyCode;
        },

        /**
         * Adds an event listener to the given element. It is expected that this
         * function will be passed the event as its first argument.
         *
         * @param   HTMLElement     el          The DOM element to listen to
         * @param   String          name        The name of the event to register
         *                                      (i.e. 'click', 'scroll', etc.)
         * @param   Function        handler     The event handler function
         * @return  void
         * @public
         */
        addEvent: function(el, name, handler){
            E.addListener(el, name, handler);
        },

        /**
         * Removes an event listener from the given element.
         *
         * @param   HTMLElement     el          The DOM element to stop listening to
         * @param   String          name        The name of the event to stop
         *                                      listening for (i.e. 'click')
         * @param   Function        handler     The event handler function
         * @return  void
         * @public
         */
        removeEvent: function(el, name, handler){
            E.removeListener(el, name, handler);
        },

        /**
         * Appends an HTML fragment to the given element.
         *
         * @param   HTMLElement     el          The element to append to
         * @param   String          html        The HTML fragment to use
         * @return  void
         * @public
         */
        append: function(el, html){
            if(el.insertAdjacentHTML){
                el.insertAdjacentHTML('BeforeEnd', html);
            }else if(el.lastChild){
                var range = el.ownerDocument.createRange();
                range.setStartAfter(el.lastChild);
                var frag = range.createContextualFragment(html);
                el.appendChild(frag);
            }else{
                el.innerHTML = html;
            }
        }

    };

})(Shadowbox);

/**
 * The English language file for Shadowbox.
 *
 * This file is part of Shadowbox.
 *
 * Shadowbox is an online media viewer application that supports all of the
 * web's most popular media publishing formats. Shadowbox is written entirely
 * in JavaScript and CSS and is highly customizable. Using Shadowbox, website
 * authors can showcase a wide assortment of media in all major browsers without
 * navigating users away from the linking page.
 *
 * You should have received a license with this distribution explaining the terms
 * under which Shadowbox may be used. If you did not, you may obtain a copy of the
 * license at http://shadowbox-js.com/LICENSE
 *
 * @author      Michael J. I. Jackson <michael@mjijackson.com>
 * @copyright   2007-2009 Michael J. I. Jackson
 */

if(typeof Shadowbox == 'undefined')
    throw 'Unable to load Shadowbox language file, Shadowbox not found.';

/**
 * An object containing all textual messages to be used in Shadowbox. These are
 * provided so they may be translated into different languages. Alternative
 * translations may be found in js/lang/shadowbox-*.js where * is an abbreviation
 * of the language name (see
 * http://www.gnu.org/software/gettext/manual/gettext.html#Language-Codes).
 *
 * @var     Object
 * @public
 */
Shadowbox.lang = {

    code:       'en',

    of:         'of',

    loading:    'loading',

    cancel:     'Cancel',

    next:       'Next',

    previous:   'Previous',

    play:       'Play',

    pause:      'Pause',

    close:      'Close',

    errors:     {
        single: 'You must install the <a href="{0}">{1}</a> browser plugin to view this content.',
        shared: 'You must install both the <a href="{0}">{1}</a> and <a href="{2}">{3}</a> browser plugins to view this content.',
        either: 'You must install either the <a href="{0}">{1}</a> or the <a href="{2}">{3}</a> browser plugin to view this content.'
    }

};

/**
 * The Shadowbox Flash video player class.
 *
 * This file is part of Shadowbox.
 *
 * Shadowbox is an online media viewer application that supports all of the
 * web's most popular media publishing formats. Shadowbox is written entirely
 * in JavaScript and CSS and is highly customizable. Using Shadowbox, website
 * authors can showcase a wide assortment of media in all major browsers without
 * navigating users away from the linking page.
 *
 * You should have received a license with this distribution explaining the terms
 * under which Shadowbox may be used. If you did not, you may obtain a copy of the
 * license at http://shadowbox-js.com/LICENSE
 *
 * @author      Michael J. I. Jackson <michael@mjijackson.com>
 * @copyright   2007-2009 Michael J. I. Jackson
 */

(function(S){

    var U = S.util,
        controller_height = 20; // height of JW FLV player controller

    /**
     * Constructor. This class is used to display Flash videos with the JW
     * FLV player.
     *
     * @param   Object      obj     The content object
     * @public
     */
    S.flv = function(obj){
        this.obj = obj;

        // FLV's are resizable
        this.resizable = true;

        // height/width default to 300 pixels
        this.height = obj.height ? parseInt(obj.height, 10) : 300;
        if(S.options.showMovieControls == true)
            this.height += controller_height;
        this.width = obj.width ? parseInt(obj.width, 10) : 300;
    }

    S.flv.prototype = {

        /**
         * Appends this movie to the document.
         *
         * @param   HTMLElement     body    The body element
         * @param   String          id      The content id
         * @param   Object          dims    The current Shadowbox dimensions
         * @return  void
         * @public
         */
        append: function(body, id, dims){
            this.id = id;

            // append temporary content element to replace
            var tmp = document.createElement('div');
            tmp.id = id;
            body.appendChild(tmp);

            var h = dims.resize_h, // use resized dimensions
                w = dims.resize_w,
                swf = S.path + 'libraries/mediaplayer/player.swf',
                version = S.options.flashVersion,
                express = S.path + 'libraries/swfobject/expressInstall.swf',
                flashvars = U.apply({
                    file:       this.obj.content,
                    height:     h,
                    width:      w,
                    autostart:  (S.options.autoplayMovies ? 'true' : 'false'),
                    controlbar: (S.options.showMovieControls ? 'bottom' : 'none'),
                    backcolor:  '0x000000',
                    frontcolor: '0xCCCCCC',
                    lightcolor: '0x557722'
                }, S.options.flashVars),
                params = S.options.flashParams;

            swfobject.embedSWF(swf, id, w, h, version, express, flashvars, params);
        },

        /**
         * Removes this movie from the document.
         *
         * @return  void
         * @public
         */
        remove: function(){
            // call express install callback here in case express install is
            // active and user has not selected anything
            swfobject.expressInstallCallback();
            swfobject.removeSWF(this.id);
        }

    };

})(Shadowbox);

/**
 * The Shadowbox HTML player class.
 *
 * This file is part of Shadowbox.
 *
 * Shadowbox is an online media viewer application that supports all of the
 * web's most popular media publishing formats. Shadowbox is written entirely
 * in JavaScript and CSS and is highly customizable. Using Shadowbox, website
 * authors can showcase a wide assortment of media in all major browsers without
 * navigating users away from the linking page.
 *
 * You should have received a license with this distribution explaining the terms
 * under which Shadowbox may be used. If you did not, you may obtain a copy of the
 * license at http://shadowbox-js.com/LICENSE
 *
 * @author      Michael J. I. Jackson <michael@mjijackson.com>
 * @copyright   2007-2009 Michael J. I. Jackson
 */

(function(S){

    /**
     * Constructor. This class is used to display inline HTML.
     *
     * @param   Object      obj     The content object
     * @public
     */
    S.html = function(obj){
        this.obj = obj;

        // height defaults to 300, width defaults to 500
        this.height = obj.height ? parseInt(obj.height, 10) : 300;
        this.width = obj.width ? parseInt(obj.width, 10) : 500;
    }

    S.html.prototype = {

        /**
         * Appends this object to the document.
         *
         * @param   HTMLElement     body    The body element
         * @param   String          id      The content id
         * @param   Object          dims    The current Shadowbox dimensions
         * @return  void
         * @public
         */
        append: function(body, id, dims){
            this.id = id;

            var div = document.createElement('div');
            div.id = id;
            div.className = 'html'; // give special class to enable scrolling
            div.innerHTML = this.obj.content;

            body.appendChild(div);
        },

        /**
         * Removes this object from the document.
         *
         * @return  void
         * @public
         */
        remove: function(){
            var el = document.getElementById(this.id);
            if(el) S.lib.remove(el);
        }

    };

})(Shadowbox);

/**
 * The Shadowbox iframe player class.
 *
 * This file is part of Shadowbox.
 *
 * Shadowbox is an online media viewer application that supports all of the
 * web's most popular media publishing formats. Shadowbox is written entirely
 * in JavaScript and CSS and is highly customizable. Using Shadowbox, website
 * authors can showcase a wide assortment of media in all major browsers without
 * navigating users away from the linking page.
 *
 * You should have received a license with this distribution explaining the terms
 * under which Shadowbox may be used. If you did not, you may obtain a copy of the
 * license at http://shadowbox-js.com/LICENSE
 *
 * @author      Michael J. I. Jackson <michael@mjijackson.com>
 * @copyright   2007-2009 Michael J. I. Jackson
 */

(function(S){

    /**
     * Constructor. This class is used to display web pages in an HTML iframe.
     *
     * @param   Object      obj     The content object
     * @public
     */
    S.iframe = function(obj){
        this.obj = obj;

        // height/width default to full viewport height/width
        var so = document.getElementById('sb-overlay');
        this.height = obj.height ? parseInt(obj.height, 10) : so.offsetHeight;
        this.width = obj.width ? parseInt(obj.width, 10) : so.offsetWidth;
    }

    S.iframe.prototype = {

        /**
         * Appends this iframe to the document.
         *
         * @param   HTMLElement     body    The body element
         * @param   String          id      The content id
         * @param   Object          dims    The current Shadowbox dimensions
         * @return  void
         * @public
         */
        append: function(body, id, dims){
            this.id = id;

            var html = '<iframe id="' + id + '" name="' + id + '" height="100%" ' +
                'width="100%" frameborder="0" marginwidth="0" marginheight="0" ' +
                'scrolling="auto"';

            if(S.client.isIE){
                // prevent brief whiteout while loading iframe source
                html += ' allowtransparency="true"';

                // prevent "secure content" warning for https on IE6
                // see http://www.zachleat.com/web/2007/04/24/adventures-in-i-frame-shims-or-how-i-learned-to-love-the-bomb/
                if(S.client.isIE6)
                    html += ' src="javascript:false;document.write(\'\');"';
            }

            html += '></iframe>';

            // use innerHTML method of insertion here instead of appendChild
            // because IE renders frameborder otherwise
            body.innerHTML = html;

            /*
            var iframe = document.createElement('iframe'),
            attr = {
                id:             id,
                name:           id,
                height:         '100%',
                width:          '100%',
                frameborder:    '0',
                marginwidth:    '0',
                marginheight:   '0',
                scrolling:      'auto'
            };

            if(S.client.isIE){
                // prevent brief whiteout while loading iframe source
                attr.allowtransparency = 'true';

                if(S.client.isIE6){
                    // prevent "secure content" warning for https on IE6
                    // see http://www.zachleat.com/web/2007/04/24/adventures-in-i-frame-shims-or-how-i-learned-to-love-the-bomb/
                    attr.src = 'javascript:false;document.write("");';
                }
            }

            for(var a in attr){
                iframe.setAttribute(a, attr[a]);
            }

            body.appendChild(iframe);
            */
        },

        /**
         * Removes this iframe from the document.
         *
         * @return  void
         * @public
         */
        remove: function(){
            var el = document.getElementById(this.id);
            if(el){
                S.lib.remove(el);
                if(S.client.isGecko)
                    delete window.frames[this.id]; // needed for Firefox
            }
        },

        /**
         * An optional callback function to process after this content has been
         * loaded.
         *
         * @return  void
         * @public
         */
        onLoad: function(){
            var win = S.client.isIE
                ? document.getElementById(this.id).contentWindow
                : window.frames[this.id];
            win.location.href = this.obj.content; // set the iframe's location
        }

    };

})(Shadowbox);

/**
 * The Shadowbox image player class.
 *
 * This file is part of Shadowbox.
 *
 * Shadowbox is an online media viewer application that supports all of the
 * web's most popular media publishing formats. Shadowbox is written entirely
 * in JavaScript and CSS and is highly customizable. Using Shadowbox, website
 * authors can showcase a wide assortment of media in all major browsers without
 * navigating users away from the linking page.
 *
 * You should have received a license with this distribution explaining the terms
 * under which Shadowbox may be used. If you did not, you may obtain a copy of the
 * license at http://shadowbox-js.com/LICENSE
 *
 * @author      Michael J. I. Jackson <michael@mjijackson.com>
 * @copyright   2007-2009 Michael J. I. Jackson
 */

(function(S){

    var U = S.util,

    /**
     * Keeps track of 4 floating values (x, y, start_x, & start_y) that are used
     * in the drag calculations.
     *
     * @var     Object
     * @private
     */
    drag,

    /**
     * Holds the draggable element so we don't have to fetch it every time
     * the mouse moves.
     *
     * @var     HTMLElement
     * @private
     */
    draggable,

    /**
     * The id to use for the drag layer.
     *
     * @var     String
     * @private
     */
    drag_id = 'sb-drag-layer',

    /**
     * Resource used to preload images. It's class-level so that when a new
     * image is requested, the same resource can be reassigned, cancelling
     * the original's callback.
     *
     * @var     HTMLElement
     * @private
     */
    pre;

    /**
     * Resets the class drag variable.
     *
     * @return  void
     * @private
     */
    function resetDrag(){
        drag = {
            x:          0,
            y:          0,
            start_x:    null,
            start_y:    null
        };
    }

    /**
     * Toggles the drag function on and off.
     *
     * @param   Boolean     on      True to toggle on, false to toggle off
     * @param   Number      h       The height of the drag layer
     * @param   Number      w       The width of the drag layer
     * @return  void
     * @private
     */
    function toggleDrag(on, h, w){
        if(on){
            resetDrag();
            // add transparent drag layer to prevent browser dragging of actual image
            var s = [
                'position:absolute',
                'height:' + h + 'px',
                'width:' + w + 'px',
                'cursor:' + (S.client.isGecko ? '-moz-grab' : 'move'),
                'background-color:' + (S.client.isIE ? '#fff;filter:alpha(opacity=0)' : 'transparent')
            ].join(';');
            S.lib.append(S.skin.bodyEl(), '<div id="' + drag_id + '" style="' + s + '"></div>');
            S.lib.addEvent(U.get(drag_id), 'mousedown', listenDrag);
        }else{
            var d = U.get(drag_id);
            if(d){
                S.lib.removeEvent(d, 'mousedown', listenDrag);
                S.lib.remove(d);
            }
            draggable = null;
        }
    }

    /**
     * Sets up a drag listener on the document. Called when the mouse button is
     * pressed (mousedown).
     *
     * @param   mixed       e       The mousedown event
     * @return  void
     * @private
     */
    function listenDrag(e){
        // prevent browser dragging
        S.lib.preventDefault(e);

        var coords = S.lib.getPageXY(e);
        drag.start_x = coords[0];
        drag.start_y = coords[1];

        draggable = U.get(S.contentId());
        S.lib.addEvent(document, 'mousemove', positionDrag);
        S.lib.addEvent(document, 'mouseup', unlistenDrag);

        if(S.client.isGecko)
            U.get(drag_id).style.cursor = '-moz-grabbing';
    }

    /**
     * Removes the drag listener. Called when the mouse button is released
     * (mouseup).
     *
     * @return  void
     * @private
     */
    function unlistenDrag(){
        S.lib.removeEvent(document, 'mousemove', positionDrag);
        S.lib.removeEvent(document, 'mouseup', unlistenDrag); // clean up

        if(S.client.isGecko)
            U.get(drag_id).style.cursor = '-moz-grab';
    }

    /**
     * Positions an oversized image on drag.
     *
     * @param   mixed       e       The drag event
     * @return  void
     * @private
     */
    function positionDrag(e){
        var c = S.content,
            d = S.dimensions,
            coords = S.lib.getPageXY(e);

        var move_x = coords[0] - drag.start_x;
        drag.start_x += move_x;
        // x boundaries
        drag.x = Math.max(Math.min(0, drag.x + move_x), d.inner_w - c.width);
        draggable.style.left = drag.x + 'px';

        var move_y = coords[1] - drag.start_y;
        drag.start_y += move_y;
        // y boundaries
        drag.y = Math.max(Math.min(0, drag.y + move_y), d.inner_h - c.height);
        draggable.style.top = drag.y + 'px';
    }

    /**
     * Constructor.
     *
     * @param   Object      obj     The content object
     * @public
     */
    S.img = function(obj){
        this.obj = obj;

        // images are resizable
        this.resizable = true;

        // preload the image
        this.ready = false;
        var self = this;
        pre = new Image();
        pre.onload = function(){
            // height/width defaults to image height/width
            self.height = obj.height ? parseInt(obj.height, 10) : pre.height;
            self.width = obj.width ? parseInt(obj.width, 10) : pre.width;

            // ready to go
            self.ready = true;

            // clean up to prevent memory leak in IE
            pre.onload = '';
            pre = null;
        }
        pre.src = obj.content;
    }

    S.img.prototype = {

        /**
         * Appends this image to the document.
         *
         * @param   HTMLElement     body    The body element
         * @param   String          id      The content id
         * @param   Object          d       The current Shadowbox dimensions
         * @return  void
         * @public
         */
        append: function(body, id, d){
            this.id = id;

            var img = document.createElement('img');
            img.id = id;
            img.src = this.obj.content;
            img.style.position = 'absolute';

            // need to use setAttribute here for IE's sake
            img.setAttribute('height', d.resize_h)
            img.setAttribute('width', d.resize_w)

            body.appendChild(img);
        },

        /**
         * Removes this image from the document.
         *
         * @return  void
         * @public
         */
        remove: function(){
            var el = U.get(this.id);
            if(el) S.lib.remove(el);

            // disable drag layer
            toggleDrag(false);

            // prevent old image requests from loading
            if(pre){
                pre.onload = '';
                pre = null;
            }
        },

        /**
         * An optional callback function to process after this content has been
         * loaded.
         *
         * @return  void
         * @public
         */
        onLoad: function(){
            var d = S.dimensions;

            // listen for drag, in the case of oversized images, the "resized"
            // height/width will actually be the original image height/width
            if(d.oversized && S.options.handleOversize == 'drag')
                toggleDrag(true, d.resize_h, d.resize_w);
        },

        /**
         * Called when the window is resized.
         *
         * @return  void
         * @public
         */
        onWindowResize: function(){
            // fix draggable positioning if enlarging viewport
            if(draggable){
                var c = S.content,
                    d = S.dimensions,
                    t = parseInt(S.lib.getStyle(draggable, 'top')),
                    l = parseInt(S.lib.getStyle(draggable, 'left'));

                if(t + c.height < d.inner_h)
                    draggable.style.top = d.inner_h - c.height + 'px';
                if(l + c.width < d.inner_w)
                    draggable.style.left = d.inner_w - c.width + 'px';
            }
        }

    };

})(Shadowbox);

/**
 * The Shadowbox QuickTime player class.
 *
 * This file is part of Shadowbox.
 *
 * Shadowbox is an online media viewer application that supports all of the
 * web's most popular media publishing formats. Shadowbox is written entirely
 * in JavaScript and CSS and is highly customizable. Using Shadowbox, website
 * authors can showcase a wide assortment of media in all major browsers without
 * navigating users away from the linking page.
 *
 * You should have received a license with this distribution explaining the terms
 * under which Shadowbox may be used. If you did not, you may obtain a copy of the
 * license at http://shadowbox-js.com/LICENSE
 *
 * @author      Michael J. I. Jackson <michael@mjijackson.com>
 * @copyright   2007-2009 Michael J. I. Jackson
 */

(function(S){

    var controller_height = 16; // height of QuickTime controller

    /**
     * Constructor. This class is used to display QuickTime movies.
     *
     * @param   Object      obj     The content object
     * @public
     */
    S.qt = function(obj){
        this.obj = obj;

        // height/width default to 300 pixels
        this.height = obj.height ? parseInt(obj.height, 10) : 300;
        if(S.options.showMovieControls == true)
            this.height += controller_height;
        this.width = obj.width ? parseInt(obj.width, 10) : 300;
    }

    S.qt.prototype = {

        /**
         * Appends this movie to the document.
         *
         * @param   HTMLElement     body    The body element
         * @param   String          id      The content id
         * @param   Object          dims    The current Shadowbox dimensions
         * @return  void
         * @public
         */
        append: function(body, id, d){
            this.id = id;

            var opt = S.options,
            autoplay = String(opt.autoplayMovies),
            controls = String(opt.showMovieControls);

            var html = '<object',
            movie = {
                id:         id,
                name:       id,
                height:     this.height, // height includes controller
                width:      this.width,
                kioskmode:  'true'
            };

            if(S.client.isIE){
                movie.classid = 'clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B';
                movie.codebase = 'http://www.apple.com/qtactivex/qtplugin.cab#version=6,0,2,0';
            }else{
                movie.type = 'video/quicktime';
                movie.data = this.obj.content;
            }

            for(var m in movie)
                html += ' ' + m + '="' + movie[m] + '"';
            html += '>';

            var params = {
                src:        this.obj.content,
                scale:      'aspect',
                controller: controls,
                autoplay:   autoplay
            };

            for(var p in params)
                html += '<param name="' + p + '" value="' + params[p] + '">';
            html += '</object>';

            body.innerHTML = html;
        },

        /**
         * Removes this movie from the document.
         *
         * @return  void
         * @public
         */
        remove: function(){
            var id = this.id;

            try{
                document[id].Stop(); // stop QT video stream
            }catch(e){}

            var el = document.getElementById(id);
            if(el) S.lib.remove(el);
        }

    };

})(Shadowbox);

/**
 * The Shadowbox SWF movie player class.
 *
 * This file is part of Shadowbox.
 *
 * Shadowbox is an online media viewer application that supports all of the
 * web's most popular media publishing formats. Shadowbox is written entirely
 * in JavaScript and CSS and is highly customizable. Using Shadowbox, website
 * authors can showcase a wide assortment of media in all major browsers without
 * navigating users away from the linking page.
 *
 * You should have received a license with this distribution explaining the terms
 * under which Shadowbox may be used. If you did not, you may obtain a copy of the
 * license at http://shadowbox-js.com/LICENSE
 *
 * @author      Michael J. I. Jackson <michael@mjijackson.com>
 * @copyright   2007-2009 Michael J. I. Jackson
 */

(function(S){

    var U = S.util;

    /**
     * Constructor. This class is used to display SWF movies.
     *
     * @param   Object      obj     The content object
     * @public
     */
    S.swf = function(obj){
        this.obj = obj;

        // SWF's are resizable
        this.resizable = true;

        // height/width default to 300 pixels
        this.height = obj.height ? parseInt(obj.height, 10) : 300;
        this.width = obj.width ? parseInt(obj.width, 10) : 300;
    }

    S.swf.prototype = {

        /**
         * Appends this swf to the document.
         *
         * @param   HTMLElement     body    The body element
         * @param   String          id      The content id
         * @param   Object          dims    The current Shadowbox dimensions
         * @return  void
         * @public
         */
        append: function(body, id, dims){
            this.id = id;

            // append temporary content element to replace
            var tmp = document.createElement('div');
            tmp.id = id;
            body.appendChild(tmp);

            var h = dims.resize_h, // use resized dimensions
                w = dims.resize_w,
                swf = this.obj.content,
                version = S.options.flashVersion,
                express = S.path + 'libraries/swfobject/expressInstall.swf',
                flashvars = S.options.flashVars,
                params = S.options.flashParams;

            swfobject.embedSWF(swf, id, w, h, version, express, flashvars, params);
        },

        /**
         * Removes this swf from the document.
         *
         * @return  void
         * @public
         */
        remove: function(){
            // call express install callback here in case express install is
            // active and user has not selected anything
            swfobject.expressInstallCallback();
            swfobject.removeSWF(this.id);
        }

    };

})(Shadowbox);

/**
 * The Shadowbox Windows Media player class.
 *
 * This file is part of Shadowbox.
 *
 * Shadowbox is an online media viewer application that supports all of the
 * web's most popular media publishing formats. Shadowbox is written entirely
 * in JavaScript and CSS and is highly customizable. Using Shadowbox, website
 * authors can showcase a wide assortment of media in all major browsers without
 * navigating users away from the linking page.
 *
 * You should have received a license with this distribution explaining the terms
 * under which Shadowbox may be used. If you did not, you may obtain a copy of the
 * license at http://shadowbox-js.com/LICENSE
 *
 * @author      Michael J. I. Jackson <michael@mjijackson.com>
 * @copyright   2007-2009 Michael J. I. Jackson
 */

(function(S){

    var controller_height = (S.client.isIE ? 70 : 45); // height of WMP controller

    /**
     * Constructor. This class is used to display Windows Media Player movies.
     *
     * @param   Object      obj     The content object
     * @public
     */
    S.wmp = function(obj){
        this.obj = obj;

        // height/width default to 300 pixels
        this.height = obj.height ? parseInt(obj.height, 10) : 300;
        if(S.options.showMovieControls)
            this.height += controller_height;
        this.width = obj.width ? parseInt(obj.width, 10) : 300;
    }

    S.wmp.prototype = {

        /**
         * Appends this movie to the document.
         *
         * @param   HTMLElement     body    The body element
         * @param   String          id      The content id
         * @param   Object          dims    The current Shadowbox dimensions
         * @return  void
         * @public
         */
        append: function(body, id, dims){
            this.id = id;

            var opt = S.options,
                autoplay = opt.autoplayMovies ? 1 : 0;

            var movie = '<object id="' + id +
                '" name="' + id +
                '" height="' + this.height +
                '" width="' + this.width + '"',
                params = {autostart: opt.autoplayMovies ? 1 : 0};

            if(S.client.isIE){
                // movie += ' type="application/x-oleobject"';
                movie += ' classid="clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6"';
                params.url = this.obj.content;
                params.uimode = opt.showMovieControls ? 'full' : 'none';
            }else{
                movie += ' type="video/x-ms-wmv"';
                movie += ' data="' + this.obj.content + '"'
                params.showcontrols = opt.showMovieControls ? 1 : 0;
            }

            movie += '>';

            for(var p in params)
                movie += '<param name="' + p + '" value="' + params[p] + '">';

            movie += '</object>'

            body.innerHTML = movie;
        },

        /**
         * Removes this movie from the document.
         *
         * @return  void
         * @public
         */
        remove: function(){
            var id = this.id;

            if(S.client.isIE){
                try{
                    window[id].controls.stop(); // stop the movie
                    window[id].URL = 'non-existent.wmv'; // force player refresh
                    window[id] = function(){}; // remove from window object
                }catch(e){}
            }

            var el = document.getElementById(id);
            if(el){
                setTimeout(function(){ // using setTimeout prevents browser crashes with WMP
                    S.lib.remove(el);
                }, 10);
            }
        }

    };

})(Shadowbox);

/*!
 * Sizzle CSS Selector Engine - v1.0
 *  Copyright 2009, The Dojo Foundation
 *  Released under the MIT, BSD, and GPL Licenses.
 *  More information: http://sizzlejs.com/
 */
(function(){

var chunker = /((?:\((?:\([^()]+\)|[^()]+)+\)|\[(?:\[[^[\]]*\]|['"][^'"]*['"]|[^[\]'"]+)+\]|\\.|[^ >+~,(\[\\]+)+|[>+~])(\s*,\s*)?/g,
	done = 0,
	toString = Object.prototype.toString,
	hasDuplicate = false;

var Sizzle = function(selector, context, results, seed) {
	results = results || [];
	var origContext = context = context || document;

	if ( context.nodeType !== 1 && context.nodeType !== 9 ) {
		return [];
	}
	
	if ( !selector || typeof selector !== "string" ) {
		return results;
	}

	var parts = [], m, set, checkSet, check, mode, extra, prune = true, contextXML = isXML(context);
	
	// Reset the position of the chunker regexp (start from head)
	chunker.lastIndex = 0;
	
	while ( (m = chunker.exec(selector)) !== null ) {
		parts.push( m[1] );
		
		if ( m[2] ) {
			extra = RegExp.rightContext;
			break;
		}
	}

	if ( parts.length > 1 && origPOS.exec( selector ) ) {
		if ( parts.length === 2 && Expr.relative[ parts[0] ] ) {
			set = posProcess( parts[0] + parts[1], context );
		} else {
			set = Expr.relative[ parts[0] ] ?
				[ context ] :
				Sizzle( parts.shift(), context );

			while ( parts.length ) {
				selector = parts.shift();

				if ( Expr.relative[ selector ] )
					selector += parts.shift();

				set = posProcess( selector, set );
			}
		}
	} else {
		// Take a shortcut and set the context if the root selector is an ID
		// (but not if it'll be faster if the inner selector is an ID)
		if ( !seed && parts.length > 1 && context.nodeType === 9 && !contextXML &&
				Expr.match.ID.test(parts[0]) && !Expr.match.ID.test(parts[parts.length - 1]) ) {
			var ret = Sizzle.find( parts.shift(), context, contextXML );
			context = ret.expr ? Sizzle.filter( ret.expr, ret.set )[0] : ret.set[0];
		}

		if ( context ) {
			var ret = seed ?
				{ expr: parts.pop(), set: makeArray(seed) } :
				Sizzle.find( parts.pop(), parts.length === 1 && (parts[0] === "~" || parts[0] === "+") && context.parentNode ? context.parentNode : context, contextXML );
			set = ret.expr ? Sizzle.filter( ret.expr, ret.set ) : ret.set;

			if ( parts.length > 0 ) {
				checkSet = makeArray(set);
			} else {
				prune = false;
			}

			while ( parts.length ) {
				var cur = parts.pop(), pop = cur;

				if ( !Expr.relative[ cur ] ) {
					cur = "";
				} else {
					pop = parts.pop();
				}

				if ( pop == null ) {
					pop = context;
				}

				Expr.relative[ cur ]( checkSet, pop, contextXML );
			}
		} else {
			checkSet = parts = [];
		}
	}

	if ( !checkSet ) {
		checkSet = set;
	}

	if ( !checkSet ) {
		throw "Syntax error, unrecognized expression: " + (cur || selector);
	}

	if ( toString.call(checkSet) === "[object Array]" ) {
		if ( !prune ) {
			results.push.apply( results, checkSet );
		} else if ( context && context.nodeType === 1 ) {
			for ( var i = 0; checkSet[i] != null; i++ ) {
				if ( checkSet[i] && (checkSet[i] === true || checkSet[i].nodeType === 1 && contains(context, checkSet[i])) ) {
					results.push( set[i] );
				}
			}
		} else {
			for ( var i = 0; checkSet[i] != null; i++ ) {
				if ( checkSet[i] && checkSet[i].nodeType === 1 ) {
					results.push( set[i] );
				}
			}
		}
	} else {
		makeArray( checkSet, results );
	}

	if ( extra ) {
		Sizzle( extra, origContext, results, seed );
		Sizzle.uniqueSort( results );
	}

	return results;
};

Sizzle.uniqueSort = function(results){
	if ( sortOrder ) {
		hasDuplicate = false;
		results.sort(sortOrder);

		if ( hasDuplicate ) {
			for ( var i = 1; i < results.length; i++ ) {
				if ( results[i] === results[i-1] ) {
					results.splice(i--, 1);
				}
			}
		}
	}
};

Sizzle.matches = function(expr, set){
	return Sizzle(expr, null, null, set);
};

Sizzle.find = function(expr, context, isXML){
	var set, match;

	if ( !expr ) {
		return [];
	}

	for ( var i = 0, l = Expr.order.length; i < l; i++ ) {
		var type = Expr.order[i], match;
		
		if ( (match = Expr.match[ type ].exec( expr )) ) {
			var left = RegExp.leftContext;

			if ( left.substr( left.length - 1 ) !== "\\" ) {
				match[1] = (match[1] || "").replace(/\\/g, "");
				set = Expr.find[ type ]( match, context, isXML );
				if ( set != null ) {
					expr = expr.replace( Expr.match[ type ], "" );
					break;
				}
			}
		}
	}

	if ( !set ) {
		set = context.getElementsByTagName("*");
	}

	return {set: set, expr: expr};
};

Sizzle.filter = function(expr, set, inplace, not){
	var old = expr, result = [], curLoop = set, match, anyFound,
		isXMLFilter = set && set[0] && isXML(set[0]);

	while ( expr && set.length ) {
		for ( var type in Expr.filter ) {
			if ( (match = Expr.match[ type ].exec( expr )) != null ) {
				var filter = Expr.filter[ type ], found, item;
				anyFound = false;

				if ( curLoop == result ) {
					result = [];
				}

				if ( Expr.preFilter[ type ] ) {
					match = Expr.preFilter[ type ]( match, curLoop, inplace, result, not, isXMLFilter );

					if ( !match ) {
						anyFound = found = true;
					} else if ( match === true ) {
						continue;
					}
				}

				if ( match ) {
					for ( var i = 0; (item = curLoop[i]) != null; i++ ) {
						if ( item ) {
							found = filter( item, match, i, curLoop );
							var pass = not ^ !!found;

							if ( inplace && found != null ) {
								if ( pass ) {
									anyFound = true;
								} else {
									curLoop[i] = false;
								}
							} else if ( pass ) {
								result.push( item );
								anyFound = true;
							}
						}
					}
				}

				if ( found !== undefined ) {
					if ( !inplace ) {
						curLoop = result;
					}

					expr = expr.replace( Expr.match[ type ], "" );

					if ( !anyFound ) {
						return [];
					}

					break;
				}
			}
		}

		// Improper expression
		if ( expr == old ) {
			if ( anyFound == null ) {
				throw "Syntax error, unrecognized expression: " + expr;
			} else {
				break;
			}
		}

		old = expr;
	}

	return curLoop;
};

var Expr = Sizzle.selectors = {
	order: [ "ID", "NAME", "TAG" ],
	match: {
		ID: /#((?:[\w\u00c0-\uFFFF_-]|\\.)+)/,
		CLASS: /\.((?:[\w\u00c0-\uFFFF_-]|\\.)+)/,
		NAME: /\[name=['"]*((?:[\w\u00c0-\uFFFF_-]|\\.)+)['"]*\]/,
		ATTR: /\[\s*((?:[\w\u00c0-\uFFFF_-]|\\.)+)\s*(?:(\S?=)\s*(['"]*)(.*?)\3|)\s*\]/,
		TAG: /^((?:[\w\u00c0-\uFFFF\*_-]|\\.)+)/,
		CHILD: /:(only|nth|last|first)-child(?:\((even|odd|[\dn+-]*)\))?/,
		POS: /:(nth|eq|gt|lt|first|last|even|odd)(?:\((\d*)\))?(?=[^-]|$)/,
		PSEUDO: /:((?:[\w\u00c0-\uFFFF_-]|\\.)+)(?:\((['"]*)((?:\([^\)]+\)|[^\2\(\)]*)+)\2\))?/
	},
	attrMap: {
		"class": "className",
		"for": "htmlFor"
	},
	attrHandle: {
		href: function(elem){
			return elem.getAttribute("href");
		}
	},
	relative: {
		"+": function(checkSet, part, isXML){
			var isPartStr = typeof part === "string",
				isTag = isPartStr && !/\W/.test(part),
				isPartStrNotTag = isPartStr && !isTag;

			if ( isTag && !isXML ) {
				part = part.toUpperCase();
			}

			for ( var i = 0, l = checkSet.length, elem; i < l; i++ ) {
				if ( (elem = checkSet[i]) ) {
					while ( (elem = elem.previousSibling) && elem.nodeType !== 1 ) {}

					checkSet[i] = isPartStrNotTag || elem && elem.nodeName === part ?
						elem || false :
						elem === part;
				}
			}

			if ( isPartStrNotTag ) {
				Sizzle.filter( part, checkSet, true );
			}
		},
		">": function(checkSet, part, isXML){
			var isPartStr = typeof part === "string";

			if ( isPartStr && !/\W/.test(part) ) {
				part = isXML ? part : part.toUpperCase();

				for ( var i = 0, l = checkSet.length; i < l; i++ ) {
					var elem = checkSet[i];
					if ( elem ) {
						var parent = elem.parentNode;
						checkSet[i] = parent.nodeName === part ? parent : false;
					}
				}
			} else {
				for ( var i = 0, l = checkSet.length; i < l; i++ ) {
					var elem = checkSet[i];
					if ( elem ) {
						checkSet[i] = isPartStr ?
							elem.parentNode :
							elem.parentNode === part;
					}
				}

				if ( isPartStr ) {
					Sizzle.filter( part, checkSet, true );
				}
			}
		},
		"": function(checkSet, part, isXML){
			var doneName = done++, checkFn = dirCheck;

			if ( !part.match(/\W/) ) {
				var nodeCheck = part = isXML ? part : part.toUpperCase();
				checkFn = dirNodeCheck;
			}

			checkFn("parentNode", part, doneName, checkSet, nodeCheck, isXML);
		},
		"~": function(checkSet, part, isXML){
			var doneName = done++, checkFn = dirCheck;

			if ( typeof part === "string" && !part.match(/\W/) ) {
				var nodeCheck = part = isXML ? part : part.toUpperCase();
				checkFn = dirNodeCheck;
			}

			checkFn("previousSibling", part, doneName, checkSet, nodeCheck, isXML);
		}
	},
	find: {
		ID: function(match, context, isXML){
			if ( typeof context.getElementById !== "undefined" && !isXML ) {
				var m = context.getElementById(match[1]);
				return m ? [m] : [];
			}
		},
		NAME: function(match, context, isXML){
			if ( typeof context.getElementsByName !== "undefined" ) {
				var ret = [], results = context.getElementsByName(match[1]);

				for ( var i = 0, l = results.length; i < l; i++ ) {
					if ( results[i].getAttribute("name") === match[1] ) {
						ret.push( results[i] );
					}
				}

				return ret.length === 0 ? null : ret;
			}
		},
		TAG: function(match, context){
			return context.getElementsByTagName(match[1]);
		}
	},
	preFilter: {
		CLASS: function(match, curLoop, inplace, result, not, isXML){
			match = " " + match[1].replace(/\\/g, "") + " ";

			if ( isXML ) {
				return match;
			}

			for ( var i = 0, elem; (elem = curLoop[i]) != null; i++ ) {
				if ( elem ) {
					if ( not ^ (elem.className && (" " + elem.className + " ").indexOf(match) >= 0) ) {
						if ( !inplace )
							result.push( elem );
					} else if ( inplace ) {
						curLoop[i] = false;
					}
				}
			}

			return false;
		},
		ID: function(match){
			return match[1].replace(/\\/g, "");
		},
		TAG: function(match, curLoop){
			for ( var i = 0; curLoop[i] === false; i++ ){}
			return curLoop[i] && isXML(curLoop[i]) ? match[1] : match[1].toUpperCase();
		},
		CHILD: function(match){
			if ( match[1] == "nth" ) {
				// parse equations like 'even', 'odd', '5', '2n', '3n+2', '4n-1', '-n+6'
				var test = /(-?)(\d*)n((?:\+|-)?\d*)/.exec(
					match[2] == "even" && "2n" || match[2] == "odd" && "2n+1" ||
					!/\D/.test( match[2] ) && "0n+" + match[2] || match[2]);

				// calculate the numbers (first)n+(last) including if they are negative
				match[2] = (test[1] + (test[2] || 1)) - 0;
				match[3] = test[3] - 0;
			}

			// TODO: Move to normal caching system
			match[0] = done++;

			return match;
		},
		ATTR: function(match, curLoop, inplace, result, not, isXML){
			var name = match[1].replace(/\\/g, "");
			
			if ( !isXML && Expr.attrMap[name] ) {
				match[1] = Expr.attrMap[name];
			}

			if ( match[2] === "~=" ) {
				match[4] = " " + match[4] + " ";
			}

			return match;
		},
		PSEUDO: function(match, curLoop, inplace, result, not){
			if ( match[1] === "not" ) {
				// If we're dealing with a complex expression, or a simple one
				if ( match[3].match(chunker).length > 1 || /^\w/.test(match[3]) ) {
					match[3] = Sizzle(match[3], null, null, curLoop);
				} else {
					var ret = Sizzle.filter(match[3], curLoop, inplace, true ^ not);
					if ( !inplace ) {
						result.push.apply( result, ret );
					}
					return false;
				}
			} else if ( Expr.match.POS.test( match[0] ) || Expr.match.CHILD.test( match[0] ) ) {
				return true;
			}
			
			return match;
		},
		POS: function(match){
			match.unshift( true );
			return match;
		}
	},
	filters: {
		enabled: function(elem){
			return elem.disabled === false && elem.type !== "hidden";
		},
		disabled: function(elem){
			return elem.disabled === true;
		},
		checked: function(elem){
			return elem.checked === true;
		},
		selected: function(elem){
			// Accessing this property makes selected-by-default
			// options in Safari work properly
			elem.parentNode.selectedIndex;
			return elem.selected === true;
		},
		parent: function(elem){
			return !!elem.firstChild;
		},
		empty: function(elem){
			return !elem.firstChild;
		},
		has: function(elem, i, match){
			return !!Sizzle( match[3], elem ).length;
		},
		header: function(elem){
			return /h\d/i.test( elem.nodeName );
		},
		text: function(elem){
			return "text" === elem.type;
		},
		radio: function(elem){
			return "radio" === elem.type;
		},
		checkbox: function(elem){
			return "checkbox" === elem.type;
		},
		file: function(elem){
			return "file" === elem.type;
		},
		password: function(elem){
			return "password" === elem.type;
		},
		submit: function(elem){
			return "submit" === elem.type;
		},
		image: function(elem){
			return "image" === elem.type;
		},
		reset: function(elem){
			return "reset" === elem.type;
		},
		button: function(elem){
			return "button" === elem.type || elem.nodeName.toUpperCase() === "BUTTON";
		},
		input: function(elem){
			return /input|select|textarea|button/i.test(elem.nodeName);
		}
	},
	setFilters: {
		first: function(elem, i){
			return i === 0;
		},
		last: function(elem, i, match, array){
			return i === array.length - 1;
		},
		even: function(elem, i){
			return i % 2 === 0;
		},
		odd: function(elem, i){
			return i % 2 === 1;
		},
		lt: function(elem, i, match){
			return i < match[3] - 0;
		},
		gt: function(elem, i, match){
			return i > match[3] - 0;
		},
		nth: function(elem, i, match){
			return match[3] - 0 == i;
		},
		eq: function(elem, i, match){
			return match[3] - 0 == i;
		}
	},
	filter: {
		PSEUDO: function(elem, match, i, array){
			var name = match[1], filter = Expr.filters[ name ];

			if ( filter ) {
				return filter( elem, i, match, array );
			} else if ( name === "contains" ) {
				return (elem.textContent || elem.innerText || "").indexOf(match[3]) >= 0;
			} else if ( name === "not" ) {
				var not = match[3];

				for ( var i = 0, l = not.length; i < l; i++ ) {
					if ( not[i] === elem ) {
						return false;
					}
				}

				return true;
			}
		},
		CHILD: function(elem, match){
			var type = match[1], node = elem;
			switch (type) {
				case 'only':
				case 'first':
					while (node = node.previousSibling)  {
						if ( node.nodeType === 1 ) return false;
					}
					if ( type == 'first') return true;
					node = elem;
				case 'last':
					while (node = node.nextSibling)  {
						if ( node.nodeType === 1 ) return false;
					}
					return true;
				case 'nth':
					var first = match[2], last = match[3];

					if ( first == 1 && last == 0 ) {
						return true;
					}
					
					var doneName = match[0],
						parent = elem.parentNode;
	
					if ( parent && (parent.sizcache !== doneName || !elem.nodeIndex) ) {
						var count = 0;
						for ( node = parent.firstChild; node; node = node.nextSibling ) {
							if ( node.nodeType === 1 ) {
								node.nodeIndex = ++count;
							}
						} 
						parent.sizcache = doneName;
					}
					
					var diff = elem.nodeIndex - last;
					if ( first == 0 ) {
						return diff == 0;
					} else {
						return ( diff % first == 0 && diff / first >= 0 );
					}
			}
		},
		ID: function(elem, match){
			return elem.nodeType === 1 && elem.getAttribute("id") === match;
		},
		TAG: function(elem, match){
			return (match === "*" && elem.nodeType === 1) || elem.nodeName === match;
		},
		CLASS: function(elem, match){
			return (" " + (elem.className || elem.getAttribute("class")) + " ")
				.indexOf( match ) > -1;
		},
		ATTR: function(elem, match){
			var name = match[1],
				result = Expr.attrHandle[ name ] ?
					Expr.attrHandle[ name ]( elem ) :
					elem[ name ] != null ?
						elem[ name ] :
						elem.getAttribute( name ),
				value = result + "",
				type = match[2],
				check = match[4];

			return result == null ?
				type === "!=" :
				type === "=" ?
				value === check :
				type === "*=" ?
				value.indexOf(check) >= 0 :
				type === "~=" ?
				(" " + value + " ").indexOf(check) >= 0 :
				!check ?
				value && result !== false :
				type === "!=" ?
				value != check :
				type === "^=" ?
				value.indexOf(check) === 0 :
				type === "$=" ?
				value.substr(value.length - check.length) === check :
				type === "|=" ?
				value === check || value.substr(0, check.length + 1) === check + "-" :
				false;
		},
		POS: function(elem, match, i, array){
			var name = match[2], filter = Expr.setFilters[ name ];

			if ( filter ) {
				return filter( elem, i, match, array );
			}
		}
	}
};

var origPOS = Expr.match.POS;

for ( var type in Expr.match ) {
	Expr.match[ type ] = new RegExp( Expr.match[ type ].source + /(?![^\[]*\])(?![^\(]*\))/.source );
}

var makeArray = function(array, results) {
	array = Array.prototype.slice.call( array );

	if ( results ) {
		results.push.apply( results, array );
		return results;
	}
	
	return array;
};

// Perform a simple check to determine if the browser is capable of
// converting a NodeList to an array using builtin methods.
try {
	Array.prototype.slice.call( document.documentElement.childNodes );

// Provide a fallback method if it does not work
} catch(e){
	makeArray = function(array, results) {
		var ret = results || [];

		if ( toString.call(array) === "[object Array]" ) {
			Array.prototype.push.apply( ret, array );
		} else {
			if ( typeof array.length === "number" ) {
				for ( var i = 0, l = array.length; i < l; i++ ) {
					ret.push( array[i] );
				}
			} else {
				for ( var i = 0; array[i]; i++ ) {
					ret.push( array[i] );
				}
			}
		}

		return ret;
	};
}

var sortOrder;

if ( document.documentElement.compareDocumentPosition ) {
	sortOrder = function( a, b ) {
		var ret = a.compareDocumentPosition(b) & 4 ? -1 : a === b ? 0 : 1;
		if ( ret === 0 ) {
			hasDuplicate = true;
		}
		return ret;
	};
} else if ( "sourceIndex" in document.documentElement ) {
	sortOrder = function( a, b ) {
		var ret = a.sourceIndex - b.sourceIndex;
		if ( ret === 0 ) {
			hasDuplicate = true;
		}
		return ret;
	};
} else if ( document.createRange ) {
	sortOrder = function( a, b ) {
		var aRange = a.ownerDocument.createRange(), bRange = b.ownerDocument.createRange();
		aRange.selectNode(a);
		aRange.collapse(true);
		bRange.selectNode(b);
		bRange.collapse(true);
		var ret = aRange.compareBoundaryPoints(Range.START_TO_END, bRange);
		if ( ret === 0 ) {
			hasDuplicate = true;
		}
		return ret;
	};
}

// Check to see if the browser returns elements by name when
// querying by getElementById (and provide a workaround)
(function(){
	// We're going to inject a fake input element with a specified name
	var form = document.createElement("div"),
		id = "script" + (new Date).getTime();
	form.innerHTML = "<a name='" + id + "'/>";

	// Inject it into the root element, check its status, and remove it quickly
	var root = document.documentElement;
	root.insertBefore( form, root.firstChild );

	// The workaround has to do additional checks after a getElementById
	// Which slows things down for other browsers (hence the branching)
	if ( !!document.getElementById( id ) ) {
		Expr.find.ID = function(match, context, isXML){
			if ( typeof context.getElementById !== "undefined" && !isXML ) {
				var m = context.getElementById(match[1]);
				return m ? m.id === match[1] || typeof m.getAttributeNode !== "undefined" && m.getAttributeNode("id").nodeValue === match[1] ? [m] : undefined : [];
			}
		};

		Expr.filter.ID = function(elem, match){
			var node = typeof elem.getAttributeNode !== "undefined" && elem.getAttributeNode("id");
			return elem.nodeType === 1 && node && node.nodeValue === match;
		};
	}

	root.removeChild( form );
})();

(function(){
	// Check to see if the browser returns only elements
	// when doing getElementsByTagName("*")

	// Create a fake element
	var div = document.createElement("div");
	div.appendChild( document.createComment("") );

	// Make sure no comments are found
	if ( div.getElementsByTagName("*").length > 0 ) {
		Expr.find.TAG = function(match, context){
			var results = context.getElementsByTagName(match[1]);

			// Filter out possible comments
			if ( match[1] === "*" ) {
				var tmp = [];

				for ( var i = 0; results[i]; i++ ) {
					if ( results[i].nodeType === 1 ) {
						tmp.push( results[i] );
					}
				}

				results = tmp;
			}

			return results;
		};
	}

	// Check to see if an attribute returns normalized href attributes
	div.innerHTML = "<a href='#'></a>";
	if ( div.firstChild && typeof div.firstChild.getAttribute !== "undefined" &&
			div.firstChild.getAttribute("href") !== "#" ) {
		Expr.attrHandle.href = function(elem){
			return elem.getAttribute("href", 2);
		};
	}
})();

if ( document.querySelectorAll ) (function(){
	var oldSizzle = Sizzle, div = document.createElement("div");
	div.innerHTML = "<p class='TEST'></p>";

	// Safari can't handle uppercase or unicode characters when
	// in quirks mode.
	if ( div.querySelectorAll && div.querySelectorAll(".TEST").length === 0 ) {
		return;
	}
	
	Sizzle = function(query, context, extra, seed){
		context = context || document;

		// Only use querySelectorAll on non-XML documents
		// (ID selectors don't work in non-HTML documents)
		if ( !seed && context.nodeType === 9 && !isXML(context) ) {
			try {
				return makeArray( context.querySelectorAll(query), extra );
			} catch(e){}
		}
		
		return oldSizzle(query, context, extra, seed);
	};

	for ( var prop in oldSizzle ) {
		Sizzle[ prop ] = oldSizzle[ prop ];
	}
})();

if ( document.getElementsByClassName && document.documentElement.getElementsByClassName ) (function(){
	var div = document.createElement("div");
	div.innerHTML = "<div class='test e'></div><div class='test'></div>";

	// Opera can't find a second classname (in 9.6)
	if ( div.getElementsByClassName("e").length === 0 )
		return;

	// Safari caches class attributes, doesn't catch changes (in 3.2)
	div.lastChild.className = "e";

	if ( div.getElementsByClassName("e").length === 1 )
		return;

	Expr.order.splice(1, 0, "CLASS");
	Expr.find.CLASS = function(match, context, isXML) {
		if ( typeof context.getElementsByClassName !== "undefined" && !isXML ) {
			return context.getElementsByClassName(match[1]);
		}
	};
})();

function dirNodeCheck( dir, cur, doneName, checkSet, nodeCheck, isXML ) {
	var sibDir = dir == "previousSibling" && !isXML;
	for ( var i = 0, l = checkSet.length; i < l; i++ ) {
		var elem = checkSet[i];
		if ( elem ) {
			if ( sibDir && elem.nodeType === 1 ){
				elem.sizcache = doneName;
				elem.sizset = i;
			}
			elem = elem[dir];
			var match = false;

			while ( elem ) {
				if ( elem.sizcache === doneName ) {
					match = checkSet[elem.sizset];
					break;
				}

				if ( elem.nodeType === 1 && !isXML ){
					elem.sizcache = doneName;
					elem.sizset = i;
				}

				if ( elem.nodeName === cur ) {
					match = elem;
					break;
				}

				elem = elem[dir];
			}

			checkSet[i] = match;
		}
	}
}

function dirCheck( dir, cur, doneName, checkSet, nodeCheck, isXML ) {
	var sibDir = dir == "previousSibling" && !isXML;
	for ( var i = 0, l = checkSet.length; i < l; i++ ) {
		var elem = checkSet[i];
		if ( elem ) {
			if ( sibDir && elem.nodeType === 1 ) {
				elem.sizcache = doneName;
				elem.sizset = i;
			}
			elem = elem[dir];
			var match = false;

			while ( elem ) {
				if ( elem.sizcache === doneName ) {
					match = checkSet[elem.sizset];
					break;
				}

				if ( elem.nodeType === 1 ) {
					if ( !isXML ) {
						elem.sizcache = doneName;
						elem.sizset = i;
					}
					if ( typeof cur !== "string" ) {
						if ( elem === cur ) {
							match = true;
							break;
						}

					} else if ( Sizzle.filter( cur, [elem] ).length > 0 ) {
						match = elem;
						break;
					}
				}

				elem = elem[dir];
			}

			checkSet[i] = match;
		}
	}
}

var contains = document.compareDocumentPosition ?  function(a, b){
	return a.compareDocumentPosition(b) & 16;
} : function(a, b){
	return a !== b && (a.contains ? a.contains(b) : true);
};

var isXML = function(elem){
	return elem.nodeType === 9 && elem.documentElement.nodeName !== "HTML" ||
		!!elem.ownerDocument && elem.ownerDocument.documentElement.nodeName !== "HTML";
};

var posProcess = function(selector, context){
	var tmpSet = [], later = "", match,
		root = context.nodeType ? [context] : context;

	// Position selectors must be done after the filter
	// And so must :not(positional) so we move all PSEUDOs to the end
	while ( (match = Expr.match.PSEUDO.exec( selector )) ) {
		later += match[0];
		selector = selector.replace( Expr.match.PSEUDO, "" );
	}

	selector = Expr.relative[selector] ? selector + "*" : selector;

	for ( var i = 0, l = root.length; i < l; i++ ) {
		Sizzle( selector, root[i], tmpSet );
	}

	return Sizzle.filter( later, tmpSet );
};

// EXPOSE

window.Sizzle = Sizzle;

})();

/*! SWFObject v2.1 <http://code.google.com/p/swfobject/>
	Copyright (c) 2007-2008 Geoff Stearns, Michael Williams, and Bobby van der Sluis
	This software is released under the MIT License <http://www.opensource.org/licenses/mit-license.php>
*/

var swfobject = function() {
	
	var UNDEF = "undefined",
		OBJECT = "object",
		SHOCKWAVE_FLASH = "Shockwave Flash",
		SHOCKWAVE_FLASH_AX = "ShockwaveFlash.ShockwaveFlash",
		FLASH_MIME_TYPE = "application/x-shockwave-flash",
		EXPRESS_INSTALL_ID = "SWFObjectExprInst",
		
		win = window,
		doc = document,
		nav = navigator,
		
		domLoadFnArr = [],
		regObjArr = [],
		objIdArr = [],
		listenersArr = [],
		script,
		timer = null,
		storedAltContent = null,
		storedAltContentId = null,
		isDomLoaded = false,
		isExpressInstallActive = false;
	
	/* Centralized function for browser feature detection
		- Proprietary feature detection (conditional compiling) is used to detect Internet Explorer's features
		- User agent string detection is only used when no alternative is possible
		- Is executed directly for optimal performance
	*/	
	var ua = function() {
		var w3cdom = typeof doc.getElementById != UNDEF && typeof doc.getElementsByTagName != UNDEF && typeof doc.createElement != UNDEF,
			playerVersion = [0,0,0],
			d = null;
		if (typeof nav.plugins != UNDEF && typeof nav.plugins[SHOCKWAVE_FLASH] == OBJECT) {
			d = nav.plugins[SHOCKWAVE_FLASH].description;
			if (d && !(typeof nav.mimeTypes != UNDEF && nav.mimeTypes[FLASH_MIME_TYPE] && !nav.mimeTypes[FLASH_MIME_TYPE].enabledPlugin)) { // navigator.mimeTypes["application/x-shockwave-flash"].enabledPlugin indicates whether plug-ins are enabled or disabled in Safari 3+
				d = d.replace(/^.*\s+(\S+\s+\S+$)/, "$1");
				playerVersion[0] = parseInt(d.replace(/^(.*)\..*$/, "$1"), 10);
				playerVersion[1] = parseInt(d.replace(/^.*\.(.*)\s.*$/, "$1"), 10);
				playerVersion[2] = /r/.test(d) ? parseInt(d.replace(/^.*r(.*)$/, "$1"), 10) : 0;
			}
		}
		else if (typeof win.ActiveXObject != UNDEF) {
			var a = null, fp6Crash = false;
			try {
				a = new ActiveXObject(SHOCKWAVE_FLASH_AX + ".7");
			}
			catch(e) {
				try { 
					a = new ActiveXObject(SHOCKWAVE_FLASH_AX + ".6");
					playerVersion = [6,0,21];
					a.AllowScriptAccess = "always";	 // Introduced in fp6.0.47
				}
				catch(e) {
					if (playerVersion[0] == 6) {
						fp6Crash = true;
					}
				}
				if (!fp6Crash) {
					try {
						a = new ActiveXObject(SHOCKWAVE_FLASH_AX);
					}
					catch(e) {}
				}
			}
			if (!fp6Crash && a) { // a will return null when ActiveX is disabled
				try {
					d = a.GetVariable("$version");	// Will crash fp6.0.21/23/29
					if (d) {
						d = d.split(" ")[1].split(",");
						playerVersion = [parseInt(d[0], 10), parseInt(d[1], 10), parseInt(d[2], 10)];
					}
				}
				catch(e) {}
			}
		}
		var u = nav.userAgent.toLowerCase(),
			p = nav.platform.toLowerCase(),
			webkit = /webkit/.test(u) ? parseFloat(u.replace(/^.*webkit\/(\d+(\.\d+)?).*$/, "$1")) : false, // returns either the webkit version or false if not webkit
			ie = false,
			windows = p ? /win/.test(p) : /win/.test(u),
			mac = p ? /mac/.test(p) : /mac/.test(u);
		/*@cc_on
			ie = true;
			@if (@_win32)
				windows = true;
			@elif (@_mac)
				mac = true;
			@end
		@*/
		return { w3cdom:w3cdom, pv:playerVersion, webkit:webkit, ie:ie, win:windows, mac:mac };
	}();

	/* Cross-browser onDomLoad
		- Based on Dean Edwards' solution: http://dean.edwards.name/weblog/2006/06/again/
		- Will fire an event as soon as the DOM of a page is loaded (supported by Gecko based browsers - like Firefox -, IE, Opera9+, Safari)
	*/ 
	var onDomLoad = function() {
		if (!ua.w3cdom) {
			return;
		}
		addDomLoadEvent(main);
		if (ua.ie && ua.win) {
			try {	 // Avoid a possible Operation Aborted error
				doc.write("<scr" + "ipt id=__ie_ondomload defer=true src=//:></scr" + "ipt>"); // String is split into pieces to avoid Norton AV to add code that can cause errors 
				script = getElementById("__ie_ondomload");
				if (script) {
					addListener(script, "onreadystatechange", checkReadyState);
				}
			}
			catch(e) {}
		}
		if (ua.webkit && typeof doc.readyState != UNDEF) {
			timer = setInterval(function() { if (/loaded|complete/.test(doc.readyState)) { callDomLoadFunctions(); }}, 10);
		}
		if (typeof doc.addEventListener != UNDEF) {
			doc.addEventListener("DOMContentLoaded", callDomLoadFunctions, null);
		}
		addLoadEvent(callDomLoadFunctions);
	}();
	
	function checkReadyState() {
		if (script.readyState == "complete") {
			script.parentNode.removeChild(script);
			callDomLoadFunctions();
		}
	}
	
	function callDomLoadFunctions() {
		if (isDomLoaded) {
			return;
		}
		if (ua.ie && ua.win) { // Test if we can really add elements to the DOM; we don't want to fire it too early
			var s = createElement("span");
			try { // Avoid a possible Operation Aborted error
				var t = doc.getElementsByTagName("body")[0].appendChild(s);
				t.parentNode.removeChild(t);
			}
			catch (e) {
				return;
			}
		}
		isDomLoaded = true;
		if (timer) {
			clearInterval(timer);
			timer = null;
		}
		var dl = domLoadFnArr.length;
		for (var i = 0; i < dl; i++) {
			domLoadFnArr[i]();
		}
	}
	
	function addDomLoadEvent(fn) {
		if (isDomLoaded) {
			fn();
		}
		else { 
			domLoadFnArr[domLoadFnArr.length] = fn; // Array.push() is only available in IE5.5+
		}
	}
	
	/* Cross-browser onload
		- Based on James Edwards' solution: http://brothercake.com/site/resources/scripts/onload/
		- Will fire an event as soon as a web page including all of its assets are loaded 
	 */
	function addLoadEvent(fn) {
		if (typeof win.addEventListener != UNDEF) {
			win.addEventListener("load", fn, false);
		}
		else if (typeof doc.addEventListener != UNDEF) {
			doc.addEventListener("load", fn, false);
		}
		else if (typeof win.attachEvent != UNDEF) {
			addListener(win, "onload", fn);
		}
		else if (typeof win.onload == "function") {
			var fnOld = win.onload;
			win.onload = function() {
				fnOld();
				fn();
			};
		}
		else {
			win.onload = fn;
		}
	}
	
	/* Main function
		- Will preferably execute onDomLoad, otherwise onload (as a fallback)
	*/
	function main() { // Static publishing only
		var rl = regObjArr.length;
		for (var i = 0; i < rl; i++) { // For each registered object element
			var id = regObjArr[i].id;
			if (ua.pv[0] > 0) {
				var obj = getElementById(id);
				if (obj) {
					regObjArr[i].width = obj.getAttribute("width") ? obj.getAttribute("width") : "0";
					regObjArr[i].height = obj.getAttribute("height") ? obj.getAttribute("height") : "0";
					if (hasPlayerVersion(regObjArr[i].swfVersion)) { // Flash plug-in version >= Flash content version: Houston, we have a match!
						if (ua.webkit && ua.webkit < 312) { // Older webkit engines ignore the object element's nested param elements
							fixParams(obj);
						}
						setVisibility(id, true);
					}
					else if (regObjArr[i].expressInstall && !isExpressInstallActive && hasPlayerVersion("6.0.65") && (ua.win || ua.mac)) { // Show the Adobe Express Install dialog if set by the web page author and if supported (fp6.0.65+ on Win/Mac OS only)
						showExpressInstall(regObjArr[i]);
					}
					else { // Flash plug-in and Flash content version mismatch: display alternative content instead of Flash content
						displayAltContent(obj);
					}
				}
			}
			else {	// If no fp is installed, we let the object element do its job (show alternative content)
				setVisibility(id, true);
			}
		}
	}
	
	/* Fix nested param elements, which are ignored by older webkit engines
		- This includes Safari up to and including version 1.2.2 on Mac OS 10.3
		- Fall back to the proprietary embed element
	*/
	function fixParams(obj) {
		var nestedObj = obj.getElementsByTagName(OBJECT)[0];
		if (nestedObj) {
			var e = createElement("embed"), a = nestedObj.attributes;
			if (a) {
				var al = a.length;
				for (var i = 0; i < al; i++) {
					if (a[i].nodeName == "DATA") {
						e.setAttribute("src", a[i].nodeValue);
					}
					else {
						e.setAttribute(a[i].nodeName, a[i].nodeValue);
					}
				}
			}
			var c = nestedObj.childNodes;
			if (c) {
				var cl = c.length;
				for (var j = 0; j < cl; j++) {
					if (c[j].nodeType == 1 && c[j].nodeName == "PARAM") {
						e.setAttribute(c[j].getAttribute("name"), c[j].getAttribute("value"));
					}
				}
			}
			obj.parentNode.replaceChild(e, obj);
		}
	}
	
	/* Show the Adobe Express Install dialog
		- Reference: http://www.adobe.com/cfusion/knowledgebase/index.cfm?id=6a253b75
	*/
	function showExpressInstall(regObj) {
		isExpressInstallActive = true;
		var obj = getElementById(regObj.id);
		if (obj) {
			if (regObj.altContentId) {
				var ac = getElementById(regObj.altContentId);
				if (ac) {
					storedAltContent = ac;
					storedAltContentId = regObj.altContentId;
				}
			}
			else {
				storedAltContent = abstractAltContent(obj);
			}
			if (!(/%$/.test(regObj.width)) && parseInt(regObj.width, 10) < 310) {
				regObj.width = "310";
			}
			if (!(/%$/.test(regObj.height)) && parseInt(regObj.height, 10) < 137) {
				regObj.height = "137";
			}
			doc.title = doc.title.slice(0, 47) + " - Flash Player Installation";
			var pt = ua.ie && ua.win ? "ActiveX" : "PlugIn",
				dt = doc.title,
				fv = "MMredirectURL=" + win.location + "&MMplayerType=" + pt + "&MMdoctitle=" + dt,
				replaceId = regObj.id;
			// For IE when a SWF is loading (AND: not available in cache) wait for the onload event to fire to remove the original object element
			// In IE you cannot properly cancel a loading SWF file without breaking browser load references, also obj.onreadystatechange doesn't work
			if (ua.ie && ua.win && obj.readyState != 4) {
				var newObj = createElement("div");
				replaceId += "SWFObjectNew";
				newObj.setAttribute("id", replaceId);
				obj.parentNode.insertBefore(newObj, obj); // Insert placeholder div that will be replaced by the object element that loads expressinstall.swf
				obj.style.display = "none";
				var fn = function() {
					obj.parentNode.removeChild(obj);
				};
				addListener(win, "onload", fn);
			}
			createSWF({ data:regObj.expressInstall, id:EXPRESS_INSTALL_ID, width:regObj.width, height:regObj.height }, { flashvars:fv }, replaceId);
		}
	}
	
	/* Functions to abstract and display alternative content
	*/
	function displayAltContent(obj) {
		if (ua.ie && ua.win && obj.readyState != 4) {
			// For IE when a SWF is loading (AND: not available in cache) wait for the onload event to fire to remove the original object element
			// In IE you cannot properly cancel a loading SWF file without breaking browser load references, also obj.onreadystatechange doesn't work
			var el = createElement("div");
			obj.parentNode.insertBefore(el, obj); // Insert placeholder div that will be replaced by the alternative content
			el.parentNode.replaceChild(abstractAltContent(obj), el);
			obj.style.display = "none";
			var fn = function() {
				obj.parentNode.removeChild(obj);
			};
			addListener(win, "onload", fn);
		}
		else {
			obj.parentNode.replaceChild(abstractAltContent(obj), obj);
		}
	} 

	function abstractAltContent(obj) {
		var ac = createElement("div");
		if (ua.win && ua.ie) {
			ac.innerHTML = obj.innerHTML;
		}
		else {
			var nestedObj = obj.getElementsByTagName(OBJECT)[0];
			if (nestedObj) {
				var c = nestedObj.childNodes;
				if (c) {
					var cl = c.length;
					for (var i = 0; i < cl; i++) {
						if (!(c[i].nodeType == 1 && c[i].nodeName == "PARAM") && !(c[i].nodeType == 8)) {
							ac.appendChild(c[i].cloneNode(true));
						}
					}
				}
			}
		}
		return ac;
	}
	
	/* Cross-browser dynamic SWF creation
	*/
	function createSWF(attObj, parObj, id) {
		var r, el = getElementById(id);
		if (el) {
			if (typeof attObj.id == UNDEF) { // if no 'id' is defined for the object element, it will inherit the 'id' from the alternative content
				attObj.id = id;
			}
			if (ua.ie && ua.win) { // IE, the object element and W3C DOM methods do not combine: fall back to outerHTML
				var att = "";
				for (var i in attObj) {
					if (attObj[i] != Object.prototype[i]) { // Filter out prototype additions from other potential libraries, like Object.prototype.toJSONString = function() {}
						if (i.toLowerCase() == "data") {
							parObj.movie = attObj[i];
						}
						else if (i.toLowerCase() == "styleclass") { // 'class' is an ECMA4 reserved keyword
							att += ' class="' + attObj[i] + '"';
						}
						else if (i.toLowerCase() != "classid") {
							att += ' ' + i + '="' + attObj[i] + '"';
						}
					}
				}
				var par = "";
				for (var j in parObj) {
					if (parObj[j] != Object.prototype[j]) { // Filter out prototype additions from other potential libraries
						par += '<param name="' + j + '" value="' + parObj[j] + '" />';
					}
				}
				el.outerHTML = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"' + att + '>' + par + '</object>';
				objIdArr[objIdArr.length] = attObj.id; // Stored to fix object 'leaks' on unload (dynamic publishing only)
				r = getElementById(attObj.id);	
			}
			else if (ua.webkit && ua.webkit < 312) { // Older webkit engines ignore the object element's nested param elements: fall back to the proprietary embed element
				var e = createElement("embed");
				e.setAttribute("type", FLASH_MIME_TYPE);
				for (var k in attObj) {
					if (attObj[k] != Object.prototype[k]) { // Filter out prototype additions from other potential libraries
						if (k.toLowerCase() == "data") {
							e.setAttribute("src", attObj[k]);
						}
						else if (k.toLowerCase() == "styleclass") { // 'class' is an ECMA4 reserved keyword
							e.setAttribute("class", attObj[k]);
						}
						else if (k.toLowerCase() != "classid") { // Filter out IE specific attribute
							e.setAttribute(k, attObj[k]);
						}
					}
				}
				for (var l in parObj) {
					if (parObj[l] != Object.prototype[l]) { // Filter out prototype additions from other potential libraries
						if (l.toLowerCase() != "movie") { // Filter out IE specific param element
							e.setAttribute(l, parObj[l]);
						}
					}
				}
				el.parentNode.replaceChild(e, el);
				r = e;
			}
			else { // Well-behaving browsers
				var o = createElement(OBJECT);
				o.setAttribute("type", FLASH_MIME_TYPE);
				for (var m in attObj) {
					if (attObj[m] != Object.prototype[m]) { // Filter out prototype additions from other potential libraries
						if (m.toLowerCase() == "styleclass") { // 'class' is an ECMA4 reserved keyword
							o.setAttribute("class", attObj[m]);
						}
						else if (m.toLowerCase() != "classid") { // Filter out IE specific attribute
							o.setAttribute(m, attObj[m]);
						}
					}
				}
				for (var n in parObj) {
					if (parObj[n] != Object.prototype[n] && n.toLowerCase() != "movie") { // Filter out prototype additions from other potential libraries and IE specific param element
						createObjParam(o, n, parObj[n]);
					}
				}
				el.parentNode.replaceChild(o, el);
				r = o;
			}
		}
		return r;
	}
	
	function createObjParam(el, pName, pValue) {
		var p = createElement("param");
		p.setAttribute("name", pName);	
		p.setAttribute("value", pValue);
		el.appendChild(p);
	}
	
	/* Cross-browser SWF removal
		- Especially needed to safely and completely remove a SWF in Internet Explorer
	*/
	function removeSWF(id) {
		var obj = getElementById(id);
		if (obj && (obj.nodeName == "OBJECT" || obj.nodeName == "EMBED")) {
			if (ua.ie && ua.win) {
				if (obj.readyState == 4) {
					removeObjectInIE(id);
				}
				else {
					win.attachEvent("onload", function() {
						removeObjectInIE(id);
					});
				}
			}
			else {
				obj.parentNode.removeChild(obj);
			}
		}
	}
	
	function removeObjectInIE(id) {
		var obj = getElementById(id);
		if (obj) {
			for (var i in obj) {
				if (typeof obj[i] == "function") {
					obj[i] = null;
				}
			}
			obj.parentNode.removeChild(obj);
		}
	}
	
	/* Functions to optimize JavaScript compression
	*/
	function getElementById(id) {
		var el = null;
		try {
			el = doc.getElementById(id);
		}
		catch (e) {}
		return el;
	}
	
	function createElement(el) {
		return doc.createElement(el);
	}
	
	/* Updated attachEvent function for Internet Explorer
		- Stores attachEvent information in an Array, so on unload the detachEvent functions can be called to avoid memory leaks
	*/	
	function addListener(target, eventType, fn) {
		target.attachEvent(eventType, fn);
		listenersArr[listenersArr.length] = [target, eventType, fn];
	}
	
	/* Flash Player and SWF content version matching
	*/
	function hasPlayerVersion(rv) {
		var pv = ua.pv, v = rv.split(".");
		v[0] = parseInt(v[0], 10);
		v[1] = parseInt(v[1], 10) || 0; // supports short notation, e.g. "9" instead of "9.0.0"
		v[2] = parseInt(v[2], 10) || 0;
		return (pv[0] > v[0] || (pv[0] == v[0] && pv[1] > v[1]) || (pv[0] == v[0] && pv[1] == v[1] && pv[2] >= v[2])) ? true : false;
	}
	
	/* Cross-browser dynamic CSS creation
		- Based on Bobby van der Sluis' solution: http://www.bobbyvandersluis.com/articles/dynamicCSS.php
	*/	
	function createCSS(sel, decl) {
		if (ua.ie && ua.mac) {
			return;
		}
		var h = doc.getElementsByTagName("head")[0], s = createElement("style");
		s.setAttribute("type", "text/css");
		s.setAttribute("media", "screen");
		if (!(ua.ie && ua.win) && typeof doc.createTextNode != UNDEF) {
			s.appendChild(doc.createTextNode(sel + " {" + decl + "}"));
		}
		h.appendChild(s);
		if (ua.ie && ua.win && typeof doc.styleSheets != UNDEF && doc.styleSheets.length > 0) {
			var ls = doc.styleSheets[doc.styleSheets.length - 1];
			if (typeof ls.addRule == OBJECT) {
				ls.addRule(sel, decl);
			}
		}
	}
	
	function setVisibility(id, isVisible) {
		var v = isVisible ? "visible" : "hidden";
		if (isDomLoaded && getElementById(id)) {
			getElementById(id).style.visibility = v;
		}
		else {
			createCSS("#" + id, "visibility:" + v);
		}
	}

	/* Filter to avoid XSS attacks 
	*/
	function urlEncodeIfNecessary(s) {
		var regex = /[\\\"<>\.;]/;
		var hasBadChars = regex.exec(s) != null;
		return hasBadChars ? encodeURIComponent(s) : s;
	}
	
	/* Release memory to avoid memory leaks caused by closures, fix hanging audio/video threads and force open sockets/NetConnections to disconnect (Internet Explorer only)
	*/
	var cleanup = function() {
		if (ua.ie && ua.win) {
			window.attachEvent("onunload", function() {
				// remove listeners to avoid memory leaks
				var ll = listenersArr.length;
				for (var i = 0; i < ll; i++) {
					listenersArr[i][0].detachEvent(listenersArr[i][1], listenersArr[i][2]);
				}
				// cleanup dynamically embedded objects to fix audio/video threads and force open sockets and NetConnections to disconnect
				var il = objIdArr.length;
				for (var j = 0; j < il; j++) {
					removeSWF(objIdArr[j]);
				}
				// cleanup library's main closures to avoid memory leaks
				for (var k in ua) {
					ua[k] = null;
				}
				ua = null;
				for (var l in swfobject) {
					swfobject[l] = null;
				}
				swfobject = null;
			});
		}
	}();
	
	
	return {
		/* Public API
			- Reference: http://code.google.com/p/swfobject/wiki/SWFObject_2_0_documentation
		*/ 
		registerObject: function(objectIdStr, swfVersionStr, xiSwfUrlStr) {
			if (!ua.w3cdom || !objectIdStr || !swfVersionStr) {
				return;
			}
			var regObj = {};
			regObj.id = objectIdStr;
			regObj.swfVersion = swfVersionStr;
			regObj.expressInstall = xiSwfUrlStr ? xiSwfUrlStr : false;
			regObjArr[regObjArr.length] = regObj;
			setVisibility(objectIdStr, false);
		},
		
		getObjectById: function(objectIdStr) {
			var r = null;
			if (ua.w3cdom) {
				var o = getElementById(objectIdStr);
				if (o) {
					var n = o.getElementsByTagName(OBJECT)[0];
					if (!n || (n && typeof o.SetVariable != UNDEF)) {
							r = o;
					}
					else if (typeof n.SetVariable != UNDEF) {
						r = n;
					}
				}
			}
			return r;
		},
		
		embedSWF: function(swfUrlStr, replaceElemIdStr, widthStr, heightStr, swfVersionStr, xiSwfUrlStr, flashvarsObj, parObj, attObj) {
			if (!ua.w3cdom || !swfUrlStr || !replaceElemIdStr || !widthStr || !heightStr || !swfVersionStr) {
				return;
			}
			widthStr += ""; // Auto-convert to string
			heightStr += "";
			if (hasPlayerVersion(swfVersionStr)) {
				setVisibility(replaceElemIdStr, false);
				var att = {};
				if (attObj && typeof attObj === OBJECT) {
					for (var i in attObj) {
						if (attObj[i] != Object.prototype[i]) { // Filter out prototype additions from other potential libraries
							att[i] = attObj[i];
						}
					}
				}
				att.data = swfUrlStr;
				att.width = widthStr;
				att.height = heightStr;
				var par = {}; 
				if (parObj && typeof parObj === OBJECT) {
					for (var j in parObj) {
						if (parObj[j] != Object.prototype[j]) { // Filter out prototype additions from other potential libraries
							par[j] = parObj[j];
						}
					}
				}
				if (flashvarsObj && typeof flashvarsObj === OBJECT) {
					for (var k in flashvarsObj) {
						if (flashvarsObj[k] != Object.prototype[k]) { // Filter out prototype additions from other potential libraries
							if (typeof par.flashvars != UNDEF) {
								par.flashvars += "&" + k + "=" + flashvarsObj[k];
							}
							else {
								par.flashvars = k + "=" + flashvarsObj[k];
							}
						}
					}
				}
				addDomLoadEvent(function() {
					createSWF(att, par, replaceElemIdStr);
					if (att.id == replaceElemIdStr) {
						setVisibility(replaceElemIdStr, true);
					}
				});
			}
			else if (xiSwfUrlStr && !isExpressInstallActive && hasPlayerVersion("6.0.65") && (ua.win || ua.mac)) {
				isExpressInstallActive = true; // deferred execution
				setVisibility(replaceElemIdStr, false);
				addDomLoadEvent(function() {
					var regObj = {};
					regObj.id = regObj.altContentId = replaceElemIdStr;
					regObj.width = widthStr;
					regObj.height = heightStr;
					regObj.expressInstall = xiSwfUrlStr;
					showExpressInstall(regObj);
				});
			}
		},
		
		getFlashPlayerVersion: function() {
			return { major:ua.pv[0], minor:ua.pv[1], release:ua.pv[2] };
		},
		
		hasFlashPlayerVersion: hasPlayerVersion,
		
		createSWF: function(attObj, parObj, replaceElemIdStr) {
			if (ua.w3cdom) {
				return createSWF(attObj, parObj, replaceElemIdStr);
			}
			else {
				return undefined;
			}
		},
		
		removeSWF: function(objElemIdStr) {
			if (ua.w3cdom) {
				removeSWF(objElemIdStr);
			}
		},
		
		createCSS: function(sel, decl) {
			if (ua.w3cdom) {
				createCSS(sel, decl);
			}
		},
		
		addDomLoadEvent: addDomLoadEvent,
		
		addLoadEvent: addLoadEvent,
		
		getQueryParamValue: function(param) {
			var q = doc.location.search || doc.location.hash;
			if (param == null) {
				return urlEncodeIfNecessary(q);
			}
			if (q) {
				var pairs = q.substring(1).split("&");
				for (var i = 0; i < pairs.length; i++) {
					if (pairs[i].substring(0, pairs[i].indexOf("=")) == param) {
						return urlEncodeIfNecessary(pairs[i].substring((pairs[i].indexOf("=") + 1)));
					}
				}
			}
			return "";
		},
		
		// For internal usage only
		expressInstallCallback: function() {
			if (isExpressInstallActive && storedAltContent) {
				var obj = getElementById(EXPRESS_INSTALL_ID);
				if (obj) {
					obj.parentNode.replaceChild(storedAltContent, obj);
					if (storedAltContentId) {
						setVisibility(storedAltContentId, true);
						if (ua.ie && ua.win) {
							storedAltContent.style.display = "block";
						}
					}
					storedAltContent = null;
					storedAltContentId = null;
					isExpressInstallActive = false;
				}
			} 
		}
	};
}();

Shadowbox.options.players=["flv","html","iframe","img","qt","swf","wmp"];
Shadowbox.options.useSizzle=true;