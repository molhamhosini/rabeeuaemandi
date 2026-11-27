<?php

// Ordering hours and booking rules, in Abu Dhabi time.
return [
    'timezone' => 'Asia/Dubai',
    // first and last time a customer can choose; orders "as soon as possible" only inside these hours
    'open' => env('ORDER_OPEN', '12:00'),
    'last' => env('ORDER_LAST', '23:30'),
    // the choices are every 30 minutes
    'slot_minutes' => 30,
    // ordinary meals: a set time must be at least this far ahead (delivery takes about 30 minutes)
    'meal_minutes' => 30,
    // sections that need notice (banquets, cooking service) can be booked this many days ahead;
    // everything else is for the same day
    'book_days' => 30,
];
