@extends('app')
@section('content')
<div class='login template'>
  <h1>Login</h1>
  {!! PkForm::open(['class'=>'login']) !!}
  <div class='row'>
    <div class='col-sm-6 pklbl'>
      {!!  PkForm::label('email', 'Email'); !!}
    </div>
    <div class='col-sm-6 input'>
      {!!  PkForm::text('email', old('email'), ['placeholder'=>'Email']); !!}	
    </div>
  </div>
  <div class='row'>
    <div class='col-sm-6 pklbl'>
      {!!  PkForm::label('password', 'Password'); !!}
    </div>
    <div class='col-sm-6 input'>
      {!!  PkForm::password('password',['placeholder'=>'Password']); !!}	
    </div>
  </div>
  <div class='row'>
    <div class='col-sm-6 pklbl'>
      {!!  PkForm::label('remember', 'Remember Me?'); !!}
    </div>
    <div class='col-sm-6'>
      {!!  PkForm::checkbox('remember'); !!}	
    </div>
  </div>
  <div class='text-align-center'>
    {!! PkForm::button('Login', ['name'=>'submit', 'type'=>'submit', 'value'=>'save_changes', 'class'=>'btn btn-primary']) !!}
  </div>


  {!! PkForm::close() !!}
  <div class='row forgot-register'>
  <div class='col-sm-6 forgot-pwd text-align-center'>
            <a href='{{url('/auth/password')}}'>Forgot Password?</a>
  </div>
  <div class='col-sm-6 forgot-pwd text-align-center'>
            <a href="{!!route('auth_registerborrower')!!}">Register</a>
  </div>
  </div>
  </div>

</div>
@stop