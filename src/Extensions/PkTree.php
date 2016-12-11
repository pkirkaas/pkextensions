<?php

/**
 * Copyright (c) <Paul Kirkaas> - 2016 - Builds a DOM tree for rendering 
 * The MIT License (MIT)
 * Much of the custom input code is taken from the Laravel Collective
 * Html & Form Builders
 * Copyright (c) <Adam Engebretson>
 * The MIT License (MIT)
 *
 */

namespace PkExtensions;

use PkHtml;
use PkForm;

//use PkExtensions\PartialSet;
//use PkExtensions\PkHtmlRenderer;

class PkTree extends PartialSet {

  public $children;
  const TPLSTR = '__CNT_TPL__';

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
      'footer',
      //'form', //Try custom
      'frame', 'frameset', 'h1', 'h2', 'h3', 'h4',
      'h5', 'h6', 'head', 'header', 'html', 'i', 'iframe', 'ins', 'kbd',
      'label', 'legend', 'li', 'main', 'map', 'mark', 'menu', 'menuitem',
      'meter', 'nav', 'noframes', 'noscript', 'object', 'ol', 'optgroup',
      'option', 'output', 'p', 'pre', 'progress', 'q', 'rp', 'rt', 'ruby',
      's', 'samp', 'script', 'section', 'small', 'span',
      'strike', 'strong', 'style', 'sub', 'summary', 'sup', 'table',
      'tbody', 'td', 'tfoot', 'th', 'thead', 'time', 'title',
      'tr', 'tt', 'u', 'ul', 'var', 'video',
  ];

  /** Means handled by special "custom" methods - $this->customselect,
   * $this->customtextarea, etc. */
  public static $custom_tags = [
      'select', 'textarea', 'radio', 'checkbox', 'boolean', 'submitButton', 'form',
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
      'select', 'textarea', 'input', 'boolean',
  ];
  public function __construct() {
    $this->children = new PartialSet();
  }

  /** Go up the parent tree -- n $lvls, or if null, all the way
   * 
   * @param int|null $lvl - how many levels/ancestors to go up?
   * @return static - a parent element
   */
  public function up($lvl = null) {
    $tst = $this;
    if ($lvl === null) {
      while ($tst->parent) {
        $tst = $tst->parent;
      }
      return $tst; #Should be the root
    }
    if (!is_int($lvl)) throw new \Exception("Invalid lvl: " . print_r($lvl, 1));
    while ($lvl && $tst->parent) {
      $tst = $tst->parent;
      $lvl--;
    }
    return $tst;
  }

  public static function contentTag($tag) {
    if (!$tag || !is_string($tag)) return false;
    //$tag = strtolower($tag);
    return in_array($tag, static::$content_tags, true);
  }

  public static function customTag($tag) {
    if (!$tag || !is_string($tag)) return false;
    //$tag = strtolower($tag);
    return in_array($tag, static::$custom_tags, true);
  }

  public static function selfClosingTag($tag) {
    if (!$tag || !is_string($tag)) return false;
    //$tag = strtolower($tag);
    return in_array($tag, static::$selfclosing_tags, true);
  }

  public static function isTag($tag) {
    return static::selfClosingTag($tag) || static::contentTag($tag) || ($tag === 'form');
  }

  public static function isInputType($tag) {
    return in_array($tag, static::$input_types, 1);
  }

  public $depth = 0;
  public $parent;
  public $pktag;
  public $attributes;

  /** Creates a child & initializes it with relation to $this,
   * fbut without content
   * @return self - empty child
   */
  public function spawn() {
    $newel = new self();
    $newel->parent = $this;
    $newel->depth = $this->depth + 1;
    //$this->children[] = $newel;
    $this[] = $newel;
    return $newel;
  }

  ## An element can only have one tag and one set of attributes
  // Should I return "this" or $content?  Try $content
  public function content($content = '', $raw = false) {
    if ($content === null) {
      $this[] = null;
      return $this;
    }
    if (is_array($content)) {
      foreach ($content as $item) {
        $this->content($item);
      }
      return $this;
    }
    if (($content instanceOf self) || ($content instanceOf PkHtmlRenderer)
        || ($content instanceOf \Illuminate\Support\HtmlString )) {
      $this[] = $content;
      return $this;
    } 
    if (is_string($content)) {
      if (!$raw) {
        $content = hpure($content);
      }
      $this[] = $content;
      return $this;
    } else {
      $type = typeOf($content);
      throw new \Exception("Unhandled Content Type: [$type], val: " . print_r($content, 1));
    }
    return $this;
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
        $ps[] = "  " . $value->unwind();
      } else {
        $ps[] = "  " . $value;
      }
    }
    $ps[] = $this->renderCloser();
    return $ps;
  }

  /*
    public static function __callStatic($method, $args) {
    throw new \Exception("Cant do that");
    $rnd = new self();
    call_user_func_array([$rnd,$method], $args);
    //return $rnd;
    }
   * 
   */

  /////  !!!!  Deal with inputs later !!!!!!
  //$this->makeOpenTag
  public function isContentElement() {
    //return in_array($this->pktag, static::$content_tags, 1);
    $tag = $this->getPkTag();
    return $this->contentTag($tag) || ($tag == 'form');
  }

  public function renderOpener() {
    $tag = $this->getPkTag();
    if (static::isTag($tag) && ($tag !== 'form')) {
      return "<$tag " . PkHtml::attributes($this->attributes) . ">";
    }
    if ($tag === 'form') {
      return $this->getSpecialOpen();
    }
  }

  #So far, just for the special 'form' element from FormBuilder

  public $specialOpen;

  public function getSpecialOpen() {
    return $this->specialOpen;
  }

  public function setSpecialOpen($specialOpen) {
    $this->specialOpen = $specialOpen;
  }

  public function renderCloser() {
    $tag = $this->getPkTag();
    if ($tag === 'form') {
      return PkForm::close();
    }
    if ($this->isContentElement()) {
      return "</$tag>\n";
    }
  }

  /** If self == true, return true for self */
  public function isAncestor($pkt, $self=1) {
    if (!($pkt instanceOf self)) return false;
    if ($self && ($pkt === $this)) return true;
    if (!$this->parent) return false;
    $tmp = $this->parent;
    while ($tmp) {
      if ($tmp === $pkt) return true;
      $tmp = $tmp->parent;
    }
    return false;
  }
  public function isDescendant($pkt, $self=1) {
    if (!($pkt instanceOf self)) return false;
    return $pkt->isAncestor($this, $self);
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
    $this->attributes = $this->cleanAttributes($attributes);
    $this->content($content, $raw);
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
      $attributes = implode(' ', $attributes);
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

  public function spaceDepth() {
    //$size = count($this->tagStack);
    $out = '';
    for ($i = 0; $i < $this->depth; $i++)
      $out .= '  ';
    return $out;
  }

  /**
   * Node Types: - < 10 Can have children; > 10 can't
   *   1 => Content Node Can have children
   *   2 => Unstructured Content - Can have children 
   *   3 => Special - form! Can have children
   *   11 => Self-Closing tag - No Children
   *   12 => No Content Node  - No Children
   *   13  => Special No Content Node  (textarea/select, etc) - No Children
   *   (NOTE! For this purpose, we consider textarea & select to be NO CONTENT
   */
  public function getNodeType() {
    $pktag = $this->getPkTag();
    if ($this->contentTag($pktag)) return 1;#Regular content tag/element
    if (!$pktag) return 2;#unstructured / non-node content
    if ($pktag === 'form') return 3;
    if ($this->selfClosingTag($pktag)) return 11;
    if (in_array($pktag, static::$no_child_tags, 1)) return 12;
    if ($this->customTag($pktag)) return 13;
    throw new \Exception("Invalid Node Type: [$pktag]");
  }

  /** Can this node CONTAIN other nodes? */
  public function canHaveChildren() {
    return $this->getNodeType() < 10;
  }

  /** does __call handle this method? */
  public function isHandled($method) {
    return $this->isTag($method) ||
        $this->isInputType($method) ||
        $this->customTag($method);
  }

  public function fresh($content=null) {
    $ret = new static();
    if ($content) $ret->content($content);
    return $ret->up();
  }

  public function __call($method, $args) {
    $method = trim($method);
    $raw = false;
    if ($tag = removeStartStr($method, 'raw')) {
      $method = $tag;
      $raw = true;
    }
    if (!$this->isHandled($method)) {
      throw new \Exception("Unhandled __call type: [$method]");
    }
    if (!$this->canHaveChildren()) {
      throw new \Exception("Can't create a child node for this nodetype: [{$this->getPkTag()}]");
    }
    $child = $this->spawn();
    if ($this->customTag($method)) {
      $custommethod = 'custom' . $method;
      return call_user_func_array([$child, $custommethod], $args);
    }

    array_unshift($args, $method);
    if ($this->contentTag($method)) {
      return call_user_func_array([$child, 'tagged'], $args);
    }


    if ($this->selfClosingTag($method)) {
      return call_user_func_array([$child, 'nocontent'], $args);
    }

    if (in_array($method, static::$input_types)) {
      return call_user_func_array([$child, 'custominput'], $args);

      ## It's a content/dom element -create a child
    }
    throw new \Exception("Unknown Method: [$method]");
  }

  public function setPkTag($tag) {
    $tag = trim($tag);
    if ($tag && !$this->isTag($tag) && !$this->isInputType($tag)) {
      throw new \Exception("Setting Illegal Tag: [$tag]");
    }
    if ($this->isInputType($tag)) $tag = 'input';
    return $this->pktag = $tag;
  }

  public function getPkTag() {
    $tag = $this->pktag;
    if ($tag && !$this->isTag($tag)) {
      throw new \Exception("Getting Illegal Tag: [$tag]");
    }
    return $tag;
  }

  ############  Use Laravel Collective Form methods for inputs/selects/etc.

  public function custominput($type, $name, $value = null, $options = []) {
    if (!$this->isInputType($type)) {
      throw new \Exception("Invalid Input Type: [$type]");
    }
    $options = $this->cleanAttributes($options);
    return $this->rawcontent(PkForm::input($type, $name, $value, $options));
  }

  public function customtextarea($name, $value = null, $options = []) {
    $options = $this->cleanAttributes($options);
    return $this->rawcontent(PkForm::textarea($name, $value, $options));
  }

  public function customselect($name, $list = [], $selected = null, $options = []) {
    $options = $this->cleanAttributes($options);
    return $this->rawcontent(PkForm::select($name, $list, $selected, $options));
  }

  public function customcheckbox($name, $value = 1, $checked = null, $options = []) {
    $options = $this->cleanAttributes($options);
    return $this->rawcontent(PkForm::checkbox($name, hpure($value), $checked, $options));
  }

  public function customradio($name, $value = null, $checked = null, $options = []) {
    $options = $this->cleanAttributes($options);
    return $this->rawcontent(PkForm::radio($name, hpure($value), $checked, $options));
  }

  public function customboolean($name, $checked = null, $options = [], $unset = '0', $value = 1) {
    $options = $this->cleanAttributes($options);
    return $this->rawcontent(PkForm::boolean($name, $checked, $options, hpure($unset), hpure($value)));
  }

  public function customsubmitButton($label = 'Submit', $options = []) {
    $options = $this->cleanAttributes($options);
    return $this->rawcontent(PkForm::submitButton($label, $options));
  }

  /** !!!!!!!!  TO WORK WITH MODEL BINDING - MUST CALL CORRECTLY SO FORM IS BOUND
   *  to Model before inputs are evaluated --
   * WRONG:  
    $frm->form([
    $frm::div([
    $frm::pair($frm::text('date',null,['class' =>'datepicker auto-attach pk-inp']), 'Date', 'col-sm-4'),
    $frm::pair($frm::select('interaction_id',InteractionRef::getSelectList(), null,'pk-inp'),'Type of Interaction?','col-sm-4'),
    $frm::pair( $frm::text('summary',null,['class' =>' pk-inp']),'Summary','col-sm-4'),
    ],'row'), $frm::submitButton(),
    ], ['model'=>$clientlog]);
   * 
   * RIGHT:
    $frm->form(null,['model'=>$clientlog])->div([
    $frm::pair($frm::text('date',null,['class' =>'datepicker auto-attach pk-inp']), 'Date', 'col-sm-4'),
    $frm::pair($frm::select('interaction_id',InteractionRef::getSelectList(), null,'pk-inp'),'Type of Interaction?','col-sm-4'),
    $frm::pair( $frm::text('summary',null,['class' =>' pk-inp']),'Summary','col-sm-4'),
    ],'row')->submitButton();
   * THAT WAS THE WHOLE POINT OF THE NEW PKTREE RENDERER!
   * @param type $content
   * @param array $options
   * @return \PkExtensions\PkTree
   */
  public function customform($content = null, array $options = []) {
    if (array_key_exists('model', $options)) {
      PkForm::setModel($options['model']);
      unset($options['model']);
    }
    $this->setSpecialOpen(PkForm::open($options));
    $this->setPkTag('form');
    if (is_array($content)) {
      foreach ($content as $citem) {
        $this->content($citem);
      }
    } else {
      $this->content($content);
    }
    return $this;
  }

  /** Merges/injects all the contents of $this into the new target */
  public function merge(PkTree $mrg) {
    $mrg->content($this);
    return $mrg;
  }

}
