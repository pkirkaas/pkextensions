<?php
$undefined = new Undefined();
$idx = -1;
if (empty($models)) $models = [];
$default_params = [
  'wrap_class' => 'row inp-row',
  //'delete_button' => "<div class='col-sm-1'><span class='js btn data-set-delete pkmvc-button'>Delete</span></div>",
  'delete_class' => "pkmvc-button pk-compact",
  'delete_content' => "Delete",
  'new_item_class' => "pkmvc-button",
  'new_item_content' => 'New',
];

$delete_reqclass = ' js btn data-set-delete ';
$new_item_reqclass = ' js btn create-new-data-set ';

if (!empty($params) && is_array($params)) {
  $params = array_merge($default_params, $params);
} else {
  $params = $default_params;
}
$delete_button = "<div class='$delete_reqclass {$params['delete_class']}'>{$params['delete_content']}</div>\n";
$new_item_button = "<div class='$new_item_reqclass {$params['new_item_class']}'>{$params['new_item_content']}</div>\n";
$params['delete_button'] = keyVal('delete_button',$params,$delete_button);
$params['new_item_button'] = keyVal('new_item_button',$params,$new_item_button);
/**
 This is simpler (only one level) one-to-many Blade Form Template Container.
 If the top level model is "Cart" which has many "Items":

1)  In the Model definition for Cart, define the standard Eloquent hasMany for
 $cart->items(), but also set the Cart $load_relations:

  public static $load_relations = [
      'items' => 'App\Models\Item',
      ];

2) Create a template for a single item that takes parameters:
      $idx,
      $model ($model instanceOf Item):
  'item.blade.php':
     <label>Desc:</label> {!! PkForm::text("items[$idx][desc]",$model->desc)!!}
     <labelPrice:</label> {!! PkForm::text("items[$idx][price]",$model->price)!!}

3) In your "one" side form, include this form with the parameters:
  ['models'=>$items, 'relationship'=>'items', 'template_path'=>'item']
 
  */
  ?>
   <div class="templatable-data-sets section">
  <!-- This hidden allows deletion of the last item -->
  <input type='hidden' name='{{$relationship}}' value='' />
  <div class='{{$params['wrap_class']}}'> 
    @include($template_path, ['header'=>true])
  </div>

  @if (is_iterable($models))
    @foreach($models as $idx => $model)
      <div class='{{$params['wrap_class']}} deletable-data-set'> 
        @include($template_path, ['model'=>$model, 'idx' => $idx,'params'=>$params])
      </div>
    @endforeach
  @endif

<?php /*
  <div class='js btn {{$params['new_item_class']}}'  data-itemcount='{{++$idx}}'>{!!$params['new_item_content']!!}</div>
  */ ?>
  {!!$params['new_item_button']!!}

  <!-- Now include the same form again, as a template, within a hidden and disabled fieldset -->
  <fieldset class='template-container hidden' disabled style='display:none;'>
  <div class='{{$params['wrap_class']}} deletable-data-set'> 
      @include($template_path, ['model'=>$undefined, 'idx'=>"__CNT_TPL__",'params'=>$params])
  </div>
  </fieldset>
</div>
