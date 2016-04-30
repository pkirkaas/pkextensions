<?php
namespace PkExtensions;
//use Illuminate\Html\FormBuilder;
use Collective\Html\FormBuilder;
//use Illuminate\Html\HtmlBuilder;
use Collective\Html\HtmlBuilder;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use PkExtensions\Models\PkModel;

class PkFormBuilder extends FormBuilder {


	/**
	 * Create a new form builder instance.
	 *
	 * @param  \Illuminate\Routing\UrlGenerator  $url
	 * @param  \Illuminate\Html\HtmlBuilder  $html
	 * @param  string  $csrfToken
	 * @return void
	 */
	public function __construct(HtmlBuilder $html, UrlGenerator $url, Factory $view, $csrfToken) {
    parent::__construct($html, $url, $view, $csrfToken);
	}

  /*
  public function open(array $options = []) {
    //$this->model = keyval('model',$options);
    $open = parent::open($options);
    return $open;
  }
   * 
   */
  /*
   * 
   */
  public function form($content, array $options =[]  ) {
    return $this->open($options) .$content.$this->close();
  }

  public function setNamePrefix($namePrefix = '') {
    $this->html->name_prefix = $namePrefix;
  }


  public function getNamePrefix() {
    return $this->html->name_prefix = $namePrefix;
  }
  public function addToNamePrefix($first, $second=null) {
    $pre = $this->getNamePrefix();
    if (!$pre) $this->setNamePrefix($first);
    else $this->setNamePrefix($pre.'['.$first.']');
    if ($second) $this->setNamePrefix($this->getNamePrefix().'['.$second.']');
  }


  /** Takes a collection of model instances (Eloquent Collection Object) as returned
   * by an Eloquent Search, and a "Display Field Name" to use as the field to display 
   * (assumes 'id' as the id field) - builds an appropriate array and then returns
   * the standard Form::select
   * 
   * @param string $name - the HTML Form Field name
   * @param EloquentCollectionObject $models - a collection of model/ORM instances
   * @param string $displayField - the name of the Eloquent Model field to use for display
   *   in the select box
   * @param string $selected - see doc for standard FormBuilder::select
   * @param array $options - see doc for standard FormBuilder::select
   * @return string - see doc for standard FormBuilder::select
   */
	public function selectFromModels($name, $models = [], $displayField = '',
          $selected = null, $options = []) {
    if (!count($models)) return $this->select($name, [], $selected, $options);
    $list = [];
    foreach ($models as $model) {
      $list[$model->id] = $model->$displayField;
    }
    return $this->select($name,$list,$selected,$options);
  }


  
  /** A standard unchecked checkbox returns no value. This Boolean control
   * always returns a value, checked or not - by default, unchecked is '0', but an option.
   * <p>
   * BE CAREFUL WHEN SETTING THE VALUE! (Moved value to last param)
   * @param type $name
   * @param type $value
   * @param type $checked
   * @param type $options
   * @param type $unset
   * @return type
   */
	public function boolean($name,  $checked = null, $options = [], $unset = '0', $value = 1) {
    return "\n<input type='hidden' name='$name' value='$unset' />\n".
            $this->checkbox($name, $value, $checked, $options);
  }

  /** Creates a radio-set - like 'select' with radio buttons - returns only one value
   * 
   * @param string $name - the input name - the POST array key
   * @param array $list - array of $values($keys) => $labels
   * @param scalar $value - the current
   * @param array $options
   * @param scalar|null $unset - the value if none of the options are selected
   * @return string - HTML for the radio-set
   */
  public function radioset ($name, $list = [], $value = null, $options = [], $unset = null) {
    $out = "\n<div class='radioset'>\n";
    $out .= "\n<input type='hidden' name='$name' value='$unset' />\n";
    foreach ($list as $key => $label) {
      $checked = $key == $value ? true : false;
      $options['id'] = $name.'_'.$key;
      $out .= "\n<div class='radio'>";
      $out .= "<label>";
      $out .= $this->radio($name,$key,$checked,$options);
      $out .= "$label</label>\n";
      $out .= "\n</div>";
    }
    $out .= "\n</div>\n";
    return $out;
  }

  public function yesno ($name, $value=null, $options=[], $unset=null) {
    return $this->radioset($name, [1=>'Yes',0=>'No'], $value, $options, $unset);
  }

  /**
   * Creates an array checkboxes with values - can return multiple values in array
   * When POSTing, will be a sparse array, so take array_values, and save array as JSON?
   * @param string $name - the base name of input set - but will be POSTed
   *   as an array "$name[0], $name[1], etc
   * @param array $list - array of $values($keys) => $labels
   * @param array|scalar $values - the array of current values. If scalar, converted to array.
   * @param array $options
   * @param scalar|null $unset - the value if none of the options are selected
   */
  public function multiselect($name, $list = [], $values=null, $options=[], $unset = null) {
    $values = $this->getValueAttribute($name, $values);
    $wrapperclass = keyval('wrapperclass',$options, ' form-control ');
    $allclass = keyval('allclass',$options);
    unset($options['wrapperclass']);
    unset($options['allclass']);
    $out = "\n<div class='multiselect $wrapperclass $allclass '>\n";
    $out .= "\n<input type='hidden' name='$name' value='$unset' />\n";
    foreach ($list as $key => $label) {
      $checked = in_array_equivalent($key,  $values) ? true : false;
      $options['id'] = $name.'_'.$key;
      $out .= "\n<div class='pk-checkbox $allclass'>";
      $out .= "<label class='multiselect-label $allclass '>";
      //$out .= $this->checkbox($name."[$i]",$key,$checked,$options);
      $options['class'] = keyval('class',$options)." $allclass ";
      $out .= $this->checkbox($name."[]",$key,$checked,$options);
      $out .= "$label</label>\n";
      $out .= "\n</div>";
    }
    $out .= "\n</div>\n";
    return $out;




  }

