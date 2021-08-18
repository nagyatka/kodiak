<?php

namespace Kodiak\ServiceProvider\TwigProvider;

use Kodiak\Application;
use Kodiak\Core\KodiConf;
use Kodiak\Exception\Http\HttpInternalServerErrorException;
use Kodiak\ServiceProvider\TwigProvider\ContentProvider\ContentProvider;
use Pimple\Container;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extension\EscaperExtension;
use Twig\Loader\FilesystemLoader;

/**
 * Class Twig
 *
 * @package Kodiak\ServiceProvider\TwigProvider
 */
class Twig
{
    const TWIG_PATH = "path";
    const PAGE_TEMPLATE_PATH = "page_frame_template_path";
    const CONTENT_PROVIDERS = "content_providers";

    /**
     * @var array
     */
    private $configuration;

    /**
     * @var bool
     */
    private $useAjax;

    /**
     * @var Container
     */
    private $contentProviders;

    /**
     * Twig constructor.
     * @param array $configuration
     */
    public function __construct($configuration)
    {
        //Load configuration
        $this->configuration = $configuration;
        $this->contentProviders = new Container();
        if(isset($this->configuration[self::CONTENT_PROVIDERS])) {
            $this->registerContentProviders($this->configuration[self::CONTENT_PROVIDERS]);
            unset($this->configuration[self::CONTENT_PROVIDERS]);
        }

        // Twig initialization
        $loader = new FilesystemLoader($configuration[self::TWIG_PATH]);
        $this->twig = new Environment($loader,[
            "debug" => Application::getEnvMode() == KodiConf::ENV_DEVELOPMENT
        ]);

        // Set ajax usage
        $this->useAjax = $this->chechAjaxUsage();

        // Escape
        try {
            $escaper = new EscaperExtension('html');
            $this->twig->addExtension($escaper);
        }
        catch (\LogicException $exception) {}

        if(Application::getEnvMode() == KodiConf::ENV_DEVELOPMENT)
            $this->twig->addExtension(new DebugExtension());

        // Saját függvények definiálása
        $this->initializeBaseTwigFunction();
    }

    /**
     * @param $contentProviders
     */
    public function registerContentProviders($contentProviders): void {
        foreach ($contentProviders as $contentProvider) {
            $className = $contentProvider["class_name"];
            $parameters = $contentProvider["parameters"];
            /** @var ContentProvider $provider */
            $provider = new $className($parameters);
            $this->contentProviders[$provider->getKey()] = function($c) use($provider) {
                return $provider->getValue();
            };
        }
    }

    /**
     * A twig segítségével lerendereli a html tartalmat. AJAX kérések esetén csak a megadott template-t rendereli ki,
     * viszont ha nem AJAX kérést kapott a szerver, akkor a Twig inicializálásánál megadott oldalkeretbe tölti bele a
     * template-t.
     *
     * Ha mindenképpen azt szeretnénk, hogy csak a template fájl renderelődjön ki, akkor a $forceRawTemplate paramétert
     * true-ra kell állítani!
     *
     * A renderelésnél elérhető az összes olyan paraméter, amit az alkalmazás addPageFrameContent függvényén keresztül
     * lett beállítva. A Twig fájlokban ezek az 'app.*' változó néven keresztül érhetőek el.
     * Példa:
     *  $twig->addContentProvider(new PageTitleContentProvider("Oldal címe"));
     *
     *  <title>{{ app.page_title }}</title> ==> <title>Oldal címe</title>
     *
     * @param $templateName
     * @param array $parameters
     * @param bool $forceRawTemplate
     * @param null $pageTemplate
     * @return string
     * @throws HttpInternalServerErrorException
     */
    public function render($templateName, array $parameters = [], bool $forceRawTemplate = false, $pageTemplate = null) {
        // Különböző contentek betöltése
        $parameters["app"] = $this->contentProviders;

        if($this->useAjax || $forceRawTemplate) {
            return $this->twig->render($templateName,$parameters);
        } else {
            if(is_array($this->configuration[self::PAGE_TEMPLATE_PATH])) {
                $templates = $this->configuration[self::PAGE_TEMPLATE_PATH];
                $actualRoute = Application::getInstance()->getCore()->getRouter()->getActualRoute();
                if($pageTemplate != null) {
                    $desiredFrame = $pageTemplate;
                }
                elseif(isset($actualRoute["page_frame"])) {
                    $desiredFrame = Application::getInstance()->getCore()->getRouter()->getActualRoute()["page_frame"];
                }
                else {
                    $desiredFrame = "default";
                }

                if(array_key_exists($desiredFrame,$templates)) {
                    $pageFrameName = $templates[$desiredFrame];
                }
                else {
                    throw new HttpInternalServerErrorException("Undefined page_template path in twig. Check the configuration!");
                }
            } else {
                $pageFrameName = $this->configuration[self::PAGE_TEMPLATE_PATH];
            }
            $parameters["app"]["content_template_name"] = $templateName;
            return $this->twig->render($pageFrameName,$parameters);
        }
    }

    /**
     *  Loads the custom twig function.
     */
    private function initializeBaseTwigFunction(): void {
        // Development or not
        $is_dev = new \Twig\TwigFunction('is_dev', function(){
            return Application::getEnvMode() == KodiConf::ENV_DEVELOPMENT;
        });
        $this->twig->addFunction($is_dev);

        /*
         * Put other twig function here for better visibility.
         */
    }

    /**
     * Returns with the original Twig_Environment.
     *
     */
    public function getTwigEnvironment(): \Twig\Environment {
        return $this->twig;
    }

    /**
     * @return bool
     */
    private function chechAjaxUsage(): bool {
        return (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        );
    }



}