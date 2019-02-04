<?php
require_once (__DIR__."/client.php");
/**
 * Хост подключения к базе данных
 * @var string
 */
define("DB_HOST", 'localhost');
/**
 * Название базы данных
 * @var string
 */
define("DB_NAME", 'sitemanager');
/**
 * Логин базы данных
 * @var string
 */
define("DB_LOGIN", 'cluster_status_user');
/**
 * Пароль базы данных
 * @var string
 */
define("DB_PASSWORD", '1111');
/**
 * Идентификатор debug-чата
 * */
define("DEBUG_CHAT_ID", '-1001401685231');

/**
 * Параметры, которые необходимо отслеживать
 * @var array
 */
$params = array(
    "SECONDS_BEHIND_MASTER" => 0,
    "SLAVE_IO_STATE" => "Waiting for master to send event",
    "SLAVE_IO_RUNNING" => "Yes",
    "SLAVE_SQL_RUNNING" => "Yes",
);
/**
 * Функция подключения к базе данных
 */
function connect()
{
    $mysqli = mysqli_init();
    if (!$mysqli->real_connect(DB_HOST, DB_LOGIN, DB_PASSWORD, DB_NAME)) {
        die('Ошибка подключения (' . mysqli_connect_errno() . ') '. mysqli_connect_error());
        //return false;
    } else
        return $mysqli;
}

/**
 * функция получения из БД SHOW SLAVE STATUS и отслеживания искомых параметров
 */
function getFromDBSlaveStatus()
{
    $mysqli = connect();
    $result = $mysqli->query("SHOW SLAVE STATUS;");
    $arStatus = $result->fetch_assoc();
    $result->close();
    $mysqli->close();
    getShowSlaveStatus($arStatus);
}

/**
 * Функция формирования сообщений об ошибках
 * @param array $logErrors выявленные ошибки
 * @param array $logStatus результата запроса SHOW SLAVE STATUS
 * @param array $params эталонные параметры (с чем сравниваем)
 * @return string сообщение с ошибками и полным логом ShowSlaveStatus
 */
function createMessageErrors($logErrors, $logStatus, $params)
{
    $returnMessage = "";
    foreach ($logErrors as $keyError => $valError)
        $returnMessage .= "Error - ".$keyError." : ".$valError." [".$params[$keyError]."]\n";

    //формируем остальной лог
    foreach ($logStatus as $keyLog => $valLog)
        //если поле было в ошибках - пропускаем
        if (!array_key_exists(strtoupper($keyLog), $logErrors))
            $returnMessage .= strtoupper($keyLog) . " : " . $valLog."\n";
    //echo  $returnMessage;
    return $returnMessage;
}

/**
 * функция поиска несоответствий
 * @param $arGetShowStatus
 */
function getShowSlaveStatus($arGetShowStatus)
{
    global $params;
    $logErrors = array();
    foreach ($arGetShowStatus as $keyStatus => $valStatus) {
        if (array_key_exists(strtoupper($keyStatus), $params)) {
            if (!is_numeric($valStatus)) {
                if (strcasecmp($valStatus, $params[strtoupper($keyStatus)]) !== 0)
                    $logErrors[strtoupper($keyStatus)] = $valStatus;
            } elseif ($valStatus > $params[strtoupper($keyStatus)]) {
                //проверить сравнение чисел с точкой
                $logErrors[strtoupper($keyStatus)] = $valStatus;
            }
        }
    }
    if (count($logErrors) > 0) {
        echo createMessageErrors($logErrors, $arGetShowStatus, $params);
        Client::sendDebugMessage(createMessageErrors($logErrors, $arGetShowStatus, $params), DEBUG_CHAT_ID);
    }
}

getFromDBSlaveStatus();


