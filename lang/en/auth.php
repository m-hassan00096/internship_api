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
    'password_reset' => [
        'otp_sent_email' => 'Verification code has been sent to your email.',
        'otp_sent_whatsapp' => 'Verification code has been sent to your WhatsApp number.',
        'otp_verified' => 'Verification code confirmed successfully.',
        'password_updated' => 'Your password has been updated successfully. You can now log in.',
        'user_not_found' => 'No matching account was found for this portal.',
        'throttled' => 'Please wait :seconds seconds before resending the verification code.',
        'expired' => 'The verification code has expired or was not requested. Please request a new one.',
        'invalid_otp' => 'The verification code is invalid.',
        'max_attempts' => 'Maximum verification attempts exceeded. Please request a new code.',
        'invalid_reset_token' => 'The password reset token is invalid or has expired.',
        'whatsapp_delivery_failed' => 'Unable to send the verification code via WhatsApp right now. Please try again later.',
        'email_subject' => 'Password Reset Verification Code - BaytFolio',
        'password_confirmed' => 'The password confirmation does not match.',
        'whatsapp_otp_message' => 'Your password reset verification code for BaytFolio is: :otp (valid for :minutes minutes). Do not share this code.',
    ],
    'google' => [
        'invalid_token' => 'The Google verification token is invalid or expired.',
        'email_required' => 'No email was provided by the Google account.',
        'invalid_audience' => 'The token is not intended for this application.',
        'invalid_role' => 'This account is not registered as a client.',
        'failed' => 'Google authentication failed.',
    ],
];
