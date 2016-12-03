<?php
use Collective\Html\HtmlBuilder;
use Collective\Html\FormBuilder;
use PkExtensions\PartialSet;
use PkExtensions\PkHtmlRenderer;

/**
 * Makes an attributes array, combining $arg1 & $arg2
 * @param array|string $arg1: If string, makes ['class'=>$arg1]
 * @param array|string|null $arg2 If string, makes ['class'=>$arg2]
 * Assumes all the values are strings - so combines them, space separated
 * @return array $attributes
 */
function merge_attributes($arg1,$arg2=[]) {
  if (is_simple($arg1)) $arg1 = ['class'=>$arg1];
  if (!$arg2) return $arg1;
  if (is_scalar($arg2)) $arg2 = ['class'=>$arg2];
  $keys = array_unique(array_merge(array_keys($arg1),array_keys($arg2)));
  foreach ($keys as $key) {
    $arg1[$key] = keyVal($key,$arg1).' '.keyVal($key,$arg2);
  }
  return $arg1;
}

/** Seems nutty, but take two associative arrays of attribute arrays, and
 * merge them with the above method, only for the given keys
 * @param type $keys
 * @param type $arr1
 * @param type $arr2
 */
function merge_att_arrs($keys, $arr1=[], $arr2 = []) {
  if (!$keys) return [];
  if (ne_string($keys)) {
    return merge_attributes(keyVal($keys,$arr1), keyVal($keys,$arr2));
  }
  $out = [];
  foreach ($keys as $key) {
    $out[$key] = merge_attributes(keyVal($key,$arr1), keyVal($key,$arr2));
  }
  return $out;
}

/** Try to redo this rationally - but works as test in kirkaas.com gallery*/
/**
 * 
 * @param type $items
 * @param type $opts
 */
function grid_layout($items, $opts=[]) {
  $defaults = [
    'num_columns' => 12,
    'items_per_row' => 3,
    'row_class' => '',
    'col_class' => '',#Additional column class
  ];
  $params = getParamsOrDefault($opts, $defaults);
  extract($params);
  if ($items instanceOf PartialSet) {
    $numitems = $items->sizeOf();
  } else {
    $numitems = count($items);
  }
  $item_width = $num_columns/$items_per_row;
  $numcols = count($items);
  $colclass = "col-xs-$item_width $col_class";
  $grout = new PkHtmlRenderer();
  $grout->rawdiv(RENDEROPEN,"row $row_class");
    foreach ($items as $i=>$item) {
      $grout->rawdiv($item,$colclass);
      if (($i+1 <$numitems) && !(($i+1) % $items_per_row) ) {
        $grout->RENDERCLOSE();
        $grout->rawdiv(RENDEROPEN,"row $row_class");
      }
    }
  $grout->RENDERCLOSE();
  return $grout;
}


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
/* Moved & improved somewhere ...
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
    $html .= "\n$item\n$col_closer";
     if (($idx < $sz) && (!($idx % $items_per_row))) {
       $html .= ($row_closer."\n".$row_opener);
     }
   }
   $html .= $row_closer;
   return $html;
}
 * 
 */

/** Creates BS4/Tether tool-tip attributes. Escapes the string, and returns
 * 'data-toggle="tooltip" title="$escapedString" '
 * @param type $string
 */
function bs_tooltip($string) {
  if (!$string || !is_string($string)) return '';
  $string = html_encode($string);
  return " data-toggle='tooltip' title='$string' ";
}

/** Creates an HTML element or array of elements holding encoded PkModel attribute
 * data for use by JS on the page.
 * @param PkModel|arrayish PkModels - $pkmodels - the model(s) to get attributes
 * for. Can be mixed types - but then have to use the default model typename
 * @param string|null $typename - what to call the type in the element.
 *   Default is basename of the PkModel class.
 * @return HTML String|Array HTML Strings - elements in the form:
 *  <div class='encoded-data-holder' data-encoded-data-objtype="$typename"
 *   data-encoded-data-objid="$id" data-encoded-data-data="$encoded_data"></div>"
 */
function jsObjDataGen($pkmodels, $typename = null) {
  if ($pkmodels instanceOf \PkExtensions\Models\PkModel) {
    $pkmodels = [$pkmodels];
  }
  $tstInstance = $pkmodels[0];
  $typeof = typeOf($tstInstance);
  if (!$tstInstance instanceOf \PkExtensions\Models\PkModel) {
    throw new \Exception ("Wrong type: [$typeof]");
  }
  //if (!$typename) $typename = strtolower(getBaseName($typeof));
  $ps = new PartialSet();
  foreach ($pkmodels as $instance) {
    if (!$typename) $thistypename = strtolower(getBaseName(get_class($instance)));
    else $thistypename = $typename;
    $encdata = $instance->getEncodedCustomAttributes();
    $id = $instance->id;
    $ps[] = "<div class='encoded-data-holder' data-encoded-data-objtype='$thistypename'
       data-encoded-data-objid='$id' data-encoded-data-data='$encdata'></div>\n";
  }
  return $ps;

} 

function checkBox($value) {
  if ($value) return '&#9745;';
  return  '&#9744;';
}

function printButton($extraclasses = 'inline', $txt='Print') {
  return "<div class='$extraclasses pkmvc-button print-button'>$txt</div>";
}