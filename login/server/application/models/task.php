<?php
use phpSweetPDO\SQLHelpers\Basic as Helpers;

Class Task extends Model
{
    public $task;
    public $notes;
    public $is_complete;
    public $is_section;
    public $assigned_to;
    public $project_id;
    public $client_id; //todo: i may not be using this
    public $created_by;
    public $due_date;
    public $weight;
    public $status_text;
    public $is_overdue;
    public $is_invoiced;
    public $invoice_id;
    public $completed_by;
    public $completed_date;
    public $total_time;
    public $created_date;
    public $modified_date;

    //params not saved to the db
    public $assigned_to_name;


    function validate(){
        $this->validator_tests = array(
            'task' => 'required'
        );

        return parent::validate();
    }

    function get($criteria = null){
        $sql = "SELECT tasks.*, CONCAT(users.first_name, ' ', users.last_name) AS assigned_to_name
                FROM tasks
                LEFT JOIN users ON users.id = tasks.assigned_to";

        if(isset($criteria) && is_numeric($criteria))
        {
            $sql .= " WHERE tasks.id = $criteria";
            $task =  parent::get_one($sql);

            $this->update_status();

            return $task;
        }
        else {
            $sql .= " $criteria";
            $sql = $this->modify_sql_for_user_type($sql, $criteria);

            $tasks =  parent::get($sql);

            foreach($tasks as &$task){
                //create the invoice
                $task_object = new Task($task);
//todo: i don't think this makes any sense because the invoice status will already be updated when I create the invoice using new Invoice
                //store the current task status as it exists in the database
                $old_status = $task_object->status_text;

                //update the task status
                $task_object->update_status();

                //if the old status and the new status do not match, then we need to save this task back to the database.
                if ($old_status != (string)$task_object->status_text) {
                    //todo:make sure this isn't saving each time
                    $task_object->save(false);
                }

                //we need to add the status text back the task array, since we're sending the array to the client,
                //not the object
                $task['status_text'] = $task_object->status_text;
            }
            return $tasks;
        }
    }

    function update_status(){

        $today = strtotime('today midnight');
        $at_risk_timeframe = get_config('task.at_risk_timeframe');
        $at_risk_startpoint =  strtotime("+$at_risk_timeframe days midnight");

        if($this->is_complete == true){
            $this->set('status_text', '');
            $this->set('is_overdue', false);
            return;
        }

        //due_date == 0 condition is a hack until we figure out how to handle
        if(!isset($this->due_date) || $this->due_date == 0){
            $this->set('status_text', '');
            $this->set('is_overdue', false);
        }
        else if($this->due_date < $today){
            $this->set('status_text', 'Overdue');
            $this->set('is_overdue', true);
        }
        else if($this->due_date >= $today && $this->due_date <= $at_risk_startpoint){
            $this->set('status_text', 'At Risk');
            $this->set('is_overdue', false);
        }
        else {
            $this->set('status_text', 'On Schedule');
            $this->set('is_overdue', false);
        }
    }

    function prep_fields_for_db(){
        //convert boolean values to 1/0 for the db. The client will send them as strings
        $is_complete = isset($_POST['isComplete']) && $_POST['isComplete'] == 'true' ? 1 : 0;
        $this->set('is_complete', $is_complete);

        $is_section = isset($_POST['isSection']) && $_POST['isSection'] == 'true' ? 1 : 0;
        $this->set('is_section', $is_section);
    }

    function save($process_task = true){
        $current_user = current_user();

        //we need to import parameters before we set the value, otherwise the manipulated values will be overridden
        $this->import_parameters();

        //todo:should we check that the user is the owner of this project or is the task owner function enough
        $project = new Project($this->project_id);

        //let's set the client id even though it's sometimes passed
        $this->set('client_id', $project->client_id);

        //there are some cases when we don't want to do all of this additional processing (i.e. when we're just
        //updating the status (overdue, on schedule, etc)
        if($process_task == false){
            //unset params that aren't saved to the db
            $this->unset_param('assigned_to_name');
            return parent::save();
        }

        $update_tasks_manager = false;

        //unset params that aren't saved to the db
        $this->unset_param('assigned_to_name');

        $this->prep_fields_for_db();


        if(!isset($this->assigned_to))
            $this->set('assigned_to', $current_user->id);

        if($this->is_new()){
            $is_new = true;
            $update_tasks_manager = true;

            $this->set('created_by', $current_user->id);
        }
        else {
            $is_new = false;

            //we need to compare the existing value to the new values
            //todo:we can eliminate the need for this extra set of queries if we create a 'previous values' array on the model that's set each time we call clear params. Too many queries when saving tasks.
            $existing_values = new Task($this->id);

            //are we marking the task complete? (i.e. it was previously set as incomplete, but we're not setting it as
            //complete
            if((bool)$existing_values->is_complete == false && (bool)$this->is_complete == true){
                $this->set('completed_date', time());
                $this->set('completed_by', $current_user->id);
                new Activity($this, 'completed', $this->task);
            }
            else if ((bool)$existing_values->is_complete == true && (bool)$this->is_complete == false){
                //the task was previously complete, but we're setting it back to active
                $this->set('completed_date', NULL);
            }

            //are we assigning this task to someone new?
            if(isset($this->assigned_to) && ($this->assigned_to != $existing_values->assigned_to)){
                $assigned_to_user = new User($this->assigned_to);

                new Activity($this, 'reassigned', "Assigned to $assigned_to_user->first_name $assigned_to_user->last_name");

                $email = new AppEmail();
                $email->send_task_assignment(array(
                    'task' => $this,
                    'user' => $assigned_to_user
                ));
            }
        }

        //save the invoice in the database
        $result = parent::save();

        //add the task to the activity stream if it's new
        if($is_new){
            new Activity($this, 'created', $this->task);
        }

        //update teh task order, if this is a new task (existing task reorders generate a call directly to the taskmanger
        //class so they don't need to be handled here
        if($update_tasks_manager == true){
            //newOrder, and oldOrder will only be set if the task is created from task list, not from the modal
            if(isset($_POST['newOrder']) && isset($_POST['oldOrder'])){
                $tasks_manager = new TasksManager();
                //normalize names using order/original order, newOrder/oldOrder, and order/newOrder or something
                $tasks_manager->order = $_POST['newOrder'];
                $tasks_manager->original_order = $_POST['oldOrder'];
                $tasks_manager->save_new($this->id);
            }
        }

        //todo:it would be nice if this only ran if the weight or complete status changed
        $project->update_progress();

        return $result;
        //todo: (all this goes in the controller - the functionality described by these words goes in the controller) this needs to 1) send back an error if validation has failed on the model 2)send an error if the sql has failed 3) only send success if neither of the first two cases happened. There should be one central function for this that we can use for all functions that call Model::save()
        //Response($result);
    }


    function get_files(){
        //todo: it would be good if calls to get_files and calls to get_time entries didn't initiate calls to update status. This is true of other entities too (i.e. projects)
        $sql = File::generate_base_sql_for_get() . " WHERE entity_type = 'task' AND entity_id = " . $this->id;

        return parent::get($sql);
    }

    function get_time_entries(){

        $sql = "SELECT time_entries.*, CONCAT(users.first_name, ' ', users.last_name) as user_name
                FROM time_entries
                LEFT JOIN users on time_entries.user_id = users.id
                WHERE task_id = $this->id";

        return parent::get($sql);
    }

    function update_total_time(){
        $total_time = 0;

        //get the time entries for this task
        $time_entries = new TimeEntry();
        $time_entries = $time_entries->get("WHERE task_id = $this->id");

        if(is_array($time_entries)){
            //add each time entry to the total
            foreach($time_entries as $time_entry){
                $total_time += (int)$time_entry['time'];
            }

        }

        $this->set('total_time', $total_time);

        //we don't need to do any of the extra processing perfomed by the task save dunction
        return parent::save();
    }

    function delete(){
        $result = parent::delete();
        $this->update_project_progress();

        new Activity($this, 'deleted', $this->task);

        return $result;
    }

    function current_user_can_access(){
        $user = current_user();

        if($user->role == 'admin' || $user->client_id == $this->client_id)
            return true;
        else return false;
    }
}
 
