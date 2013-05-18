<?php

class GlobalSearch extends Model{

    public $query;
    public $projects;
    public $tasks;
    public $invoices;
    public $files;
    public $clients;
    public $messages;

    function do_search($query){

       $this->query = $query;

        $this->get_projects();
        $this->get_tasks();
        $this->get_invoices();
        $this->get_files();
        $this->get_clients();
        $this->get_messages();

       return $this->to_array();
    }

    function modify_sql_for_user_type($sql, $type){
        $current_user = current_user();

         if(!$current_user->is('admin')){
             $sql .= " AND $type.client_id = $current_user->client_id";
         }

        return $sql;
    }

    function get_projects(){
        $sql = "SELECT projects.id, projects.name, projects.status_text, projects.due_date, clients.name AS client_name
                FROM projects
                LEFT JOIN clients
                  ON clients.id = projects.client_id
                WHERE
                  (
                      projects.name LIKE '%$this->query%'
                      OR clients.name LIKE '%$this->query%'
                  )";


        $sql = $this->modify_sql_for_user_type($sql, 'projects');
        $this->projects = parent::get($sql);
    }

    function get_tasks(){
        $sql = "SELECT tasks.id, tasks.task, tasks.notes, tasks.project_id, tasks.due_date, projects.name AS project_name
                FROM tasks
                LEFT JOIN projects
                  ON projects.id = tasks.project_id
                WHERE
                  (
                  tasks.task LIKE '%$this->query%'
                  OR tasks.notes LIKE '%$this->query%'
                  )";

        $sql = $this->modify_sql_for_user_type($sql, 'tasks');
        $this->tasks = parent::get($sql);
    }

    function get_invoices(){
        $sql = "SELECT invoices.*, clients.name AS client_name, projects.name AS project_name
                FROM invoices
                LEFT JOIN clients
                  ON clients.id = invoices.client_id
                LEFT JOIN projects
                  ON projects.id = invoices.project_id
                WHERE
                  (
                  clients.name LIKE '%$this->query%'
                  OR projects.name LIKE '%$this->query%'
                  )";

        $sql = $this->modify_sql_for_user_type($sql, 'invoices');
        $this->invoices = parent::get($sql);
    }

    function get_files(){
        $sql = "SELECT files.id, files.name, files.project_id,  files.type AS file_type, projects.name AS project_name
                FROM files
                LEFT JOIN projects
                  ON projects.id = files.project_id
                WHERE
                  (
                  files.name LIKE '%$this->query%'
                  OR files.type LIKE '%$this->query%'
                  )";

        $sql = $this->modify_sql_for_user_type($sql, 'files');
        $this->files = parent::get($sql);
    }

    function get_clients(){}

    function get_messages(){
        $sql = "SELECT messages.*
                FROM messages
                WHERE
                  messages.message LIKE '%$this->query%'";

        $sql = $this->modify_sql_for_user_type($sql, 'messages');
        $this->messages = parent::get($sql);
    }
}