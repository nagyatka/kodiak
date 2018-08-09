<?php

namespace Kodiak\Response;


use Kodiak\Request\Request;

interface ErrorResponse
{
    /**
     * Authorization Required
     *
     * @param Request $request
     * @return Response
     */
    public function error_401(Request $request): Response;

    /**
     * Forbidden
     *
     * @param Request $request
     * @return Response
     */
    public function error_403(Request $request): Response;

    /**
     * Not found
     *
     * @param Request $request
     * @return Response
     */
    public function error_404(Request $request): Response;

    /**
     * Internal Server Error
     *
     * @param Request $request
     * @return Response
     */
    public function error_500(Request $request): Response;

    /**
     * Service Temporarily Unavailable
     *
     * @param Request $request
     * @return Response
     */
    public function error_503(Request $request): Response;

    /**
     * Custom exception handling
     * @param Request $request
     * @param \Exception $exception
     * @return Response
     */
    public function custom_error(Request $request, \Exception $exception): Response;
}