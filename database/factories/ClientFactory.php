<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_name' => mb_strtoupper($this->faker->company()).' S.A.C.',
            'ruc' => '20'.$this->faker->unique()->numerify('#########'),
            'address' => $this->faker->streetAddress(),
            'ubigeo' => '150101',
            'district' => $this->faker->city(),
            'phone' => $this->faker->numerify('9########'),
            'email' => $this->faker->unique()->companyEmail(),
            'contact_name' => $this->faker->name(),
            'is_active' => true,
        ];
    }
}
