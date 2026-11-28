@extends('admin.layout')
@section('title', __('admin.board'))
@section('heading', __('admin.board'))
@section('content')
<div data-board="{{ route('admin.board', ['partial' => 1]) }}">
  @include('admin._board')
</div>
@endsection
