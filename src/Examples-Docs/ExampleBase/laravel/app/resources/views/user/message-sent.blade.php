@extends('app')
@section('content')
<h1>Your message to {{$them->getname()}} has been sent!</h1>
@stop