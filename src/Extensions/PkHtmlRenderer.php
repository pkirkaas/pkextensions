<?php
/** I Had great dreams for this once - but maybe later....
 * 
 */
namespace PkExtensions;
use PkHtml;
use PkForm;

if (!defined('RENDEROPEN')) define('RENDEROPEN', true);

/* Linked ? */
class PkHtmlRenderer extends PartialSet {
  public static $selfclosing_tags = [
    'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input',
    'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr', ];
  public static $content_tags = [
      'a', 'abbr', 'acronym', 'address', 'applet', 'article', 'aside',
      'audio', 'b', 'basefont', 'bdi', 'bdo', 'big', 'blockquote', 'body',
      'button', 'canvas', 'caption', 'center', 'cite', 'code', 'colgroup',
      'datalist', 'dd', 'del', 'details', 'dfn', 'dialog', 'dir', 'div',
      'dl', 'dt', 'em', 'fieldset', 'figcaption', 'figure', 'font',
      'footer', 'form', 'frame', 'frameset', 'h1', 'h2', 'h3', 'h4',
      'h5', 'h6', 'head', 'header', 'html', 'i', 'iframe', 'ins', 'kbd',
      'label', 'legend', 'li', 'main', 'map', 'mark', 'menu', 'menuitem',
      'meter', 'nav', 'noframes', 'noscript', 'object', 'ol', 'optgroup',
      'option', 'output', 'p', 'pre', 'progress', 'q', 'rp', 'rt', 'ruby',
      's', 'samp', 'script', 'section', 'select', 'small', 'span',
      'strike', 'strong', 'style', 'sub', 'summary', 'sup', 'table',
      'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'time', 'title',
      'tr', 'tt', 'u', 'ul', 'var', 'video',
    ];
  public static function contentTag($tag) {
    if (!$tag || !is_string($tag)) return false;
    $tag = strtolower($tag);
    return in_array($tag, static::$content_tags, true);
  }
  public static function selfClosingTag($tag) {
    if (!$tag || !is_string($tag)) return false;
    $tag = strtolower($tag);
    return in_array($tag, static::$selfclosing_tags, true);
  }
  public static function isTag($tag) {
    return static::selfClosingTag($tag) || static::contentTag($tag);
  }

  public $tagStack = [];
  public function addTagStack($tag) {
    $this->tagStack[] = $tag;
    return count($this->tagStack);
  }
  public function content($content='') {
    $this[] = hpure($content);
    return $this;
  }
  public function rawcontent($content='') {
    $this[] = $content;
    return $this;
  }

  /** Takes to values, puts them each in their own div, then wraps them both in another
   * 
   * @param type $value
   * @param type $label
   * @param type $valueClass
   * @param type $labelClass
   * @param type $wrapperClass
   */
  public function wrap($value='', $label='',$valueClass='', $labelClass='', $wrapperClass ='', $raw=null) {
    $this->div(RENDEROPEN,$wrapperClass);
    if ($raw === true) {
      $this->rawdiv($label, $labelClass);
      $this->rawdiv($value, $valueClass);
    } else {
      $this->div($label, $labelClass);
      $this->div($value, $valueClass);
    }
    $this->RENDERCLOSE();
    return $this;
  }
  public function rawwrap($value='', $label='',$valueClass='', $labelClass='', $wrapperClass ='') {
    return $this->wrap($value, $label,$valueClass, $labelClass, $wrapperClass, true);
  }

  public function rawtagged($tag, $content = null, $attributes=null, $raw = true) {
    return $this->tagged($tag, $content, $attributes, $raw);
  }
  public function tagged($tag, $content = null, $attributes=null, $raw = false) {
    $attributes = $this->cleanAttributes($attributes);
    if (!$content) $content = ' ';
    if ($content === true) {
      $spaces = $this->spaceDepth();
      $size = $this->addTagStack($tag);
      $this[]="$spaces<$tag ".PkHtml::attributes($attributes).">\n";
      return $this;
    } else {
      if (!$raw) $content = hpure($content);
      $this[]=$this->spaceDepth()."<$tag ".PkHtml::attributes($attributes).">
        $content</$tag>\n";
      return $this;
    }
  }

