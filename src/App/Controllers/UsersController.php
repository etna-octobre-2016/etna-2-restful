<?php namespace App\Controllers;

use App\Application as Application;
use App\Interfaces\iController as iController;
use Symfony\Component\HttpFoundation\Request as SilexRequest;
use Symfony\Component\HttpFoundation\Response as SilexResponse;

class UsersController implements iController
{
    static public function get(Application $app, SilexRequest $request, $id)
    {

        var_dump($app);

        echo '--------------------<br>';

        var_dump($request);

        echo '--------------------<br>';

        var_dump($id);

        echo '--------------------<br>';

        return new SilexResponse('test titi', 200);
    }
}
