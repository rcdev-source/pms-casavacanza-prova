<?php

namespace Database\Seeders;

use App\Enums\RoomStatus;
use App\Models\Property;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomAmenity;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = collect([
            Role::ADMIN => 'Amministratore',
            Role::RECEPTION => 'Reception',
            Role::CLEANING => 'Pulizie',
            Role::MAINTENANCE => 'Manutenzione',
        ])->mapWithKeys(fn (string $name, string $key): array => [
            $key => Role::query()->firstOrCreate(['key' => $key], ['name' => $name]),
        ]);

        $property = Property::query()->firstOrCreate(
            ['name' => 'Casa Vacanze Demo'],
            [
                'description' => 'Struttura demo con quattro camere nel centro di Catania.',
                'address' => 'Via Etnea 1',
                'city' => 'Catania',
                'postal_code' => '95124',
                'province' => 'CT',
                'country' => 'IT',
                'email' => 'info@example.test',
                'phone' => '+39 095 0000000',
                'default_check_in_time' => '15:00',
                'default_check_out_time' => '10:00',
                'currency' => 'EUR',
                'timezone' => 'Europe/Rome',
            ],
        );

        $users = [
            ['email' => 'admin@example.test', 'name' => 'Admin Demo', 'role' => Role::ADMIN],
            ['email' => 'reception@example.test', 'name' => 'Reception Demo', 'role' => Role::RECEPTION],
            ['email' => 'cleaning@example.test', 'name' => 'Cleaning Demo', 'role' => Role::CLEANING],
            ['email' => 'maintenance@example.test', 'name' => 'Maintenance Demo', 'role' => Role::MAINTENANCE],
        ];

        foreach ($users as $item) {
            $user = User::query()->firstOrCreate(
                ['email' => $item['email']],
                ['name' => $item['name'], 'password' => Hash::make('Password123!'), 'email_verified_at' => now()],
            );
            $user->roles()->syncWithoutDetaching([$roles[$item['role']]->id]);
            $user->properties()->syncWithoutDetaching([$property->id]);
        }

        $amenities = collect([
            'WiFi',
            'Aria condizionata',
            'TV',
            'Bagno privato',
            'Frigorifero',
            'Balcone',
            'Asciugacapelli',
        ])->map(fn (string $name): RoomAmenity => RoomAmenity::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name],
        ));

        foreach (range(1, 4) as $number) {
            $room = Room::query()->firstOrCreate(
                ['property_id' => $property->id, 'code' => "ROOM-{$number}"],
                [
                    'name' => "Camera {$number}",
                    'description' => "Camera demo numero {$number}",
                    'max_guests' => 2,
                    'max_adults' => 2,
                    'max_children' => 1,
                    'base_price' => 85,
                    'status' => RoomStatus::READY,
                    'is_active' => true,
                ],
            );
            $room->amenities()->syncWithoutDetaching($amenities->pluck('id'));
        }
    }
}
