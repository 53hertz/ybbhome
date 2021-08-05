@extends('layouts.app')
@section('title', '错误')

@section('content')
  <div class="card">
    <div class="card-header">哎呦，出错啦~~</div>
    <div class="card-body text-center">
      <h1>{{ $msg }}</h1>
      <a class="btn btn-warning" href="{{ route('root') }}">返回首页</a>
    </div>
  </div>
@endsection
