<?php
/** Blade Template, with PkHtmlRenderer & PkMultiSubformRenderer - for scrolling subforms (Cart=>items) */
use PkExtensions\Models\PkModel;
use App\Models\Client;
use PkExtensions\PkHtmlRenderer;
use PkExtensions\PkMultiSubformRenderer;
use App\References\DiagnosisRef;

$out = new PkHtmlRenderer();
$infoout = new PkHtmlRenderer();

$diagnoses = $client->diagnoses;
$diagrows = [];
foreach ($diagnoses as $diagnosis) {
  $diagrows[]= ['diagnosiscode_id' => $diagnosis->diagnosiscode_id,'id'=>$diagnosis->id];
}
$diagsf = new PkMultiSubformRenderer([
    'basename'=>'diagnoses',
    'tpl_fields' =>  ['id', 'diagnosiscode_id'],
  ]);
//Test adding attributes
$diagsf->append_atts('create_button','tst-create-btn-class');
$diagsf->append_atts('deletable_dataset','tst-dds-class');
$diagsf->append_atts('js_template',['class'=>'tst-add-class-arr']);

$diagsf->subform_data = $diagrows;
$diagsf->hidden('id');
//$diagsf->text('diagnosiscode_id');
$diagsf->select('diagnosiscode_id',DiagnosisRef::getSelectList(true,true));



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
$out[]=$diagsf;

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

