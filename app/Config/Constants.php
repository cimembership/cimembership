<?php
declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Constants extends BaseConfig
{
    /**
     * @deprecated Use \CodeIgniter\HTTP\Response::HTTP_OK
     */
    public int $HTTP_OK = 200;

    /**
     * @deprecated Use \CodeIgniter\HTTP\Response::HTTP_CREATED
     */
    public int $HTTP_CREATED = 201;

    /**
     * @deprecated Use \CodeIgniter\HTTP\Response::HTTP_ACCEPTED
     */
    public int $HTTP_ACCEPTED = 202;

    /**
     * @deprecated Use \CodeIgniter\HTTP\Response::HTTP_NO_CONTENT
     */
    public int $HTTP_NO_CONTENT = 204;

    /**
     * @deprecated Use \CodeIgniter\HTTP\Response::HTTP_BAD_REQUEST
     */
    public int $HTTP_BAD_REQUEST = 400;

    /**
     * @deprecated Use \CodeIgniter\HTTP\Response::HTTP_UNAUTHORIZED
     */
    public int $HTTP_UNAUTHORIZED = 401;

    /**
     * @deprecated Use \CodeIgniter\HTTP\Response::HTTP_FORBIDDEN
     */
    public int $HTTP_FORBIDDEN = 403;

    /**
     * @deprecated Use \CodeIgniter\HTTP\Response::HTTP_NOT_FOUND
     */
    public int $HTTP_NOT_FOUND = 404;

    /**
     * @deprecated Use \CodeIgniter\HTTP\Response::HTTP_METHOD_NOT_ALLOWED
     */
    public int $HTTP_METHOD_NOT_ALLOWED = 405;

    /**
     * @deprecated Use \CodeIgniter\HTTP\Response::HTTP_NOT_ACCEPTABLE
     */
    public int $HTTP_NOT_ACCEPTABLE = 406;

    /**
     * @deprecated Use \CodeIgniter\HTTP\Response::HTTP_CONFLICT
     */
    public int $HTTP_CONFLICT = 409;

    /**
     * @deprecated Use \CodeIgniter\HTTP\Response::HTTP_UNSUPPORTED_MEDIA_TYPE
     */
    public int $HTTP_UNSUPPORTED_MEDIA_TYPE = 415;

    /**
     * @deprecated Use \CodeIgniter\HTTP\Response::HTTP_INTERNAL_ERROR
     */
    public int $HTTP_INTERNAL_ERROR = 500;

    /**
     * @deprecated Use \CodeIgniter\HTTP\Response::HTTP_NOT_IMPLEMENTED
     */
    public int $HTTP_NOT_IMPLEMENTED = 501;

    /**
     * @deprecated Use \CodeIgniter\HTTP\Response::HTTP_BAD_GATEWAY
     */
    public int $HTTP_BAD_GATEWAY = 502;

    /**
     * @deprecated Use \CodeIgniter\HTTP\Response::HTTP_SERVICE_UNAVAILABLE
     */
    public int $HTTP_SERVICE_UNAVAILABLE = 503;

    // CIMembership specific constants
    public const VERSION = '4.0.0';
    public const MIN_PHP_VERSION = '8.1.0';
    public const REQUIRED_EXTENSIONS = ['json', 'mbstring', 'intl', 'curl'];
}
