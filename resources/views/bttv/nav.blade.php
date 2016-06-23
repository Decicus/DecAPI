<nav class="navbar navbar-default navbar-static-top" role="navigation">
    <div class="container-fluid">
        <div class="navbar-header">
            <a href="/bttv/" class="navbar-brand">BetterTTV Channel Emotes</a>
        </div>
        <ul class="nav navbar-nav">
            <li <?php echo ( $page === 'home' ? 'class="active"' : '' ); ?>><a href="{{ route('bttv.home') }}"><i class="fa fa-home fa-1x"></i> Home</a></li>
        </ul>
    </div>
</nav>
