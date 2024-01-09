@extends('base')

@section('main')
    <div class="container mw-90 mt-4">
        <h1><i class="fab fa-fw fa-twitch"></i> Twitch - Random subscriber</h1>
        <p>You have successfully authenticated and can now use this API to get a random subscriber. Below is an example using two common chatbots: Nightbot &amp; Streamlabs Chatbot.</p>

        <div class="card mt-4" id="nightbot">
            <div class="card-header">Nightbot</div>

            <div class="card-body">
                <p class="card-text">Add a command that includes the part below and it will be replaced with your subscriber count in the command:</p>
                <pre>$(urlfetch {{ $route }}/$(channel))</pre>
                <small><strong><span class="text-warning">Note:</span> You do <u>not</u> have to change <code>$(channel)</code> &mdash; Nightbot will automatically replace <code>$(channel)</code> with the correct channel name.</strong></small>

                <hr />

                <p class="card-text mt-3">For example, if you wanted to display this message:</p>
                <pre>Random subscriber is: {{ $channel }}</pre>

                <p class="card-text mt-3">You would put this in the command response:</p>
                <pre>Random subscriber is: $(urlfetch {{ $route }}/$(channel))</pre>
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
                <pre>Random subscriber is: {{ $channel }}</pre>

                <p class="card-text mt-3">You would put this in the command response:</p>
                <pre>Random subscriber is: $readapi({{ $route }}/$mychannel)</pre>
                <small><strong><span class="text-warning">Note:</span> You do <u>not</u> have to change <code>$mychannel</code> &mdash; StreamLabs Chatbot will automatically replace <code>$mychannel</code> with the correct channel name.</strong></small>
            </div>
        </div>

        <p class="text text-info mt-4">If you wish to log out, you can do so below. Any bots using this API will continue working, even if you click "Log out".</p>
        <a class="btn btn-danger" href="{{ route('auth.twitch.logout') }}">Log out</a>

        <div class="card mt-4" id="query-params">
            <div class="card-header">Optional query string parameters</div>
            <div class="card-body">
                <p class="text text-muted">
                    These parameters are optional and can be added at the end of the url.
                    <br />
                    Quick example: <code><a href="{{ $route }}/{{ $channel }}?count=10&separator= - &field=user_login">{{ $route }}/{{ $channel }}?count=10&separator= - &field=user_login</a></code>
                </p>
                <table id="qs-body" class="table table-bordered table-striped table-hover table-dark">
                    <thead>
                        <tr>
                            <th>Parameter name:</th>
                            <th>Description:</th>
                            <th>Type:</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th>count</th>
                            <td>The <i>maximum</i> amount of subscribers to retrieve - Default: <code>1</code>.</td>
                            <td><code>int</code></td>
                        </tr>
                        <tr>
                            <th>field</th>
                            <td>What field from the user object to retrieve. See under "Response body => data" in the <a href="https://dev.twitch.tv/docs/api/reference#get-broadcaster-subscriptions" target="blank">Twitch API docs</a> to see which ones are available. Default: <code>user_name</code>.</td>
                            <td><code>string</code></td>
                        </tr>
                        <tr>
                            <th>separator</th>
                            <td>What character(s) should separate each subscriber value from each other. Default: <code>, </code>.</td>
                            <td><code>string</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
