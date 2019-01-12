<?php

return [

    /*
    |--------------------------------------------------------------------------
    | List of Available Roles
    |--------------------------------------------------------------------------
    |
    | This is an array of all available roles in the system. The parent_id
    |
    | Format:
    |   'list' => [
    |       [id, parent_id, name, description],
    |       [1, null, 'Super Admin', 'Performs all actions.'],
    |       [2, null, 'User', 'Regular user with restricted access'],
    |       [3, 2, 'Some Sub Task', 'Regular user with an addition child role'],
    |   ]
    |
    */

    'list' => [
        [1, null, 'Account Owner', 'Can perform all actions.'],
        [2, null, 'Admin', 'Can add, edit, and delete content.'],
        [3, null, 'User', 'Can login and see restricted content.']
    ]
    
];
