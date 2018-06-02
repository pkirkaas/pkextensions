<?php
namespace PkExtensions;
use PkExtensions\Traits\PkDynamicPropsMethodsTrait;

/*
 * Description of PkDynamicFactory
 * Base for other, specific dynamic factories using dynamic props & methods trait
 * @author pkirkaas
 */
class PkDynamicFactory {
  public $builders;
  public $instance;
  public function __constructor($instance) {
    $this->builders = $this->makeBuilderArray();
    $this->instance = instance;
    if (!$instance->instancetype || 
        !in_array($instance->instancetype, $this->getInstanceTypes(),1)) {
      return;
    }
  }
  public function makeBuilderArray() {
    return [
        'sample'=>
            ['hi'=>function(){echo "hi";},
              'by'=>function(){echo "by";},
                  ]
        ];
  }

  public function getInstanceTypes() {
    return getBuilder(true);
  }
  public function getBuilder($key=null) {
    if ($key) return array_keys($this->builders);
    return $this->builders[$this->instance->instancetype];
  }
  function applyBuilder() {
    $builder = $this->getBuilder();
    $instance = $this->instance;
    $instance->addInstanceMethod($builder);
  }
}
