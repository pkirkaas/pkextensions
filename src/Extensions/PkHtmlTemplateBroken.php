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
   * 
   * @var type 
   */
  public $defaultdefaults = ['tootik'=>'',
  ];
  public $presetKey;
  public $presets =[
    'wrapinp' =>"
      <div class='{{wrapClass}}' data-tootik-conf='multiline' {{wrapAtts}}>
        <div class='{{lblWrap}}'>{{label}}</div>
        <div class='{{inpWrap}}'>{!!input!!}</div>
      </div>\n",

    'wrapval' =>"
      <div class='{{wrapClass}} data-tootik-conf='multiline' {{wrapAtts}}>
        <div class='{{lblWrap}}'>{{label}}</div>
        <div class='{{valWrap}}'>{{value}}</div>
      </div>\n",
      

      'js-ajax-button'=>"
        <div class='{{button-class}} {{extra-class}}' data-ajax-url='{!!data-ajax-url!!}'
        data-ajax-params='{!!data-ajax-params!!}'>{{label}}</div>",
  ];

  public $presetdefaults = [
      'wrapinp'=>['wrapAtts'=>'','lblWrap'=>'pk-lbl','inpWrap'=>''],
      'wrapval'=>['wrapAtts'=>'','lblWrap'=>'pk-lbl', 'valWrap'=>'pk-val'],

      /*
      'js-ajax-button'=>["button-class"=>'site-button', 'extra-class'=>'',
          'data-ajax-params'=>'', 'label'=>'Submit'],
       * 
       */
  ];

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
    if (!$values) $values = [];
    if (!$defaults) $defaults = [];
    if (!$tplStr) $tplStr = '';
    if (!is_stringish($tplStr) || !is_arrayish($values) || !is_arrayish($defaults)) {
      throw new PKException(["Invalid constructor Arg:",$tplStr,$values,$defaults]);
    }
    $this->tplStr = $tplStr;
    $this->values = $values;
    $this->defaults = $defaults;
    pkdebug("Uh, here?");
    //pkdebug("tplStr", $tplStr, "Vals", $values,"Defaults:", $defaults);
    return;
    //$this->template();
  }

  /** Execute/template the template. 
   * 
   * @param null|array $values - if null, uses $this->values,
   *   else if an assoc array, substitute. 
   *   BUT IF INDEXED ARRAY OF ASSOC ARRAYS, return a PartialSet with all the
   *   value arrays substituted 
   * of values to substitute
   * @param null|array $defaults - if null, uses $this->defaults, else an assoc array 
   * of defaults to substitute - so can temporarily substitute defaults for one
   * rendering, then return to the base defaults for the next templating
   * @param null|stringish $tplStr - if null, uses $this->defaults, else just
   *   in this templating action, temporarilly use the passed $tplStr,
   *   return to original next time.
   * @return string - the substituted/rendered template
   */
  public function tpl($values = null, $defaults=null, $tplStr = null) {
    return $this->template($values, $defaults, $tplStr);
  }
  public function template($values = null, $defaults=null, $tplStr = null) {
    if (!$values) $values = $this->values;
    pkdebug("Defaults:", $defaults);
    return;
    if (!$defaults || !is_array($defaults)) {
      $defaults = $this->defaults;
    } else if ($this->defaults && is_array($this->defaults)) {
      $defaults = $defaults + $this->defaults;
    }
    if (!$tplStr) $tplStr = $this->tplStr;
    if (is_arrayish_indexed($values) && is_arrayish_assoc($values[0])) {
      $ps = new PartialSet();
      foreach ($values as $valarr) {
        $ps[]=$this->template($valarr,$defaults,$tplStr);
      }
      return $ps;
    }
    pkdebug("Defaults:", $defaults);
    return;
    $this->substituted = $tplStr.'';
    $this->substituted = $this->substitute($values);
    $this->substituted = $this->substitute($defaults);
    /*
    if ($usepredefs) {
      $this->substituted = $this->substitute($this->presetdefaults);
    }
     * 
     */
    $this->substituted = $this->substitute($this->defaultdefaults);
    //$this->substituted = $this->substitute();
    //return new PkHtmlRenderer([$this->substituted]);
    return new PkHtmlRenderer([$this->substituted]);
  }


  public function preset($key = null, $defaults=[]) {
    if (!$key) { #Clear preset
      $this->presetkey = null;
    } else if (array_key_exists($key, $this->presets)) {
      $this->presetkey = $key;
      $this->tplStr = $this->presets[$key];
      if (!is_array($defaults)) {
        $defaults = [];
      }
      $presetdefaults = keyVal($key,$this->presetdefaults,[]);
      $this->defaults = $defaults + $presetdefaults;
    } else {
      throw new PkException("Preset key [$key] not found");
    }
    return $this->tplStr;
  }

  public function substitute($arr = null) {
    if (is_arrayish($arr)) {
      pkdebug("ARR:", $arr);
      return '';
      foreach ($arr as $tkey => $tval) {
        $this->substituted = str_replace('{{'.$tkey.'}}', hpure($tval), $this->substituted);
        $this->substituted = str_replace('{!!'.$tkey.'!!}', $tval, $this->substituted);
      }
    } else { #TODO: do preg_replace to remove tplStr keys without values
      #Might want to partially fill a template, & re-use it again with other params
      /**
      if ((strpos($this->substituted,'{{') !== false) ||
          (strpos($this->substituted,'{!!')!==false)) {
        throw new PkException("Missing param key for: [{$this->substitued}]");
      }
       * 
       */
    }
    return $this->substituted;
  }

  public function __toString() {
    return $this->template();
  }
}
