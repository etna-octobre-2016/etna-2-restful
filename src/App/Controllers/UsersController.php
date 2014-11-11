<?php namespace App\Controllers;

use App\Application as Application;
use App\Interfaces\iController as iController;
use PDO;
use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\Request as SilexRequest;
use Symfony\Component\HttpFoundation\Response as SilexResponse;

class UsersController implements iController
{
    // Messages
    const MSG_DATABASE_ERROR        = 'Database error';
    const MSG_RESOURCE_CREATED      = 'User created';
    const MSG_RESOURCE_DELETED      = 'User deleted';
    const MSG_RESOURCE_MODIFIED     = 'User modified';
    const MSG_RESOURCE_NOT_FOUND    = 'User not found';
    const MSG_RESOURCE_UNAUTHORIZED = 'User unauthorized';
    const MSG_RESOURCE_UPDATED      = 'User updated';

    // Codes HTTP
    const STATUS_CONFLICT           = 409;
    const STATUS_CREATED            = 201;
    const STATUS_INTERNAL_ERROR     = 500;
    const STATUS_NOT_FOUND          = 404;
    const STATUS_OK                 = 200;
    const STATUS_UNAUTHORIZED       = 401;

    static public function initRoutes(Application $app)
    {
        $silex  = $app->getSilexApplication();
        $user   = $silex['controllers_factory'];
        $users  = $silex['controllers_factory'];

        $users->get('/{id}/', function(SilexRequest $request, $id) use ($app) {
            return self::get($app, $request, $id);
        });
        $users->post('/', function(SilexRequest $request) use ($app) {
            return self::post($app, $request);
        });
        $users->put('/{id}/', function(SilexRequest $request, $id) use ($app) {
            return self::put($app, $request, $id);
        });
        $users->delete('/{id}/', function(SilexRequest $request, $id) use ($app) {
            return self::delete($app, $request, $id);
        });
        $silex->mount('/users', $users);

        /* Deprecated */
        $user->get('/{id}/', function(SilexRequest $request, $id) use ($app) {
            return self::get($app, $request, $id);
        });
        $user->post('/', function(SilexRequest $request) use ($app) {
            return self::post($app, $request);
        });
        $user->put('/{id}/', function(SilexRequest $request, $id) use ($app) {
            return self::put($app, $request, $id);
        });
        $user->delete('/{id}/', function(SilexRequest $request, $id) use ($app) {
            return self::delete($app, $request, $id);
        });
        $silex->mount('/user', $user);
    }
    static public function get(Application $app, SilexRequest $request, $id)
    {
        $format = 'json';
        $headers = ['Content-Type' => 'application/json'];
        if (!is_numeric($id))
        {
            return new SilexResponse(
                $app->serialize(
                    [
                        'status'    => self::STATUS_CONFLICT,
                        'message'   => 'the id is not a number'
                    ],
                    $format
                ),
                self::STATUS_CONFLICT,
                $headers
            );
        }
        try{
            $sql = 'SELECT id, lastname, firstname, email, role FROM user WHERE id = :id';
            $params = [':id' => (int)$id];
            $types = [PDO::PARAM_INT];
            $pdoStatement = $app->db->executeQuery($sql, $params, $types);
            $user = $pdoStatement->fetch(PDO::FETCH_ASSOC);
            if ($user !== false)
            {
                if ($user['role'] === 'admin')
                {
                    return new SilexResponse(
                        $app->serialize(
                            [
                                'status'    => self::STATUS_UNAUTHORIZED,
                                'message'   => self::MSG_RESOURCE_UNAUTHORIZED
                            ],
                            $format
                        ),
                        self::STATUS_UNAUTHORIZED,
                        $headers
                    );
                }
                return new SilexResponse(
                    $app->serialize($user, $format),
                    self::STATUS_OK,
                    $headers
                );
            }
            return new SilexResponse(
                $app->serialize(
                    [
                        'status'    => self::STATUS_NOT_FOUND,
                        'message'   => self::MSG_RESOURCE_NOT_FOUND
                    ],
                    $format
                ),
                self::STATUS_NOT_FOUND,
                $headers
            );
        }
        catch (DBALException $e)
        {
            $app->logger->addError($e->getMessage());
            return new SilexResponse(
                $app->serialize(
                    [
                        'status'    => self::STATUS_INTERNAL_ERROR,
                        'message'   => self::MSG_DATABASE_ERROR
                    ],
                    $format
                ),
                self::STATUS_INTERNAL_ERROR,
                $headers
            );
        }
    }
    static public function post(Application $app, SilexRequest $request)
    {
        $format = 'json';
        $headers = ['Content-Type' => 'application/json'];
        $user = $request->request;
        try{
            $sql = 'INSERT INTO user (lastname, firstname, email, password, role) VALUES (:lastname, :firstname, :email, :password, :role)';
            $params = [
                ':lastname'     => $user->get('lastname'),
                ':firstname'    => $user->get('firstname'),
                ':email'        => $user->get('email'),
                ':password'     => $user->get('password'),
                ':role'         => $user->get('role')
            ];
            $pdoStatement = $app->db->executeQuery($sql, $params);
            $user->remove('password');
            $user->set('id', $app->db->lastInsertId());
            return new SilexResponse(
                $app->serialize($user->all(), $format),
                self::STATUS_CREATED,
                $headers
            );
        }
        catch (DBALException $e)
        {
            $app->logger->addError($e->getMessage());
            return new SilexResponse(
                $app->serialize(
                    [
                        'status'    => self::STATUS_INTERNAL_ERROR,
                        'message'   => self::MSG_DATABASE_ERROR
                    ],
                    $format
                ),
                self::STATUS_INTERNAL_ERROR,
                $headers
            );
        }
    }
    static public function put(Application $app, SilexRequest $request, $id)
    {
        $format = 'json';
        $headers = ['Content-Type' => 'application/json'];
        if (!is_numeric($id))
        {
            return new SilexResponse(
                $app->serialize(
                    [
                        'status'    => self::STATUS_CONFLICT,
                        'message'   => 'the id is not a number'
                    ],
                    $format
                ),
                self::STATUS_CONFLICT,
                $headers
            );
        }
        try{
            $id = (int)$id;
            $user = [
                'email'     => $request->request->get('email'),
                'firstname' => $request->request->get('firstname'),
                'lastname'  => $request->request->get('lastname'),
                'password'  => $request->request->get('password'),
                'role'      => $request->request->get('role')
            ];
            foreach ($user as $k => $v)
            {
                if ($v === null)
                {
                    unset($user[$k]);
                }
            }
            $app->db->update('user', $user, ['id' => $id]);
            return new SilexResponse(
                $app->serialize(
                    [
                        'status'    => self::STATUS_OK,
                        'message'   => self::MSG_RESOURCE_MODIFIED
                    ],
                    $format
                ),
                self::STATUS_OK,
                $headers
            );
        }
        catch (DBALException $e){
            $app->logger->addError($e->getMessage());
            return new SilexResponse(
                $app->serialize(
                    [
                        'status'    => self::STATUS_INTERNAL_ERROR,
                        'message'   => self::MSG_DATABASE_ERROR
                    ],
                    $format
                ),
                self::STATUS_INTERNAL_ERROR,
                $headers
            );
        }
    }
    static public function delete(Application $app, SilexRequest $request, $id)
    {
        $format = 'json';
        $headers = ['Content-Type' => 'application/json'];
        if (!is_numeric($id))
        {
            return new SilexResponse(
                $app->serialize(
                    [
                        'status'    => self::STATUS_CONFLICT,
                        'message'   => 'the id is not a number'
                    ],
                    $format
                ),
                self::STATUS_CONFLICT,
                $headers
            );
        }
        try{
            $sql = 'DELETE from user where id = :id';
            $params = [':id' => (int)$id];
            $types = [PDO::PARAM_INT];
            $pdoStatement = $app->db->executeQuery($sql, $params, $types);
            return new SilexResponse(
                $app->serialize(
                    [
                        'status'    => self::STATUS_OK,
                        'message'   => self::MSG_RESOURCE_DELETED
                    ],
                    $format
                ),
                self::STATUS_OK,
                $headers
            );
        }
        catch (DBALException $e)
        {
            $app->logger->addError($e->getMessage());
            return new SilexResponse(
                $app->serialize(
                    [
                        'status'    => self::STATUS_INTERNAL_ERROR,
                        'message'   => self::MSG_DATABASE_ERROR
                    ],
                    $format
                ),
                self::STATUS_INTERNAL_ERROR,
                $headers
            );
        }
    }
}
