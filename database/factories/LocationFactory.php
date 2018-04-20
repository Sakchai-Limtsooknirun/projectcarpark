<?php

use Faker\Generator as Faker;

$factory->define(App\Location::class, function (Faker $faker) {
    return [
      'location' => $faker->city,
      'address' => $faker->address,
    ];
});