@extends('base')

@section('main')
    <div class="container mt-4 mw-90">
        {{-- This view is only used in scenarios where we display an error message, e.g. InvalidStateException --}}

        <p class="text-danger">An error occurred when trying to authenticate with your Twitch account.</p>
        @if (!empty($error))
            <p class="text-warning">Reason received from Twitch: <strong>{{ $error }}</strong></p>
        @endif

        <p class="text-info">You can re-authenticate using <a href="{{ $authUrl }}">this URL</a>, if you wish to do so.</p>
    </div>
@endsection
