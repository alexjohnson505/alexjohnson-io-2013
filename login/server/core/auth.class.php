<?php
require_once('db/db.connection.class.php');
require_once('db/db.recordset.class.php');
require_once('db/db.recordsetrow.class.php');
require_once('db/db.exception.class.php');
require_once('db/db.helpers.class.php');

use phpSweetPDO\SQLHelpers\Basic as Helpers;

class Auth
{

    private $db;

    function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function logged_in($update_activity = true)
    {
        @session_start();

        //todo:I'm probably not getting the user agent when the app uses https
        $user_agent = md5($_SERVER['HTTP_USER_AGENT']);
        $session_id = session_id();

        if (isset($_SESSION['auth'])) {
            if ($_SESSION['auth'] == 1) {
                //Get session data from session table
                $db = Database::getInstance();
                $res = $db->select("SELECT user_id, user_agent, last_activity FROM sessions WHERE session_id = '$session_id'");
                $res = $res->export();
                $res = count($res) > 0 ? $res[0] : false;

                if ($res !== false) {
                    $db_user_agent = $res['user_agent'];

                    //Determine if current user agent is the same as user agent in the db.
                    if ($user_agent == $db_user_agent) {
                        //return success IFF user has been active within the last 30 minutes

                        $max_inactivity = get_config('auto_logout.max_inactivity'); //1800; //todo: config value?
                        $last_activity = $res['last_activity'];

                        if ((get_config('auto_logout.is_enabled') == true) && ((time() - $last_activity) > $max_inactivity)) {
                            session_destroy();
                            return false;

                        } else {
                            $user['id'] = $res['user_id'];

                            $user = new User($res['user_id']);

                            if ($update_activity == true) {
                                $ip_address = $_SERVER['REMOTE_ADDR'];
                                $last_activity = time();

                                $res = $db->execute("UPDATE sessions SET ip_address = '$ip_address', user_agent = '$user_agent', last_activity = '$last_activity' WHERE session_id = '$session_id'");
                            }

                            return $user;
                        }
                    } else {
                        return false;
                    }
                }

            }
        } else {
            return false;
        }
    }


    /**
     * The User Authentication class handles all
     * access related function such as login,
     * check status, etc. This class validates sessions
     * against the database.
     */


