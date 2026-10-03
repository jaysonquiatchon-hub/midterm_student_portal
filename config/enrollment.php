<?php

return [
    'max_file_kilobytes' => 1024,
    'requirements' => [
        'New Student' => [
            'birth_certificate' => 'PSA-issued Birth Certificate',
            'senior_high_record' => 'Senior High School Report Card (Form 138 / SF9)',
            'good_moral' => 'Certificate of Good Moral Character',
            'id_photo' => 'Recent 2x2 ID Photo',
        ],
        'Transferee' => [
            'birth_certificate' => 'PSA-issued Birth Certificate',
            'transcript' => 'Transcript of Records or Certificate of Grades',
            'transfer_credential' => 'Honorable Dismissal or Transfer Credential',
            'good_moral' => 'Certificate of Good Moral Character',
            'id_photo' => 'Recent 2x2 ID Photo',
        ],
        'Returning Student' => [
            'previous_grades' => 'Most Recent Certificate of Grades',
            'clearance' => 'Previous Term Clearance',
            'id_photo' => 'Recent 2x2 ID Photo',
        ],
    ],
];
