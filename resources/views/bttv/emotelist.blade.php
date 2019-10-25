<ul class="list-group">
    @foreach($emotes as $emote)
        <li class="list-group-item list-group-item-success">
            Emote code:
            <a href="https://betterttv.com/emotes/{{ $emote['id'] }}">
                {{ $emote['code'] }}
            </a>
        </li>

        @php
            $cdnUrl = 'https://cdn.betterttv.net/emote/' . $emote['id'];
        @endphp

        <li class="list-group-item">
            <img src="{{ $cdnUrl }}/1x" alt="{{ $emote['code'] }} - 28x28" />
            <img src="{{ $cdnUrl }}/2x" alt="{{ $emote['code'] }} - 56x56" />
            <img src="{{ $cdnUrl }}/3x" alt="{{ $emote['code'] }} - 112x112" />

            @if (!empty($emote['user']))
                @php
                    $shared = $emote['user'];
                @endphp

                <br><br>
                <strong>
                    Shared by:
                    <a href="https://www.twitch.tv/{{ $shared['name'] }}"><i class="fab fa-1x fa-twitch"></i> {{ $shared['display_name'] }}</a>
                </strong>
            @endif
        </li>
    @endforeach
</ul>
