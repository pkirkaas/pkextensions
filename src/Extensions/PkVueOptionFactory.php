<?php
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
  public $instance;  #Optional, the PkModel Instance
  public $class; #Optional, the PkModel subclass
  /** The very most basic generic defaults for all controls, can be overrided 
   * by the instance defaults & then by the arguement opts
   * @var array 
   */
  public static $sgendefaults=['labelclass'=>'pk-lbl', 'wrapperclass'=>'ctl-wrapper',
      'inputclass'=>'pk-inp'];
  public $defaults = []; #This instance may have defaults if not specified in input

  public static $inptypetomethods =[
      'input'=> 'makeInputCtlOpts',
      'select' => 'makeSelectCtlOpts',
      'checkbox' => 'makeCheckboxCtlOpts',
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
  
  public function makeControlOptArray($params) {
    $controls = keyVal('controls', $params);
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
    if ($asjson) {
      $ret=json_encode($ret,static::$jsonopts);
    }
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
      $params['selopts'] = $selopts::mkVueSelectArray($nullopt);
    }
    return $params;
  }
  
  //For regular inputs - not so different.
  public function makeInputCtlOpts($params=[]) {
    $name = keyVal('name',$params);
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
    return $params;
  }

  #Now the tricky checkbox

   public function makeCheckboxCtlOpts($params) {
    $name = keyVal('name',$params);
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
    return $params;
  }

  //put your code here
}
