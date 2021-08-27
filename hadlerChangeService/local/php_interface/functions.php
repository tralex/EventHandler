<?
namespace bzFunctions;
// Подключаем два класса для замены шорт кодов
use \lib\shortcode\Replace;
// Библиотеки для создания csv
use Bitrix\Main\IO,
    Bitrix\Main\Application;
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/csv_data.php");

 
function putDataToCSV($file, $array){

    $fields_type = 'R'; //дописываем строки в файл
    $delimiter = ";";   //разделитель для csv-файла
    $csvFile = new \CCSVData($fields_type, false);
    $csvFile->SetFieldsType($fields_type);
    $csvFile->SetDelimiter($delimiter);
    $csvFile->SetFirstHeader(false);
    $arrayFields = array();

    foreach($array as $item) {
        $newItem = array_values($item);
        $csvFile->SaveFile($file,$newItem);
    }
}

function addRecordChangeMISInStorage($arFields){
    if ($arFields['IBLOCK_ID'] == MIS_SERVICE_IBLOCK_ID){
        global $USER;
        $arMISInfoByDB = [];

	    // Зафиксируем изменения полей МИС
        $arSelect = Array("ID", "NAME", "ACTIVE","XML_ID");
        $arFilter = Array(
            "IBLOCK_ID"=>$arFields['IBLOCK_ID'],
            "=ID" => $arFields['ID']
        );
        $res = \CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
        if ($ob = $res->GetNext())
        {
            if ($arFields['NAME'] != $ob['NAME'] ||
                $arFields['ACTIVE'] != $ob['ACTIVE']
            ) {
                $arMISInfoByDB = [
                    'ID' => $ob['ID'],
                    'NAME' => $ob['NAME'],
                    'XML_ID' => $ob['XML_ID'],
                    'ACTIVE' => $ob['ACTIVE'],
                ];
            }
        }

        if ($arMISInfoByDB) {
            // Массив услуги
            $arChangesMISInfo[] = [
                "ID" => $arMISInfoByDB['ID'],
                "BEFORE_NAME" => $arMISInfoByDB['NAME'],
                "AFTER_NAME" => $arFields['NAME'],
                "BEFORE_ACTIVE" => $arMISInfoByDB['ACTIVE'],
                "AFTER_ACTIVE" =>$arFields['ACTIVE'],
                "XML_ID" => $arMISInfoByDB['XML_ID'],
                "DATE" => date("d.m.y H:i:s"),
                "TYPE" => 'Обновление',
                "USER_ID" => $USER->GetID(),
            ];

            // Записываем в файл csv
            $fileWithPropsValue = '/local/logs/sync/StorageUpdateMISInfo/propsAfterChangeMIS.csv';
            if(!IO\Directory::isDirectoryExists(Application::getDocumentRoot() . '/local/logs/sync/StorageUpdateMISInfo/')){
                IO\Directory::createDirectory(Application::getDocumentRoot() . '/local/logs/sync/StorageUpdateMISInfo/');
            }

            putDataToCSV(Application::getDocumentRoot().$fileWithPropsValue,$arChangesMISInfo);
        }
    }
}

function addRecordChangePriceMISInStorage ($event)
{
    global $USER;
    // Функция, которая отслеживает изменение стоимости услуг при обновлении
    $arFields = $event->getParameter("fields");

    $pricesChange = [];

    // Цены не было
    $priceBeforeUpd = null;
    // Получим цены, которые были
    $iterator = \CIBlockElement::GetList(
        array(),
        array(
            'IBLOCK_ID' => MIS_SERVICE_IBLOCK_ID,
            'PRICE_TYPE' => $arFields['CATALOG_GROUP_ID'],
            '=ID' => $arFields['PRODUCT_ID'],
        ),
        false,
        false,
        array('ID', 'PRICE_'.$arFields['CATALOG_GROUP_ID'])
    );
    if ($ob = $iterator->GetNext())
    {
        // Цена была ранее и обновилась
        $priceBeforeUpd = (int)$ob['PRICE_'.$arFields['CATALOG_GROUP_ID']];
    }

    if ($priceBeforeUpd != (int)$arFields['PRICE']) {
        $pricesChange[] = [
            "ID" => $arFields['PRODUCT_ID'],
            "PRICE_TYPE" => $arFields['CATALOG_GROUP_ID'],
            "PRICE_BEFORE" => $priceBeforeUpd,
            "PRICE_AFTER" => (int)$arFields['PRICE'],
            "DATE" => date("d.m.y H:i:s"),
            "USER_ID" => $USER->GetID(),
        ];
    }

    if ($pricesChange) {
        // Записываем в файл csv
        $fileWithPropsValue = '/local/logs/sync/StorageUpdateMISInfo/priceAfterChangeMIS.csv';
        if(!IO\Directory::isDirectoryExists(Application::getDocumentRoot() . '/local/logs/sync/StorageUpdateMISInfo/')){
            IO\Directory::createDirectory(Application::getDocumentRoot() . '/local/logs/sync/StorageUpdateMISInfo/');
        }
        $file = new IO\File(Application::getDocumentRoot() . $fileWithPropsValue);

        putDataToCSV(Application::getDocumentRoot().$fileWithPropsValue,$pricesChange);
    }
}

