<?php
/** Blade Template */
use PkExtensions\Models\PkModel;
use App\Models\Client;
use PkExtensions\PkHtmlRenderer;
$out = new PkHtmlRenderer();
$infoout = new PkHtmlRenderer();
$out[] =  PkForm::model($client);
$infoout->wrap([
    'value' => PkForm::text('fname',null,['placeholder'=>'First Name']),
    'raw'=>true,
    'label' => 'Client First Name',
    'labelAttributes' => 'block tpm-label',
    'valueAttributes' => 'block tpm-value',
    'wrapperAttributes' => 'col-xs-4 tpm-wrapper',
]);

 #Note arguement 'true' to ::getRefArr(true) - prepends null=>'None' to array
 $infoout->wrap([
      'value' => PkForm::select('insurance_status_id',App\References\InsuranceStatusRef::getRefArr(true),null),
      'raw'=>true,
      'label' => 'Insurance Status',
      'labelAttributes' => 'block tpm-label',
      'valueAttributes' => 'block tpm-value',
      'wrapperAttributes' => 'col-xs-4 tpm-wrapper',
    ]);
$out[] = $infoout;

$out[] =   PkForm::button('Submit', ['type'=>'submit', 'name'=>'submit','value'=>'submit',
      'class'=>'pkmvc-button block fullwidth','title'=>"Save Changes"]);
 $out[] = PkForm::close();
?>
@extends('app')
@section('content')
<div class='template type'>
  <h1>The Client Edit Template</h1>

  <?= $out ?>



</div>
@stop

