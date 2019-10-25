<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>BetterTTV Channel Emotes - Home</title>
        <link href="https://stackpath.bootstrapcdn.com/bootswatch/4.3.1/united/bootstrap.min.css" rel="stylesheet" integrity="sha384-WTtvlZJeRyCiKUtbQ88X1x9uHmKi0eHCbQ8irbzqSLkE0DpAZuixT5yFvgX0CjIu" crossorigin="anonymous">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.11.2/css/all.css" integrity="sha384-KA6wR/X5RY4zFAHpv/CnoG2UW1uogYfdnP67Uv7eULvTveboZJg0qUpmJZb5VqzN" crossorigin="anonymous">
        <style type="text/css">
            body {
                padding-top: 80px;
                padding-bottom: 80px;
            }
        </style>
    </head>
    <body>
        @include('bttv.nav')
        <div class="container-fluid">
            <div class="card bg-info text-white">
                <div class="card-header">
                    <i class="fas fa-1x fa-fw fa-info-circle"></i> BetterTTV Channel Emotes
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Look up the BetterTTV emotes a specific Twitch channel has here.
                        <br>
                        By default all image types are included (both GIFs and PNGs), as well as "shared" emotes from other channels.
                    </p>
                </div>
            </div>
            <br>

            @if(!empty($message))
                <div class="alert alert-warning">
                    {{ $message }}
                </div>
            @endif

            @if(isset($user, $emotes))
                <h2>
                    BetterTTV emotes in channel:
                    <a href="https://www.twitch.tv/{{ $user['name'] }}"><i class="fab fa-1x fa-twitch"></i> {{ $user['display_name'] }}</a>
                </h2>

                @include('bttv.emotelist')
            @endif

            <br>
            <div class="card text-white bg-primary">
                <div class="card-header"><i class="fas fa-1x fa-fw fa-search"></i> Check channel emotes:</div>

                <div class="card-body">
                    <form method="get" action="{{ route('bttv.home') }}">
                        <div class="form-group">
                            <label for="channel"><strong><i class="fas fa-1x fa-user"></i> Channel name:</strong></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-1x fa-user"></i></span>
                                </div>
                                <input type="text" class="form-control" id="channel" name="channel" placeholder="decicus" value="{{ $channel }}">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success"><i class="fas fa-1x fa-search"></i> Submit</button>
                    </form>
                </div>
            </div>
        </div>

        <nav class="navbar fixed-bottom navbar-dark bg-primary" role="navigation">
            <span class="navbar-text">
                Created with <i class="fas fa-heart" style="color: #ff0000;"></i> by <a href="https://thomassen.sh/" class="navbar-link">Alex Thomassen</a>.
            </span>

            <span class="navbar-text justify-content-end">
                Powered by the <a href="https://betterttv.com/" class="navbar-link">BetterTTV</a> API.
            </span>
        </nav>
    </body>
</html>
