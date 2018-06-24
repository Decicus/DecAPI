<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="/css/bootstrap.min.css" type="text/css" />
        <link rel="stylesheet" href="/css/darkly.css" type="text/css" />
        {{-- <link rel="stylesheet" href="/css/font-awesome.min.css" type="text/css" /> --}}
        <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-87DrmpqHRiY8hPLIr7ByqhPIywuSsjuQAfMXAE0sMUpY3BM7nXjf+mLIUSvhDArs" crossorigin="anonymous">
        <link rel="stylesheet" href="/css/custom.css" type="text/css" />
        <title>{{ env('SITE_TITLE', 'DecAPI') }} | {{ $page or '[Undefined]' }}</title>
    </head>
    <body>
        @yield('main')
    </body>
</html>
