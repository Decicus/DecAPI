@extends('base')

@section('main')
    <div class="container-fluid">
        <div class="row-fluid">
            <div class="span3 centering text-center">
                @if (isset($_GET['404']))
                    <div class="container"><div class="alert alert-danger">404 &mdash; Page not found</div></div>
                @endif

                @if (!empty($message))
                    <div class="container">
                        <div class="alert alert-{{ $message['type'] }}">
                            {{ $message['text'] }}
                        </div>
                    </div>
                @endif

                <h1 class="text-success">{{ env('SITE_TITLE', 'DecAPI') }}</h1>
                <div class="container">
                    <div class="list-group">
                        <a href="https://docs.decapi.me/" class="list-group-item" target="_blank"><i class="fa fa-book fa-1x fa-fw"></i> Documentation (docs.decapi.me)</a>
                        <a href="mailto:alex@thomassen.xyz" class="list-group-item"><i class="fa fa-envelope fa-1x fa-fw"></i> E-mail (alex@thomassen.xyz)</a>
                        <a href="https://blog.decicus.com/" class="list-group-item" target="_blank"><i class="fa fa-pencil-square-o fa-1x fa-fw"></i> Website/blog (blog.decicus.com)</a>
                        <a href="https://twitter.com/Decicus" target="_blank" class="list-group-item"><i class="fa fa-twitter fa-1x fa-fw"></i> Twitter (@Decicus)</a>
                        <a href="https://github.com/Decicus" target="_blank" class="list-group-item"><i class="fa fa-github fa-1x fa-fw"></i> GitHub (Decicus)</a>
                        <a href="https://www.twitch.tv/Decicus" target="_blank" class="list-group-item"><i class="fa fa-twitch fa-1x fa-fw"></i> Twitch (Decicus)</a>
                    </div>
                </div>

                <div class="container">
                    <p class="text-muted">Made with <i class="fa fa-heart fa-1x" style="color: #D10000;"></i> by <a href="https://www.thomassen.xyz/" target="_blank">Alex Thomassen</a></p>

                    <p class="text-info">
                        Project repository on <a href="https://github.com/Decicus/DecAPI" target="_blank"><i class="fa fa-github fa-1x"></i> GitHub</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
