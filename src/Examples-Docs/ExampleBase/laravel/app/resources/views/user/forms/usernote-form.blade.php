<?php /*
 * The form to submit or delete "my" notes about a user. Required param:
 * @param string|null $note - the note about the other user
 */
?>
<div class='row note-form'>
  <div class='col-sm-12'>
    {!!PkForm::open() !!}
    <div class='row note-head'>
      <div class='col-sm-9 margin-top-5'>Make private notes about this user. They won't see them</div>
      <div class='col-sm-1'>

    {!!PkForm::button('Save',['name'=>'submit','type'=>'submit','value'=>'save', 'class'=> 'pkmvc-button inline bcc-nopadding']) !!}
      </div>
      <div class='col-sm-1'>

    {!!PkForm::button('Delete',['name'=>'submit','type'=>'submit','value'=>'delete', 'class'=> 'pkmvc-button inline bcc-nopadding']) !!}
      </div>
    </div>
    {!!PkForm::textarea('note',$note,['class'=>'full-width', 'placeholder'=>'Enter your notes here']) !!}
    {!!PkForm::close() !!}
  </div>
</div>
