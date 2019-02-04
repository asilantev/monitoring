<?
/**
 * Клиент телеграма, может быть использован для отправки сообщений об отладке в него.
 * Зависит только от curl
 * @class Client
 * @package Intervolga\Mybox\Telegram
 */
class Client
{
	/**
	 * Токен для бота, который используется для доступа к боту
	 * @var string
	 */
	const BOT_TOKEN = '734556111:AAEn3xO891SQHuaxqEYXt6FEt1sgSOwYfNE';
	/**
	 * ID чата для дебага
	 * @var string
	 */
	const DEBUG_CHAT_ID = '-262291243';
	/**
	 * ID чата для лога
	 * @var string
	 */
	const LOG_CHAT_ID = '-285576226';
	/**
	 * Максимальная длина сообщения для посылки в Telegram . Урезано с 4096 чтобы добавить сообшения о хосте и прочее
	 * @var int
	 */
	const MAX_MESSAGE_LENGTH = 4025;

	/**
	 * Токен аутентификации прокси хоста
	 * @var string
	 */
	const PROXY_AUTH_TOKEN = 'tF-*X8u3-@48%5tUjvHmv()Mf/m{a89s';

	/**
	 * Токен прокси-хоста
	 */
	const PROXY_HOST = 'http://tgproxy.ivsupport.ru/';

	/**
	 * Отправляет сообщение в чат об отладке
	 * @param string $message сообщение
     * @param string $chatId ID чата для дебага
	 * @return bool
	 */
	public static function sendDebugMessage($message, $chatId = self::DEBUG_CHAT_ID)
	{
		return self::sendMessageToChat($message, $chatId);
	}

	/**
	 * Отправляет сообщение в чат об логах
	 * @param string $message сообщение
	 * @return bool
	 */
	public static function sendLogMessage($message)
	{
		return self::sendMessageToChat($message, self::LOG_CHAT_ID);
	}


	/**
	 * Отправляет сообщение  с документом  в отладочный чат
	 * @param string $filePath путь к файлу
	 * @param string $caption подпись для чата
	 * @return bool true при успешной отправке
	 */
	public static function sendDebugDocument($filePath, $caption = '')
	{
		return self::sendDocument(self::DEBUG_CHAT_ID, $caption, $filePath);
	}

	/**
	 * Отправляет сообщение  с документом  в чат логов
	 * @param string $filePath путь к файлу
	 * @param string $caption подпись для чата
	 * @return bool true при успешной отправке
	 */
	public static function sendLogDocument($filePath, $caption = '')
	{
		return self::sendDocument(self::LOG_CHAT_ID, $caption, $filePath);
	}

	/**
	 * Посылает документ в чат телеграм
	 * @param string $chatId ID чата
	 * @param string $caption подпись к файлу
	 * @param string $filePath путь к файлу
	 * @return bool true при успешной отправке
	 */
	private static function sendDocument($chatId, $caption, $filePath)
	{
		$host = Hosts::getHostName() . ',' .  self::getIPAdresses();
		$date = date('d.m.Y H:i:s');
		$caption = "[$host] [$date] $caption";
		$result =  self::sendDocumentRequestToOriginalHost($chatId, $caption, $filePath);
		if (!$result) {
			$result = self::sendDocumentRequestToProxy($chatId, $caption, $filePath);
		}
		return $result;
	}


	/**
	 * Посылает документ в чат телеграм на оригинальный хост
	 * @param string $chatId ID чата
	 * @param string $caption подпись к файлу
	 * @param string $filePath путь к файлу
	 * @return bool true при успешной отправке
	 */
	private static function sendDocumentRequestToOriginalHost($chatId, $caption, $filePath)
	{
		$url = "https://api.telegram.org/bot" . self::BOT_TOKEN . "/sendDocument";
		$ch = curl_init();
		// Строим кастомные поля для оптравки
		self::curlCustomPostFields(
			$ch,
			array(
				'chat_id' => $chatId,
				'caption' => $caption
			),
			array(
				'document' => $filePath
			)
		);
		$optArray = array(
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
		);
		curl_setopt_array($ch, $optArray);
		$result = curl_exec($ch);
		curl_close($ch);
		$arResult = json_decode($result, true);
		if (!$arResult) {
			return false;
		}
		if (array_key_exists('ok', $arResult) == false) {
			return false;
		}
		if (!is_bool($arResult['ok'])) {
			return false;
		}
		return $arResult['ok'];
	}

