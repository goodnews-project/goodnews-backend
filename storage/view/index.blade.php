@extends('layout')
@section('content')
    @foreach ($statuses as $status)
        @include('common.tweet', ['status' => $status])
    @endforeach
    @include('common.page')
@endsection
