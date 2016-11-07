<?php
require_once(__DIR__.'/../../app/References/AppReferenceCollection.php');
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use PkExtensions\Traits\BuildQueryTrait;
use PkExtensions\PkTestGenerator;
use PkExtensions\PkMatch;
use PkExtensions\References\ZipRef;
use PkExtensions\References\StateRef;
use App\Models\User;





class DatabaseSeeder extends Seeder {
    public function run() {
       $this->call(UserTableSeeder::class);
    }
}


class UserTableSeeder extends Seeder {
  public function run() {
    $faker = Faker\Factory::create();
    DB::table('users')->delete();
    #Make default admin
    $admindata = [];
    $admindata['email'] = 'admin@a.b';
    $admindata['name'] = 'admin';
    $admindata['password'] = Hash::make('abc');
    $admindata['admin'] = true;
    $adminuser = User::create($admindata);

#Regular user
    $userdata = [
      'email'=>'user@a.b',
      'name'=>'user',
      'password' => Hash::make('abc'),
    ];
    $user = User::create($userdata);
  }
}

