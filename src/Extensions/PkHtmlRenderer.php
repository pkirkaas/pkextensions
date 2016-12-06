<?php
/** Renders HTML - can be included in Blade templates, but also implements a
 * custom templating/HTML rendering system.
 * 
 */
namespace PkExtensions;
use PkHtml;
use PkForm;
use PkExtensions\Models\PkModel;

if (!defined('RENDEROPEN')) define('RENDEROPEN', true);

/** We allow it to know about the possibility of the extended class
 * PkMultiSubformRenderer
 */
class PkHtmlRenderer extends PartialSet {
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
    if($raw || ($content instanceOf PkHtmlRenderer)) {
      $this[] = $content;
    } else {
      $this[] = hpure($content);
    }
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

  /**
   *The Templating Injection! Very powerful & flexible, with reusable
   * htmlRenderer mini-templates. Can even have default values. Example:
   * 
   * 
   * #Template:
      $wrap_tpl = new PkHtmlRenderer();
      $wrap_tpl[] = "<div  class='";
      $wrap_tpl['wrapper_class'] = 'col-xs-3 tpm-wrapper'; #Default
      $wrap_tpl[] = "'>\n<div  class='block tpm-label'>\n";
      $wrap_tpl['label']=null; #Replace
      $wrap_tpl[] = "</div>\n<div  class='block tpm-value'>\n";
      $wrap_tpl['input'] = null; #Replace
      $wrap_tpl[] = "</div>\n</div>\n";
   * 
   * #Call:
        PkRenderer::inject([
            'label'=>"First Name",
            'input'=>PkForm::text('fname', null, ['placeholder' => 'First Name','class'=>'pk-inp']),
        ],$wrap_tpl),

   *  
   * @param assoc $arr: The key/values to insert in the template - OR, if not
   *   an array, a "stringish" value (could be a PartialSet) inserted into
   *   $tpl at default "content":  <tt>$tpl['content']=$arr;</tt>
   * @param PartialSet|null: $tpl: A PartialSet/Renderer with indices matching
   * the value keys of the input array
   * 
   * @return PkHtmlRenderer - with data inserted in the template
   */
  public function inject($arr,$tpl=null) {
    if (!is_array($arr) && is_stringish($arr)) {
      $arr = ['content' => $arr];
    }
    if ($tpl instanceOf PartialSet) {
      $tpl = $tpl->copy();
    } else {
      $tpl = new PkHtmlRenderer();
    }
    foreach ($arr as $key =>$val) {
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

  /*
  public static function resetRawCount($i = 0) {
    static::$raw_depth = $i;
  }

  public static function incRawCount() {
    static::$raw_depth ++;
    return static::$raw_depth;
  }

  public static function decRawCount() {
    static::$raw_depth --;
    return static::$raw_depth;
  }

  public static function getRawCount() {
    return static::$raw_depth;
  }
   * 
   */



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
  public function tagged($tag, $content = null, $attributes=null, $raw = false) {
    /* // Want to use $content array for nesting 
    if (!is_stringish($content) && is_arrayish($content)) {
      $content = keyVal('content', $content);
      $attributes = keyVal('attributes', $content,$attributes);
      $raw = keyVal('raw', $content,false,$raw);
    }
     * 
     */
    /*
    if ($raw) {
      static::incRawCount();
    }
     * 
     */
    $ctype = typeOf($content);
    //if (! is_simple($content)) pkdebug("Type of Content: [$ctype]");
    $attributes = $this->cleanAttributes($attributes);
    //if (!$content) $content = ' ';
    if (($content === true) || ($content === $this)) { #That's RENDEROPEN === TRUE
    //if ($content === true) {
      $spaces = $this->spaceDepth();
      $size = $this->addTagStack([$tag=>['raw'=>$raw]]);
      //$this[]="$spaces<$tag ".PkHtml::attributes($attributes).">\n";
      return $this->rawcontent("$spaces<$tag ".PkHtml::attributes($attributes).">\n");
    } else if (($content === false)) {
                                ##Nest the elements
      $spaces = $this->spaceDepth();
      $size = $this->addDepthTagStack($tag);
      //$this[]="$spaces<$tag ".$this->attributes($attributes).">\n";
      return $this->rawcontent("$spaces<$tag ".$this->attributes($attributes).">\n");
    } else {
      #Trust that text already wrapped in PhHtmlRenderer has already been filtered

//if (!$raw && !static::getRawCount() && !($content instanceOf PkHtmlRenderer)) {
      //$this[]=$this->spaceDepth()."<$tag ".PkHtml::attributes($attributes).">
      $this->rawcontent($this->spaceDepth()."<$tag ".PkHtml::attributes($attributes).">\n");
      if (is_array($content)){
        foreach ($content as $citem) {
          $this->content($citem,$raw);
        }
      } else {
        $this->content($content,$raw);
      }
      $this->rawcontent($this->spaceDepth()."</$tag>\n");

/*


    if ($this->depthTagStack) {
      $i = count($this->depthTagStack);
      while ($i) {
        $tag = $this->popDepthTagStack();
        $i = count($this->depthTagStack);
        $this[] = "</$tag>\n";
        pkdebug("[i] $i:, tag: $tag; Depth Tag Stack: ", $this->depthTagStack);
      }




      //if ($raw) static::decRawCount();
*/
      return $this;
    }
  }

  public function nocontent($tag, $attributes=null) {
    $attributes = $this->cleanAttributes($attributes);
    //pkdebug("TAG: [$tag], atts:",$attributes);
    //$this[] = "<$tag ". PkHtml::attributes($attributes).">\n";
    return $this->rawcontent("<$tag ". PkHtml::attributes($attributes).">\n");
  }


  //Inputs  & Forms

  public function form($content, array $options = []) {
    return $this->rawcontent(PkForm::open($options) . $content . PkForm::close());
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
  /*
  public function hidden($name, $value = null, $options = []) {
    if (is_arrayish($name)) {
      $name = keyVal('name', $name);
      $value = keyVal('value', $name,$value);
      $options = keyVal('options', $name,$options);
    }
    $options = $this->cleanAttributes($options);
    $this[] = PkForm::hidden($name, $value, $options);
    return $this;
  }
  public function text($name, $value = null, $options = []) {
    return $this->input('text',$name,$value,$options);
  }
   * 
   */
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

  public function input($type, $name, $value = null, $options = []) {
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
    //$this[] = PkForm::input($type, $name, $value, $options);
    return $this->rawcontent(PkForm::input($type, $name, $value, $options));
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
   */
  public function cleanAttributes($attributes) {
    if (is_array_indexed($attributes)) {
      $attributes = implode (' ', $attributes);
    }
    if (is_string($attributes)) $attributes = ['class' => $attributes];
    return $attributes;
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
        $colclasses = "col-xs-$colsz";
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

  public function spaceDepth() {
    $size = count($this->tagStack);
    $out = '';
    for ($i = 0 ; $i < $size ; $i++) $out .= '  ';
    return $out;
  }

  public function __callStatic($method, $args) {
    $rnd = new static();
    return call_user_func_array([$rnd,$method], $args);
  }

  public function __call($method, $args) {
    $raw = false;
    if ($tag = removeStartStr($method,'raw')) {
      $method = $tag;
      $raw = true;
    }
    #TODO: Do something with $raw
    array_unshift($args,$method);
    if (in_array($method, static::$selfclosing_tags)) {
      return call_user_func_array([$this,'nocontent'], $args);
    } else if (!$raw && in_array($method, static::$content_tags)) {
      return call_user_func_array([$this,'tagged'], $args);
    } else if ( $raw  && in_array($method, static::$content_tags)) {
      return call_user_func_array([$this,'rawtagged'], $args);
    } else if (in_array($method, static::$input_types)) {
      //$args = array_unshift($args,$method,$args);
      return call_user_func_array([$this,'input'], $args);
    }
    throw new \Exception("Unknown Method: [$method]");
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


  /*  Not used for now....
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
   * 
   */
  


}
