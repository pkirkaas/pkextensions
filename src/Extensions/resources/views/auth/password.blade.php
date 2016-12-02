<?php
/** The form to request a password reset */
/** DEPRECATED FOR 'requestreset.blade.php */
?>
@extends('app')
@section('content')
<div class="template forgot-password">
  <div class="template forgot-password-head">
    Forgot Password? Enter your email address for a link to reset it. 
  </div>
  <form method="POST" action="/password/email">
    {!! csrf_field() !!}
    @if (count($errors) > 0)
    <ul>
      @foreach ($errors->all() as $error)
      <li class='error-li'>{{ $error }}</li>
      @endforeach
    </ul>
    @endif
    <div>
      Email
      <input type="email" name="email" value="{{ old('email') }}" placeholder='Email Address' />
             </div>
             <div>
             <button type="submit" class='pkmvc-button'>
             Send Password Reset Link
             </button>
    </div>
  </form>
</div>
@stop