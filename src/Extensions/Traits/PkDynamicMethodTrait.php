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
  public $methodfactory;
  public $methods;

  public  function addInstanceMethods($methods=null,$params=[]) {
    if (!$methods && !$this->methodFactoryClass) {
      return;
    }
    if (is_class($methods)) {
      $this->methodFactoryClass = $methods;
      $methods = new $methods($this, $params);
    }
    if (is_object($methods)) {
      $closures = $methods->getClosures($this,$params);
    }
      if (
        $methods = new $this->methodFactoryClass($this,$params);
      } 

    }
    if (is_class($methods) && implements(
          $methods = new $this->methodFactoryClass($this, $params);
        }
    }
    if (!$methods) {
      return;
    }
    if ($methods instanceOf PkExtensions\PkMethodGeneratorInterface) {
      if (is_class($methods)) {
        $this->methodFactoryClass = $methods;
        $methods = new $methods($this);
        $methods = $methods->generateMethods($this);

    foreach ($methods as $name=>$closure) {
      $this->instanceMethods[strtolower($name)]=$closure->bindTo($this);
    }
  }

  public function assignMethods($closures = null) {
    if ($closures && !$this->closures) {
      $this->closures = $closures;
    }
    if ($closures instanceOf PkExtenstions\PkClosuresInterface) {
      if (is_class($closures)) {
        $closures = new $closures($this);
      }
      $closures = $closures->generate($this);
    }
    if (!$closures) {
    }
    foreach ($closures as $name=>$closure) {
      $name = strtolower($name);
      $this->instanceMethods[$name]= $closure->bindTo($this,$this);
    }
  }

  public function __call($method, $args=[]) {
    $name = strtolower($method);
    if (!$this->instanceMethods ||
        !in_array($name,array_keys($this->instanceMethods),1)) {
       return parent::__call($method,$args);
    } else {
      pkdebug("The method: $method");
      return call_user_func_array($this->instanceMethods[$name], $args);
    }
  }
  /*
  public function __call($method, $args) {
    if (in_array($method, array_keys($this->newmethods),1)) {
      return call_user_func_array($this->newmethods[$method],$args);
    }
    return parent::__call($method, $args);
  }
   * 
   */
  public function ExtraConstructorDynamicMethods($atts = []) {
    //pkecho ("IN Extra Constructor");
    $methodfactory = unsetret($atts,'methodfactory');
    if (is_class($methodfactory)) {
      $this->methodfactory = $methodfactory;
    }
    //$this->closures = unsetret($atts,'closures');
    #A method factory class just needs to be construted with $this,
    #then examines $this->instancetype and add the instanceMethods for that type
    //$this->methodfactoryclass = unsetret($atts,'methodfactoryclass');
    $methodfactory = unsetret($atts,'methodfactory');
    $this->buildInstanceMethods($this->closures);
    #Can be used by builders to determine what instanceMethods to add
  }

  public function buildInstanceMethods($arg=null) {
    if (!$arg) {
      if ($this->closures) {
        return $this->assignMethods($this->closures);
      }
    }
    if ($arg instanceOf PkExtenstions\PkClosuresInterface) {
      $this->methodfactory = $methodfactory;
    }
    if (class_exists($this->methodfactory)) {
       $factory = new $this->methodfactory($this);
       //$factory->instance = $this;
      // pkecho("Factory", $factory);
      // print_r(['The factory Instance:'=>$factory]);
       $factory->setMethodsAndProps();
    }
  }
  
  public static $table_field_defs_DynamicType = [
    'instancetype' => ['type' => 'string', 'methods' => 'nullable'],
    'methodfactoryclass' => ['type' => 'string', 'methods' => 'nullable'],
    'typename' => ['type' => 'string', 'methods' => 'nullable'],
  ];


  /*
  public function getInstanceMethod($name) {
    return keyVal(strtolower($name), $this->instanceMethods);
  }

  public function callInstanceMethod($name,$args=[]) {
    $method = $this->getInstanceMethod($name);
    if (!$method) return null;
    return $method($args);
  }
   * 
   */

  /*
  public $methodNames;
  public function buildMethodNames() {
    if (!$this->instanceMethods) {
      return $this->methodNames = null;
    }
    return $this->methodNames = array_keys($this->instanceMethods);
  }
   */


}
