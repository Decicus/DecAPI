<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>BetterTTV Channel Emotes - Home</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@4.5.2/dist/darkly/bootstrap.min.css" integrity="sha384-nNK9n28pDUDDgIiIqZ/MiyO3F4/9vsMtReZK39klb/MtkZI3/LtjSjlmyVPS3KdN" crossorigin="anonymous">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.3/css/all.css" integrity="sha384-SZXxX4whJ79/gErwcOYf+zWLeJdY/qpuqC4cAa9rOGUstPomtqpuNWT9wdPEn2fk" crossorigin="anonymous">
        <style type="text/css">
            body {
                padding-top: 80px;
            }
        </style>
    </head>
    <body>
        @include('bttv.nav')
        <div class="container-fluid">
            <div class="card bg-primary text-white">
                <div class="card-header bg-primary">
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
            <div class="card text-white bg-secondary">
                <div class="card-header bg-dark"><i class="fas fa-1x fa-fw fa-search"></i> Check BetterTTV channel emotes:</div>

                <div class="card-body">
                    <form method="get" action="{{ route('bttv.home') }}">
                        <div class="form-group">
                            <label for="channel"><strong><i class="fab fa-1x fa-fw fa-twitch"></i> Twitch channel name:</strong></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fab fa-1x fa-fw fa-twitch"></i></span>
                                </div>
                                <input type="text" class="form-control" id="channel" name="channel" placeholder="decicus" value="{{ $channel }}">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-dark"><i class="fas fa-1x fa-search"></i> Lookup channel emotes</button>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>