    function login($username, $password)
    {
        /** If the username is available, the
         * user doesn't exist and the login fails
         */
        if ($this->username_available($username) == true) {
            return false;
        } else {

            $result = $this->db->select("SELECT id, password, salt FROM users WHERE email = '$username'")->export();

            $credentials = $result[0];

            if (!empty($credentials)) {
                $salt = $credentials['salt'];
                $db_password = $credentials['password'];
                $input_password = $this->hash_password($password, $salt);

                /**
                 * If the hased input password matches the
                 * hashed password in the db, the login
                 * succeed, otherwise it fails.
                 */
                if ($input_password == $db_password) {

                    $session_id = $this->start_logged_in_session();
                    $this->save_session_data($session_id, $credentials['id'], $username);

                    return $credentials['id'];
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    }

    public function username_available($username)
    {
        $result = $this->db->execute("SELECT id from users WHERE email = '$username'");

        if (!empty($result)) {
            return false;
        } else
            return true;
    }

    public function hash_password($password = false, $salt = false)
    {
        if ($password === false) {
            return false;
        }

        $pepper = '';
        $password = hash('sha256', $password . $salt . $pepper);

        return $password;
    }

    public function salt()
    {
        //todo:i need to stop saving the salt, use bcrypt?
        return hash('sha256', uniqid(mt_rand(), true));
    }

    function start_logged_in_session()
    {
        @session_start();

        //reset all session variables
        $_SESSION = array();

        //generate a new session id
        @session_regenerate_id();

        //set the ussers status to logged in
        $_SESSION['auth'] = 1;

        return session_id();
    }

    function save_session_data($session_id, $user_id, $username)
    {
        $user_agent = md5($_SERVER['HTTP_USER_AGENT']);
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $last_activity = time();

        $result = $this->db->execute("SELECT * FROM sessions WHERE session_id = '$session_id'");

        if (empty($result)) {
            $result = $this->db->execute("INSERT INTO sessions (session_id, user_id, username, ip_address, user_agent, last_activity) VALUES ('$session_id', '$user_id', '$username', '$ip_address', '$user_agent', '$last_activity')");
        } else {
            $result = $this->db->execute("UPDATE sessions SET ip_address = '$ip_address', user_agent = '$user_agent', last_activity = '$last_activity' WHERE session_id = '$session_id'");
        }
    }

    function register($user_id, $role_id, $password = null)
    {
        $registration = array();

        $salt = $this->salt();

        if (!isset($password)) {
            $registration['temporary_password'] = $this->temp_password();
            $password = $registration['temporary_password'];
        }

        $password = $this->hash_password($password, $salt);

        $result = $this->db->execute("UPDATE users set password = '$password', salt = '$salt', temp_password = '$password' WHERE id = $user_id");

        $this->create_user_role($user_id, $role_id);

        if ($result != 0)
            $registration['result'] = true;
        else $registration['result'] = false;

        return $registration;
    }

    function create_user_role($user_id, $role_id){
        $created = time();
        $result = $this->db->execute("INSERT INTO role_user (user_id, role_id, created) VALUES('$user_id', '$role_id', '$created')");
        return $result;
    }

    function unregister($user_id){
        $sql = "DELETE FROM role_user WHERE user_id = $user_id";
        $result = $this->db->execute($sql);

        $sql = "DELETE FROM users WHERE id = $user_id";
        $result = $this->db->execute($sql);

        return $result;
    }

    function temp_password(){
        return substr(uniqid(), -6, 6);
    }

    function change_password($client_id, $password, $new_password, $confirm_new_pass)
    {
        $record_set = $this->db->select("SELECT password, salt FROM users WHERE id = '$client_id'");
        $records = $record_set->export();

        $credentials = $records[0];

        if (!empty($credentials)) {

            $salt = $credentials['salt'];
            $db_password = $credentials['password'];
            $input_password = $this->hash_password($password, $salt);

            /**
             * If the user entered the wrong value
             * for current password, the change
             * password request fails
             */
            if ($input_password != $db_password) {
                return false;
            }

            /**
             * Fail if new pass and confirm pass
             * do not match
             */
            if ($new_password != $confirm_new_pass) {
                return false;
            }

            /**
             * Salt new passowrd and update the
             * user table
             */
            $salt = $this->salt();
            $password = $this->hash_password($new_password, $salt);
            $result = $this->db->execute("UPDATE users SET password = '$password',  salt = '$salt', temp_password='' WHERE  id = '$client_id' ");

            if ($result == true) {
                return true;
            } else
                return false;
        } else return false;
    }


    function reset_password($new_password, $new_pass_confirm, $user_id)
    {
        if ($new_password != $new_pass_confirm) {
            return false;
        }
        $salt = $this->salt();
        $password = $this->hash_password($new_password, $salt);
        $result = $this->db->execute("UPDATE users SET password = '$password',  salt = '$salt', temp_password='' WHERE  id = '$user_id' ");

        if ($result == true) {
            return true;
        } else
            return false;
    }


    function forgot_password($email){
        //get the user id from the email address
        $record_set = $this->db->select("SELECT id FROM users WHERE email = '$email'");
        $records = $record_set->export();
        $user_id = isset($records[0]) ? $records[0]['id'] : false;

        if($user_id !== false){
            $temp_password = $this->temp_password();
            $result = $this->reset_password($temp_password, $temp_password, $user_id);

            if($result == true)
                return $temp_password;
            else return false;
        }
        else return false;
    }

    /**
     * Logs a user out by destroying the session
     * and the session data in db
     */
    function logout()
    {
        @session_start();

        $session_id = session_id();

        $this->db->execute("DELETE FROM sessions WHERE session_id ='$session_id'");

        @session_destroy();
    }
}
 