function addRecordAddPriceMISInStorage ($event) {
    // Заведена новая цена
    global $USER;
    $arFields = $event->getParameter("fields");

    $pricesChange[] = [
        "ID" => $arFields['PRODUCT_ID'],
        "PRICE_TYPE" => $arFields['CATALOG_GROUP_ID'],
        "PRICE_BEFORE" => '',
        "PRICE_AFTER" => (int)$arFields['PRICE'],
        "DATE" => date("d.m.y H:i:s"),
        "USER_ID" => $USER->GetID(),
    ];

    if ($pricesChange) {
        // Записываем в файл csv
        $fileWithPropsValue = '/local/logs/sync/StorageUpdateMISInfo/priceAfterChangeMIS.csv';
        if(!IO\Directory::isDirectoryExists(Application::getDocumentRoot() . '/local/logs/sync/StorageUpdateMISInfo/')){
            IO\Directory::createDirectory(Application::getDocumentRoot() . '/local/logs/sync/StorageUpdateMISInfo/');
        }
        $file = new IO\File(Application::getDocumentRoot() . $fileWithPropsValue);

        putDataToCSV(Application::getDocumentRoot().$fileWithPropsValue,$pricesChange);
    }
}

function addRecordAddMISInStorage(&$arFields)
{
    if ($arFields['IBLOCK_ID'] == MIS_SERVICE_IBLOCK_ID){
        global $USER;

        // Массив услуги
        $arChangesMISInfo[] = [
            "ID" => $arFields['ID'],
            "BEFORE_NAME" => $arFields['NAME'],
            "AFTER_NAME" => '',
            "BEFORE_ACTIVE" => $arFields['ACTIVE'],
            "AFTER_ACTIVE" =>'',
            "XML_ID" => $arFields['XML_ID'],
            "DATE" => date("d.m.y H:i:s"),
            "TYPE" => 'Создание',
            "USER_ID" => $USER->GetID(),
        ];

        // Записываем в файл csv
        $fileWithPropsValue = '/local/logs/sync/StorageUpdateMISInfo/propsAfterChangeMIS.csv';
        if(!IO\Directory::isDirectoryExists(Application::getDocumentRoot() . '/local/logs/sync/StorageUpdateMISInfo/')){
            IO\Directory::createDirectory(Application::getDocumentRoot() . '/local/logs/sync/StorageUpdateMISInfo/');
        }

        putDataToCSV(Application::getDocumentRoot().$fileWithPropsValue,$arChangesMISInfo);
    }
}

function deleteMisServicePrice($intID,&$arExceptionIDs) {
    global $USER;

    $db_res = \CPrice::GetList(
        array(),
        array(
            "=PRODUCT_ID" => $intID,
            "!ID" => $arExceptionIDs,

        )
    );
    while ($ar_res = $db_res->Fetch())
    {
        $pricesChange[] = [
            "ID" => $ar_res['PRODUCT_ID'],
            "PRICE_TYPE" => $ar_res['CATALOG_GROUP_ID'],
            "PRICE_BEFORE" => (int)$ar_res['PRICE'],
            "PRICE_AFTER" => '',
            "DATE" => date("d.m.y H:i:s"),
            "USER_ID" => $USER->GetID(),
        ];
    }

    if ($pricesChange) {
        // Записываем в файл csv
        $fileWithPropsValue = '/local/logs/sync/StorageUpdateMISInfo/priceAfterChangeMIS.csv';
        if(!IO\Directory::isDirectoryExists(Application::getDocumentRoot() . '/local/logs/sync/StorageUpdateMISInfo/')){
            IO\Directory::createDirectory(Application::getDocumentRoot() . '/local/logs/sync/StorageUpdateMISInfo/');
        }
        $file = new IO\File(Application::getDocumentRoot() . $fileWithPropsValue);

        putDataToCSV(Application::getDocumentRoot().$fileWithPropsValue,$pricesChange);
    }
}

function deleteMisService(&$arFields) {
    global $USER;
    // Получим информацию об услуге
    $arMISInfoByDB = [];
    $arSelect = Array(
        "ID",
        "NAME",
        "ACTIVE",
        "XML_ID",
    );
    $arFilter = Array(
        "IBLOCK_ID"=>MIS_SERVICE_IBLOCK_ID,
        "=ID" => $arFields
    );
    $res = \CIBlockElement::GetList(
        Array(),
        $arFilter,
        false,
        false,
        $arSelect
    );
    if ($ob = $res->GetNext())
    {
        $arMISInfoByDB = [
            'ID' => $ob['ID'],
            'NAME' => $ob['NAME'],
            'XML_ID' => $ob['XML_ID'],
            'ACTIVE' => $ob['ACTIVE'],
        ];
    }

    if ($arMISInfoByDB) {
        // Массив услуги
        $arChangesMISInfo[] = [
            "ID" => $arMISInfoByDB['ID'],
            "BEFORE_NAME" => $arMISInfoByDB['NAME'],
            "AFTER_NAME" => '',
            "BEFORE_ACTIVE" => $arMISInfoByDB['ACTIVE'],
            "AFTER_ACTIVE" => '',
            "XML_ID" => $arMISInfoByDB['XML_ID'],
            "DATE" => date("d.m.y H:i:s"),
            "TYPE" => 'Удаление',
            "USER_ID" => $USER->GetID(),
        ];

        // Записываем в файл csv
        $fileWithPropsValue = '/local/logs/sync/StorageUpdateMISInfo/propsAfterChangeMIS.csv';
        if(!IO\Directory::isDirectoryExists(Application::getDocumentRoot() . '/local/logs/sync/StorageUpdateMISInfo/')){
            IO\Directory::createDirectory(Application::getDocumentRoot() . '/local/logs/sync/StorageUpdateMISInfo/');
        }
        putDataToCSV(Application::getDocumentRoot().$fileWithPropsValue,$arChangesMISInfo);
    }
}

?>