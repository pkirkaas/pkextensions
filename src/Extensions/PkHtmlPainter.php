<?php
namespace PkExtensions;
use PkExtensions\Models\PkModel;
use PkExtensions\PkController;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag; #A collection of MessageBags
use PkForm;
use PkRenderer;
/** A base class to generate HTML pages & Forms.  */
#See bottom of file for examples

/** For methods with <tt>$args=[]</tt>, the param keys are:
 * 'content' - and the default if NOT $args is arrayish: Stringable content
 * 'attributes' attributes array, or if just string, classes
 */
class PkHtmlPainter extends PkHtmlRenderer{
  public $csrf_token;
  public function __construct($args=[]) {
    foreach ($args as $key => $val) {
      if (property_exists($this,$key)) {
        $this->$key = $val;
      }
    }
    parent::__construct();
  }

  public function mkPrintButton($args=[]) {
    $label = keyVal('label',$args,'Print');
    $class = keyVal('class',$args,'pkmvc-button inline ').' print-button ';
    return PkRenderer::div($label,$class);
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
            'attributes'=>$attributes,
             'params'=>$params];
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
  public $header; #Optional lead/head content
  public $create_button;
  public $delete_button;
  public $subform_tpl;
  public $subform_tag = 'div';
  public $subform_attributes;
  public $custom_opts; #Array of special options that any method can access, eg, ['createbtn_content'=>'New Diagnosis']
  public $row_tpl; #The Renderer template the inputs should be injected into; else just sequential
  public $row_tag = 'div';
  public $row_attributes;
  public $tpl_fields;#Defines Inputs, fldname=>input def:
     #  ['id'=>'hidden',
     #   'diagnosiscode_id'=>['select',['list'=>DiagnosisRef::getSelectList(true,true)]], ],
  public $data_rows=[]; #indexed array of [['fname1'=>$fval11,'fname2'=>$fval21],['fname1'=>$fval12,...
  public $basename;


  /** Make a create button. Options:
   *  'content' - what to show in the button
   *  'tag' - default 'div'
   *  'attributes' - if string, assumed classes & converted to array. 
   *       But the classes required for JS ['js create-new-data-set'] will always be added
   * 'ps_tpl' Optional: PkHtmlRenderer template to inject the results in. Default key: 'content'
   * 'ps_key' - if other than 'content' 
   * 'item_template': The Form item template to encode into this 'Create' button's 'data-template' attribute
   * 'class' - to override the default (but not the REQUIRED) css classes
   * 'attributes' - for HTML
   * 'add-class' - to SUPLEMENT the default
   * @return PkHtmlRenderer
   */
  public function mkCreateBtn($args=[]) {
    $defaults = [
        'createbtn_content'=>'Create',
        'createbtn_tag' => 'div',
        'class' => 'mf-btn pkmvc-button',
        'requiredClasses'=>'js btn create-new-data-set-int',
        'data-itemcount'=>0,

    ];

    if (!is_array($args)) $args = [];
    if (keyVal('createbtn_class',$args)) $args['class'] = $args['createbtn_class'];
    if (!keyVal('createbtn_content',$args) && keyVal('createbtn_content',$this->custom_opts)) {
      $args['createbtn_content'] = keyVal('createbtn_content',$this->custom_opts);
    }
    $res = $this->clean_opts($args,$defaults,1);
    $method_vars = $res['method_vars'];
    $params = $res['params'];
    $attributes = keyVal('attributes',$res,[]);
    if (($item_template = keyVal('item_template',$args)) && !keyVal('data-template',$attributes)) {
      pkdebug("Item Template:\n\n". $item_template);
        $attributes['data-template']=html_encode($item_template);
        pkdebug("HTML Encoded:", html_encode($item_template));
    }
    if (($item_count = keyVal('data-itemcount',$params)) && !keyVal('data-itemcount',$attributes)) {
        $attributes['data-itemcount']=$item_count;
    }
    $content = keyVal('createbtn_content', $params);
    $tag = keyVal('createbtn_tag', $params);
    $ps_tpl = keyVal('ps_tpl', $method_vars);
    $ps_key = keyVal('ps_key', $method_vars);
    //pkdebug("args:",$args,"attributes", $attributes);
    $hr=new PkHtmlRenderer();
    return $this->create_button = $this->injectTpl($hr->$tag($content,
        $attributes),$ps_tpl,$ps_key);
    //return $this->injectTpl($hr->$tag($content,
    //    keyVal('attributes',$res)),$ps_tpl,$ps_key);
  }

