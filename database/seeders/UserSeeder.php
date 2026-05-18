<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        $this->command->warn(PHP_EOL.'Creating Admin User...');
        $name = config('app.admin_name');
        $phone = config('app.admin_phone');
        $email = config('app.admin_email');
        $password = config('app.admin_password');
        $admin = User::query()->create([
            'account_no' => 'ACC'.str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT),
            'name' => $name,
            'username' => Str::slug($name),
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($password),
            'remember_token' => Str::random(10),
        ]);

        $admin->is_admin = true;
        $admin->status = 'active';
        $admin->email_verified_at = now();
        $admin->save();
        $this->command->info("✓ Admin: {$name} created.");
    }
}
