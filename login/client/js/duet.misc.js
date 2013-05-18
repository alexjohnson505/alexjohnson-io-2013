DUET.misc = function () {
    /*
     * object.watch polyfill
     *
     * 2012-04-03
     *
     * By Eli Grey, http://eligrey.com
     * Public Domain.
     * NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK.
     */

// object.watch
    if (!Object.prototype.watch) {
        Object.defineProperty(Object.prototype, "watch", {
            enumerable:false, configurable:true, writable:false, value:function (prop, handler) {
                var oldval = this[prop],
                    newval = oldval, getter = function () {
                        return newval;
                    },
                    setter = function (val) {
                        oldval = newval;
                        return newval = handler.call(this, prop, oldval, val);
                    };

                if (delete this[prop]) { // can't watch constants
                    Object.defineProperty(this, prop, {
                        get:getter, set:setter, enumerable:true, configurable:true
                    });
                }
            }
        });
    }

// object.unwatch
    if (!Object.prototype.unwatch) {
        Object.defineProperty(Object.prototype, "unwatch", {
            enumerable:false, configurable:true, writable:false, value:function (prop) {
                var val = this[prop];
                delete this[prop]; // remove accessors
                this[prop] = val;
            }
        });
    }

    //jQuery tools
    //Position the error messages next to input labels
    $.tools.validator.addEffect("labelMate", function (errors, event) {
        $.each(errors, function (index, error) {
            error.input.first().parents('.field').addClass('has-error').find('.error').remove().end().prepend('<span class="error">' + error.messages[0] + '</span>');
        });

    }, function (inputs) {
        inputs.each(function () {
            $(this).parents('.field').removeClass('has-error').find('.error').remove();
        });
    });

    $.fn.serializeObject = function () {
        var obj = {};

        $.each(this.serializeArray(), function (i, o) {
            var n = o.name,
                v = o.value;

            obj[n] = obj[n] === undefined ? v
                : $.isArray(obj[n]) ? obj[n].concat(v)
                : [ obj[n], v ];
        });

        return obj;
    };

    // usage: log('inside coolFunc', this, arguments);
    // paulirish.com/2009/log-a-lightweight-wrapper-for-consolelog/
    window.log = function () {
        log.history = log.history || [];   // store logs to an array for reference
        log.history.push(arguments);
        if (this.console) {
            arguments.callee = arguments.callee.caller;
            var newarr = [].slice.call(arguments);
            (typeof console.log === 'object' ? log.apply.call(console.log, console, newarr) : console.log.apply(console, newarr));
        }
    };

    // make it safe to use console.log always
    (function (b) {
        function c() {
        }

        for (var d = "assert,clear,count,debug,dir,dirxml,error,exception,firebug,group,groupCollapsed,groupEnd,info,log,memoryProfile,memoryProfileEnd,profile,profileEnd,table,time,timeEnd,timeStamp,trace,warn".split(","), a; a = d.pop();) {
            b[a] = b[a] || c
        }
    })((function () {
        try {
            console.log();
            return window.console;
        } catch (err) {
            return window.console = {};
        }
    })());
};