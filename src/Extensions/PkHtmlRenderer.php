<?php
namespace PkExtensions;
use PkHtml;
use PkForm;

class PkHtmlRenderer extends PartialSet {
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

  public function tag($tag, $content = '', $attributes = []){
    $value = PkHtml::tag($tag, $content, $attributes);
    $this[] = $value;
    return $this;
  }

  public function div($content = '', $attributes = []) {
    return $this->tag('div', $content, $attributes);
  }
  

}
