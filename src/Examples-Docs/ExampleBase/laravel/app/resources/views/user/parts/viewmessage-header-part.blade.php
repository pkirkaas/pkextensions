<?php /*
 * Shows the header for a message. Required Parameter:
 * @param Message $message
 */
?>
<div class='row head'>
@if (Auth::user() && (Auth::user()->id == $message->user_id_from))
  <div class='col-sm-4'>Sent to {{$message->touser->getname()}}</div>
  <div class='col-sm-4'>{{sqlDateToFriendly($message->created_at)}}</div>
  <div class='col-sm-2'>{!!$message->touser->linkToViewProfile('View Profile','pkmvc-button inline')!!}</div>
  <div class='col-sm-2'>
      @include('controls.sendmessage-button',['them' => $message->touser])
  </div>

@else
  <div class='col-sm-4'>Message from {{$message->fromuser->getname()}}</div>
  <div class='col-sm-4'>Sent: {{sqlDateToFriendly($message->created_at)}}</div>
  <div class='col-sm-2'>{!!$message->fromuser->linkToViewProfile('View Profile','pkmvc-button inline')!!}</div>
  <div class='col-sm-2'>
      @include('controls.sendmessage-button',['them' => $message->touser, 'label' => 'Reply?'])
  </div>
@endif
</div>
