<?php
namespace PkExtensions;
use PkExtensions\Models\PkModel;
use PkForm;
/** A base class to generate HTML pages & Forms.  */
#Just playing while I figure out what I want to do with it...

/** For methods with <tt>$args=[]</tt>, the param keys are:
 * 'content' - and the default if NOT $args is arrayish: Stringable content
 * 'attributes' attributes array, or if just string, classes
 */
class PkHtmlPainter {
  public $csrf_token;
  public function __construct($args=[]) {
  }

  public function wrapForm($args=[]) {
    
  }
  /** Returns an array of two arrays - the cleaned, suitable attributes, and
   * the method_vars array, which has values particular to that method
   * NOTE the 'component_args' key - it's an array of arrays of argument sets
   * for OTHER methods: $args = [$key1=>$val1,$key2=>$val2...,'component_args'=>
   *    ['injectTpl'=>$argsx,'mkCreateBtn'=>$argsy,... ]];
   * The component_args array is returned to the calling method, which decides
   * what to extract & provide the components IT calls.
   */
  public function clean_opts($args,$defaults,$show=false) {
    if ($args && is_stringish($args)) {
        $args = ['content'=>$args];
    }
    if (!$args || !is_array($args)) {
      $params = $defaults;
    } else {
      unset($args['requiredClasses']);
      $params = array_merge($defaults,$args);
    }
    $attributes = keyVal('attributes',$params,[]); 
    $attributes['class'] = keyVal('class',$params).' '.keyVal('requiredClasses',$params)
        .' ' .keyVal('add-class',$params). ' '.keyVal('class',$attributes);
    $rawpre = keyVal('raw',$params) ? 'raw' : '';
    $method_vars = [
        'ps_tpl' => keyVal('ps_tpl',$params),
        'ps_key'=>keyVal('ps_key',$params,'content'),
        'tag' => $rawpre.keyVal('tag',$params,'div'),
        'raw' => keyVal('raw',$params),
        'content' => keyVal('content',$params),
        'component_args'=>keyVal('component_args',$params),
    ];
    return ['method_vars'=>$method_vars,
            'attributes'=>$attributes,];
  }

  /** Returns the "content" injected into PkHtmlRenderer $tpl, according to $key (default: 'content')
   * 
   * @param type $content
   * @param type $tpl
   * @param type $key
   */
  public function injectTpl($content='',$tpl=null,$key='content') {
    if (!$tpl instanceOf PartialSet) return $content;
    $tpl[$key] = $content;
    return $tpl;

  }

  ###############  This section to support scrolling subforms 1 to many creation & deletion 
  /** Make a create button. Options:
   *  'content' - what to show in the button
   *  'tag' - default 'div'
   *  'attributes' - if string, assumed classes & converted to array. 
   *       But the classes required for JS ['js create-new-data-set'] will always be added
   * 'ps_tpl' Optional: PkHtmlRenderer template to inject the results in. Default key: 'content'
   * 'ps_key' - if other than 'content' 
   * 'class' - to override the default (but not the REQUIRED) css classes
   * 'attributes' - for HTML
   * 'add-class' - to SUPLEMENT the default
   * @return PkHtmlRenderer
   */
  public function mkCreateBtn($args=[]) {
    $defaults = [
        'content'=>'Create',
        'tag' => 'div',
        'class' => 'mf-btn pkmvc-button',
        'requiredClasses'=>'js btn create-new-data-set',

    ];

    $res = $this->clean_opts($args,$defaults,1);
    $method_vars = $res['method_vars'];
    $content = keyVal('content', $method_vars);
    $tag = keyVal('tag', $method_vars);
    $ps_tpl = keyVal('ps_tpl', $method_vars);
    $ps_key = keyVal('ps_key', $method_vars);
    $hr=new PkHtmlRenderer();
    return $this->injectTpl($hr->$tag($content,
        keyVal('attributes',$res)),$ps_tpl,$ps_key);
  }

  public function mkDelBtn($args=[]) {
    $defaults = [
        'content'=>'Delete',
        'tag' => 'div',
        'class' => 'mf-btn pkmvc-button',
        'requiredClasses'=>'js btn data-set-delete',

    ];
    $res = $this->clean_opts($args,$defaults);
    $method_vars = $res['method_vars'];
    $content = keyVal('content', $method_vars);
    $tag = keyVal('tag', $method_vars);
    $ps_tpl = keyVal('ps_tpl', $method_vars);
    $ps_key = keyVal('ps_key', $method_vars);
    $hr=new PkHtmlRenderer();
    return $this->injectTpl($hr->$tag($content,
        keyVal('attributes',$res)),$ps_tpl,$ps_key);
  }

  /** These methods can insert the content now, or return a keyed template for the
   * content to be inserted or replaced repeatedly
   * @param type $content
   * @return string
   */
  public function mkJsTemplate($content=null) {
    $ps = new PkHtmlRenderer();
      $ps['template-open']="<fieldset disabled style='display:none;' class='template-container'>\n";
      $ps['content'] = $content;
      $ps['template-close']="</fieldset>\n";
      return $ps;
  }

  /** $args['component_args']['createBtnArgs'] is for createBtn */
  public function mkSubformTemplate($args=[]) {
    $defaults = [
        'tag' => 'div',
        'requiredClasses'=>'templatable-data-sets',
    ];
    //PkHtmlRenderer::incRawCount();
    $res = $this->clean_opts($args,$defaults);
    $method_vars = $res['method_vars'];
    $content = keyVal('content', $method_vars);
    $tag = keyVal('tag', $method_vars);
    $ps_tpl = keyVal('ps_tpl', $method_vars);
    $ps_key = keyVal('ps_key', $method_vars);
    $createBtnArgs = keyVal('createBtnArgs',keyVal('component_args',$method_vars));
    $hr = new PkHtmlRenderer();
    $ps = new PkHtmlRenderer();
    $cbtn = $this->mkCreateBtn($createBtnArgs);
    $ps[]=$content;
    $ps[]=$cbtn;
    #This is where I had trouble with Renderer automatically "Purifying" output
    #AND the arg count was wrong 28 Nov 16
    return $this->injectTpl($hr->$tag($ps,
        keyVal('attributes',$res)),$ps_tpl,$ps_key);

  }

}



