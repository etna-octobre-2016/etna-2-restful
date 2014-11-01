<?php namespace App\Controllers;

use App\Application as Application;
use App\Interfaces\iController as iController;
use PDO;
use PDOException;
use Symfony\Component\HttpFoundation\Request as SilexRequest;
use Symfony\Component\HttpFoundation\Response as SilexResponse;

class UsersController implements iController
{
    static public function get(Application $app, SilexRequest $request, $id)
    {
        try{
            $sql = 'SELECT id, lastname, firstname, email, role FROM user WHERE id = :id';
            $params = [ ':id' => (int)$id ];
            $types = [PDO::PARAM_INT];
            $pdoStatement = $app->db->executeQuery($sql, $params, $types);
            $user = $pdoStatement->fetch(PDO::FETCH_ASSOC);
            $format = 'json';
            if ($user !== false)
            {
                if ($user['role'] === 'admin')
                {
                    return new SilexResponse($app->serialize(['status' => 401, 'message' => 'unauthorized'], $format), 401);
                }
                return new SilexResponse($app->serialize($user, $format), 200);
            }
            return new SilexResponse($app->serialize(['status' => 404, 'message' => 'not found'], $format), 404);
        }
        catch (PDOException $e){
            $app->logger->addFatal($e->getMessage());
            return new SilexResponse($app->serialize(['status' => 500, 'message' => 'database error'], $format), 500);
        }
    }
}
