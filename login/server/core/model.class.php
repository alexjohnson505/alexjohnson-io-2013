<?php
//TODO: Autoload or something
require_once('db/db.connection.class.php');
require_once('db/db.recordset.class.php');
require_once('db/db.recordsetrow.class.php');
require_once('db/db.exception.class.php');
require_once('db/db.helpers.class.php');

use phpSweetPDO\SQLHelpers\Basic as Helpers;

class Model
{
    public $id;
    protected $model;
    protected $params;
    protected $params_imported;
    protected $validator;
    protected $validator_tests;
    protected $table;
    protected $db;
    protected $has_errors;
    protected $is_valid_id;
    protected $cid;

    public $previous_params;

    /**
     * Connect to database and initialize model
     */
    function __construct($parameters = null)
    {
        $this->db = Database::getInstance();
        $this->model = get_class($this);

        if(!isset($this->table))
            $this->table = $this->table_name();

        $this->is_valid_id = true;

        if (isset($parameters)) {
            $is_valid_parameters = true;

            //a model can be built with and id or the list of parameters that need to be imported
            //if the parameters are passed in, they can be an object or array. If it's an object, we convert to an array //todo:may not be necessary if we get rid of the recordset concept
            //before we import_parameters
            if (is_numeric($parameters)) {
                //get the record that corresponds to this item
                $parameters = $this->get($parameters);

                //imported into this object. We should just populate this object directly
                if ($parameters !== false) {
                    //create the model using the record
                    $this->import_parameters($parameters, true);
                }
                else{
                    $this->is_valid_id = false;
                }

            } else {

                //todo:when is this branch used?
                if(!is_string($parameters)){
                    $this->import_parameters($parameters);
                }
            }
        }
    }

    function table_name(){
        $last_char = substr($this->model, -1);

        if($last_char != 'y')
            $table = $this->model . 's';
        else $table = substr($this->model, 0, -1) . 'ies';

        return $this->from_camel_case($table);
    }

    function is_valid(){
        return isset($this->id) && $this->is_valid_id; //todo:this isn't quite right. is_valid_id will be true if we create a model with no id (i.e. new Project())
    }

    //this returns an array of recordsets, not an array of models
    function get($criteria = null)
    {
        //no criteria = get all
        //id = get this specific item
        //sql use this sql
        if (is_numeric($criteria)) {
            return $this->get_one($criteria);
        } else {
            //the $criteria can be:
            //1. A valid select statement, in which case we just execute the statement,
            //2. The where clause of a select statement, in which case we need to build the rest of the select statement
            //3. Blank, in which case we're just returning all records from this model's table.
            //We need to determine which of these conditions is being met
            $test_criteria = explode(' ', $criteria);

            //Test $criteria to see if it is a select statement
            if($test_criteria[0] == 'SELECT'){
                $sql = $criteria;
            }
            else{
                //The criteria is not a select statement. Let's build the select statement now
                $sql = 'SELECT * FROM ' . $this->table;

                //Add the where clause if it exists
                if (isset($criteria)) {
                    $sql .= ' ' . $criteria;
                }
            }

            $this->log_sql($sql);
            $record_set = $this->db->select($sql);
            $records = $record_set->export();

            //if we're getting a specific item (via the item id, i.e. tasks/23) just return that task, otherwise return an array
            return $records;
        }
    }

    function get_one($idOrSql)
    {
        //todo:there should be better consistency between the types returned by get and get_one. It would be nice if they all always returned objects
        if (is_numeric($idOrSql)) {
            $sql = 'SELECT * FROM ' . $this->table . ' WHERE id = ' . $idOrSql;
        } else $sql = $idOrSql;

        $this->log_sql($sql);

        $record_set = $this->db->select($sql);
        $records = $record_set->export();

        //if we're getting a specific item (via the item id, i.e. tasks/23) just return that task, otherwise return an array
        if (is_array($records) && count($records) > 0)
            return $records[0];
        else return false;
    }

    function run_action($parameters)
    {
        //this line is somewhat unnecessary since the id will have already been set when the model was instantiated in the controller
        $this->id = array_shift($parameters);

        //todo:make sure the user owns the enity before running the action
        if (method_exists(get_class($this), 'action_map'))
            return $this->action_map($parameters);
        else return false;
    }

