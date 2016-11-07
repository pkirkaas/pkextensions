<?php 
/** View Notes on Users
 * @param Notes Collection $notes - 
 */
?>
@extends('app')
@section('content')
<div class="user notes template">
<h4>You have {{count($notedusers)}} notes on Borrowers. Go to a Borrower's Profile Page to edit your note or "Favorite" them</h4>
@foreach ($notedusers as $noteduser) 
<div class="row borrower half">
  <div class='col-sm-6'>
    @include('borrower.parts.borrower-half-width',['borrower'=>$noteduser->type])
  </div>
  <div class='col-sm-6 note'>
    <div class='note content'>
      
      {{$noteduser->note()}}
    </div>
  </div>
</div>
@endforeach
</div>
@stop