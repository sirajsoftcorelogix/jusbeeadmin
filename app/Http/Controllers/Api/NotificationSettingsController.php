<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\NotificationSettings;

class NotificationSettingsController extends Controller
{
    public function setUserNotification(Request $request, NotificationSettings $settings): Response
    {
        $fields = [];
        $errors = [];
        $body = $request->input();
        foreach (NotificationSettings::FIELDS as $field => $column) {
            $providedKeys = array_values(array_filter(
                [$field, $column],
                static fn (string $key): bool => array_key_exists($key, $body)
            ));
            if ($providedKeys === []) {
                continue;
            }
            if (count($providedKeys) === 2 && $body[$providedKeys[0]] !== $body[$providedKeys[1]]) {
                $errors[$field] = 'Provide the setting only once.';
                continue;
            }
            $value = $body[$providedKeys[0]];
            if (!in_array($value, [true, false, 1, 0, '1', '0'], true)) {
                $errors[$providedKeys[0]] = 'Must be a boolean value.';
                continue;
            }
            $fields[$field] = in_array($value, [true, 1, '1'], true);
        }
        if ($errors !== []) {
            return $this->fail('Validation failed.', 422, $errors);
        }
        if ($fields === []) {
            return $this->fail('Provide at least one notification setting to update.', 422, [
                'settings' => 'Provide one or more notification boolean fields.',
            ]);
        }

        $userId = (int) $request->attribute('api_user')['id'];
        $settings->saveForUser($userId, $fields);
        $savedSettings = $settings->findForUser($userId);
        if ($savedSettings === null) {
            throw new \RuntimeException('Notification settings were saved but could not be loaded.');
        }

        return $this->json([
            'success' => true,
            'message' => 'Notification settings saved.',
            'notification_settings' => $this->present($savedSettings),
        ]);
    }

    public function fetchUserNotification(Request $request, NotificationSettings $settings): Response
    {
        $userId = (int) $request->attribute('api_user')['id'];
        $storedSettings = $settings->findForUser($userId);
        if ($storedSettings === null) {
            return $this->json([
                'success' => true,
                'notification_settings' => [
                    'app_user_id' => $userId,
                    'daily_reminder' => false,
                    'streak_end_reminder' => false,
                    'push_notification' => false,
                    'lunar_notification' => false,
                    'added_date_time' => null,
                    'updated_date_time' => null,
                ],
            ]);
        }

        return $this->json([
            'success' => true,
            'notification_settings' => $this->present($storedSettings),
        ]);
    }

    private function present(array $settings): array
    {
        return [
            'app_user_id' => (int) $settings['app_user_id'],
            'daily_reminder' => (bool) $settings['daily_reminder'],
            'streak_end_reminder' => (bool) $settings['streak_end_reminder'],
            'push_notification' => (bool) $settings['push_notification'],
            'lunar_notification' => (bool) $settings['lunar_notification'],
            'added_date_time' => $settings['added_date_time'],
            'updated_date_time' => $settings['updated_date_time'],
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