  public function nocontent($tag, $attributes=null) {
    $attributes = $this->cleanAttributes($attributes);
    //pkdebug("TAG: [$tag], atts:",$attributes);
    $this[] = "<$tag ". PkHtml::attributes($attributes).">\n";
    return $this;
  }

  public function RENDERCLOSE() {
    return $this->close();
  }

  /** Make it look cleaner by just using many of the PkForm shortcuts */
  public function submitButton($label = 'Submit', $options = []) {
      if (is_string($options)) $options = ['class'=>$options];
    $this[] = PkForm::submitButton($label,$options);
    return $this;
  }


  public function textareaset($name, $value = null, $labeltext = '', $inatts = [], $labatts = [], $wrapatts =[]) {
    $this[] = PkForm::textareaset($name, $value, $labeltext, $inatts, $labatts, $wrapatts);
    return $this;
  }


  public function selectset( $name='', $list=[], $selected = null, $labeltext=null, $inatts = [], $labatts=[], $wrapatts = []) {
     $this[] = PkForm::selectset( $name, $list, $selected, $labeltext, $inatts, $labatts, $wrapatts);
     return $this;
  }

  /** Builds a pair of controls:
   *  - a criteria chooser - (like, a select box with '>', '<', etc)
   *  - and a value holder - (typically text box)
   *  - with a label
   * 
   * @param array $params: There are default values for several settings - 
   *    if the input $param value is an array, it is added to the default value;
   *    if it is a string, it replaces the default value. 
   *    Example: defaultWrapClass = ' query-set-class ': 
   *     if $param['wrapClass'] === ' custom-set-class ', $setClass = ' custom-set-class'
   *     if $param['wrapClass'] === ['custom-set-class'], $setClass = 'query-set-class custom-set-class'
   * 
   *   @paramParam string 'label' - The label for the set
   *   @paramParam string 'wrapTag' - The wrapper type: default: 'fieldset'
   *   @paramParam string 'critVal' - Criteria value - default null
   *   @paramParam string 'valVal' - (comparison) Value value - default null
   *   @paramParam string|array 'wrapClass' - The css class for the set wrapper
   *   @paramParam string|array 'labelClass'  - The css class for the set label
   *   @paramParam string|array 'critClass'  - The css class for the critBox
   *   @paramParam string|array 'valClass'  - The css class for the valBox
   *   @paramParam assoc array 'critAtts'  - optional criteria control atts
   *   @paramParam assoc array 'valAtts'  - optional value control atts
   *   @paramParam string 'critType'  - Default: 'select'
   *   @paramParam string 'valType'  - Default: 'text'
   *   @paramParam string 'enabled'  - 'enabled', 'disabled', null : Default: null
   * 
   * #Field Names: - EITHER 'basename' is set, or 'critname' & 'valname' are set.
   *   @paramParam string 'basename': If set, creates
   *      the criteria field "$basename_crit'  
   *      the value field "$basename_val'  
   * OTHERWISE:
   *   @paramParam string 'critname': The name of the crit field
   *   @paramParam string 'valname': The name of the crit field
   * 
   *   @paramParam assoc array 'criteriaSet' : crit values => labels
   * 
   * @return \PkExtensions\PkHtmlRenderer - Representing the HTML for the Query Control
   */
  public function querySet($params = []) {
    $defaults = [
    'wrapTag' => 'fieldset', 
    'wrapClass' => ' form-group block search-crit-val-pair ',
    'labelClass' => '',
    'critClass' => ' form-control search-crit ',
    'valClass' => ' form-control search-val ',
    'valAtts' => [],
    'critAtts' => [],
    'valType' => 'text',
    'critType' => 'select',
    'label' => '',
    'valVal' => null,
    'critVal' => null,
    'enabled' => null,

    ];

    $appendableOpts = ['wrapClass', 'labelClass', 'critClass', 'valClass'];
    $tmpOpts = [];
    foreach ($appendableOpts as $apOpt) {
      $tmpOpts[$apOpt] = keyVal($apOpt, $params);
      if (ne_array($tmpOpts[$apOpt])) {
        $params[$apOpt] = $defaults[$apOpt] . ' '. explode(' ', $tmpOpts[$apOpt]);
      }
    }

    $params = array_merge($defaults, $params);
    $basename = keyVal('basename', $params);
    $params['critname'] = keyVal('critname', $params, $basename.'_crit');
    $params['valname'] = keyVal('valname', $params, $basename.'_val');
    $params['critAtts']['class'] = $params['critClass'];
    unset( $params['critClass']);
    $params['valAtts']['class'] = $params['valClass'];
    unset( $params['valClass']);

    $wrapTag = $params['wrapTag'];
    $critType = $params['critType'];
    $valType = $params['valType'];

    #Start building!
    $this->$wrapTag(RENDEROPEN, $params['wrapClass']);
      $this->label(RENDEROPEN, $params['labelClass']);
        //$this->div($params['label'], $params['labelClass']);
        $this->rawcontent($params['label']);
        $this->rawcontent(PkForm::select($params['critname'],$params['criteriaSet'], $params['critVal'], $params['critAtts']));
        $this->rawcontent(PkForm::text($params['valname'], $params['valVal'], $params['valAtts']));
      $this->RENDERCLOSE();
    $this->RENDERCLOSE();

    return $this;

  }
  public function textset( $name='', $value=null, $labeltext=null, $inputatts = [], $labelatts=[], $wrapatts = []) {
    $this[] = PkForm::textset( $name, $value, $labeltext, $inputatts, $labelatts, $wrapatts);
    return $this;
  }
  public function inputlabelset($type, $name='', $value=null, $labeltext=null, $inatts = [], $labatts=[], $wrapatts = []) {
     $this[] = PkForm::inputlabelset($type, $name, $value, $labeltext, $inatts, $labatts, $wrapatts);
     return $this;
  }

