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

  /** This returns a whole complete page of treeEditPanes,
   * called & returned by
   * the application's admin/treeedit action - like:
   * public function treeeditpage() {
   *   return _treeEditPage(["skills","interests","locations"]);
   * //or if just one:
   *   return _treeEditPage("skills");
   * }
   */
  public function _treeEditPage($treeNames = []) {
    if (ne_string($treeNames)) {
      $treeNames = [$treeNames];
    }
    $treePanes = ["<h1>NO VALID TREE NAMES PASSED</h1>"];
    if (ne_array($treeNames)) {
      $treePanes = [];
      foreach ($treeNames as $treeName) {
        $treePanes[] = $this._treeEditPane($treeName);
      }
    }
    return view('admin.treeedit',["treePanes"=>$treePanes]);
  }
  /** This is a single pane, used to edit a single tree file */
  public function _treeEditPane($treeName) {
  }



  
}
