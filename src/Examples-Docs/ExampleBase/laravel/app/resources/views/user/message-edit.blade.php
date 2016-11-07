@extends('app')
@section('content')
<h1>Hi, I like your application and want to contact you</h1>
{!! PkForm::open(['class'=>'contact form']) !!}
{!! PkForm::hidden('user_id_to',$them->id) !!}
{!! PkForm::textarea('message',null,['class'=>'message area']) !!}
{!! PkForm::button('Send Message', ['name'=>'submit', 'type'=>'submit', 'value'=>'submit', 'class'=>'pkmvc-button btn btn-primary']) !!}
{!! PkForm::close() !!}
@stop