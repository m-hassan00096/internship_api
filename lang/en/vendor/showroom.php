<?php

return [
    'status' => [
        'DRAFT' => 'Draft',
        'ACTIVE' => 'Active',
        'SUSPENDED' => 'Suspended',
        'TERMINATED' => 'Terminated',
    ],

    'contract_status' => [
        'DRAFT' => 'Draft',
        'ACTIVE' => 'Active',
        'EXPIRED' => 'Expired',
        'TERMINATED' => 'Terminated',
    ],

    'verification_status' => [
        'PENDING' => 'Pending Review',
        'APPROVED' => 'Verified',
        'REJECTED' => 'Rejected',
        'EXPIRED' => 'Expired',
    ],

    'messages' => [
        'created_success' => 'Showroom created and configured successfully.',
        'updated_success' => 'Showroom profile updated successfully.',
        'contract_created' => 'Contract created successfully.',
        'verification_submitted' => 'Showroom verification request submitted successfully and is under review.',
        'verification_approved' => 'Showroom verified successfully.',
        'verification_rejected' => 'Showroom verification request has been rejected.',
        'branch_created' => 'Showroom branch created successfully.',
        'branch_updated' => 'Showroom branch updated successfully.',
        'branch_deleted' => 'Showroom branch deleted successfully.',
        'main_branch_set' => 'Main branch updated successfully.',
    ],

    'errors' => [
        'not_found' => 'Showroom not found.',
        'already_exists' => 'A showroom is already registered for this account.',
        'slug_taken' => 'The unique identifier (Slug) is already taken, please choose another.',
        'no_active_contract' => 'The showroom does not have an active contract currently.',
        'room_limit_exceeded' => 'You have exceeded the maximum allowed rooms limit for your current contract plan.',
        'package_limit_exceeded' => 'You have exceeded the maximum allowed packages limit for your current contract plan.',
        'pending_verification_exists' => 'A verification request is already pending review for this showroom.',
        'already_verified' => 'The showroom is already verified.',
        'branch_not_found' => 'Branch not found or does not belong to your showroom.',
        'cannot_delete_only_branch' => 'Cannot delete the only branch of the showroom.',
    ],
    'attributes' => [
        'month' => 'Verification Period (Months)',
    ],
];
