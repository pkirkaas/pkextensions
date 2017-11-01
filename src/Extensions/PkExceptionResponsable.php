<?php
/** A user exception that displays the exception message to the user in the
    context of the app template. Use MsgBag
    */
namespace PkExtensions;
use \Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag; #A collection of MessageBags
use Request;

class PkExceptionResponsable extends PkException implements Responsable {
  public function render($request) {
    return redirect("/")->withError(new MessageBag(['error' => $this->getMessage()]));;
  }

  public function toResponse($request) {
    return $this->getMessage();
  }
}

