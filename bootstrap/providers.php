<?php

use App\Core\CoreServiceProvider;
use App\Providers\AppServiceProvider;
use Modules\Communications\CommunicationsServiceProvider;
use Modules\Helpdesk\HelpdeskServiceProvider;
use Modules\Hr\HrServiceProvider;
use Modules\Learning\LearningServiceProvider;
use Modules\Microsoft365\Microsoft365ServiceProvider;
use Modules\Operations\OperationsServiceProvider;
use Modules\People\PeopleServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    PeopleServiceProvider::class,
    CommunicationsServiceProvider::class,
    Microsoft365ServiceProvider::class,
    OperationsServiceProvider::class,
    HelpdeskServiceProvider::class,
    HrServiceProvider::class,
    LearningServiceProvider::class,
];
