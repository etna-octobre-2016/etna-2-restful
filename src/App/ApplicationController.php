<?php namespace App;

use App\Interfaces\iController as iController;

abstract class ApplicationController implements iController
{
    /* Status codes */
    const STATUS_CONFLICT           = 409;
    const STATUS_CREATED            = 201;
    const STATUS_INTERNAL_ERROR     = 500;
    const STATUS_NOT_FOUND          = 404;
    const STATUS_OK                 = 200;
    const STATUS_UNAUTHORIZED       = 401;

    /* Response messages */
    const MSG_DATABASE_ERROR        = 'Database error';
    const MSG_RESOURCE_CREATED      = 'Resource created';
    const MSG_RESOURCE_DELETED      = 'Resource deleted';
    const MSG_RESOURCE_NOT_FOUND    = 'Resource not found';
    const MSG_RESOURCE_UNAUTHORIZED = 'Resource access unauthorized';
    const MSG_RESOURCE_UPDATED      = 'Resource updated';
}
