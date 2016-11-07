<?php
use App\Models\User;
if (empty($label)) $label = "Send Message?";
if (empty($class)) $class = '';

/* Template to produce the JS button that pops up a dialog to send a message.
 * Must be used in conjunction with the lsbb.js lib, and the jstemplates.sendmessage-dialog
 * template. But this is the only component that REQUIRES the "$them" user object.
 * @param User $them - REQUIRED: The user to send the message to
 * @param string $label - Optional - label for the button
 * @param string $class - Optional - Additional CSS classes to add to the button.
 * 
 * 
 * 
      <div class='lbl pkmvc-button inline js-contact' data-user_id_to='{{$borrower->user->id}}'>Send Message?</div>
 */
?>
@if (!empty($them) && $them instanceOf User && $them->id && $them->canBeMessaged())
<div class='lbl pkmvc-button inline js-contact {{$class}}' data-user_name='{!!html_encode($them->getname())!!}' data-user_id_to='{{$them->id}}'>{{$label}}</div>
@endif