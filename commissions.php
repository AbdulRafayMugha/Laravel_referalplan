<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Commission Rates
    |--------------------------------------------------------------------------
    |
    | Define the commission percentages for each affiliate level.
    |
    */
    'levels' => [
        1 => env('LEVEL_1_COMMISSION', 15),
        2 => env('LEVEL_2_COMMISSION', 5),
        3 => env('LEVEL_3_COMMISSION', 2.5),
    ],
];