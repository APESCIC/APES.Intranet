<?php

namespace App\Core\Health;

use Illuminate\Http\JsonResponse;

class HealthzController
{
    public function __invoke(HealthCheck $health): JsonResponse
    {
        $checks = $health->run();
        $healthy = ! in_array('fail', $checks, true);
        $revision = is_file(base_path('REVISION')) ? substr(trim((string) file_get_contents(base_path('REVISION'))), 0, 12) : null;

        return response()->json([
            'status' => $healthy ? 'ok' : 'fail',
            'checks' => $checks,
            'revision' => $revision,
        ], $healthy ? 200 : 503, [
            'Cache-Control' => 'no-store',
        ]);
    }
}