  public function multiselect($name, $list = [], $values=null, $options=[], $unset = null) {
      if (is_string($options)) $options = ['class'=>$options];
     $this[] = PkForm::multiselect($name, $list, $values, $options, $unset);
     return $this;
  }

  public function hidden($name, $value = null, $options = []) {
      if (is_string($options)) $options = ['class'=>$options];
      $this[] = PkForm::hidden($name, $value, $options);
    }
  public function text($name, $value = null, $options = []) {
      if (is_string($options)) $options = ['class'=>$options];
      $this[] = PkForm::text($name, $value, $options);
    }
	public function boolean($name,  $checked = null, $options = [], $unset = '0', $value = 1) {
      if (is_string($options)) $options = ['class'=>$options];
	   $this[]= PkForm::boolean($name,  $checked, $options, $unset, $value);
     return $this;
  }

  public function render($view,$data=[]) {
    if (!$view || !is_string($view)) return '';
    $relview = str_replace('.','/', $view);
    $viewroots = \Config::get('view.paths');
    $viewfile = null;
    foreach ($viewroots as $viewroot ) {
      $testpath = $viewroot.'/'.$relview.'.phtml';
      if (file_exists($testpath)) {
        $viewfile = $testpath;
        continue;
      }
    }
    if (!$viewfile) {
      pkdebug("ERROR: Couldn't find viewtemplate: [$view]");
      return $this;
    }

    if (is_array($data)) {
      ############# BE VERY CAREFUL ABOUT VARIABLE NAMES USED AFTER EXTRACT!!!
      ###########  $out, for example, was a terrible choice!
      extract($data);
    }
    ob_start();
    include ($viewfile);
    $___PKMVC_RENDERER_OUT = ob_get_contents();
    ob_end_clean();
    $this[] = $___PKMVC_RENDERER_OUT;
    return $this;
  }

  public function close() {
    $tag = array_pop($this->tagStack);
    $this[] = $this->spaceDepth()."</$tag>\n";
    return $this;
  }

