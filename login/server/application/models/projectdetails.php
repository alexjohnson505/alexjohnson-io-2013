<?php

class ProjectDetails extends Model{
    public $activity;
    public $project;
    public $task_counts;


    function get($project_id){
        $activity = new Activity();
        $activity = $activity->get('WHERE project_id = ' . $project_id);

        $this->project = new Project($project_id);;

        $task_counts = $this->project->get_task_counts();

        $this->project->update_status($task_counts);

        $project_details = array(
            'project' => $this->project->to_array(),
            'activity' => $activity,
            'task_counts' => $task_counts
        );

        return $project_details;
    }

    //project details is a collection of other models. There is nothing to save, so let's prevent the base model save
    //from being called accidentally
    function save(){}

    function current_user_can_access(){
        $user = current_user();

        //this->project is now an array, because of the call to to_array in the get function
        if($user->role == 'admin' || $user->client_id == $this->project['client_id'])
            return true;
        else return false;
    }
}