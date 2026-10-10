<?php

/*
|--------------------------------------------------------------------------
| PayMongo (online payment for a bill: GCash, Maya, card)
|--------------------------------------------------------------------------
| The keys are in the PayMongo Dashboard -> Developers -> API keys.
| Use the TEST key (sk_test_...) while building and during the defense.
| Put the key in .env only. Never upload .env to GitHub or anywhere.
*/

return [
    // Empty = online payment is turned off (the "Pay Online" box is hidden)
    'secret_key' => env('PAYMONGO_SECRET_KEY'),

    'api_url' => 'https://api.paymongo.com/v1',

    // What the customer can choose on the PayMongo page
    'methods' => array_filter(explode(',', (string) env('PAYMONGO_METHODS', 'gcash,paymaya,card'))),

    // DEMO MODE (no PayMongo account yet): PAYMONGO_DEMO=true in .env shows a practice payment page
    // inside the system instead of PayMongo. No real money. It never works when APP_ENV=production.
    // When the clinic has its PayMongo account: remove PAYMONGO_DEMO and add PAYMONGO_SECRET_KEY.
    'demo' => (bool) env('PAYMONGO_DEMO', false),

    // PayMongo does not accept very small amounts (in pesos)
    'minimum' => 20,
];
