<?php
/*
   Нужно:
1. Вынести настройки подключения в константы, в начале скрипта.

2. Добавить лимит на отставание в секундах от мастера при достижении - рапортовать, что кластер сломался в Telegram.
Ориентировочный скрипт класса работы с телеграмом прилагаю, из него нужно брать namespace, chatId брать из настроект
исходного скрипта.
chatId нужного чата сейчас: -1001401685231

3. Все работы делаются на slave-сервере 704ka.ru. Скрипт поставить в crontab и приложить его в задаче.
Скорее всего должно быть два скрипта - client.php и main.php, первый - измененная логика клиента телеграма,
второй - основная логика и настройки.

Что нужно мониторить и является триггером при отправке ошибки:
"Slave_IO_State", должен быть Waiting for master to send event
"Slave_IO_Running" должен быть Yes
"Slave_SQL_Running" должен быть Yes
Seconds_Behind_Master должен быть ниже настройки.

Если хотя бы одно из этих условий не выполняется - отправляем сообщение.
В сообщении говорим, какое условие не выполнилось и прикладываем полный лог SHOW SLAVE STATUS, в формате
ключ: значение, на каждой строке.
*/
//https://dev.mysql.com/doc/refman/8.0/en/show-slave-status.html
//'localhost', 'cluster_status_user', '1111', 'sitemanager'

namespace Intervolga\Mybox\Telegram;

class Monitoring
{
    /**
     * Хост подключения к базе данных
     * @var string
     */
    const DB_HOST = 'localhost';
    /**
     * Название базы данных
     * @var string
     */
    const DB_NAME = 'test';
    /**
     * Логин базы данных
     * @var string
     */
    const DB_LOGIN = 'root';
    /**
     * Пароль базы данных
     * @var string
     */
    const DB_PASSWORD = '1234';
    /**
     * Настройки лимита на отставание от мастера в секундах
     * @var int
     */
    const LIMIT_SECOND_BEHIND_MASTER = 10;

    /**
     * Объект MySQLI
     */
    public $mysqli;

    /**
        Результат запроса SHOW SLAVE STATUS
     */
    public $result;
    /**
      Массив ошибок,которые нужно отслеживать
     * @var array
     */
    public $logErrors = array();
    /**
     * Функция подключения к базе данных
     */

    public function connect()
    {
        $this->mysqli = mysqli_init();
        if (!$this->mysqli->real_connect(self::DB_HOST, self::DB_LOGIN, self::DB_PASSWORD, self::DB_NAME)) {
            die('Ошибка подключения (' . mysqli_connect_errno() . ') '. mysqli_connect_error());
            //return false;
        } else
            return true;

    }

    public function getShowSlaveStatus()
    {
        if ($this->connect()) {
            $this->result = $this->mysqli->query("SHOW SLAVE STATUS;");

            $this->result->fetch_assoc();

            $this->checkBehindLimit($this->result['Seconds_Behind_Master']);

            $this->result->close();
            $this->mysqli->close();
        }


    }
    /**
     * Функция проверки Seconds_Behind_Master. Должен быть ниже const LIMIT_SECOND_BEHIND_MASTER.
     * @param int $secBehindMaster полученное значение
     */
    public function checkBehindLimit($secBehindMaster)
    {
        if(isset($secBehindMaster) && $secBehindMaster >= self::LIMIT_SECOND_BEHIND_MASTER) {
            $this->logErrors["LIMIT_SECOND_BEHIND_MASTER"] = "LIMIT = {$secBehindMaster}";
            //нужно отправить сообщение в телегу, что кластер сломался
        }
    }
}

$monitor = new Monitoring();
$monitor->getShowSlaveStatus();