  public function mkDelBtn($args=[]) {
    $defaults = [
        'delbtn_content'=>'Delete',
        'delbtn_tag' => 'div',
        'class' => 'mf-btn pkmvc-button',
        'requiredClasses'=>'js btn data-set-delete',

    ];
    if (keyVal('delbtn_class',$args)) $args['class'] = $args['delbtn_class'];
    $res = $this->clean_opts($args,$defaults);
    $method_vars = $res['method_vars'];
    $params = $res['params'];
    $content = keyVal('delbtn_content', $params);
    $tag = keyVal('delbtn_tag', $params);
    $ps_tpl = keyVal('ps_tpl', $method_vars);
    $ps_key = keyVal('ps_key', $method_vars);
    $hr=new PkHtmlRenderer();
    return $this->delete_button = $this->injectTpl($hr->$tag($content,
        keyVal('attributes',$res)),$ps_tpl,$ps_key);
  }

  public function mkSubform($args=[]) {
     $subform_tag = keyVal('subform_tag',$args,$this->subform_tag);
     $subform_attributes = merge_attributes(
         keyVal('subform_attributes',$args,$this->subform_attributes),'templatable-data-sets');
     $subform_tpl = keyVal('subform_tpl',$args,$this->subform_tpl);
     if ($subform_tpl instanceOf PartialSet) {
       $subform_tpl = $subform_tpl->copy();
     } else {
       $subform_tpl = new PkHtmlRenderer();
     }
     $basename = keyVal('basename',$args,$this->basename);
     $jsRowTpl = $this->mkSubformRow($args);
     $data_rows = keyVal('data_rows',$args,$this->data_rows);
     $item_count = is_arrayish($data_rows) ? count($data_rows) : 0;
     $rows = new PkHtmlRenderer();
     //$rows->input(['name'=>$basename,'type'=>'hidden']);
     $rows->input('hidden', $basename);

     foreach ($data_rows as $idx=>$data_row) {
       $rowargs = $args + ['data_row'=>$data_row,'idx'=>$idx];
       //$rows[$idx] = $this->mkSubformRow($rowargs);
       $rows[] = $this->mkSubformRow($rowargs);
     }
     $create_button = keyVal('create_button',$args,$this->create_button);
     if (!$create_button) {
       $cbtnargs = $args + ['item_template'=>$jsRowTpl,'data-itemcount'=>$item_count];
       $create_button=$this->mkCreateBtn($cbtnargs);
     }
     $header = keyVal('header',$args,$this->header);
     if ($header) {
       $subform_tpl['header'] = $header;
     }
     $subform_tpl['rows']=$rows;
     $subform_tpl['create'] = $create_button;
     return PkRenderer::$subform_tag($subform_tpl,$subform_attributes);
  }

  public function mkBtn($label,$opts=[]) {
    $defaults = [
        'class' => 'pkmvc-button inline',
    ];
    $atts = merge_attributes($defaults,$opts);
    $tag =trim(keyVal('tag',$atts,'div'));
    unset($atts['tag']);
    return PkRenderer::$tag($label,$atts);
  }
  public function mkLinkBtn($route,$label=null,$params=[], $opts=[]) {
      $opts['href'] = route($route,$params);
      $opts['tag'] = 'a';
      return $this->mkBtn($label,$opts);
  }

