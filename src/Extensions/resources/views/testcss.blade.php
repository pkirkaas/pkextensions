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
  }
/*****   Some Colors  ***/

.afs0 { font-size: 10px;}
.afs1 { font-size: 11px;}
.afs2 { font-size: 12px;}
.afs4 { font-size: 14px;}
.afs6 { font-size: 16px;}
.afs8 { font-size: 18px;}
.afs20 { font-size: 20px;}
.afs24 { font-size: 24px;}
.afs28 { font-size: 28px;}

.gr2 { background: linear-gradient( #02aab0, #00cdac); }
.gr3 { background: linear-gradient( to bottom, #0fb8ad 0%, #1fc8db 51%, #2cb5e8 75%); }
.gr4 { background: linear-gradient( #0fb8ad, #1fc8db); }
.gr1 { background: linear-gradient(blue, pink);}
.bC30 { background-color: #C30; }
.b193542 { background-color: #193542; }
.b004BA8 { background-color: #004BA8; }
.bB94E3C { background-color: #B94E3C; }
.b772B14 { background-color: #772B14; }
.b0A4082 { background-color: #0A4082; }
.b9B59B6 { background-color: #9B59B6; }
.b1C90F3 { background-color: #1C90F3; }
.bF4307C { background-color: #F4307C; }
.b3B5998 { background-color: #3B5998; }
.b283E4A { background-color: #283E4A; }
.b0092BD { background-color: #0092BD; }
.gr1 { border: solid black 2px;}

.bs1 {box-shadow: 2px 2px 1px 1px rgba(0,0,0,.5) }
.bs2 {box-shadow: 3px 3px 2px 2px rgba(0,0,200,.5) }

</style>
@extends('app')
@section('content')
<style>
.sbk , .vbk {
  /*
  display: inline-block;
  line-height: 4em;
  display: table-cell;
  */
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  min-width: 10em;
  /*
  min-height: 4em;
  */
  margin: .5em;
  padding: .5em .5em;
  border-radius: .5em;
  color: white;
  font-family: verdana;
  font-weight: bold;
}

.vbk {
  writing-mode: vertical-lr;
  text-orientation: upright;
  min-height: 10em;
  min-width: 4em;
  flex-direction: row;
}

.vced {
  display: inline-block;
  vertical-aligne: middle;
  line-height: normal;
}
</style>
<!--
<h2>Testing putting too many columns in row</h2>
<div class="row bg-fef">
  <div class="col-md-6 bg-ef4 bold fs-6">My first md-6 col in the row</div>
  <div class="col-md-6 bg-e8f bold fs-6">My second md-6 col in the row</div>
  <div class="col-md-6 bg-8ac bold fs-6">My third md-6 col in the row</div>
  <div class="col-md-6 bg-fa8 bold fs-6">My fourth md-6 col in the row</div>
  <div class="col-md-6 bg-4a8 bold fs-6">My fifth md-6 col in the row</div>
       
</div>
-->
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
  <h2>Testing backgound colors, box-shadows, & gradients</h2>
<div class='afs0 sbk bC30 bs1'>  background-color: #C30; </div>
<div class='afs1 sbk b193542  bs1'> background-color: #193542; </div>
<div class='afs2 sbk b004BA8  bs1'>{ background-color: #004BA8; }</div>
<div class='afs4 sbk bB94E3C  bs1'>{ background-color: #B94E3C; }</div>
<div class='afs6 sbk b772B14  bs1'>{ background-color: #772B14;; }</div>
<div class='afs8 sbk b0A4082'><div class='vced'>{ background-color: #0A4082; }</div></div>
<div class='afs10 sbk bF4307C'> { background-color: #F4307C;}</div>
<div class='afs20 sbk b9B59B6'> { background-color: #9B59B6;  afs20 front}</div>
<div class='sbk b1C90F3 bs2 afs20'> { background-color: #1C90F3; afs20 back}</div>
<div class='sbk b3B5998  bs2 afs20'> background-color: #3B5998; </div>
<div class='sbk b283E4A bs2'> { background-color: #283E4A; }</div>
<div class='sbk gr1 bs2'> { background-color: #gr1; }</div>
<div class='sbk b0092BD bs2'> { background-color: #0092BD; }</div>
<div class='sbk gr1  bs1'>{ background-color: gr1; }</div>
<div class='sbk gr2  bs2'>{ background-color: gr2;; }</div>
<div class='sbk gr4  bs2'>{ background-color: gr4;; }</div>

<!--
<div class='vbk gr3  bs2'>{ background-color: gr3;; }</div>
<div class='vbk b3B5998  bs2'>{ background-color: b3B5998 gr3;; }</div>
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
  -->
  




</div>
@stop
