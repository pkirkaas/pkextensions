<?php
/** Marks a conversation (all messages) between the current user and other user
 * as deleted - from the perspective of the current user. The other user can still
 * see it
 * @param User $them - the other user to delete messages for the logged in user
 * @param string $class - optional additional class to add to the button
 * @param string $label - optional label - default: "Delete Conversation"
 */
if (empty($class)) $class = '';
if (empty($label)) $label = 'Delete<br>Thread';
use App\Models\User;
?>
@if (!empty($them) && $them->exists)
<div class='pkmvc-button js-delete-el del-conversation {{$class}}' title='Delete Conversation?' data-them_id='{{$them->id}}'>{!!$label!!}</div>
@endif

