<?php

use App\Http\Controllers\IndexController;
use App\Partials\PartialServer;
?>
@extends('app')
@section('content')
<div class='index-index info template'>
  @include('partials.responsive-head',
  ['slug'=>"Here's a Partial..."])


<h1>Welcome to our site</h1>



</div>

@stop
