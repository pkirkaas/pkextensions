<?php
use App\Models\User;
if (empty($label)) $label = "View Profile?";
if (empty($class)) $class = '';

/* Button-like link template to the other user. If the logged in user isn't authorized
 * to view their profile, returns empty.
 * @param User $them - REQUIRED: The user to send the message to
 * @param string $label - Optional - label for the button
 * @param string $class - Optional - Additional CSS classes to add to the button.
 */
?>
@if (!empty($them) && $them instanceOf User && $them->id && $them->canBeViewed() )
  {!!$them->linkToViewProfile($label,"pkmvc-button $class")!!}
@endif