  /**
   * For Bootstrap - when displaying a collection, make rows and cols
   * @param arrayish $data
   * @param string $template
   * @param type $cols
   * @param type $class
   */
  public function rows($data,$template,$cols=4, $rowclass='', $colclass = '', $itemclass='') {
    if (!is_arrayish($data) ||!count($data)) return $this;
    $colsize = (int) (12/$cols);
    //$this->div(RENDEROPEN, "row fsi-row-lg-level fsi-row-md-level $rowclass");
    $this->div(RENDEROPEN, "row $rowclass");
    $i = 0;
    foreach ($data as $datum) {
      $this->div(RENDEROPEN, "col-sm-$colsize  $colclass");
        $this->render($template,['datum'=>$datum, 'class'=>$itemclass]);
      $this->RENDERCLOSE();
      $i++;
      if (!($i % $cols)) {
        $this->RENDERCLOSE();
        //$this->div(RENDEROPEN, "row fsi-row-lg-level fsi-row-md-level $rowclass");
        $this->div(RENDEROPEN, "row $rowclass");
      }
    }
    $this->RENDERCLOSE();
    return $this;
  }

  /** Totally misconceived
  public function wrapToolTip($tooltip, $wrapperClasses =' ', $tooltipClasses = '') {
    $arrayIterator = $this->getIterator();
    $currentVal = $arrayIterator->current();
    $currentKey = $arrayIterator->key();
    $this->offsetUnset($currentKey);
    $this->div(RENDEROPEN, "tooltip-wrapper $wrapperClasses");
    $this->rawcontent($currentVal);
    $this->tooltip($tooltip,$tooltipClasses);
    $this->RENDERCLOSE();
    return $this;
  }
   * 
   */

  public function tooltip($tooltip, $extraclasses = ' ') {
    $this->rawdiv($tooltip, "pk-tooltip $extraclasses");
    return $this;
  }

  public function spaceDepth() {
    $size = count($this->tagStack);
    $out = '';
    for ($i = 0 ; $i < $size ; $i++) $out .= '  ';
    return $out;
  }

  public function __call($method, $args) {
    $raw = false;
    if ($tag = removeStartStr($method,'raw')) {
      $method = $tag;
      $raw = true;
    }
    array_unshift($args,$method);
    if (in_array($method, static::$selfclosing_tags)) {
      return call_user_func_array([$this,'nocontent'], $args);
    } else if (!$raw && in_array($method, static::$content_tags)) {
      return call_user_func_array([$this,'tagged'], $args);
    } else if ( $raw  && in_array($method, static::$content_tags)) {
      return call_user_func_array([$this,'rawtagged'], $args);
    }
    throw new \Exception("Unknown Method: [$method]");
  }


  /**
   * Just makes attributes more flexible. If it's just a string, build an
   * attribute array of ['class' => $attributes]
   * if it's an indexed array, assume array of classes, implode, and do the same
   * @param array|string $attributes
   */
  public function cleanAttributes($attributes) {
    if (is_array_indexed($attributes)) {
      $attributes = implode (' ', $attributes);
    }
    if (is_string($attributes)) $attributes = ['class' => $attributes];
    return $attributes;
  }

  public static function buildQuerySet($params = []) {
    $out=new static();
    $out->querySet($params);
    return $out;
  }

  /**
   * Takes a label and an input and wraps them
   * @param array $args - can't think of all the params I need now
   */
  /*
  public static function controlPair($control, $shorttext='', $longtext='', $args=[]) {
    $label = div($shorttext);
    

  }

  public $parent; #The owning parent of this type

  public function __construct($args = []) {
    if (!empty($args['parent']) && $args['parent'] instanceOf self) {
      $this->parent = $args['parent'];
    }
    unset ($args['parent']);
    parent::__construct($args);
  }

  public function up() {
    if ($this->parent instanceOf self) {
      return $this->parent;
    }
  }

  public function down() {
    $new = new static();
    $this[] = $new;
    return $new;
  }

  public function tag($tag, $content = '', $attributes = []){
    $value = PkHtml::tag($tag, $content, $attributes);
    $this[] = $value;
    return $this;
  }

  public function div($content = '', $attributes = []) {
    return $this->tag('div', $content, $attributes);
  }
   * 
   */
  

}