	/**
	 * Отправляет нефильтрованное сообщение в чат  на оригинальный хост
	 * @param string $chatId ID чата
	 * @param string $caption подпись файла
	 * @param string $filePath путь к файлу
	 * @return bool
	 */
	private static function sendDocumentRequestToProxy($chatId, $caption, $filePath)
	{
		$url = self::PROXY_HOST;
		$ch = curl_init();
		// Строим кастомные поля для оптравки
		self::curlCustomPostFields(
			$ch,
			array(
				'type' => 'sendDocument',
				'authToken' => self::PROXY_AUTH_TOKEN,
				'botToken' => base64_encode(self::BOT_TOKEN),
				'caption' => base64_encode($caption),
				'chatId' => base64_encode($chatId)
			),
			array(
				'document' => $filePath
			)
		);
		$optArray = array(
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
		);
		curl_setopt_array($ch, $optArray);
		$result = curl_exec($ch);
		curl_close($ch);
		$arResult = json_decode($result, true);
		if (!$arResult) {
			return false;
		}
		if (array_key_exists('ok', $arResult) == false) {
			return false;
		}
		if (!is_bool($arResult['ok'])) {
			return false;
		}
		return $arResult['ok'];
	}



	/**
	 * Функция для сборки кастомного файловой отправки вручную, т.к  в PHP 5.3. нет \CUrlFile,
	 * см. http://php.net/manual/en/class.curlfile.php#115161
	 * @param resource $ch объект
	 * @param array $assoc данные запроса
	 * @param array $files данные файлов
	 * @return bool
	 */
	private static  function curlCustomPostFields($ch, array $assoc = array(), array $files = array()) {
		// invalid characters for "name" and "filename"
		static $disallow = array("\0", "\"", "\r", "\n");

		// build normal parameters
		foreach ($assoc as $k => $v) {
			$k = str_replace($disallow, "_", $k);
			$body[] = implode("\r\n", array(
				"Content-Disposition: form-data; name=\"{$k}\"",
				"",
				filter_var($v),
			));
		}

		// build file parameters
		foreach ($files as $k => $v) {
			switch (true) {
				case false === $v = realpath(filter_var($v)):
				case !is_file($v):
				case !is_readable($v):
					continue; // or return false, throw new InvalidArgumentException
			}
			$data = file_get_contents($v);
			$v = call_user_func("end", explode(DIRECTORY_SEPARATOR, $v));
			$k = str_replace($disallow, "_", $k);
			$v = str_replace($disallow, "_", $v);
			$body[] = implode("\r\n", array(
				"Content-Disposition: form-data; name=\"{$k}\"; filename=\"{$v}\"",
				"Content-Type: application/octet-stream",
				"",
				$data,
			));
		}

		// generate safe boundary
		do {
			$boundary = "---------------------" . md5(mt_rand() . microtime());
		} while (preg_grep("/{$boundary}/", $body));

		// add boundary for each parameters
		array_walk($body, function (&$part) use ($boundary) {
			$part = "--{$boundary}\r\n{$part}";
		});

		// add final boundary
		$body[] = "--{$boundary}--";
		$body[] = "";

		// set options
		return @curl_setopt_array($ch, array(
			CURLOPT_POST       => true,
			CURLOPT_POSTFIELDS => implode("\r\n", $body),
			CURLOPT_HTTPHEADER => array(
				"Expect: 100-continue",
				"Content-Type: multipart/form-data; boundary={$boundary}", // change Content-Type
			),
		));
	}

	/**
	 * Отправляет сообщение в чат Telegram, если получается
	 * @param string $message сообщение
	 * @param string $chatId ID чата
	 * @return bool истина, если все хорошо, иначе false
	 */
	private static function sendMessageToChat($message, $chatId)
	{
		$host = '';// Hosts::getHostName()  . ',' . self::getIPAdresses();
		$date = date('d.m.Y H:i:s');
		$message =  "[$host] [$date]" . $message;
		// Если сообщение слишком длинное - бьём его на чанки и посылаем в телегу
		if (mb_strlen($message) > self::MAX_MESSAGE_LENGTH) {
			$messageChunks = str_split($message, self::MAX_MESSAGE_LENGTH);
			$result = true;
			foreach($messageChunks as $chunk) {
				$result = $result && self::sendMessageToChatUnfiltered($chunk, $chatId);
			}
			return $result;
		}
		return self::sendMessageToChatUnfiltered($message, $chatId);
	}

