<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->date('start_date');
            $table->date('end_date');
            $table->json('days_of_week')->nullable();
            $table->decimal('price_per_night', 10, 2);
            $table->unsignedSmallInteger('minimum_stay')->default(1);
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['property_id', 'start_date', 'end_date']);
            $table->index(['room_id', 'is_active']);
        });

        Schema::create('availability_blocks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('room_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['room_id', 'start_date', 'end_date']);
        });

        Schema::create('reservations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('room_id')->constrained()->restrictOnDelete();
            $table->string('booking_code', 32)->unique();
            $table->foreignUlid('primary_guest_id')->constrained('guests')->restrictOnDelete();
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedTinyInteger('children')->default(0);
            $table->string('status', 32);
            $table->string('source', 32);
            $table->char('currency', 3)->default('EUR');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('taxes', 10, 2)->default(0);
            $table->decimal('extras_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->boolean('deposit_required')->default(false);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->decimal('balance_due', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'check_in_date', 'check_out_date']);
            $table->index(['room_id', 'status', 'check_in_date', 'check_out_date']);
        });

        Schema::create('guest_reservation', function (Blueprint $table): void {
            $table->foreignUlid('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('reservation_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->primary(['guest_id', 'reservation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_reservation');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('availability_blocks');
        Schema::dropIfExists('pricing_rules');
    }
};
