<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/*
 * Description of PkVueOptionFactory
 * @author pkirkaas
 * This is a base class that can take PkModel instances, PkReferences, lists of
 * attributes & types, & build a JavaScript data object (or array of objects)
 *  that can be used to initiliaze Vue components contained in a wrapper component
 * with a v-for. Especially useful for form sections, & submission by ajax.
 * This base class make only 3 kinds so far input, select & checkbox, can be added to 
 * or subclased.
 */
namespace PkExtensions;
use PkExtensions\Models\PkModel;
use PkExtensions\Traits\UtilityMethodsTrait;
Class PkVueOptionFactory {
  use UtilityMethodsTrait;
  #Some standard formats & sizes
  #orientation can be horizontal or vertical
  public static $formorientation = ['horizontal','vertical'];
  #width if vertical, height if horizontal
  #For vertical forms:
  public static $formdim = [
      'thin'=>'10em',
      'semithin'=>'20em',
      'medium'=>'30em',
      'thick' => '40em',
      'xthick' => '50em'
      ];
  #Wrapper width with be 90% & centered
  public static $labelplacement = ['top','left','right'];
  #If label to left or right of input, what proportion of form width?
  public static $labelinpratio = [.2, .4, .5, .6, .8];
  #Probably want all wrappers w. same width, padding, margin, border

  #Params are keyed by type with custom props. or None
  public static  $types=['form','wraper','input','check',
        'select', 'textarea', 'input'];

  public function setCtlProps($params=[]) {
    $allProps =  [];
    foreach (static::$types as $type) {
      $method = "set{$type}props";
      $allProps[$type] = 
          $this->mkStyleAndClass($this->$method(keyVal($type,$params,[])));
    }
    return $allProps;
  }

  #All dims in em, so we can adust the other components
  public static $formdefaults = [
      'width'=>"20em",
      'margin'=> ".5em",
      'padding' => ".5em",
      'border' => 'solid #aaa 1px',
      'border-radius' => ".5em",
      'background-color' => '#ccd'
      ];
  public function setFormProps($params=[]) {
    return $this->formProps = array_merge(static::$formdefaults, $params);
  }
  public $formProps;
  public $wrapperProps;
  public static $wraperDefaults = [
      'margin' => ".3em",
      'padding'=> ".3em",
      'background-color' => '#eee',
      'border' => 'solid #bbb 1px',
      ];
  public $wrapperprops;
  public function setWraperProps($params=[]) {
    return $this->wrapperprops= array_merge (static::$wraperDefaults, $params);
  }

  public static $checkDefaults = [
      'propwidth'=>20,
      'label'=>'left',
    ];
  public $checkprops;
  public function setCheckProps($params=[]) {
    return $this->checkprops = array_merge(static::$checkDefaults,$params);
  }

  public static $selectDefaults = [
      'propwidth'=> 90,
      'label' => 'top',
      'margin' => ".3em",
      'padding' => ".3em",
      ];
  public $selectprops;
  public function setSelectProps($params=[]) {
    return $this->selectprops =
        array_merge(static::$selectDefaults, $params);
  }

  public static $textareaDefaults =[
      'propwidth' => 90,
      'height' => "10em",
      'border'=> 'solid #777 1px',
      'margin' => ".3em",
      'padding' => ".3",
      'background' => '#fee',
      'label'=>'top'
      ];
public $textareaprops;
public function setTextAreaProps($params=[]) {
  return $this->textareaprops =
      array_merge(static::$textareaDefaults, $params);
}

public $inputprops;
public static $inputPropDefaults = [
    'border' => "solid #666 1px",
    'margin' => '.3em',
    'padding' => '.3em',
    'background' => '#eff',
    ];

public function setInputProps($params=[]) {
  return $this->inputprops =
      array_merge(static::$inputPropDefaults, $params);
}


/** Depends on form prop set first */
function mkStyleAndClass($props = []) {
  $ctlclass =unsetret($props,'class');
  if (!$ctlclass) {
    $ctlclass = unsetret($props,'ctlclass');
    $lblclass = unsetret($props,'lblclass');
  }

  $lblstyle = $ctlstyle =  " ";
  /*
  $fwidth = keVal('width',$this->formProps)?:
    keyVal('width',static::$formDefaults);
   * *
   */
  
  $atts=['border', 'background', 'border-radius',
      'margin', 'padding', 'height'];
  foreach ($atts as $att) {
    if (in_array($att,array_keys($props),1)) {
      $ctlstyle .= " $att:".keyVal($att,$props)."; ";
    }
  $ctlpropwidth = keyVal('propwidth',$props,50) -3;
  if (keyVal('label',$props) == 'left') {
    $lblpropwidth = 94 - $ctlpropwidth;
  } else {
    $lblpropwidth = $ctlpropwidth;
  }
  $ctlstyle .= " width:$ctlpropwidth"."; ";
  $lblstyle =  " width:$lblpropwidth"."; ";
  return [
      'ctlclass'=>$ctlclass,
      'lblclass'=>$lblclass,
      'lblstyle'=>$lblstyle,
      'ctlstyle'=>$ctlstyle,
      ];
  }
    
}



  

  public $instance;  #Optional, the PkModel Instance
  public $class; #Optional, the PkModel subclass
  /** The very most basic generic defaults for all controls, can be overrided 
   * by the instance defaults & then by the arguement opts
   * @var array 
   */
  #Have to set the properties of the form first, then the wrappers,
  #then the controls
  public static $sgendefaults=['labelclass'=>'pk-lbl', 'wrapperclass'=>'ctl-wrapper',
      'inputclass'=>'pk-inp'];
  public $defaults = []; #This instance may have defaults if not specified in input

  public static $inptypetomethods =[
      'input'=> 'makeInputCtlOpts',
      'select' => 'makeSelectCtlOpts',
      'checkbox' => 'makeCheckboxCtlOpts',
      'textarea' => 'makeTextAreaCtlOpts',
      ];

  public static function getInputTypeMethods() {
    $tag= 'inptypetomethods';
    $closure = function() use($tag) {
      return static::getAncestorArraysMerged($tag);
    };
    return static::getCached($tag, $closure);
  }


  public function __construct($params = []) {
    $this->instance = keyVal('instance',$params);
  }

  //The form component takes 2 properties - the FormOpts object
  //Defining the Form, and the CtlOpts array of objects making up
  // the form. So here we return an array of both.
  
  /*
  public static  $types=['form','wraper','input','checkbox',
        'select', 'textarea', 'input'];

  public function setCtlProps($params=[]) {
   * */
  /** The Main function that builds the forms */
  public function makeFormsAndControls($params=[]) {
    //$ctlFmt = keyVal('ctlFme',$params);
    $ret = [
      'formopts'=>$this->makeFormOpts(keyVal('formopts',$params),$params),
      'inpopts'=>$this->makeControlOptArray(keyVal('controls', $params),$params)
        ];
    if (!keyVal('asarray',$params)) {
      $ret=json_encode($ret,static::$jsonopts);
    }
    return $ret;
  }


  /** Not just formatting, but the url,how to handle, etc. */
  public function makeFormOpts($formopts) {
    return $formopts;
  }

  /** Takes a loosly strutured array & normalizes it. Also takes
   * formatting as styles or classes
   * @param array $params=> ['controls' indexed array of individual fields
   *     and 'opts' - that can apply to all controls full input specs to make a form
   * @return Vue Form
   */ 
  public function makeControlOptArray($controls,$params) {
    $styles = $this->setCtlProps(keyVal('ctlProps', $params));
    $asjson = keyVal('asjson',$params,1);
    $normalized = [];
    foreach ($controls as $name=>$control) {
      $control['params']['name'] = $name;
      $control['inptype'] = keyVal('inptype',$control) ?: 'input';
      $control['params']['inptype'] = $control['inptype'];
      $normalized[$name] = $control;
    }
    $ret = [];
    foreach ($normalized as $name=>$control) {
      $ret[$name] = $this->applyMethodToCtl($control);
    }
    /*
    if ($asjson) {
      $ret=json_encode($ret,static::$jsonopts);
    }
     * *
     */
    return $ret;

  }

  public function applyMethodToCtl($control) {
      $method = static::getInputTypeMethods()[$control['inptype']];
      return $this->$method($control['params']);
  }
  /** Makes a single selection opt object to use in Vue selection component.
   * @param string $name - the control/attribute name
   * @param array|PkRefManager $selopts - either idx array [['label'=>$label1,'value'=>$val1,]
   * OR a PKRefManager subclass, which generates the above with static::mkVueSelectArray($null)
   * @param scalar|null - $value if there is a known value
   * @param PkModel $instance  - if it's for an different instance. If not null, 
   * $value takes precedence over $instance->name
   * @param array - Options for the generated ctl - like, label, classes, etc.
   * 
   */
  public function makeSelectCtlOpts($params=[]){
  //  $label = keyVal('label',$params);
    $name = keyVal('name',$params);
    $selopts = keyVal('selopts',$params);
    //$compopts = keyVal('comopts',$params,[]); 
    $value = keyVal('value',$params);
    $instance = keyVal('instance',$params);
    $nullopt = keyVal('nullopt',$params);
    if ($value === null) {
      if ($instance) {
        $value = $instance->$name;
      } else if ($this->instance) {
        $instance = $this->instance;
        $value = $instance->$name;
      }
    }
    $params['value']=$value;
    #Careful here, selopts will be a class, maybe in differenc namespace
    if (is_subclass_of($selopts,PkRefManager::class)) {
      $params['options'] = $selopts::mkVueSelectArray($nullopt);
    } else {
      pkdebug("Whoops, no selopts for params:",$params);
    }
    unset($params['selopts']);
    return $params;
  }
  
  public function makeTextareaCtlOpts($params=[]){
  //  $label = keyVal('label',$params);
    $name = keyVal('name',$params);
    $value = keyVal('value',$params);
    $instance = keyVal('instance',$params);
    if ($value === null) {
      if ($instance) {
        $value = $instance->$name;
      } else if ($this->instance) {
        $instance = $this->instance;
        $value = $instance->$name;
      }
    }
    $params['value']=$value;
    return $params;
  }
  
  //For regular inputs - not so different.
  public function makeInputCtlOpts($params=[]) {
    $name = keyVal('name',$params);
    $value = keyVal('value',$params);
    $instance = keyVal('instance',$params);
    if ($value === null) {
      if ($instance) {
        $value = $instance->$name;
      } else if ($this->instance) {
        $instance = $this->instance;
        $value = $instance->$name;
      }
    }
    $params['value']=$value;
    return $params;
  }

  #Now the tricky checkbox


   public function makeCheckboxCtlOpts($params) {
    $cbdefaults = [
        'lblprop'=>80, #Ratio of lbl to checkbox
        'nullopt'=>true,
        'lblpos'=>'left',
        ];
        
    $name = keyVal('name',$params);
    $value = keyVal('value',$params);
    //$params['checked'] = !!$value;
    $instance = keyVal('instance',$params);
    $nullopt = keyVal('nullopt',$params);
    $labelprop = keyVal('labelprop',$params,80); 
    if ($value === null) {
      if ($instance) {
        $value = $instance->$name;
      } else if ($this->instance) {
        $instance = $this->instance;
        $value = $instance->$name;
      }
    }
    $params['checked'] = !!$value;
    $params['value']=$value;
    return $params;
  }

  //put your code here
}
