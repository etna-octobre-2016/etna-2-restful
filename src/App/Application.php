<?php namespace App;

use Silex\Application                           as SilexApplication;
use Silex\Provider\MonologServiceProvider       as SilexMonologProvider;
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

    /* Méthodes privées */

    private function _initSilexApplication()
    {
        $app = new SilexApplication();
        $app['debug'] = $this->config('debug');
        $app->register(new SilexMonologProvider(),[
            'monolog.logfile'   => $this->config('logs')->app,
            'monolog.name'      => 'Application'
        ]);
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
        // Users
        $this->silexApplication->get('/user/{id}', function(SilexRequest $request, $id){
            return Controllers\UsersController::get($this, $request, $id);
        });
        $this->silexApplication->get('/users/{id}', function(SilexRequest $request, $id){
            return Controllers\UsersController::get($this, $request, $id);
        });
    }
}