  public function mkSubformRow($args=[]) {
     $row_tpl = keyVal('row_tpl',$args,$this->row_tpl);
     if ($row_tpl instanceOf PartialSet) {
       $row_tpl = $row_tpl->copy();
     } else {
       $row_tpl = new PkHtmlRenderer();
     }
     $delete_button = keyVal('delete_button',$args,$this->delete_button);
     if (!$delete_button) $delete_button = $this->mkDelBtn();
     $row_tag = keyVal('row_tag',$args,$this->row_tag);
     $row_attributes = merge_attributes(
         keyVal('row_attributes',$args,$this->row_attributes),'deletable-data-set');
     $tpl_fields = keyVal('tpl_fields',$args,$this->tpl_fields);
     $basename = keyVal('basename',$args,$this->basename);
     $idx = keyVal('idx',$args);
     $data_row = keyVal('data_row',$args,[]);
     foreach ($tpl_fields as $fname => $inputdef) {
       $row_tpl[$fname]=$this->mkInputFromDef([
           'name' => $this->mkInputName($fname,$idx,$basename),
           'def'=>$inputdef,
           'value'=>keyVal($fname,$data_row),
        ]);
     }
     $row_tpl['delete_button'] = $delete_button;
     return PkRenderer::$row_tag($row_tpl,$row_attributes);
  }

  public function mkInputName($fname,$idx=null,$basename=null) {
    if (!$basename) $basename = $this->basename;
    if (($idx==='')|($idx===null)) $idx = '__CNT_TPL__';
    return $basename.'['.$idx.']['.$fname.']';
  }

  public function mkInputFromDef($args=[]) {
    $name=keyVal('name',$args);
    $value=keyVal('value',$args);
    $def=keyVal('def', $args);
    $type = 'text';
    $params = [];
    $options = [];
    #Parse $def - can be many forms
    if (is_string($def)) {
      $type = $def;
    } else if (is_array($def)){
      $type = keyVal('type',$def,keyVal(0,$def,'text'));
      $params = keyVal('params',$def,keyVal(1,$def,$def));
      $options = keyVal('options',$params,$params);
    }
    $inp = new PkHtmlRenderer();
    if ($type === 'select') {
      $list = keyVal('list',$params);
      unset($params['list']);
      return $inp->select($name,$list,$value,$options);
    } else if ($type === 'textarea') {
      return $inp->textarea($name,$value,$options);
    } else if ($type === 'boolean') {
      return $inp->boolean($name,$value,$options);
    } else {
      return $inp->input($type,$name,$value,$options);
    }
  }

//$field_arr,$idx=null,$basename=null,$row_tpl = null
  /**
   * Makes a subform row
   * @param assoc array $args:
   *   'field_input_arr': assoc array of field key names=>values, or just indexed array of field names
   *   'idx': The integer index of the row, or '__CNT_TPL__' if in the 'create' template
   *   'basename' - The basename to use for the form input name
  public function mkSubformRow($args = []) {

  }
   */




  /** These methods can insert the content now, or return a keyed template for the
   * content to be inserted or replaced repeatedly
   * !! Deprecated for data-template encoding within Create Button
   * @param type $content
   * @return string
   */
  /*
  public function mkJsTemplate($content=null) {
    $ps = new PkHtmlRenderer();
      $ps['template-open']="<fieldset disabled style='display:none;' class='template-container'>\n";
      $ps['content'] = $content;
      $ps['template-close']="</fieldset>\n";
      return $ps;
  }
   * 
   */

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

  public function mkBsMenu($links = [],$opts = []) {
    $nav_class=keyVal('nav_class',$opts,'pk-nav');
    $nav_ul_class=keyVal('nav_ul_class',$opts,'flex-row');
    $menu = new PkHtmlRenderer();
    $menu[]= "
      <nav class='$nav_class navbar navbar-inverse no-print'>";
    $menu[]= " <ul class='nav navbar-nav $nav_ul_class'>\n";
    foreach ($links as $link) {
      $menu[]="
    <li class='nav-item'>
      $link
    </li>\n";
    }
    $menu[] = " </ul>";
    $menu[] = " </nav>\n";
    return $menu;
  }


