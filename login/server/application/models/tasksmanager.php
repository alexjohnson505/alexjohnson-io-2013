<?php

class TasksManager extends Model
{
    public $order;
    public $original_order;
    public $project_id;

    function __construct($parameters = null){
        $this->import_parameters();

        parent::__construct($parameters);
    }

    function save()
    {
        $this->import_parameters();
        $i = 0;

        foreach($this->order as $task_at_position){
            //if there is currently no task at this position (i.e. new task added at end of  list) update order
            //OR if this position has a task that is different from the original order, then we need to update the task's order field
            //todo: use client id if id isn't available?
            if(!isset($this->original_order[$i]) || ($this->original_order[$i] != $task_at_position)){
                if(isset($task_at_position) && !empty($task_at_position))
                    $this->update(array('order' => $i), 'tasks', "id = $task_at_position");
            }

            $i++;
        }
    }


    function save_new($new_task_id){

        foreach($this->order as &$task_at_position){
            if(empty($task_at_position)){
                $task_at_position = $new_task_id;
                break;
            }
        }

        $this->save();
    }

    function current_user_can_access(){
        $user = current_user();

        $project = new Project($this->project_id);

        if($user->role == 'admin' || $user->client_id == $project->client_id)
            return true;
        else return false;
    }

    function get(){
        //override the default get function, since there is currently no functionality in the app to view a payment
        //or list payments
    }
}
 
