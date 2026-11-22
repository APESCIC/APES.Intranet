<?php

namespace Modules\Learning\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Learning\Models\Certificate;
use Modules\Learning\Services\CertificateService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CertificateController extends Controller
{
    public function verify(string $code, CertificateService $certificates): View
    {
        $certificate = $certificates->findByVerifyCode($code);

        return view('learning::certificates.verify', [
            'title' => 'Verify certificate',
            'certificate' => $certificate,
            'code' => strtoupper(trim($code)),
        ]);
    }

    public function download(Request $request, Certificate $certificate, CertificateService $certificates): Response
    {
        $user = $request->user();
        if ($user === null || (int) $certificate->user_id !== (int) $user->id) {
            throw new AccessDeniedHttpException('You can only download your own certificate.');
        }

        if ($certificate->isRevoked()) {
            abort(404);
        }

        $pdf = $certificates->pdf($certificate);
        $filename = 'certificate-'.$certificate->verify_code.'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
