{{-- This contains the senators for the countries and subtemplates for their mistresses --}}

<div class='section senator'>
    {!!PkForm::hidden($basename.'[id]', $senator->id) !!}
  <div class="input pair">
    {!!PkForm::label($basename.'[name]','Senator Name') !!}
    {!!PkForm::text($basename.'[name]',$senator->name) !!}
  </div>
  <div class='section'>
    <h3>Mistresses</h3>
      @include('forms.templatable-container',['template_path'=>'multiform.subforms.mistresses-formpart', 'models'=>$senator->mistresses, 'attributename'=>'mistresses','basename'=>$basename, 'modelname'=>'mistress']) 
  </div>
</div>