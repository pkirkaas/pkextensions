<?php
namespace App\Extensions;
/** Class to work with Blade::declare() to automate the inclusion of mulit-subforms in forms
 * 
 */
  class BuildMultiSubform {
    public $Collection;
    public $subform; #subform template to include
    /** Params to be passed to the included subform */
    public $params = []; #Params to be passed to the subform template
    public $templatable_data_sets_class = '';
    public $templatable_data_set_class = '';
    public $instances = []; #existing instances to loop through and edit
    public $create_button_label;
    public $create_button_class;
    public $collection_name; #The name of the collection, like 'items', as defied in the owner;
    public $dataset = []; #Usually an array or Eloquent Collection of instance
    //public $template;
    public $item_name;


   
    
    /** Build a template HTML to create and delete one-to-many relations in a form
     * 
     * @param string $subform - standard view/template specifier, like
     *  <tt>'project.forms.edititems-subform'</tt>
     * @param array $args - Associatice array of optional args
     * 'templatable_data_sets_class' => optional additional classes to add to the templatable-data-sets div.
     * 'params' => optional array of additional parameters to be passed to the subform
     * 'collection_name' => the relationship name used by it's owning class, like 'items'
     * 'item_name' => The name the subform uses for it's variable/model
     * 'dataset' => the collection of existing instances, like, $project->items;
     * 'create_button_label' => How to label the "New Item" button
     * 'create_button_class' => additional classes to add to the create/new button
     */
    
    public function __construct($subform,$args = []) {
      $this->templatable_data_sets_class = keyValOrDefault('templatable_data_sets_class',$args,'');
      $this->dataset = keyValOrDefault('dataset', $args, []);
      $this->item_name = keyValOrDefault('item_name', $args, 'item');
      $this->collection_name = keyValOrDefault('collection_name', $args, 'items');
      $this->create_button_label = keyValOrDefault('create_button_label', $args, 'New Item');
      $this->params[$this->collection_name] = keyValOrDefault($this->collection_name,$args);
      if (!is_iterable($this->dataset)) $this->dataset = [];
      $this->subform = $subform;
      $this->params = keyValOrDefault('params',$args,[]);




    }

    public function getTemplateSets() {
      $out = "\n<div class='templatable-data-sets $this->templatable_data_sets_class'>\n";
      $out .="<input type='hidden' name='$this->collection_name' value='' />\n";
      $out .= '<?php $idx = -1; ?>'."\n";
      if (count($this->dataset)) foreach ($this->dataset as $idx => $data) {
        $params = $this->params;
        $params[$this->item_name] = $data;
        $params['idx'] = $idx;
        $out .= view($this->subform,$params)."\n";
      }
      $out.= "
        <div class='js btn create-new-data-set pkmvc-button'
          data-itemcount='{{++".'$idx}}'."'>$this->create_button_label
      </div>\n";

      $out .="<fieldset class='template-container hidden' disabled >\n"; 
      $params['idx']="__CNT_TPL__";
      $params[$this->item_name] = new Universal();
      $out .= view($this->subform, $params);
      $out .= "\n</fieldset>\n";
      $out .= "</div>\n";
      return $out;

    }


}
