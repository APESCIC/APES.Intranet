<?php

return [

    /*
    | Cookies placed by the intranet today. Each entry is listed on /cookies.
    | Use name_from when the cookie name comes from another config value.
    */
    'cookies' => [
        [
            'name' => 'XSRF-TOKEN',
            'purpose' => 'Protects signed-in actions and forms from cross-site request forgery.',
            'duration' => 'Session (until the browser closes or the session ends)',
            'type' => 'strictly_necessary',
        ],
        [
            'name_from' => 'session.cookie',
            'purpose' => 'Keeps you signed in and stores your session securely.',
            'duration' => 'Up to 2 hours of inactivity, then refreshed while you use the intranet',
            'type' => 'strictly_necessary',
        ],
    ],

];
