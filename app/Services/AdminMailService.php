<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use Illuminate\Support\Facades\Mail;

class AdminMailService
{
    public function configure(): bool
    {
        $settings = ApplicationSetting::find('mail')?->values;
        if ($settings && ! ($settings['enabled'] ?? false)) {
            return false;
        }
        if (! $settings) {
            return config('mail.default') === 'smtp'
                ? (bool) (config('mail.mailers.smtp.host') && config('mail.mailers.smtp.username') && config('mail.mailers.smtp.password'))
                : in_array(config('mail.default'), ['ses', 'postmark', 'resend', 'mailgun']);
        }
        config(['mail.default' => 'smtp', 'mail.mailers.smtp' => [
            'transport' => 'smtp', 'scheme' => $settings['encryption'] === 'ssl' ? 'smtps' : 'smtp',
            'host' => $settings['host'], 'port' => $settings['port'],
            'username' => $settings['username'], 'password' => $settings['password'], 'timeout' => 15,
        ], 'mail.from' => ['address' => $settings['from_address'], 'name' => $settings['from_name']]]);
        Mail::purge('smtp');

        return true;
    }
}
