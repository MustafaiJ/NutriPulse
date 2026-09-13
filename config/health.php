<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Health Target Ranges (mg/dL)
    |--------------------------------------------------------------------------
    |
    | These defaults are common clinical ranges and can be overridden per user
    | later if needed. All values are stored in mg/dL; mmol/L readings are
    | converted for comparison.
    |
    */

    'fasting_min' => env('TARGET_FASTING_MIN', 70),
    'fasting_max' => env('TARGET_FASTING_MAX', 100),

    'postmeal_min' => env('TARGET_POSTMEAL_MIN', 100),
    'postmeal_max' => env('TARGET_POSTMEAL_MAX', 140),

    'random_min' => env('TARGET_RANDOM_MIN', 70),
    'random_max' => env('TARGET_RANDOM_MAX', 180),

];