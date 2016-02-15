<?php
namespace App\Extensions;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Mail\MailQueue as MailQueueContract;
use Illuminate\Mail\Message;
class FakeMailer implements MailerContract, MailQueueContract {
  public function send($template, array $params, $closure) {
    /** Write the supposed email to a log file for checking
     * 
     * @param type $template
     * @param array $params
     * @param type $closure
     */
    $msg = new Message(new Universal());
    $closure($msg);
    $emailFile = getAppLogDir() . '/testEmail-' . time() . "template-$template.htm";
    pkdebug("IN FAKE MAILER SEND; writing to: [$emailFile]!");
    $fp = fopen($emailFile, "w");
    $str = '';
    $str .= "<pre>\n";
    $str .= print_r($msg, 1);
    $str.="</pre>\n\n";
    $str .= view($template, $params);
    fwrite($fp, $str);
  }

  public function raw($text, $callback) { }
  public function failures() { }
  public function queue($view, array $data, $callback, $queue = null) { }
  public function later($delay, $view, array $data, $callback, $queue = null) {}
}