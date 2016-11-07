<?php

/** The Contact form. Takes no parameters * */
use App\Models\Contact;
?>
@extends('app')
@section('content')
<div class='info template contact'>
  <h1>Contact Us</h1>
  <h2>{!! PkHtml::mailto(env('CONTACT_EMAIL'), 'Email us') !!} or submit the Contact Form below</h2>
  {!!PkForm::open()!!}
  <h4 class='h-1-2-3 italic text-align-center'>All fields are optional, but remember to give us some contact info if you want a response</h4>

  <div class='row margin-bottom-5'>
    <div class='col-sm-1 input-label'>
      {!!PkForm::label('name','Name',['class'=>'name text']) !!}
    </div>
    <div class='col-sm-2'>
      {!!PkForm::text('name',null,['class'=>'name text full-width', 'placeholder'=>'Name']) !!}
    </div>

    <div class='col-sm-1 input-label'>
      {!!PkForm::label('telno','Phone',['class'=>'telno text']) !!}
    </div>
    <div class='col-sm-2'>
      {!!PkForm::text('telno',null,['class'=>'telno text full-width', 'placeholder'=>'Telephone']) !!}
    </div>


    <div class='col-sm-1 input-label'>
      {!!PkForm::label('email','Email',['class'=>'email text']) !!}
    </div>
    <div class='col-sm-2'>
      {!!PkForm::text('email',null,['class'=>'email text full-width', 'placeholder'=>'Email']) !!}
    </div>


    <div class='col-sm-1 input-label'>
      {!!PkForm::label('companyname','Company',['class'=>'companyname text']) !!}
    </div>
    <div class='col-sm-2'>
      {!!PkForm::text('companyname',null,['class'=>'companyname text full-width', 'placeholder'=>'Company Name']) !!}
    </div>
  </div>
  <div class='row margin-bottom-5 margin-top-5'>

    <div class='col-sm-1 input-label'>
      {!!PkForm::label('subject','Subject',['class'=>'subject text']) !!}
    </div>
    <div class='col-sm-9'>
      {!!PkForm::text('subject',null,['class'=>'subject text full-width', 'placeholder'=>'Subject']) !!}
    </div>


  </div>

  {!! PkForm::textarea('msg', null, ['class'=>'textarea msg margin-top-5 full-width', 'placeholder'=>'Tell us how we can help you']) !!}



  {!!PkForm::button('Send!',['type'=>'submit', 'name'=>'submit', 'value'=>'submit', 'class'=>'pkmvc-button']) !!}
  {!!PkForm::close()!!}

</div>
@stop
