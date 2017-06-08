<?php
namespace PkExtensions;
use PkExtensions\Traits\UtilityMethodsTrait;
/**
 * A keyed Html template, that substitutes keyed values & defaults
 * Can construct once with $tplStr & $defaults, & re-use by resetting
 * $values.
 */
class PkHtmlTemplate extends PkHtmlRenderer {
  use UtilityMethodsTrait;
  /**
   * @var string 
   * An HTML Template string, with template keys as: {{key}} (for escaped/cleaned w. hpure)
   * or {!!$key!!} for unescaped, like form inputs, etc, like: 
   * "<div class='{{wrapClass}}'
   *   <div class='{{lblClass}}'{{lblVal}}</div>
   *   <div class='{{inpWrapClass}}'>{!!input!!}</div>
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
   * The 'default defaults - if not specified anywhere else 
   * @var type 
   */
  public $defaultdefaults = ['tootik'=>'',
      'data-tootik'=>'', 'extra-classs'=>'',
  ];
  #Provide several 'preset' templates, can be added to in subclasses
  public $presetKey;
  #Provide several 'preset' templates, can be added to in subclasses
  public  $presets =[
    'wrapinp' =>"
      <div class='{{wrapClass}}' data-tootik='{{tootik}}' {{wrapAtts}}>
        <div class='{{lblWrap}}'>{{label}}</div>
        <div class='{{inpWrap}}'>{!!input!!}</div>
      </div>\n",

    'wrapval' =>"
      <div class='{{wrapClass}} data-tootik='{{tootik}}' {{wrapAtts}}>
        <div class='{{lblWrap}}'>{{label}}</div>
        <div class='{{valWrap}}'>{{value}}</div>
      </div>\n",

      'checkSet' => "
        <div class='{{checkrow}}'>
          <div class='{{checkclass}}'>{{check}}</div>
          <div class='{{labelclass}}'>{{label}}</div>
        </div>\n",


      'js-ajax-button'=>"
        <div class='{{button-class}} {{extra-class}}' data-ajax-url='{!!data-ajax-url!!}'
data-ajax-params='{!!data-ajax-params!!}' data-tootik='{{data-tootik}}'>{{label}}</div>",


  ];

  #Provide defaults for 'preset' templates, can be added to or replaced in subclasses
  public $presetdefaults = [
      'wrapinp'=>['wrapAtts'=>'','lblWrap'=>'pk-lbl','inpWrap'=>'', 'wrapClass'=>''],
      'wrapval'=>['wrapAtts'=>'','lblWrap'=>'pk-lbl', 'valWrap'=>'pk-val', 'wrapClass'=>''],
      'js-ajax-button'=>["button-class"=>'site-button',
          'data-ajax-params'=>'', 'label'=>'Submit'],
      'checkSet' => ['checkrow'=>'check-row',
          'checkclass'=>'check-class inline', 'labelclass'=>'pk-lbl inline'],
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
    #Test building presets from this & ancestor presets
    $this->presets = $this->getInstanceAncestorArraysMerged('presets');
    $this->presetdefaults = $this->getInstanceAncestorArraysMerged('presetdefaults');
    $this->defaultdefaults = $this->getInstanceAncestorArraysMerged('defaultdefaults');
    //pkdebug("Preset:",$this->presets,'PD',$this->presetdefaults,'dd',$this->defaultdefaults);
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
    $this->template();
  }

  /** If you want to add / substitute some preset templates, without subclassing
   * @param array $presets
   * @param array $presetdefaults
   */
  public function addPresets(Array $presets = [], Array $presetdefaults = []) {
    if ($presetdefaults && is_array($presetdefaults)) {
      $this->presetdefaults = $presetdefaults + $this->presetdefaults;
    }
    if ($presets && is_array($presets)) {
      $this->presets = $presets + $this->presets;
    }
    return $this;
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
    $this->substituted = $tplStr.'';
    $this->substituted = $this->substitute($values);
    $this->substituted = $this->substitute($defaults);
    $this->substituted = $this->substitute($this->defaultdefaults);
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
    return $this;
  }

  public function substitute($arr = null) {
    if (is_arrayish($arr)) {
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

  /*
  public function __toString() {
    return $this->template();
  }
   * 
   */
}
