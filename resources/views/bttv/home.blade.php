<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>BetterTTV Channel Emotes - Home</title>
        <link rel="stylesheet" href="/css/bootstrap.min.css" type="text/css" />
        <link rel="stylesheet" href="/css/united.css" type="text/css" />
        <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-87DrmpqHRiY8hPLIr7ByqhPIywuSsjuQAfMXAE0sMUpY3BM7nXjf+mLIUSvhDArs" crossorigin="anonymous">
    </head>
    <body>
        @include('bttv.nav')
        <div class="container-fluid">

            <div class="page-header"><h1>BetterTTV Channel Emotes</h1></div>
            <div class="jumbotron">
                <p class="text-info">BetterTTV now has <a href="https://community.nightdev.com/t/new-bttv-feature-custom-emotes/3316">per-channel emotes</a>. This page is meant to be an easier way of checking the BetterTTV emotes on peoples channels.</p>

                @if(!empty($channel))
                    @if(empty($message) && !empty($emotes))
                        <ul class="list-group">
                        @foreach($emotes as $emote)
                            <li class="list-group-item list-group-item-success">{{ $emote['code'] }} &mdash; {!! strtolower(trim($emote['channel'])) === strtolower($channel) ? 'Owned' : '<a href="https://www.twitch.tv/' . strtolower($emote['channel']) . '">Shared by ' . $emote['channel'] . '</a>' !!}</li>

                            <li class="list-group-item">
                                <img src="{{ str_replace(['__id__', '__image__'], [$emote['id'], '1x'], $template) }}" alt="{{ $emote['code'] }} - 28x28" />
                                <img src="{{ str_replace(['__id__', '__image__'], [$emote['id'], '2x'], $template) }}" alt="{{ $emote['code'] }} - 56x56" />
                                <img src="{{ str_replace(['__id__', '__image__'], [$emote['id'], '3x'], $template) }}" alt="{{ $emote['code'] }} - 112x112" />
                            </li>
                        @endforeach
                        </ul>
                    @else
                        <div class="alert alert-warning">
                            {!! $message !!}
                        </div>
                    @endif
                @endif

                <div class="panel panel-success">
                    <div class="panel-heading">Check a channel:</div>

                    <div class="panel-body">
                        <form method="get" action="{{ route('bttv.home') }}">
                            <div class="form-group">
                                <label for="channel">Channel name:</label>
                                <input type="text" class="form-control" id="channel" name="channel" placeholder="decicus" value="{{ $channel }}">
                            </div>

                            <button type="submit" class="btn btn-success">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <nav class="navbar navbar-default navbar-fixed-bottom" role="navigation">
            <div class="container-fluid">
                <p class="navbar-text">Created with <i class="fas fa-heart" style="color: #ff0000;"></i> by <a href="https://www.thomassen.xyz/" class="navbar-link">Alex Thomassen</a>.</p>

                <p class="navbar-text pull-right">Powered by the <a href="https://nightdev.com/betterttv/" class="navbar-link">BetterTTV</a> API.</p>
            </div>
        </nav>
    </body>
</html>
