<?php

namespace App\Core\Health;

use App\Core\Version\AppVersion;
use Illuminate\Http\JsonResponse;

class HealthzController
{
    public function __invoke(HealthCheck $health, AppVersion $version): JsonResponse
    {
        $checks = $health->run();
        $healthy = ! in_array('fail', $checks, true);
        $revision = is_file(base_path('REVISION')) ? substr(trim((string) file_get_contents(base_path('REVISION'))), 0, 12) : null;

        $body = [
            'status' => $healthy ? 'ok' : 'fail',
            'checks' => $checks,
            'version' => $version->current(),
            'revision' => $revision,
        ];

        // The live preview gate checks the local preview serves this checkout through FastCGI.
        if (app()->environment('local')) {
            $body['local'] = [
                'php' => PHP_VERSION,
                'php_sapi' => PHP_SAPI,
                'git_revision' => (new LocalRevision(base_path()))->current(),
            ];
        }

        return response()->json($body, $healthy ? 200 : 503, [
            'Cache-Control' => 'no-store',
        ]);
    }
}
