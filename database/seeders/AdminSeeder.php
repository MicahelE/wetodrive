<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Make the first user an admin
        $firstUser = User::first();
        if ($firstUser) {
            $firstUser->update(['role' => 'admin']);
            $this->command->info("User '{$firstUser->name}' has been made an admin.");
        } else {
            $this->command->warn('No users found to make admin.');
        }
    }
}