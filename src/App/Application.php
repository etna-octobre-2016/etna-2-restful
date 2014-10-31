<?php namespace App;

class Application
{
    private $silexApplication;
    private $cfg;

    /* Méthodes magiques */

    function __construct()
    {
        $this->_fetchConfiguration('config'.DIRECTORY_SEPARATOR.'app.json');
        $this->_initSilexApplication();
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
        return false;
    }

    /* Méthodes privées */

    private function _initSilexApplication()
    {
        $this->silexApplication = new \Silex\Application();
    }
    private function _fetchConfiguration($filename)
    {
        $this->cfg = null;

        if (file_exists($filename))
        {
            $this->cfg = json_decode(file_get_contents($filename));
        }
    }
}
