<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): Response
    {
        return app(View::class)->render($view, $data);
    }

    protected function json(array|object $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $url): Response
    {
        return Response::redirect($url);
    }

    protected function validate(Request $request, array $rules): array
    {
        return app(Validator::class)->validate($request->all(), $rules);
    }
}
