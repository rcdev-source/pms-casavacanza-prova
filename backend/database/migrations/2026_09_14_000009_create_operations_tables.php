<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cleaning_tasks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('room_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('check_out_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamp('scheduled_for');
            $table->string('status', 32);
            $table->unsignedTinyInteger('priority')->default(3);
            $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('completion_notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['property_id', 'status', 'scheduled_for']);
            $table->index(['assigned_to', 'status']);
        });

        Schema::create('maintenance_tickets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('room_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('status', 32);
            $table->string('priority', 32);
            $table->boolean('blocks_room')->default(false);
            $table->foreignUlid('availability_block_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'status', 'priority']);
            $table->index(['room_id', 'blocks_room', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_tickets');
        Schema::dropIfExists('cleaning_tasks');
    }
};
