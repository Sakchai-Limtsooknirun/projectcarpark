<?php

use Illuminate\Database\Seeder;

class CurrentMapsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      $cur = new App\Current_map;
      $cur->id_user = 6;
      $cur->id_map = 7;
      $cur->password = 'FhqOSkCfdCPVtICtMpZYqyRZly8OO7vre5dzKv0QVzGeOykr9LT2fs1JBfXAV8Z2';
      $cur->status="empty";
      $cur->save();
    }
}
