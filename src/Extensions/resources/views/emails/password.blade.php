<?php /** The email Template to send password reset link */
?>
@extends('emails.mail-layout')
@section('content')
@include('emails.email-header')
<p>Someone requested a password reset for your email address to your account on
  <span style='
        color: blue;
        font-weight: bold;
        font-family: verdana;
        font-style: oblique;
        '>{{env('SITE_NAME')}}
  </span>.

<p>If it wasn't you, just ignore this email.
<p>Otherwise, click here to reset your password:
<p>@include('emails.email-linkbutton', ['href'=>url('password/reset/'.$token), 'label'=>url('password/reset/'.$token)])
  @stop