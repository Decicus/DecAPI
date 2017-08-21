@extends('base')

@section('main')
    <div class="container" style="margin-top: 20px;">
        <div class="jumbotron">
            {{-- This view is only used in scenarios where we display an error message, e.g. InvalidStateException --}}

            <p class="text text-danger">An error occurred when trying to authenticate with your Twitch account.</p>
            @if (!empty($error))
                <p class="text text-warning">Reason received from Twitch: <strong>{{ $error }}</strong></p>
            @endif

            <p class="text text-info">You can re-authenticate using <a href="{{ $authUrl }}">this URL</a>, if you wish to do so.</p>
        </div>
    </div>
@endsection
