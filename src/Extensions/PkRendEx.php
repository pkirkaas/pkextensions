<?php
/** Experimenting with a couple thoughts here. If the received content in the
 * method is ME, I don't return it - I create a new instance, put it in the next slot, & return it. 
 */
namespace PkExtensions;
use PkHtml;
use PkForm;
use PkExtensions\Models\PkModel;
use PkRenderer;

class PkRendEx extends PkHtmlRenderer {
  public $myid = 0;
  public $mykids = null;
  public static $total = 0;
  public static $alltostrings=0;
  public static $totalops = 0;
  public $mytostrings = 0;
  public $parent = null;
  public $depth = 0;
  public $myops = 0;

  public function report() {
    $rep = "
      Depth: ".$this->depth."
      ID:   ".$this->myid."
      Total Instances: ".static::$total."
      Direct Kids: ".count($this->mykids)."
      My array size: ". $this->sizeOf()."
      Num Ops: ".$this->myops."
      My Depth: ".$this->depth."
      This Key: ".$this->key()."
        ";
       if ($this->parent instanceOf static) {
        $rep.="Parent Key: ".$this->parent->key()."
          ";
       }
      return $rep;
    }

  //public function __construct() {
    /*
    $this->myid = static::$total;
    $this->mykids = new PartialSet();
     */
   // return parent::__construct();
  //}
  public function announce($msg) {
    $msg.="\n\n".$this->report();
    //pkdebug ($msg);
  }

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

    $this->announce($this->myid." spawned:".
        count($this->mykids));
     return $child;
    }
    public function __toString() {
      /*
      $id = $this->myid;
      $all = static::$total;
      $allstr = static::$alltostrings++;
      $mystr = $this->mytostrings++;
      $depth = $this->depth;
      $rep = "\n\n#
        About to To Str:
        ID:  $id;
        All Instances: $all;
        My toStrs: $mystr;
        All toSrings: $allstr;
        Depth:  $depth;
        ";
       * 
       */
       //  pkdebug("No Special Objects");
      /*
      //pkdebug("OUT of tostr");
       error_log("To String");
       */
           return  parent::__toString();
         /*
         pkdebug("#ID  $id, depth: $depth,
           alltostrs: ".static::$alltostrings ."
             : Finishefd __toString\n");
         return $str;
 */
   }

  public function tagged($tag, $content = null, $attributes=null, $raw = false) {
    $rep = $this->report();
    //pkdebug("Can I write now? .. $rep  ");
    $ctype = typeOf($content);
    //if (! is_simple($content)) pkdebug("Type of Content: [$ctype]");
    $attributes = $this->cleanAttributes($attributes);
    if (($content === true) || ($content === $this)) { #That's RENDEROPEN === TRUE
      $spaces = $this->spaceDepth();
      $size = $this->addTagStack([$tag=>['raw'=>$raw]]);
      return $this->rawcontent("$spaces<$tag ".PkHtml::attributes($attributes).">");
    } else if (($content === false)) {
                                ##Nest the elements
      $spaces = $this->spaceDepth();
      $size = $this->addDepthTagStack($tag);
      return $this->rawcontent("$spaces<$tag ".$this->attributes($attributes).">");
    } else {
      #2107 - so it's valid content - make a child, put the content in the child, put
      #the child in the next slot, then put the closing tag in our slot after the child,
      #AND RETURN THE CHILD. Then anythin that comes into the child is still enclosed 
      #in our tags..pput it in a child, put the closing tag afterwords,
      #Trust that text already wrapped in PhHtmlRenderer has already been filtered

//if (!$raw && !static::getRawCount() && !($content instanceOf PkHtmlRenderer)) {
      //$this[]=$this->spaceDepth()."<$tag ".PkHtml::attributes($attributes).">
      $this->rawcontent($this->spaceDepth()."<$tag ".PkHtml::attributes($attributes).">");

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
    }
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
    $this[] = new self([$content]);
    return $this;
  }

}
