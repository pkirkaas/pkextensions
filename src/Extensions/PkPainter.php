<?php
namespace PkExtensions;
class PkPainter {
/** Initializes/resets any member attributes that have a key in $args,
 * then calls parent construct with NO args.
 * @param type $args
 */
  public function __construct($args = []) {
    foreach ($args as $key => $val) {
      if (property_exists($this,$key)) {
        $this->$key = $val;
      }
    }
  }
  public $labelAttributes = 'block tpm-label';
  public $valueAttributes = 'block tpm-value';
  public $wrapperAttributes = 'tpm-wrapper';

  /**
   * Wraps two elements, each in a containing element, the wraps the pair in
   * a third, containing element. The optional attributes are MERGED with the
   * default attributes, but the default attributes can be set on construct.
   * @param html $val
   * @param html $lbl
   * @param array $args - optional attributes for each of the 3 elements, keyed:
        'labelAttributes' => []
        'valueAttributes' => []
        'wrapperAttributes' => []
   *   
   */


  public function tree() {
    return new PkTree();
  }

  public function pair($val,$lbl=null,$args=[]) {
    $defaults = [
        'labelAttributes' => $this->labelAttributes,
        'valueAttributes' => $this->valueAttributes,
        'wrapperAttributes' => $this->wrapperAttributes,
    ];
    if (ne_string($args)) { #If just a string, it's the wrapper class
      $args=['wrapperAttributes' => ['class'=>$args]];
    }
    $labelAttributes = merge_att_arrs('labelAttributes',$defaults,$args);
    $valueAttributes = merge_att_arrs('valueAttributes',$defaults,$args);
    $wrapperAttributes = merge_att_arrs('wrapperAttributes',$defaults,$args);
    $labelTag = keyVal('tag', $labelAttributes,'div');
    unset($labelAttributes['tag']);
    $wrapTag = keyVal('tag', $wrapperAttributes,'div');
    unset($wrapperAttributes['tag']);
    $valueTag = keyVal('tag', $valueAttributes,'div');
    unset($valueAttributes['tag']);
    $pkt = new PkTree();
    $pkt->$labelTag($lbl, $labelAttributes);
    $pkt->$valueTag($val, $valueAttributes);
    //return $this->fresh()->$wrapTag($pkt, $wrapperAttributes);
    return (new PkTree())->$wrapTag($pkt, $wrapperAttributes)->up();
  }

  /**
   * Wraps/nests an $el as specified by $opts 
   * @param stringable|array $el - text or dom element or array of elements to be wrapped
   *   if array, entire array nested / wrapped as specified by $opts
   * Example: $this->nest(['<h2>Simple Text</h2>','<h3>H3 Header</h3>'],'section');
   * @param string|array $opts - simplest, if just string, wrap $el
      * Example: $tpp->wrap('Simple Text','section')=>"<div class'section'>Simple Text</div>"
   * in a div of class $opts -
   * if $opts array, might have 'tag' key - the rest are html attributes
   * Example: $opts = ['tag'=>'h2', 'class'=>'site-header',...]
   *   return "<h2 class='site-header'>$el</h2>"
   * 
   */
  public function nest($el,$opts = []) {
    $ret = new PkTree();
    if (is_simple($opts)) {
      //return $ret->div($el,$opts);
      $ret->div($el,$opts);
      return $ret;
    }
    if (is_array($opts)) {
      $tag = keyVal('tag',$opts,'div');
      unset($opts['tag']);
      //return $ret->$tag($el, $opts);
      $ret->$tag($el, $opts);
      return $ret->up();
    }
    throw new \Exception("Invalid argument for OPTS: ".print_r($opts,1));
  }


}
