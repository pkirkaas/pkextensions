<?php

/** Blade Template */
use PkExtensions\Models\PkModel;
?>
@extends('app')
@section('content')
<div class='template type'>
  <h1>Edit JSON Tree Models here</h1>
  <?= view('admin.treepane'); ?>
</div>
@stop
