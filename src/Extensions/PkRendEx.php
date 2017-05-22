<?php
namespace App\Http;
use PkExtensions\PkHtmlRenderer;

class Renderer2 extends PkHtmlRenderer {
  public $numk = 0;
  public $depth = 0;
  public $myid = 0;
  public $parent = 0;
  public static $total = 0;

  public function __constrct($a=null, $b=null, $c=null, $d=null) {
    return parent::__construct($a, $b, $c, $d);
  }

  public function tagged($tag, $content = null, $attributes=null, $raw = false) {
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
      #Trust that text already wrapped in PhHtmlRenderer has already been filtered

//if (!$raw && !static::getRawCount() && !($content instanceOf PkHtmlRenderer)) {
      //$this[]=$this->spaceDepth()."<$tag ".PkHtml::attributes($attributes).">
      $this->rawcontent($this->spaceDepth()."<$tag ".PkHtml::attributes($attributes).">");
      if (is_array($content)){
        foreach ($content as $citem) {
          $this->content($citem,$raw);
        }
      } else {
        $this->content($content,$raw);
      }
      $this->rawcontent($this->spaceDepth()."</$tag>\n");
      return $this;
    }
  }

}
