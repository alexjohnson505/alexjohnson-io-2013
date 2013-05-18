var DUET = DUET || {};

DUET.routes = {
    routes:{
        'projects/:id/:entityType':'projectEntityList',
        'projects/:id/:entityType/:entityId':'projectEntity',
        'projects/:id/invoices/:invoiceId/:invoiceAction':'invoiceScreens',
        'projects/:id':'projectEntityList',
        'projects':'projectEntityList',
        'tasks/:id':'task',
        'tasks':'task',
        'clients/:id':'client',
        'clients':'client',
        'users/:id':'user',
        'users':'user',
        'invoices':'invoice',
        'invoices/:id':'invoice',
        'files':'file',
        'files/:id':'file',
        'login':'login',
        'logout':'logout',
        'dashboard':'dashboard',
        'profile':'myProfile',
        'search/:query':'search',
        'forgot_password':'forgotPassword',
        'admin':'admin',
        'reporting':'reporting',
        '*path':'dashboard'
    },
    projects:function(){

    },
    projectEntityList:function (projectId, entityType) {
        var args, panelLoaded, params;

        panelLoaded = DUET.routeHelpers.initPrimaryPanel(projectId);
        args = arguments;

        $.when(panelLoaded).done(function () {
            params = DUET.routeHelpers.initSecondaryPanel(args);

            if(params.project){
                DUET.panelTwo.setTitle(params.project.name);
                DUET.panelTwo.setModel(params.project);
            }

            function collectionHandler() {
                var collection, view;

                collection = new DUET.Collection({
                    model:params.activeModelSingular,
                    url:params.project.entityUrl(params.activeModelSingular)
                });

                //todo: maybe some kind of loading text while the collection is loading for slow connections
                //TODO:clicking on any of these list items reloads the entire page. not cool
                collection.on('loaded', function () {
                    view = new DUET[DUET.utils.ucFirst(params.activeModelSingular) + 'ListView'](collection, params.project);
                    DUET.routeHelpers.panelTwoHandler(params, view);
                });

                collection.load();
            }

            function modelHandler() {
                var model, viewNamePrefix, view;

                if(DUET[params.activeModelName])
                    model = new DUET[params.activeModelName];
                else model = new DUET['Project' + params.activeModelName];

                model.on('loaded', function () {
                    viewNamePrefix = DUET.utils.ucFirst(params.activeModelName);

                    if(DUET[viewNamePrefix + 'View'])
                        view = new DUET[viewNamePrefix + 'View'](model);
                    else view = new DUET['Project' + viewNamePrefix + 'View'](model);

                    DUET.routeHelpers.panelTwoHandler(params, view);
                });

                model.load(projectId);
            }

            if (params.activeModel != 'calendar' && params.activeModel != 'details' && params.activeModel != 'notes') {
                collectionHandler();
            }
            else {
                modelHandler();
            }
        });

        DUET.evtMgr.publish('messagesContextChanged');
    },
    projectEntity:function (projectId, entityType, entityId) {
        var args, panelLoaded, params;

        panelLoaded = DUET.routeHelpers.initPrimaryPanel(projectId);
        args = arguments;

        $.when(panelLoaded).done(function () {
            params = DUET.routeHelpers.initSecondaryPanel(args);
            DUET.panelTwo.setModel(params.project);

            var activeModelUppercase = DUET.utils.ucFirst(params.activeModelSingular);
            var model = new DUET[activeModelUppercase];

            model.on('loaded', function () {
                DUET.context(params.activeModelSingular, model.id);
                var view = new DUET[activeModelUppercase + 'View'](model);
                DUET.panelTwo.setInnerContent(view);
                DUET.panelTwo.itemCategories.setSelected('project-' + params.activeModel);
            });

            model.load(params.activeModelId);
        });

        DUET.evtMgr.publish('messagesContextChanged');

    },
    invoiceScreens:function (projectId, invoiceId, invoiceAction) {
        var invoice, view, args, params,
            panelLoaded = DUET.routeHelpers.initPrimaryPanel(projectId);

        args = arguments;

        $.when(panelLoaded).done(function () {
            invoice = new DUET.Invoice();
            params = DUET.routeHelpers.initSecondaryPanel(args);

            //prevent a user from opening up the build or import views
            //if opened, they still wouldn't be able to modify the invoice because it's restricted on the server side
            if(!DUET.userIsAdmin() && invoiceAction != 'preview')
                return false;

            invoice.on('loaded', function () {
                if(invoiceAction == 'build')
                    view = new DUET.InvoiceEditorView(invoice);
                else if(invoiceAction == 'import')
                    view = new DUET.InvoiceImportView(invoice);
                else if (invoiceAction == 'preview')
                    view = new DUET.InvoicePreviewView(invoice);

                DUET.routeHelpers.panelTwoHandler(params, view);
            });

            invoice.load(invoiceId);
        });
    },
    task:function (id) {
        var task, view, taskData;
//todo:base model route?
        DUET.baseModelRoute('task', id);
    },
    client:function (id) {
        DUET.baseModelRoute('client', id);
    },
    user:function (id) {
        DUET.baseModelRoute('user', id);
    },
    invoice:function (id) {
        DUET.baseModelRoute('invoice', id);
    },
    file:function (id) {
        DUET.baseModelRoute('file', id);
    },
    dashboard:function () {
        var dashboardView,
            dashboard = new DUET.Dashboard();

        DUET.context('dashboard', 1);

        //todo:this route is getting called before the initialization has completed, causing this if statement to be required. This shouldn't be necessary.
        if(DUET.initComplete == true){
            DUET.panelTwo.setTitle('Dashboard'); //todo:lang file
            DUET.panelTwo.setModel(dashboard);

            DUET.panelOne.hide();

            DUET.panelTwo.setContent(DUET.panelTwo.loadingView.$get());
        }

        dashboard.on('loaded', function(){
            dashboardView = new DUET.DashboardView(dashboard);
            DUET.panelTwo.setContent(dashboardView);
        });
        dashboard.load(1);
    },
    login:function () {
        DUET.stop();
    },
    logout:function(){
        new DUET.Request({
            url:'app/logout',
            success:function(){
                 window.location = '#' + DUET.config.default_route;
            }
        });
    },
    myProfile:function(){
        this.user(DUET.my.id);
        DUET.panelOne.hide();
    },
    search:function (query) {
        var searchModel = new DUET.Search();


        DUET.context('dashboard', 1);
        DUET.panelTwo.setTitle('Search results for \'' + query + '\''); //todo:lang file
        DUET.panelTwo.setModel(searchModel);
        DUET.panelTwo.removeTitleWidget();

        DUET.panelOne.hide();

        searchModel.on('loaded', function () {
            var searchResultsView = new DUET.SearchResultsView(searchModel);

            DUET.panelTwo.setContent(searchResultsView);
        });

        searchModel.load(query);
    },
    forgotPassword:function(){
        var forgotPasswordView = new DUET.ForgotPasswordView();
        forgotPasswordView.addTo({$anchor:$('body')});
    },
    admin:function(){
        if(!DUET.userIsAdmin())
            return false;

        var adminView = new DUET.AdminView();
        DUET.context('admin-settings', 1);
        DUET.panelTwo.setTitle('Admin Settings'); //todo:lang file
        DUET.panelTwo.setModel(); //todo:lang file

        DUET.panelOne.hide();

        DUET.panelTwo.setContent(adminView);
    },
    reporting:function(){
        var reports = new DUET.Reports();
        DUET.context('reporting', 1);
        DUET.panelTwo.setTitle('Reporting');
        DUET.panelTwo.setModel();

        DUET.panelOne.hide();

        reports.on('loaded', function(){
            var reportingView = new DUET.ReportingView(reports);
            DUET.panelTwo.setContent(reportingView);
        });

        reports.load(1);
    }
};

