<?php namespace App\Controllers;

use App\Application as Application;
use App\ApplicationController as ApplicationController;
use App\Models\UserModel as User;
use PDO;
use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\Request as SilexRequest;
use Symfony\Component\HttpFoundation\Response as SilexResponse;
use Symfony\Component\HttpFoundation\ParameterBag as SilexParameterBag;

class UsersController extends ApplicationController
{
    const ROLE_UNDEFINED    = 0;
    const ROLE_NORMAL       = 1;
    const ROLE_ADMIN        = 2;

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
            $sql = 'SELECT * FROM user WHERE id = :id';
            $params = [':id' => (int)$id];
            $types = [PDO::PARAM_INT];
            $pdoStatement = $app->db->executeQuery($sql, $params, $types);
            $result = $pdoStatement->fetch(PDO::FETCH_ASSOC);
            if ($result !== false)
            {
                $user = new User($result);
                if ($user->get('role') === 'admin')
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
                    $app->serialize($user->all(['password', 'SYS_ROLE']), $format),
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
        $user = new User($request->request->all());
        $sys_user = $app->getuser();
        if($sys_user->get('SYS_ROLE') != self::ROLE_ADMIN && $user->get('role') == 'admin')
        {
            return new SilexResponse(
                $app->serialize(
                    [
                        'status'    => self::STATUS_UNAUTHORIZED
                    ],
                    $format
                ),
                self::STATUS_UNAUTHORIZED,
                $headers
            );
        }
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
            $user->set('id', $app->db->lastInsertId());
            return new SilexResponse(
                $app->serialize($user->all(['password', 'SYS_ROLE']), $format),
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
            $sys_user = $app->getuser();
            if($sys_user->get('SYS_ROLE') != self::ROLE_ADMIN && $request->request->get('role') == 'admin')
            {
                return new SilexResponse(
                    $app->serialize(
                        [
                            'status'    => self::STATUS_UNAUTHORIZED
                        ],
                        $format
                    ),
                    self::STATUS_UNAUTHORIZED,
                    $headers
                );
            }

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
                        'message'   => self::MSG_RESOURCE_UPDATED,
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

        $sys_user = $app->getuser();
        if($sys_user->get('SYS_ROLE') != self::ROLE_ADMIN)
        {
            return new SilexResponse(
                $app->serialize(
                    [
                        'status'    => self::STATUS_UNAUTHORIZED
                    ],
                    $format
                ),
                self::STATUS_UNAUTHORIZED,
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
    static public function authenticate(Application $app, SilexRequest $request, $credentials)
    {
        if (empty($credentials['username']) || empty($credentials['password']))
        {
            return false;
        }
        try{
            $sql = 'SELECT * FROM user WHERE email = :username AND password = :password';
            $types = [PDO::PARAM_STR, PDO::PARAM_STR];
            $pdoStatement = $app->db->executeQuery($sql, $credentials, $types);
            $result = $pdoStatement->fetch(PDO::FETCH_ASSOC);

            if ($result === false)
            {
                return false;
            }
            $user = new User($result);
            switch ($user->get('role'))
            {
                case 'admin':
                    $user->set('SYS_ROLE', self::ROLE_ADMIN);
                    break;
                case 'normal':
                    $user->set('SYS_ROLE', self::ROLE_NORMAL);
                    break;
                default:
                    $user->set('SYS_ROLE', self::ROLE_UNDEFINED);
                    break;
            }
            $app->setUser($user);
            return true;
        }
        catch (DBALException $e){
            $app->logger->addError($e->getMessage());
            return false;
        }
    }
}
