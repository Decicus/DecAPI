@extends('base')

@section('main')
    <div class="container" style="margin-top: 20px;">
        <div class="jumbotron">
            <p class="text text-info">You have successfully authenticated and can now use this to display subscriber age for a specific user in your channel using chatbots.</p>

            <p class="text text-warning">
                Please keep in mind that the subscription age <strong>might not be accurate to the subscription anniversaries</strong> posted in chat (e.g. <code>Decicus has been subscribed for 21 months in a row!</code>).
                <br>
                <br>
                This type of subscription only tracks when payment info was updated, while the anniversaries have a grace period of roughly 30 days before they expire.
                <br>
                <br>
                What this means is that if someone temporarily cancels their subscription to renew it using different payment information (e.g. their old credit/debit card expires), the subscription age will be counted from the date they re-subscribed using the new payment information.
                <br>
                <br>
                Another instance is Twitch Prime: If you have a subscriber that consistently uses their Twitch Prime to subscribe to your channel every month, <strong>the subscription age will reset every month</strong>.
                <br>
                <br>
                This message only serves as a "heads up", to make sure people know the difference.
            </p>

            <h3>Ankhbot:</h3>
            <p class="text text-muted">Add a command that includes the part below and it will be replaced with the age of the current subscription for the user using the command:</p>
            <pre>$readapi({{ $route }}/$user)</pre>
            <p class="text text-muted">For example, if you wanted to display this message whenever the user "Decicus" types in your chat:</p>
            <pre>Decicus has been subscribed for: 3 months, 1 week, 2 days</pre>
            <p class="text text-muted">You would put this in the command response:</p>
            <pre>$user has been subscribed for: $readapi({{ $route }}/$user)</pre>

            <h3>Nightbot:</h3>
            <p class="text text-muted">Add a command that includes the part below and it will be replaced with the age of the current subscription for the user using the command:</p>
            <pre>$(urlfetch {{ $route }}/$(user))</pre>
            <p class="text text-muted">For example, if you wanted to display this message whenever the user "Decicus" types in your chat:</p>
            <pre>Decicus has been subscribed for: 3 months, 1 week, 2 days</pre>
            <p class="text text-muted">You would put this in the command response:</p>
            <pre>$(user) has been subscribed for: $(urlfetch {{ $route }}/$(user))</pre>

            <p class="text text-info">If you wish to log out, you can do so below. This will not prevent any of the above commands from working.</p>
            <a class="btn btn-danger" href="{{ route('auth.twitch.logout') }}">Log out</a>
        </div>
    </div>
@endsection
