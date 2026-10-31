<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Counsellor;

class CounsellorController extends Controller
{
    public function index(Counsellor $counsellors): Response
    {
        return $this->json([
            'success' => true,
            'counsellors' => array_map(
                static fn (array $counsellor): array => self::present($counsellor),
                $counsellors->verifiedList()
            ),
        ]);
    }

    public function show(string $id, Counsellor $counsellors): Response
    {
        $counsellorId = filter_var($id, FILTER_VALIDATE_INT);
        if ($counsellorId === false || $counsellorId < 1) {
            return $this->fail('Counsellor not found.', 404);
        }
        $counsellor = $counsellors->findVerified($counsellorId);
        if ($counsellor === null) {
            return $this->fail('Counsellor not found.', 404);
        }

        return $this->json(['success' => true, 'counsellor' => self::present($counsellor)]);
    }

    private static function present(array $counsellor): array
    {
        $counsellor['id'] = (int) $counsellor['id'];
        $counsellor['standard_fee'] = (float) $counsellor['standard_fee'];
        $counsellor['discovery_fee'] = (float) $counsellor['discovery_fee'];

        return $counsellor;
    }

    private function fail(string $message, int $status): Response
    {
        return $this->json(['success' => false, 'message' => $message], $status);
    }
}
