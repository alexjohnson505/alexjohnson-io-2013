var DUET = DUET || {};

DUET.config = {};

DUET.panelOne = {};

DUET.panelTwo = {};

DUET.sidebar = {};

DUET.layoutMgr = {};

DUET.messagesSidebar = {};    //todo:this may not be used

DUET.options.defaultProjectTab = 'details';

DUET.$appWrapper = false;

//todo:is this used? we have addTo...
DUET.addView = function(options) {

    var view = new DUET[options.view](options.data);

    options.$anchor.append(view.$get());

    if (view.postRenderProcessing)
        view.postRenderProcessing();
};

/****************************************
 Modules
 ****************************************/
//Base Module
//todo:get rid of this in favor of views
DUET.Module = function() {
    this.cssSelectors = {};

    this.$element = {};

    this.evtMgr = DUET.evtMgr;

    this.getTemplate = DUET.templateManager.$get;
};

DUET.Module.prototype.cssSelector = function(selectorName) {
    var self = this;

    if (self.cssSelectors[selectorName])
        return self.cssSelectors[selectorName];
    else return false;
};

DUET.Module.prototype.cssClass = function(selectorName) {
    var self = this;

    if (self.cssSelectors[selectorName])
        return self.cssSelectors[selectorName].substr(1);
    else return false;
};

DUET.Module.prototype.initialize = function() {
    if (this.load)
        this.load();

    if (this.render)
        this.render();
};

DUET.Module.prototype.debugMessage = function() {
    DUET.utils.debugMessage.apply(this);
};

DUET.Module.prototype.run = function(callback) {
    DUET.utils.run.apply(this, arguments);
};

//Layout Module
DUET.LayoutManager = function() {
    var self = this,
        state, sel, c,
        $domWindow, $appWindow, $sidebar, verticalMargin, horizontalMargin, headerHeight;

    //the css selectors used by the layout manager
    this.cssSelectors = {
        sidebar:'#sidebar',
        appWindow:'.window'
    };

    //maintains the state of each of the layout components
    state = {
        sidebarWidth:0 //TODO: used?
    };

    //quick access to the cssSelector and cssClass functions
    sel = self.cssSelector.bind(this);

    init_base_layout();
    
    //save references to each of the dom elements required to manage the layout
    $domWindow = $(window);
    $appWindow = $(sel('appWindow'));
    $sidebar = $(sel('sidebar'));

    var sidebarWidth = $sidebar.width();

    function initialize() {
        state.sidebarWidth = $sidebar.width();

        verticalMargin = parseInt($appWindow.css('margin-top'), 10) + parseInt($appWindow.css('margin-bottom'), 10);
        headerHeight = $('#header-wrapper').height();
        horizontalMargin = parseInt($appWindow.css('margin-left'), 10) + parseInt($appWindow.css('margin-right'), 10);
        resize();

        $appWindow.animate({'opacity':1});
    }

    function init_base_layout(){
       DUET.$appWrapper = DUET.templateManager.$get('base-layout');
        $('body').append(DUET.$appWrapper);
    }

    function resize() {
        $appWindow.height($domWindow.height() - verticalMargin - headerHeight); //.width($domWindow.width() - sidebarWidth - horizontalMargin);
    }

    //event handlers
    $domWindow.resize(resize);

    initialize();

    return{
        resize:resize
    }
};

DUET.LayoutManager.prototype = new DUET.Module();

//TODO: ALl of these are views (button, button set, list) not modules. A module is a collection of views that work together for some purpose

//Button //todo:this should be a veiw
DUET.Button = function(options) {
    this.options = options;

    this.buttonText = options.buttonText; //Make sure there is consistency in there I am setting these values, constructor or load? Perhaps load funciton is replaced entirely by a model?

    this.buttonType = this.options.buttonType || 'flat-button';

    this.buttonId = options.buttonId;

    this.initialize();
}; //TODO: Should I combine button and button set? They are extremely similar in terms of the code that was written

DUET.Button.prototype = new DUET.Module();

