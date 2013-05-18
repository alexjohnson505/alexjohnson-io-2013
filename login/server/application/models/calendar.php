<?php


class Calendar extends Model{
    public $tasks;
    public $project_id;
    public $project;


    function get($project_id){
        $this->project = new Project($project_id);

        //todo:this should not get section headers
        $this->tasks = $this->project->get_tasks('incomplete');
    }

    function current_user_can_access(){
        $user = current_user();

        //this->project is now an array, because of the call to to_array in the get function
        if($user->role == 'admin' || $user->client_id == $this->project->client_id)
            return true;
        else return false;
    }
}
 
