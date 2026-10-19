<?php

namespace App\Http\Controllers;

use App\Traits\{
    ResponseApi,
    Pagination,
    Generator
};

abstract class Controller
{
    use ResponseApi, Pagination, Generator;
}
