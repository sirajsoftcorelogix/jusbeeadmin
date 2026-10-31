<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\JournalLog;

class JournalLogController extends Controller
{
    public function index(Request $request, JournalLog $journalLogs): Response
    {
        $type = $request->query('type');
        if (!is_string($type) || !in_array($type, JournalLog::TYPES, true)) {
            return $this->fail('A valid type query parameter is required.', 422, [
                'type' => 'Allowed values: ' . implode(', ', JournalLog::TYPES) . '.',
            ]);
        }

        $userId = (int) $request->attribute('api_user')['id'];
        $entries = array_map(
            fn (array $entry): array => $this->present($entry),
            $journalLogs->listForUser($userId, $type)
        );

        return $this->json(['success' => true, 'journal_logs' => $entries]);
    }

    public function store(Request $request, JournalLog $journalLogs): Response
    {
        $type = $request->input('type');
        $title = $request->input('title');
        $description = $request->input('description');
        $favorite = $request->input('favorite', false);
        $errors = [];

        if (!is_string($type) || !in_array($type, JournalLog::TYPES, true)) {
            $errors['type'] = 'Provide a valid journal log type.';
        }
        if (!is_string($title) || trim($title) === '' || mb_strlen(trim($title)) > 255) {
            $errors['title'] = 'Title is required and must be 255 characters or fewer.';
        }
        if (!is_string($description) || trim($description) === '' || strlen($description) > 65535) {
            $errors['description'] = 'Description is required and must fit within 65,535 bytes.';
        }
        if (!in_array($favorite, [true, false, 1, 0, '1', '0'], true)) {
            $errors['favorite'] = 'Favorite must be a boolean value.';
        }
        if ($errors !== []) {
            return $this->fail('Validation failed.', 422, $errors);
        }

        $userId = (int) $request->attribute('api_user')['id'];
        $id = $journalLogs->createForUser(
            $userId,
            $type,
            trim($title),
            trim($description),
            in_array($favorite, [true, 1, '1'], true)
        );
        $entry = $journalLogs->find($id);
        if ($entry === null) {
            throw new \RuntimeException('Journal log was created but could not be loaded.');
        }

        return $this->json([
            'success' => true,
            'message' => 'Journal log created.',
            'journal_log' => $this->present($entry),
        ], 201);
    }

    private function present(array $entry): array
    {
        return [
            'id' => (int) $entry['id'],
            'type' => $entry['type'],
            'app_user_id' => (int) $entry['app_user_id'],
            'title' => $entry['title'],
            'description' => $entry['description'],
            'favorite' => (bool) $entry['favorite'],
            'added_date_time' => $entry['added_date_time'],
        ];
    }

    private function fail(string $message, int $status, array $errors = []): Response
    {
        $body = ['success' => false, 'message' => $message];
        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return $this->json($body, $status);
    }
}
