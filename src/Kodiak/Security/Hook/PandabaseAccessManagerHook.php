<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2018. 12. 20.
 * Time: 17:29
 */

namespace Kodiak\Security\Hook;


use Kodiak\Application;
use Kodiak\Core\KodiConf;
use Kodiak\Hook\HookInterface;
use Kodiak\Request\Request;
use Kodiak\Security\Model\SecurityManager;
use PandaBase\Connection\ConnectionManager;

class PandabaseAccessManagerHook extends HookInterface
{

    /**
     * @param KodiConf $kodiConf
     * @param Request $request
     * @return Request
     */
    public function process(KodiConf $kodiConf, Request $request): Request
    {
        /** @var SecurityManager $securityManager */
        $securityManager = Application::get("security");

        ConnectionManager::getInstance()->registerAuthorizedUser($securityManager->getUser());

        return $request;
    }
}