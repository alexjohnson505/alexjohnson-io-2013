<?php

class Controller
{
    public $user;
    public $allow_public_access;
    public $is_restricted_route;
    private $model;
    private $action;

    function __construct($model, $action)
    {
        $this->model = $model;
        $this->action = $action;

        if ($action != 'login' && $action != 'logout') //todo: i don't like that this is repeated in the response class
        {
            $this->user = current_user();
            $this->is_public_route();

            if (!$this->user && !$this->allow_public_access)
                Response()->not_logged_in();

            if($this->is_restricted_route())
                Response()->not_authorized();

            //Authority::initialize($this->user);
        } else {
            $auth = new Auth();
            if ($action == 'login') {

                $result = $auth->login($_POST['username'], $_POST['password']);

                if ($result !== false)
                    Response()->successful_login();
                else Response()->unsuccessful_login();
            } else {
                $auth->logout();
            }
        }

        //todo:seems like i might be able to have a default handler. If function doesn't exist on this controller, and there is no controller for the specified model, check the actual model for a function with the model name and run it. this may really eliminate the need for controllers. I hope...
    }

    function get($id = null)
    {

        //create a model of this type
        $model = new $this->model($id);

        //if we are requesting a specific model, make sure the current user is the owner, if we're not getting a specific model
        //and are getting a list, the list will be filtered in each of the individual models
        if (!$this->allow_public_access) {
            if (isset($id) && !current_user()->can_access($model))
                Response()->not_authorized();
        }


        //check to see if the url is in the format /projects or /projects/1 (instead of projects/1/tasks)
        if (@func_get_arg(1) == false) {
            if ($id == null){
                Response($model->get());
            }
            else {
                //todo:i really don't like that we're returning the entire model. we shlould be returning thre result of the function call....
                Response($model->to_array());
            }

        } else {

            //url is in the format projects/1/tasks
            //we are getting a list of entities associated with a specific entity (i.e. the tasks for this project)
            //TODO:Perhaps it might be useful to have controllers on the server after all? This line and the supporting code in each model seems a bit like routing
            $parameters = func_get_args();
            $id = array_shift($parameters);
            $action = array_shift($parameters);

            $model_class = get_class($model);

            if (method_exists($model_class, $action)) {
                $result = call_user_func_array(array($model, $action), $parameters);
                //$result = $model->$action($parameters);
            } else if (method_exists($model_class, "get_$action")) {
                //todo:need to implement routing logic for cases where the function name isnt the same as the action ie. action is 'tasks' but function is get_tasks, right now I'm using a hack
                $action = "get_$action";
                $result = call_user_func_array(array($model, $action), $parameters);
                //$result = $model->$action($parameters);
            }
            else $result = false;

            Response($result);
        }
    }

    function is_public_route()
    {
        global $CONFIG;

        $is_public_route = $this->find_route($CONFIG['public_routes']);
        $this->allow_public_access = $is_public_route;

        return $is_public_route;
    }

    function is_restricted_route(){
        global $CONFIG;

        $is_restricted_route = $this->find_route($CONFIG['restricted_routes']);
        $this->is_restricted_route = $is_restricted_route;

        return $is_restricted_route;
    }

    function find_route($routes_to_check){
        $match_found = false;

        foreach ($routes_to_check as $route) {
            $pieces = explode('/', $route);
            $model = $pieces[0];
            $action = isset($pieces[1]) ? $pieces[1] : null;

            if($this->model == $model && isset($action) && $action == '*'){
                $match_found = true;
                break;
            }
            else if ($this->model == $model && isset($action) && $this->action == $action) {
                $match_found = true;
                break;
            } else if ($this->model == $model && !isset($action)) {
                $match_found = true;
                break;
            }
        }

        return $match_found;
    }

    function delete($id = null)
    {
        if (current_user()->role != 'admin')
            return false;


        $id = isset($id) ? $id : $_POST['id'];

        //parameters imported from $_POST or passed directly
        $model = new $this->model($id);

        //if we are requesting a specific model, make sure the current user is the owner, if we're not getting a specific model
        //and are getting a list, the list will be filtered in each of the individual models
        if (!current_user()->can_access($model))
            Response()->not_authorized();
        else {
            $result = $model->delete();
            Response($result);
        }
    }

    function set_model($model_name)
    {
        $this->model = $model_name;
    }

    function alert($msg = "There was an error", $type = "notice")
    {
        @session_start();
        $_SESSION['alert'] = $msg;
        $_SESSION['alert_class'] = $type;
    }

    function save()
    {
        $id = Request::param('id');

        $model = new $this->model($id);

        if(!current_user()->can_access($model))
            Response()->not_authorized();

        //clear the params that were imported from the database
        $model->clear_params();

        //import the parameters sent from the client
        $model->import_parameters($_POST);

        $result = $model->save();

        if ($model->validation_passed())
            Response($result);
        else Response()->error($model->errors());

    }

    function logged_in()
    {
        $this->user = Auth::logged_in();

        if (!$this->user)
            Response()->not_logged_in();
        else Response()->logged_in();
    }

    public static function current_user()
    {
        $user = Auth::logged_in();
        //$user = new User(2);

        if ($user) {
            $_SESSION['current_user'] = $user; //todo: is this a good idea?
            return $user;
        } else return false;
    }



    function messages($reference_object, $reference_id)
    {
        //todo:does this really belong on the controller?
        $model = new $reference_object($reference_id);

        //make sure the user owns the object that we are requesting messages for
        if (!current_user()->can_access($model))
            Response()->not_authorized();

        $message = new Message();
        $messages = $message->get($reference_object, $reference_id);
        Response($messages);
    }


    function config(){
        global $CS_CONFIG;
        Response($CS_CONFIG);
    }
}