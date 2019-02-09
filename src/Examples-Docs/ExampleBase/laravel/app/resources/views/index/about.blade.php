@extends('app')
@section('content')
<div class='index about info template'>
  <h3 class='italic'>Welcome to our website</h3>
  <h5>About <div class='site-name inline'>{{config('app.site_name')}}</div></h5>
  <div class='about body'>

    <p>This is about <div class='site-name inline'>{{config('app.site_name')}}</div>; a cool website.
    <p><a class='contact lnk text-align-center' href="{!!route('index_contact')!!}">Contact Us</a> <p>for more information.
  </div>
</div>
@stop
