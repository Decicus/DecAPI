<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="/css/bootstrap.min.css" type="text/css" />
        <link rel="stylesheet" href="/css/darkly.css" type="text/css" />
        {{-- <link rel="stylesheet" href="/css/font-awesome.min.css" type="text/css" /> --}}
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.1/css/all.css" integrity="sha384-O8whS3fhG2OnA5Kas0Y9l3cfpmYjapjI0E4theH4iuMD+pLhbf6JI0jIMfYcK3yZ" crossorigin="anonymous">
        <link rel="stylesheet" href="/css/custom.css" type="text/css" />
        <title>{{ env('SITE_TITLE', 'DecAPI') }} | {{ $page ?? '[Undefined]' }}</title>
    </head>
    <body>
        @yield('main')
    </body>
</html>
