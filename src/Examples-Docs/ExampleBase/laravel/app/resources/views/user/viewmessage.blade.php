<?php /*
 * The template to view individual messages. Requires one parameter:
 * @param Message $message
 */
?>
@extends('app')
@section('content')
<div class='user view message template {{$message->firstView(true)}}'>
@include('user.parts.viewmessage-header-part',['message'=>$message])
<div class='msg-body'>
  {{$message->message}}
</div>

</div>
@stop