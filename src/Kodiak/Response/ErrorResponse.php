<?php

namespace Kodiak\Response;


use Kodiak\Request\Request;

interface ErrorResponse
{
    /**
     * Authorization Required
     *
     * @param Request $request
     * @param \Exception $exception
     * @return Response
     */
    public function error_401(Request $request, \Exception $exception): Response;

    /**
     * Forbidden
     *
     * @param Request $request
     * @param \Exception $exception
     * @return Response
     */
    public function error_403(Request $request, \Exception $exception): Response;

    /**
     * Not found
     *
     * @param Request $request
     * @param \Exception $exception
     * @return Response
     */
    public function error_404(Request $request, \Exception $exception): Response;

    /**
     * Internal Server Error
     *
     * @param Request $request
     * @param \Exception $exception
     * @return Response
     */
    public function error_500(Request $request, \Exception $exception): Response;

    /**
     * Service Temporarily Unavailable
     *
     * @param Request $request
     * @param \Exception $exception
     * @return Response
     */
    public function error_503(Request $request, \Exception $exception): Response;

    /**
     * Custom exception handling
     * @param Request $request
     * @param \Exception $exception
     * @return Response
     */
    public function custom_error(Request $request, \Exception $exception): Response;
}