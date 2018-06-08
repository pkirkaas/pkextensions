<?php
namespace PkExtensions;
use PkExtensions\Traits\PkDynamicPropsMethodsTrait;

/*
 * Description of PkDynamicFactory
 * Base for other, specific dynamic factories using dynamic props & methods trait
 * @author pkirkaas
 */
class PkDynamicFactory {
 // public $builders;
  public $instance;
  public function __constructor($instance) {
  //  $this->builders = $this->makeBuilderArray();
    $this->instance = instance;
    /*
    if (!$instance->instancetype || 
        !in_array($instance->instancetype, $this->getInstanceTypes(),1)) {
      return;
    }
     * 
     */
  }

  //Assumes array in form of: 
  /*
      ['name'=>'Social',
       'methods'=>$renderers,
       'keys'=>[
         'relationship' =>['profilelbl'=>"What's your relationsip status?",
            'frmlbl'=>"Relationship",'value'=>null],
        'color' =>['profilelbl'=>"What's your favorite color?",
            'frmlbl'=>"Favorite Color",'value'=>null],
        'movie' =>['profilelbl'=>"What's your favorite Movie?",
            'frmlbl'=>"Favorite Movie",'value'=>null],
        'book' =>['profilelbl'=>"What's your favorite book?",
            'frmlbl'=>"Favorite Book",'value'=>null],
          ],
        ],
   * */
  public function setMethodsAndProps() {
    $fnm = $this->getFieldsAndMethods();
    $keys = $fnm['keys'];
    $methods = $fnm['methods'];
    $this->instance->arrayKeys($keys);
    $this->instance->addInstanceMethod($methods);
  }
  //Override. Keyed first by type ('professional' or 'social'),
  //then method names, then closures */
  /*
  public function makeBuilderArray() {
    $renderFormField = function($key,$params=[]) {
      };
    $renderProfileField = function($key,$params) {
      };
    return [
        'sample'=>
            ['hi'=>function(){echo "hi";},
              'by'=>function(){echo "by";},
                  ]
        ];
  }
   * 
   */

  /*
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
   * 
   */
}
