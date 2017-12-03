<?php /* Generic Routes available to all applications */

Auth::routes();
Route::group(['middleware' => ['web']], function () {
Route::group(['prefix'=>'admin','middleware'=> ['auth', 'admin']],function() {
  Route::get('/btest',function () {return "<h1> Proxied Hi, kid</h1><h1> Proxied ";});
  //Route::any('/deletemodel', ['as' => 'admin_deletemodel'], function() {
  Route::any('/deletemodel',['as'=>'admin_deletemodel',  function() {
    $data = Request::all();
    $model = $data['model'];
    $id = to_int ($data['id']);
    $instance = $model::find($id);
    $instance->delete(true);
    return redirect()->back();
  }]);

  Route::any('/orphans', ['as' => 'admin_orphans',
   //function(){return view('admin.orphans');},
      'uses' => 'AdminController@orphans',
      'type'=>'admin', 'desc'=>'Manage Orphans']);
});
//Non - admin roles
Route::any('auth/logout', ['as' => 'auth_logout', 
      function() {
        Auth::logout();
        return redirect()->route('home');
      }
  ]);

//Ajax Routes for common PkAjaxController
Route::any('ajax/delete', ['as' => 'ajax_delete', 'uses'=> 'AjaxController@delete']);

});
