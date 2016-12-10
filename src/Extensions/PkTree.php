<?php

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
  public static $dialogAtts = ['data-title', 'data-closetext', 'data-modal',
      'data-autoOpen', 'data-buttons', 'data-closeOnEscape',
      'data-dialogClass', 'data-minHeight', 'data-minWidth',
      'data-width', 'data-height', 'class',
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

  /////  !!!!  Deal with inputs later !!!!!!

  public $depth = 0;
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

  public function content($content = '', $raw = false) {
    if ($raw || ($content instanceOf PkHtmlRenderer)) {
      $this[] = $content;
    } else {
      $this[] = hpure($content);
    }
  }

  public function rawcontent($content = '') {
    return $this->content($content, true);
  }

  public function __toString() {
    return $this->unwind() . " ";
  }

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
    return in_array($this->pktag, static::$content_tags, 1);
  }

  public function renderOpener() {
    if (static::isTag($this->pktag)) {
      return "<{$this->pktag} " . PkHtml::attributes($this->attributes) . ">";
    }
  }

  public function renderCloser() {
    if ($this->isContentElement()) {
      return "</{$this->pktag}>\n";
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

  public function __call($method, $args) {
    $method = trim($method);
    $raw = false;
    if ($tag = removeStartStr($method, 'raw')) {
      $method = $tag;
      $raw = true;
    }

    $child = new static();
    $child->pktag = $method;
    $child->depth = 1 + $this->depth;
    $this[] = $child;
    #TODO: Do something with $raw
    array_unshift($args, $method);
    if (in_array($method, static::$selfclosing_tags)) {
      return call_user_func_array([$child, 'nocontent'], $args);
    } else if (in_array($method, static::$input_types)) {
      //$args = array_unshift($args,$method,$args);
      return call_user_func_array([$child, 'input'], $args);

      ## It's a content/dom element -create a child
    } else if (in_array($method, static::$content_tags)) {
      return call_user_func_array([$child, 'tagged'], $args);
    }
    throw new \Exception("Unknown Method: [$method]");
  }

}
