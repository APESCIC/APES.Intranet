<?php

use App\Core\CoreServiceProvider;
use App\Providers\AppServiceProvider;
use Modules\Communications\CommunicationsServiceProvider;
use Modules\Helpdesk\HelpdeskServiceProvider;
use Modules\Operations\OperationsServiceProvider;
use Modules\People\PeopleServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    PeopleServiceProvider::class,
    CommunicationsServiceProvider::class,
    OperationsServiceProvider::class,
    HelpdeskServiceProvider::class,
];
