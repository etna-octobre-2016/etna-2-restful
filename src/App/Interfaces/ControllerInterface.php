<?php namespace App\Interfaces;

use App\Application as Application;
use Symfony\Component\HttpFoundation\Request as SilexRequest;

interface iController
{
    static public function get(Application $app, SilexRequest $request, $id);
}