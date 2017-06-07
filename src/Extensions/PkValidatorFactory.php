<?php namespace PkExtensions;
use Illuminate\Validation\Factory;

/** The purpose of this ValidatorFactory extension is to bind custom validation rule closures to the
 * validator instance. In particular, so we can get access to the other data parameters within the 
 * closure by using $this->getData() from the Validator.
 * @Author: Paul Kirkaas (paul.kirkaas@disney.com)
 * @Date: 19 June 2015
 */
class PkValidatorFactory extends Factory {

  /*
  public function make(array $data, array $rules, array $messages = [], array $customAttributes = []) {
    $validator = parent::make($data, $rules, $messages, $customAttributes);
    // We assume all custom extend closures would like to bind with the validator - can't hurt. Override
     // if not the case 
    $extensions = $validator->getExtensions();
    foreach ($extensions as $extensionName => $extension) {
      if ($extension instanceOf \Closure) {
        $extension = $extension->bindTo($validator);
        $validator->addExtension($extensionName, $extension);
      }
    }
    return $validator;
  }
  */

    /**
     * Resolve a new PkValidator instance.
     *
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return \Illuminate\Validation\Validator
     */
    protected function resolve(array $data, array $rules, array $messages, array $customAttributes)
    {
        if (is_null($this->resolver)) {
            return new PkValidator($this->translator, $data, $rules, $messages, $customAttributes);
        }

        return call_user_func($this->resolver, $this->translator, $data, $rules, $messages, $customAttributes);
    }

}
