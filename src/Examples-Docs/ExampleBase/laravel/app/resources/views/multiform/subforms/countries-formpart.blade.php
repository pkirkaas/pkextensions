{{-- This contains the countries and subtemplates for states and senators --}}

<div class='section country'>
  <p>Country Info: 
    {!!PkForm::hidden($basename.'[id]', $country->id) !!}
  <div class="input pair">
    {!!PkForm::label($basename.'[name]','Country Name') !!}
    {!!PkForm::text($basename.'[name]',$country->name) !!}
  </div>
  <div class="input pair">
    {!!PkForm::label($basename.'[capitol]','Capitol') !!}
    {!!PkForm::text($basename.'[capitol]',$country->capitol) !!}
  </div>
  <div class="input pair">
    {!!PkForm::label($basename.'[president]','President') !!}
    {!!PkForm::text($basename.'[president]',$country->president) !!}
  </div>
  <div class='section'>
    <h3>States</h3>
      @include('forms.templatable-container',['template_path'=>'multiform.subforms.states-formpart', 'models'=>$country->states, 'attributename'=>'states','basename'=>$basename, 'modelname'=>'state']) 
  </div>
  <div class='section'>
    <h3>Senators</h3>
      @include('forms.templatable-container',['template_path'=>'multiform.subforms.senators-formpart', 'models'=>$country->senators, 'attributename'=>'senators','basename'=>$basename, 'modelname'=>'senator']) 
  </div>
</div>