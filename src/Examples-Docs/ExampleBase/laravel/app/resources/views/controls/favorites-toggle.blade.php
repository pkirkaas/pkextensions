<?php
/** Toggles if the "them" user is favorited. The only required param:
 * @param User $them - the other user the logged in user is favoriting or not
 * @param string $class - optional additional class to add to the toggle
 */
if (empty($class)) $class = '';
use App\Models\User;
?>
@if (!empty($them) && $them instanceOf User && $them->id)
<div class='favorite-toggle {{$class}} {{$them->favorited_class()}}' title='Click to Set or Unset this user as a Favorite!' data-them_id='{{$them->id}}'></div>
@endif

