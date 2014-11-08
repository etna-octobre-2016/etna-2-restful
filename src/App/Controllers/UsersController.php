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
        $id = (int)$id;
        $json = file_get_contents('php://input');
        $obj = json_decode($json);
        $format = 'json';
        $headers = ['Content-Type' => 'application/json'];
        try{
            $sql = 'UPDATE user SET';
            $params = [':id' => $id];
            $i = 0;
            foreach ($obj as $key => $value)
            {
                if ($key == "id")
                {
                    return new SilexResponse(
                        $app->serialize(
                            [
                                'status'    => self::STATUS_CONFLICT,
                                'message'   => 'You cannot change the id'
                            ],
                            $format
                        ),
                        self::STATUS_CONFLICT,
                        $headers
                    );
                }
                if ($i == 0)
                {
                    $sql .=' '.$key .'= :'.$key;
                    $i++;
                }
                else
                {
                    $sql .=' ,'.$key .'= :'.$key;
                }
                $params[':'.$key] = $obj->{$key};
            }
            $sql.= " where id = :id";
            $pdoStatement = $app->db->executeQuery($sql, $params);
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
