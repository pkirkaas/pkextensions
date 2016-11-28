<?php
namespace PkExtensions;
use Illuminate\Database\Seeder;
/** Just a few helpers to preserve data we want to keep
 * Usage: in your App DatabaseSeeder, extend PkSeeder in the main DatabaseSeeder
   class, and all the Seeder classes: 

Example:
class DatabaseSeeder extends PkSeeder {
  public function run() {
    if (!defined('DEBUG')) define('DEBUG', true);
    PkLibConfig::setSuppressPkDebug(false);
    appLogPath(__DIR__ . "/seed.log");
    $user = User::find(2); #For example
    if($user) {
      static::$tablesAndKeys=$user->getTablesAndKeys();
    }
    //....
  }
}

class ClientTableSeeder extends PkSeeder {
  public function run() {
    //DB::table('clients')->delete();
    $this->delete_test('clients');
    //$users = User::get()->all();
    $users = $this->get_tests('User');
    $data = [];
    foreach($users as $user) {
      $data['user_id'] = $user->id;

 */

abstract class PkSeeder extends Seeder {
  public static $tablesAndKeys = [];
  public function delete_test($tableName) {
    $keydata = keyVal($tableName,static::$tablesAndKeys,[]);
    pkdebug("For tbl [$tableName], keyData:",$keydata);
    $keys = keyVal('keys',$keydata,[]);
    $keyName = keyVal('key_name',$keydata);
    if (!$keys || !count($keys) ||!$keyName) {
      pkdebug("Delete All");
      DB::table($tableName)->delete();
    } else { #Delete Where Not In
      DB::table($tableName)->whereNotIn($keyName,$keys)->delete();
    }
  }

  public function get_tests($className) {
    $className = 'App\\Models\\'.$className;
    $tableName = $className::getTableName();
    $keys = keyVal('keys',keyVal($tableName,static::$tablesAndKeys),[]);
    $keyName = keyVal('key_name',keyVal($tableName,static::$tablesAndKeys));
    pkdebug("For class [$className], keyName: [$keyName], keys:",$keys);
    if (!$keys || !count($keys) ||!$keyName) {
      pkdebug("Get All [$className]");
      return  $className::get();
    } else { #Don't return real data
      return  $className::whereNotIn($keyName,$keys)->get();
    }
  }
}
