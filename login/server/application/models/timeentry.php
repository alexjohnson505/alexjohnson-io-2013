<?php

class TimeEntry extends Model{
    public $time;
    public $task_id;
    public $client_id;
    public $user_id;
    public $start_date;

    function __construct($parameters = null){
        $this->import_parameters();

        $this->task = new Task($this->task_id);

        parent::__construct($parameters);
    }

    function validate(){
        $this->validator_tests = array(
            'time' => 'required',
            'task_id' => 'required',
            'user_id' => 'required',
            'start_date' => 'required'
        );

        return parent::validate();
    }

    function save(){
        //todo: enforce ownership
        $this->import_parameters();

        if(!$this->valid_time())
            return false;

        //we need to enforce the user and the start date on the server side, regardless of what's passed on the client
        //side
        $user = current_user();
        $this->set('user_id', $user->id);

        if($this->is_new()){
            $this->set('start_date', time());
        }

        //save the time entry
        $time_entry_result = parent::save();

        //update the total time on the task

        $this->task->update_total_time();

        return $time_entry_result;
    }

    function valid_time(){

        $is_time_in_seconds_set = isset($this->time) && (int)$this->time > 0;

        if(!$is_time_in_seconds_set && $this->has_valid_time_components()){
            $time = 0;

            $hours = $_POST['hours'];
            $minutes = $_POST['minutes'];
            $seconds = $_POST['seconds'];

            $time = (int)$hours * 3600;
            $time += (int)$minutes * 60;
            $time += $seconds;

            $this->set('time', $time);

            if($this->time > 0)
                return true;
            else return false;
        }
        else if($is_time_in_seconds_set == true){
            return true;
        }
        else return false;

    }

    function has_valid_time_components(){
        return isset($_POST['hours']) && isset($_POST['minutes']) && isset($_POST['seconds']);
    }

    function delete(){
        $delete_result = parent::delete();

        $task = new Task($this->task_id);
        $task->update_total_time();

        return $delete_result;
    }

    function current_user_can_access(){
        $user = current_user();

        if($user->is('admin') || $user->client_id == $this->task->client_id)
            return true;
        else return false;
    }



}
