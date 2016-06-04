<?php
use Collective\Html\HtmlBuilder;
use Collective\Html\FormBuilder;


/** Returns HTML of the items in a grid
 * 
 * @param arrayish $items - the items to display
 * @param assoc array $params - optional parameters:
 *   'row_opener': default: '<div class="row">';
 *   'row_closer': default: '</div>'
 *   'col_opener': default: '<div class="col-$colwidth-md">'; 
 *   'col_closer': default: '</div>'
 *   'extra_row_class': default: ''
 *   'extra_col_class': default: ''
 *   'num_columns' : default: 12
 *   'items_per_row': default: 3
 *   'item_sprintfmt': String or NULL: default: NULL - If present, assume
 *       use with sprintf & the item elements to format an individual items
 *   'item_keys': default: NULL - If present, assume individual items are
 *       also arrayish, use key or keys to de-reference component. Can use the 
 *       same key twice, if you want URL twice, say
 *   'renderer' : 'callable' or null - if callable, used to render/display item.
 *        if null, $item has to be stringable.
 * @return false || $HTML string of layout grid
 * 
 */
function grid_layout($items, $params = []) {
  if (!is_arrayish($items) || !($sz = count($items))) return false;
   $num_columns = keyVal('num_columns', $params, 12);
   $items_per_row = keyVal('items_per_row', $params, 3);
   $colWidth = $num_columns/$items_per_row;
   $extra_row_class = keyVal('extra_row_class',$params,'');
   $extra_col_class = keyVal('extra_col_class',$params,'');
   $row_opener = keyVal('row_opener' ,$params,
       "<div class='row $extra_row_class'>\n");
   $row_closer = keyVal('row_closer', $params, "</div>\n");
   $col_opener = keyVal('col_opener', $params, 
       "<div class='col-md-$colWidth $extra_col_class'>\n"); 
   $col_closer = keyVal('col_closer', $params, "</div>\n");
   $item_keys = keyVal('item_keys', $params);
   $item_sprintfmt  = keyVal('item_sprintfmt', $params);
   $renderer = keyVal('renderer' , $params);
   if (is_scalar($item_keys)) $item_keys = [ $item_keys];
   $html = $row_opener;
   $idx = 0;
   foreach ($items as $item) {
     $idx++;
     $html .= $col_opener;
     if (is_callable($renderer)) $item .= $renderer($item);
     if ($item_sprintfmt && is_string($item_sprintfmt) && strlen($item_sprintfmt)) {
       if (is_arrayish($item_keys) && count($item_keys) && is_arrayish($item) && count($item)) {
         $argarr = [];
         $argarr[] = $item_sprintfmt;
         foreach ($item_keys as $item_key) {
           $argarr[] = $item[$item_key];
         }
         $item = call_user_func_array('sprintf', $argarr);
       } else if (is_stringish($item) || is_scalar($item)) {
         $item = sprintf($item_sprintfmt, $item);
       } else {
         $item_str = print_r($items, true);
         $param_str = print_r($params, true);
         throw new \Exception("Invalid args to grid_layout:
           Items:\n$item_str\nParams:\n$param_str");
       }
     }
    $html .= "\n$item\n";
       //$html .= $renderer($item);
     //} else if (is_arrayish($item) && is_arrayish($item_keys) && count($item_keys)) {










     //} else if ($item_sprintfmt && is_string($item_sprintfmt) && strlen($item_sprintfmt)) {
     //} else if (is_arrayish($item) && is_arrayish($item_keys) && count($item_keys)) {
       #Put specified / keyed elements of item into the 
      // if ($item_sprintfmt && is_string($item_sprintfmt) && strlen($item_sprintfmt)) {
       //  $argarr = [];
        // $argarr[] = $item_sprintfmt;
         //foreach ($item_keys as $item_key) {
          // $argarr[] = $item[$item_key];
        // }
        // $html = call_user_func_array('sprintf', $argarr);
       //} else {
        // $item_str = print_r($items, true);
        // $param_str = print_r($params, true);
       //  throw new \Exception("Invalid args to grid_layout:
        //   Items:\n$item_str\nParams:\n$param_str");
      // }

     //} else if (is_stringish($item)) {
      // $html .= $item;
     //} else {
     //  return false; #Don't know how to show it
    // }
     $html .= $col_closer;
     if (($idx < $sz) && (!($idx % $items_per_row))) {
       $html .= ($row_closer."\n".$row_opener);
     }
   }
   $html .= $row_closer;
   return $html;
}

/**
 * Takes arrayish 'items' and returns an array of formatted strings with 
 * 
 */

/* overkill...
function wrap_items($items,$sprintfmt=null, $keys=null) {
}
 * 
 */