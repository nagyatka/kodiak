<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2019. 02. 15.
 * Time: 12:58
 */

namespace Kodiak\Core\Router;

/**
 * Interface RouterHook
 *
 * A RouterHook will be executed after the findRoute method call run successfully.
 *
 * @package Kodiak\Core\Router
 */
interface RouterHook
{
    /**
     * Executes the hook.
     *
     * @param $actual_route
     * @return void
     */
    public function run($actual_route);
}