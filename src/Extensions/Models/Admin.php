<?php
namespace PkExtensions\Models;
class Admin extends PolymorphicUser {
  public $viewable = false;
  public function fullname() {
    return $this->user->name;
  }
}
