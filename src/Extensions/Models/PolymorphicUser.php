<?php
namespace App\Extensions\Models;
use Illuminate\Database\Eloquent\Model;
/**
 * Common Abstract base class for the different user types, Lender & Borrower, say,
 * which are both one-to-one polymorphic relations to User
 *
 * @author Paul Kirkaas
 */
abstract class PolymorphicUser extends PkModel {
    function user() {
      return $this->morphOne('App\User','type');
    }
    public function __construct(array $attributes = []) {
      $this->fillable=$this->getAttributeNames();
      unset($this->fillable['id']);
      return parent::__construct($attributes);
    }

  public function isAdmin(self $poly = null) {
    if ($this->type() !== 'Admin') return false;
    $user = $this->getUser();
    if (!$user->admin) return false;
    return true;
  }


    public function getUser() {
      if ($this->user_id) {
        $user = User::find($this->user_id);
        return $user;
      } else {
        $user = $this->user;
        $this->user_id = $user->id;
        $this->save();
        return $this->user;
      }
    }
    public function setUser($userorid) {
      if ($userorid instanceOf User) {
        $this->user_id = $userorid->id;
      }
      if ($userorid && is_numeric($userorid)) $this->user_id = $userorid;
      //save?
    }

  public function type() {
    return getBaseName($this);
  }
  /** Possibly dangerous.... */
  /*
  public function save(Array $opts = []) {
    $this->user->save();
    return parent::save($opts);
  }
   * 
   */
}
