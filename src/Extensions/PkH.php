<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
namespace PkExtensions;
use PkHtml;
use PkForm;

/**
 * JUST render HTML, nothing fancy, always creates a child & returns it
 * @author pkirk
 */
class PkH extends PartialSet {
  #These are called with $this->nocontent($tagname, $attributes)
  public static $cnt=0;
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

  public static function getWrappableInputs() {
    $additionalWrapIns = ['textarea', 'select', 'boolean'];
    return array_merge(static::$input_types, $additionalWrapIns);
  }

  public static $input_types = [
      'checkbox', 'color', 'date', 'datetime', 'datetime-local', 'email', 'file', 'hidden',
      'image', 'month', 'number', 'password', 'radio', 'range', 'min', 'max', 'value',
      'step', 'reset', 'search', 'submit', 'tel', 'text', 'time', 'url', 'week',
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

  ##############  Start testing nested 5/2017 

  public $myid = 0;
  public $mykids = null;
  public static $total = 0;
  public static $alltostrings = 0;
  public static $totalops = 0;
  public $mytostrings = 0;
  public $parent = null;
  public $depth = 0;
  public $myops = 0;

  public function returnChild($child) {
    static::$total--;
    if (!static::$total) {
    return $child;
    }
      $this[]=$child;
  }

  public function makeChild($content = null) {
    if ($content) {
      $child = new static($content);
    } else {
      $child = new static();
    }
    static::$total++;
    $child->myid = static::$total;
    if (!$this->mykids instanceOf PartialSet) {
      $this->mykids = new PartialSet();
    }
    $this->mykids[] = $child;
    $child->parent = $this;
    $child->depth = $this->depth + 1;
    return $child;
  }

  ##############  End testing nested 5/2017 

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

  public function content($content = '', $raw = false) {

    if (is_array($content)) {
      $child = $this->makeChild();
      foreach ($content as $element) {
        $child->content($element, $raw);
      }
      return $this->returnChild($child);
    }
    if (!$raw && !($content instanceOf self) && !($content instanceOf PkTree)) {
      $content = hpure($content);
    }
    $child = $this->makeChild([$content]);
    return $this->returnChild($child);
  }

  public function rawcontent($content = '') {
    return $this->content($content, true);
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
  //NOTE: I inserted a newline after every open tag for readability, but that's 
  // just wrong. 
  ## SOME SPECIAL EXPERIMENTAL STUFF STARTING HERE with nesting - 5/17
  /** For years, I used the convention if $content === true (RENDEROPEN),
   * used that as an flag to  Open the content element tag, and not close it until
   *  be closed later by an explicit $this->RENDERCLOSE();, which also added the
   * matching closing tag. Now considering if $content === NULL or FALSE. Normally
   * a content tag call adds the content to the next cell in the array, with the
   * tag/attribute stack, then retuns {$this} to continiue the un-nested linear chain/array.
   * 
   * What if, if $content === null or false, this creates a new static instance, stores
   * it as usual in the next array position, BUT RETURNS THE NEW CHILD!. Then you could
   * chain method calls, and they would apply to the child, so nested/embedded
   *  within the the parent. You could go as deep as you want; the first terminating
   * ';' would end it. I can see that allowing both deeply nested elements AND
   * sequential elements.  But how you would go back a few steps down & then
   * continue from that base without rewinding all the way or using clumsy
   * temporary variables, I'm not clear on.
   * 
   * I suppose there could be a method that accepts a number and returns it's 
   * parent n generations back...
   * 

   * the 
   * @param type $tag
   * @param type $content
   * @param type $attributes
   * @param type $raw
   * @return $this
   */
  ######!!!! TODO!!! See PkRendEx.php for testing of nested children
  public function tagged($tag, $content = null, $attributes = null, $raw = false) {
    static::$cnt++;
    $child = $this->makeChild();
    $attributes = $this->cleanAttributes($attributes);
    $child->rawcontent("<$tag " . PkHtml::attributes($attributes) . ">");

      #############  ORIG ##############
      if (is_array($content)) {
        foreach ($content as $citem) {
          $child->content($citem, $raw);
        }
      } else {
        $child->content($content, $raw);
      }
      $child->rawcontent( "</$tag>\n");
      return $this->returnChild($child);
    }

  /** This called for no-content element tags, like img, br, hr, input, etc */
  public function nocontent($tag, $attributes = null) {
    $child = $this->makeChild();
    $attributes = $this->cleanAttributes($attributes);
    //pkdebug("TAG: [$tag], atts:",$attributes);
    //$this[] = "<$tag ". PkHtml::attributes($attributes).">\n";
    $child->rawcontent("<$tag " . PkHtml::attributes($attributes) . ">\n");
    return $this->returnChile($child);
  }

  public static function __callStatic($method, $args) {
    $rnd = new static();
    return call_user_func_array([$rnd, $method], $args);
  }

  public function __call($method, $args) {
    $method = trim($method);
    $raw = false;
    if ($tag = removeStartStr($method, 'raw')) {
      $method = $tag;
      $raw = true;
    }

    #TODO: Do something with $raw
    array_unshift($args, $method);
    if (in_array($method, static::$selfclosing_tags)) {
      return call_user_func_array([$this, 'nocontent'], $args);
    } else if (in_array($method, static::$content_tags)) {
      if ($raw) {
        return call_user_func_array([$this, 'rawtagged'], $args);
      } else {
        return call_user_func_array([$this, 'tagged'], $args);
      }
    } else if (in_array($method, static::$input_types)) {
      //$args = array_unshift($args,$method,$args);
      return call_user_func_array([$this, 'input'], $args);
    }
    throw new \Exception("Unknown Method: [$method]");
  }

  /**
   * Just makes attributes more flexible. If it's just a string, build an
   * attribute array of ['class' => $attributes]
   * if it's an indexed array, assume array of classes, implode, and do the same
   * NOTE: Overridden in subclasses to customize attributes (like SubForm)
   * @param array|string $attributes
   * @param array|string $defaults, if equivalent key not set in attributes.
   *   Again, $defaults can be string, then converted to $defaults=['class'=>$defaults]
   */
  public function cleanAttributes($attributes, $defaults = null) {
    if (is_array_indexed($attributes)) {
      $attributes = implode(' ', $attributes);
    }
    if (is_string($attributes)) $attributes = ['class' => $attributes];
    if (!is_array_assoc($attributes)) $attributes = [];
    if (!$defaults) {
      return $attributes;
    }
    $defaults = $this->cleanAttributes($defaults);
    return array_merge($defaults, $attributes);
  }

}
