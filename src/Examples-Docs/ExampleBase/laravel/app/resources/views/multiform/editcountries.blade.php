{{-- This is the view that loads the form to edit the world --}}
@extends('app')
@section('content')
<div class='section edit countries'>
<h1>Edit the World!</h1>
@include('forms.form-container',['model'=>$world,'template_path'=>$template_path,'parameters'=>$parameters])
</div>
@stop