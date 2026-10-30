<?php

return [
    // Set only the proxy IPs/CIDRs provided by the hosting provider.
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES', ''))))),
];
