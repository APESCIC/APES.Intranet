<?php

return [

    /*
    | Local QA role switcher. Only works when this is true and APP_ENV is
    | local. Leave it off on Cloudron.
    */
    'qa_login_enabled' => (bool) env('QA_LOGIN_ENABLED', false),

];
