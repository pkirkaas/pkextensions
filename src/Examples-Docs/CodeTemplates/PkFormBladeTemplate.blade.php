<?php
/** A blade form to be included in a blade template */
?>
<!-- Open Form -->
{!! PkForm::model($user) !!}
  <div class="row">
    <div class='col-sm-4 input-label'>
      {!!PkForm::label('name','Name',['class'=>'name text']) !!}
    </div>
    <div class='col-sm-8'>
      {!!PkForm::text('name',null,
        ['class'=>'name text full-width', 'placeholder'=>'Name']) !!}
    </div>
  </div>
  <div class="row">
    <div class='col-sm-12'> {!! PkForm::submit('Save?',['class'=>'pkmvc-button']) !!} </div>
  </div>
{!! PkForm::model('close') !!}
