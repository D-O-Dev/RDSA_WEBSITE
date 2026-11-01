<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/** TOTP SHA-1, pas de 30 secondes, conforme à RFC 6238. */
class TwoFactorService
{
    public function secret(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $alphabet[random_int(0, 31)];
        }

        return $secret;
    }

    public function code(string $secret, int $time, int $digits = 6): string
    {
        $bits = '';
        $key = '';
        foreach (str_split($secret) as $char) {
            $index = strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', $char);
            if ($index === false) {
                throw new \InvalidArgumentException('Secret TOTP invalide.');
            }
            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $key .= chr(bindec(substr($bits, $i, 8)));
        }
        $counter = intdiv($time, 30);
        $hash = hash_hmac('sha1', pack('N2', intdiv($counter, 4294967296), $counter % 4294967296), $key, true);
        $offset = ord($hash[19]) & 15;
        $value = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    public function window(string $secret, string $code): ?int
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            return null;
        }
        foreach ([0, -1, 1] as $offset) {
            $time = now()->timestamp + $offset * 30;
            if (hash_equals($this->code($secret, $time), $code)) {
                return intdiv($time, 30);
            }
        }

        return null;
    }

    public function consume(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code) {
            $record = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (! $record->two_factor_secret) {
                return false;
            }
            $window = $this->window($record->two_factor_secret, $code);
            if ($window !== null && $window > ($record->two_factor_last_window ?? -1)) {
                $record->forceFill(['two_factor_last_window' => $window])->save();

                return true;
            }
            $codes = $record->two_factor_recovery_codes ?? [];
            foreach ($codes as $index => $hash) {
                if (hash_equals($hash, hash('sha256', strtoupper(trim($code))))) {
                    unset($codes[$index]);
                    $record->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                    return true;
                }
            }

            return false;
        });
    }
}
