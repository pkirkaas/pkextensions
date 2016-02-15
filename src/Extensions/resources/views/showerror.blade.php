@extends('app')
@section('content')
    <div class='idx err msg'>
      <h3>Whoops...</h3>
      <div class='error block'>
        @foreach ($error->get('error') as $msg)
        {!! $msg !!}
        @endforeach
      </div>
</div>
@stop