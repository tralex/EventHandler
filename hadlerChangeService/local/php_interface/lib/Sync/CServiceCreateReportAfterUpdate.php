<?php
namespace lib\sync;

use Bitrix\Main\IO,
    Bitrix\Main\Application;

use Bitrix\Main\Entity;
use Bitrix\Main\Loader;
use \Bitrix\Main\Mail\Event;
use function bzFunctions\my_dump;
// Библиотека для работы с csv
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/csv_data.php");
\Bitrix\Main\Loader::includeModule('iblock');

class CServiceCreateReportAfterUpdate
{
    const LOG_PATH = "/local/php_interface/logs/Sync/ReportsUpdatedServiceFromMis/";
    const PRICE_STORAGE_UPDATE = '/local/logs/sync/StorageUpdateMISInfo/priceAfterChangeMIS.csv';
    const FIELDS_STORAGE_UPDATE = '/local/logs/sync/StorageUpdateMISInfo/propsAfterChangeMIS.csv';

    static function sync(): string
    {
        // Объект класса
        $sync = new CServiceMISCreateReportAfterUpdate();
        // Чтение данных из файла
        $priceChangeInfo = $sync->readCsvFile(Application::getDocumentRoot() . self::PRICE_STORAGE_UPDATE);
        $propsChangeInfo = $sync->readCsvFile(Application::getDocumentRoot() . self::FIELDS_STORAGE_UPDATE);
        $priceTypeInfo = [];
        // Получим информацию о типах цен
        if ($priceChangeInfo) {
            // Получим id типов цен у которых поменялась цена
            $idsType = array_reduce(
                $priceChangeInfo,
                function ($result, $priceRow){
                    $result[$priceRow[1]] = $priceRow[1];

                    return $result;
                },
                []
            );
            // Получим название клиники
            $priceTypeInfo = $sync->getPriceName($idsType);
        }
        /**********
        Данные для будущего отчета
        ************/
        // Изменение полей ИБ
        $fieldsChanges = [];
        if ($propsChangeInfo) {
            $fieldsChanges = array_reduce(
                $propsChangeInfo,
                function ($result, $elem) {
                    // Название услуги изменилось
                    $afterName = "";
                    if ($elem[2] != $elem[1]) {
                        $afterName = $elem[2];
                    }
                    // Активность услуги изменилось
                    $afterActive = "";
                    if ($elem[3] != $elem[4]) {
                        $afterActive = $elem[4];
                    }

                    $result[$elem[0]][] = [
                        "TYPE" => $elem[7],
                        "ID" => $elem[0],
                        "DATE" => $elem[6],
                        "BEFORE_ACTIVE" => $elem[3],
                        "AFTER_ACTIVE" => $afterActive,
                        "PRICE_BEFORE" => '',
                        "PRICE_AFTER" => '',
                        "CLINIC" => '',
                        "XML_ID" => "<a href='" . SITE_URL . "/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=" . MIS_SERVICE_IBLOCK_ID . "&type=test1&lang=ru&ID=" . $elem[0] . "&find_section_section=-1&WF=Y' target='_blank'>".$elem[5].'</a>',
                        "BEFORE_NAME" => $elem[1],
                        "AFTER_NAME" => $afterName,
                        "USER_ID" => $elem[8],
                    ];

                    return $result;
                },
                []
            );
        }

        // Изменение цен в клиниках
        $pricesChanges = [];

        if ($priceChangeInfo) {
            // Чтобы не дублировать строки в таблице, получим id услуг у которых изменилась цена
            $idsService = array_reduce(
                $priceChangeInfo,
                function ($result, $row) {
                    $result[$row[0]] = $row[0];

                    return $result;
                },
                []
            );

            // Обработка случая. Изменилась только Цена, добираем код услуги МИС
            $arXmlIdMISService = [];
            // Получим код услуги МИС, Название, Активность
            $arXmlIdMISService = $sync->getInfoMISServ($idsService);

            $pricesChanges = array_reduce(
                $priceChangeInfo,
                function ($result, $priceRow) use(
                    $priceTypeInfo,
                    $arXmlIdMISService
                ){
                    $result[$priceRow[0]]['clinic_'.$priceRow[1]] = [
                        "TYPE" => 'Изменение стоимости',
                        "ID" => $priceRow[0],
                        "DATE" => $priceRow[4],
                        "BEFORE_ACTIVE" => $arXmlIdMISService[$priceRow[0]]['ACTIVE'],
                        "AFTER_ACTIVE" => '',
                        "PRICE_BEFORE" => (int)$priceRow[2],
                        "PRICE_AFTER" => (int)$priceRow[3],
                        "CLINIC" => $priceTypeInfo[$priceRow[1]],
                        "XML_ID" => "<a href='" . SITE_URL . "/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=" . MIS_SERVICE_IBLOCK_ID . "&type=test1&lang=ru&ID=" . $priceRow[0] . "&find_section_section=-1&WF=Y' target='_blank'>".$arXmlIdMISService[$priceRow[0]]['XML_ID'].'</a>',
                        "BEFORE_NAME" => $arXmlIdMISService[$priceRow[0]]['NAME'],
                        "AFTER_NAME" => '',
                        "USER_ID" => $priceRow[5],
                    ];

                    return $result;
                },
                []
            );
        }

        $allChanges = $fieldsChanges;

        foreach ($pricesChanges as $idService => $prices) {
            foreach ($prices as  $price) {
                $allChanges[$idService][] = $price;
            }
        }

        // Формируем файл для создания отчёта
        if ($propsChangeInfo || $priceChangeInfo) {
            // Сформируем массив для содержимого таблицы

            // Шапка таблицы
            $updInfoHead = [
                'Событие',
                'ID',
                'Дата изменения',
                'Активность была',
                'Активность стала',
                'Цена была',
                'Цена стала',
                ' Клиника (цена которой изменилась)',
                'Код услуги МИС',
                'Название было',
                'Название стало',
                'User ID',
            ];
            // Создание тела письма
            $htmlMessage = $sync->createTableHtml($updInfoHead, $allChanges);

            $today = date("Y_m_d_H_i_s");
            $descriptionLog = '';
            $siteUri = SITE_URL.self::LOG_PATH;
            $message = "";

            if($htmlMessage) {
                $nameFile = $today . 'updatedServiceMIS.html';
                $fileLog1 = $sync->saveFile($nameFile, $htmlMessage);
                $descriptionLog .= $fileLog1['fileName']." ";
                $message .= $siteUri.$nameFile."<br>";
            }
            // Вносим в журнал событий
            \CEventLog::Add(array(
                "SEVERITY" => "INFO",
                "AUDIT_TYPE_ID" => "Создание отчёта после обновления услуг МИС",
                "MODULE_ID" => "main",
                "ITEM_ID" => 'price',
                "DESCRIPTION" => $descriptionLog,
            ));

            $title = $htmlMessage ? "Есть изменения" : "Нет изменений";
            // Получаем Emails Контент-редакторы-базовый
            $arContentEmails = $sync->getEmailByGroupID($groupId = GROUP_CONTENT_USERS);

            // Отправка данных после обновления услуг
            $sync->sendEmail($arContentEmails, $emailType = "SEND_REPORT_AFTER_MIS_UPDATE", $title, $message);
            // Очищаем хранилище
            $sync->clearStorage(Application::getDocumentRoot() . self::PRICE_STORAGE_UPDATE);
            $sync->clearStorage(Application::getDocumentRoot() . self::FIELDS_STORAGE_UPDATE);
            echo $htmlMessage;
        }



        return "lib\sync\CServiceCreateReportAfterUpdate::sync();";
    }