DUET.Button.prototype.render = function() {
    var self = this;

    self.$element = self.getTemplate('button', {
        buttonId:self.buttonId,
        buttonType:self.buttonType,
        buttonText:self.buttonText
    });
};

DUET.Button.prototype.setAction = function(action) {
    var self = this;

    self.$element.click(function() {
        self.run(action, this);
    });

    if (self.options.action)
        this.setAction(self.options.action);
};

/****************************************
 Panel Managers
 ****************************************/
DUET.PanelManager = function() {
    this.anchor = $('.window');

    this.content = $();

    this.actions = [
        {panelAction:'+', actionUrl:'#'}
    ];

    this.panel = {};

    this.cssSelectors = {};
};

DUET.PanelManager.prototype.buildPanel = function() {
    var self = this;

    self.panel = new DUET.PanelView({
        id:self.id,
        isPrimary:self.isPrimary,
        type:self.isPrimary ? '' : 'secondary',
        title:self.title,
        anchor:self.anchor,
        content:self.content, //TODO: Where is this getting set?
        actions:self.actions
    });

    self.panel.$element.appendTo(self.anchor);
};

//Primary Panel
DUET.PrimaryPanelManager = function() {
    var self = this;

    this.id = 'panel-one';

    this.isPrimary = true;

    this.isHidden = false;

    this.anchor = $('.window');

    this.content = $();

    this.$action = false;

    this.panel = {};

    this.model = '';

    this.title = '';

    this.collection = {};

    this.firstItem = {};

    this.maps = {
        task:{
            title:'task',
            meta1:'notes'
        },
        project:{
            title:'name',
            meta1:'clientName',
            meta2:'dueDateHumanized'
        },
        client:{
            title:'name',
            meta1:'email'
        },
        user:{
            title:'name',
            meta1:'email'
        },
        invoice:{
            title:'clientName',
            meta1:'formattedTotal',
            meta2:'statusText'
        },
        file:{
            title:'name'
        }
    };

    this.list = {};

    this .actions = [
        {panelAction:'+', actionUrl:'#'}
    ];

    function showHidePanelAction(){

        if(self.model == 'client' || self.model == 'project'){
            self.$action.css('display', 'block');
        }
        else self.$action.css('display', 'none');
    }

    this.setContent = function(model, selectedId) {
        var panelLoaded = new $.Deferred(),
            loadingView = new DUET.PanelLoadingView();

        if(this.isHidden)
            this.show();

        //x is a special id that the app will use to indicate that the list needs to be updated regardless of whether
        //the model changed (i.e. when deleting items from the list)
        if (this.model !== model || selectedId == 'x') {
            this.model = model;

            self.panel.setTitle(DUET.utils.ucFirst(model) + 's');

            self.collection = new DUET.Collection({model:model});

            self.collection.on('loaded', function() { //TODO: Is this redundant? I'm checking whether the collection is loaded in the list object
                self.firstItem = self.collection.getFirst();
                panelLoaded.resolve();
            });

            //self.firstItem = self.collection.getFirst();
            showHidePanelAction();

            //remove the previous list view if it exists
            if(self.listView)
                self.listView.unload();

            self.panel.notify('loading', false);

            self.collection.on('loaded', function(){
                self.listView = new DUET.ListView(self.collection, self.maps[model], selectedId);

                self.panel.hideNotification();

                self.listView.addTo({
                    $anchor:self.panel.$element,
                    position:'append'
                });
            });

            self.collection.load();
        }
        else {
            //the model hasn't changed so no need to update the list, but we still want to change the selected item
            self.listView.setSelected(selectedId);
            panelLoaded.resolve();
        }

        return panelLoaded;
    };

    //useful if we want to force the list to reload (i.e. when we've just added a new item to the list
    this.reset = function(){
        this.model = false;
    };

    this.buildPanel();

    this.$listWrapper = this.panel.$element.find('.list-wrapper');

    this.$action = this.panel.$element.find('.panel-action');

    this.$action.click(function(){
        new DUET['New' + DUET.utils.ucFirst(self.model) + 'View']();
    });

    this.hide = function(){
        self.isHidden = true;
        self.panel.$element.closest('.window').addClass('one-panel');
    };

    this.show = function(){
        self.isHidden = false;
        self.panel.$element.closest('.window').removeClass('one-panel');
    };
};

