<?php
/** An email link button
 * @param $url - the URL to go to
 * @param $label - the text to display
 */
?>
<a style='
  white-space: nowrap;
  text-align: center;
  text-decoration: none;
  background-image: linear-gradient(to top, #759ae9, #376fe0);
  border-top: 1px solid #1f58cc;
  border-right: 1px solid #1b4db3;
  border-bottom: 1px solid #174299;
  border-left: 1px solid #1b4db3;
  border-radius: 4px;
  box-shadow: inset 0 0 2px 0 rgba(57, 140, 255, 0.8);
  color: #fff;
  font: bold 12px/1 "helvetica neue", helvetica, arial, sans-serif;
  padding: 7px ;
  margin: 7px;
  text-shadow: 0 -1px 1px #1a5ad9;
  ' href='{{$url}}'>{{$label}}</a>