    function readCsvFile($file): array
    {
        $csvFile = new \CCSVData('R', false);
        $csvFile->LoadFile($file);
        $csvFile->SetDelimiter(';');
        $arRows = array();
        $Headers = array();
        while ($arRes = $csvFile->Fetch()) {
            $arRows[] = $arRes;
        }

        return $arRows;
    }

    function getPriceName($idsType): array
    {
        $arPriceName = [];
        $dbPriceType = \CCatalogGroup::GetList(
            array("SORT" => "ASC"),
            array("ID"=>$idsType)
        );
        while ($arPriceType = $dbPriceType->Fetch())
        {
            $arPriceName[$arPriceType['ID']] = $arPriceType['NAME_LANG'];
        }

        return $arPriceName;
    }

    function getUpdatedInfo(
        $propsChangeInfoWithIdKey,
        $priceChangeInfo,
        $priceTypeInfo,
        $arIdsMISServiceWhichPriceChange,
        $arXmlIdMISService
    ): array
    {
        $updInfo = [];

        $updInfoChangePrice = [];
        $updInfoChangeFields = [];

        // Цены менялись
        if ($priceChangeInfo) {

            $updInfoChangePrice = array_reduce(
                $priceChangeInfo,
                function ($result, $priceRow) use(
                    $priceTypeInfo,
                    $propsChangeInfoWithIdKey,
                    $arXmlIdMISService
                ){
                   my_dump($priceRow);
                    $result[$priceRow[0]]['clinic_'.$priceRow[1]] = [
                        "TYPE" => $propsChangeInfoWithIdKey[$priceRow[0]]['TYPE'],
                        "ID" => $priceRow[0],
                        "DATE" => $priceRow[4],
                        "BEFORE_ACTIVE" => $arXmlIdMISService[$priceRow[0]]['ACTIVE'],
                        "AFTER_ACTIVE" => $propsChangeInfoWithIdKey[$priceRow[0]]['AFTER_ACTIVE'],
                        "PRICE_BEFORE" => (int)$priceRow[2],
                        "PRICE_AFTER" => (int)$priceRow[3],
                        "CLINIC" => $priceTypeInfo[$priceRow[1]],
                        "XML_ID" => "<a href='" . SITE_URL . "/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=" . MIS_SERVICE_IBLOCK_ID . "&type=test1&lang=ru&ID=" . $priceRow[0] . "&find_section_section=-1&WF=Y' target='_blank'>".$arXmlIdMISService[$priceRow[0]]['XML_ID'].'</a>',
                        "BEFORE_NAME" => $arXmlIdMISService[$priceRow[0]]['NAME'],
                        "AFTER_NAME" => $propsChangeInfoWithIdKey[$priceRow[0]]['AFTER_NAME'],
                        "USER_ID" => $propsChangeInfoWithIdKey[$priceRow[0]]['USER_ID'],
                    ];

                    return $result;
                },
                []
            );
        }

        if ($propsChangeInfoWithIdKey) {

            $updInfoChangeFields = array_reduce(
                $propsChangeInfoWithIdKey,
                function ($result, $row) use ($arIdsMISServiceWhichPriceChange){

                    //if (!in_array($row['ID'], $arIdsMISServiceWhichPriceChange)) {
                        $result[] = [
                            "TYPE" => $row['TYPE'],
                            "ID" => $row['ID'],
                            "DATE" => $row['DATE'],
                            "BEFORE_ACTIVE" => $row['BEFORE_ACTIVE'],
                            "AFTER_ACTIVE" => $row['AFTER_ACTIVE'],
                            "PRICE_BEFORE" => '',
                            "PRICE_AFTER" => '',
                            "CLINIC" => '',
                            "XML_ID" => "<a href='" . SITE_URL . "/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=" . MIS_SERVICE_IBLOCK_ID . "&type=test1&lang=ru&ID=" . $row['ID'] . "&find_section_section=-1&WF=Y' target='_blank'>".$row['XML_ID'].'</a>',
                            "BEFORE_NAME" => $row['BEFORE_NAME'],
                            "AFTER_NAME" => $row['AFTER_NAME'],
                            "USER_ID" => $row['USER_ID'],
                        ];
                   // }

                    return $result;
                },
                []
            );
        }




        $updInfo = array_merge($updInfoChangePrice, $updInfoChangeFields);

        sort($updInfo);

        return $updInfo;
    }

