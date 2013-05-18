
// usage: log('inside coolFunc', this, arguments);
// paulirish.com/2009/log-a-lightweight-wrapper-for-consolelog/
window.log = function(){
  log.history = log.history || [];   // store logs to an array for reference
  log.history.push(arguments);
  if(this.console) {
    arguments.callee = arguments.callee.caller;
    var newarr = [].slice.call(arguments);
    (typeof console.log === 'object' ? log.apply.call(console.log, console, newarr) : console.log.apply(console, newarr));
  }
};

// make it safe to use console.log always
(function(b){function c(){}for(var d="assert,clear,count,debug,dir,dirxml,error,exception,firebug,group,groupCollapsed,groupEnd,info,log,memoryProfile,memoryProfileEnd,profile,profileEnd,table,time,timeEnd,timeStamp,trace,warn".split(","),a;a=d.pop();){b[a]=b[a]||c}})((function(){try
{console.log();return window.console;}catch(err){return window.console={};}})());


(function($){
    $.fn.outerHtml = function(){
        return $('<div></div>').append(this).html();
    };

  

    /*!
     * jQuery serializeObject - v0.2 - 1/20/2010
     * http://benalman.com/projects/jquery-misc-plugins/
     *
     * Copyright (c) 2010 "Cowboy" Ben Alman
     * Dual licensed under the MIT and GPL licenses.
     * http://benalman.com/about/license/
     */

// Whereas .serializeArray() serializes a form into an array, .serializeObject()
// serializes a form into an (arguably more useful) object.

    (function($, undefined) {
        '$:nomunge'; // Used by YUI compressor.

        $.fn.serializeObject = function() {
            var obj = {};

            $.each(this.serializeArray(), function(i, o) {
                var n = o.name,
                    v = o.value;

                obj[n] = obj[n] === undefined ? v
                    : $.isArray(obj[n]) ? obj[n].concat(v)
                    : [ obj[n], v ];
            });

            return obj;
        };

    })(jQuery);

    //jQuery tools
    //Position the error messages next to input labels
    $.tools.validator.addEffect("labelMate", function(errors, event) {
        $.each(errors, function(index, error) {
            error.input.first().parents('.field').addClass('has-error').find('.error').remove().end().prepend('<span class="error">' + error.messages[0] + '</span>');
        });

    }, function(inputs) {
        inputs.each(function() {
            $(this).parents('.field').removeClass('has-error').find('.error').remove();
        });
    });

})(jQuery);
// place any jQuery/helper plugins in here, instead of separate, slower script files.

/**
 * Within Viewport jQuery Plugin
 *
 * @description Companion plugin for withinViewport.js
 * @author      Craig Patik, http://patik.com/
 * @version     0.2
 * @date        2011-11-05
 */

;
(function ($, window, undefined) {

    /**
     * $.withinViewport()
     * @description          jQuery method
     * @param {Object}       [settings] optional settings
     * @return {Collection}  Contains all elements that were within the viewport
     */
    $.fn.withinViewport = function (settings) {
        if (typeof settings === "string") {
            settings = {sides:settings};
        }
        var opts = $.extend({}, settings, {sides:"all"}), elems = [];
        this.each(function () {
            if (withinViewport(this, opts)) {
                elems.push(this);
            }
        });
        return $(elems);
    };

    // Custom selector
    $.extend($.expr[":"], {
        "within-viewport":function (element) {
            return withinViewport(element, "all");
        }
    });

    /**
     * Optional enhancements and shortcuts
     *
     * @description Uncomment or comment these pieces as they apply to your project and coding preferences
     */

        // Shorthand jQuery methods
        //
    $.fn.withinViewportTop = function (settings) {
        if (typeof settings === "string") {
            settings = {sides:settings};
        }
        var opts = $.extend({}, settings, {sides:"top"}), elems = [];
        this.each(function () {
            if (withinViewport(this, opts)) {
                elems.push(this);
            }
        });
        return $(elems);
    };

    $.fn.withinViewportRight = function (settings) {
        if (typeof settings === "string") {
            settings = {sides:settings};
        }
        var opts = $.extend({}, settings, {sides:"right"}), elems = [];
        this.each(function () {
            if (withinViewport(this, opts)) {
                elems.push(this);
            }
        });
        return $(elems);
    };

    $.fn.withinViewportBottom = function (settings) {
        if (typeof settings === "string") {
            settings = {sides:settings};
        }
        var opts = $.extend({}, settings, {sides:"bottom"}), elems = [];
        this.each(function () {
            if (withinViewport(this, opts)) {
                elems.push(this);
            }
        });
        return $(elems);
    };

    $.fn.withinViewportLeft = function (settings) {
        if (typeof settings === "string") {
            settings = {sides:settings};
        }
        var opts = $.extend({}, settings, {sides:"left"}), elems = [];
        this.each(function () {
            if (withinViewport(this, opts)) {
                elems.push(this);
            }
        });
        return $(elems);
    };

    // Custom jQuery selectors
    //
    $.extend($.expr[":"], {
        "within-viewport-top":function (element) {
            return withinViewport(element, "top");
        },
        "within-viewport-right":function (element) {
            return withinViewport(element, "right");
        },
        "within-viewport-bottom":function (element) {
            return withinViewport(element, "bottom");
        },
        "within-viewport-left":function (element) {
            return withinViewport(element, "left");
        }
        //,
        // "within-viewport-top-left-45": function(element) {
        //   return withinViewport(element, {sides:'top left', top: 45, left: 45});
        // }
    });

})(jQuery, window);

//based on https://gist.github.com/399624
jQuery.fn.single_double_click = function (options) {
    return this.each(function () {
        var clicks = 0, self = this;
        jQuery(this).click(function (event) {
            clicks++;

            //this function will run regardless of whether it was a single or double click
            if (options.alwaysCallback)
                options.alwaysCallback.call(self, event);

            if (clicks == 1) {
                setTimeout(function () {
                    if (clicks == 1) {
                        //run the single click callback if it exists
                        if (options.singleClickCallback)
                            options.singleClickCallback.call(self, event);

                    } else {
                        //run the double click callback if it exists
                        if (options.doubleClickCallback)
                            options.doubleClickCallback.call(self, event);
                    }
                    clicks = 0;
                }, options.timeout || 300);
            }
        });
    });
}

