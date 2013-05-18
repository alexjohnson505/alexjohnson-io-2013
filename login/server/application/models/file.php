<?php

Class File extends Model
{
    public $name;
    public $project_id;
    public $client_id;
    public $entity_type;
    public $entity_id;
    public $uploader_id;
    public $size;
    public $notes;
    public $created;

    //not saved to db
    public $uploaded_by;


    function validate(){
        $this->validator_tests = array(
            'name' => 'required'
        );

        return parent::validate();
    }

    static function generate_base_sql_for_get(){
        return "SELECT files.*, CONCAT(users.first_name, ' ', users.last_name) AS uploaded_by, projects.name AS project
                FROM files
                LEFT JOIN users
                ON files.uploader_id = users.id
                LEFT JOIN projects
                ON files.project_id = projects.id";
    }

    function get($criteria = null){
        if (is_numeric($criteria)) {
            $sql = $this->generate_base_sql_for_get() . " WHERE files.id = $criteria";

            return parent::get_one($sql);
        }
        else {
            //if we're not getting a specific file, the base get function is fine
            return parent::get($this->modify_sql_for_user_type('', $criteria));
        }
    }

    static function upload_notification($project, $files){
        $email = new AppEmail();
        $email->send_file_upload_notification($project, $files);
    }

    function save(){
        $this->unset_param('uploaded_by');
        return parent::save();
    }

    function allow_client_uploads(){
        return get_config('uploads.allow_client_uploads') == true;
    }

    function upload()
    {
        $this->set_upload_headers();

        if(!isset($_POST['object']) || !isset($_POST['id']) )
            return false;

        //the file might be uploaded to an object that isn't a project (i.e. a task), so we need to figure out the project id
        $context = Project::context($_POST['object'], $_POST['id']);

        if(!isset($context['project_id']))
            return false;

        $project_id = $context['project_id'];


        $project = new Project($project_id);

        if(!current_user()->can_access($project))
            return false;

        if(!current_user()->is('admin') && !$this->allow_client_uploads())
            return false;


        //we need to set the project id, before we generate the upload paths
        $this->set('project_id', $project_id);
        $this->set('client_id', $project->client_id);

        //perform the upload
        $upload_options = $project->file_paths();
        $upload = new Upload(array(
            'upload_dir' => $upload_options['upload_path'],
            'upload_url' => $upload_options['web_path']
        ));
        $status = $upload->post();
        $status = $status[0];

        if ($status->size > 0) {
            $name = $status->name;


            $this->set('entity_type', $context['entity_type']);
            $this->set('entity_id', $context['entity_id']);
            $this->set('uploader_id', current_user()->id);
            $this->set('name', $name);
            $this->set('size', $status->size);
            $this->set('created', time());

            //prevent the save function from calling import_parameters. This would cause the object id that is sent
            //as a post parameter to be imported as the id of this file, and then the model would try to do an update.
            //this is not the desired behavior. This is a new file and does not have an id yet...
            $this->params_imported = true;
            $this->save();

            new Activity($this, 'uploaded', $this->name);

            return true;
        } else return false;
    }

    static function set_upload_headers()
    {
        error_reporting(E_ALL | E_STRICT);
        header('Pragma: no-cache');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Content-Disposition: inline; filename="files.json"');
        header('X-Content-Type-Options: nosniff');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, DELETE');
        header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');
    }

    function is_download($do_download = null){
        return isset($do_download) && $do_download == 'do';
    }

    static function do_download($file_path){
        //turn off error reporting to prevent the document from being corrupted
        error_reporting(0);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($file_path));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        ob_clean();
        flush();
        readfile($file_path);
        exit;
    }

    function download($do_download = null)
    {
        $project = new Project($this->project_id);

        if($project->is_valid())
        {
            $paths = $project->file_paths();
        }


        if (isset($paths) && file_exists($paths['upload_path'] . $this->name)) {

            if (!$this->is_download($do_download)) {
                return $paths['web_path'] . $this->name;
            } else {
                $this->do_download($paths['upload_path'] . $this->name);
                return true;
            }
        } else return false; //return false;
    }

    function delete(){
        $result = parent::delete();

        //delete the file from the server
        $project = new Project($this->project_id);

        if($project->is_valid()){
            $paths = $project->file_paths();

            if (isset($paths) && file_exists($paths['upload_path'] . $this->name)) {
                $file = $paths['upload_path'] . $this->name;
                unlink($file);
            }
        }

        new Activity($this, 'deleted', $this->name);

        return $result;
    }

    function current_user_can_access(){
        $user = current_user();

        if($user->role == 'admin' || $user->client_id == $this->client_id)
            return true;
        else return false;
    }
}