    function createTableHtml($headArrTable, $contentArrTable): string
    {
        $message = '
         <style>
            table {
            max-width: 700px;
                margin: 0 auto;
            font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
            text-align: left;
            border-collapse: separate;
            border-spacing: 5px;
            background: #e2f6ff;
            color: #656665;
            border: 16px solid #e2f6ff;
            border-radius: 20px;
            }
            th {
            font-size: 18px;
            padding: 10px;
            }
            td {
            background: #ffffff;
            padding: 10px;
            }
        </style>
        ';
        $message .= "
            <table>
                <caption><h3>Информация об обновлении услуг МИС</h3></caption>  
                <thead><tr>
                ";
        foreach ($headArrTable as $th) {
            $message .= "<th>".$th."</th>";
        }
        $message .= "</tr></thead><tbody>";
        foreach ($contentArrTable as $content) {
            foreach ($content as $row) {
                $message .= "<tr>";
                foreach ($row as $tdContent) {
                    $message .= "<td>" . $tdContent . "</td>";
                }
                $message .= "</tr>";
            }
        }

        $message .= "</tbody></table>";

        return $message;

    }

    function saveFile($nameFile, $data): array
    {
        $file = $_SERVER["DOCUMENT_ROOT"] . self::LOG_PATH . $nameFile;
        $result =  file_put_contents($file, $data);

        return ['fileName' => $file, 'data' => $result];
    }

