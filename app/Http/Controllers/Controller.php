<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    // Base controller extended from framework to enable middleware(), callAction(), etc.
}
