<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="/css/darkly.css" type="text/css" />
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.14.0/css/all.css" integrity="sha384-HzLeBuhoNPvSl5KYnjx0BT+WB0QEEqLprO+NBkkk5gbc67FTaL7XIGa2w1L0Xbgc" crossorigin="anonymous">
        <link rel="stylesheet" href="/css/custom.css" type="text/css" />
        <title>{{ env('SITE_TITLE', 'DecAPI') }} | {{ $page ?? '[Undefined]' }}</title>
    </head>
    <body>
        @yield('main')
    </body>
</html>
