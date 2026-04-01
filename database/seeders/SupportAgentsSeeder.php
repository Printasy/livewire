<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SupportAgentsSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'sarah@supportdesk.test'],
            [
                'name' => 'Sarah Support',
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'tom@supportdesk.test'],
            [
                'name' => 'Tom Triage',
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'els@supportdesk.test'],
            [
                'name' => 'Els Escalation',
                'password' => Hash::make('password'),
            ]
        );
    }
}