    function sendEmail($arEmailList, $type, $title, $message)
    {
        // Строка кому отправляем
        $Emails = implode(',', $arEmailList);

        Event::send(array(
            "EVENT_NAME" => $type,
            "LID" => "s1",
            "C_FIELDS" => array(
                "EMAIL_TO" => $Emails,
                "MESSAGE" => $message,
                "TITLE" => $title
            ),
        ));
    }

    function getEmailByGroupID($groupId): array
    {
        //получение email лов по группе пользовтаелей бтрикс (две группы нужно создать в админке)
        //на входе ид группы пользователя
        //на выходе почтовые адреса всех пользователей этих групп
        $arEmail = [];
        if (!empty($groupId)) {
            // Запрос пользователей
            $queryUsers = \CUser::GetList(
                ($by = "ID"),
                ($order = "ASC"),
                array(
                    'GROUPS_ID' => $groupId,
                    'ACTIVE' => 'Y'
                ),
                array('FIELDS' => array('ID', 'EMAIL'))
            );

            while ($arUser = $queryUsers->Fetch()) {
                if (!empty($arUser['EMAIL'])) {
                    $arEmail[$arUser['ID']] = $arUser['EMAIL'];
                }
            }
        }

        return $arEmail;
    }

    function clearStorage($path) {
        $file = new IO\File($path);
        $file->putContents(''); // очищаем файл
    }

    function getInfoMISServ($ids): array
    {
        $arMIS_XML_ID = [];

        $arSelect = Array("ID","XML_ID","ACTIVE","NAME");
        $arFilter = Array(
            "IBLOCK_ID"=>MIS_SERVICE_IBLOCK_ID,
            "ID" => $ids
        );
        $res = \CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
        while ($ob = $res->GetNext())
        {
            $arMIS_XML_ID[$ob['ID']] = [
                'XML_ID' => $ob['XML_ID'],
                'NAME' => $ob['NAME'],
                'ACTIVE' => $ob['ACTIVE'],
            ];
        }

        return $arMIS_XML_ID;
    }
}
