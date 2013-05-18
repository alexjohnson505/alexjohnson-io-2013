<?php
use phpSweetPDO\SQLHelpers\Basic as Helpers;

Class Message extends Model
{
    public $message;
    public $reference_object;
    public $reference_id;
    public $project_id;
    public $client_id;
    protected $user_id;
    protected $created_date;

    //not saved to the db
    public $linked_object_title;
    public $linked_object;

    function validate(){
        $this->validator_tests = array(
            'message' => 'required',
            'reference_object' => 'required',
            'reference_id' => 'required'
        );

        return parent::validate();
    }

    function save(){
        $this->import_parameters();


        $reference_object = new $this->reference_object($this->reference_id);

        //if the message is being created for an object other than a project, then the project id will be retrieved from
        //the actual object
        //if the message is being posted on a project, then the project id is the messages reference_id
        if($this->reference_object != 'project')
            $project_id = isset($reference_object->project_id) ? $reference_object->project_id : false;
        else $project_id = $this->reference_id;

        if($project_id)
            $this->set('project_id', $project_id);

        if(isset($reference_object->client_id))
            $this->set('client_id', $reference_object->client_id);


        $this->set('user_id', current_user()->id);

        //these two parameters shouldn't be set yet (they are set when we log activity which happens after the save),
        //but let's just make sure
        $this->unset_param('linked_object');
        $this->unset_param('linked_object_title');

        $result = parent::save();

        $this->log_activity();

        $email = new AppEmail();
        $email->send_message_notification(array_merge($this->to_array(), array(
            'linked_object_title' => $this->linked_object_title,
            'linked_object_url' => Request::param('message_url'),
            'linked_object_type' => strtolower(get_class($this->linked_object))
        )));

        return $result;
    }

    function log_activity(){
        $activity = new Activity();
        $activity->set_object($this);
        $activity->set('object_title', $this->message);
        $activity->set('action_taken', 'posted');

        $this->linked_object = new $this->reference_object($this->reference_id);
        $activity->set_linked_object($this->linked_object);

        $this->linked_object_title = $this->get_activity_title($this->linked_object);
        $activity->set('linked_object_title', $this->linked_object_title);

        $activity->save();
    }

    function get_activity_title($object){
        //get the activity title
        switch (get_class($object)) {
            case 'File':
                $title_field = 'name';
                break;
            case 'Invoice':
                $title_field = 'number';
                break;
            case 'Project':
                $title_field = 'name';
                break;
            case 'Task':
                $title_field = 'task';
                break;
            default:
                $title_field = '';
                break;
        }

        return isset($object->$title_field) ? $object->$title_field : '';
    }

    function get($reference_object, $reference_id)
    {
        $reference_table = $reference_object . "s";
        $name_field  = $this->name_field($reference_object);
        $name_field  = $this->name_field($reference_object);

        $sql = "SELECT messages.*, CONCAT(users.first_name, ' ', users.last_name) AS user_name, $reference_table.$name_field AS entity_name
                FROM messages
                LEFT JOIN users ON messages.user_id = users.id
                LEFT JOIN $reference_table ON messages.reference_id = $reference_table.id
                WHERE reference_object = '$reference_object' AND reference_id = '$reference_id'";


        $sql = $this->modify_sql_for_user_type($sql, null);
        $messages = parent::get($sql);

        User::set_profile_images($messages);

        return $messages;
    }

    //used when getting a list of projects
    function modify_sql_for_user_type($sql){
        $current_user = current_user();

        if(!$current_user->is('admin')){
                $sql .= " AND $this->table.client_id = $current_user->client_id";
        }

        return $sql;
    }

    function name_field($object){
        if($object == 'invoice')
            $name = 'number';
        else if ($object == 'task')
            $name = 'task';
        else $name = 'name';

        return $name;
    }

    function current_user_can_access(){
        $user = current_user();

        if($this->is_new())
            return true;

        if($user->role == 'admin' || $user->client_id == $this->client_id)
            return true;
        else return false;
    }
}
 
