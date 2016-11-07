<?php

/** The template for showing all messages/conversation between me & a particular user
 * 
 * @param MessageCollection $messages - REQUIRED - the messages to show, or empty
 * @param User $them - REQUIRED - The conversant
 */
use App\Models\User;
use App\Models\Message;

if (empty($them || !$them->exists)) throw new Exception("Invalid Conversant");
if (!Auth::user() instanceOf User) throw new Exception("Must be logged in");
?>
@extends('app')
@section('content')
<div class='user conversation messages template'>
  <div class='row conversation head'>
    <div class='thread head col-sm-8'>Conversation be between me and <div class='conversant-name'>{{$them->getname()}}</div></div>
    <div class='col-sm-2'>
      <div class='msg-button'>@include('controls.viewprofile-button', ['them'=>$them,'class'=>'full-width block','label'=>'Visit Profile'])</div>
    </div>
    <div class='col-sm-2'>
      <div class='msg-button'>@include('controls.sendmessage-button',['them' => $them, 'class'=>'full-width'])</div>
    </div>
  </div>
  <h2 class='some-head'>{!! numberItems($messages, 'Message')!!}</h2>
  @foreach ($messages as $message)

  @if($message->isFromUser())
  <div class='outer-row-wrap'>
  <div class='row from me msg'>
    <div class='col-sm-3 from-sender'>
      <div class='to-usr'>From <b>Me:</b></div>
      <div class='sent-date'>{{sqlDateToFriendly($message->created_at)}}</div>
    </div>

    <div class='col-sm-8'> <div class='from me msg block msg-content'><a href="{!! route('user_viewmessage',[$message])!!}"><div class='speech-bubble left'><div class="bubble-content">{{$message->message}}</div></div></a></div></div>
  </div>
    </div>

  @else 

  <div class='outer-row-wrap'>
  <div class='row from them msg {{$message->firstView(true)}}'>
    <div class='col-sm-8'> <div class='from them msg block msg-content'><a href='{!! route('user_viewmessage',[$message])!!}'><div class='speech-bubble right'><div class="bubble-content">{{$message->message}}</div></div></a></div></div>
    <div class='col-sm-3 from-sender'>
      <div class='from-usr'>From <b>{{$message->fromuser->getname()}}</b></div>
      <div class='sent-date'>{{sqlDateToFriendly($message->created_at)}}</div>
    </div>
  </div>
  </div>
  @endif
  @endforeach
  @stop