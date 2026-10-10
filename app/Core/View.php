<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    public function render(string $view, array $data = []): Response
    {
        $basePath = (string) config('app.view_path');
        $viewFile = $basePath . '/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($viewFile)) {
            return response('View not found: ' . $view, 500);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        return response($content);
    }
}
