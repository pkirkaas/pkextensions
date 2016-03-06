<?php
namespace PkExtensions;
use \PkHtml;
use \Html;
class BaseTransformer {
  const NONVALID = '____NOT___A____VALID____EXPECTATION___';
  /**
   * @param PkModel $item - the object to transform
   * 
   * @param array $defaulttransform ['name'=>$name, 'function'=>$runnable]
   */

  public function __construct( $item = null, $defaulttransform = null) {
    $this->setItem($item);
    $this->name = keyval('name',$defaulttransform);
    $this->donothingaction = ['function' => function($data) {return $data;}];
  }
  public function setItem($item) {
    $this->item = $item;
  }
  protected $donothingaction;
  public $item;
  public $type;
  public $result;
  public $action;
  public $actions = [];
  public $arguments;
  public $h1; public $actionset =[]; public $actionsets =[];
  public $transforms = [];
  public function addTransforms(Array $transforms) {
    pkdebug("transforms:", $transforms);
    foreach ($transforms as $name => $runnable) {
      //$this->$transforms[$name] = ['function'=>$runnable];
      $this->transforms[$name] = $runnable;
    }
  }

  /** This is amazing. It allows you to add as many custom formatting actions
   * as you want, either for this instance, or for permanently associating
   * with your model attributes. And a model can have multiple, keyed transformers,
   * so you can represent the same data from the same object just by calling on a 
   * transformer!
   * @param array $actionset - either a single, associative array of
   *  ['function'=>$function, 'args'=> [], 'name'=>$name|null], 
   *   OR an array(indexed or assoc) of such arrays, OR arrays containing
   *    ['property'=>$propertyname, 'call'=>$args (optional, to indicate a function)
   * 
   * @param string $property - optional, if the user wants this acction array 
   *   permanently associated with this property, FOR THIS TRANSFORMER ONLY.
   * @param string $type - expect a "_get" or _call or _callStatic.
   * @return $this OR the modified property from the underlying PkModel
   */
  /*
  public function addActionSet($actionset, $property=null, $type=null) {
    $result = static::NONVALID;
    if(is_array_assoc($actionset)) $actionset = [$actionset];
    #Want to figure out if this is a single action array, or an array of them
    if(!empty($actionset['function'])) $actionset = [$actionset];
    if ($property !==null) $ourset = &$this->actionsets[$property];
    else $ourset = &$this->actionset;
    foreach($actionset as $action) {
      if (is_string($action)) {
        $result = $this->__get($action);
      } else if (is_array($action)) {
        $att=keyval('attribute',$action);
        if (!$att) continue;
        $args = keyVal('args',$action,[]);
        if (!is_array($args)) $args = [$args];
        $method ='__'. keyVal('method',$action,'get');
        $result = $this->$method($att,$args);
      }
      if ($result !== static::NONVALID) {
        $this->result = $result;
        return $this->executeActionSet($ourset);
      }
      if (array_key_exists('function', $action)) $ourset[] = $action;
    }
    return $this;
  }

  public $name;
  protected $thingstohide = [];
  public function hide($arr=[]) {
    if (!is_array($arr)) $arr = [arr];
    $this->thingstohide=array_merge($arr,['',null,false]);
    return $this;
  }
  public function wrap($el, $att=[]) {
    $this->actionset[]= ['function'=>[$this,'dowrap'],
                'args' =>[$el, $att],
        ];
    return $this;
  }
   * 
   */
  /** Show for boolean can show a checked or empty box, for example */
  /*
  public function showforboolean($true,$false=null) {
    $this->showforboolean = [$false, $true];
    return $this;
  }
  public $showforboolean;
   * 
   */
  /** If there is no value, nothing will show. It there is, that will be
   * shown instead.
   * @param scalar|null $val
   * @return \PkExtensions\BaseTransformer
   */
  /*
  public $hideempty;
  public function forempty($val = null) {
    if (!$val) $this->hideempty=true;
    else $this->hideempty = $val;
    return $this;
  }
  public function postpend($string) {
    $this->actionset[]=['function'=> [$this,'dopostpend'],'args'=>[$string]];
    return $this;
  }
  public function dopostpend($result='', $string) {return $result.$string;}
  public function prepend($string) {
    $this->actionset[]=['function'=>[$this,'doprepend'],'args'=>[$string]];
    return $this;
  }
  public function doprepend($result = '',$string) {return $string.$result;}
   * 
   */
  public function executeActionSet($actionset = null) {
    if (!$actionset) $actionset = &$this->actionset;
    $result = $this->result;
    //pkdebug("Result: [$result], actionset:",$actionset);
    pkdebug("Result: [$result]");
    $return = $this->result;
    /*
    if (in_array($result,$this->thingstohide)) {
      $result = null;
      $this->thingstohide = [];
      $hideempty = $this->hideempty;
      $this->hideempty = null;
      if($hideempty === true) return null; 
      $return = $hideempty;
    }
    if (is_array($this->showforboolean)) {
      $showbool = $this->showforboolean;
      $this->showforboolean = null;
      $return = ($result===null) ?  keyVal(0,$showbool)  : keyVal(1,$showbool);
    }
     * 
     */
    foreach ($this->actionset as $action) {
      $args = keyVal('args',$action,[]);
      $action = $action['function'];
      array_unshift($args,$return);
      //pkdebug("ARGS", $args, "return [$return], action",$action);
      $return = call_user_func_array($action,$args);
    }
    $this->actionset = [];
    $this->result = null;
    return $return;
  }
  public function dowrap($result, $tag, $atts=[]) {
    $out = PkHtml::tag($tag, $result, $atts);
    return $out;
  }
  public function getItem() {
    return $this->item;
  }
  public $attributes = [];
  public function __get($key) {
    $name = removeEndStr($key,'Tfrm');
    if (!$name) {
      $this->result = $this->item->$key;
      return $this->executeActionSet();
    }
    //pkdebug("NAME [$name] Transforms", $this->transforms);
    $actionarr = keyval($name, $this->transforms, $this->donothingaction);
    $actionarr['name']=$name;
    $actionarr['args']=[];
    $this->actionset[]=$actionarr;
    return $this;
  }
  public function __set($key, $val) {
    if (is_array($this->item)) $this->item[$key]= $val;
    else if (is_object($this->item)) $this->item->$key = $val;
  }

  public function __call($method, $args=[]) {
    $name = removeEndStr($method,'Tfrm');
    if (!$name) {
      $this->result = call_user_func_array([$this->item,$method],$args);
      return $this->executeActionSet();
    }
    $actionarr = keyval($name, $this->transforms, $this->donothingaction);
    $actionarr['args']=$args;
    $actionarr['name']=$name;
    $this->actionset[]=$actionarr;
    return $this;
  }

}
