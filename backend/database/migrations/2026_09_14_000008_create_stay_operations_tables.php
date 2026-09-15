<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('reservation_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method', 32);
            $table->string('status', 32);
            $table->string('transaction_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['reservation_id', 'status']);
        });

        Schema::create('reservation_services', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamp('occurred_at');
            $table->foreignUlid('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('check_ins', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('reservation_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->foreignUlid('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('keys_delivered')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('check_outs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('reservation_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->foreignUlid('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('keys_returned')->default(false);
            $table->text('condition_notes')->nullable();
            $table->decimal('damages_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('pre_check_in_tokens', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('reservation_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->longText('payload')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['reservation_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_check_in_tokens');
        Schema::dropIfExists('check_outs');
        Schema::dropIfExists('check_ins');
        Schema::dropIfExists('reservation_services');
        Schema::dropIfExists('payments');
    }
};
