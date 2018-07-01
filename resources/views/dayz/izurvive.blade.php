@extends('base')

@section('main')
    <div class="container">
        <div class="page-header">
            <h1>{{ env('SITE_TITLE', 'DecAPI') }} - {{ $page }}</h1>
        </div>

        <div class="list-group">
            @foreach($list as $title => $id)
                <a href="{{ $prefix . $id }}" class="list-group-item">
                    <h4 class="list-group-item-heading">
                        {{ $title }} &mdash; {{ $prefix . $id }}
                    </h4>
                    <p class="list-group-item-text">
                        <strong>Different variations/spellings:</strong> &mdash; {{ implode(', ', $spellings[$title]) }}
                    </p>
                </a>
            @endforeach
        </div>
    </div>
@endsection
