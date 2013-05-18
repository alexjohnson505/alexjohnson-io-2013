<?php

class Dashboard extends Model
{
    protected $projects;
    protected $activity;

    function get(){
        $user = current_user();
        $projects = new Project();
        $activity = new Activity();

        $this->projects = $projects->get();
        $this->activity = $activity->get();
    }

    function current_user_can_access(){
        return true;
    }
}