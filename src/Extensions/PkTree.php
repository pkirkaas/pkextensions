<?php
/**
 * Copyright (c) <Paul Kirkaas> - 2016 - Builds a DOM tree for rendering 
 * The MIT License (MIT)
 * Much of the custom input code is taken from the Laravel Collective
 * Html & Form Builders
 * Copyright (c) <Adam Engebretson>
 *
 */

namespace PkExtensions;

use PkHtml;
use PkExtensions\PartialSet;
use PkExtensions\HtmlRenderer;

class PkTree extends PartialSet {
  #These are called with $this->nocontent($tagname, $attributes)

  public static $selfclosing_tags = [
      'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input',
      'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr',];
  #These are by default called with $this->tagged($tagname,$value/content, $attributes)
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
  public static $input_types = [
      'checkbox', 'color', 'date', 'datetime', 'datetime-local', 'email', 'file', 'hidden', 'image', 'month',
      'number', 'password', 'radio', 'range', 'min', 'max', 'value', 'step', 'reset', 'search', 'submit',
      'tel', 'text', 'time', 'url', 'week',
  ];


  public static $skip_value_input_types = ['file', 'password',];
  public static $add_checked_input_types = ['checkbox', 'radio'];


  public static $dialogAtts = ['data-title', 'data-closetext', 'data-modal',
      'data-autoOpen', 'data-buttons', 'data-closeOnEscape',
      'data-dialogClass', 'data-minHeight', 'data-minWidth',
      'data-width', 'data-height', 'class',
  ];

