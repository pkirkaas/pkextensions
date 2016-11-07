<?php
use App\Models\User;
/** This template should be included in all pages that want to use jQuery to open a dialog box to send a message
 * NO PARAMETERS - TEMPLATE VALUES REPLACED BY JAVASCRIPT
 */

?>
<div class='send-message-dialog-template hidden template-container'>
  <div class='send-message-dialog' title='Send a message to __USR_NAME__'>
    <p><em>We won't send any of your contact details - they will get an email from us with a link to view your message on this site</em>
  {!! PkForm::open(['class'=>'contact form', 'route'=>['user_sendmessage', '__CNT_TPL__' ]]) !!}
    {!! PkForm::hidden('user_id_to','__CNT_TPL__') !!}
    <div class="row">
      <div class="col-sm-2"></div>
      <div class="col-sm-8">
    {!! PkForm::textarea('message',null,['class'=>'message area']) !!}
      </div>
    </div>
    {!! PkForm::button('Send Message', ['name'=>'submit', 'type'=>'submit', 'value'=>'submit', 'class'=>'pkmvc-button btn btn-primary']) !!}
  {!! PkForm::close() !!}
  </div>
</div>