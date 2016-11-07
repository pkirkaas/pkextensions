{{-- This contains the states  --}}

<div class='section state'>
    {!!PkForm::hidden($basename.'[id]', $state->id) !!}
  <div class="input pair">
    {!!PkForm::label($basename.'[name]','State Name') !!}
    {!!PkForm::text($basename.'[name]',$state->name) !!}
  </div>
  <div class="input pair">
    {!!PkForm::label($basename.'[governer]','Governer') !!}
    {!!PkForm::text($basename.'[governer]',$state->governer) !!}
  </div>
</div>