    /**
     * Create a Submit Button as a real button, with resonalbe defaults.
     *
     * @param  string $label - to show on the button - default 'Submit'
     * @param  array  $options default type->submit, name->submit, value->submit
     * @return \Illuminate\Support\HtmlString
     */
    public function submitButton($label = 'Submit', $options = []) {
        if (! array_key_exists('type', $options)) $options['type'] = 'submit';
        if (! array_key_exists('name', $options)) $options['name'] = 'submit';
        if (! array_key_exists('value', $options)) $options['value'] = 'submit';

        return $this->toHtmlString('<button' . $this->html->attributes($options) . '>' . $label . '</button>');
    }

  //public function checkbox($name, $value = 1, $checked = null, $options = [])

  /**
   * This returns an HTML string representing either an input field or a display
   * value, depending on permissions. 
   * @param string $name - the name of the TABLE field
   * @param string $type - the type of input. If just scalar input, default to 
   *   'text', if choice/array, default to 'select'
   * @param PkExtensions\PkModel $object - the object to check for allowable edits
   * @return string - HTML representing the existing display value OR the relevant HTML
   * input control
   */
  public function customInputField($name, PkModel $object, $value=null, $type=null, $options=[]) {
    $valids = $object->canEditThisField($name);
    if ($valids === false) {
      return "\n<div class='display_val val'>".$object->displayValue($name,$value)."</div>\n";
    } 
    if ($valids === true) {
      if (!$type) $type = 'text';
      if ($type === 'boolean') {
        return $this->boolean( $name, 1, $object->$name,  $options);
      }
      if ($type === 'textarea') {
        return $this->textarea( $name, $object->$name,  $options);
      }
      //return "\n<input type='text' class='display_val val' value='".$object->displayValue($name)."'/>\n";
      return $this->input($type, $name, $object->$name, $options);
    } 
    if (is_array($valids)) { #Make a select option control, limited to the options
      if (!$type) $type = 'select';
      return $this->$type($name, $valids, $object->$name,  $options);
    }
    throw new \Exception("Invalid 'valids' result from PkModel instance"); 
  }

  /** Some functions that just combine other functions to take some of the tedium out.. */
  public function textareaset($name, $value = null, $labeltext = '', $inatts = [], $labatts = [], $wrapatts =[]) {
    $labatts = $this->mergeClass($labatts);
    $inatts = $this->mergeClass($inatts);
    $textarea = $this->textarea($name, $value, $inatts);
    $label = '';
    if ($labeltext !== null) $label = $this->label($name, $labeltext, $labatts);
    if ($wrapatts !== null) { #If wrappteratts are null, it means we want to skip the wrapper
      $wrapatts = $this->mergeClass($wrapatts);
      $wrapper = $this->div($label.$textarea,$wrapatts);
      return $wrapper;
    }
    return $label.$textarea;
  }
  public function mergeClass($arg1, $arg2 = null) {
    if (is_string($arg1)) $arg1 = ['class' => $arg1 ];
    if (is_array($arg1)) {
      $arg1['class'] =  keyval('class',$arg1) . " $arg2 ";
    }
    return $arg1;
  }

  public function selectset( $name='', $list=[], $selected = null, $labeltext=null, $inatts = [], $labatts=[], $wrapatts = []) {
    $labatts = $this->mergeClass($labatts);
    $inatts = $this->mergeClass($inatts);
    $label = '';
    if ($labeltext !==null) $label = $this->label($name, $labeltext, $labatts);
    $select = $this-> select($name, $list, $selected, $inatts);
    if ($wrapatts !== null) { #If wrappteratts are null, it means we want to skip the wrapper
      $wrapatts = $this->mergeClass($wrapatts);
      $wrapper = $this->div($label.$select,$wrapatts);
      return $wrapper;
    } 
    return $label.$textarea;
  }
  
  public function textset( $name='', $value=null, $labeltext=null, $inputatts = [], $labelatts=[], $wrapatts = []) {
    return $this->inputlabelset('text', $name, $value, $labeltext, $inputatts, $labelatts, $wrapatts);
  }
  public function inputlabelset($type, $name='', $value=null, $labeltext=null, $inatts = [], $labatts=[], $wrapatts = []) {
    $labatts = $this->mergeClass($labatts);
    $inatts = $this->mergeClass($inatts);
    $input = $this->input($type, $name, $value, $inatts);
    $label = '';
    if ($labeltext !== null) $label = $this->label($name, $labeltext, $labatts);
    if ($wrapatts !== null) { #If wrappteratts are null, it means we want to skip the wrapper
      $wrapatts = $this->mergeClass($wrapatts);
      $wrapper = $this->div($label.$input,$wrapatts);
      return $wrapper;
    }
    return $label.$input;
  }

   public function div($content,$attributes) {
   if (is_string($attributes)) $attributes = ['class'=>$attributes];
   return $this->html->tag('div', $content, $attributes);
}


  /** Looks like I'll have to do everything in here.... */

}

