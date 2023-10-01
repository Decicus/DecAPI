@extends('base')

@section('main')
    <div class="container" style="margin-top: 20px;">
        <div class="jumbotron">
            <p class="text text-info">You have successfully authenticated and can now use this to get the "followage" of viewers in your channel using bots. Below is an example using the two common ones: Streamlabs Chatbot and Nightbot.</p>

            <p>Here is your token for the followage &amp; followed APIs: <code>{{ $apiToken }}</code></p>

            <h3>Streamlabs Chatbot:</h3>
            <p class="text text-muted">Add a command that includes the part below and it will be replaced with the followage in the command:</p>
            <pre>$readapi({{ $slcbRoute }})</pre>
            <small class="text text-muted">Note: You do not have to change <code>$mychannel</code> or <code>$touserid</code>. Streamlabs Chatbot will automatically replace them with the correct names when used.</small>

            <p class="text text-muted mt-1">For example, if you wanted to display this message:</p>
            <pre>JustAnExampleTwitchUsername has been following for 3 months, 6 days</pre>
            <p class="text text-muted">You would put this in the command response:</p>
            <pre>$touserid has been following for $readapi({{ $slcbRoute }})</pre>

            <h3>Nightbot:</h3>
            <p class="text text-muted">Add a command that includes the part below and it will be replaced with the followage in the command:</p>
            <pre>$(urlfetch {{ $nightbotRoute }})</pre>
            <small class="text text-muted">Note: You do not have to change <code>$(channel)</code> or <code>$(touser)</code>. Nightbot will automatically replace them with the correct names when used.</small>

            <p class="text text-muted mt-1">For example, if you wanted to display this message:</p>
            <pre>JustAnExampleTwitchUsername has been following for 3 months, 6 days</pre>
            <p class="text text-muted">You would put this in the command response:</p>
            <pre>$(touser) has been following for $(urlfetch {{ $nightbotRoute }})</pre>

            <p class="text text-info">If you wish to log out, you can do so below. This will not prevent any commands from working.</p>
            <a class="btn btn-danger" href="{{ route('auth.twitch.logout') }}">Log out</a>
        </div>
    </div>
@endsection
