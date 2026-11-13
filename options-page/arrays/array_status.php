<?php

defined( 'ABSPATH' ) || exit;

return [
    [
        'field'       => 'label',
        'label'       => 'Name of the status',
        'placeholder' => 'e.g. Business customer',
        'default'     => '',
        'type'        => 'text',
        'description' => 'Shown as the title of the status on the user\'s screen and on the Users list, and in the texts of its links there. Left empty, it is "Status". '
            . 'The status is None, Pending, Approved or Rejected. It never changes the role of a user, stops nobody from logging in and hides no content.',
    ],
];
