<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
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
  /**
   * @param string $model - fully qualified model name subclass of PkModel
   * @param integer $id - the ID of the instance
   * @return 
   */
  public function deletemodel() { 
    $data = request()->all();
    $model = $data['model'];
    if (!is_subclass_of($model,PkModel::class)) {
      $err = "Model not subclass of PkModel? Model: [$model]";
      pkdebug($err);
      return redirect()->back()->with('errors',$err);
    }
    $id = to_int ($data['id']);
    $instance = $model::find($id);
    if (!$instance) {
      $err = "Didn't find instance of Model: [$model] with ID: [$id]";
      pkdebug($err);
      return redirect()->back()->with('errors',$err);
    }
    $instance->delete(true);
    return redirect()->back();
  }

  public function tools() {
    return view('admin.tools');
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
    /*
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
     * */
    return view('admin.treeedit');
    //return view('admin.treeedit',["treePanes"=>$treePanes]);
  }
  /** This is a single pane, used to edit a single tree file */
  public function _treeEditPane($treeName) {
  }



  
}
