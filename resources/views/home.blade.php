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
                    <h3>DecAPI-related links:</h3>
                    <div class="list-group">
                        <a href="https://docs.decapi.me/" class="list-group-item" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-fw fa-book"></i> Documentation (docs.decapi.me)
                        </a>

                        <a href="https://links.decapi.me/discord" class="list-group-item" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-fw fa-discord"></i> Discord
                        </a>

                        <a href="https://github.com/Decicus/DecAPI" class="list-group-item" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-github"></i> GitHub - Decicus/DecAPI
                        </a>
                    </div>

                    <h4>Personal links:</h4>
                    <div class="list-group">
                        <a href="mailto:alex@thomassen.xyz" class="list-group-item">
                            <i class="fas fa-envelope fa-fw"></i> E-mail (alex@thomassen.xyz)
                        </a>

                        <a href="https://www.patreon.com/Decicus" class="list-group-item" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-fw fa-patreon"></i> Patreon - Decicus
                        </a>
                        <a href="https://thomassen.sh/" class="list-group-item" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-fw fa-pen-square"></i> Website &amp; blog (thomassen.sh)
                        </a>

                        <a href="https://twitter.com/Decicus" target="_blank" rel="noopener noreferrer" class="list-group-item">
                            <i class="fab fa-fw fa-twitter"></i> Twitter (@Decicus)
                        </a>
                    </div>
                </div>

                <div class="container">
                    <p class="text-muted">
                        Made with <i class="fas fa-heart" style="color: #D10000;"></i> by <a href="https://thomassen.sh/" target="_blank" rel="noopener noreferrer">Alex Thomassen</a>
                    </p>

                    <p class="text-info">
                        Project repository on <a href="https://github.com/Decicus/DecAPI" target="_blank" rel="noopener noreferrer"><i class="fab fa-github"></i> GitHub</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
