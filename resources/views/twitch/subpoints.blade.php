@extends('base')

@section('main')
    <div class="container mw-90 mt-4">
        <h1><i class="fab fa-fw fa-twitch"></i> Twitch - Subscriber points (Subpoints)</h1>
        <p>You have successfully authenticated and can now use this API to see your subscriber points. Below is an example using two common chatbots: Nightbot &amp; Streamlabs Chatbot.</p>

        <div class="card mt-4" id="nightbot">
            <div class="card-header">Nightbot</div>

            <div class="card-body">
                <p class="card-text">Add a command that includes the part below and it will be replaced with the number of your subscriber points in the command:</p>
                <pre>$(urlfetch {{ $route }}/$(channel))</pre>
                <small><strong><span class="text-warning">Note:</span> You do <u>not</u> have to change <code>$(channel)</code> &mdash; Nightbot will automatically replace <code>$(channel)</code> with the correct channel name.</strong></small>

                <hr />

                <p class="card-text mt-3">For example, if you wanted to display this message:</p>
                <pre>{{ $name }} currently has 69 subpoints.</pre>

                <p class="card-text mt-3">You would put this in the command response:</p>
                <pre>$(channel) currently has $(urlfetch {{ $route }}/$(channel)) subpoints</pre>
                <small><strong><span class="text-warning">Note:</span> You do <u>not</u> have to change <code>$(channel)</code> &mdash; Nightbot will automatically replace <code>$(channel)</code> with the correct channel name.</strong></small>
            </div>
        </div>

        <div class="card mt-4" id="streamlabs-chatbot">
            <div class="card-header">StreamLabs Chatbot</div>

            <div class="card-body">
                <p class="card-text">Add a command that includes the part below and it will be replaced with the number of your subscriber points in the command:</p>
                <pre>$readapi({{ $route }}/$mychannel)</pre>
                <small><strong><span class="text-warning">Note:</span> You do <u>not</u> have to change <code>$mychannel</code> &mdash; StreamLabs Chatbot will automatically replace <code>$mychannel</code> with the correct channel name.</strong></small>

                <hr />

                <p class="card-text mt-3">For example, if you wanted to display this message:</p>
                <pre>{{ $name }} currently has 69 subpoints.</pre>

                <p class="card-text mt-3">You would put this in the command response:</p>
                <pre>$mychannel currently has $readapi({{ $route }}/$mychannel) subpoints</pre>
                <small><strong><span class="text-warning">Note:</span> You do <u>not</u> have to change <code>$mychannel</code> &mdash; StreamLabs Chatbot will automatically replace <code>$mychannel</code> with the correct channel name.</strong></small>
            </div>
        </div>
    </div>
@endsection
