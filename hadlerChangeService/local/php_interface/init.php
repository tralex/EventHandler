<?
global $APPLICATION;
$APPLICATION->AddHeadString('<link rel="canonical" href="https://' . $_SERVER['SERVER_NAME'] . $APPLICATION->sDirPath . '"/>', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/local/vendor/autoload.php');

//функции
if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/functions.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/functions.php");
}

//Автозагрузка классов
if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/autoload.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/autoload.php");
}
//обработка событий
if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/event_handler.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/event_handler.php");
}