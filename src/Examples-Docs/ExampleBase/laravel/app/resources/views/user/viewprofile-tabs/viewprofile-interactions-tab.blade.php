<?php 
/** Common 'interactions' tab for both Borrowers and Lenders. Includes messages
 *  between current user and other - and personal notes by each on each message
 * 
 */
use PkExtensions\PkHtmlRenderer;
$out = new PkHtmlRenderer();
?>
<div class="pkh5">Interactions</div>

@if (ne_arrayish($borrower->conversation()))
  <div class='row'>
    <div class='col-sm-3'>From Name</div>
    <div class='col-sm-3'>From Type</div>
    <div class='col-sm-3'>To Name</div>
    <div class='col-sm-3'>To Type</div>
  </div>
  @foreach ($borrower->conversation() as $message)
  <div class='row'>
    <div class='col-sm-3'>{{$message->fromuser->getname()}}</div>
    <div class='col-sm-3'>{{$message->fromuser->type->type()}}</div>
    <div class='col-sm-3'>{{$message->touser->getname()}}</div>
    <div class='col-sm-3'>{{$message->touser->type->type()}}</div>
  </div>
  <div>
{{$message->message}}
  </div>
<?php /*
 */?>
  @endforeach 
@endif

