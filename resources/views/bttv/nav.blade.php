<nav class="navbar fixed-top navbar-expand-lg navbar-dark bg-primary" role="navigation">
    <a href="{{ route('bttv.home') }}" class="navbar-brand">BetterTTV Channel Emotes</a>
    <ul class="navbar-nav mr-auto">
        <li class="nav-item {{ $page === 'home' ? 'active' : '' }}">
            <a href="{{ route('bttv.home') }}" class="nav-link"><i class="fas fa-home fa-1x"></i> Home</a>
        </li>
    </ul>
</nav>
