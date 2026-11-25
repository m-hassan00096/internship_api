<?php

return [
    'status' => [
        'PENDING' => 'Pending Review',
        'APPROVED' => 'Approved',
        'ACTIVE' => 'Active',
        'EXPIRED' => 'Expired',
        'REJECTED' => 'Rejected',
    ],
    'placements' => [
        'banner' => 'Main Banner',
        'home' => 'Home Page',
        'search' => 'Top of Search Results',
        'category' => 'Category Page',
    ],
    'messages' => [
        'created' => 'Promotion request submitted successfully and is pending review.',
        'approved' => 'Promotion request approved successfully.',
        'rejected' => 'Promotion request rejected.',
        'cancelled' => 'Promotion request cancelled successfully.',
    ],
    'errors' => [
        'not_found' => 'Promotion request not found or does not belong to your showroom.',
        'cannot_cancel' => 'Promotion request can only be cancelled while pending review.',
        'promotable_not_found' => 'The item to promote was not found or does not belong to your showroom.',
        'promotable_not_published' => 'Cannot promote an item that is not published or active.',
        'type_inactive' => 'The selected promotion type is currently inactive.',
        'duplicate_pending' => 'A pending promotion request already exists for this item in the same placement.',
        'already_active' => 'This item already has an active promotion running in the same placement.',
    ],
    'attributes' => [
        'showrooms_id' => 'Showroom',
        'promotable_type' => 'Promotable Item Type',
        'promotable_id' => 'Promotable Item',
        'promotion_type_id' => 'Promotion Type',
        'days' => 'Days',
        'daily_price' => 'Daily Price',
        'total_amount' => 'Total Amount',
        'status' => 'Promotion Status',
        'starts_at' => 'Start Date',
        'ends_at' => 'End Date',
        'reject_reason' => 'Rejection Reason',
    ],
];
