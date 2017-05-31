<?php /** The page to come to to reset password */ 
?>
@extends('app')
@section('content')
<div class='template forgot-password password-reset-forgot'>
  <div class="forgot-password-head margin-5 padding-5">
   Reset your password:
  </div>
<form method="POST" action="/password/reset">
    {!! csrf_field() !!}
    @if (count($errors) > 0)
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <div class="row margin-5">
      <div class='col-sm-6 text-align-right'>Email:</div>
      <div class='col-sm-6 text-align-left'><input type="email" name="email" value="{{ old('email') }}" placeholder='Email'></div>
    </div>

    <div class="row margin-5">
      <div class='col-sm-6 text-align-right'>Password:</div>
      <div class='col-sm-6 text-align-left'>  <input type="password" name="password" placeholder='New Password'></div>
    </div>

    <div class="row margin-5">
      <div class='col-sm-6 text-align-right'>  Confirm Password:</div>
      <div class='col-sm-6 text-align-left'>   <input type="password" name="password_confirmation" placeholder="Repeat Password"></div>
    </div>

    <div>
        <button type="submit" class='pkmvc-button'>
            Reset Password
        </button>
    </div>
</form>


</div>
@stop