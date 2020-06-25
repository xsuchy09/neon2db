<?php
/******************************************************************************
 * Author: Petr Suchy (xsuchy09) <suchy@wamos.cz> <https://www.wamos.cz>
 * Project: nette-neon2db
 * Date: 25.06.20
 * Time: 13:57
 * Copyright: (c) Petr Suchy (xsuchy09) <suchy@wamos.cz> <http://www.wamos.cz>
 *****************************************************************************/

declare(strict_types=1);

namespace xsuchy09\Neon2Db;

use Nette\Database\Connection;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use xsuchy09\Neon2Db\DI\Configuration;

/**
 * Class Neon2Database
 * @package xsuchy09\Neon2Db
 */
class Neon2Database
{
	/**
	 * @var Connection
	 */
	protected Connection $connection;

	/**
	 * @var Configuration
	 */
	protected Configuration $configuration;

	/**
	 * Neon2Database constructor.
	 *
	 * @param Connection    $connection
	 * @param Configuration $configuration
	 */
	public function __construct(Connection $connection, Configuration $configuration)
	{
		$this->connection = $connection;
		$this->configuration = $configuration;
	}

	/**
	 * @param string $path
	 * @param bool   $justInsertNew
	 */
	public function updateFromFile(string $path, bool $justInsertNew = true): void
	{
		$filename = pathinfo($path, PATHINFO_FILENAME);
		$data = self::getDataFromFile($path);
		$dbData = $this->prepareData($filename, $data);
		$this->insert2Database($dbData, $justInsertNew);
	}

	/**
	 * @param string $path
	 *
	 * @return array
	 */
	public static function getDataFromFile(string $path): array
	{
		$fileContent = FileSystem::read($path);
		$neonItems = Neon::decode($fileContent);
		return self::getDataFromNeonItems($neonItems);
	}

	/**
	 * @param array       $neonItems
	 * @param string|null $key
	 * @param array       $data
	 *
	 * @return array
	 */
	public static function getDataFromNeonItems(array $neonItems, string $key = null, array &$data = []): array
	{
		foreach ($neonItems as $itemKey => $itemValue) {
			if ($key !== null) {
				$itemKey = $key . '.' . $itemKey;
			}
			if (true === is_array($itemValue)) {
				self::getDataFromNeonItems($itemValue, $itemKey, $data);
			} else {

				$data[$itemKey] = $itemValue;
			}
		}
		return $data;
	}

	/**
	 * @param string $filenameAndLocale
	 * @param array  $data
	 *
	 * @return array
	 */
	protected function prepareData(string $filenameAndLocale, array $data): array
	{
		$dbData = [];
		$locale = pathinfo($filenameAndLocale, PATHINFO_EXTENSION);
		$filename = pathinfo($filenameAndLocale, PATHINFO_FILENAME);

		foreach ($data as $key => $value) {
			$dbData[] = [
				$this->configuration->getFile() => $filename, // basic name - without locale
				$this->configuration->getLocale() => $locale, // locale from original filename
				$this->configuration->getKey() => $key,
				$this->configuration->getMessage() => $value
			];
		}

		return $dbData;
	}

	/**
	 * @param string $key
	 *
	 * @return int|null
	 */
	protected function getTranslateId(string $key): ?int
	{
		return $this->connection->query('SELECT ?name FROM ?name WHERE ?name = ?', $this->configuration->getId(), $this->configuration->getTable(), $this->configuration->getKey(), $key)->fetchField();
	}

	/**
	 * @param array  $data
	 * @param bool   $justInsertNew
	 */
	protected function insert2Database(array $data, bool $justInsertNew = true): void
	{
		foreach ($data as $item) {
			$translateId = $this->getTranslateId($item[$this->configuration->getKey()]);
			if ($translateId === null) {
				$this->connection->query('INSERT INTO ?name', $this->configuration->getTable(), $item);
			} else if ($justInsertNew === false) {
				$item[$this->configuration->getUpdated()] = 'NOW()';
				$this->connection->query('UPDATE ?name SET', $this->configuration->getTable(), $item, 'WHERE ?name = ?', $this->configuration->getId(), $translateId);
			}
		}
	}
}