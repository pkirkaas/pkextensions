<?php
/** Marks the message as deleted by the current user - the other user can still
 * see it
 * @param Message $message - the message to delete for the logged in user
 * @param string $class - optional additional class to add to the button
 * @param string $label - optional label - default: "Delete Message"
 */
if (empty($class)) $class = '';
if (empty($label)) $label = 'Delete?';
use App\Models\User;
?>
@if (!empty($message) && $message->exists)
<div class='pkmvc-button js-delete-el del-msg {{$class}}' title='Delete Message?' data-message_id='{{$message->id}}'>{{$label}}</div>
@endif