DUET.PrimaryPanelManager.prototype = new DUET.PanelManager();

//Secondary Panel
DUET.SecondaryPanelManager = function() {
    var self = this, sel, c;

    this.isPrimary = false;

    this.id = 'panel-two';

    this.title = '';

    this.anchor = $('.window');

    this.content = $();

    this.actions = [
        {panelAction:'Edit', actionUrl:'#', id:'edit-panel-entity'},
        {panelAction:'Delete', actionUrl:'#', class:'danger', id:'delete-panel-entity'}
    ];

    this.$actions = false;
    this.$actionsWrapper = false;

    this.panel = {};

    //this panel may be based on a specific item(i.e. a project)
    this.item = {};

    this.itemCategories = {};


    this.messagesButton = {};

    this.loadingView = new DUET.PanelLoadingView();

    this.noSelectionView = new DUET.NoSelectionView();

    //quick access to the cssSelector and cssClass functions
    sel = DUET.Module.prototype.cssSelector.bind(this);
    c = DUET.Module.prototype.cssClass.bind(this);

    function showHidePanelAction(){
        if(self.model && $.inArray(self.model.type, ['project', 'client', 'user']) !== -1){
            //todo:shouldn't have to do both of these. Need a better way to manage these buttons
            self.$actionsWrapper.css('display', 'block');
            self.$actions.css('display', 'block');
        }
        else self.$actions.css('display', 'none');
    }

    this.setContent = function($content) {
        self.panel.setContent($content);
    };

    this.setInnerContent = function($content) {
        self.panel.setInnerContent($content);
    };

    this.setTitle = function(title){
        self.panel.$title.html(title);
    };

    //todo:consider using DUET.context instead of storing reference to the model
    this.setModel = function(model){
        self.model = model;
        showHidePanelAction();
    };

    this.setTitleWidget = function(view) {
        self.panel.setTitleWidget(view);
    };

    this.removeTitleWidget = function(){
       self.panel.removeTitleWidget();
    };

    this.destroy = function(){
        $(window).off('resize.secondaryPanelManager');
    };

    //Not using the SecondaryPanelManager.prototype because there should only be one SecondaryPanelManger
    function initialize() {
        var projectItemCategories, messagesButton;

        self.buildPanel();

        self.itemCategories = buildProjectItemCategories();
        self.panel.addToInnerMenu(self.itemCategories.$element, true);

        //initially, there will be nothing selected
        self.panel.clearContent(self.noSelectionView);

        self.panel.resize();
    }

    function buildProjectItemCategories() {
        var projectItemCategories = new DUET.ButtonSetView({
            buttons:[
                {buttonId:'project-details', buttonText:'Details'},
                {buttonId:'project-calendar', buttonText:'Calendar'},
                {buttonId:'project-tasks', buttonText:'Tasks'},
                {buttonId:'project-files', buttonText:'Files'},
                {buttonId:'project-invoices', buttonText:'Invoices'},
                {buttonId:'project-notes', buttonText:'Notes'}
            ]
        });

        projectItemCategories.setAction(function(button) {
            var type = $(button).attr('id').substr(8);

            DUET.navigate('projects/' + self.item.id + '/' + type);

        });

        return projectItemCategories;
    }


    $(window).on('resize.secondaryPanelManager',function() {
        self.panel.resize();
    });

    DUET.evtMgr.subscribe('contextChanged', function(){
        self.setContent(self.loadingView.$get());
    });

    DUET.evtMgr.subscribe('contextCleared', function(){
        self.panel.clearContent(self.noSelectionView);
    });

    DUET.evtMgr.subscribe('setSecondaryContent', function(e, contentDetails) {
        var view = new DUET[contentDetails.view](contentDetails.data);

        self.setInnerContent(view.$get());
    });

    DUET.evtMgr.subscribe('secondaryContentUpdated', function(){
        self.panel.resize();
    });


    initialize();

    //todo:need a better way to manage these buttons. Actions wrapper and actions are conflicting
    this.$actions = this.panel.$element.find('.panel-actions');
    this.$actionsWrapper = this.$actions.parent();

    this.panel.$element.find('#edit-panel-entity').on('click', function(){
        new DUET['New' + DUET.utils.ucFirst(self.model.type) + 'View'](self.model);
    });

    this.panel.$element.find('#delete-panel-entity').on('click', function(){
        var type = self.model.type;

        DUET['Delete' + DUET.utils.ucFirst(type) + 'View'](self.model);
    });
};

