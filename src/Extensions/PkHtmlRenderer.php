<?php
/** Renders HTML - can be included in Blade templates, but also implements a
 * custom templating/HTML rendering system.
 * Accessable as the PkRenderer facade
 * 
 */
namespace PkExtensions;
use PkHtml;
use PkForm;
use PkExtensions\Models\PkModel;
use PkRenderer;

if (!defined('RENDEROPEN')) define('RENDEROPEN', true);

/** We allow it to know about the possibility of the extended class
 * PkMultiSubformRenderer
 */

/** To wrap a pair of elements inside a wrapping el, and put that inside 
 *  a bs4-column, AND have the label only one
 * line and the value content stretch to most of the column; something like:
 * 
$descPair = PkRenderer::div([
    PkRenderer::div('About Me...', 'pk-lbl bg-ffa no-grow'),
    PkRenderer::div($bouncer->desc,'section bg-eee flex-grow')],
    'section bg-ccc flex-col height-90');
 */
class PkHtmlRenderer extends PartialSet {
  ##############  Start testing nested 5/2017 
  public $myid = 0;
  public $mykids = null;
  public static $total = 0;
  public static $alltostrings=0;
  public static $totalops = 0;
  public $mytostrings = 0;
  public $parent = null;
  public $depth = 0;
  public $myops = 0;

  public function makeChild() {
    $child = new static();
    static::$total++;
    $child->myid = static::$total;
    if (! $this->mykids instanceOf PartialSet ) {
        $this->mykids =  new PartialSet();
    }
    $this->mykids[] = $child;
    $child->parent = $this;
    $child->depth = $this->depth + 1;

    /*
    $this->announce($this->myid." spawned:".
        count($this->mykids));
     * 
     */
     return $child;
    }
  ##############  End testing nested 5/2017 

