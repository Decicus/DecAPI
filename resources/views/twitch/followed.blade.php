@extends('base')

@section('main')
    <div class="container mw-90 mt-4">
        <h1><i class="fab fa-fw fa-twitch"></i> Twitch - Followed</h1>

        <p class="mt-4">You have successfully authenticated and can now use this to get the <i>follow date &amp; time</i> of your viewers.</p>

        <p>
            Here is your token for the followed &amp; followage APIs:
            <pre class="text-warning">{{ $apiToken }}</pre>
            The same token can be used for any channel you are a moderator in.
        </p>

        <If>Below you will find examples for Nightbot &amp; Streamlabs Chatbot. If you have any further questions, please take a look at <a href="{{ route('home') }}">DecAPI's homepage</a> for links to the documentation, the Discord server and more.</p>

        <div class="card mt-4" id="nightbot">
            <div class="card-header">Nightbot</div>

            <div class="card-body">
                <p class="card-text">Add a command that includes the part below and it will be replaced with the follow date &amp; time in the command:</p>
                <pre>$(urlfetch {{ $nightbotRoute }})</pre>
                <small><strong><span class="text-warning">Note:</span> You do <u>not</u> have to change <code>$(channel)</code> or <code>$(touser)</code>. Nightbot will automatically replace them with the correct names when the command is used in your channel.</strong></small>

                <p class="card-text mt-3">For example, if you wanted to display this message:</p>
                <pre>JustAnExampleTwitchUsername has been following since May 6. 2020 - 03:28:37 PM (UTC)</pre>

                <p class="card-text mt-3">You would put this in the command response:</p>
                <pre>$(touser) has been following since $(urlfetch {{ $nightbotRoute }})</pre>
            </div>
        </div>

        <div class="card mt-4" id="streamlabs-chatbot">
            <div class="card-header">Streamlabs Chatbot</div>
            <div class="card-body">
                <p class="card-text">Add a command that includes the part below and it will be replaced with the follow date in the command:</p>
                <pre>$readapi({{ $slcbRoute }})</pre>
                <small><strong><span class="text-warning">Note:</span> You do <u>not</u> have to change <code>$mychannel</code> or <code>$touserid</code>. Streamlabs Chatbot will automatically replace them with the correct names when the command is used in your channel.</strong></small>

                <p class="card-text mt-3">For example, if you wanted to display this message:</p>
                <pre>JustAnExampleTwitchUsername has been following since May 6. 2020 - 03:28:37 PM (UTC)</pre>
                <p class="card-text">You would put this in the command response:</p>
                <pre>$touserid has been following since $readapi({{ $slcbRoute }})</pre>
            </div>
        </div>

        <p class="text text-info mt-2">If you wish to log out from this page, you can do so below. Your token will still be active and any commands using it will continue to work as normal.</p>
        <a class="btn btn-danger" href="{{ route('auth.twitch.logout') }}">Log out</a>
    </div>
@endsection
