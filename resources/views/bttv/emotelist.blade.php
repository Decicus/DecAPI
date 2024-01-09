<div class="row mt-4 mb-4">
@foreach($emotes as $emote)
    @php
        $index = $loop->index + 1;
        $cdnUrl = sprintf('https://cdn.betterttv.net/emote/%s', $emote['id']);
    @endphp

    <div class="col text-center mt-4 mb-4">
        <div class="card bg-secondary text-white">
            <div class="card-header bg-primary">
                Emote code:
                <a href="https://betterttv.com/emotes/{{ $emote['id'] }}">
                    {{ $emote['code'] }}
                </a>
            </div>
            <div class="card-body">
                <p class="card-text">
                    <img src="{{ $cdnUrl }}/3x" alt="{{ $emote['code'] }} - 3x" />
                </p>

                <p class="card-text">All sizes: <a href="{{ $cdnUrl }}/1x">1x</a> | <a href="{{ $cdnUrl }}/2x">2x</a> | <a href="{{ $cdnUrl }}/3x">3x</a></p>
            </div>
            @if (!empty($emote['user']))
                @php
                    $shared = $emote['user'];
                @endphp

                <div class="card-footer bg-dark">
                    Shared by
                    <a href="https://www.twitch.tv/{{ $shared['name'] }}"><i class="fab fa-1x fa-twitch"></i> {{ $shared['display_name'] }}</a>
                </div>
            @else
                <div class="card-footer bg-dark">
                    Uploaded by
                    <a href="https://www.twitch.tv/{{ $user['name'] }}"><i class="fab fa-1x fa-twitch"></i> {{ $user['display_name'] }}</a>
                </div>
            @endif
        </div>
    </div>

    @if ($index % 4 === 0)
        </div>
        <div class="row">
    @endif
@endforeach
</div>
