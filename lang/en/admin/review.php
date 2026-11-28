<?php

return [
    'status' => [
        'PENDING' => 'Pending Review',
        'APPROVED' => 'Approved & Published',
        'REJECTED' => 'Rejected',
        'HIDDEN' => 'Hidden',
    ],
    'target_types' => [
        'room' => 'Room',
        'showroom' => 'Showroom',
    ],
    'target_badges' => [
        'room' => 'Room',
        'showroom' => 'Showroom',
    ],
    'sku_label' => 'SKU: :sku',
    'verified_purchase' => 'Verified Purchase',
    'operational_note_title' => 'Platform Calculation Rule',
    'operational_notes' => [
        'pending' => 'This review is currently pending review and is not calculated in the overall average rating until approved.',
        'approved' => 'This review is currently approved and published, affecting the average rating of the room and showroom.',
        'rejected' => 'This review is rejected and hidden from the platform, not calculated in any public metric.',
        'hidden' => 'This review is currently hidden by administration and does not affect the platform average rating.',
    ],
    'sentiments' => [
        'positive' => 'Positive Review (:percentage%)',
        'neutral' => 'Neutral Review (:percentage%)',
        'negative' => 'Negative Review (:percentage%)',
    ],
    'kpis' => [
        'total' => 'Total Reviews',
        'pending' => 'Pending Review',
        'average_rating' => 'Average Rating',
        'rejected_or_hidden' => 'Rejected & Hidden',
    ],
    'messages' => [
        'fetched' => 'Reviews list retrieved successfully.',
        'detail_fetched' => 'Review details retrieved successfully.',
        'status_updated' => 'Review status updated successfully.',
        'approved' => 'Review approved and published successfully.',
        'hidden' => 'Review hidden successfully.',
        'rejected' => 'Review rejected successfully.',
    ],
    'attributes' => [
        'status' => 'Review status',
        'reject_reason' => 'Rejection reason',
    ],
    'errors' => [
        'not_found' => 'Review not found.',
        'reject_reason_required' => 'Rejection reason is required when rejecting a review.',
        'status_invalid' => 'The selected review status is invalid.',
        'status_required' => 'The review status field is required.',
    ],
];
