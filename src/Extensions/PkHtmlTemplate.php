<?php
namespace PkExtensions;
/**
 * A keyed Html template, that substitutes keyed values & defaults
 * Can construct once with $tplStr & $defaults, & re-use by resetting
 * $values.
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
   * ['input'=>PkForm::text('zip'), 'lblVal'=>'ZIP']
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
   * below. The keys of $values & $defaults should be scalar, but the values can
   * be stringable - eg, PartialSets or PkHtmlRenderer instances
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
    $this->template();
  }

  /** Execute/template the template. 
   * 
   * @param null|array $values - if null, uses $this->values, else an assoc array 
   * of values to substitute
   * @param null|array $defaults - if null, uses $this->defaults, else an assoc array 
   * of defaults to substitute - so can temporarily substitute defaults for one
   * rendering, then return to the base defaults for the next templating
   * @param null|stringish $tplStr - if null, uses $this->defaults, else just
   *   in this templating action, temporarilly use the passed $tplStr,
   *   return to original next time.
   * @return string - the substituted/rendered template
   */
  public function template($values = null, $defaults=null, $tplStr = null) {
    if (!$values) $values = $this->values;
    if (!$defaults) $defaults = $this->defaults;
    if (!$tplStr) $tplStr = $this->tplStr;
    $this->substituted = $tplStr;
    $this->substituted = $this->substitute($values);
    $this->substituted = $this->substitute($defaults);
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
