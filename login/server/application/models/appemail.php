<?php

class AppEmail extends Email
{
    function send_invoice($client_id, $invoice){
        $users = $this->get_client_targets($client_id);

        foreach($users as $user){
            if($user['role_id'] != 1){
                $this->set_recipient($user->email);
                $this->set_subject(get_config('email.new_invoice_subject'));
                $this->generate('new-invoice', get_object_vars($invoice));
                $this->send();
            }
        }

        return true;
    }

    function send_forgot_password($to, $params)
    {
        $this->set_recipient($to);
        $this->set_subject(get_config('email.forgot_password_subject'));

        $this->generate('forgot-password', $params);
        return $this->send();
    }

    function send_changed_password($to)
    {
        $this->set_recipient($to);
        $this->set_subject(get_config('email.changed_password_subject'));
        $this->generate('changed-password');
        return $this->send();
    }

    function send_new_user($to, $params)
    {
        $this->set_recipient($to);
        $this->set_subject(get_config('email.new_account_subject'));
        $this->generate('new-user', $params);
        return $this->send();
    }

    function send_client_payment_notification($to, $params)
    {
        $this->set_recipient($to);
        $this->set_subject(get_config('email.client_payment_subject'));
        $this->generate('client-payment', $params);
        return $this->send();
    }

    function send_admin_payment_notifications($admins, $params)
    {
        foreach ($admins as $admin) {
            $this->set_recipient($admin['email']);
            $this->set_subject(get_config('email.admin_payment_subject'));
            $this->generate('admin-payment', $params);
            $this->send();
        }
    }

    function send_message_notification($params)
    {
        $users = $this->get_all_other_users($params['client_id']);

        foreach ($users as $user) {
            $this->set_recipient($user['email']);
            $this->set_subject(get_config('email.message_subject'));
            $this->generate('message', $params);
            $result = $this->send();
        }
    }

    function send_file_upload_notification($project, $files)
    {
        $users = $this->get_all_other_users($project->client_id);

        $params = array(
            'project' => $project,
            'files' => $files,
            'base_url' => get_config('base_url')
        );

        foreach ($users as $user) {
            $this->set_recipient($user['email']);
            $this->set_subject(get_config('email.uploaded_file_subject'));
            $this->generate('file', $params);
            $result = $this->send();
        }
    }

    function send_payment_notifications($client_to, $params)
    {
        $admins = $this->get_admins();
        $this->send_client_payment_notification($client_to, $params);
        $this->send_admin_payment_notifications($admins, $params);
    }

    function send_task_assignment($params)
    {
        $this->set_recipient($params['user']->email);
        $this->set_subject(get_config('email.task_assignment_subject'));
        $this->generate('task-assignment', $params);
        return $this->send();
    }

    function get_client_targets($client_id)
    {
        //todo:this gets all users, even admins. It shoudldnt I don't think.
        $current_user = current_user();
        if ($current_user->is('admin') || (current_user()->client_id == $client_id)) {
            $client = new Client($client_id);
            $users = $client->get_users();

            return $users;
        } else return array();
    }

    function get_admins()
    {
        $sql = "SELECT role_user.user_id, users.email, users.id
                FROM role_user
                LEFT JOIN users
                  ON users.id = role_user.user_id
                WHERE role_user.role_id = 1";


        $result = $this->select($sql);

        return $result;
    }

    function get_all_other_users($client_id)
    {
        $current_user = current_user();
        $clients = $this->get_client_targets($client_id);
        $admins = $this->get_admins();
        $users = array_merge($admins, $clients);


        foreach ($users as $key => $user) {
            if ($user['id'] == $current_user->id) {
                unset($users[$key]);
            }
        }

        return $users;
    }
}