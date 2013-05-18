<?php 
/**
 * Authority
 *
 * Authority is an authorization library for CodeIgniter 2+ and PHPActiveRecord
 * This library is inspired by, and largely based off, Ryan Bates' CanCan gem
 * for Ruby on Rails.  It is not a 1:1 port, but the essentials are available.
 * Please check out his work at http://github.com/ryanb/cancan/
 *
 * @package     Authority
 * @version     0.0.3
 * @author      Matthew Machuga
 * @license     MIT License
 * @copyright   2011 Matthew Machuga
 * @link        http://github.com/machuga
 *
 **/

//if ( ! defined('BASEPATH')) exit('No direct script access allowed'); //todo:make sure i can't directly access any of theses


require 'authority/ability.php';
require 'authority/rule.php';

class Authority extends Authority\Ability {

    public static function initialize($user)
    {
        Authority::action_alias('manage', array('create', 'read', 'update', 'delete'));
        Authority::action_alias('save', array('create', 'read', 'update'));

        if ( ! $user || ! $user->role) return false;

        if ($user->role == 'admin')
        {
            Authority::allow('manage', 'all');
            Authority::deny('update', 'User', function ($a_user) use ($user) {
                return $a_user->id !== $user->id;
            });
        }
        else{

            Authority::allow('save', 'questionresponse');
            Authority::allow('save', 'certificate');
            Authority::allow('save', 'evaluation');
            Authority::allow('read', 'file');
            Authority::allow('save', 'payment');
            Authority::allow('read', 'question');
            Authority::allow('read', 'question');

          //  Authority::allow('save', 'questionresponse');
        }


    }

    protected static function current_user()
    {
        return parent::current_user();
    }
}
