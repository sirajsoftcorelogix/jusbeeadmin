<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): \App\Core\Response
    {
        return $this->view('home.index', [
            'appName' => (string) config('app.name'),
        ]);
    }

    public function ping(): \App\Core\Response
    {
        return $this->json([
            'status' => 'ok',
            'framework' => (string) config('app.name'),
            'timestamp' => date(DATE_ATOM),
        ]);
    }

}
