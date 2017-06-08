<?php namespace PkExtensions;
use Illuminate\Validation\Validator;
use Symfony\Component\Translation\TranslatorInterface;
use Illuminate\Contracts\Translation\Translator;
/** Custom Validator for Laravel */
class PkValidator extends Validator {
    /**
     * Create a new Validator instance.
     *
     * @param  \Symfony\Component\Translation\TranslatorInterface  $translator
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return void
     */
    public function __construct(Translator $translator, array $data, array $rules, array $messages = [], array $customAttributes = []) {
      foreach ($rules as $att => $rule) {
        if (!$rule || ($rule === 'skip')) {
          unset ($data[$att]);
          unset ($rules[$att]);
        }
      }
      //pkdebug("Making PkValidator: Translator:", $translator, "Data:", $data, "Rules:", $rules, "Messages:", $messages, "CustomAtts:", $customAttributes);
       parent::__construct($translator, $data, $rules, $messages, $customAttributes);
       //$this->setCustomMessages(['id.test'=>"Failed ID Test!", 'test'=>'Failed the Test']);
    }
  public function validateTest2 ($attribute, $value, $params = null) {
    //pkdebug ("In ValidateTest: attriubute: [$attribute], value: [$value]; Params:", $params);
    $errorMsg = "No, Not, not Valid!";
    $this -> setCustomMessages(['test2'=>"Quite a problem"]);
    return false;
  }

    /** 
     * The uniquecombo validator expects $field_name to be 'id', $value to be the
     * value of the existing ID in the table if an update, or empty if a new entry.
     * $paramaters are an indexed array, first the table name, then the field names
     * to compare for uniqueness.
     */
  public function uniquecombo($field_name, $value, $parameters = null) {
     if (!is_array($parameters) || !sizeOf($parameters)) throw new \Exception("No Parameters passed to UniqueCombo!");
      $table_name = array_shift($parameters);
      $field_names = $parameters;
      $data = $this->getData();
      #Get the subset of $data with keys in $field_names:
      $compareFields = array_intersect_key($data, array_flip($field_names));
      //pkdebug("CompareFields:", $compareFields);
      $builder=DB::table($table_name);
      if (Schema::hasColumn($table_name, 'deleted_at')) { #Remove from query
        $builder = $builder->whereNull('deleted_at');
      }
      foreach ($compareFields as $fname => $fval) {
        $builder = $builder->where($fname,'=',$fval);
      }
      $arrOfObjs = $builder->get();
      $arrOfArrs = json_decode(json_encode($arrOfObjs), true);
      if (!$arrOfArrs || !sizeOf($arrOfArrs)) return true;
      if (sizeOf($arrOfArrs) > 1) {
        $this -> setCustomMessages(['uniquecombo'=>"Failed Unique Combo step 1"]);
        return false;
      }
      $row = $arrOfArrs[0];
      if ($row[$field_name] != $value) {
        $this -> setCustomMessages(['uniquecombo'=>"Failed Unique Combo step 2"]);
        return false;
      }
      //pkdebug("FieldName: [$field_name], value: [$value], ROW:", $row);
      return true;
  }
  protected function isValidatable($rule, $attribute, $value) {
    //pkdebug("IN isValidatable, RULE:", $rule);
    if (is_string($rule) && (strtolower($rule)==='sum')) return true;
    return parent::isValidatable($rule, $attribute, $value);
  }

  public function validateAttribute($attribute, $rule) {
    $value = $this->getValue($attribute);
   // pkdebug("In Val Att, Att:",$attribute,'value', $value);
    $res = parent::validateAttribute($attribute, $rule);
    //pkdebug("In Val Att, Att:",$attribute,'value', $value, 'RES:', $res);
    return $res;
  }

  /** Attributes separated by '+', or single, followed by '+'
   *  Makes sure the total for the attributes is more than $params[0] - $sum
   * @param string $attribute ex: 'userfee+' or 'userfee+insfee', etc. 
   * @param mixed $value - not used
   * @param array $params - [minsum = 0, "Optional Custom Message"]
   */
  public function validateSum($attribute,$value,$params) {
    //pkdebug("In validatesum, w params::" ,$params, "value:", $value, "Att:", $attribute);
    if (count($params)) {
      $sum = $params[0];
    } else {
      $sum = 0;
    }
    if (strpos($attribute,'.') !== false) {
      $arrpart = substr($attribute, 0, strrpos($attribute, '.')+1);
      $attpart = substr($attribute,  strrpos($attribute, '.')+1);
    } else {
      $arrpart = '';
      $attpart = $attribute;
    }
    //pkdebug("arrpart: [$arrpart]; attpart: [$attpart]");
    $atts = explode('+', $attpart);
    $total = 0;
    $attkeys = ' ';
    foreach ($atts as $att) {
      if ($arrpart) $key = $arrpart.$att;
      else $key = $att;
      $value = $this->getValue($key);
      //pkdebug("For key: [$key], Value: [$value]");
      if (!is_numeric($value)) continue;
      $total += $value;
      $attkeys .= "$att ";
    }
    if ($total <= $sum) {#Fail
        $msg = keyVal(1,$params,"Total of $attkeys must be more than $sum");
        $this -> setCustomMessages(["{$attribute}.sum"=>$msg]);
        return false;
    }
    return true;
  }
}
