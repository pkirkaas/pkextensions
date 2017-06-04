<?php
/** Test CSS Template */
$fontstr="The Quick Brown Fox Jumped Over The Lazy Dogs. And then <em>kept on running</em> <b>for hours and miles!</b>";

$fsclass = 'fs-6';
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
<h2>Testing putting too many columns in row</h2>
<div class="row bg-fef">
  <div class="col-md-6 bg-ef4 bold fs-6">My first md-6 col in the row</div>
  <div class="col-md-6 bg-e8f bold fs-6">My second md-6 col in the row</div>
  <div class="col-md-6 bg-8ac bold fs-6">My third md-6 col in the row</div>
  <div class="col-md-6 bg-fa8 bold fs-6">My fourth md-6 col in the row</div>
  <div class="col-md-6 bg-4a8 bold fs-6">My fifth md-6 col in the row</div>
       
</div>
  <!--
<div class="big-space">
  <select class="pk-inp tac">
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
  -->
<h2>Testing various site-header types</h2>
  <div class="sh bg-ff8 tac">SH bg-ff8 tac</div>
  <div class="sh1 bg-8ff tac">SH1 bg-8ff tac</div>
  <div class="sh5 bg-cff tac">SH5 bg-f8f tac</div>
  <div class="sh10 bg-ffc tac">SH10 bg-f8f tac</div>
  <div class="shb bb">SHb</div>
  <div class="shb1 bb">SHb1</div>
  <div class="shb5 bb">SHb5</div>
  <div class="shb10">SHb10</div>
  <div class="sh">SH</div>
  <div class="sh1">SH1</div>
  <div class="sh5">SH5</div>
  <div class="sh10">SH10</div>
  <div class="shib">SHib</div>
  <div class="shib1">SHib1</div>
  <div class="shib5">SHib5</div>
  <div class="shib10">SHib10</div>
  <div class="shir">SHir</div>
  <div class="shir1">SHir1</div>
  <div class="shir5">SHir5</div>
  <div class="shir10">SHir10</div>
  <div class="shir table m-h-a">SHir table m-h-a</div>
  <div class='section'>
    <div class="shir1 table m-h-a">SHir1 table m-h-a</div>
  </div>
  <div class="shir5 table m-h-a">SHir5 table m-h-a</div>
  <div class="shir10 table m-h-a">SHir10 table m-h-a</div>
<div class='page-template type'>
  <div class='page-title'>This demos various site CSS:  .page-template .page-title</div>
  <div class='page-title inverse'>.page-template .page-title.inverse</div>
  <div class='page-subtitle'>Fonts:  .page-template .page-subtitle</div>


  <div class="verdana <?=$fsclass?>"><b>Verdana:</b> <?=$fontstr?> </div>
  <div class="oswald <?=$fsclass?>"><b>Oswald:</b> <?=$fontstr?> </div>
  <div class="lato <?=$fsclass?>"><b>Lato:</b> <?=$fontstr?> </div>
  <div class="roboto <?=$fsclass?>"><b>Roboto:</b> <?=$fontstr?> </div>
  <div class="open-sans <?=$fsclass?>"><b>Open Sans:</b> <?=$fontstr?> </div>
  <div class="montserrat <?=$fsclass?>"><b>Montserrat:</b> <?=$fontstr?> </div>
  <div class="raleway <?=$fsclass?>"><b>Raleway:</b> <?=$fontstr?> </div>
  <div class="droid-sans <?=$fsclass?>"><b>Droid Sans:</b> <?=$fontstr?> </div>

  <div class='page-subtitle inverse'>Section:  .page-template .page-subtitle.inverse</div>
  <div class='section'>This is the content of a section</div>
  




</div>
@stop
