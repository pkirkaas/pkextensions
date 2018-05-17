<?php /* Generic Routes available to all applications */
/**
 *  in RouteServiceProvider:
<pre>
protected function mapWebRoutes() {
  Route::middleware('web')
     ->namespace($this->namespace)
     ->group(function() {
       require base_path('vendor/pkirkaas/PkExtensions/src/Extensions/resources/Routes.php');
       require base_path('routes/web.php');
     });
}
 */
Auth::routes();
Route::group(['middleware' => ['web']], function () {
//Route::group(['prefix'=>'admin','middleware'=> ['auth', 'admin']],function() {
Route::group(['prefix'=>'admin','middleware'=> ['auth']],function() {
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
Route::any('ajax/query', ['as' => 'ajax_query', 'uses'=> 'AjaxController@query']);
Route::any('test', ['as' => 'test',  function() { ## Just echos the submitted data
    $data = Request::all();
    pkdebug("The submitted data:\n\n", $data);
    return json_encode(["The Request Data" =>$data], JSON_PRETTY_PRINT |  JSON_UNESCAPED_LINE_TERMINATORS | JSON_UNESCAPED_SLASHES);
}]);

});
