<?php namespace App\Models;

use App\ApplicationModel as ApplicationModel;

class UserModel extends ApplicationModel
{
    protected $email;
    protected $firstname;
    protected $id;
    protected $lastname;
    protected $password;
    protected $role;

    function __construct($user)
    {
        $this->setProperties($user);
        $this->_hydrate();
    }

    /* Protected methods */

    protected function setProperties($user)
    {
        if (isset($user['email']))
        {
            $this->set('email', $user['email']);
        }
        if (isset($user['firstname']))
        {
            $this->set('firstname', $user['firstname']);
        }
        if (isset($user['id']))
        {
            $this->set('id', $user['id']);
        }
        if (isset($user['lastname']))
        {
            $this->set('lastname', $user['lastname']);
        }
        if (isset($user['password']))
        {
            $this->set('password', $user['password']);
        }
        if (isset($user['role']))
        {
            $this->set('role', $user['role']);
        }
    }
    protected function onUpdate()
    {
        $this->_hydrate();
    }

    /* Private methods */

    private function _hydrate()
    {
        $this->id = (int) $this->id;
    }
}
