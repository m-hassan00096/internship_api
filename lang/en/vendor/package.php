<?php

return [
    'status' => [
        'DRAFT' => 'Draft',
        'PUBLISHED' => 'Published',
        'ARCHIVED' => 'Archived',
    ],
    'messages' => [
        'created' => 'Package created successfully.',
        'updated' => 'Package updated successfully.',
        'deleted' => 'Package deleted successfully.',
        'status_changed' => 'Package status changed successfully.',
    ],
    'attributes' => [
        'name' => 'Package Name',
        'sku' => 'Package SKU',
        'description' => 'Package Description',
        'image' => 'Package Image',
        'image_path' => 'Package Image',
        'price' => 'Package Price',
        'status' => 'Package Status',
        'current_step' => 'Current Step',
        'published_at' => 'Published At',
        'rooms' => 'Included Rooms',
    ],
    'errors' => [
        'not_found' => 'Package not found or does not belong to your showroom.',
        'invalid_rooms' => 'One or more selected rooms do not belong to your showroom.',
        'empty_rooms_cannot_publish' => 'Cannot publish a package without any included rooms.',
    ],
];
