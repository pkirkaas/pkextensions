<?php
/** Test CSS Template */
$fontstr="The Quick Brown Fox Jumped Over The Lazy Dogs. And then <em>kept on running</em> for hours and miles!";
?>
@extends('app')
@section('content')
<div class='page-template type'>
  <div class='page-title'>This demos various site CSS:  .page-template .page-title</div>
  <div class='page-title inverse'>.page-template .page-title.inverse</div>
  <div class='page-subtitle'>Fonts:  .page-template .page-subtitle</div>


  <div class='verdana'><b>Verdana:</b> <?=$fontstr?> </div>
  <div class='oswald'><b>Oswald:</b> <?=$fontstr?> </div>
  <div class='lato'><b>Lato:</b> <?=$fontstr?> </div>
  <div class='roboto'><b>Roboto:</b> <?=$fontstr?> </div>
  <div class='open-sans'><b>Open Sans:</b> <?=$fontstr?> </div>
  <div class='montserrat'><b>Montserrat:</b> <?=$fontstr?> </div>
  <div class='raleway'><b>Raleway:</b> <?=$fontstr?> </div>
  <div class='droid-sans'><b>Droid Sans:</b> <?=$fontstr?> </div>

  <div class='page-subtitle inverse'>Section:  .page-template .page-subtitle.inverse</div>
  <div class='section'>This is the content of a section</div>
  




</div>
@stop
