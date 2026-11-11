<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'login_success' => 'Login successful.',
    'login_failed' => 'Invalid login credentials.',
    'logout_success' => 'Logout successful.',
    'unauthenticated' => 'Unauthenticated.',
    'forbidden' => 'You are not authorized to access this resource.',
    'logout_failed' => 'You are not authenticated.',
    'email_not_verified' => 'Please verify your email before logging in.',
    'user_active' => [
        'labels' => [
            'INACTIVE' => 'Inactive',
            'ACTIVE' => 'Active',
            'PENDING_PASSWORD' => 'Pending Password Setup',
            'UNKNOWN' => 'Unknown',
        ],
        'messages' => [
            'INACTIVE' => 'Your account is inactive. Please contact support.',
            'ACTIVE' => 'Your account is active.',
            'PENDING_PASSWORD' => 'Please set your new password using the activation link sent to your email before logging in.',
            'UNKNOWN' => 'Unknown account status.',
        ],
    ],
];
