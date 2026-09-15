<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AuditLogObserver
{
    private const REDACTED = [
        'password',
        'remember_token',
        'token_hash',
        'document_number',
        'payload',
    ];

    public function created(Model $model): void
    {
        $this->write($model, 'created', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), ['updated_at']);

        if ($changes === []) {
            return;
        }

        $old = [];
        foreach (array_keys($changes) as $key) {
            $old[$key] = $model->getRawOriginal($key);
        }

        $this->write($model, 'updated', $old, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->write($model, 'deleted', $model->getAttributes(), null);
    }

    private function write(Model $model, string $event, ?array $old, ?array $new): void
    {
        $propertyId = $model instanceof Property
            ? $model->getKey()
            : $model->getAttribute('property_id');

        AuditLog::query()->create([
            'property_id' => $propertyId,
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => (string) $model->getKey(),
            'old_values' => $this->redact($old),
            'new_values' => $this->redact($new),
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 500, ''),
            'created_at' => now(),
        ]);
    }

    private function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach (self::REDACTED as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = '[REDACTED]';
            }
        }

        return $values;
    }
}
