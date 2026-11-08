<?php

return [
    'labels' => [
        'NEW' => 'New',
        'UNDER_REVIEW' => 'Under Review',
        'APPROVED' => 'Approved',
        'REJECTED' => 'Rejected',
        'UNKNOWN' => 'Unknown',
    ],

    'messages' => [
        'NEW' => 'Your registration request has been received and is awaiting review.',
        'UNDER_REVIEW' => 'Your showroom registration is currently under review.',
        'APPROVED' => 'Your registration request has been approved successfully.',
        'REJECTED' => 'We regret to inform you that your registration request was rejected.',
        'UNKNOWN' => 'Unknown registration status.',
    ],

    // API responses only
    'success_submission' => 'Your application has been received successfully and is under review by our team.',
    'setup_password_success' => 'Password created and account activated successfully, welcome to your dashboard.',

    'validation' => [
        'email_unique' => 'The email is already registered or has a pending application.',
        'phone_unique' => 'The phone number is already registered or has a pending application.',
        'password_confirmed' => 'The password confirmation does not match.',
        'invalid_token' => 'The activation link is invalid or has expired.',
    ],

    'emails' => [
        'approved_subject' => 'Your application to BaytFolio has been approved 🎉',
        'rejected_subject' => 'Update regarding your BaytFolio registration application',
    ],
];
