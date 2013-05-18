(function ($) {

    "use strict";

    var DocumentViewerDependencyLoader = function (loaderOptions) {
        var load, dependencies, dependencyTests;

        //paths to each dependency
        dependencies = {
            pdfjs:['libs/pdfjs/compatibility.js', 'libs/pdfjs/pdf.js'],
            prettify:['libs/google-code-prettify/prettify.js', 'libs/google-code-prettify/prettify.css'],
            flowplayer:['libs/flowplayer/flowplayer-3.2.6.min.js'],
            jplayer:['libs/jPlayer/jquery.jplayer.js']
        };

        yepnope.errorTimeout = loaderOptions.errorTimeout || 4000;

        //tests to determine if the dependency is already loaded
        dependencyTests = {
            pdfjs:function () {
                return typeof PDFJS !== 'undefined';
            },
            prettify:function () {
                return typeof prettyPrint !== 'undefined';
            },
            flowplayer:function () {
                return typeof flowplayer !== 'undefined';
            },
            jplayer:function () {
                return typeof $.jPlayer !== 'undefined';
            }
        };

        function loadDependency(userOptions) {
            var options = $.extend({}, loaderOptions, userOptions);

            userOptions = userOptions || {};

            load = [];

            //if the user has specified a different path for the libs folder, update the paths for each of the dependencies
            if (options.path) {
                var realPaths = {};

                $.each(dependencies, function (key, dependency) {
                    var files = [];

                    $.each(dependency, function (i, file) {
                        files.push(options.path + file);
                    });

                    realPaths[key] = files;
                });

                dependencies = realPaths;
            }

            //test for the dependency, if it exists run the callback. If not, load the dependency then run the callback
            //TODO: Look at the yepnope api. It probably handles this case.
            if (dependencyTests[options.dependency]()) {
                options.callback();
            }
            else {
                yepnope({
                    load:dependencies[options.dependency],
                    complete:function () {
                        //check to see if the dependency actually loaded, since script loaders can't accurately report
                        //loading errors in a cross browser manner (from yepnope docs)
                        if (dependencyTests[options.dependency]()) {
                            if ($.isFunction(options.callback)) {
                                options.callback();
                            }
                        }
                        else {
                            //there was an error loading the dependency. Throw an error
                            options.errorHandler('There was an error loading the dependency (' + dependencies[options.dependency] + ') Please check your options.path value');
                        }

                    }
                });
            }
        }

        return{
            loadDependency:loadDependency
        };
    };

    var DocumentViewer = function ($anchor, userOptions) {
        var $wrapper,
            $outer,
            $inner,
            codeExtensions = ['bsh', 'c', 'cc', 'cpp', 'cs', 'csh', 'css', 'cyc', 'cv', 'htm', 'html', 'java', 'js',
                'm', 'mxml', 'perl', 'php', 'pl', 'pm', 'py', 'rb', 'sh', 'xhtml', 'xml', 'xsl', 'sql', 'vb'],
            imageExtensions = ['png', 'jpg', 'jpeg', 'gif'],
            audioExtensions = ['mp3', 'm4a', 'oga', 'webma', 'fla'],
            jPlayerVideoExtensions = ['m4v', 'ogv', 'ogg', 'webmv', 'flv'],
            flowplayerExtensions = ['mpg', 'mpeg', 'mov', 'divx', 'avi', 'wmv'],
            videoExtensions = jPlayerVideoExtensions.concat(flowplayerExtensions),
            jPlayerExtensions = jPlayerVideoExtensions.concat(audioExtensions),
            defaultOptions = {
                width:500,
                height:'auto',
                //there are situations where we may not want the plugin to force the audio player height
                setAudioHeight:true,
                debug:false,
                autoplay:true,
                autoLoadDependencies:true,
                enableTextAndCode:true,
                jPlayer:{},
                emptyText:'<div class="document-viewer-empty-text">No Document Loaded</div>',
                unsupportedBrowserText:'<div class="document-viewer-empty-text">This document can not be opened in this browser. Please upgrade.</div>',
                errorText:'An error occurred while loading the ',
                showErrors:true,
                markup:false,
                serverResponseText:'Unexpected server response of ',
                path:'document-viewer/',
                init:true,
                useTextLoaderHelper:true,
                timeoutValue:5000
            },
            cssSelector = {
                scrollable:'.scrollable',
                viewport:'.viewport',
                scrollContent:'.scroll-content',
                wrapper:'.document-viewer-wrapper',
                outer:'.document-viewer-outer',
                anchor:'.document-viewer',
                loaded:'.loaded',
                error:'.error'
            },
            options = {},
            dependencyLoader,
            currentType,
            currentFilename,
            id,
            $loadingIndicator = $('<div class="dv-loading"></div>');

        function init() {
            var markup,
                defaultMarkup = '<div class="document-viewer-wrapper dv-markup clearfix">' +
                    '<div class="document-viewer-outer dv-markup clearfix">' +
                    '<div class="document-viewer dv-markup clearfix"></div>' +
                    '</div>' +
                    '</div>';

            //apply any user defined options
            options = $.extend(true, {}, defaultOptions, userOptions);

            //set up the document viewer markup
            markup = options.markup || defaultMarkup;
            $anchor.append(markup);

            $wrapper = $anchor.find(cssSelector.wrapper);
            $outer = $anchor.find(cssSelector.outer);
            $inner = $anchor.find(cssSelector.anchor);

            //we need to create an id for the inner element. This is only used by flowplayer
            id = 'document-viewer' + new Date().getTime();
            $inner.attr('id', id);

            //nothing has been loaded yet, add the empty text;
            $inner.html(options.emptyText);
            setSize(options); //todo:necessary? why?

            //initialize the dependency loader
            dependencyLoader = new DocumentViewerDependencyLoader({
                path:options.path,
                errorHandler:debugMessage
            });
        }

        function getType(ext) {

            var type = false;

            if(!ext)
                return false;

            //just in case the ext is uppercase (which would make it miss the correct branch in the if/else)
            ext = ext.toLowerCase();

            if (ext === 'pdf') {
                type = 'pdf';
            }
            else if (ext === 'txt') {
                type = 'txt';
            }
            else if ($.inArray(ext, codeExtensions) !== -1) {
                type = 'code';
            }
            else if ($.inArray(ext, videoExtensions) !== -1) {
                type = 'video';
            }
            else if ($.inArray(ext, audioExtensions) !== -1) {
                type = 'audio';
            }
            else if ($.inArray(ext, imageExtensions) !== -1) {
                type = 'image';
            }

            return type;
        }

        function debugMessage(msg) {
            if (options.debug && window.console) {
                console.log('DOCUMENT VIEWER: ' + msg);
            }
        }

        function getExtension(filename) {
            //From: Tomalak's answer, http://stackoverflow.com/questions/680929/how-to-extract-extension-from-filename-string-in-javascript
            var re = /(?:\.([^.]+))?$/;

            return re.exec(filename)[1];
        }

        function initScrollbar(type) {
            if (type === 'txt' || type === 'code') {
                $wrapper.addClass(c('scrollable'));
                $outer.addClass(c('viewport'));
                $inner.addClass(c('scrollContent'));

                if (options.scrollbar !== false)
                    $wrapper.tinyscrollbar();
            }
            else {
                //we need to remove the scrollbar classes otherwise the viewer will not display correctly
                $wrapper.removeClass(c('scrollable'));
                $outer.removeClass(c('viewport'));
                $inner.removeClass(c('scrollContent'));

                if (options.scrollbar !== false)
                    $wrapper.tinyscrollbar_destroy();
            }
        }

        //get the class or id for a selector
        function c(selector) {
            if (cssSelector[selector]) {
                return cssSelector[selector].substr(1);
            }
            else {
                return '';
            }
        }

        function getHeight(options, type) {
            //todo:all of this sizing logic needs to be refactord. Possibly removed. Seems unnecessarily complicated.
            var height;
            if (type === 'pdf' || type === 'txt' || type === 'code') {
                height = (options.height && typeof options.height !== "string") ? options.height : options.width * 1.3;
            }
            else if (type === 'video') {
                height = (options.height && typeof options.height !== "string") ? options.height : Math.round((options.width / 16) * 9);
            }
            else if (type === 'image') {
                height = options.height || 'auto';
            }
            else if (type === 'audio') {
                height = options.setAudioHeight ? 43 : 'auto';
            }
            else {
                height = 'auto';
            }

            return height;
        }

        function getSize(options) {
            options.width = options.width || defaultOptions.width;
            options.height = getHeight(options, options.type);

            return {height:options.height, width:options.width};
        }

        function setSize(options) {
            var type = options.type;

            getSize(options);

            $wrapper.paddedWidth(options.width + 2).find('.dv-markup').paddedWidth(options.width);

            //pdf.js needs the height of the inner element to be explicitly be set, but tinyscrollbar will break in
            //firefox if the inner height is set
            if (type == 'pdf') {
                $inner.paddedHeight(options.height).parent().paddedHeight(options.height);
            }
            else if (type === 'txt' || type === 'code') {
                $inner.parent().paddedHeight(options.height);
            }
            else if (type == 'video') {
                //todo: check box-sizing value. Behaved differently for border-box
                //todo:adding 63 is hack, but if i don't $inner will not take into accoun the space required by the controls
                $inner.height(options.height + 0).parent().height(options.height + 53);
            }
            else if (type == 'audio' || type == 'image') {
                $inner.height(options.height).parent().height(options.height);
            }
            else {
                $inner.height('auto').parent().height('auto');
            }
        }

        function loadDependency(dependency, callback) {
            //if the user doesn't want to use the dependency loader, run the callback immediately (this assumes the user
            //has included the dependency manually
            if (!options.autoLoadDependencies) {
                callback();
            }
            else {
                dependencyLoader.loadDependency({
                    dependency:dependency,
                    callback:callback
                });
            }
        }

        function load(filename, userOptions) {
            var loadOptions,
                isLoaded = new $.Deferred();

            //nothing to do if the filename is invalid
            if(!filename){
                isLoaded.reject();
                error(1, 'No Filename Provided');
                return isLoaded;
            }

            filename = encodeURI(filename);

            loadOptions = $.extend(true, {}, options, userOptions);

            if (typeof loadOptions.extension === 'undefined') {
                loadOptions.extension = getExtension(filename);
            }
            if (typeof loadOptions.type === 'undefined') {
                loadOptions.type = getType(loadOptions.extension);
            }

            //We don't want to have to type loadOptions.type for each usage.
            currentType = loadOptions.type;

            //The error function is going to want the current filename if there is an error. Let's set it to a global var
            currentFilename = filename;

            //add a class to the document viewer for the current type
            $wrapper.removeClass('pdf code txt video audio image').addClass(currentType);

            //we need to pass the deferred into the loader for the specific file type
            loadOptions.isLoaded = isLoaded;

            //run the callback when the file is loaded
            $.when(isLoaded).done(
                function () {
                    $anchor.addClass(c('loaded')); //todo:this isn't getting called for code elements that fail

                    if (loadOptions.callback && $.isFunction(loadOptions.callback)) {
                        loadOptions.callback();
                    }
                }).fail(function () {
                    $anchor.addClass(c('error'));
                });

            setSize(loadOptions);

            $inner.html('');

            //add the loading indicator
            $loadingIndicator.css('top', options.height / 2 - 18);
            $inner.append($loadingIndicator);

            //set the timeout for this file load
            setTimeout(function(){
                isLoaded.reject();
            }, loadOptions.timeoutValue);

            //load the file using the loader for this file type
            switch (currentType) {
                case 'pdf':
                    var pdf = new PDFLoader(filename, loadOptions);
                    break;
                case 'code':
                case 'txt':
                    //the user may not want to use the php dependency required for text and code
                    if (options.enableTextAndCode === true) {
                        var txt = new TextLoader(filename, loadOptions);
                    }
                    else {
                        error(1, 'Invalid File Type. Please set enableTextAndCode option to true');
                        debugMessage('Invalid File Type. Please set enableTextAndCode option to true');
                    }
                    break;
                case 'video':
                    var video = new VideoLoader(filename, loadOptions);
                    break;
                case 'audio':
                    var audio = new JPlayerLoader(filename, loadOptions);
                    break;
                case 'image':
                    var image = new ImageLoader(filename, loadOptions);
                    break;
                default:
                    //TODO: better arguments for error function, perhaps pass in object
                    error(1, 'Invalid File Type');
                    debugMessage('Invalid File Type');
                    isLoaded.reject();
                    break;
            }

            //since text/code are asynchronous, the handler will run the callback on it's own
            if (currentType !== 'txt' && currentType !== 'code') {
                initScrollbar(currentType);
            }


            return isLoaded;
        }

        function close() {
            $inner.html(options.emptyText);
            setSize(options);
            initScrollbar();
            //todo:are there resources/events that should be released?
        }

        //Convenience method for:
        // 1. determining what type of document is associated with a given filename
        // 2. Determining if a document can be opened i.e. if(getDocumentType !== false)
        function getDocumentType(filename) {
            return getType(getExtension(filename));
        }

        function error(errorCode, errorText, serverResponse) {

            if (options.showErrors) {
                var errorMessage = '<br/><span>' + options.serverResponseText + ' ' + errorCode + ' (' + errorText + ')</span>';
                $inner.find('.dv-loading').remove();
                $inner.append('<div class="dv-error">' + options.errorText + currentType + errorMessage + '</div>');
            }
            debugMessage('Error loading file (' + currentFilename + '). Please make sure that the path is correct');
        }

        function hideLoadingIndicator() {
            //TODO: global Set content function that handles all of this rather than calling in each handler
            $loadingIndicator.remove();
        }

        var PDFLoader = function (filename, options) {
            var pdf,
                currentPage = 1,
                $menu = $('<div class="pdf-menu"><div class="prev-page" >Prev Page</div><div class="next-page">Next Page</div><div class="go-to-page">Go to page <input></div></div>');

            //bind event handlers for the pdf menu
            $menu.on('click', '.prev-page', function () {
                if (currentPage > 1) {
                    currentPage -= 1;
                    setPage(currentPage);
                }
            });

            $menu.on('click', '.next-page', function () {
                if (currentPage < pdf.numPages) {
                    currentPage += 1;
                    setPage(currentPage);
                }
            });

            $menu.on('keyup', 'input', function () {
                var pageNum = $(this).val();
                if (pageNum > 0 && pageNum <= pdf.numPages) {
                    setPage(pageNum);
                }
            });

            function supports_canvas() {
                return !!document.createElement('canvas').getContext;
            }

            function load(filename) {

                $inner.append($menu);

                PDFJS.workerSrc = options.path + 'libs/pdfjs/pdf.js';

                //todo: it looks like there may be an updated version of pdfjs, with a getDocument function rather than getPdf
                PDFJS.getPdf({
                    url:filename,
                    error:function (e) {
                        error(e.target.status, e.target.statusText);
                        options.isLoaded.reject();
                    }
                }, function (data) {
                    pdf = new PDFJS.PDFDoc(data);
                    setPage(currentPage);
                });
            }

            function setPage(pageNum) {
                $inner.find('canvas').remove();
                $inner.append(renderPage(pageNum));
            }

            function renderPage(pageNum) {
                var page, canvas, context;
                page = pdf.getPage(pageNum);

                // Prepare canvas using PDF page dimensions
                canvas = document.createElement('canvas');
                canvas.id = 'page' + pageNum;

                context = canvas.getContext('2d');
                canvas.width = $inner.width();
                canvas.height = $inner.height();

                // Render PDF page into canvas context
                page.startRendering(context, function () {
                    hideLoadingIndicator();
                    options.isLoaded.resolve();
                });

                return canvas;
            }

            if (supports_canvas()) {
                loadDependency(['pdfjs'], function () {
                    load(filename);
                });
            }
            else {
                $inner.html(options.unsupportedBrowserText);
                return;
            }

            return{
                load:load,
                setPage:setPage
            };
        };

        var TextLoader = function (filename, options) {

            function loadUsingGetContents(){
                $.ajax({
                    url:options.path + 'libs/getContents.php',
                    type:'POST',
                    data:{file:filename},
                    success:function (response) {
                        response = $.parseJSON(response);

                        if (response.status === 'success') {
                            var $contents = $('<pre class="prettyprint linenums">' + response.response + '</pre>').css('opacity', 0);

                            hideLoadingIndicator();

                            //display the text
                            $inner.append($contents);
                            $contents.animate({opacity:1}); //TODO: Put this in a function, $inner.animate

                            initScrollbar(options.type);

                            //enable prettify after the text has loaded
                            loadDependency(['prettify'], function () {
                                if (options.type === 'code') {
                                    prettyPrint();
                                }

                                options.isLoaded.resolve();
                            });
                        }
                        else {
                            error('404', 'Not Found');
                            options.isLoaded.reject();
                        }
                    },
                    error:function (e) {
                        error();
                    }
                });
            }

            function loadDirectly(){

                $.ajax({
                    url:filename,
                    type:'POST',
                    data:{file:filename},
                    success:function (response) {

                            var $contents = $('<pre class="prettyprint linenums">' + response + '</pre>').css('opacity', 0);

                            //todo:duplicated in loadUsingGetContents
                            hideLoadingIndicator();

                            //display the text
                            $inner.append($contents);
                            $contents.animate({opacity:1}); //TODO: Put this in a function, $inner.animate

                            initScrollbar(options.type);

                            //enable prettify after the text has loaded
                            loadDependency(['prettify'], function () {
                                if (options.type === 'code') {
                                    prettyPrint();
                                }

                                options.isLoaded.resolve();
                            });

                    },
                    error:function (e) {
                        error();
                        options.isLoaded.reject();
                    }
                });
            }

            if(options.useTextLoaderHelper){
                loadUsingGetContents();
            }
            else loadDirectly();

        };

        var VideoLoader = function (filename, options) {
            var videoMgr;

            if ($.inArray(options.extension, jPlayerExtensions) !== -1) {
                videoMgr = new JPlayerLoader(filename, options);
            }
            else {
                videoMgr = new FlowplayerLoader(filename, options);
            }
        };

        var JPlayerLoader = function (filename, options) {
            var jPlayerOptions, jPlayerDefaults, $myJplayer, cssSelector, isVideo;

            //the css selectors from the markup that will be processed by this code
            cssSelector = {
                jPlayer:"#jquery_jplayer",
                jPlayerContainer:'.jPlayer-container',
                jPlayerInterface:'.jp-interface',
                jPlayerAncestor:'#jp-interface-' + new Date().getTime(),
                playlist:'.playlist',
                playing:'.playing',
                progress:'.progress-wrapper',
                volume:'.volume-wrapper'
            };

            jPlayerDefaults = {
                swfPath:options.path + "libs/jPlayer",
                supplied:options.extension,
                solution:'html, flash',
                cssSelectorAncestor:cssSelector.jPlayerAncestor,
                errorAlerts:options.debug,
                warningAlerts:options.debug,
                size:{
                    //height/width will be set when build interface is called
//                    height:options.height,
//                    width:options.width,
                    cssClass:"show-video"
                },
                sizeFull:{
                    width:"100%",
                    height:"90%",
                    cssClass:"show-video-full"
                },
                play:function () {
                    $(this).jPlayer("pauseOthers");
                }
            };

            function buildInterface() {
                var playerMarkup, $interface, width, height;

                playerMarkup = '<div class="ttw-video-player">' +
                    '<div class="jPlayer-container"></div>' +
                    '<div class="clear"></div>' +
                    '<div class="player jp-interface">' +
                    '<div class="player-controls">' +
                    '<div class="play jp-play button"></div>' +
                    '<div class="pause jp-pause button"></div>' +
                    '<div class="progress-wrapper">' +
                    '<div class="progress-bg">' +
                    '<div class="progress jp-seek-bar">' +
                    '<div class="elapsed jp-play-bar"></div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="volume-wrapper">' +
                    '<div class="volume jp-volume-bar">' +
                    '<div class="volume-value jp-volume-bar-value"></div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<!-- These controls aren\'t used by this plugin, but jPlayer seems to require that they exist -->' +
                    '<span class="unused-controls">' +
                    '<span class="previous jp-previous"></span>' +
                    '<span class="next jp-next"></span>' +
                    '<span class="jp-video-play"></span>' +
                    '<span class="jp-stop"></span>' +
                    '<span class="jp-mute"></span>' +
                    '<span class="jp-unmute"></span>' +
                    '<span class="jp-volume-max"></span>' +
                    '<span class="jp-current-time"></span>' +
                    '<span class="jp-duration"></span>' +
                    '<span class="jp-repeat"></span>' +
                    '<span class="jp-repeat-off"></span>' +
                    '<span class="jp-gui"></span>' +
                    '<span class="jp-restore-screen"></span>' +
                    '<span class="jp-full-screen"></span>' +
                    '<span class="jp-no-solution"></span>' + //TODO: I probably want to use this one

                    '</span>' +
                    '</div>' +
                    '<div class="clear"></div>' +
                    '</div>';

                //Build the html
                $interface = $(playerMarkup).css({opacity:0}).appendTo($inner);
                $interface.find('.jp-interface').attr('id', cssSelector.jPlayerAncestor.substr(1));
                width = $inner.width();
                height = $inner.height();

                //we need to reset the width of the video container based on the inner width, which factors in padding
                jPlayerOptions.size.width = width;

                //apply widths to the player controls
                $interface.find(cssSelector.jPlayerInterface).paddedWidth(width);

                //if this is a video, we need to apply a size to the video element, otherwise we need to hide it (height = 0)
                if (isVideo) {
             //       $interface.height(parseInt(options.height, 10)).find(cssSelector.jPlayerContainer).height(height);
                }
                else {
                    $interface.find(cssSelector.jPlayerContainer).height(0);
                }

                $interface.css({opacity:1});
            }

            function setDefaultDimensions(){
                //we can't use the heights currently being passed in by the options object because they do not take into
                //account padding on the parent enlement
                //todo: the size of the player is 2px too big if I don't minus two, should i be subtracting border-width?
                jPlayerOptions.size.height = options.height - $inner.verticalPadding() - 2;

                if (!isVideo)
                    jPlayerOptions.size.height = 0;
                else jPlayerOptions.size.width = options.width - $inner.horizontalPadding();

            }

            function load() {

                //is this video or audio?
                isVideo = $.inArray(options.extension, jPlayerVideoExtensions) !== -1;

                //apply any user defined jPlayer options
                jPlayerOptions = $.extend(true, {}, jPlayerDefaults, options.jPlayer);

                //if this is audio, we need to override the height for the jPlayer element
                // (needs to be hidden, but display:none breaks flash component)

                hideLoadingIndicator();


                //build the interface
                buildInterface();
                setDefaultDimensions();

                //initialize jPlayer
                $myJplayer = $inner.find('.jPlayer-container');

                $myJplayer.bind($.jPlayer.event.ready, function () {
                    var media = {};

                    //jPlayer setMedia accepts an object i.e. {mp3:somesong.mp3}, this creates the object
                    media[options.extension] = filename;
                    $myJplayer.jPlayer("setMedia", media);

                    debugMessage('jPlayer Ready');

                    if (options.autoplay) {
                        $myJplayer.jPlayer('play');
                    }

                    options.isLoaded.resolve();
                });

                $myJplayer.bind($.jPlayer.event.progress, function () {
                    //we were using this resolve the isLoaded deferred, but it's not fired with the flash player
                });

                $myJplayer.bind($.jPlayer.event.error, function () {
                    error('404', 'Not Found');
                    options.isLoaded.reject();
                });

                //Initialize jPlayer

                $myJplayer.jPlayer(jPlayerOptions);


            }

            loadDependency(['jplayer'], function () {
                load();
            });
        };

        var FlowplayerLoader = function (filename, options) {
            //TODO:flowplayer options

            function load() {
                $inner.height(options.height);

                hideLoadingIndicator();

                flowplayer(id, options.path + "libs/flowplayer/flowplayer-3.2.7.swf", {
                    clip:{            // Clip is an object, hence '{...}'
                        autoPlay:options.autoplay,
                        autoBuffering:true,
                        url:filename,
                        height:options.height
                    },
                    plugins:{
                        controls:{
                            backgroundColor:"transparent",
                            backgroundGradient:"none",
                            sliderColor:'#FFFFFF',
                            sliderBorder:'1.5px solid rgba(160,160,160,0.7)',
                            volumeSliderColor:'#FFFFFF',
                            volumeBorder:'1.5px solid rgba(160,160,160,0.7)',
                            timeColor:'#ffffff',
                            durationColor:'#535353',
                            tooltipColor:'rgba(255, 255, 255, 0.7)',
                            tooltipTextColor:'#000000'
                        }
                    },
                    onLoad:function () {
                        options.isLoaded.resolve();
                    },
                    onError:function (errorCode, errorText) {
                        error(errorCode, errorText);
                        options.isLoaded.reject();
                    }
                });
            }

            loadDependency(['flowplayer'], function () {
                load();
            });
        };

        var ImageLoader = function (filename, options) {
            var $img = $('<img class="dv-image">').css('opacity', 0);

            //trigger an error if the image can not be loaded
            $img.error(function (e) {
                error('404', 'Not Found');
                options.isLoaded.reject();
            });

            //set the image source after we have already created the error handler
            $img.attr('src', filename);

            //trigger the loaded event when the image is loaded
            $img.imagesLoaded(function () {
                hideLoadingIndicator();

                $inner.append($img);
                $img.animate({opacity:1});

                options.isLoaded.resolve();
            });
        };

        //initialize the document viewer
        //we can't check options.init because the 'options' variable isn't defined until the init function is run
        if (userOptions.init !== false)
            init();

        return{
            load:load,
            close:close,
            getDocumentType:getDocumentType,
            getExtension:getExtension,
            getSize:getSize
        };
    };

    //todo:what is this for?
    window.DocumentViewer = DocumentViewer;

    $.fn.documentViewer = function (options) {
        var documentViewer = new DocumentViewer(this, options);

        this.data('document-viewer', documentViewer);

        return documentViewer;
    };


})(jQuery);