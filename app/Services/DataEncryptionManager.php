<?php

namespace App\Services;

use App\Models\DataEncryptionSetting;
use Illuminate\Support\Facades\Schema;

class DataEncryptionManager
{
    private ?string $status = null;

    public function isEnabled(): bool
    {
        return in_array($this->status(), ['converting', 'active'], true);
    }

    public function isConverting(): bool
    {
        return $this->status() === 'converting';
    }

    private function status(): string
    {
        if ($this->status !== null) {
            return $this->status;
        }

        return $this->status = Schema::hasTable('data_encryption_settings')
            ? (string) (DataEncryptionSetting::query()->value('status') ?? 'inactive')
            : 'inactive';
    }

    public function forget(): void
    {
        $this->status = null;
    }
}
