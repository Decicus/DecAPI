<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="/css/bootstrap.min.css" type="text/css" />
        <link rel="stylesheet" href="/css/darkly.css" type="text/css" />
        {{-- <link rel="stylesheet" href="/css/font-awesome.min.css" type="text/css" /> --}}
        <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.11.2/css/all.css" integrity="sha384-zrnmn8R8KkWl12rAZFt4yKjxplaDaT7/EUkKm7AovijfrQItFWR7O/JJn4DAa/gx" crossorigin="anonymous">
        <link rel="stylesheet" href="/css/custom.css" type="text/css" />
        <title>{{ env('SITE_TITLE', 'DecAPI') }} | {{ $page ?? '[Undefined]' }}</title>
    </head>
    <body>
        @yield('main')
    </body>
</html>
