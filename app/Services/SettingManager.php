<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingManager
{
    private const CACHE_KEY = 'feedevent.site-settings';

    public function get(string $key, mixed $default = null): mixed
    {
        $stored = $this->allStored();

        if (array_key_exists($key, $stored)) {
            return $this->normalize($stored[$key]);
        }

        return $this->normalize(config('feedevent.settings', [])[$key] ?? $default);
    }

    public function hasStored(string $key): bool
    {
        return array_key_exists($key, $this->allStored());
    }

    public function set(string $key, mixed $value, ?int $userId = null): void
    {
        SiteSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $this->serialize($value), 'updated_by' => $userId],
        );

        Cache::forget(self::CACHE_KEY);
    }

    public function forget(string $key): void
    {
        SiteSetting::where('key', $key)->delete();
        Cache::forget(self::CACHE_KEY);
    }

    public function isSecret(string $key): bool
    {
        return in_array($key, config('feedevent.secret_keys', []), true);
    }

    /**
     * @return array<string, string|null>
     */
    private function allStored(): array
    {
        if (! Schema::hasTable('site_settings')) {
            return [];
        }

        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => SiteSetting::query()
                ->get()
                ->mapWithKeys(fn (SiteSetting $setting) => [$setting->key => $setting->value])
                ->all(),
        );
    }

    private function serialize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    private function normalize(mixed $value): mixed
    {
        return match ($value) {
            'true' => true,
            'false' => false,
            default => $value,
        };
    }
}
