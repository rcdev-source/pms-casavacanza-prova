<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('postal_code', 20);
            $table->string('province', 2);
            $table->string('country', 2)->default('IT');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->time('default_check_in_time')->default('15:00');
            $table->time('default_check_out_time')->default('10:00');
            $table->text('cancellation_policy')->nullable();
            $table->text('house_rules')->nullable();
            $table->char('currency', 3)->default('EUR');
            $table->string('timezone')->default('Europe/Rome');
            $table->timestamps();
        });

        Schema::create('property_user', function (Blueprint $table): void {
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['property_id', 'user_id']);
        });

        Schema::create('rooms', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 40);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('max_guests');
            $table->unsignedSmallInteger('max_adults');
            $table->unsignedSmallInteger('max_children')->default(0);
            $table->decimal('base_price', 10, 2);
            $table->string('status', 30)->default('READY')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['property_id', 'code']);
        });

        Schema::create('room_amenities', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('room_amenity_room', function (Blueprint $table): void {
            $table->foreignUlid('room_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('room_amenity_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['room_id', 'room_amenity_id']);
        });

        Schema::create('guests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->char('nationality', 2)->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 40)->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->char('country', 2)->nullable();
            $table->timestamps();
        });

        Schema::create('guest_property', function (Blueprint $table): void {
            $table->foreignUlid('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['guest_id', 'property_id']);
        });

        Schema::create('guest_documents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('guest_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 40);
            $table->text('document_number');
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->char('issuing_country', 2)->nullable();
            $table->string('file_path', 500);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('document_access_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('guest_document_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('accessed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_access_logs');
        Schema::dropIfExists('guest_documents');
        Schema::dropIfExists('guest_property');
        Schema::dropIfExists('guests');
        Schema::dropIfExists('room_amenity_room');
        Schema::dropIfExists('room_amenities');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('property_user');
        Schema::dropIfExists('properties');
    }
};
