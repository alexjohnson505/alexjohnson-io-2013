<?php

use phpSweetPDO\SQLHelpers\Basic as Helpers;

Class User extends Model
{

    public $client_id;
    public $first_name;
    public $last_name;
    public $email;
    public $address1;
    public $address2;
    public $phone;
    public $role;
    public $temp_password;

    //not saved to the db
    public $profile_image;
    public $client_name;

    function validate(){
        $this->validator_tests = array(
            'first_name' => 'required',
            'email' => 'required'
        );

        return parent::validate();
    }


    function save(){
        $this->import_parameters();

        if($this->is_new()){
            return $this->create();
        }
        else {
            $current_user = current_user();

            if(!$current_user->is('admin') && $current_user->id != $this->id)
                return false;

            $this->unset_param('profile_image');
            $this->unset_param('role');
            $this->unset_param('client_name');
            return parent::save();
        }
    }


    function new_admin(){
        if(!current_user()->is('admin'))
            return false;

        $this->import_parameters();
        $this->role = 1;
        return $this->create();

    }

    function create(){
        $auth = new Auth();

        if($auth->username_available($this->email)){
            parent::save();

            //if the role isn't set (by the new_admin function), then this is a client
            if(!isset($this->role))
                $this->role = 2;

            //only an admin can create a new admin
            if($this->role == 1 && !current_user()->is('admin'))
                return false;

            $registration = $auth->register($this->id, $this->role);

            if($registration['result'] != false){

                $email = new AppEmail();

                $email->send_new_user($this->email, array(
                    'email'=>$this->email,
                    'temporary_password' => $registration['temporary_password']
                ));

                return true;
            }
            else return true;
        }
        else $this->set_error('email', 'A user with this email address already exists'); //todo:lang

    }

    function get($criteria = null)
    {
        if (is_numeric($criteria)) {
            //if we are getting a specific user, we want to get their role info
            //TODO: this only selects the first role. Need to change this if multiple roles are ever applied to one user
            //TODO: there should be a standard way to do this
            //TODO: this is including the username, password, salt in the user object - big no no
            $sql = "SELECT users.*, role_user.role_id, clients.name AS client_name, roles.name AS role FROM users
                    LEFT JOIN role_user
                      ON role_user.user_id = '$criteria'
                    LEFT JOIN roles
                      ON roles.id = role_user.role_id
                    LEFT JOIN clients
                      ON clients.id = users.client_id
                    WHERE users.id = '$criteria'";

            $params = parent::get_one($sql);

            $params->profile_image = $this->get_profile_image($params->id, false);

            return $params;
        } else {

            //we don't need the user's role info if we're getting a list so let's use the get function on the base model
            return parent::get($this->modify_sql_for_user_type('', $criteria));
        }
    }

    function client_side()
    {
        return array(
            'role' => $this->role,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'image' => $this->get_profile_image($this->id),
            'id' => $this->id,
            'requires_password_reset' => !empty($this->temp_password)
        );
    }

    function is($role)
    {
        return $this->role == strtolower($role);
    }

    static function get_profile_image($user_id, $is_thumb = true)
    {
        $image_path = glob(get_config('uploads.user_images_path') . "$user_id.*");
        if (isset($image_path[0])) {
            $ext = pathinfo($image_path[0], PATHINFO_EXTENSION);
            $base = get_config('uploads.user_images_web_path');

            if(!$is_thumb){
                $image_url = $base . "$user_id.$ext";
            }
            else  $image_url = $base . "thumbs/$user_id.$ext";

        } else {
            if(!$is_thumb){
                $image_url = get_config('unknown_user');
            }
            else $image_url = get_config('unknown_user_thumb');
        }

        return $image_url;
    }

    function delete_existing_images($user_id){
        $images = glob(get_config('uploads.user_images_path') . "$user_id.*");
        $thumbs = glob(get_config('uploads.user_images_path') . "thumbs/$user_id.*");

        if(is_array($images)){
            foreach($images as $image){
                if(is_file($image))
                    unlink($image);
            }
        }

        if(is_array($thumbs)){
            foreach($thumbs as $image){
                if(is_file($image))
                    unlink($image);
            }
        }
    }

    function set_profile_image()
    {
        $this->delete_existing_images($this->id);

        //save references to the upload paths
        $upload_path = get_config('uploads.user_images_path');
        $web_path = get_config('uploads.user_images_web_path');

        File::set_upload_headers();


        //make the directories for the user images if they do not exist
        if (!is_dir($upload_path))
            mkdir($upload_path, 0777);

        if (!is_dir($upload_path . 'thumbs/'))
            mkdir($upload_path . 'thumbs/', 0777);

        //get the extension of the original image
        $ext = pathinfo($_FILES['files']['name'], PATHINFO_EXTENSION);

        //generate the filename for the image (user id + original extionsion) i.e. 1.jpg
        $name = isset($ext) && !empty($ext) ? "$this->id.$ext" : $this->id;

        //perform the upload
        $upload = new Upload(array(
            'upload_dir' => $upload_path,
            'upload_url' => $web_path,
            'file_name' => $name,
            'overwrite_existing' => true
        ));

        $status = $upload->post();

        $status = $status[0];

        if ($status->size > 0) {
            //we need to resize the images, the large size, and the thumb size
            $this->resize($upload_path . "$name", $upload_path . "$name", 200, 100);
            $this->resize($upload_path . "$name", $upload_path . "thumbs/$name", 80, 90);
            return true;
        } else return false;
    }

    //pulled from acp
    function resize($src_image, $dest_image, $thumb_size = 64, $jpg_quality = 90)
    {
        $image = getimagesize($src_image);

        if ($image[0] <= 0 || $image[1] <= 0) return false;
        $image['format'] = strtolower(preg_replace('/^.*?\//', '', $image['mime']));

        switch ($image['format']) {
            case 'jpg':
            case 'jpeg':
                $image_data = imagecreatefromjpeg($src_image);
                break;
            case 'png':
                $image_data = imagecreatefrompng($src_image);
                break;
            case 'gif':
                $image_data = imagecreatefromgif($src_image);
                break;
            default:
                // Unsupported format
                return false;
                break;
        }


        if ($image_data == false) return false;

        // Calculate measurements
        if ($image[0] > $image[1]) {
            // For landscape images
            $x_offset = ($image[0] - $image[1]) / 2;
            $y_offset = 0;
            $square_size = $image[0] - ($x_offset * 2);
        } else {
            // For portrait and square images
            $x_offset = 0;
            $y_offset = ($image[1] - $image[0]) / 2;
            $square_size = $image[1] - ($y_offset * 2);
        }

        // Resize and crop
        $canvas = imagecreatetruecolor($thumb_size, $thumb_size);
        if (imagecopyresampled($canvas, $image_data, 0, 0, $x_offset, $y_offset,
            $thumb_size, $thumb_size, $square_size, $square_size)
        ) {

            // Create thumbnail
            switch (strtolower(preg_replace('/^.*\./', '', $dest_image))) {
                case 'jpg':
                case 'jpeg':
                    return imagejpeg($canvas, $dest_image, $jpg_quality);
                    break;
                case 'png':
                    return imagepng($canvas, $dest_image);
                    break;
                case 'gif':
                    return imagegif($canvas, $dest_image);
                    break;
                default:
                    // Unsupported format
                    return false;
                    break;
            }
        } else {
            return false;
        }
    }


    static function set_profile_images($array, $is_thumb = true){
        foreach($array as &$array_item){
            $array_item['user_image'] =  User::get_profile_image($array_item['user_id'], $is_thumb);
        }
    }

    function forgot_password(){
        $auth = new Auth();

        $temp_password = $auth->forgot_password($this->email);

        $email = new AppEmail();
        $result = $email->send_forgot_password($this->email, array(
            'temp_password' => $temp_password
        ));

        return $result;
    }

    function change_password(){
        $auth = new Auth();

        $current_password = Request::param('current_password');
        $new_password = Request::param('new_password');
        $new_password_confirm = Request::param('new_password_confirm');

        if($new_password != $new_password_confirm)
            $this->set_error('new_password', 'New password values do not match');


        $result = $auth->change_password($this->id, $current_password, $new_password, $new_password_confirm);

        if($result !== true){
            $this->set_error('new_password', 'There was an error changing your password');
        }
        else{
            $email = new AppEmail();
            $result = $email->send_changed_password($this->email);
        }

        return $result;
    }

    function can_access(Model $object){
        return $object->current_user_can_access();
    }

    function current_user_can_access(){
        $user = current_user();

        if($user->role == 'admin' || $user->client_id == $this->client_id)
            return true;
        else return false;
    }

    function delete(){
        if(!current_user()->is('admin'))
            return false;

        $auth = new Auth();
        return $auth->unregister($this->id);
    }
}
 
