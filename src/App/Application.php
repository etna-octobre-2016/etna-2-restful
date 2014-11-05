<?php namespace App;

use Silex\Application                           as SilexApplication;
use Silex\Provider\DoctrineServiceProvider      as SilexDoctrineProvider;
use Silex\Provider\MonologServiceProvider       as SilexMonologProvider;
use Silex\Provider\SerializerServiceProvider    as SilexSerializerProvider;
use Symfony\Component\HttpFoundation\Request    as SilexRequest;

class Application
{
    public  $logger;

    private $cfg;
    private $silexApplication;

    /* Méthodes magiques */

    function __construct()
    {
        $this->_fetchConfiguration('config'.DIRECTORY_SEPARATOR.'app.json');
        $this->_initSilexApplication();
        $this->_setRoutes();
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
    public function run()
    {
        $this->silexApplication->run();
    }
    public function serialize($data, $format)
    {
        return $this->silexApplication['serializer']->serialize($data, $format);
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
        // ressource: /user
        $this->silexApplication->get('/user/{id}', function(SilexRequest $request, $id){
            return Controllers\UsersController::get($this, $request, $id);
        });

        // ressource: /users
        $this->silexApplication->get('/users/{id}', function(SilexRequest $request, $id){
            return Controllers\UsersController::get($this, $request, $id);
        });

        // $this->silexApplication->post('/users'), function(SilexRequest $request){
        //     return Controllers\UsersController::post($this, $request);
        // }
    }
}
