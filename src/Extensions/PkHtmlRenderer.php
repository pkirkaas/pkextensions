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
    pkdebug("TAG: [$tag], atts:",$attributes);
    $this[] = "<$tag ". PkHtml::attributes($attributes).">\n";
    return $this;
  }

  public function RENDERCLOSE() {
    return $this->close();
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
  public function rows($data,$template,$cols=4, $rowclass='', $colclass = '') {
    if (!is_arrayish($data) ||!count($data)) return $this;
    $colsize = (int) (12/$cols);
    $this->div(RENDEROPEN, "row $rowclass");
    $i = 0;
    foreach ($data as $datum) {
      $i++;
      if (!($i % $cols)) {
        $this->RENDERCLOSE();
        $this->div(RENDEROPEN, "row $rowclass");
      }
      $this->div(RENDEROPEN, "col-sm-$colsize  $colclass");
        $this->rawcontent(PkController::staticRender($template,['datum'=>$datum]));
      $this->RENDERCLOSE();
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
