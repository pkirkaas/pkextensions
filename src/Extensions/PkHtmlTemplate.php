<?php
namespace PkExtensions;
/**
 * A keyed Html template, that substitutes keyed values & defaults
 */
class PkHtmlTemplate extends PkHtmlRenderer {
  /**
   * @var string 
   * An HTML Template string, with template keys as: {{key}}, like: 
   * "<div class='{{wrapClass}}'
   *   <div class='{{lblClass}}'{{lblVal}}</div>
   *   <div class='{{inpWrapClass}}'>{{input}}</div>
   * </div>";
   */
  public $tplStr='';
  /**
   * @var array 
   * Associative array of values to substitute into the $tplStr, like:
   * ['input'=>$input]
   */
  public $values = [];
  /**
   * @var array 
   * Default values, if not specified in $values, like:
   * ['lblClass'=>'pk-lbl', 'wrapClass'=>'pk-wrap', 'inpWrapClass'=>'pk-inp-wrap']
   */
  public $defaults = [];

  /**
   * Takes a single associative array arg, $tplStr, as:
   * ['tplStr'=>$tplStr, 'values'=>$values, 'defaults'=>$defaults], or 3 args as
   * below.
   * @param string|array $tplStr
   * @param array|PartialSet $values
   * @param array|PartialSet $defaults
   */

  /**
   * @var string
   * The substituted/templated HTML
   */
  public $substituted = '';
  public function __construct($tplStr = '', $values = [], $defaults = []) {
    parent::__construct();
    if (is_array_assoc($tplStr)) {
      $values = keyVal('values',$tplStr,[]);
      $defaults = keyVal('defaults',$tplStr,[]);
      $tplStr = keyVal('tplStr',$tplStr,'');
    }
    if (!is_stringish($tplStr) || !is_arrayish($values) || !is_arrayish($defaults)) {
      throw new PKException(["Invalid constructor Arg:",$tplStr,$values,$defaults]);
    }
    $this->tplStr = $tplStr;
    $this->values = $values;
    $this->defaults = $defaults;
    $this->substituteAll();
  }

  public function substituteAll() {
    $this->substituted = $this->tplStr;
    $this->substituted = $this->substitute($this->values);
    $this->substituted = $this->substitute($this->defaults);
    //$this->substituted = $this->substitute();
    return $this->substituted;
  }
  public function substitute($arr = null) {
    if (is_arrayish($arr)) {
      foreach ($arr as $tkey => $tval) {
        $this->substituted = str_replace('{{'.$tkey.'}}', $tval, $this->substituted);
      }
    } else { #TODO: do preg_replace to remove tplStr keys without values
    }
    return $this->substituted;
  }
}
