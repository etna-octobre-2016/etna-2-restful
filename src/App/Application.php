<?php namespace App;

use Silex\Application                           as SilexApplication;
use Silex\Provider\DoctrineServiceProvider      as SilexDoctrineProvider;
use Silex\Provider\MonologServiceProvider       as SilexMonologProvider;
use Silex\Provider\SerializerServiceProvider    as SilexSerializerProvider;
use Symfony\Component\HttpFoundation\Request    as SilexRequest;
use Symfony\Component\HttpFoundation\Response   as SilexResponse;

class Application
{
    public  $logger;

    private $cfg;
    private $silexApplication;
    private $user;

    /* Méthodes magiques */

    function __construct()
    {
        $this->_fetchConfiguration('config'.DIRECTORY_SEPARATOR.'app.json');
        $this->_initSilexApplication();
        $this->_setRoutes();
        $this->_parseData();
        $this->_removeRequestUriSlash();
        $this->_initAuthentication();
    }

    /* Méthodes publiques */

    /**
     * Retourne la configuration de l'application
     * @param  string $sectionName  (optionel) une section spécifique du fichier de configuration
     * @return mixed                retourne la configuration ou false en cas d'erreur
     */
    public function config($sectionName = null)
    {
        if (!is_string($sectionName))
        {
            return $this->cfg;
        }
        if (property_exists($this->cfg, $sectionName))
        {
            return $this->cfg->$sectionName;
        }
        return null;
    }
    public function getSilexApplication()
    {
        return $this->silexApplication;
    }
    public function getUser()
    {
        return $this->user;
    }
    public function run()
    {
        $this->silexApplication->run();
    }
    public function serialize($data, $format)
    {
        return $this->silexApplication['serializer']->serialize($data, $format);
    }
    public function setUser($userModel)
    {
        $this->user = $userModel;
    }

    /* Méthodes privées */

    private function _initSilexApplication()
    {
        $app = new SilexApplication();
        $dbConfig = $this->config('database');
        $app['debug'] = $this->config('debug');

        // Journalisation des erreurs
        $app->register(new SilexMonologProvider(),[
            'monolog.logfile'   => $this->config('logs')->app,
            'monolog.name'      => 'Application'
        ]);

        // Accès à la base de données
        $app->register(new SilexDoctrineProvider(),[
            'db.options' => [
                'charset'   => $dbConfig->charset,
                'dbname'    => $dbConfig->name,
                'driver'    => $dbConfig->driver,
                'host'      => $dbConfig->hostname,
                'password'  => $dbConfig->password,
                'port'      => $dbConfig->port,
                'user'      => $dbConfig->username
            ]
        ]);

        // Sérialisation
        $app->register(new SilexSerializerProvider());

        $this->db = $app['db'];
        $this->logger = $app['monolog'];
        $this->silexApplication = $app;
    }
    private function _fetchConfiguration($filename)
    {
        $this->cfg = null;
        if (file_exists($filename))
        {
            $this->cfg = json_decode(file_get_contents($filename));
        }
    }
    private function _setRoutes()
    {
        // user(s)
        Controllers\UsersController::initRoutes($this);

        // default
        $this->silexApplication->get('/', function(){
            return new SilexResponse('Welcome to the RESTful API', 200);
        });
    }
    private function _parseData()
    {
        $this->silexApplication->before(function(SilexRequest $request){

            if (0 === strpos($request->headers->get('Content-Type'), 'application/json'))
            {
                $data = json_decode($request->getContent(), true);
                $request->request->replace(is_array($data) ? $data : array());
            }
        });
    }
    private function _removeRequestUriSlash()
    {
        $_SERVER['REQUEST_URI'] = rtrim($_SERVER['REQUEST_URI'], '/') . '/';
    }
    private function _initAuthentication()
    {
        $this->silexApplication->before(function(SilexRequest $request){

            $isAuthenticated = Controllers\UsersController::authenticate($this, $request, [
                'username'  => $request->server->get('PHP_AUTH_USER'),
                'password'  => $request->server->get('PHP_AUTH_PW')
            ]);

            var_dump(
                [
                    'isAuthenticated'   => $isAuthenticated,
                    'role'              => $this->user
                ]
            );

        });
    }
}
