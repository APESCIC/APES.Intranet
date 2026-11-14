<?php

return [

    /*
    | Fields on an HR record that only HR and admin may edit. Management may
    | view them when they have hr.access. Special category data is separate
    | (DBS / health) and uses SensitiveData::openSpecialCategory.
    */
    'editable_fields' => [
        'employment_status',
        'start_date',
        'end_date',
        'contract_type',
        'work_location',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
    ],

];
