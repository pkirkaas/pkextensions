<?php

/** Blade Template */
use PkExtensions\Models\PkModel;
?>
@extends('app')
@section('content')
<script>
/** Set up arrays of data for Vue to use */
var allTreeInfo = [];
</script>
<div class='template type'>
  <div id="vuemnt">
  <h1>Edit JSON Tree Models here</h1>
  @foreach ($treePanes as $treePane)
    {!!$treePane!!}   
  @endforeach
  <?= view('admin.treepane',['allTreeInfo'=>$allTreeInfo])?>
  </div>
</div>
@stop