    function import_parameters($parameters = null, $is_get_operation = false)
    {

        //THE PARAMS ARRAY IS OFTEN BEING FILLED WITH ALL VALUES BECAUSE ALL VALUES ARE RETRIEVED FROM THE SERVER,
        //PASSED TO THE CLIENT, WHICH CREATES A CLIENT SIDE MODEL WITH ALL PARAMETERS. WHEN THAT CLIENT SIDE MODEL IS
        //PASSED BACK TO THE SERVER, IT SENDS ALL PARAMETERS. TO FIX THERE ARE THREE POTENTIAL SOLUTIONS
        //1. DON'T IMPORT PROPERTIES THAT DON'T HAVE A VALUE WHEN SENT FROM THE CLIENT (NOT IDEAL. PROBABLY PREVENTS YOU
        //   FROM SETTING A FIELD WITH A PREVIOUS VALUE TO AN EMPTY STRING
        //2. ONLY SEND PROPERTIES THAT WERE CHANGED ON THE CLIENT SIDE
        //3. ONLY SEND PROPERTIES THAT HAVE A VALUE ON THE CLIENT SIDE


        //if the parameters have already been imported, we don't want to do it again. This condition should only be met
        //if this object is a child of another object (i.e. invoice items on an invoice). The $_POST parameters
        //are used for the top level object (i.e. invoice), the properties for each child object (i.e. invoice item are
        //passed directly into the constructor (pulled from the $_POST parameters). When we attempt to save each
        //individual invoice item in invoice.php, import_parameters will be called again. Since the $_POST parameters
        //still exist, the app will end up overriding the correct parameters (those passed into the constructor) with
        //the parameters of the parent object (located in $_POST). We need to prevent this behavior by only allowing
        //one import

        if ($this->params_imported == true)
            return;

        $class = get_class($this);
        $parameters = isset($parameters) && !empty($parameters) ? $parameters : $_POST;

        //todo:may want to get rid of this entire params functionality. more of a headache than anything else
        foreach ($parameters as $property => $value) {
            $property = is_string($property) ? $this->from_camel_case($property) : $property;

            if (property_exists($class, $property)) {

                if (!is_array($value)) {

                    //when saved to the databsase, optional int properties (i.e. due date), were being set as 0 rather
                    //than null. This wreaked havoc on processing logic (for tasks). May not be necessary once the param
                    //array no longer contains ALL properties on an object.
                    if(empty($value))
                        $value = NULL;

                    $this->$property = $value;


                    //todo:this may be creating more work than it's worth. Forcing us to use model->set rather than setting properties directy on the object
                    //add the property to the parameters array that will be used for sql statements
                    //we need the params array because we may not be saving every property every time (i.e. update).
                    //No need to use all the model properties if we don't have to
                    //------------------------------------------------------
                    //If this is a get operation, i.e. the record exists in the database and we are populating the model
                    //with existing info, we don't need to add this to the params array, because we don't want to re-save
                    //information that we already have
                    if(!$is_get_operation && $property != 'cid') //todo: if
                        $this->params[$property] = $value;
                } else {

                    $this->$property = $value;

                    //todo:reevaluate - removed becasue params werent' added to the params array when objects were created, causing issues with invoice item updates, since we're just saving the base invoice rather than each individual item
                    //$className = ucfirst($this->to_camel_case($property));
                    //$this->$property = $this->import_object($className, $value);
                }
            }
        }


        $this->params_imported = true;
    }

    function import_object($className, $value)
    {
        if (class_exists($className)) {
            return new $className($value);
        } else {
            $className = substr($className, 0, -1);
            if (class_exists($className)) {
                foreach ($value as &$model) {
                    $model = new $className($model);
                }
            }

            return $value;
        }
    }

    function clear_params(){
        $this->previous_params = $this->params;
        $this->params = array();
        $this->params_imported = false;
    }

    /**
     * Translates a camel case string into a string with underscores (e.g. firstName -&gt; first_name)
     * @param    string   $string    String in camel case format
     * @return    string            $str Translated into underscore format
     */
    static function from_camel_case($string)
    {
        $string[0] = strtolower($string[0]);
        return preg_replace_callback('/([A-Z])/', function ($c)
        {
            return '_' . strtolower($c[1]);
        }, $string);
    }

    /**
     * Translates a string with underscores into camel case (e.g. first_name -&gt; firstName)
     * @param    string   $str                     String in underscore format
     * @param    bool     $capitalise_first_char   If true, capitalise the first char in $str
     * @return   string                              $str translated into camel caps
     */
    static function to_camel_case($str, $capitalise_first_char = false)
    {
        if ($capitalise_first_char) {
            $str[0] = strtoupper($str[0]);
        }
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback('/_([a-z])/', $func, $str);
    }

    //todo:why isn't this named set?
    function set($parameter, $value)
    {
        if (property_exists(get_class($this), $parameter)) {
            $this->params[$parameter] = $value;
            $this->$parameter = $value;
            return $value;
        }
        else return false;
    }

    function unset_param($name){
        unset($this->params[$name]);
    }

    function set_tests()
    {
        //todo:should this go on the validator rather than the model?
        $this->validator_tests = isset($this->validator_tests) ? $this->validator_tests : array();
    }

    function init_validator()
    {
        if (!isset($this->validator)) {
            $this->validator = new Validator();
            $this->set_tests();
        }
    }

