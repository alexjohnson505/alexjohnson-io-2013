<?php

class Activity extends Model
{

    public $project_id;
    public $client_id;
    public $object_type;
    public $object_id;
    public $user_id;
    public $action_taken;
    public $object_title;
    public $linked_object_type;
    public $linked_object_id;
    public $linked_object_title;
    public $activity_date;



    function __construct($object = null, $action_taken = null, $object_title = null)
    {
        $this->table = 'activity';
        parent::__construct();

        //most activity will be user generated. We will need to manually set this to false for system actions
        $this->is_user_generated = true;

        //Each object will manually create an Activity record. Therefore we don't want to import any values that may be
        //in $_POST, so let's stop import_parameters from running
        $this->params_imported = true;


        //log the activity if the params were passed in the constructor
        if (isset($object) && isset($action_taken)) {
            $this->set_object($object);
            $this->set_action($action_taken);

            if (isset($object_title))
                $this->set_title($object_title);

            if ($this->validate())
                $this->save();

        }
    }

    function save(){
        $this->set('activity_date', time());

        if($this->is_user_generated == true)
            $this->set('user_id', current_user()->id);

        $this->set('object_type', lcfirst($this->object_type));

        if(isset($this->linked_object_type))
        {
            $this->set('linked_object_type', lcfirst($this->linked_object_type));

            if($this->linked_object_type == 'project'){
                $this->set('project_id', $this->linked_object_id);
            }
        }

        parent::save();
    }

    function set_object($object)
    {
        $this->set('object_type', get_class($object));
        $this->set('object_id', $object->id);

        //get the project id
        if(isset($object->project_id))
            $this->set('project_id', $object->project_id);

        //if the object is a project, the project_id is the object id
        if($object instanceof Project)
            $this->set('project_id', $object->id);

        //todo:make sure the client_id is being set, even for messages
        if(isset($object->client_id))
            $this->set('client_id', $object->client_id);
    }

    function set_action($action){
        $this->set('action_taken', $action);
    }

    function set_title($title){
        $this->set('object_title', $title);
    }

    function set_linked_object($object)
    {
        $this->set('linked_object_type', get_class($object));
        $this->set('linked_object_id', $object->id);
    }



    function validate()
    {
        $this->validator_tests = array(
            'object_type' => 'required',
            'object_id' => 'required',
            'action_taken' => 'required'
        );

        return parent::validate();
    }

    function get($criteria = null){
        //todo:make sure the user has access, filter by type, there are probably some activity items clients cant see?
        $sql = "SELECT activity.*,
                       CONCAT(users.first_name, ' ', users.last_name) AS user_name,
                       projects.name AS project_name
                FROM activity
                LEFT JOIN users on activity.user_id = users.id
                LEFT JOIN projects on activity.project_id = projects.id";

        $sql = isset($criteria) ? "$sql $criteria" : $sql;

        //todo: does this make sense for activity? Both clients and admins can access all activity for a project. Plus it looks like it would build invalid sql with two where statements (see projectdetails class)
        $sql = $this->modify_sql_for_user_type($sql, $criteria);

        $sql .= " ORDER BY activity_date DESC LIMIT 100";

        $activity = parent::get($sql);

        User::set_profile_images($activity);

        return $activity;
    }


}
 
