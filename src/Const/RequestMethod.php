<?php

declare(strict_types=1);

namespace Ingenerator\MicroFramework\Const;

enum RequestMethod: string
{
    case GET = 'GET';
    case QUERY = 'QUERY';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case TRACE = 'TRACE';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case POST = 'POST';
    case PATCH = 'PATCH';
    case CONNECT = 'CONNECT';
}
