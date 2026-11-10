@if (session('status'))
    <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-sg-field bg-sg-error-mist p-3 text-sg-error" role="alert">
        <p class="font-semibold">Please check the form.</p>
        <ul class="mt-1 list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
