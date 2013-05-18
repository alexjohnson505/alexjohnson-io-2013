(function ($) {
    var FluidList = function ($anchor, options) {
        var self = this,
            existingItems = $anchor.children(),
            currentItem,
            ListItem,
            itemOrder,
            itemCount,
            autoSaveTimeout;

        //todo: need to normalize the names of the callbacks. Perhaps switch to an event model.

        ListItem = function ($item) {
            this.id = new Date().getTime() + '-' + Math.floor(Math.random() * (100000 - 1 + 1)) + 1;

            //we need to make sure we create a new jq object for each item, otherwise we are just passing around the same object which wreaks all kinds of havoc
            this.$element = $item || $(options.itemTemplate);
            //the actual input element that the user will type in, it's set in makeEditable
            this.$input = false;
            this.editableSelector = options.editableSelector;
            this.value = '';
            this.originalValue = false;
            this.isSection = this.$element.hasClass('list-header');//todo:cssSelectors object

            //user defined extra data for this list item
            this.extraData = {};

            //keep track of whether there is already a save request for this item
            this.isSaving = false;

            //if there is a save operation in progress for this list item, we may need to get a reference to it (i.e.
            //for saves called in quick succession)
            this.savingDeferred = false;

            this.$element.data('item', this);

            this.makeEditable();

            if ($item && options.itemImportCallback) {
                options.itemImportCallback(this, $item);
            }

            return this;
        };

        ListItem.prototype.setSaving = function(deferred){
            this.isSaving = true;
            this.savingDeferred = deferred;
        };

        ListItem.prototype.setNotSaving = function(){
            this.isSaving = false;
            this.savingDeferred = false;
        };

        ListItem.prototype.makeEditable = function () {
            var $editable, $input = {};

            if (this.editableSelector && !this.$element.find('input').length) {
                $input = $('<input type="text"/>');
                $editable = this.$element.find(this.editableSelector);

                if(options.getItemValueCallback)
                    this.value = options.getItemValueCallback($editable.text());
                else this.value = $editable.text();

                //set the input value to the existing text and replace the text with the input
                $input.val(this.value);
                $editable.html($input);

                this.$input = $input;
            }
        };

        ListItem.prototype.makeNotEditable = function () {
            var $input = this.$element.find('input');

            if (this.editableSelector && $input) {
                $input.parent().text($input.val());
            }
        };

        ListItem.prototype.manageHeaderStatus = function () {
            var self = this,
                headerCriteriaOK = this.headerCriteriaMet();

            if (self.isSection && !headerCriteriaOK) {
                this.makeRegularItem();
            }
            else if (!self.isSection && headerCriteriaOK) {
                this.makeHeader();
            }
        };

        ListItem.prototype.headerCriteriaMet = function () {
            return this.value[this.value.length - 1] == ':';
        };

        ListItem.prototype.makeHeader = function () {
            this.isSection = true;
            this.$element.addClass('list-header');
            this.save(true);
        };

        ListItem.prototype.makeRegularItem = function () {
            this.isSection = false;
            this.$element.removeClass('list-header');
            this.save(true);
        };

        ListItem.prototype.focus = function () {
            var resetCurrentItemOnFocus = false;
            this.originalValue = this.value;

            this.setFocusAtEndOfInput();
            this.$element.addClass('selected').find('input').trigger('focus', resetCurrentItemOnFocus); //TODO: Maybe this should be a class rather than input.allows for more flexible markup


        };

        ListItem.prototype.setFocusAtEndOfInput = function(){
            //Without this function, the cursor will be at the beginning of the input in firefox and ie
            //this function must be called before we trigger focus on the element
            //http://stackoverflow.com/a/12654402
            var el = this.$input.get(0);
            var elemLen = el.value.length;

            if(elemLen){
                el.selectionStart = elemLen;
                el.selectionEnd = elemLen;
            }

        };

        ListItem.prototype.unfocus = function () {
            this.$element.removeClass('selected');
            this.save();
        };

        ListItem.prototype.hasChanged = function () {
            return this.originalValue !== this.value;
        };

        ListItem.prototype.save = function (forceSave) {
            var self = this,
                classes = 'saving error-saving',
                newOrder = currentOrder(),
                deferred = false;


//todo:this is where we check to see if order has
//            if(this.value.length === 0){
//                this.destroy();
//            }
            clearTimeout(autoSaveTimeout);

            if (this.hasChanged() || forceSave == true) {
                if (options.itemSaveCallback && self.isSaving == false) {

                    self.$element.removeClass(classes);

                    self.$element.addClass('saving');

                    //manually setting isSaving. We want isSaving set as quickly as possible just in case another call to save is
                    //initiated before the callback finishes (i.e. if for some reason the callback itself triggers an event
                    //that calls save
                    self.isSaving = true;

                    //todo:this is a bad name, makes it seem as if the callback is fired AFTER save is complete. Should be called save handler or someothin
                    deferred = options.itemSaveCallback(this, itemOrder, newOrder);

                    //we keep track of whether there is a pending save request to prevent more than one save of the
                    //same item at the same time (i.e. quickly focus/unfocus an item before a previous save request has finished)
                    self.setSaving(deferred);

                    setCurrentOrder(newOrder);

                    $.when(deferred).done(function () {
                        self.$element.removeClass('saving');
                        self.setNotSaving();
                    }).fail(function(){
                       self.$element.removeClass(classes);
                       self.$element.addClass('error-saving');
                       self.setNotSaving();
                    });

                    return self.savingDeferred;
                }
                else if(self.isSaving === true){
                    //the list item is already saving, so let's return the deferred from the existing save request
                    return self.savingDeferred;
                }
                return false;
            }
            return false;
        };

        ListItem.prototype.setData = function (key, value) {
            this.extraData[key] = value;

        };

        ListItem.prototype.getData = function (key) {
            return this.extraData[key];
        };

        ListItem.prototype.disable = function () {
            if (this.$input) {
                this.$element.addClass('disabled');
                this.$input.attr('disabled', 'disabled');

                if (this.disabledCallback)
                    this.disabledCallback(this);
            }

        };

        ListItem.prototype.enable = function () {
            if (this.$input) {
                this.$element.removeClass('disabled');
                this.$input.removeAttr('disabled');
            }
        };

        ListItem.prototype.isDisabled = function () {
            return typeof this.$input.attr('disabled') != 'undefined';
        };

        function handleKeyPress() {
            currentItem.manageHeaderStatus();

            //handle the autosave
            clearTimeout(autoSaveTimeout);
            //todo: change back to 3000
            autoSaveTimeout = setTimeout(saveCurrentItem, options.autoSaveInterval || 3000);
        }

        function saveCurrentItem(){
            currentItem.save();
        }

        function keydownBackspaceHandler(e) {
            var val = currentItem.value;

            if (!val.length) {
                //we need this because the current item is going to be reset once we call focuse on previous item.
                //without this we will be deleting the wrong item from the list
                var oldCurrent = currentItem;

                //delete this item, set the focus to the previous item. Passing false in the
                focusOnPreviousItem();
               // self.deleteListItem(oldCurrent);
                e.preventDefault();
            }
        }

        function focusOnPreviousItem() {
            var $prev = currentItem.$element.prev(), prev;

            deleteCurrentIfEmpty();

            if ($prev.length) {
                prev = $prev.data('item'); //find('input').focus();
                prev.focus(); //TODO: This needs to be refactored (all of the key press logic). This should automatically set the current item without causing an infinite loop
                self.setCurrentItem(prev);
            }
        }

        function focusOnNextItem() {
            var $next = currentItem.$element.next(), next;

            deleteCurrentIfEmpty();

            if ($next.length) {
                next = $next.data('item'); //find('input').focus();
                next.focus();
                self.setCurrentItem(next);
            }
        }

        function deleteCurrentIfEmpty() {
            if (!currentItem.value.length)
                self.deleteListItem(currentItem);
        }

        function openCurrentItem(){
            if(options.openItemCallback)
                options.openItemCallback(currentItem);
        }

        function addNewItem(){
            //prevent the user from creating a new item if there current item is empty
            if (currentItem.value.length)
                self.addListItem();
        }

        function generateStructuredArray() {
            var list = [],
                parentIndex = false;

            $anchor.find('.list-item').each(function (i, itemDomObj) {
                //we need to clone the item because we are going to remove the $element property and we don't want
                //to affect the dom.
                var item = $.extend(true, {}, $(itemDomObj).data('item'));
                delete item.$element;

                if (item.isSection) {
                    list.push(item);
                    item.children = [];
                    parentIndex = list.length - 1;
                }
                else {
                    if (parentIndex === false) {
                        list.push(item);
                    }
                    else {
                        list[parentIndex].children.push(item);
                    }
                }
            });

            return list;
        }

        function generateFlatArray() {
            var list = [],
                parentId = false;

            $anchor.find('.list-item').each(function (i, itemDomObj) {
                //we need to clone the item because we are going to remove the $element property and we don't want
                //to affect the dom.
                var item = $.extend(true, {}, $(itemDomObj).data('item'));
                delete item.$element;

                if (item.isSection) {
                    list.push(item);
                    parentId = item.id;
                }
                else {
                    if (parentId === false) {
                        list.push(item);
                    }
                    else {
                        item.parentId = parentId;
                        list.push(item);
                    }
                }
            });

            return list;
        }

        function currentOrder(){
            return $anchor.sortable('toArray');
        }

        function setCurrentOrder(currentOrder){
            itemOrder = currentOrder;
        }

        function init() {
            options.itemTemplate = options.itemTemplate || '<li class="list-item"><div class="sort-handle"></div><input type="text"/></li>';

            $anchor.data('FluidList', this);

            initSortable();

            bindEvents();

            start();
        }

        function bindEvents() {

            $anchor.on('keydown.fluidlist', 'input', function (e) {
                var code = e.which;

                switch (code) {
                    case 8:
                        //backspace
                        keydownBackspaceHandler(e);
                        break;
                    case 13:
                        //enter
                        e.preventDefault();

                        if(e.ctrlKey || e.metaKey){
                            //ctrl + enter or cmd + enter
                            openCurrentItem();
                        }
                        else{
                            //just the enter key
                            addNewItem();
                        }

                        break;
                    case 38:
                        e.preventDefault();
                        focusOnPreviousItem();
                        break;
                    case 40:
                        e.preventDefault();
                        focusOnNextItem();
                        break;
                    case 83:
                        //capture Ctrl + S or Cmd + S
                        if(e.ctrlKey || e.metaKey){
                            e.preventDefault();
                            saveCurrentItem();
                        }
                        break;


                }
            });

            //handleKeyPress needs to be called on keyup rather than keydown, otherwise neither the input, nor
            //currentItem.value will contain the key that was just pressed.
            $anchor.on('keyup.fluidlist', 'input', function (e) {
                var $this = $(this);

//todo:changes that happen too quickly on imported items(press on key and then enter immediately after) are not getting recored. This next line needs to be in the keydown handler, we should also probably cache the input on the listItem so we don't have to do $(this) every time
                currentItem.value = $this.val();

                switch (e.which) {
                    case 13 || 38 || 40:
                        //we don't need to take any action on enter, up, or down for the key up event
                        break;
                    default:
                        //the backspace case is handled here as well
                        handleKeyPress($(this));
                        break;
                }
            });

            $anchor.on('focus.fluidlist', 'input', function (e, setCurrentItem) {
                //TODO: THis event seems to be firing wayyyy too many times. console.log('yerrrr');

                //this check is needed to prevent infinite recursion (calls to the focus event)
                if (typeof setCurrentItem == 'undefined')
                    self.setCurrentItem($(this).parents('li').first().data('item'));
            });

            $anchor.on('blur.fluidlist', 'input', function(e){
                //todo:this sucks for performance. We can't use currentItem, because this (the item blurred) may no longer be the current item, this is because the 'Enter' key handler is bound to keydown, which fires before this blur event. The enter handler resets the currentItem reference, which means this item is no longer the currentItem. Consider moving the 'enter' handler to keyup or some other event that happens after blur??
                var item = $(this).parents('li').data('item');

                if(item)
                    item.unfocus();
            });

            $anchor.on('click.fluidlist', '.save-item', function () {
                $(this).closest('li').data('item').save(true);
            });

            $anchor.single_double_click({
                doubleClickCallback:function (e) {
                    var $target = $(e.target),
                        $li = $target.closest('li');

                    //we only want to act on a double click event if the target is an input and the input is actually
                    //in a list item
                    if ($target.is('input') && $li.length)
                        options.doubleClickCallback($li.data('item'));
                }
            });
        }

        function start(){
            itemCount = 0;

            //import existing items or create the first empty item..
            if (existingItems.length)
                self.importItems(existingItems);
            else self.addListItem();

            //set the initial order
            itemOrder = currentOrder();
        }

        function initSortable(){
            $anchor.sortable({
                handle:'.sort-handle',
                start:function (e, ui) {
                    itemOrder = currentOrder();
                },
                stop:function (e, ui) {
                    var newOrder,
                        item = ui.item.data('item');

                    function stopSort(){
                        self.setCurrentItem(ui.item.data('item'));
                        if (options.orderChangeCallback) {
                            newOrder = currentOrder();
                            options.orderChangeCallback(newOrder, itemOrder);

                            //the new order becomes the current order, since we have already called the orderchange callback
                            // and any order processing would have run
                            setCurrentOrder(newOrder);
                        }
                    }

                    if(item.isSaving){
                        $.when(item.savingDeferred).done(function(){
                            stopSort();
                        });
                    }
                    else stopSort();


                }
            });
        }



        this.list = {};

        this.updateList = function (type) {
            var self = this;
            if (!type || type === 'structured')
                self.list = generateStructuredArray();
            else if (type == 'flat')
                self.list = generateFlatArray();

        };

        this.setCurrentItem = function (listItem) {
            //TODO: There has to be a better way to avoid the infinite calls
            var resetCurrentItemOnFocus = false;

            //unfocus previous item handled by the blur, event handler

            //set and focus the new current item
            currentItem = listItem;
            currentItem.focus();
        };

        this.deleteListItem = function (listItem) {
            listItem.$element.remove();

            //we need to run the delete callback before the item is actually deleted from the array, because it will
            //likely be removed from memory altogether if there are no other references to it.
            if (options.itemDeleteCallback)
                options.itemDeleteCallback(listItem);

            delete self.list[listItem.id];
        };

        this.addListItem = function (item, silent) {
            var newItem,
                //if we're passing in an instance of listItem, then the item has previously been in the list and should be
                // appended to the list or added at the position specified in the ListItem object. Otherwise, it should
                // be added after the current item
                ignoreCurrentPosition = false;

            if (item instanceof ListItem) {
                newItem = item;
                ignoreCurrentPosition = true;
                newItem.makeEditable();
            }
            else  newItem = new ListItem(item);

            if (currentItem && !ignoreCurrentPosition) {
                currentItem.$element.after(newItem.$element);
            }
            else {
                $anchor.append(newItem.$element);
            }

            self.list[newItem.id] = newItem;
            itemCount++;

            if (silent !== true)
                self.setCurrentItem(newItem);
        };

        this.importItems = function (items) {
            var index = 0;

            $.each(items, function (i, item) {
                self.addListItem($(item), true);
            });

            //if a value was passed in for currentItemIndex, we need to set the item at that index to the currentItem.
            //this should work even though hashes don't actually have numerical indexes.
            //todo: is this still necessary?
            if (typeof options.currentItemIndex !== 'undefined') {
                $.each(self.list, function (key, value) {
                    if (options.currentItemIndex === index) {
                        self.setCurrentItem(value);
                        return false;
                    }

                    index++;
                });
            }
        };

        this.destroy = function () {
            $anchor.unbind('.fluidlist');
            $anchor.removeData('FluidList');

            //we want to remove the inputs, since they probably weren't part of the original markup
            $.each(self.list, function (id, listItem) {
                var $editable = listItem.$element.find(listItem.editableSelector);
                $editable.html($editable.find('input').val());
            });

            delete self;
        };

        this.toggleDisabled = function (item) {
            var listItem = item instanceof ListItem ? item : item.data('item');

            if (!listItem.isDisabled())
                listItem.disable();
            else listItem.enable();
        };

        this.removeListItem = function (item) {

            var listItem = item instanceof ListItem ? item : item.data('item');

            //use detach, because we don't want to remove the reference to the ListItem object that is stored on the
            // DOM element (just in case we want to add it back later)
            listItem.$element.detach();

            listItem.makeNotEditable();

            delete self.list[listItem.id];
            itemCount--;

            //if there are no more items in the list, we need to create an empty item (so the user has something to type in)
            if(itemCount <= 0)
                self.addListItem();

            return listItem;
        };

        init.apply(this);
    };

    $.fn.fluidList = function (options) {
        new FluidList(this, options);

        return this;
    };

//    $(function () {
//        var $list = $('<ul></ul>'),
//            fluid_list = new FluidList($list);
//        $list.appendTo('#inner');
//
//        //$list.data('FluidList',fluid_list );
//
//        $('#generate-object').click(function () {
//            fluid_list.updateList();
//        });
//        //$('body').append('<ul></ul>')
//    });
})(jQuery);



