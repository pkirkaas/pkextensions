<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */

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
use PkExtensions\PkHtmlBuilder;

class PkFormBuilder extends FormBuilder {

  /**
   * Create a new form builder instance.
   * if config/app.php set right, will use PkHtmlBuilder to build $html
   * @param  \Illuminate\Routing\UrlGenerator  $url
   * @param  \Illuminate\Html\HtmlBuilder  $html
   * @param  string  $csrfToken
   * @return void
   */
  public function __construct(PkHtmlBuilder $pkhtml, UrlGenerator $url, Factory $view, $csrfToken) {
    parent::__construct($pkhtml, $url, $view, $csrfToken);
  }

    public function open(array $options = []) {
      if (array_key_exists('model', $options)) {
        //static::setModel($options['model']);
        $this->setModel($options['model']);
        unset($options['model']);
      }
    return parent::open($options);
    }

  protected function setTextAreaSize($options) {
    if (isset($options['size'])) {
          return $this->setQuickTextAreaSize($options);
    }
    return $options;
  }



  public function form($content, array $options = []) {
    return $this->open($options) . $content . $this->close();
  }

  public function setNamePrefix($namePrefix = '') {
    $this->html->name_prefix = $namePrefix;
  }

  public function getNamePrefix() {
    return $this->html->name_prefix = $namePrefix;
  }

