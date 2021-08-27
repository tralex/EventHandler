<?
use Bitrix\Main\EventManager;

$eventManager = EventManager::getInstance();

$eventManager->addEventHandler(
    "iblock",
    "OnAfterIBlockElementAdd",
    'bzFunctions\addRecordAddMISInStorage'
);
$eventManager->addEventHandler(
    "iblock",
    "OnBeforeIBlockElementUpdate",
    'bzFunctions\addRecordChangeMISInStorage'
);
$eventManager->addEventHandler(
    "iblock",
    "OnBeforeIBlockElementDelete",
    'bzFunctions\deleteMisService'
);

$eventManager->addEventHandler(
    "catalog",
    "\Bitrix\Catalog\Price::OnBeforeAdd",
    'bzFunctions\addRecordAddPriceMISInStorage'
);

$eventManager->addEventHandler(
    "catalog",
    "\Bitrix\Catalog\Price::OnBeforeUpdate",
    'bzFunctions\addRecordChangePriceMISInStorage'
);
$eventManager->addEventHandler(
    "catalog",
    "OnBeforeProductPriceDelete",
    'bzFunctions\deleteMisServicePrice'
);
 
?>