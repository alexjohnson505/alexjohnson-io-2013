<?php

use phpSweetPDO\SQLHelpers\Basic as Helpers;

Class Client extends Model
{
    public $name;
    public $email;
    public $address1;
    public $address2;
    public $phone;
    public $website;
    public $primary_contact_id;

    //not saved to the db
    public $primary_contact_name;
    public $primary_contact_image;

    
    function validate(){
        $this->validator_tests = array(
            'name' => 'required'
        );

        return parent::validate();
    }

    function save(){
        $this->import_parameters();

        //we need to determine if this is a new client before we do the initial save (it won't be new once we do the
        //initial save
        $is_new = $this->is_new();

        //unset params that aren't saved to the db
        $this->unset_param('primary_contact_name');
        $this->unset_param('primary_contact_image');

        $result = parent::save();

        if($is_new == true){

            $primary_contact = new User($_POST['client']);
            $primary_contact->set('client_id', $this->id);
            $primary_contact->validate();

            if($primary_contact->validation_passed()){
                $primary_contact->save();

                //set the client email and the primary contact id
                $this->set('email', $primary_contact->email);
                $this->set('primary_contact_id', $primary_contact->id);
                parent::save();
            }
            else {
                $this->set_error('first_name', 'Error saving primary contact');
                return false;
            }

        }

        return $result;
    }

    function get($criteria = null){
        if (is_numeric($criteria)) {
            $sql = "SELECT clients.*, CONCAT(users.first_name, ' ', users.last_name) AS primary_contact_name
                    FROM clients
                    LEFT JOIN users on clients.primary_contact_id = users.id
                    WHERE clients.id = $criteria";

            $client = parent::get_one($sql);
            $client['primary_contact_image'] = User::get_profile_image($client['primary_contact_id'], true);

            return $client;
        }
        else {
            if(!current_user()->is('admin'))
                return false;

            //if we're not getting a specific file, the base get function is fine
            return parent::get($criteria);
        }
    }


    function get_users(){
    //todo:lockdown
        $sql = "SELECT users.id, users.email, CONCAT(users.first_name, ' ', users.last_name) AS name, role_user.role_id
                FROM users
                LEFT JOIN role_user
                  ON role_user.user_id = users.id
                WHERE client_id = $this->id";

        return parent::get($sql);
    }

    function get_projects(){
        $sql = "SELECT projects.id, projects.name, projects.due_date, projects.status_text
                FROM projects
                WHERE projects.client_id = $this->id";

        return parent::get($sql);
    }

    function get_invoices(){
        $project_query = "SELECT projects.id
                          FROM projects
                          WHERE client_id = $this->id";

        $sql = "SELECT invoices.*, projects.name AS project_name
                          FROM invoices
                          LEFT JOIN projects ON invoices.project_id = projects.id
                          WHERE invoices.project_id IN($project_query)";


        return parent::get($sql);
    }

    function current_user_can_access(){
        $user = current_user();

        //only admins or the the client that this model represents can access this model
        if($user->role == 'admin' || $user->client_id == $this->id)
            return true;
        else return false;
    }

    function delete_projects(){
        $projects = $this->get_projects();

        foreach($projects as $project){
            $this_project = new Project($project['id']);
            $this_project->delete();
        }
    }

    function delete_users(){
        $users = $this->get_users();

        foreach($users as $user){
            $this_user = new User($user['id']);
            $this_user->delete();
        }
    }

    function delete(){
        $result = parent::delete();

        $this->delete_projects();

        $this->delete_users();

        new Activity($this, 'deleted', $this->name);

        return $result;
    }
}
 
