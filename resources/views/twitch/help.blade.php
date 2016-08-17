@extends('base')

@section('main')
    <div class="container">
        <div class="page-header">
            <h1>{{ env('SITE_TITLE', 'DecAPI') }} - Help Articles</h1>
        </div>

        <div class="list-group">
            @foreach($list as $title => $id)
                <a href="{{ $prefix . $id }}" class="list-group-item">{{ $title }} &mdash; {{ $prefix . $id }}</a>
            @endforeach
        </div>
    </div>
@endsection