//common functions used throughout the routes
DUET.routeHelpers = {
    initPrimaryPanel:function (projectId) {
        DUET.context('project', projectId);
        return DUET.panelOne.setContent('project', projectId);
    },
    initSecondaryPanel:function (params) {
        var collection, view, activeModel, activeModelSingular, activeModelName, project, params, projectId, activeModelId;

        //secondary panel
        projectId = params[0];
        activeModel = params[1] || DUET.options.defaultProjectTab;
        activeModelName = DUET.utils.ucFirst(activeModel);
        activeModelId = params[2];
        activeModelSingular = DUET.utils.trim(activeModel, 's');

        project = projectId ? DUET.panelOne.collection.get(projectId) : false;

        DUET.panelTwo.item = project; //TODO: store the item in state instead?

        return{
            activeModel:activeModel,
            activeModelName:activeModelName,
            activeModelId:activeModelId,
            activeModelSingular:activeModelSingular,
            project:project
        };
    },
    collectionHandler:function () {
    },
    panelTwoHandler:function (params, view) {
        var context;

        DUET.panelTwo.itemCategories.setSelected('project-' + params.activeModel);
        DUET.panelTwo.setInnerContent(view); //TODO: Think about having a DUET.setContent('panelTwo', view.get()), basically an app level set content function?

        context = DUET.context();

        if (context && (context.object == 'project')) {
            var progressWidget = new DUET.ProjectProgressTitleWidgetView(params.project);
            DUET.panelTwo.setTitleWidget(progressWidget);
        }
    }
};

DUET.baseModelRoute = function (modelType, id) {
    var model, view, modelData, modelTypeU = DUET.utils.ucFirst(modelType);

    function getTitle(){
        var type = model.type,
            title = '';

        switch(type){
            case 'project':
            case 'client':
            case 'file':
                title = model.name;
                break;
            case 'task':
                title = 'Task: ' + model.task.substr(0, 10) + '...';
                break;
            case 'invoice':
                title = 'Invoice ' + model.number;
                break;
            case 'user':
                title = model.firstName + ' ' + model.lastName;
                break;
            case 'dashboard':
                title = 'Dashboard';
                break;
        }

        return title;
    }

    //primary panel
    var panelLoaded = DUET.panelOne.setContent(modelType, id);

    $.when(panelLoaded).done(function () {
        modelData = id || DUET.panelOne.firstItem;

        //secondary panel
        model = new DUET[modelTypeU];

        model.on('loaded', function () {
            DUET.context(modelType, model.id);
            if(DUET[modelTypeU + 'DetailsView'])
                view = new DUET[modelTypeU + 'DetailsView'](model);
            else view = new DUET[modelTypeU + 'View'](model);

            DUET.panelTwo.removeTitleWidget();
            DUET.panelTwo.setTitle(getTitle());
            DUET.panelTwo.setContent(view);
            DUET.panelTwo.setModel(model);
        });

        model.load(modelData);

        DUET.evtMgr.publish('messagesContextChanged');
    });
};
