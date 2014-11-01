<?php namespace App\Controllers;

use App\Application as Application;
use Symfony\Component\HttpFoundation\Request as SilexRequest;
use Symfony\Component\HttpFoundation\Response as SilexResponse;

class UsersController
{
    static public function get(Application $app, SilexRequest $request, $id)
    {
        $app->logger->addWarning('hey ca marche !');
        return new SilexResponse('test titi', 200);
    }
}
