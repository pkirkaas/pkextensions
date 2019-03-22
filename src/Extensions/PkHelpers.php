<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */

use PkExtensions\Universal;
use PkExtensions\PartialSet;
#############   SEEMS NOT TO BE INCLUDED !!!!!

/** A library of Laravel - specific "helper" functions */

/** Builds a templatable many to one subform, whith exit/create/delete of items.
 * @param string $subform - standard view/template specifier, like
 *  <tt>'project.forms.edititems-subform'</tt>
 * @param array $args - Associatice array of optional args
 * 'templatable_data_sets_class' => optional additional classes to add to the templatable-data-sets div.
 * 'params' => optional array of additional parameters to be passed to the subform
 * 'collection_name' => the relationship name used by it's owning class, like 'items'
 * 'item_name' => The name the subform uses for it's variable/model
 * 'collection' => the collection of existing instances, like, $project->items;
 * 'create_button_label' => How to label the "New Item" button
 * 'create_button_class' => additional classes to add to the create/new button
 */
function multiSubform($subform, $args = []) {
  $templatable_data_sets_class = keyValOrDefault('templatable_data_sets_class', $args);
  $collection = keyValOrDefault('collection', $args, []);
  if (!is_iterable($collection)) $collection = [];
  $item_name = keyValOrDefault('item_name', $args, 'item');
  $collection_name = keyValOrDefault('collection_name', $args, 'items');
  $create_button_label = keyValOrDefault('create_button_label', $args, 'New Item');
  $params[$collection_name] = keyValOrDefault($collection_name, $args);
  $params = keyValOrDefault('params', $args, []);

  $out = "\n<div class='templatable-data-sets $templatable_data_sets_class'>\n";
  $out .="<input type='hidden' name='$collection_name' value='' />\n";
  $idx = -1;
  if (count($collection)) {
    foreach ($collection as $idx => $data) {
      $params = $params;
      $params[$item_name] = $data;
      $params['idx'] = $idx;
      $out .= view($subform, $params) . "\n";
    }
  }
  $out.= "
    <div class='js btn create-new-data-set pkmvc-button'
      data-itemcount='" . ++$idx . "'>$create_button_label
  </div>\n";

  $out .="<fieldset class='template-container hidden' disabled >\n";
  $params['idx'] = "__CNT_TPL__";
  $params[$item_name] = new Universal();
  $out .= view($subform, $params);
  $out .= "\n</fieldset>\n";
  $out .= "</div>\n";
  return $out;
}
