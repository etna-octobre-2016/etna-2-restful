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
            $sql = 'SELECT * FROM user WHERE id = :id';
            $params = [ ':id' => (int)$id ];
            $types = [PDO::PARAM_INT];
            $pdoStatement = $app->db->executeQuery($sql, $params, $types);
            $result = $pdoStatement->fetch();
            if ($result !== false)
            {
                return new SilexResponse('test titi', 200);
            }
            return new SilexResponse('not found', 404);
        }
        catch (PDOException $e){
            $app->logger->addFatal($e->getMessage());
            return new SilexResponse('an error occured', 500);
        }
    }
}
