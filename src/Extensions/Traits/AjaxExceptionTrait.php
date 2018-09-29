<?php
/*
 * Use in App\Exceptions\Handler to return default Ajax exception if unhandled
 * ex:
use PkExtensions\Traits\AjaxExceptionTrait;
class Handler extends ExceptionHandler {
  use AjaxExceptionTrait;
    ...
  public function render($request,Exception $exception) {
    return $this->traitRender($request, $exception);
  }
 */
namespace PkExtensions\Traits;
use PkExtensions\PkAjaxController;
/**
 * @author pkirkaas
 */
trait AjaxExceptionTrait {
  public function traitRender($request, \Exception $exception) {
    $segs = $request->segments();
    if ($segs && is_array($segs) && ($segs[0]==='ajax')) {
      $status = [498=>'exception'];
      $data = [
        'exception'=>$exception->getMessage(),
        'exceptiontype' => get_class($exception),
        'requestdata'=>$request->toArray(),
        'url'=>$request->fullUrl(),
        'error'=>'exception',
        //'exceptiontrace'=>array_slice($exception->getTrace(),0,3),
        ];
      return PkAjaxController::sjsonresponse($data,$status);
    }
    return parent::render($request, $exception);
  }
}
