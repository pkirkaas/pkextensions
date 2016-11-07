<?php
/* 
 * Basic Edit User Profile Blade 
 */
use App\Models\User;
?>
@extends('app')
@section('content')
<div class='user editprofile template'>
  <h1>User Profile</h1>
  {!! PkForm::model($user) !!}


  <div class="row">
    <div class='col-sm-4 input-label'>
      {!!PkForm::label('name','Name',['class'=>'name text']) !!}
    </div>
    <div class='col-sm-8'>
      {!!PkForm::text('name',null,['class'=>'name text full-width', 'placeholder'=>'Name']) !!}
    </div>
  </div>

  <div class="row">
    <div class='col-sm-12'> {!! PkForm::submit('Save?',['class'=>'pkmvc-button']) !!} </div>
  </div>

  
  {!! PkForm::model('close') !!}
</div>
@stop











