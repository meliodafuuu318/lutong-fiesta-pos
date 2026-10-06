<?php

namespace App\Http\Controllers;

use App\Traits\{
    ResponseApi,
    Pagination
};

abstract class Controller
{
    use ResponseApi, Pagination;
}
