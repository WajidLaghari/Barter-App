<?php

namespace App\Http\Utils;

enum Status: int
{
    case OK = 200;
    case CREATED = 201;
    case FOUND = 302;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case INVALID_REQUEST = 422;
    case INTERNAL_SERVER_ERROR = 500;
    case CONFLICT  = 409;
}