    //this should be called on each of the individual models after validator_tests has been defined 
    function validate()
    {
        $this->init_validator();

        if (isset($this->validator_tests)) {
            $this->validator->validate($this, $this->validator_tests);

            if (!$this->validator->has_errors())
                return true;
            else return false;
        } else return true;
    }

    function set_error($field, $error)
    {
        $this->init_validator();
        $this->validator->set_error($field, $error);
    }

    function errors($error = null)
    {
        $this->init_validator();

        $errors = $this->validator->errors();

        if(isset($error) && isset($errors[$error]))
            return $errors[$error];
        else return $errors;
    }

    function validation_passed()
    {
        $this->init_validator();

        $this->has_errors = $this->validator->has_errors();

        return !$this->has_errors;
    }

    function save()
    {
        //todo:the relationship between the model and the validator seems messy. Re-evaluate
        $this->import_parameters();
        $this->validate();

        if ($this->validation_passed()) {
            $is_insert = $this->is_new();

            //validate params. We can not save arrays, or objects to the db. It will throw a pdo error
            //so lets remove any invalid params
            foreach($this->params as $key => &$param){
                if(is_object($param) || is_array($param)){
                    unset($this->params[$key]);
                }
            }

            //todo: handle case when there are no params to save, (just return true)??
            if ($is_insert) {
                //if this model has a created date field, set it here
                $this->set('created_date', time());
                $sql = Helpers::insert($this->table, $this->params);


            } else {
                $id = isset($this->id) ? $this->id : $this->params['id'];
                $sql = Helpers::update($this->table, $this->params, "id = '" . $id . "'");
            }

            $this->log_sql($sql);
            $result = $this->db->execute($sql);

            //todo:should I clear the param s array after a save?

            if ($is_insert) {
                $this->id = $this->db->getLastInsertId();
                return array('id' => $this->id);
            }
            else return $result;
        }
        else {
            return $this->validator->errors();
        }
    }

    function is_new(){
       return (!isset($this->params['id']) || empty($this->params['id']))
              && (!isset($this->id) || empty($this->id));
    }

    function insert($params, $table = null){
        $table = isset($table) ? $table : $this->table;

        $sql = Helpers::insert($table, $params);
        $this->log_sql($sql);
        $this->db->execute($sql);

        //returns the id of the newly created row
        return $this->db->getLastInsertId();
    }

    function update($params, $table = null, $criteria = null){
        $table = isset($table) ? $table : $this->table;

        $sql = Helpers::update($table, $params, $criteria);
        $this->log_sql($sql);

        //will return the row count
        return $this->db->execute($sql);
    }

    function select($sql){
        $this->log_sql($sql);

        $record_set = $this->db->select($sql);
        $records = $record_set->export();

        return $records;
    }

    function execute($sql){
        $this->log_sql($sql);

        return $this->db->execute($sql);
    }

    function delete(){
        $this->import_parameters();

        if(!isset($this->id))
            return false;

        $sql = "DELETE FROM " . $this->table . " WHERE id = " . $this->id;

        $this->log_sql($sql);

        $result = $this->db->execute($sql);

        return $result;
    }



    function load($model){
        return new $model;
    }

    //todo:repeated from controller. not dry
    //load the requested library
    function load_library($library){
        require_once (ROOT . DS . 'application' . DS . 'libraries' . DS . $library . '.php');
    }

    function current_user_can_access()    {
        //determine whether a user
        $user = current_user();

        if ($user->role == 'admin')
            return true;
        else {
            return false;
        }
    }

    //used when getting a list of projects
    function modify_sql_for_user_type($sql, $criteria){
        $current_user = current_user();

        if(!$current_user->is('admin')){
            if(!isset($criteria))
                $sql .= " WHERE $this->table.client_id = $current_user->client_id";
        }

        return $sql;
    }

    function to_array()
    {
        //we need an array of the properties defined directly on this object, which doesn't include those on the base model
        //we're not using the base model get function because we're returning THIS object, not retrieving one from the db
        $array = get_object_vars($this);
        $return = array();

        foreach ($array as $parameter => $value) {
            if (!property_exists('Model', $parameter) || $parameter == 'id')
                $return[$parameter] = $value;
        }

        return $return;
    }

    //pdo execute will return the number of rows affected as the result. This function uses that information to determin
    //if the save was successful or not
    static function is_successful($db_result){
        return $db_result != 0;
    }

    function __destruct()
    {
    }

    function log_sql($sql){
        if(get_config('log_queries') != true)
            return false;

        if(!isset($_SESSION['queries']))
            $_SESSION['queries'] = array();

        $_SESSION['queries'][] = $sql;
    }

    static function send_sql_log(){
        if (get_config('log_queries') != true)
            return false;

        if(isset($_SESSION['queries']))
        {
            //todo:smtp
            mail(get_config('company.email'), 'SQL COUNT = ' . count($_SESSION['queries']), pre($_SESSION['queries'], true));
        }


        $_SESSION['queries'] = array();
    }
}
