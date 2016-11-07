{{-- The World Form Part Container --}}
<?php
  $basename = 'world';
?>
<div class='section world-container'>

<h2>For the World called: {!! PkForm::text($basename, $parameters->name) !!}</h2>
<h3>Countries:</h3>

@include('forms.templatable-container',['template_path'=>'multiform.subforms.countries-formpart', 'models'=>$parameters->countries, 'attributename'=>'countries','basename'=>'', 'modelname'=>'country']) 

</div>