  /** Takes a PkModel instance & $attname, and formats/wraps them as
   * a label & value. If $tpl is provided, should have keys for 'pk_lbl' &
   * 'pk_val'. Otherwise, a default is constructed
   * 
   * @param PkModel $model
   * @param string $attname
   * @param PartialSet $tpl
   * @return stringable HTML representation
   */
  public function mkAttDesc($model,$attname, $tpl = null) {
    if (!$model instanceOf PkModel) return null;
    if (!ne_string($attname)) return null;
    if ($tpl instanceOf PartialSet) {
      $tpl = $tpl->copy();
    } else {
      $tpl = new PkHtmlRenderer();
      $tpl[] = "<div class='pk-wrapper'>\n<div class='pk-lbl'>\n";
      $tpl['pk_lbl'] = null;
      $tpl[]="\n</div>\n<div class='pk-val'>\n";
      $tpl['pk_val'] = null;
      $tpl[]="\n</div>\n</div>\n";
    }
    $tpl['pk_lbl'] = $model->attdesc($attname);
    $tpl['pk_val'] = $model->$attname;
    return $tpl;
  }


/** Makes an initially hidden jQueryUI dialog box template, that will be popped
 * up on page load by pklib.js function.
 * @param stringish $content - HTML box to wrap in popup dialog template.
 * @param PartialSet $wrapTpl - Optional template - insert $content at $wrapTpl['content']
 * @return PkHtmlRenderer - wrapped content
 */
  public static function mkPopUp($content) {
    return PkRenderer::rawdiv($content,'jqui-dlg-pop-load-wrapper');
  }

  /** Examines session('errorBag') - if empty, nothing, else creates pop up
   * qQuery dialog box with error content
   */
  public static function mkErrorPopUp() {
    //$errorMsgBag = session('errorBag');
    //$errorMsgBag = session('errors');
    $errorSet = session('errors');
    if (!$errorSet) return null;
    if ($errorSet instanceOf ViewErrorBag) {
      $errorBags = $errorSet->getBags();
    } else if ($errorSet instanceOf MessageBag) {
      $errorBags = [$errorSet];
    }
    $allMsgs = [];
    foreach ($errorBags as $errorBag) {
      foreach($errorBag->all() as $msg) {
        $allMsgs[] = $msg;
      }
    }
    
//    $errors = $errorMsgBag->get('error');
    if (!$allMsgs || !is_array($allMsgs)) {
      pkwarn("Got an Error Msg bag with invalid 'errors':",$allMsgs);
      return null;
    }
    $errorItems = new PkHtmlRenderer();
    foreach ($allMsgs as $error) {
      $errorItems->rawli($error);
    }
    $errorList = PkRenderer::ul($errorItems,'popup error-list');
    $errorMsgOut = PkRenderer::div($errorList,
        ['class'=>"error-popup-box",
            'data-title'=>"Notice:",
            'data-dialogClass'=>'pk-warn-dlg error-dlg-box']);
        /*
        [
        PkRenderer::div("The following errors occurred",'error-head'),
        $errorList,
    ]
         * 
        $errorList,
        ,['class'=>"error-popup-box", 'data-title'=>"The following Errors Occurred", 'data-dialogClass'=>'error-dlg-box']);
         */
    return static::mkPopUp($errorMsgOut);
  }

  #####################  Packaging HTML for Dialogs & Popus, in data atts, etc

  /** Wraps content to be appropriate for a jQuery/pklib.js Pop Up Dialog */
  public function wrapDlg($content,$args=[]) {
    if (!$args) $args = [];
    if (ne_string($args)) $args = ['class'=>$args];
    $atts = [];

    foreach ($args as $key => $value) {
      if (removeStartStr($key,'data-') || 
        in_array($key,static::$dialogAtts)) {
          $atts[$key] = $value;
      } 
    }

    $extraClass = keyVal('extra_class',$args);
    $atts = merge_attributes($atts, $extraClass);

    return PkRenderer::div($content,$atts);
  }

  /** Takes packaged HTML for a jQuery dialog, encodes it into an element
   * data-dialog-encoded attribute, assigns the jQuery class, & returns it,
   * ready to click on & pop.
   * @param type $dlg
   * @param type $args
   */
  public function encodeDlgInEl($dlg,$args=[]) {
    if (!$args) $args = [];
    if (ne_string($args)) $args = ['class'=>$args];
    $defaults = [
        'class' => 'pkmvc-button',
        'content'=>'Click',
        'tag'=>'div',
    ];
    $requiredClass = 'js-dialog-button';
    $params = array_merge($defaults,$args);
    $extraClass = keyVal('extra_class',$params); unset($params['extra_class']);
    $content = $params['content']; unset($params['content']);
    $tag = $params['tag']; unset($params['tag']);
    $atts = merge_attributes($params,"$requiredClass $extraClass");
    $atts['data-dialog-encoded'] = html_encode($dlg);
    return PkRenderer::$tag($content,$atts);

  }

