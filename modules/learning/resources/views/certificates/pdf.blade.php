<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <title>Certificate — {{ $certificate->course->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1a2b3c; margin: 48px; }
        h1 { font-size: 28px; margin-bottom: 8px; }
        .sub { color: #5a6a7a; font-size: 14px; margin-bottom: 32px; }
        .name { font-size: 22px; margin: 24px 0 8px; }
        .course { font-size: 18px; margin-bottom: 24px; }
        .meta { font-size: 12px; line-height: 1.6; }
        .code { font-size: 14px; margin-top: 32px; letter-spacing: 0.08em; }
    </style>
</head>
<body>
    <h1>Certificate of completion</h1>
    <p class="sub">Association of Protecting Exotic Species CIC — APES Intranet</p>
    <p class="name">{{ $certificate->user->name }}</p>
    <p class="course">completed <strong>{{ $certificate->course->title }}</strong></p>
    <div class="meta">
        <p>Issued: {{ $certificate->issued_at?->timezone(config('app.timezone'))->format('j F Y') }}</p>
        @if ($certificate->cpd_hours !== null)
            <p>CPD hours: {{ $certificate->cpd_hours }}</p>
        @endif
        @if ($certificate->expires_on !== null)
            <p>Expires: {{ $certificate->expires_on->format('j F Y') }}</p>
        @endif
    </div>
    <p class="code">Verify code: {{ $certificate->verify_code }}</p>
</body>
</html>
