<?php
/** The template for showing all messages in, all out, or conversation between a particular user
 * 
 * @param MessageCollection $messages - REQUIRED - the messages to show, or empty
 * @param User $them - optional - if only showing conversation between me and them
 */
use App\Models\User;
use App\Models\Message;
if (empty($them)) $them = null;

if (!Auth::user() instanceOf User) throw new Exception("Must be logged in");
?>
@extends('app')
@section('content')
<div class='user messages template'>
  <div class='template-heading'>{!! numberItems($messages, 'Message')!!}</div>

  @foreach ($messages as $message)
  @if($message->isFromUser())
  <div class='row from me msg deletable-data-set'>

    <div class='col-sm-2'>
      <div class='to-usr'>To <b>{{$message->touser->getname()}}</b></div>
      <div class='sent-date'>{{sqlDateToFriendly($message->created_at)}}</div>
      @include('controls.deletemessage-button',['message'=>$message, 'class'=>'full-width block'])
    </div>

    <div class='col-sm-8'> <div class='inner-col-wrap from me msg block msg-content'>{{$message->message}}</div></div>

    <div class='col-sm-2'>
      <div class='msg-button visit-btn'>@include('controls.viewprofile-button', ['them'=>$message->touser,'class'=>'full-width block','label'=>'Visit Profile'])</div>
      <div class='msg-button'>@include('controls.sendmessage-button',['them' => $message->touser, 'class'=>'full-width'])</div>
    </div>
  </div>
  @else 
  <div class='row from them msg  deletable-data-set {{$message->fromAdminClass()}} {{$message->firstView()}}'>

    <div class='col-sm-2'>
      <div class='from-usr'>From <b>{{$message->fromuser->getname()}}</b></div>
      <div class='sent-date'>{{sqlDateToFriendly($message->created_at)}}</div>
      @include('controls.deletemessage-button',['message'=>$message, 'class'=>'full-width block'])
    </div>

    @if ($message->isFromAdmin())
    <div class='col-sm-8'> <div class='inner-col-wrap from them msg block msg-content'>{!!$message->message!!}</div></div>
    @else
    <?php /*
    <div class='col-sm-8'> <div class='inner-col-wrap from them msg block msg-content'>{{$message->message}}</div></div>
     */ ?>
    <div class='col-sm-8'> <div class='inner-col-wrap from them msg block msg-content'>{{$message->message}}</div></div>
    @endif

    <div class='col-sm-2 '>
      <div class='msg-button visit-btn'>@include('controls.viewprofile-button', ['them'=>$message->fromuser,'class'=>'full-width block','label'=>'Visit Profile Here'])</div>
      <div class='msg-button'>@include('controls.sendmessage-button',['them' => $message->fromuser, 'label' => 'Reply', 'class'=>'full-width'])</div>
      <div class='msg-button'>  @include('controls.viewconversation-button',
        ['them'=>$message->fromuser, 'class'=>'full-width block ', 'label'=>'View'])
      </div>
    </div>
  </div>
  @endif
  @endforeach
  @stop