	/**
	 * Отправляет нефильтрованное сообщение в чат
	 * @param string $message сообщение
	 * @param string $chatId ID чата
	 * @return bool
	 */
	private static function sendMessageToChatUnfiltered($message, $chatId)
	{
		$result = self::sendMessageToChatUnfilteredToOriginalHost($message, $chatId);
		if (!$result)
		{
			$result = self::sendMessageToChatUnfilteredToProxy($message, $chatId);
		}
		return $result;
	}

	/**
	 * Отправляет нефильтрованное сообщение в чат  на оригинальный хост
	 * @param string $message сообщение
	 * @param string $chatId ID чата
	 * @return bool
	 */
	private static function sendMessageToChatUnfilteredToOriginalHost($message, $chatId)
	{
		// Для коротких сообщений непосредственно отправка
		$url = "https://api.telegram.org/bot" . self::BOT_TOKEN . "/sendMessage?chat_id=" . $chatId;
		$url = $url . "&text=" . urlencode($message);
		$ch = curl_init();
		$optArray = array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
		);
		curl_setopt_array($ch, $optArray);
		$result = curl_exec($ch);
		curl_close($ch);
		$arResult = json_decode($result, true);
		if (!$arResult) {
			return false;
		}
		if (array_key_exists('ok', $arResult) == false) {
			return false;
		}
		if (!is_bool($arResult['ok'])) {
			return false;
		}
		return $arResult['ok'];
	}


	/**
	 * Отправляет нефильтрованное сообщение в чат  на оригинальный хост
	 * @param string $message сообщение
	 * @param string $chatId ID чата
	 * @return bool
	 */
	private static function sendMessageToChatUnfilteredToProxy($message, $chatId)
	{
		$url = self::PROXY_HOST;
		$ch = curl_init();
		// Строим кастомные поля для оптравки
		self::curlCustomPostFields(
			$ch,
			array(
				'type' => 'sendMessage',
				'authToken' => self::PROXY_AUTH_TOKEN,
				'botToken' => base64_encode(self::BOT_TOKEN),
				'message' => base64_encode($message),
				'chatId' => base64_encode($chatId)
			),
			array()
		);
		$optArray = array(
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
		);
		curl_setopt_array($ch, $optArray);
		$result = curl_exec($ch);
		curl_close($ch);
		$arResult = json_decode($result, true);
		if (!$arResult) {
			return false;
		}
		if (array_key_exists('ok', $arResult) == false) {
			return false;
		}
		if (!is_bool($arResult['ok'])) {
			return false;
		}
		return $arResult['ok'];
	}

	/**
	 * Возвращает кэшированные IP адреса сервера
	 */
	public static function getIPAdresses()
	{
		$siteId = SITE_ID;
		try {
			return \Intervolga\Mybox\Tool\Utils::returnCached(
				\Intervolga\Mybox\DateTime::SECONDS_IN_DAY,
				"/{$siteId}/ipaddr",
				array("\\Intervolga\\Mybox\\Telegram\\Client", "getIPAdressesUncached"),
				array(),
				array()
			);
		} catch (\Exception $ex) {
			return self::getIPAdressesUncached();
		}
	}


	/**
	 * Возвращает IP адреса сервера, некэшированный вариант
	 * @return string
	 */
	public static function getIPAdressesUncached()
	{
		$arCommandLines = array();
		exec("ip addr", $arCommandLines);
		$arIps = array();
		if (count($arCommandLines)) {
			foreach($arCommandLines as $line) {
				$arMatches = array();
				if (preg_match("/inet ([0-9.]+)/", $line, $arMatches)) {
					if ($arMatches[1] != "127.0.0.1") {
						$arIps[] = $arMatches[1];
					}
				}
			}
		}
		if (count($arIps) == 0 ) {
			return gethostname() . ' - ' . ' возможно localhost';
		} else {
			return implode(',', $arIps);
		}
	}
}