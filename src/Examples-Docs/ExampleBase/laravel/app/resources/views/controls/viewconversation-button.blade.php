<?php
use App\Models\User;
if (empty($label)) $label = "View Conversation?";
if (empty($class)) $class = '';

/* Button-like link template to view the conversation with the other user
 * @param User $them - REQUIRED: The user to send the message to
 * @param string $label - Optional - label for the button
 * @param string $class - Optional - Additional CSS classes to add to the button.
 */
?>
@if (!empty($them) && $them instanceOf User && $them->id)
  <a class='pkmvc-button {{$class}}' href='{{route('user_conversation',[$them])}}'>{!!$label!!}</a>
@endif