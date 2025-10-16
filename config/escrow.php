<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Escrow Hold Periods (in days)
    |--------------------------------------------------------------------------
    |
    | Configure how long funds are held before sellers can withdraw them.
    | Hold periods are based on seller trust level.
    |
    */

    'hold_period_new' => env('HOLD_PERIOD_NEW_SELLER', 21),
    'hold_period_standard' => env('HOLD_PERIOD_STANDARD_SELLER', 14),
    'hold_period_trusted' => env('HOLD_PERIOD_TRUSTED_SELLER', 7),
    'hold_period_verified' => env('HOLD_PERIOD_VERIFIED_SELLER', 3),

    /*
    |--------------------------------------------------------------------------
    | Chargeback Reserve Percentages
    |--------------------------------------------------------------------------
    |
    | Percentage of earnings to hold as a reserve for potential chargebacks.
    |
    */

    'reserve_new' => env('RESERVE_NEW_SELLER', 20),
    'reserve_standard' => env('RESERVE_STANDARD_SELLER', 10),
    'reserve_trusted' => env('RESERVE_TRUSTED_SELLER', 5),
    'reserve_verified' => env('RESERVE_VERIFIED_SELLER', 0),

    /*
    |--------------------------------------------------------------------------
    | Risk Thresholds
    |--------------------------------------------------------------------------
    |
    | Configure high-risk transaction detection and handling.
    |
    */

    'high_risk_threshold' => env('HIGH_RISK_AMOUNT_THRESHOLD', 500),
    'high_risk_multiplier' => env('HIGH_RISK_HOLD_MULTIPLIER', 1.5),
    'auto_approve_max' => env('AUTO_APPROVE_MAX_AMOUNT', 100),
];