  public function addToNamePrefix($first, $second = null) {
    $pre = $this->getNamePrefix();
    if (!$pre) $this->setNamePrefix($first);
    else $this->setNamePrefix($pre . '[' . $first . ']');
    if ($second)
        $this->setNamePrefix($this->getNamePrefix() . '[' . $second . ']');
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
  public function selectFromModels($name, $models = [], $displayField = '', $selected = null, $options = []) {
    if (!count($models)) return $this->select($name, [], $selected, $options);
    $list = [];
    foreach ($models as $model) {
      $list[$model->id] = $model->$displayField;
    }
    return $this->select($name, $list, $selected, $options);
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
  public function boolean($name, $checked = null, $options = [], $unset = '0', $value = 1) {
    return "\n<input type='hidden' name='$name' value='$unset' />\n" .
        $this->checkbox($name, $value, $checked, $options);
  }

  /** Creates a radio-set - like 'select' with radio buttons - returns only one value
   * Adds the 'rs-layout' class to every element - but it shouldn't have a default
   * styling - can put inside a 'bs-horizontal' div or 'bs-vertical'...
   * @param string $name - the input name - the POST array key
   * @param array $list - array of $values($keys) => $labels
   * @param scalar $value - the current
   * @param array $options
   * @param scalar|null $unset - the value if none of the options are selected
   * @return string - HTML for the radio-set
   */
  public function radioset($name, $list = [], $value = null, $options = [], $unset = null) {
    $options['class'] = keyVal('class', $options) . ' rs-layout';
    $rs_style = keyVal('rs-style', $options);
    unset($options['rs-style']);
    $out = "\n<div class='radioset rs-layout $rs_style'>\n";
    $out .= "\n<input type='hidden' name='$name' value='$unset' />\n";
    foreach ($list as $key => $label) {
      $checked = $key == $value ? true : false;
      $options['id'] = $name . '_' . $key;
      $out .= "\n<div class='radio rs-layout'>";
      $out .= "<label class='rs-layout'>";
      $out .= $this->radio($name, $key, $checked, $options);
      $out .= "$label</label>\n";
      $out .= "\n</div>";
    }
    $out .= "\n</div>\n";
    return $out;
  }

  public function yesno($name, $value = null, $options = [], $unset = null) {
    $options = $this->mergeClass($options,'rs-yn');
    $options['rs-style'] = 'rs-yn';
    return $this->radioset($name, [1 => 'Yes', 0 => 'No'], $value, $options, $unset);
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
  public function multiselect($name, $list = [], $values = null, $options = [], $unset = null) {
    $values = $this->getValueAttribute($name, $values);
    if ($values && is_string($values)) { #JSON Encoded?
      $values = json_decode($values,1);
    }
    if (is_string($options)) {
      $options=['class'=>$options];
    }
    $wrapperclass = unsetret($options,'wrapperclass',  ' form-control ');
    //$wrapperclass = unsetret($options,'wrapperclass', );
    $allclass = unsetret($options, 'allclass' );
    //unset($options['wrapperclass']);
    //unset($options['allclass']);
    $out = "\n<div class='multiselect $wrapperclass $allclass '>\n";
    $out .= "\n<input type='hidden' name='$name' value='$unset' />\n";
    foreach ($list as $key => $label) {
      $checked = in_array_equivalent($key, $values) ? true : false;
      $options['id'] = $name . '_' . $key;
      $out .= "\n<div class='pk-checkbox $allclass'>";
      $out .= "<label class='multiselect-label $allclass '>";
      //$out .= $this->checkbox($name."[$i]",$key,$checked,$options);
      $options['class'] = keyval('class', $options) . " $allclass ";
      $out .= $this->checkbox($name . "[]", $key, $checked, $options);
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
    if (is_string($options)) $options = ['class'=>$options];
    if (!array_key_exists('type', $options)) $options['type'] = 'submit';
    if (!array_key_exists('name', $options)) $options['name'] = 'submit';
    if (!array_key_exists('value', $options)) $options['value'] = 'submit';

    return $this->toHtmlString('<button' . $this->html->attributes($options) . '>' . $label . '</button>');
  }

  /**
   * Make an Ajax clickable component, assuming JS support.
   *
   * Makes an arbitrary DOM element that has the data attribute:
   * <tt>data-pk-ajax-element</tt>
   * so clicking will automatically perform an AJAX call.
   * The URL for the call will be in 'data-ajax-url', and so on 

   * @param  array|string  $options:
   *   In simplest form, $options is a string with the ajax-url to call.
   *   Then just render a button, with content/label $content
   *   If is_array($options), can contain all arguments - 
   *    - Normal HTML attributes, plus optionally:
   *   'params' => [$key1=>$val2, $key2=>$val2,] data param arr for AJAX
   * 
   * 
   *  'attr-target' => string: 'name of attribute target_to_recieve_response
   *  'selector-target' => string: The selector that matches this element or child - replace inner HTML with response
   *  'func-target' => string: The name of the JS function to be called with fn(click_target, data, func_arg)
   * 
   *   'attr-arg' => mixed - scalar or array use after AJAX to set the named attribute with.
   *         If an array, expect the AJAX return is a key to the array, to get the value
   *   'selector-arg' => mixed - scalar or array use after AJAX to set the
   *       inner HTML of the matched element with. If array, AJAX return key to the array, to get the value
   * 
   *   'func-arg' => mixed - scalar or array to be passed to the JS function from this component
   * 
   *   If no targets are set, the return from the AJAX call does nothing
   * 
   * @param string $tag - the HTML element to make clickable
   * @param  string $content - the content of the HTML element, if it's a content type
   * 
   * @return \Illuminate\Support\HtmlString
   */
  public function ajaxElement($options = [], $content = null, $ajax_url = null, $tag = 'button') {
    if (is_string($options)) $options = ['ajax-url' => $options];
    $options[] = 'data-pk-ajax-element';

    $content = keyVal('content', $options, $content);
    unset($options['content']);

    $options['data-ajax-url'] = keyVal('ajax-url', $options, $ajax_url);
    unset($options['ajax-url']);
    if (empty($options['data-ajax-url']))
        throw new \Exception("No AJAX URL for AJAX Element");

    $tag = keyVal('tag', $options, $tag);
    if ($tag === 'button')
        $options['type'] = keyVal('type', $options, 'button');
    if (empty($options['params'])) $options['data-ajax-params'] = '';
    else $options['data-ajax-params'] = http_build_query($options['params']);
    unset($options['params']);

    $options['data-attr-target'] = keyVal('attr-target', $options);
    unset($options['attr-target']);

    $options['data-selector-target'] = keyVal('selector-target', $options);
    unset($options['selector-target']);

    $options['data-func-target'] = keyVal('func-target', $options);
    unset($options['func-target']);

    #JSON encode target-args - but convert empty array to empty obj manually
    if (array_key_exists('func-arg', $options)) {
      if ($options['func-arg'] === []) $options['data-func-arg'] = '{}';
      else $options['data-func-arg'] = json_encode($options['func-arg']);
      unset($options['func-arg']);
    }


    #JSON encode target-args - but convert empty array to empty obj manually
    if (array_key_exists('attr-arg', $options)) {
      if ($options['attr-arg'] === []) $options['data-attr-arg'] = '{}';
      else $options['data-attr-arg'] = json_encode($options['attr-arg']);
      unset($options['attr-arg']);
    }


    #JSON encode target-args - but convert empty array to empty obj manually
    if (array_key_exists('selector-arg', $options)) {
      if ($options['selector-arg'] === []) $options['data-selector-arg'] = '{}';
      else
          $options['data-selector-arg'] = json_encode($options['selector-arg']);
      unset($options['selector-arg']);
    }


    pkdebug('options', $options);
    if (PkHtmlRenderer::contentTag($tag)) {
      return $this->toHtmlString("<$tag"
              . $this->html->attributes($options) . '>' .
              $content . "</$tag>\n");
    }
    if (PkHtmlRenderer::selfClosingTag($tag)) { #Ignore content
      return $this->toHtmlString("<$tag"
              . $this->html->attributes($options) . ' />');
    }
    throw new PkException("Invalid args to ajaxElement");
  }

  /** Convenience function - returns ajaxElement with simple defaults */
  public function ajaxButton($options = [], $content = null) {
    return $this->ajaxElement($options, $content);
  }
  public function getModel() {
    return $this->model;
  }

  /** For use with the Query controls - to select the criteria
   * 
   */
  public function critselect($basename, $options=[]) {
    $querymodel = $this->getModel();
    if (ne_string($options)) {
      $options = ['class'=>$options];
    }
    return $this->select($basename.'_crit', $querymodel::staticCritset($basename),null,$options);
  }

  /**
   * Used for both "Exists" & "Boolean"
   * @param type $basename
   * @return type
   */
  public function valis($basename) {
    return $this->hidden($basename.'_val',1);
  }
  public function valtext($basename,$options=[]) {
    if (ne_string($options)) {
      $options = ['class'=>$options];
    }
    return $this->text($basename.'_val', null, $options);
  }
  public function valmultiselect($basename,$list=[],$options=[]) {
    if (ne_string($options)) {
      $options = ['class'=>$options];
    }
      return $this->multiselect($basename.'_val',$list,null,$options);
  }
  
  public function critvalmultiselect($basename,$list=[],$valopts=[], $critopts=[]){
    $ps = new PartialSet();
    $ps[]=$this->critselect($basename,$critopts);
    $ps[]=$this->valmultiselect($basename,$list, $valopts);
    return $ps;
  }

  public function critvaltext($basename,$valopts,$critopts) {
    $ps = new PartialSet();
    $ps[]=$this->critselect($basename,$critopts);
    $ps[]=$this->valtext($basename, $valopts);
    return $ps;
  }



  public function critvalis($basename,$critopts=[]) {
    $ps = new PartialSet();
    $ps[]=$this->critselect($basename,$critopts);
    $ps[]=$this->valis($basename);
    return $ps;
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
  public function customInputField($name, PkModel $object, $value = null, $type = null, $options = []) {
    $valids = $object->canEditThisField($name);
    if ($valids === false) {
      return "\n<div class='display_val val'>" . $object->displayValue($name, $value) . "</div>\n";
    }
    if ($valids === true) {
      if (!$type) $type = 'text';
      if ($type === 'boolean') {
        return $this->boolean($name, 1, $object->$name, $options);
      }
      if ($type === 'textarea') {
        return $this->textarea($name, $object->$name, $options);
      }
      //return "\n<input type='text' class='display_val val' value='".$object->displayValue($name)."'/>\n";
      return $this->input($type, $name, $object->$name, $options);
    }
    if (is_array($valids)) { #Make a select option control, limited to the options
      if (!$type) $type = 'select';
      return $this->$type($name, $valids, $object->$name, $options);
    }
    throw new \Exception("Invalid 'valids' result from PkModel instance");
  }

  /** Some functions that just combine other functions to take some of the tedium out.. */
  public function textareaset($name, $value = null, $labeltext = '', $inatts = [], $labatts = [], $wrapatts = []) {
    $labatts = $this->mergeClass($labatts);
    $inatts = $this->mergeClass($inatts);
    $textarea = $this->textarea($name, $value, $inatts);
    $label = '';
    if ($labeltext !== null) $label = $this->label($name, $labeltext, $labatts);
    if ($wrapatts !== null) { #If wrappteratts are null, it means we want to skip the wrapper
      $wrapatts = $this->mergeClass($wrapatts);
      $wrapper = $this->div($label . $textarea, $wrapatts);
      return $wrapper;
    }
    return $label . $textarea;
  }

  /** Returns an array with its 'class' key set to an array or string, combining
   * values from $arg1 & $arg2 
   */
  public function mergeClass($arg1, $arg2 = null) {
    if (!$arg1) {
      if (is_array($arg2)) {
        $arg2['class'] = keyVal('class', $arg2);
        return $arg2;
      }
      return ['class'=>$arg2];
    }
    if (is_string($arg1)) $arg1 = ['class' => $arg1];
    if (is_array($arg1)) {
      if (empty($arg1['class'])) {
        if (is_string($arg2) || is_array_indexed($arg2)) {
          $arg1['class'] = $arg2;
          return $arg1;
        }
        $arg1['class']='';
      }
      if (is_array($arg1['class'])) {
        if (is_string($arg2)) $arg1['class'][] = $arg2;
        else if (is_array($arg2))
            $arg1['class'] = array_merge($arg1['class'], $arg2);
        return $arg1;
      }
      if (is_string($arg1['class'])) {
        if (is_array($arg2)) {
          $arg2[] = $arg1['class'];
          $arg1['class'] = $arg2;
        } else if (is_string($arg2)) {
          $arg1['class'].= " $arg2 ";
        }
      }
    }
    return $arg1;
  }

  public function selectset($name = '', $list = [], $selected = null, $labeltext = null, $inatts = [], $labatts = [], $wrapatts = []) {
    $labatts = $this->mergeClass($labatts);
    $inatts = $this->mergeClass($inatts);
    $label = '';
    if ($labeltext !== null) $label = $this->label($name, $labeltext, $labatts);
    $select = $this->select($name, $list, $selected, $inatts);
    if ($wrapatts !== null) { #If wrappteratts are null, it means we want to skip the wrapper
      $wrapatts = $this->mergeClass($wrapatts);
      $wrapper = $this->div($label . $select, $wrapatts);
      return $wrapper;
    }
    return $label . $textarea;
  }

  public function textset($name = '', $value = null, $labeltext = null, $inputatts = [], $labelatts = [], $wrapatts = []) {
    return $this->inputlabelset('text', $name, $value, $labeltext, $inputatts, $labelatts, $wrapatts);
  }

  public function inputlabelset($type, $name = '', $value = null, $labeltext = null, $inatts = [], $labatts = [], $wrapatts = []) {
    $labatts = $this->mergeClass($labatts);
    $inatts = $this->mergeClass($inatts);
    $input = $this->input($type, $name, $value, $inatts);
    $label = '';
    if ($labeltext !== null) $label = $this->label($name, $labeltext, $labatts);
    if ($wrapatts !== null) { #If wrappteratts are null, it means we want to skip the wrapper
      $wrapatts = $this->mergeClass($wrapatts);
      $wrapper = $this->div($label . $input, $wrapatts);
      return $wrapper;
    }
    return $label . $input;
  }

  public function div($content, $attributes) {
    if (is_string($attributes)) $attributes = ['class' => $attributes];
    return $this->html->tag('div', $content, $attributes);
  }

  /** Looks like I'll have to do everything in here.... */
}
