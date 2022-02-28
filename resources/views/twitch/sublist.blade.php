@extends('base')

@section('main')
    <div class="container" style="margin-top: 20px;">
        <div class="jumbotron">
            <p class="text text-info">You have successfully authenticated and can now use this to display {{ $action }} subscriber(s) in your channel using chatbots.</p>
 
            <h3>Streamlabs Chatbot (formerly Ankhbot):</h3>
            <p class="text text-muted">Add a command that includes the part below and it will be replaced with {{ $action }} subscriber(s):</p>
            <pre>$readapi({{ $route }}/$mychannel)</pre>
            <p class="text text-muted">For example, if you want to show @if ($action == 'latest')your last @elseif ($action == 'random')a random @endif subscriber of your stream:</p>
            <pre>{{ ucfirst($action) }} subscriber is: {{ $channel }}</pre>
            <p class="text text-muted">You would put this in the command response:</p>
            <pre>{{ ucfirst($action) }} subscriber is: $readapi({{ $route }}/$mychannel)</pre>

            <h3>Nightbot:</h3>
            <p class="text text-muted">Add a command that includes the part below and it will be replaced with {{ $action }} subscriber(s):</p>
            <pre>$(urlfetch {{ $route }})</pre>
            <p class="text text-muted">For example, if you want to show @if ($action == 'latest')your last @elseif ($action == 'random')a random @endif subscriber of your stream:</p>
            <pre>{{ ucfirst($action) }} subscriber is: {{ $channel }}</pre>
            <p class="text text-muted">You would put this in the command response:</p>
            <pre>{{ ucfirst($action) }} subscriber is: $(urlfetch {{ $route }})</pre>   

            <p class="text text-info">If you wish to log out, you can do so below. This will not prevent any of the above commands from working.</p>
            <a class="btn btn-danger" href="{{ route('auth.twitch.logout') }}">Log out</a>
        </div>

        <div class="row">
            <h3>Optional query string parameters:</h3>
            <p class="text text-muted">These parameters are optional and can be added at the end of the url.</p>
            <table id="qs-body" class="table table-bordered table-striped table-hover">
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
                        <td>The amount of subscribers to retrieve - Default: <code>1</code>.</td>
                        <td><code>int</code></td>
                    </tr>
                    <tr>
                        <th>field</th>
                        <td>What field from the user object to retrieve. See <a href="https://dev.twitch.tv/docs/api/reference#get-broadcaster-subscriptions" target="blank">Twitch API docs</a> to see which ones are available. Default: <code>user_name</code>.</td>
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
@endsection
