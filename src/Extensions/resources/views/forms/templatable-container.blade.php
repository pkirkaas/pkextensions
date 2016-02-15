<?php
use \PkForm;
use \Undefined;
$undefined = new Undefined();
/**
This is an abstract Blade Form Template Container, that takes a template
  and possibly an array of model instances, and displays the existing model
  instances in the passed template, then creates a "ghost" template for making
  new instances

Expected Parameters:
  $basename: The basename to use for the items
  $template_path: String, the empty template
  $attributename: The name to call the model or collection
  $modelname - convenience, the name of the model to use in the template, but defaults to 'model'
  $models: a possibly empty array or Collection of Model instances to be passed
    to the template.
  */
  if (empty($modelname)) $modelname = 'model';
  $newbase = buildFormInputNameFromSegments($basename, $attributename);
  ?>
   <div class="templatable-data-sets section">

  <?php
  $idx = -1;
  ?>
  <input type='hidden' name='{{$newbase}}' value='' />
  @if (is_iterable($models))
  @foreach($models as $idx => $model)
  <?php /**
    @include($template_path, [$modelname=>$model, 'idx' =>$idx, 'basename'=>$newbase])
   */
  ?>
  <div class='deletable-data-set'> 
    @include($template_path, [$modelname=>$model, 'basename'=>buildFormInputNameFromSegments($newbase,$idx)])
		<span class='js btn data-set-delete pkmvc-button'>Delete</span>
  </div>
  @endforeach
  @endif
  <div class='js btn create-new-data-set pkmvc-button' data-itemcount='{{++$idx}}'>New Item</div>
  <!-- Now include the same form again, as a template, within a hidden and disabled fieldset -->
  <fieldset class='template-container hidden' disabled >
    <?php /*
      @include($template_path, [$modelname=>null, 'idx' =>"__CNT_TPL__", 'basename'=>$newbase])
     */ ?>
  <div class='deletable-data-set'> 
      @include($template_path, [$modelname=>$undefined, 'basename'=>buildFormInputNameFromSegments($newbase, "__CNT_TPL__")])
		<span class='js btn data-set-delete pkmvc-button'>Delete</span>
  </div>
  </fieldset>
</div>