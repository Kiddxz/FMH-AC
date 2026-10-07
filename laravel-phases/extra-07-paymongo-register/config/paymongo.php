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

    // PayMongo does not accept very small amounts (in pesos)
    'minimum' => 20,
];
