<?php
/** Provides some base, common admin functionality, in conjunction with routes
 * & views in PkExtensions/resources
 */
namespace PkExtensions;
use App\Models\User;
use PkExtensions\Models\PkModel;
use Auth;
Abstract class PkAdminController extends PkController {
  /** Finds "Orphans", identifies their missing "parents", & allows deletion */
  public $submenu_routearr = ['admin_orphans'];
  public function orphans() {
    return view('admin.orphans');
  }



  
}
