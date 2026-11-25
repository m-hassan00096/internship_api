<?php

return [
    'status' => [
        'PENDING' => 'Pending Moderation',
        'APPROVED' => 'Approved & Published',
        'REJECTED' => 'Rejected & Hidden',
    ],
    'messages' => [
        'created' => 'Review submitted successfully and is pending moderation.',
        'replied' => 'Showroom reply submitted successfully.',
        'reply_updated' => 'Showroom reply updated successfully.',
        'reply_deleted' => 'Showroom reply deleted successfully.',
        'updated' => 'Review updated successfully.',
        'deleted' => 'Review deleted successfully.',
    ],
    'attributes' => [
        'rating' => 'Rating',
        'comment' => 'Comment',
        'reply' => 'Showroom Reply',
        'status' => 'Review Status',
        'reject_reason' => 'Rejection Reason',
    ],
    'errors' => [
        'not_found' => 'Review not found or does not belong to your showroom.',
        'reply_not_found' => 'Reply not found.',
        'already_reviewed' => 'You have already reviewed this item.',
        'already_replied' => 'This review already has a reply.',
        'cannot_reply' => 'Cannot reply to an unapproved review.',
    ],
];
