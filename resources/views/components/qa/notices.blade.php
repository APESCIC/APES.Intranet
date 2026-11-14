@props(['profile', 'confirm' => false, 'notice' => null])
@if ($notice)
    <p class="sg-qa-notice" role="status">{{ $notice }}</p>
@endif
@if ($confirm)
    <div class="sg-qa-notice" role="status" data-qa-confirm>
        <p>That page needs a fresh sign-in. Confirm you are still testing as QA {{ \App\Core\Qa\QaLogin::label($profile) }}.</p>
        <form method="post" action="{{ route('qa.login') }}" class="mt-3">
            @csrf
            <input type="hidden" name="profile" value="{{ $profile }}">
            <button type="submit" class="sg-qa-btn">Confirm QA {{ \App\Core\Qa\QaLogin::label($profile) }}</button>
        </form>
    </div>
@endif