  ###########  Input & form element wrappers  ************


  public $labelAttributes = 'block tpm-label';
  public $valueAttributes = 'block tpm-value';
  public $wrapperAttributes = 'tpm-wrapper';

  public $defaultColClass = 'col-sm-2';



  /** The BS Column Class (if any) should is a separate param, 'wrapperColClass'
   * Default is 'col-sm-2', but any 'wrapperColClass' will replace it, including
   * if the key exists but is empty.
   * 
   * @param type $inp
   * @param type $lbl
   * @param array|string $args - if string, the BS Col Class
   * @return type
   */
  public function pair($inp,$lbl=null,$args=[]) {
    $defaults = [
        'labelAttributes' => $this->labelAttributes,
        'valueAttributes' => $this->valueAttributes,
        'wrapperAttributes' => $this->wrapperAttributes,
    ];
    if (is_array($args) && array_key_exists('wrapperColClass',$args)) {
      $wrapperColClass = $args['wrapperColClass'];
    } else if (!is_array($args)){ #Default is [], so explicitly set, also to null.
      $wrapperColClass = $args;
    } else {
      $wrapperColClass = $this->defaultColClass;
    }
    return PkRenderer::rawwrap([
        'value'=>$inp,
        'label'=>$lbl,
        'labelAttributes'=>merge_att_arrs('labelAttributes',$defaults,$args),
        'valueAttributes'=>merge_att_arrs('valueAttributes',$defaults,$args),
        'wrapperAttributes'=>merge_attributes(merge_att_arrs('wrapperAttributes',$defaults,$args),$wrapperColClass),
    ]);
  }


  ################   Generic Builders ################
  public $defaultTblClass = 'pk-tbl';

  /** Just the simplest of table generators for q & d output. If $header 
   * 
   * @param 2 dimen array, $data - even if empty.
   * @param array|scalar|null $header
   */
  public function mkTbl($data=[],$header=null,$args=null) {
    if ($header && is_simple($header)) {
      $header = [$header];
    }
    $fullRow = (count($header)===1) ? ['colspan'=>99] : [];
    $thb = new PkHtmlRenderer();
    foreach ($header as $th) {
      $thb->rawth($th,$fullRow);
    }
    $trb = PkRenderer::tr($thb);
    foreach ($data as $dr) {
      $tdb = new PkHtmlRenderer();
      foreach ($dr as $td) {
        $tdb->rawtd($td);
      }
      $trb->tr($tdb);
    }
    return PkRenderer::table($trb,$this->defaultTblClass);
  }

  /** It makes a form based on options, but als passes back a PartialSet template that the 
   * calling function can then populate 
   */
  public function mkFrm() {

  }


#Close class
}




/** Examples:
 * 
#Diagnoses
$diagnoses = $client->diagnoses;
$diagrows = [];
foreach ($diagnoses as $diagnosis) {
  $diagrows[]= ['diagnosiscode_id' => $diagnosis->diagnosiscode_id,'id'=>$diagnosis->id];
}
#New HtmlPainter for Diagnosis Subform

$cdrow_tpl = new PkHtmlRenderer(["<div class='col-sm-9'>",
    'diagnosiscode_id'=>null,
    "</div><div class='col-sm-3'>",'delete_button'=>null,"</div>\n"]);
$params = [
    'basename'=>'diagnoses',
    'row_attributes'=>'row',
    'row_tpl'=>$cdrow_tpl,
    'tpl_fields' => [
        'id'=>'hidden',
        'diagnosiscode_id'=>['select',['list'=>DiagnosisRef::getSelectList(true,true),
            'options'=>['class'=>'form-control pk-val']]],
        ],
    'data_rows' => $diagrows,
];

$diagsf = new PkHtmlPainter($params);
$diagwrapper = PkRenderer::div([
  PkRenderer::div('Diagnoses','pk-h5 pk-label sect-head client-info form-sect'),
  PkRenderer::content($diagsf->mkSubform()),
], 'section inline multiform diagnoses-subform');

 */
