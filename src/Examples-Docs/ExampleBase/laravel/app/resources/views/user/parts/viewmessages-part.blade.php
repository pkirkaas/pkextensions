<?php
/*
 * The View Messages Partial - only displays the other user name, date sent,
 * and message content
 * @param Collection of Message $messages 
 * @param User $them 
 * @param String $css_class - optional css class for the wrapper
 */
if (empty($css_class)) $css_class = '';
if (empty($messages)) $messages = [];
?>
<div class='msgs part {{$css_class}}'>
  <div class="sect-head-5">Messages between you and {{$them->getname()}}</div>
  @foreach ($messages as $message)
  @if($message->isFromUser())
  <div class ='a-msg from-me'>
    <div class='ahdr'>
   <div class='who inline'> Me</div>
      @else
  <div class ='a-msg from-them {{$message->firstView()}}'>
    <div class='ahdr'>
      <div class='who inline'>{{$message->fromuser->getname()}}</div>
      @endif
      <div class='bcc-when inline'>{{sqlDateToFriendly($message->created_at)}}</div>
    </div>
    <div class='msg-content'>{{$message->message}}</div>
  </div>
  @endforeach
</div>