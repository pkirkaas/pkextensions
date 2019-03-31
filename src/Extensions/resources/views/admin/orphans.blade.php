<?php
/** Blade Template */
use PkExtensions\Models\PkModel;
$allorphans = PkModel::getAllOrphans(true);
?>
@extends('app')
@section('content')
<div class='template type'>
  <h1>Orphans</h1>
  <h1>STOP!! THIS IS VERY DANGEROUS !! ASSUMES EVERY TABLE CALLS 'blocks' OWNS every table that has a field "block_id" and proposes to delete them all if empty!</h1>
  <table class='pk-tbl fullwidth'>
    <tr><th>Model</th><th>ID</th><th>Delete?</th><th>Parent Key</th><th>Parent Model</th><th>Parent ID</th></tr>
  @foreach ($allorphans as $model => $orphans)
  <tr><td>{{$model}}</td></tr>
    @foreach ($orphans as $orphan)
    <tr><td></td><td>{{$orphan['id']}}</td><td>{!!$orphan['orphan']->deleteRoute()!!}</td></tr>
      @foreach ($orphan['missings'] as $key => $parentArr)
      <tr><td></td><td></td><td></td><td>{{$key}}</td>
        <td>{{$parentArr['parentmodel']}}</td><td>{{$parentArr['parentkey']}}</td></tr>
      @endforeach
    @endforeach
  @endforeach
  </table>
</div>
@stop
