<?php

use App\Core\CoreServiceProvider;
use App\Providers\AppServiceProvider;
use Modules\Communications\CommunicationsServiceProvider;
use Modules\Operations\OperationsServiceProvider;
use Modules\People\PeopleServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    PeopleServiceProvider::class,
    CommunicationsServiceProvider::class,
    OperationsServiceProvider::class,
];
