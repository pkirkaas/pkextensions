{{-- This contains the Senator's Mistresses --}}

<div class='section mistress'>
    {!!PkForm::hidden($basename.'[id]', $mistress->id) !!}
  <div class="input pair">
    {!!PkForm::label($basename.'[name]','Mistress Name') !!}
    {!!PkForm::text($basename.'[name]',$mistress->name) !!}
  </div>
  <div class="input pair">
    {!!PkForm::label($basename.'[age]','Age') !!}
    {!!PkForm::text($basename.'[age]',$mistress->age) !!}
  </div>
</div>