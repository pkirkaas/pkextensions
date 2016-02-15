@extends('app')
@section('content')
    <div class='idx msg'>
      <div class='message block'>
        @foreach ($message->get('message') as $msg)
        {!! $msg !!}
        @endforeach
      </div>
</div>
@stop