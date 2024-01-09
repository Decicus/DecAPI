@extends('base')

@section('main')
    <div class="container mw-90 mt-4">
        <h1><i class="fab fa-fw fa-twitch"></i> Twitch - Subscriber count (Subcount)</h1>
        <p>You have successfully authenticated and can now use this API to see your subscriber count. Below is an example using two common chatbots: Nightbot &amp; Streamlabs Chatbot.</p>

        <div class="card mt-4" id="nightbot">
            <div class="card-header">Nightbot</div>

            <div class="card-body">
                <p class="card-text">Add a command that includes the part below and it will be replaced with your subscriber count in the command:</p>
                <pre>$(urlfetch {{ $route }}/$(channel))</pre>
                <small><strong><span class="text-warning">Note:</span> You do <u>not</u> have to change <code>$(channel)</code> &mdash; Nightbot will automatically replace <code>$(channel)</code> with the correct channel name.</strong></small>

                <hr />

                <p class="card-text mt-3">For example, if you wanted to display this message:</p>
                <pre>{{ $name }} currently has 69 subscribers.</pre>

                <p class="card-text mt-3">You would put this in the command response:</p>
                <pre>$(channel) currently has $(urlfetch {{ $route }}/$(channel)) subscribers</pre>
                <small><strong><span class="text-warning">Note:</span> You do <u>not</u> have to change <code>$(channel)</code> &mdash; Nightbot will automatically replace <code>$(channel)</code> with the correct channel name.</strong></small>
            </div>
        </div>

        <div class="card mt-4" id="streamlabs-chatbot">
            <div class="card-header">StreamLabs Chatbot</div>

            <div class="card-body">
                <p class="card-text">Add a command that includes the part below and it will be replaced with your subscriber count in the command:</p>
                <pre>$readapi({{ $route }}/$mychannel)</pre>
                <small><strong><span class="text-warning">Note:</span> You do <u>not</u> have to change <code>$mychannel</code> &mdash; StreamLabs Chatbot will automatically replace <code>$mychannel</code> with the correct channel name.</strong></small>

                <hr />

                <p class="card-text mt-3">For example, if you wanted to display this message:</p>
                <pre>{{ $name }} currently has 69 subscribers.</pre>

                <p class="card-text mt-3">You would put this in the command response:</p>
                <pre>$mychannel currently has $readapi({{ $route }}/$mychannel) subscribers</pre>
                <small><strong><span class="text-warning">Note:</span> You do <u>not</u> have to change <code>$mychannel</code> &mdash; StreamLabs Chatbot will automatically replace <code>$mychannel</code> with the correct channel name.</strong></small>
            </div>
        </div>
    </div>
@endsection
