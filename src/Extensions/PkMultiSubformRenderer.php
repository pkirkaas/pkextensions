<?php
/** Extends PkHtmlRenderer, for explicit repeating subform (Items in Cart) 
 * rendering, with template, dependent upon pklib.js definitions
 * 
 * Should eventually support nesting, initially single one-many level
 * ... But still can be several per form/object
 */
namespace PkExtensions;
use PkHtml;
use PkForm;
use PkExtensions\Models\PkModel;

if (!defined('RENDEROPEN')) define('RENDEROPEN', true);

/**
 * Usage: 
 * $subform = new PkMultiSubformRenderer;
 * //Make the base subform
 * $subform->hidden('tblname[__CNT_TPL__][id]','__FLD_TPL__id');
 * $subform->text('tblname[__CNT_TPL__][name]','__FLD_TPL__name');
 * $subform->text('tblname[__CNT_TPL__][ssn]','__FLD_TPL__ssn');
 * $subform->$subform_string_templates = ['id','name','ssn'];
 * $subform->subform_data = [
 *   ['id'=>7, 'name'=>'Joe', 'ssn'=>'555-33-4444',],
 *   ['id'=>9, 'name'=>'Jane', 'ssn'=>'666-22-8888',],
 *  ];
 * // (Generally, build $subform_data in a foreach )
 * echo $subform;
 */
class PkMultiSubformRenderer  extends PkHtmlRenderer {
  /**
   * @var null|array  - idxd arr of assoc array of
   *    field-string  => values, as above
   */
  public $subform_data = null;
  /**Should be array of string template fieldnames to be converted
   * using "__FLD_TPL__$val"
   * eg, ['id','name','ssn'];
   * @var null|array
   */
  public $subform_string_templates=null;
  public $cnt_tpl = '__CNT_TPL__';
  public $fld_tpl_prefix = '__FLD_TPL__';
  public $templatables_attributes = ['class'=>'templatable-data-sets'];
  public $deletable_dataset_attributes = 'deletable-data-set';
  public $create_button_label = 'Create';
  public $create_button_attributes = ['class'=>'js btn create-new-data-set'];
  public $create_button_tag = 'div';
  public $delete_button_label = 'Delete';
  public $delete_button_tag = 'div';
  public $delete_button_attributes = ['class'=>'js btn data-set-delete'];
  public $js_template_tag = 'fieldset';
  public $js_template_attributes=['disabled'=>true,'style'=>'display: none;', 'class'=>'template-container'];
  /** Generally, the table name */
  public $basename = null;
  /** Generally, the owner ID - like cart_id for 'items'
   * @var null|scalar  
   */
  public $owner_id = null;

  /*
  public function tagged($tag, $content = null, $attributes=null, $raw = false) {
    if (is_arrayis($content)) {
      $content = keyVal('content', $content);
      $attributes = keyVal('attributes', $content);
      $raw = keyVal('raw', $content,false);
    }
    $attributes = $this->cleanAttributes($attributes);
    if (!$attributes) $attributes = [];
  }
   * 
   */

  public function __toString() {
    $baseSubForm = parent::__toString();
    //pkdebug("The Values:", $this->subform_data);
    $out = new PkHtmlRenderer();
    $out[] = "\n";
    $cnt = 0;
    if (is_arrayish($this->subform_data)) $cnt = count($this->subform_data);
    $out->div(RENDEROPEN,$this->templatables_attributes);
      if (is_arrayish($this->subform_data)) foreach ($this->subform_data as $idx=> $row) {
        $out[] = $this->makeSubformPart($baseSubForm, $idx, $row);
      }
      $create_button_attributes = $this->create_button_attributes;
      $create_button_tag = $this->create_button_tag;
      $create_button_attributes['data-itemcount'] = $cnt;
      //$createbutton = $this->div($this->create_button_label, $this->create_button_attributes);
      $out ->$create_button_tag($this->create_button_label, $create_button_attributes);
      //$out[] = $this->div($this->create_button_label, $create_button_attributes);
    /*
      $out[] = $this->$create_button_tag($this->create_button_label, $this->create_button_attributes);
     */
      $out[]="\n";
      $out[] = $this->makeSubformPart($baseSubForm);
    $out->RENDERCLOSE();
    $out[] = "\n";
    return $out->__toString();
  }

  /**Make subform component - either initialized or invisible template, 
   * depending if $values is null or array
   * 
   */
  public function makeSubformPart($baseSubForm, $idx = null, $values=null) {
    //pkdebug("baseSubform: \n$baseSubForm\nidx:", $idx,'Values',$values);
    if ($idx !== null) $baseSubForm = str_replace($this->cnt_tpl,$idx,$baseSubForm);
    if (!is_arrayish($this->subform_string_templates)) return $baseSubForm;
    $tpl = new PkHtmlRenderer();
    $tpl[]= "\n";
    //return "<h3>In makeSubformPart</h3>";
    if ($values === null) { //Invisible template part
      $js_template_tag = $this->js_template_tag;
      $tpl->$js_template_tag(RENDEROPEN,$this->js_template_attributes);
    } 
    //$tpl->div(RENDEROPEN,$this->templatable_attributes);
    $tpl->div(RENDEROPEN,$this->deletable_dataset_attributes);
      foreach ($this->subform_string_templates as $inp_fld) {
        $valkey = $this->fld_tpl_prefix.$inp_fld;
        $val = keyVal($inp_fld,$values);
        if (is_string($val)) $val = "'".$val."'";
        //pkdebug("INP_FLD: $inp_fld, valkey: $valkey; val: $val");
        $baseSubForm = str_replace($valkey,$val,$baseSubForm);
      }
      $tpl[] = $baseSubForm."\n";
      $delete_button_tag = $this->delete_button_tag;
      $tpl->$delete_button_tag($this->delete_button_label, $this->delete_button_attributes);
    $tpl->RENDERCLOSE();
    if ($values === null) { //Invisible template part
      $tpl->RENDERCLOSE();
    } 
    $tpl[]="\n";
    return $tpl;
  }
}
