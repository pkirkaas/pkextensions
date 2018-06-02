<?php
/*
 * This adds a field to an object, $instancetype,
 * and an array of names=>closures - which can be set based on the instancetype
 * Especially useful with Json fields - so a single model/class can fill MANY
 * roles. Also provides an empty method used in the constructor, that can be
 * overridden by implenting classes to add methods based on the instances type.
 * 
 */

namespace PkExtensions\Traits;
/**
 * @author pkirkaas
 */
trait PkDynamicMethodTrait {
  public $instanceMethods=[];
  public  function addInstanceMethod(Array $methods) {
    foreach ($methods as $name=>$closure) {
      $this->instanceMethods[strtolower($name)]=$closure->bindTo($this);
    }
  }
  public function ExtraConstructorDynamicMethods($atts = []) {
    $this->instancetype = keyVal('instancetype',$atts);
    #A method factory class just needs to be construted with $this,
    #then examines $this->instancetype and add the instanceMethods for that type
    $this->methodfactoryclass = keyVal('methodfactoryclass',$atts);
    $this->buildInstanceMethods();
    #Can be used by builders to determine what instanceMethods to add
  }

  public function buildInstanceMethods($methodfactoryclass=null) {
    if ($methodfactoryclass) {
      $this->methodfactoryclass = $methodfactoryclass;
    }
    if (class_exists($this->methodfactoryclass)) {
       new $this->methodfactoryclass($this);
    }
  }
  public static $table_field_defs_DynamicType = [
    'instancetype' => ['type' => 'string', 'methods' => 'nullable'],
    'methodfactoryclass' => ['type' => 'string', 'methods' => 'nullable'],
  ];


  public function getInstanceMethod($name) {
    return keyVal($name, $this->instanceMethods);
  }

  public function callInstanceMethod($name,$args=[]) {
    $method = $this->getInstanceMethod($name);
    if (!$method) return null;
    return $method($args);
  }

  /*
  public $methodNames;
  public function buildMethodNames() {
    if (!$this->instanceMethods) {
      return $this->methodNames = null;
    }
    return $this->methodNames = array_keys($this->instanceMethods);
  }
   */

  public function __call($method, $args=[]) {
    if (!$this->instanceMethods ||
        !in_array(strtolower($method),array_keys($this->instanceMethods,1))) {
      return parent::__call($method,$args);
    }
    return call_user_func_array($this->instanceMethods[strtolower($method)], $args);
  }

}
