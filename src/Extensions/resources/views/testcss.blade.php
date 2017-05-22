<?php
/** Test CSS Template */
$fontstr="The Quick Brown Fox Jumped Over The Lazy Dogs. And then <em>kept on running</em> for hours and miles!";
?>
<style>
  .big-space {
    min-height: 15em;
    border: solid #888 1px;
    background-color: #ffa;
    text-align: center;
  }
  select {
    text-align-last: center;
    /*
width: 5em;
*/
  }
</style>
@extends('app')
@section('content')
<div class="big-space">
  <!--
  <select class="pk-inp tac">
  -->
  <p>Top Paragraph</p>
    <br>
  <input type="checkbox" value="1">
  <br>
  <p>Bottom Paragraph
    <br>
  <select class=' tac ' placeholder='Please select a value'>
    <option></option>
    <option value="1">ONE</option>
    <option value="2">Two</option>
    <option value="3">And this will be a really long entry</option>
    <option value="4">This one is shorter</option>
    <option value="5">What, no 6?</option>
    <option value="7" >Right to 7</option>
    <option value="8">8</option>
    <option value="9">The Big Nine</option>
  </select>








</div>
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
