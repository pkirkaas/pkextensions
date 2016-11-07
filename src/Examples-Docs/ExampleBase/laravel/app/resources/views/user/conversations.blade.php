<?php

/** The template for listing all conversations - all exchanges between this user and others
 * @param UserCollection $users - REQUIRED - empty, or collection of users we have exchanged messages with
 */
use App\Models\User;

if (!Auth::user() instanceOf User) throw new Exception("Must be logged in");
?>
@extends('app')
@section('content')
<?php
//Let's make some variables to hold all our classes while we're playing...
/*
  $col_classes_1 = " col-lg-1 pk-col-lg-1 col-md-1 col-sm-2 pk-col-md-1 pk-col-sm-2 pk-flex-resize-lg";
  $col_classes_2 = "col-lg-2 pk-col-lg-2 col-md-2 col-sm-4 pk-col-md-2 pk-col-sm-4 pk-flex-resize-lg";
  $col_classes_3 = "col-lg-1 pk-col-lg-1 col-md-2 col-sm-4 pk-col-md-2 pk-col-sm-4 pk-flex-resize-lg";
  $col_classes_4 = "col-lg-2 pk-col-lg-2 col-md-4 col-sm-8 pk-col-md-4 pk-col-sm-8 no-padding pk-flex-resize-lg";
  $labels = '  hidden-lg-up pk-lbl res-lbl';
 * 
 */
$col_classes_1 = " col-lg-1 pk-col-lg-1";
$col_classes_2 = "col-lg-2 pk-col-lg-2";
$col_classes_3 = "col-lg-1 pk-col-lg-1";
$col_classes_4 = "col-lg-2 pk-col-lg-2";
$labels = '  hidden-lg-up pk-lbl res-lbl';
?>

<div class='user messages conversations template'>
  <div class='template-list-head'>{!! numberItems($users, 'Conversation') !!}</div>
  @if (count($users))
  <div class='conversant set'>
    <div class='row conversant head pk-row-sm hidden-md-down'>
      <div class='pk-col-lg-2 pk-lbl'>Conversation With:</div>
      <div class='pk-col-lg-1 pk-lbl'></div>
      <div class='pk-col-lg-1 pk-lbl'>Messages:</div>
      <div class='pk-col-lg-2 pk-lbl'>Last Contact:</div>
      <div class='pk-col-lg-2'></div>
      <div class='pk-col-lg-2'></div>
      <div class='pk-col-lg-2'></div>
    </div>
    @foreach ($users as $user)
    <div class='row conversant deletable-data-set pk-row-lg'>

      <div class='self-center {{$labels}} conv-with pk-col-md-1 pk-col-sm-2 pk-col-xs-4 pk-flex-resize-lg'>Thread With:</div>
      <div class='self-center pk-col-lg-2 conv-with  pk-col-md-2 pk-col-sm-4  pk-col-xs-6 pk-flex-resize-lg'>{{$user->getname()}}</div>

      <div class=' pk-col-lg-1  pk-col-md-1 pk-col-sm-2 pk-col-xs-4 pk-flex-resize-lg'><div class='{{$user->favorited_class()}} '></div></div>

      <div class='self-center {{$labels}}  pk-col-md-1  pk-col-sm-2 pk-col-xs-4 pk-flex-resize-lg'>Msgs:</div>
      <div class='self-center pk-col-lg-1  pk-col-md-1  pk-col-sm-1 pk-col-xs-2  pk-flex-resize-lg'>
        <div class='self-center'>{{$user->numMessagesWithMe()}}</div></div>

      <div class='self-center  {{$labels}} pk-col-md-1   pk-col-sm-2 pk-col-xs-4 pk-flex-resize-lg'>Last Contact:</div>
      <div class='self-center pk-col-lg-2  pk-col-md-2  pk-col-sm-3 pk-col-xs-4 pk-flex-resize-lg'>{{friendlyCarbonDate($user->lastContact())}}</div>

      <div class='pk-col-lg-2  pk-col-md-1  pk-col-sm-2 pk-col-xs-4 pk-flex-resize-lg'>
        @include('controls.viewconversation-button',['them'=>$user, 'class'=>'inline', 'label'=>'View<br>Thread'])
      </div>
      <div class='pk-col-lg-2 pk-col-md-1  pk-col-sm-2 pk-col-xs-4 pk-flex-resize-lg'>
        @include('controls.deleteconversation-button',['them'=>$user, 'class'=>'inline'])
      </div>
      <div class='pk-col-lg-2 pk-col-md-1  pk-col-sm-2 pk-col-xs-4 pk-flex-resize-lg'>
        @include('controls.viewprofile-button',['them'=>$user, 'class'=>'inline', 'label'=>'Visit<br>Profile'])
      </div>

    </div>
    @endforeach
  </div>
  @endif
</div>


<?php /*
  <div class='user messages conversations template'>
  <div class='template-list-head'>{!! numberItems($users, 'Conversation') !!}</div>
  @if (count($users))
  <div class='conversant set'>
  <div class='row conversant head pk-row-sm hidden-md-down'>
  <div class='col-md-2 col-sm-4'>Conversation With:</div>
  <div class='col-md-1 col-sm-2'></div>
  <div class='col-md-1 col-sm-2'>Messages:</div>
  <div class='col-md-2 col-sm-4'>Last Contact:</div>
  <div class='col-md-2 col-sm-4'></div>
  <div class='col-md-2 col-sm-4'></div>
  <div class='col-md-2 col-sm-4'></div>
  </div>
  @foreach ($users as $user)
  <div class='row conversant deletable-data-set pk-row-sm'>
  <div class='col-md-2 col-sm-4 pk-col conv-with'><span class='conversant name'>{{$user->getname()}}</div>
  <div class='col-md-1 col-sm-2 pk-col centering'><div class='{{$user->favorited_class()}} '></div></div>
  <div class='col-md-1 col-sm-2 pk-col num'><span class='conversant date'>{{$user->numMessagesWithMe()}}</div>
  <div class='col-md-2 col-sm-4 pk-col last-contact'><span class='conversant date'>{{friendlyCarbonDate($user->lastContact())}}</div>
  <div class='col-md-2 col-sm-4 pk-col conv-link'>
  @include('controls.viewconversation-button',['them'=>$user, 'class'=>'inline', 'label'=>'View<br>Conversation'])
  </div>
  <div class='col-md-2 col-sm-4 pk-col'>
  @include('controls.deleteconversation-button',['them'=>$user, 'class'=>'inline'])
  </div>
  <div class='col-md-2 col-sm-4 conv-link prof pk-col'>
  @include('controls.viewprofile-button',['them'=>$user, 'class'=>'inline', 'label'=>'Visit<br>Profile?'])
  </div>

  </div>
  @endforeach
  </div>
  @endif
  </div>


 */ ?>
@stop