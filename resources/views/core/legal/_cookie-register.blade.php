<section class="legal-page__cookie-register space-y-3" aria-labelledby="cookie-register-heading">
    <h2 id="cookie-register-heading" class="font-serif text-xl font-semibold">Cookie register</h2>
    <p class="text-sm text-sg-muted">
        These cookies are strictly necessary for the intranet to work. We do not use a cookie consent banner for them.
    </p>
    <div class="overflow-x-auto">
        <table class="sg-table w-full text-left text-sm">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Purpose</th>
                    <th scope="col">Duration</th>
                    <th scope="col">Type</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cookieRegister as $cookie)
                    <tr>
                        <td><code>{{ $cookie['name'] }}</code></td>
                        <td>{{ $cookie['purpose'] }}</td>
                        <td>{{ $cookie['duration'] }}</td>
                        <td>{{ $cookie['type_label'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>