  # Specially handled content tags that can't have children
  public static $no_child_tags = [
      'select', 'textarea',
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

  public static function isInputType($tag) {
    return in_array($tag, static::$input_types,1);
  }

  /////  !!!!  Deal with inputs later !!!!!!

  public $depth = 0;
  /*
  public $tagStack = [];

  public function addTagStack($tagarr) {
    $this->tagStack[] = $tagarr;
    return count($this->tagStack);
  }

  public function popTagStack() {
    $tagrr = array_pop($this->tagStack);
    if (!$tagrr) return;
    $tagparams = reset($tagrr);
    $tag = key($tagrr);
    return $tag;
  }
   * 
   */

  public $pktag;
  public $attributes;

  ## An element can only have one tag and one set of attributes

  //public function __construct($pktag = null, $theval = null, $attributes=[]) {
    /*
  public function __construct() {
    $this->attributes = $attributes;
    $this->pktag = $pktag;
    $this->content($theval);
  }
     */

  // Not sure if I should return anything from this or not? Try not, first
  public function content($content = '', $raw = false) {
    if ($raw || ($content instanceOf PartialSet)) {
      $this[] = $content;
    } else {
      $this[] = hpure($content);
    }
  }

  public function rawcontent($content = '') {
    return $this->content($content, true);
  }

  public function __toString() {
    return $this->unwind() . "";
  }

  ### Don't use PKDEBUG $THIS in here - INFINITE NESTING!
  public function unwind() {
    $ps = new PartialSet();
    $ps[] = $this->renderOpener();
    $this->getIterator()->rewind();
    foreach ($this as $key => $value) {
      if ($value instanceOf self) {
        $ps[] = "  ".$value->unwind();
      } else {
        $ps[] = "  ".$value;
      }
    }
    $ps[] = $this->renderCloser();
    return $ps;
  }


  public static function __callStatic($method, $args) {
    $rnd = new static();
    return call_user_func_array([$rnd,$method], $args);
  }
  /////  !!!!  Deal with inputs later !!!!!!
  //$this->makeOpenTag
  public function isContentElement() {
    //return in_array($this->pktag, static::$content_tags, 1);
    return $this->contentTag($this->getPkTag());
  }

  public function renderOpener() {
    if (static::isTag($this->getPkTag())) {
      return "<{$this->getPkTag()} " . PkHtml::attributes($this->attributes) . ">";
    }
  }

  public function renderCloser() {
    if ($this->isContentElement()) {
      return "</{$this->getPkTag()}>\n";
    }
  }

  /** Just return $this->tagged with $raw default to true rather than false
   * 
   * @param string $tag - the HTML tag - required
   * @param scalar|array|null $content
   * @param string|array|null $attributes
   * @param boolean $raw - default true
   * @return html string
   */
  public function rawtagged($tag, $content = null, $attributes = null, $raw = true) {
    return $this->tagged($tag, $content, $attributes, $raw);
  }

  /**
   * Generate HTML CONTENT element of type $tag
   * Change to allow $content to be assoc array with same params as args
   * EXPERIMENT: Try Using Raw Count to prevent filtering of input els.
   * @param string $tag - the HTML tag - required
   * @param scalar|array|null $content
   * @param string|array|null $attributes
   * @param boolean $raw - default true
   * @return html string
   */
  //I'm the child - just got created & got this handed to me...
  public function tagged($tag, $content = null, $attributes = null, $raw = false) {
    $this->setPkTag($tag);
    $gottag = $this->getPkTag();
    pkdebug("TAG: [$tag]; gottag: [$gottag]");
    $ctype = typeOf($content);
    $this->attributes = $this->cleanAttributes($attributes);
    if (is_array($content)) {
      foreach ($content as $citem) {
        $this->content($citem, $raw);
      }
    } else {
      $this->content($content, $raw);
    }
    return $this;
  }


  /**
   * Just makes attributes more flexible. If it's just a string, build an
   * attribute array of ['class' => $attributes]
   * if it's an indexed array, assume array of classes, implode, and do the same
   * NOTE: Overridden in subclasses to customize attributes (like SubForm)
   * @param array|string $attributes
   */
  public function cleanAttributes($attributes) {
    if (is_array_indexed($attributes)) {
      $attributes = implode (' ', $attributes);
    }
    if (is_string($attributes)) $attributes = ['class' => $attributes];
    return $attributes;
  }

  public function nocontent($tag, $attributes = null) {
    if (!$this->setPktag($tag)) {
      throw new \Exception("Illegal tag: [$tag]");
    }
    $this->attributes = $this->cleanAttributes($attributes);
    return $this;
  }

  public function input($type, $name, $value = null, $options = []) {
    if (!$this->isInputType($type)) {
      throw new \Exception ("Invalid Input Type: [$type]");
    }
    $options = $this->cleanAttributes($options);
    #If value exists in options, override arg
    //$options['value']=keyVal('value',$options,$value);
    $options['value']=$value;
    $options['type']=$type;
    #Set name in options
    //$options['name']=keyVal('name',$options,$name);
    $options['name']=$name;
    return $this->nocontent('input',$options);
  }


  public function spaceDepth() {
    //$size = count($this->tagStack);
    $out = '';
    for ($i = 0; $i < $this->depth; $i++)
      $out .= '  ';
    return $out;
  }

  /** 
   * Node Types:
   *   1 => Content Node
   *   2 => Unstructured Content - 
   *   3 => No Content Node 
   *   4 => Special No Content Node  (textarea/select
   *   (NOTE! For this purpose, we consider textarea & select to be NO CONTENT
   */
  public function getNodeType() {
    if (!($pktag = $this->getPkTag())) return 2;
    if ($this->selfClosingTag($pktag)) return 3;
    if (in_array($pktag,static::$no_child_tags,1)) return 4;
    if ($this->contentTag($pktag)) return 1;
    throw new \Exception ("Invalid Node Type: [$pktag]");
  }

  /** Can this node CONTAIN other nodes? */
  public function canHaveChildren() {
    return in_array($this->getNodeType(),[1,2],1);
  }

  public function __call($method, $args) {
    $method = trim($method);
    $raw = false;
    if ($tag = removeStartStr($method, 'raw')) {
      $method = $tag;
      $raw = true;
    }
    if(!$this->isTag($method) && !$this->isInputType($method)) {
      throw new \Exception ("Unhandled __call type: [$method]");
    }

    if (!$this->canHaveChildren()) {
      throw new \Exception ("Can't create a child node for this nodetype: [{$this->getPkTag()}]");
    }
    $child = new static();
    //$child->pktag = $method;
    /*
    if (!$child->setPktag($method)) {
      throw new \Exception("Illegal tag: [$method]");
    }
     * 
     */
    $child->depth = 1 + $this->depth;
    $this[] = $child;
    #TODO: Do something with $raw

    array_unshift($args, $method);
    //if (in_array($method, static::$content_tags)) {
    if ($this->contentTag($method)) {
      return call_user_func_array([$child, 'tagged'], $args);
    }


    if ($this->selfClosingTag($method)) {
      return call_user_func_array([$child, 'nocontent'], $args);
    } 

    if (in_array($method, static::$input_types)) {
      //$args = array_unshift($args,$method,$args);
      return call_user_func_array([$child, 'input'], $args);

      ## It's a content/dom element -create a child
    } 
    throw new \Exception("Unknown Method: [$method]");
  }

  public function setPkTag($tag) {
    $tag = trim($tag);
    if(!$this->isTag($tag) && !$this->isInputType($tag)) return false;
    if ($this->isInputType($tag)) $tag = 'input';
    return $this->pktag = $tag;
  }

  public function getPkTag() {
    if(!$this->isTag($this->pktag)) return false;
    return $this->pktag;
  }


  ############  Use Laravel Collective Form methods for inputs/selects/etc.

  /** Don't just default to 'tagged', cuz it's input
   * @param string $name - the ta 'name' attribute
   * @param string $value - the ta content - in between the open/close tags
   * @param string|array $options - attributes
   */
  /**
  public function textarea($name, $value = null, $options = []) {
    $options = $this->cleanAttributes($options);
    #If value exists in options, override arg
    $value=keyVal('value',$options,$value);
    #Set name in options
    $options['name']=keyVal('name',$options,$name);
    //$this[] = PkForm::textarea($name, $value, $options);
    return $this->tagged('textarea',$value,$options);
  }
   * 
   */

  public function textarea($name, $value = null, $options = []) {
    $options = $this->cleanAttributes($options);
    #If value exists in options, override arg
    $value=hpure(keyVal('value',$options,$value));
    $name = keyVal('name',$options,$name);
    unset($options['name']);
    unset($options['value']);
    #Set name in options
    return $this->rawcontent(PkForm::textarea($name, hpure($value), $options));
  }

  public function select($name, $list = [], $selected = null, $options = []) {
    $options = $this->cleanAttributes($options);
    #If selected exists in options, override arg
    $selected=hpure(keyVal('selected',$options,$selected));
    #Set name in options
    $name=keyVal('name',$options,$name);
    unset($options['name']);
    unset($options['selected']);
    //$this[] = PkForm::select($name, $list, $selected, $options);
    return $this->rawcontent( PkForm::select($name, $list, $selected, $options));
  }

  public function checkbox($name, $value = 1, $checked = null, $options = []) {
    $options = $this->cleanAttributes($options);
    $name=keyVal('name',$options,$name);
    $value=hpure(keyVal('value',$options,$value));
    unset($options['name']);
    unset($options['value']);
    return $this->rawcontent(PkForm::checkbox($name, hpure($value), $checked, $options));
  }
  public function radio($name, $value = null, $checked = null, $options = []) {
    $options = $this->cleanAttributes($options);
    $name=keyVal('name',$options,$name);
    $value=hpure(keyVal('value',$options,$value));
    unset($options['name']);
    unset($options['value']);
    return $this->rawcontent(PkForm::radio($name, hpure($value), $checked, $options));
  }

  public function boolean($name, $checked = null, $options = [], $unset = '0', $value = 1) {
    $options = $this->cleanAttributes($options);
    $name=keyVal('name',$options,$name);
    $value=hpure(keyVal('value',$options,$value));
    $unset=hpure(keyVal('unset',$options,$unset));
    unset($options['name']);
    unset($options['value']);
    unset($options['unset']);
    return $this->rawcontent(PkForm::boolean($name, $checked, $options, hpure($unset), hpure($value)));
  }


}
