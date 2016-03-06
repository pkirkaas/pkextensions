<?php
namespace PkExtensions;
//use Illuminate\Html\HtmlBuilder;
use Collective\Html\HtmlBuilder;
class PkHtmlBuilder extends HtmlBuilder {
  public $name_prefix = null;
	public function attributes($attributes) {
    if ($this->name_prefix && array_key_exists('name', $attributes) ) {
      $attributes['name'] = $this->name_prefix . '['.$attributes['name'].']';
    }
    return parent::attributes($attributes);
  }
    protected function toHtmlString($html) {
      return $html;
    }
}