  public static $raw_depth = 0; #See if we can use this to keep track of raw depth....
  public static $raw_threshold = 0; #See if we can use this to keep track of raw depth....
  #These are called with $this->nocontent($tagname, $attributes)
  public static $selfclosing_tags = [
    'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input',
    'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr', ];
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
    $additionalWrapIns = ['textarea', 'select','boolean'];
    return array_merge(static::$input_types,$additionalWrapIns);
  }



  public static $input_types = [
    'checkbox', 'color', 'date', 'datetime', 'datetime-local', 'email', 'file', 'hidden', 'image', 'month', 
    'number', 'password', 'radio', 'range', 'min', 'max', 'value', 'step', 'reset', 'search', 'submit', 
    'tel', 'text', 'time', 'url', 'week', 
    ];
 public static  $dialogAtts = ['data-title','data-closetext', 'data-modal',
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
    /*
    if (keyVal('raw',$tagparams)) {
      static::decRawCount();
    }
     * 
     */
    return $tag;
  }
  public function content($content='', $raw = false) {
    //if(static::getRawCount() || ($content instanceOf PkHtmlRenderer)) {
    if (is_array($content)) {
      foreach ($content as $element) {
        $this->content($element, $raw);
      }
      return $this;
    }
    if(!$raw && !($content instanceOf self) && !($content instanceOf PkTree)) {
      $content = hpure($content);
    }
    $this[] = new static([$content]);
    return $this;
  }

  
  public function rawcontent($content='') {
    //$this[] = $content;
    return $this->content($content,true);
  }

  /** Takes two values, puts them each in their own div, then wraps them both in another
   * TWO FORMS! $value can be an associative array! (Like wrapattr below)!
   * @param type $value
   * @param type $label
   * @param type $valueClass
   * @param type $labelClass
   * @param type $wrapperClass
   */
  public function wrap($value='', $label='',$valueClass='', $labelClass='', $wrapperClass ='', $raw=null) {
    if (!is_stringish($value) && is_arrayish($value)) {
      $opts = $value;
      $defaultOpts = [
        'value' => '',
        'label' => '',
        'raw' => false,
        'wrapperTag'=>'div',
        'labelTag' => 'div',
        'valueTag' => 'div',
        'valueAttributes' => 'pk-value',
        'labelAttributes' => 'pk-label',
        'wrapperAttributes' => 'pk-wrapper',
      ];
      $opts = array_merge($defaultOpts, $opts);
      extract($opts);
      if ($raw) {
        $valueTag = 'raw'.$valueTag;
        $labelTag = 'raw'.$labelTag;
      }
      $this->$wrapperTag(RENDEROPEN,$wrapperAttributes);
        if ($label) $this->$labelTag($label, $labelAttributes);
        $this->$valueTag($value, $valueAttributes);
      $this->RENDERCLOSE();
      return $this;
    }
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



  /** Re-implement wrap above - wrap two elements in a third. The first argument
   * (value) will be displayed second; the second argument (label) first, both
   * wrapped in the wrap tag(default: div) with the wrapatts.
   * @param stringish|array $value - the SECOND element shown - typically beneath
   * Can be simple "stringish", or an array of stringish value and attributes,
   * of the element,  with optional element tag (default: div)
   * Ex: "Today" OR ['Today','col'], OR ['Today', ['class'=>'col val', data-xx=>'yy', 'tag'=>'h1']]
   * @param stringish|array $label - as above
   * @param string|array $wrapatts - the wrap attributes and tag (default: div)
   *   If just a string, the wrap div class
   */
  public function wrap2($value=null, $label=null,$wrapatts=null, $raw=false) {
    #Normalize Wrapatts
    $wrapatts = arrayifyArg($wrapatts, 'class', ['tag'=>'div', 'class'=>'pk-wrap']);
    #Normalize Value
    $valArr = arrayifyArg($value, 'value', ['tag'=>'div', 'class'=>'pk-val']);
    #Normalize Label
    $lblArr = arrayifyArg($label, 'value', ['tag'=>'div', 'class'=>'pk-lbl']);

    $raw = keyVal('raw', $wrapatts,$raw);
    $rawprefix = '';
    if ($raw) $rawprefix = 'raw';
    $lblval = $lblArr['value'];
    $lbltag = $lblArr['tag'];
    $valval = $valArr['value'];
    $valtag = $valArr['tag'];
    unset ($valArr['value']);
    unset ($valArr['tag']);
    unset ($lblArr['value']);
    unset ($lblArr['tag']);
    unset($wrapatts['tag']);
    $wraptag = $rawprefix. keyVal('tag',$wrapatts,'div');
    $this->$wraptag(RENDEROPEN,$wrapatts);
    $this->$lbltag($lblval,$lblArr);
    $this->$valtag($valval,$valArr);
    $this->RENDERCLOSE();
    return $this;
  }
  public function wrap3($value=null, $label=null,$allatts=null) {
    $wrapatts = keyVal('wrapatts',$allatts);
    $valatts = keyVal('valatts',$allatts);
    $lblatts = keyVal('lblatts',$allatts);
    $raw = keyVal('raw',$allatts);
    #Normalize Wrapatts
    $wrapatts = arrayifyArg($wrapatts, 'class', ['tag'=>'div', 'class'=>'pk-wrap']);
    #Normalize Value
    $valArr = arrayifyArg($value, 'value', ['tag'=>'div', 'class'=>'pk-val']);
    #Normalize Label
    $lblArr = arrayifyArg($label, 'value', ['tag'=>'div', 'class'=>'pk-lbl']);

    $raw = keyVal('raw', $wrapatts,$raw);
    $rawprefix = '';
    if ($raw) $rawprefix = 'raw';
    $lblval = $lblArr['value'];
    $lbltag = $lblArr['tag'];
    $valval = $valArr['value'];
    $valtag = $valArr['tag'];
    unset ($valArr['value']);
    unset ($valArr['tag']);
    unset ($lblArr['value']);
    unset ($lblArr['tag']);
    unset($wrapatts['tag']);
    $wraptag = $rawprefix. keyVal('tag',$wrapatts,'div');
    $this->$wraptag(RENDEROPEN,$wrapatts);
    $this->$lbltag($lblval,$lblArr);
    $this->$valtag($valval,$valArr);
    $this->RENDERCLOSE();
    return $this;
  }

  /**
   *The Templating Injection! Very powerful & flexible, with reusable
   * htmlRenderer mini-templates. Can even have default values. Example:
   * 
   * 
   * #Template:
      $wrap_tpl = new PkHtmlRenderer();
      $wrap_tpl[] = "<div  class='";
      $wrap_tpl['wrapper_class'] = 'col-sm-3 tpm-wrapper'; #Default
      $wrap_tpl[] = "'>\n<div  class='block tpm-label'>\n";
      $wrap_tpl['label']=null|true; #Replace - if null, hpure, if true, raw
      $wrap_tpl[] = "</div>\n<div  class='block tpm-value'>\n";
      $wrap_tpl['input'] = null|true; #Replace - if null, hpure, if true, raw
      $wrap_tpl[] = "</div>\n</div>\n";
   * 
   * #Call:
        PkRenderer::inject([
            'label'=>"First Name",
            'input'=>PkForm::text('fname', null, ['placeholder' => 'First Name','class'=>'pk-inp']),
        ],$wrap_tpl,['input']),

   *  
   * @param assoc $arr: The key/values to insert in the template - OR, if not
   *   an array, a "stringish" value (could be a PartialSet) inserted into
   *   $tpl at default "content":  <tt>$tpl['content']=$arr;</tt>
   * @param PartialSet|Array|null: $tpl: A PartialSet/Renderer with indices matching
   * the value keys of the input array
   * @param array $rawkeys - alternate method for specifying a "raw" input
   * 
   * @return PkHtmlRenderer - with data inserted in the template
   */
  public function inject($arr,$tpl=null,$rawkeys=[]) {
    //pkdebug("ARR:",$arr,"TPL", $tpl);
    if (!is_array($arr) && is_stringish($arr)) {
      $arr = ['content' => $arr];
    }
    if ($tpl instanceOf PartialSet) {
      $tpl = $tpl->copy();
    } else if (is_array($tpl)) {
      $tpl = new PartialSet($tpl);
    } else {
      $tpl = new PkHtmlRenderer();
    }
    foreach ($arr as $key =>$val) {
      //$raw = (keyVal($key,$tpl) === true) || in_array($key,$rawkeys,true);
      //if (!$raw) $val = hpure($val);
      $tpl[$key] = $val;
    }
    return $this->content($tpl,true);
  }


  /**
   * Takes a PkModel instance & string attribute name and displays as per $opts
   * Really messes up separation of model & display, but so convenient.
   * @param PkModel $model - REQUIRED
   * @param string $attname - REQUIRED
   * @param assoc array $opts:
   *   'wrapperAttributes' : string | array - if string, class name, else key/val
   *   'labelAttributes': string | array - if string, class name, else key/val
   *   'valueAttributes': string | array - if string, class name, else key/val
       'wrapperTag'=>'div',
       'labelTag' => 'div',
       'valueTag' => 'div',
   *   'raw': boolean: default: false - 
   *   'label':true | false | 'a string' : If true - use PkModel->attdesc() for
   *       label. If false, no label, if string, use as label
   */
  ## Don't want this for now
  /*
  public function wrapattr(PkModel $model,$attname,$opts=[]) {
    $defaultOpts = [
        //'wrapperClass' => 'pk-wrapper',
        //'labelClass' => 'pk-label',
        //'valueClass' => 'pk-value',
        'label' => true,
        'raw' => false,
        'wrapperTag'=>'div',
        'labelTag' => 'div',
        'valueTag' => 'div',
        'valueAttributes' => 'pk-value',
        'labelAttributes' => 'pk-label',
        'wrapperAttributes' => 'pk-wrapper',
    ];
    $opts = array_merge($defaultOpts, $opts);
    extract($opts);
    if ($raw) {
      $valueTag = 'raw'.$valueTag;
      $labelTag = 'raw'.$labelTag;
    }
    if ($label === true) $label = $model->attdesc($attname);
    $value = $model->$attname;
    $this->$wrapperTag(RENDEROPEN,$wrapperAttributes);
      if ($label !== false) $this->$labelTag($label, $labelAttributes);
      $this->$valueTag($value, $valueAttributes);
    $this->RENDERCLOSE();
    return $this;
  }
   * 
   */

  public function filterOutput($raw) {

  }

  public function rawwrap($value='', $label='',$valueClass='', $labelClass='', $wrapperClass ='') {
    if (is_array($value)) {
      $value['raw'] = true;
    }
    return $this->wrap($value, $label,$valueClass, $labelClass, $wrapperClass, true);
  }

  /** Just return $this->tagged with $raw default to true rather than false
   * 
   * @param string $tag - the HTML tag - required
   * @param scalar|array|null $content
   * @param string|array|null $attributes
   * @param boolean $raw - default true
   * @return html string
   */
  public function rawtagged($tag, $content = null, $attributes=null, $raw = true) {
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
  public function tagged($tag, $content = null, $attributes=null, $raw = false) {
    $ctype = typeOf($content);
    //if (! is_simple($content)) pkdebug("Type of Content: [$ctype]");
    $attributes = $this->cleanAttributes($attributes);
    if (($content === true) || ($content === $this)) { #That's RENDEROPEN === TRUE
      #We don't know when the balanced close is going to happen, so cant automate it,
      #so we just use the open tag wait for "RENDERCLOSE() to add the closing tag.
      #just have to be told by 
      $spaces = $this->spaceDepth();
      $size = $this->addTagStack([$tag=>['raw'=>$raw]]);
      return $this->rawcontent("$spaces<$tag ".PkHtml::attributes($attributes).">");
    } else if (($content === false)) {
                                ##Nest the elements
      #Don't remember what I meant by nest the elements, but I guess that means
      #don't expect this cell to enclose anything else.
      $spaces = $this->spaceDepth();
      $size = $this->addTagStack([$tag=>['raw'=>$raw]]);
      #Add the tag & attributes. This is a contentless, self-closing tag, like IMG, so shouldn't
      #contain anything more.
      return $this->rawcontent("$spaces<$tag ".PkHtml::attributes($attributes).">");
    } else {
      #Trust if text has ALREADY  been wrapped in PhHtmlRenderer has already been filtered

//if (!$raw && !static::getRawCount() && !($content instanceOf PkHtmlRenderer)) {
      //$this[]=$this->spaceDepth()."<$tag ".PkHtml::attributes($attributes).">
      #This tag CAN contain content in addition to what we already added, so return a refeference
      #this point in the array, so anyone who adds to this point adds next to it, inside
      #us. We DO close after we insert this content, but her position is secure
      # and everyone who joins her is save in the same position.

      $this->rawcontent("<$tag ".PkHtml::attributes($attributes).">");

      #############  ORIG ##############
      if (is_array($content) || ($content instanceOf \Generator)){
        foreach ($content as $citem) {
          $this->content($citem,$raw);
        }
      } else {
        $this->content($content,$raw);
      }
      $this->rawcontent("</$tag>\n");
      return $this;


      #############  Trying to Nest May 2017 ##############
      /*
      $child = $this->makeChild();
      if (is_array($content)){
        foreach ($content as $citem) {
          $child->content($citem,$raw);
        }
      } else {
        $child->content($content,$raw);
      }
      $this->content($child);
      $this->rawcontent($this->spaceDepth()."</$tag>\n");
      return $child;
       */


    }

    
  }

  /** This called for no-content element tags, like img, br, hr, input, etc */
  public function nocontent($tag, $attributes=null) {
    $attributes = $this->cleanAttributes($attributes);
    //pkdebug("TAG: [$tag], atts:",$attributes);
    //$this[] = "<$tag ". PkHtml::attributes($attributes).">\n";
    return $this->rawcontent("<$tag ". PkHtml::attributes($attributes).">\n");
  }


  //Inputs  & Forms
### Don't think this works well
  public function form($content, $options = []) {
    $options = $this->cleanAttributes($options);
    //return $this->rawcontent(PkForm::open($options) . $content . PkForm::close());
    $this->rawcontent(PkForm::open($options));
    $this->rawcontent($content); 
    $this->rawcontent(PkForm::close());
    return $this;
  }

  public function model($model, $options=[]) {
    return $this->rawcontent(PkForm::model($model,$options));
  }

  public function close() {
    return $this->rawcontent(PkForm::close());
  }

  public function open( $options=[]) {
    return $this->rawcontent(PkForm::open($options));
  }

  public function select($name, $list = [], $selected = null, $options = []) {
    if (!is_stringish($name) && is_arrayish($name)) {
      $name = keyVal('name', $name);
      $list = keyVal('list', $name,$list);
      $selected = keyVal('selected', $name,$selected);
      $options = keyVal('options', $name,$options);
    }
    $options = $this->cleanAttributes($options);
    #If selected exists in options, override arg
    $selected=keyVal('selected',$options,$selected);
    #Set name in options
    $options['name']=keyVal('name',$options,$name);
    //$this[] = PkForm::select($name, $list, $selected, $options);
    return $this->rawcontent( PkForm::select($name, $list, $selected, $options));
  }

  public function multiselect($name, $list = [], $values=null, $options=[], $unset = null) {
    if (!is_stringish($name) && is_arrayish($name)) {
      $name = keyVal('name', $name);
      $list = keyVal('list', $name,$list);
      $values = keyVal('values', $name,$values);
      $options = keyVal('options', $name,$options);
      $unset = keyVal('unset', $name,$unset);
    }
    $options = $this->cleanAttributes($options);
    #If values exists in options, override arg
    $values=keyVal('values',$options,$values);
    #Set name in options
    $options['name']=keyVal('name',$options,$name);
    //$this[] = PkForm::multiselect($name, $list, $values, $options, $unset);
    return $this->rawcontent( PkForm::multiselect($name, $list, $values, $options, $unset));
  }

  /** Appends / adds $att_val to the CLASS property </tt>{$att_name}_attributes</tt>
   * Mostly used by subclasses.
   * If {$att_name}_attributes exists & is string,
   *    converted to ['class'=>$this->{$att_name}_attributes]
   * If $att_val is string,
   *    converted to ['class'=>$att_val]
   * If the two arrays have the same keys ('class'), & is_string($val), they
   * are combined with a ' ' separator.
   * @param string $attr_name - the CLASS PROPERTY att name, gets '_attributes' appended
   * @param array|scalar $att_val
   */
  public function append_atts($attr_name, $att_val) {
    $property_name = $attr_name.'_attributes';
    if (!property_exists($this,$property_name)) return false;
    $property = $this->$property_name;
    if (!is_arrayish($property)) {
      $property = ['class' => $property];
    }
    if (!is_arrayish($property)) return false; #Something wrong...
    if (!is_arrayish($att_val)) {
      $att_val = ['class' => $att_val];
    }
    if (!is_arrayish($att_val)) return false; #Something wrong...
    foreach ($property as $propkey => $propval) {
      if (!array_key_exists($propkey,$att_val)) continue;
      $att_val_val = $att_val[$propkey];
      //if (is_scalarish($propval)) $propval = [$propval];
      //if (is_scalarish($att_val_val)) $att_val_val = [$att_val_val];
      $property[$propkey] = $propval.' '.$att_val_val;
    }
    $this->$property_name = $property;
    return $this->$property_name;
  }

  public function hidden($name, $value = null, $options = []) {
    if (is_arrayish($name)) {
      $name = keyVal('name', $name);
      $value = keyVal('value', $name,$value);
      $options = keyVal('options', $name,$options);
    }
    $options = $this->cleanAttributes($options);
    $this->rawcontent(PkForm::hidden($name, $value, $options));
    return $this;
  }
  public function text($name, $value = null, $options = []) {
    return $this->input('text',$name,$value,$options);
  }
	public function boolean($name,  $checked = null, $options = [], $unset = '0', $value = 1) {
    if (!is_stringish($name) && is_arrayish($name)) {
      $name = keyVal('name', $name);
      $checked = keyVal('checked', $name,$checked);
      $options = keyVal('options', $name,$options);
      $unset = keyVal('unset', $name,$unset);
      $value = keyVal('value', $name,$value);
    }
    $options = $this->cleanAttributes($options);
    #If checked exists in options, override arg
    $checked=keyVal('checked',$options,$checked);
    #Set name in options
    $options['name']=keyVal('name',$options,$name);
    //$this[]= PkForm::boolean($name,  $checked, $options, $unset, $value);
    return $this->rawcontent(PkForm::boolean($name,  $checked, $options, $unset, $value));
  }

  public function radio($name, $value = null, $checked = null, $options = []){
    $options = $this->cleanAttributes($options);
    return $this->rawcontent(PkForm::radio($name, $value, $checked, $options));
  }
  public function checkbox($name, $value = 1, $checked = null, $options = []) {
    $options = $this->cleanAttributes($options);
    return $this->rawcontent(PkForm::checkbox($name, $value, $checked, $options));
  }


  // THIS OBVIOUSLY WON'T BIND WITH MODELS, THOUGH! 
  /** Expect all the attributes to be set - that's the only arguemnt */
  public function pureinput($atts) {
    return $this->nocontent('input', $atts);
  }

  /** Just makes a fresh, standalone instance of static */
  /*
  public function clonedInput($type, $name, $value = null, $options = []) {
    $inp = new static();
    $ret = $inp->input($type, $name, $value, $options);
    pkdebug("CLONED-INPUT:",$inp, "RET: [$ret]:", $ret);
    return $ret;
    //return $inp;
  }
   * 
   */

  /** If 'options' contains 'label'=>text, wraps input within label
   * 
   * @param type $type
   * @param type $name
   * @param type $value
   * @param type $options
   * @return type
   */
  public function input($type, $name, $value = null, $options = []) {
    if (!is_stringish($name) && is_arrayish($name)) {
      $name = keyVal('name', $name);
      $value = keyVal('value', $name,$value);
      $options = keyVal('options', $name,$options);
    }
    $options = $this->cleanAttributes($options);
    if ($type === 'checkbox') {
      $value=keyVal($options,'value',1);
    }

    #If value exists in options, override arg
    $value=keyVal('value',$options,$value);
    #Set name in options
    $options['name']=keyVal('name',$options,$name);
    $label = unsetret($options,'label');
    $input = PkForm::input($type, $name, $value, $options);
    if ($label) { 
      $input = $this->labelize($label, $input);
    }
    return $this->rawcontent($input);
    //return $this->rawcontent(PkForm::input($type, $name, $value, $options));
  }

  /**
   * Wrap a form input with a label
   * @param mixed $label - can be label text, or assoc array of label & atts,
   * ['label'=>"User Name",'class'=>"inp-lbl", ...]
   * @param htmlInput $input
   * @param type $options
   */
  public function labelize($label,$input, $options=[]) {
    if (is_array_assoc($label)) {
      $txt = unsetret($label,'label');
      $attstr = PkHtml::attributes($label);
    } else {
      $txt = $label;
      $attstr = '';
    }
    $ret = new static();
    $ret::rawcontent("<label $attstr>$txt $input</label>\n");
    return $ret;
  }

  /** Don't just default to 'tagged', cuz it's input
   * 
   * @param type $name
   * @param type $value
   * @param type $options
   */
  public function textarea($name, $value = null, $options = []) {
    if (!is_stringish($name) && is_arrayish($name)) {
      $name = keyVal('name', $name);
      $value = keyVal('value', $name,$value);
      $options = keyVal('options', $name,$options);
    }
    $options = $this->cleanAttributes($options);
    #If value exists in options, override arg
    $value=keyVal('value',$options,$value);
    #Set name in options
    $options['name']=keyVal('name',$options,$name);
    //$this[] = PkForm::textarea($name, $value, $options);
    return $this->rawcontent(PkForm::textarea($name, $value, $options));
  }



  /** Make it look cleaner by just using many of the PkForm shortcuts */
  public function submitButton($label = 'Submit', $options = []) {
      if (is_string($options)) $options = ['class'=>$options];
    //$this[] = PkForm::submitButton($label,$options);
    return $this->rawcontent(PkForm::submitButton($label,$options));
  }


  /**
   * Just makes attributes more flexible. If it's just a string, build an
   * attribute array of ['class' => $attributes]
   * if it's an indexed array, assume array of classes, implode, and do the same
   * NOTE: Overridden in subclasses to customize attributes (like SubForm)
   * @param array|string $attributes
   * @param array|string $defaults, if equivalent key not set in attributes.
   *   Again, $defaults can be string, then converted to $defaults=['class'=>$defaults]
   * @param $required - The $defaults are REPLACED by the other att, required is combined
   */
  public function cleanAttributes($attributes, $defaults=null, $required=null) {
    if (is_array_indexed($attributes)) {
      $attributes = implode (' ', $attributes);
    }
    if (is_string($attributes)) $attributes = ['class' => $attributes];
    if (is_string($required)) $required = ['class' => $required];
    if (!is_array_assoc($attributes)) $attributes = [];
    if (!is_array_assoc($required)) $required = [];
    $atts = merge_attributes($attributes, $required);
    if (!$defaults) {
      return $atts;
    }
    $defaults = $this->cleanAttributes($defaults);
    return array_merge($defaults, $atts);
  }

  /** To render regular Blade templates within a PkHtmlRenderer context
   * ... uh, looks like it renders .phtml templates - which is fine
   * @param string $view - standarad blade view name
   * @param array $data - standard blade view data
   * @return string|\PkExtensions\PkHtmlRenderer
   */
  public function render($view,$data=[]) {
    if (!$view || !is_string($view)) return '';
    $relview = str_replace('.','/', $view);
    $viewroots = \Config::get('view.paths');
    $viewfile = null;
    //pkdebug('viewroots', $viewroots);
    foreach ($viewroots as $viewroot ) {
      $testpath = $viewroot.'/'.$relview.'.phtml';
     // pkdebug("testpath:  $testpath");
      if (file_exists($testpath)) {
        $viewfile = $testpath;
        break;
      }
      #Will this work with Blade templates?
      $testpath = $viewroot.'/'.$relview.'.blade.php';
      if (file_exists($testpath)) {
        $viewfile = $testpath;
        break;
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
    //pkdebug("RENDEROUT\n\n$___PKMVC_RENDERER_OUT\n\n");
    //$this[] = $___PKMVC_RENDERER_OUT;
    return $this->rawcontent( $___PKMVC_RENDERER_OUT);
  }

  /**Close with matched tag */
  public function closematch() {
    //$tag = array_pop($this->tagStack);
    $tag = $this->popTagStack();
    //$this[] = $this->spaceDepth()."</$tag>\n";
    return $this->rawcontent( $this->spaceDepth()."</$tag>\n");
  }

  #Alias for ->close()
  public function RENDERCLOSE() {
    return $this->closematch();
  }


  #Not happy with this...
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

  /**
   * May 2017 -- Try re-implementing function row() below - trying again
   * Very simple BS Row Renderer. Makes a <div class='row $rowclass'>
   * Then iterates through $data array and wraps each datum in corresponding col class
   * @param array|stringish $data - INDEXED ARRAY (not ISH) of stringish datums
   *    OR EACH DATUM is also an INDEXED array, $datum[0] stringish,
   *    $datum[1] assoc arr of atts or string class name
   *     for the column
   * @param array $opts: (but can be $rowclass string, then $colclass is string)
   *    @param string $rowatts - optional additional attributes for the row
   *    @param string|array $colclass - optional if string, same col class for every column
   *      if indexed array, must be same size as $data, custom per data col
   *    @param boolean $raw
   * @return $this
   */
  public function bs_row($data, $opts=[], $colclass='') {
    if (is_array($opts)) {
      $colclass=keyVal('colclass',$opts, $colclass);
      $raw = keyVal('raw', $opts);
      unset($opts['colclass']);
      unset($opts['raw']);
      $rowatts = $opts;
    } else {
      $rowatts=['class'=>$opts];
      $raw=false;
    }
    if ($raw) {
      $div = 'rawdiv';
    } else {
      $div = 'div';
    }
    $rowatts['class'] = keyVal('class',$rowatts,''). " row ";
    $this->$div(RENDEROPEN, $rowatts);
    if ($data === RENDEROPEN) { #It's just a render open - put the cols in manually
      return $this;
    } 
    if (is_stringish($data)) $data=[$data];
    if (!is_array_indexed($data)) throw new PkException(["Invalid data type",$data]);
    foreach ($data as $i => $datum) {
      if (is_array_indexed($colclass)) {
        $icolclass = $colclass[$i];
      } else {
        $icolclass=$colclass. ' col';
      }
      $val=$atts=null;
      //pkdebug("Datum:",$datum);
      if (is_stringish($datum)) {
        $val = $datum;
        $atts = ['class' =>""];
      } else if (is_array_indexed($datum)) { #$datum[0] must be stringish - or array?
        $val=$datum[0];
        //pkdebug("And val:", $val);
        //if (!is_stringish ($val=$datum[0])) throw new PkException(['Invalid Datum 1',$datum]);
        $atts = keyVal(1,$datum, '');
        if (is_stringish($atts)) {
          $atts = ['class'=>$atts];
        }
        //pkdebug("...and atts: ", $atts);
      } else {
        throw new PkException(['Invalid Datum 2',$datum]);
      }
      if (!is_array_assoc($atts)) throw new PkException(['Invalid Atts',$atts]);
      $atts['class'] = keyVal('class',$atts,'');
      //if (strpos($atts['class'],'col') === false) $atts['class'].= ' col ';
      $atts['class'] .= " $icolclass  ";
      //pkdebug("And Val, atts:",$val, $atts);
      $this->$div($val,$atts);
    }
    $this->RENDERCLOSE();
    return $this;
  }

  /** Just make a BS4 column from the data, with additional atts
   * 
   * @param stringish $data
   * @param string|assoc arr $atts - if assoc array, the div atts, if just string,
   * make it the div class ($atts=['class'=>$atts])
   */
  public function bs_col($data=null,$atts=[]) {
    if ($data && !is_stringish($data)) throw new PkException (["Invalid Data arg:", $data]);
    if (is_stringish($atts)) $atts-['class'=>$atts];
    if ($atts && !is_arrayish($atts)) throw new PkException (["Invalid Atts arg:", $atts]);
    $atts['class'] = keyVal('class', $atts,''). ' col ';
    $raw = keyVal('raw',$atts,false);
    unset($atts['raw']);
    $div = 'div';
    if ($raw) $div = 'rawdiv';
    $this->$div($data,$atts);
    return $this;
  }
 
  

  /**
   * May 2017 -- See above function bs_row() - trying again
   * Very simple BS Row Renderer. Makes a <div class='row $rowclass'>
   * Then iterates through $data array and wraps each datum in corresponding col class
   * @param array $data - indexed array of stringish datums for the row
   * @param array|string $colclasses - indexed array of same size as $data,
   *   with corresponding col classes, OR a single column class string to be used
   *   on EACH column of the row - identical
   * @param array $opts: (but can be $rowclass string, then $colclass is string)
   *    @param string $rowclass - optional additional class for the row
   *    @param string $colclass - optional additional class for every column
   *    @param boolean $raw
   * @return $this
   */
  public function row ($data, $colclasses=null, $opts=[], $colclass='') {
    if (is_array($opts)) {
      $rowclass= keyVal('rowclass', $opts);
      $colclass=keyVal('colclass',$opts);
      $raw = keyVal('raw', $opts);
    } else {
      $rowclass=$opts;
      $raw=false;
    }
    if ($raw) {
      $div = 'rawdiv';
    } else {
      $div = 'div';
    }
    $row = "row $rowclass";
    $sz = count($data);
    $colsz = 12/$sz;
    $this->$div(RENDEROPEN, $row);
    foreach ($data as $i => $datum) {
      if (!$colclasses) {
        $colclasses = "col-sm-$colsz";
      }
      if (is_array($colclasses)) {
        $itemclass = $colclasses[$i];
      } else {
        $itemclass = $colclasses;
      }
      $data_colclass = "$itemclass $colclass";
      $this->$div($datum,$data_colclass);
    }
    $this->RENDERCLOSE();
    return $this;
  }


  public function rawrow ($data, $colclasses=null, $opts=[], $colclass='') {
    if (!is_arrayish($opts)) {
      $opts = ['rowclass'=>$opts, 'colclass'=>$colclass];
    }
    $opts['raw']=true;
    return $this->row($data, $colclasses,$opts, $colclass);

  }

  /**
   * Outputs an html table row
   * @param array|string $data - the array of columns to output (string if only one)
   *   BUT if an element of $data is an array, is ['val'=>$value,'coltag'=>$tag,'colatts'=>$attributes]
   * @param string|array $opts
   *   if string, the th or (default) td tag to use for the columns
   * @return html table row string
   */
  public function tablerow($data=[],$opts='td') {
    //pkdebug("Data:",$data);
    if (!is_array($data)) $data = [$data];
    if (is_string($opts)) $opts = ['coltag' => $opts];
    if (is_array($opts)) { 
      $coltag = keyVal('coltag',$opts,'td');
      $rowclass = keyVal('row_class',$opts);
      if (ne_string($rowclass)) $rowclass = " class='$rowclass'";
    }
    //$this[]="<tr>\n";
    $this->rawcontent("<tr $rowclass>\n");
    foreach ($data as $idx =>$datum) {
      if (!is_array($datum)) $datum = ['value'=>$datum];
      $value = keyVal('value',$datum);
      $lcoltag = keyVal('coltag',$datum,$coltag);
      $latts =  keyVal('colatts',$datum);
      //pkdebug( "About to return:  <$lcoltag $latts>$value</$lcoltag>\n");
      $this->rawcontent( "  <$lcoltag $latts>$value</$lcoltag>\n");
    }
    //$this[]="</tr>\n";
    return $this->rawcontent("</tr>\n");
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

  /** Does nothing (noop) - but can be called statically to create an empty
   * instance: $rnd = PkRenderer::noop();
   * //@param string $tag
   * //@param array|string|null $atts
   * @return $this
   */
  public function noop($tag=null, $atts=null) {
    return $this;
  }
  public function spaceDepth() {
    $size = count($this->tagStack);
    $out = '';
    for ($i = 0 ; $i < $size ; $i++) $out .= '  ';
    return $out;
  }

  public static function __callStatic($method, $args) {
    $rnd = new static();
    return call_user_func_array([$rnd,$method], $args);
  }

  public function __call($method, $args) {
    $method = trim($method);
    $raw = false;
    if ($tag = removeStartStr($method,'raw')) {
      $method = $tag;
      $raw = true;
    }

    #Try to wrap form inputs 
    if ($wrapInput = removeStartStr($method,'wrap')) {
      $wrapInput = strtolower($wrapInput);
      if (in_array($wrapInput,static::getWrappableInputs())) {
        return $this->handleInputWrap($wrapInput, $args);
      }
    }

    #Check for $method = wrap[Form-Inputs]





    #TODO: Do something with $raw
    array_unshift($args,$method);
    if (in_array($method, static::$selfclosing_tags)) {
      return call_user_func_array([$this,'nocontent'], $args);
    } else if (in_array($method, static::$content_tags)) {
      if ($raw) {
        return call_user_func_array([$this,'rawtagged'], $args);
      } else {
        return call_user_func_array([$this,'tagged'], $args);
      }
    } else if (in_array($method, static::$input_types)) {
      //$args = array_unshift($args,$method,$args);
      return call_user_func_array([$this,'input'], $args);
    }
    throw new \Exception("Unknown Method: [$method]");
  }


  /** Build an entire PkHtmlRenderer set, perhaps nested deeply, just from 
   * arrays - that include the PkRenderer method name
   * @param array $args. Indexed or Associative.  If assoc, the key 'method' must be defined.
   * 
   * If Indexed,  Each array /row element of $args is an ASSOCIATIVE array as above,
   *   with at least $args[0]['method'] set
   *    $args[0]['args'] is an array - either Associative or Indexed. If indexed, create a 
   *     new instance of static & return user_fun_.... with $method & args.
   *   if ($args[n]['args'] is an ASSOCIATIVE array, $args[n]['args']['method'] must be defined.
   *     then $args[n]['args']['args'] must be an array or null. It can be an indexed or assoc array.
   *    to the method of 
   *   $method - the method name as a string
   *   $content - could be array or stringish. If array, an array of strings or recursive array args to 
   *     this method
   *  $attributes: An associative array of attributes/args for $method
   * @return PkHtmlRenderer representation of the tree
   */
  /*
  public function buildFromArray($args=[]) {
    $method = keyVal(0,$args); #Must be string method name
    $methodargs = keyVal(1,$args,[]); #Must be array
    $childargs = keyVal(2,$args); #Must be indexex array of arrays just like this args array
    if (!is_string($method) || !is_array($methodargs) ||
        ($childargs && !is_array_indexed($childargs))) {
      throw new PkException(["Bad Args",$args]);
    }
    $ret = call_user_func_array([$this,$method], $methodargs);
    if (!$childargs) return $ret;
    foreach ($childargs as $row) {
      $this->buildFromArray($row);
    }
    return $this;
  }
   * 
   */

  /** Calls the _call method handleInputWraps below, but with the args individually
   * specified, rather than in an $args array
   * @param string $formel
   * @param array $inputprops
   * @param array $valprops
   * @param array $lblprops
   * @param array $wrapprops
   * @return \static
   * @throws PkException
   */
  public function inputWrap($formel, $inputprops=null, $valprops=null, $lblprops=null, $wrapprops=null) {
    $argarr = [$inputprops,$valprops,$lblprops,$wrapprops];
    return $this->handleInputWrap($formel, $argarr);
  }
  /** Wraps form elements/controls in a couple of divs, w. a label
   * 
   * @param string $formel - the name of a handleable form control
   * @param array $args: an indexed array of the arguments to the _called 
   * function.
   *   $args[0] array of all the properties of the control we will make
   * 
   * Then Ideally:
   *   $args[1]: Assoc array of all properties of the 3 wrapping elements -
   *     $args[1]['valprops'] - values for the input wrapping class
   *     $args[1]['labprops'] - assoc array of all properteis of the label, 
   *       including "value" & "tag"
   *    $args[1]['wrapprops'] - assoc array of props for the enclosing element
   * But actually:
   * $inputprops = $args[0] #Most important: name
   * $valprops=$args[1],
   * $lblprops = $args[2]
   * $wrapprops = $args[3]
   * 
   * return new static, beautifully wrapped, with nice defaults
   * 
   */

 //arrayifyArg($arg = null, $key = 'value', $defaults = null, $addons = null, $replace = null) {

  public function handleInputWrap($formel, Array $args=[]){
    /*
    $pararr = keyVal(1,$args,[]);
    if (!is_array($pararr)) throw new PkException(['Unexpecte Args:', $args]);
    if (is_array_assoc($pararr)) {
      $valprops = arrayifyArg(keyVal('valprops', $pararr), 'class',['class'=>'pk-inp-wrap']);
      $lblprops = arrayifyArg(keyVal('lblprops', $pararr),'value',['class'=>'pk-lbl']);
      $wrapprops  = arrayifyArg(keyVal('wrapprops', $pararr),'class',['class'=>'pk-lbl']);
    }
     */
    $inputprops = keyVal(0,$args);
    $valprops = arrayifyArg(keyVal(1, $args), 'class',['class'=>'pk-inp-wrap']);
    $lblprops = arrayifyArg(keyVal(2, $args),'value',['class'=>'pk-lbl']);
    $wrapprops  = arrayifyArg(keyVal(3, $args),'class',['class'=>'pk-wrapper']);
    $frm_ctl = new static();

    if (in_array($formel, static::$input_types,true)) {
      $inputprops['type'] = $formel;
      $formel = 'input';
    }
    if ($formel === 'input') {
      $bkup = $inputprops;
      $value = unsetret($inputprops,'value');
      $name = unsetret($inputprops, 'name');
      $type = unsetret($inputprops,'type');
      //$frm_ctl->clonedInput($type,$name, $value, $inputprops);
      $frm_ctl->input($type,$name, $value, $inputprops);
      //pkdebug("MkClonedInput: type: [$type], name: [$name], value: [$value], inputprops:", $inputprops, "Cloned Input Ctl: [$frm_ctl]");
      //$frm_ctl = static::pureinput($inputprops);
    } else if ($formel === 'textarea') {
      $name = unsetret($inputprops,'name');
      $value = unsetret($inputprops,'value');
      $frm_ctl->textarea($name, $value, $inputprops);
    } else if ($formel === 'boolean') {
      $checked = unsetret($inputprops,'checked');
      $name = unsetret($inputprops,'name');
      $value = unsetret($inputprops,'value',1);
      if ($value === null) $value = '1';
      $unset = unsetret($inputprops,'unset','0');
      $frm_ctl->boolean($name,$checked,$inputprops,$unset,$value);
    } else if ($formel === 'select') {
      $list = unsetret($inputprops,'list');
      if (!$list) { #Don't throw exception, but return empty row & report
        pkdebug("There were no choices for the select; args were:", $args);
      }
      $name = unsetret($inputprops,'name');
      $selected = unsetret($inputprops, 'selected');
      $frm_ctl->select($name, $list, $selected, $inputprops);
    } else { #Didn't hind an input type we handle yet
      throw new PkException("Unhandled Wrap Method: [$formel]");
    }
    //pkdebug('Args:',$args,'valprops:',$valprops,'lblprops', $lblprops,'wrapprops',$wrapprops,"frmctl:\n\n$frm_ctl");
    $vw_tag = unsetret($valprops,'tag','div');
    $inputwrapper = new static();
    $inputwrapper->$vw_tag($frm_ctl,$valprops);
    $lbl_tag =  unsetret($lblprops,'tag','div');
    $lbl_val =  unsetret($lblprops,'value');
    $lblwrapper = new static();
    $lblwrapper->$lbl_tag($lbl_val,$lblprops);
    //pkdebug("Made lblwrapper w. lbl_tag [$lbl_tag]; lbl_val: [$lbl_val], lblwrapper is: [$lblwrapper], the lblprops:", $lblprops);
    $wrp_tag  = unsetret($wrapprops,'tag','div');
    $set_wrapper = new static();
    $set_wrapper->$wrp_tag([$lblwrapper,$inputwrapper],$wrapprops);
    /*
    if ($formel === 'input') pkdebug("SetWrapper:\n\n$set_wrapper",
     "Made lblwrapper w. lbl_tag [$lbl_tag]; lbl_val: [$lbl_val], lblwrapper is: [$lblwrapper], the lblprops:", $lblprops);
     * 
     */
    return $set_wrapper;
  }

  
  /** Uses the above function to return many nearly identical wrapped input
   * rows. Only input[name] & label[value] differ. They are mapped by
   * @param string|null|array $type  - the default input/control type for the set of rows:
   *    boolean, text, textarea or... CAN BE NULL, if 'type' is a key in the $map
   *    array elements. If both $type & $inp[type] exist, the value of $inp['type'] 
   *   will be type used for that row - but the next map row might not HAVE a type
   *   key, so the default '$type" will be used. So you  can pass a $map of several
   *   similar but different input types and return them all as similarly formatted rows.
   * 
   *  BUT if is_array($type), assume assoc array with keys as argument names
   * 
   *   If 'type' exists both as a non-empty argument, AND $map[m][inp][type] is set,
   *   the  $map[m][inp][type] value will be used to override the default $type, ONLY
   *   FOR THAT ROW. The default $type will be used for the rest.
   * @param array $map: TWO FORMS:
   *    Assoc array inpnames=>lblText: = [$ctlName1=>lblVal1,$ctlName2=>$lblVal2,....
   *   OR INDEXED ARRAY of arrays - gives more configurability: Array of:
   *   ['inp'=>$iprops, 'val'=>$vprops, 'lbl'=>$lprops, 'wrap'=>$wprops']
  *   WHERE
   *   $iprops EITHER assoc array of atts, or string ctl name: $iprops = ['name'=>$iprops]
   *   $vprops EITHER assoc array of atts, or string class name: $vprops = ['class'=>$vprops]
   *   $lprops EITHER assoc array of atts, or string label value name: $lprops = ['value'=>$lprops]
   *   $wprops EITHER assoc array of atts, or string class  name: $wprops = ['class'=>$wprops]
   * 
   * @param array $inputTpl : substitute the ctl name here - $inputTpl['name'] ...
   * @param mixed $inpWrapProps - just pass it along directly
   * @param array $lblTpl : substitute the label value name here - $lblTpl['value'] ...
   * @param mixed $wrapWrapProps - just pass it along directly
   */

  public function mkWrappedInputSet($type=null, $map = [], $inpTpl=[], $valTpl=[], $lblTpl=[], $wrapTpl=[]) {
    //pkdebug("Coming In: Type: $type, Map:",$map, 'inpTpl:', $inpTpl);
    if (is_array_assoc($type)) {
      $map = keyVal('map',$type,[]);
      $inpTpl = keyVal('inpTpl', $type, []);
      $valTpl = keyVal('valTpl', $type,[]);
      $lblTpl = keyVal('lblTpl', $type,[]);
      $wrapTpl = keyVal('wrapTpl',$type,[]);
      $type = keyVal('type',$type);
    }
    $ret = new static();
    if (!is_array($map) || !count($map)) return $ret;
    if (is_string($inpTpl) || ($inpTpl === null)) $inpTpl = ['class'=>$inpTpl];
    if (is_string($valTpl) || ($valTpl == null))  $valTpl = ['class'=>$valTpl];
    if (is_string($lblTpl) || ($lblTpl == null))  $lblTpl = ['class'=>$lblTpl];
    if (is_string($wrapTpl) || ($wrapTpl == null))  $wrapTpl = ['class'=>$wrapTpl];
    if (!is_array($inpTpl) || !is_array($lblTpl)) {
      throw new PkException(['Bad values for the TPLs: inpTpl:', $inpTpl, 'lblTpl', $lblTpl]);
    }
    if (is_array_indexed($map)) { #New, more configurable map
    #Could even have a different 'input type' in each map row, so can mix different inputs
    #in same array of rows.
     // pkdebug("After Processing: Type: $type, Map:",$map, 'inpTpl:', $inpTpl, 'valTpl', $valTpl, 'lblTpl', $lblTpl, 'wrapTpl', $wrapTpl);
 //arrayifyArg($arg = null, $key = 'value', $defaults = null, $addons = null, $replace = null) {
      foreach ($map as $proparr) {
        if (!is_array($proparr)) throw new PkExceptions(['Invalid proparr, MAP:', $map]);
        $inpprops = arrayifyArg(keyVal('inp',$proparr),'name',$inpTpl);
        $valprops = arrayifyArg(keyVal('val',$proparr),'class',$valTpl);
        //pkdebug("\n\n\nvalprops:",$valprops,'valTpl', $valTpl,"\n\n\n");
        $lblprops = arrayifyArg(keyVal('lbl',$proparr),'value',$lblTpl);
        $wrapprops = arrayifyArg(keyVal('wrap',$proparr),'class',$wrapTpl);
        if (is_array($inpprops) && keyVal('type', $inpprops)) {
          $rowtype = $inpprops['type'];
        } else {
          $rowtype = $type;
        }
        //pkdebug("About to wrap:",$type,$inpprops, $valprops, $lblprops, $wrapprops);

        $ret[] = $ret->inputWrap($rowtype,$inpprops, $valprops, $lblprops, $wrapprops);
      }
    } else {
      foreach ($map as $name=>$value) {
        $inpTpl['name'] = $name;
        $lblTpl['value'] = $value;
        $ret[] = $ret->inputWrap($type,$inpTpl, $valTpl, $lblTpl, $wrapTpl);
      }
    }
    return $ret;
  }

  ## Build Query controls
  public static function buildQuerySet($params = []) {
    $out=new static();
    $out->querySet($params);
    return $out;
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
  /** Makes an un-named datepicker text input (so it doesn't POST), and pairs it
   * with a hidden named input, 
   * Deeply depends on support from JS
   */
  public function datepicker($name,$value=null,$args=[]) {
    if (is_string($args)) $args =['class'=>$args];
    $dpClasses = ' datepicker nameless-proxy ';
    $args['data-ctrl-name'] = $name;
    $args['class'] = keyVal('class',$args,''). $dpClasses;
    /*
    return $this->rawcontent([
      $this->input('text',null,null,$args),
      $this->input('hidden', $name,$value,['class'=>'hidden-datepicker-partner']),
    ]);
     * 
     */
    $ret = PkRenderer::input('text',null,null,$args);
    $ret->input('hidden', $name,$value,['class'=>'hidden-datepicker-partner']);
    //$ret[] = static::input('text',null,null,$args);
    //$ret[] = static::
    return $ret;
  }


  /**Don't want to use these anymore - just wrap - but keep in case used in older apps*/
  public function textset( $name='', $value=null, $labeltext=null, $inputatts = [], $labelatts=[], $wrapatts = []) {
    //$this[] = PkForm::textset( $name, $value, $labeltext, $inputatts, $labelatts, $wrapatts);
    //return $this;
    return $this->rawcontent( PkForm::textset( $name, $value, $labeltext, $inputatts, $labelatts, $wrapatts));
  }
  public function inputlabelset($type, $name='', $value=null, $labeltext=null, $inatts = [], $labatts=[], $wrapatts = []) {
    //$this[] = PkForm::inputlabelset($type, $name, $value, $labeltext, $inatts, $labatts, $wrapatts);
    // return $this;
    return $this->rawcontent(PkForm::inputlabelset($type, $name, $value, $labeltext, $inatts, $labatts, $wrapatts));
  }

  public function textareaset($name, $value = null, $labeltext = '', $inatts = [], $labatts = [], $wrapatts =[]) {
    //$this[] = PkForm::textareaset($name, $value, $labeltext, $inatts, $labatts, $wrapatts);
    //return $this;
    return $this->rawcontent(PkForm::textareaset($name, $value, $labeltext, $inatts, $labatts, $wrapatts));
  }

  public function selectset( $name='', $list=[], $selected = null, $labeltext=null, $inatts = [], $labatts=[], $wrapatts = []) {
     //$this[] = PkForm::selectset( $name, $list, $selected, $labeltext, $inatts, $labatts, $wrapatts);
     //return $this;
     return $this->rawcontent(PkForm::selectset( $name, $list, $selected, $labeltext, $inatts, $labatts, $wrapatts));
  }

  public function dump() {
    echo($this);
    return $this;
  }






}
