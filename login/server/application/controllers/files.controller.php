<?php

Class FilesController extends Controller{
    function upload(){

        $file = new File();


        //we check access in the upload function
        if($file->upload())
            Response($file);
        else Response()->error('There was an error uploading the file'); //todo:language file
    }

    function download($id, $init_download = null){
        $file = new File($id);

        if(!current_user()->can_access($file))
            Response()->not_authorized();

        if($file->is_valid())
        {
            $path = $file->download($init_download);

            if ($path !== false)
                Response($path);
            else Response()->error('There was an error downloading the file');
        }
        else Response()->invalid_model($id);
    }

    function upload_notification(){
        $files = Request::param('files');
        $project = new Project($files[0]['project_id']);

        if(!current_user()->can_access($project))
            Response()->not_authorized();

        File::upload_notification($project, $files);
    }
}

 
