<?php
/** Makes a simple generic bs4 grid with a header row
 * and content row.
 * @param arrayish $collection
 * @param arrayish $fieldmap: Associative Array
 *     fieldnames->HeaderTitles
 * @param arrayish $params - optional params
 *   $titleClass: The Title CSS Class
 *   $valueClass: The Value CSS Class
 */
if (!isset($params)) $params = [];
$titleClass = keyVal('titleClass',$params,'pk-lbl');
$valueClass = keyVal('valueClass',$params,'pk-val');
$cols = count($fieldmap);
$colsz = (int)(12/$cols);
$titles = [];
$fields = [];
foreach ($fieldmap as $fieldName => $title) {
  $fields[]=$fieldName;
  $titles[]=$title;
}
?>
<div class='row grid-head-row'>
  @foreach($titles as $title)
  <div class='col-md-{{$colsz}}  pkl1 grid-head-col {{$titleClass}}'>{{$title}}</div>
  @endforeach
</div>
@foreach ($collection as $item)
<div class='row grid-data-row'>
  @foreach($fields as $field)
    <div class='col-md-{{$colsz}} grid-data-col {{$valueClass}}'>
      {{$item->$field}}
    </div>
  @endforeach
</div>
@endforeach