DUET.SecondaryPanelManager.prototype = new DUET.PanelManager();



DUET.buildLayout = function(){
    DUET.layoutMgr = new DUET.LayoutManager();
    DUET.panelOne = new DUET.PrimaryPanelManager();
    DUET.panelTwo = new DUET.SecondaryPanelManager();

    DUET.headerViewInstance = new DUET.HeaderView();
    DUET.headerViewInstance.addTo({$anchor:$('#header-wrapper')});

    DUET.sidebarViewInstance = new DUET.SidebarView();
    DUET.sidebarViewInstance.addTo({$anchor:$('#sidebar'), position:'append'});

    DUET.messagesViewInstance = new DUET.MessagesPanelView();
    DUET.messagesViewInstance.addTo({$anchor:$('.inner-right'), position:'append'});

    DUET.initComplete = true;
};

DUET.initViewCommonParams = function(){
    DUET.viewCommonParams = {
        currencySymbol:DUET.config.currency_symbol
    };
};

DUET.loadConfig = function(){
    var configRequest;
    //load the client side config options
    configRequest = DUET.Request({
        url:'app/config',
        success:function(response){
            if(response.isValid()){
                DUET.config = response.data;
                DUET.initViewCommonParams();
            }
        }
    });

    return configRequest;
};

DUET.start = function(newLogin) {
    var self = this,
        continueStart = false,
        loginCheckRequest, appStarted = $.Deferred();

    //we can't do anything without the router, so lets make sure it's initialized
    if(!DUET.router){
        //start the history and the app router
        DUET.history = new DUET.History();
        DUET.router = new DUET.Router(DUET.routes);
    }

    $.when(DUET.templateManager.loadingTemplatesPromise, DUET.loadConfig()).done(function() { //TODO: I should have some kind of global on function
        loginCheckRequest = DUET.Request({
            url:'app/logged_in',
            success:function(response){
                if(response.auth != 'not_logged_in')
                    continueStart = true;
            }
        });

        $.when(loginCheckRequest.isComplete).done(function(){
            if (continueStart) {
                DUET.buildLayout();

                //we need to manually call the resize function once the main components have been built
                DUET.layoutMgr.resize();

                appStarted.resolve();

                if(!DUET.history || !DUET.history.started)
                    DUET.history.start();


                //todo: i probably should't show the screen until after the layout manager is finished loading?
            }
            else if(DUET.isPublicRoute()){
                if(!DUET.history || !DUET.history.started)
                    DUET.history.start();

            }
        });
    });

    return appStarted;
};

DUET.stop = function(message){
    if(!DUET.history || !DUET.history.started)
        DUET.history.start();

    if(DUET.sidebarViewInstance)
        DUET.sidebarViewInstance.unload();

    if(DUET.headerViewInstance)
        DUET.headerViewInstance.unload();

    if(DUET.messagesViewInstance)
        DUET.messagesViewInstance.unload();

    if(DUET.$appWrapper)
        DUET.$appWrapper.remove();

    //todo:this should stop history or the router or both
    if(!$('.login-window').length){
        DUET.addView({
            view:'LoginView',
            data:{message:message},
            $anchor:$('body')
        }); //todo: replace with the view.addTo funciton
    }
};

DUET.error = function (message) {
    var error = DUET.templateManager.$get('error');
    return error.prepend(message);
};

DUET.notice = function(message){
    var error = DUET.templateManager.$get('notice');
    return error.prepend(message);
};