<?php namespace App;

use DIRECTORY_SEPARATOR as DS;
use Silex\Application as SilexApp;

class Application extends SilexApp
{
    function __construct(ArrayAccess $config = null)
    {
        parent::__construct();
        exit('app init biatch test');
    }
}
