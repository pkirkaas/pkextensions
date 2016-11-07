<?php
if (empty($label)) $label = "Help";
if (empty($class)) $class = '';

/* Template to produce the JS button that pops up a help dialog from the page template
 * Must be used in conjunction with the pklib.js lib, and the templates defined there
 * @param string $label - Optional - label for the button
 * @param string $class - Optional - Additional CSS classes to add to the button.
 * 
 */
?>
<div class='lbl pkmvc-button showHelp {{$class}}'>{{$label}}</div>