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
       "<div class='col-$colWidth-md $extra_col_class'>\n"); 
   $col_closer = keyVal('col_closer', $params, "</div>\n");
   $renderer = keyVal('renderer' , $params);

   $html = $row_opener;
   $idx = 0;
   foreach ($items as $item) {
     $idx++;
     $html .= $col_opener;
     if (is_callable($renderer)) {
       $html .= $renderer($item);
     } else if (is_stringish($item)) {
       $html .= $item;
     } else {
       return false; #Don't know how to show it
     }
     $html .= $col_closer;
     if (($idx < $sz) && (!($idx % $items_per_row))) {
       $html .= ($row_closer."\n".$row_opener);
     }
   }
   $html .= $row_closer;
   return $html;
}
