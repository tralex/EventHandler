<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(null, [
    'lib\Sync\CServiceCreateReportAfterUpdate' => APP_CLASS_FOLDER . 'Sync/CServiceCreateReportAfterUpdate.php',
]);