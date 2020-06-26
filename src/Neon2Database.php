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

use DirectoryIterator;
use Nette\Database\Connection;
use Nette\Database\IRow;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;

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
	public function insertFromNeon(string $path, bool $justInsertNew = true): void
	{
		$filename = pathinfo($path, PATHINFO_FILENAME);
		$data = self::getDataFromNeon($path);
		$dbData = $this->prepareData($filename, $data);
		$this->insert2Database($dbData, $justInsertNew);
	}

	/**
	 * @param string $path
	 * @param bool   $justInsertNew
	 */
	public function updateFromNeon(string $path, bool $justInsertNew = false): void
	{
		$filename = pathinfo($path, PATHINFO_FILENAME);
		$data = self::getDataFromNeon($path);
		$dbData = $this->prepareData($filename, $data);
		$this->insert2Database($dbData, $justInsertNew);
	}

	/**
	 * @param string $path
	 * @param bool   $justInsertNew
	 */
	public function insertFromDir(string $path, bool $justInsertNew = true): void
	{
		$dir = new DirectoryIterator($path);
		foreach ($dir as $file) {
			if ($file->isDot() === false && $file->isFile() === true && strtolower($file->getExtension()) === 'neon') {
				$this->insertFromNeon($file->getRealPath(), $justInsertNew);
			}
		}
	}

	/**
	 * @param string $path
	 * @param bool   $justInsertNew
	 */
	public function updateFromDir(string $path, $justInsertNew = false): void
	{
		$dir = new DirectoryIterator($path);
		foreach ($dir as $file) {
			if ($file->isDot() === false && $file->isFile() === true && strtolower($file->getExtension()) === 'neon') {
				$this->updateFromNeon($file->getRealPath(), $justInsertNew);
			}
		}
	}

	/**
	 * @param string $path
	 *
	 * @return array
	 */
	public static function getDataFromNeon(string $path): array
	{
		$fileContent = FileSystem::read($path);
		$neonItems = Neon::decode($fileContent);
		return self::getDataFromNeonItems($neonItems);
	}

	/**
	 * @param array           $neonItems
	 * @param string|int|null $key
	 * @param array           $data
	 *
	 * @return array
	 */
	public static function getDataFromNeonItems(array $neonItems, $key = null, array &$data = []): array
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
	 * @param string      $dirPath
	 * @param string|null $file
	 * @param string|null $locale
	 */
	public function saveToNeon(string $dirPath, ?string $file = null, ?string $locale = null): void
	{
		// get all data from database and save it to neon files
		$translates = $this->getDataFromDatabase($file, $locale);
		if (count($translates) > 0) {
			$file = null;
			$locale = null;
			$neonData = [];
			foreach ($translates as $translate) {
				if (($file !== null && $file !== $translate->offsetGet('file')) ||
					($locale !== null && $locale !== $translate->offsetGet('locale'))) {
					// save prev messages to file
					FileSystem::write($dirPath . $file . '.' . $locale . '.neon', Neon::encode($neonData, Neon::BLOCK), 0664);
					$neonData = [];
				}
				$file = $translate->offsetGet('file');
				$locale = $translate->offsetGet('locale');
				$keys = explode('.', $translate->offsetGet('key'));

				$this->createArray($neonData, $keys, $translate->offsetGet('message'));
			}
			if (count($neonData) > 0) {
				FileSystem::write($dirPath . $file . '.' . $locale . '.neon', Neon::encode($neonData,Neon::BLOCK), 0664);
			}
		}
	}

	/**
	 * @param string|null $file
	 * @param string|null $locale
	 *
	 * @return array|IRow[]
	 */
	protected function getDataFromDatabase(?string $file = null, ?string $locale = null): array
	{
		$conditions = [];
		if ($file !== null) {
			$conditions['file'] = $file;
		}
		if ($locale !== null) {
			$conditions['locale'] = $locale;
		}
		if (count($conditions) > 0) {
			$result = $this->connection->query('SELECT ?name, ?name, ?name, ?name FROM ?name WHERE',
				$this->configuration->getFile(),
				$this->configuration->getLocale(),
				$this->configuration->getKey(),
				$this->configuration->getMessage(),
				$this->configuration->getTable(),
				$conditions,
				'ORDER BY ?name, ?name, ?name',
				$this->configuration->getFile(),
				$this->configuration->getLocale(),
				$this->configuration->getKey()
			);
		} else {
			$result = $this->connection->query('SELECT ?name, ?name, ?name, ?name FROM ?name ORDER BY ?name, ?name, ?name',
				$this->configuration->getFile(),
				$this->configuration->getLocale(),
				$this->configuration->getKey(),
				$this->configuration->getMessage(),
				$this->configuration->getTable(),
				$this->configuration->getFile(),
				$this->configuration->getLocale(),
				$this->configuration->getKey()
			);
		}
		return $result->fetchAll();
	}

	/**
	 * @param array  $array
	 * @param array  $keys
	 * @param string $message
	 */
	protected function createArray(array &$array, array $keys, string $message): void
	{
		$temp =& $array;

		foreach ($keys as $key) {
			$temp =& $temp[$key];
		}
		$temp = $message;
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
	 * @param array $item
	 *
	 * @return int|null
	 */
	protected function getTranslateId(array $item): ?int
	{
		return $this->connection->query('SELECT ?name FROM ?name WHERE ?name = ? AND ?name = ? AND ?name = ?',
			$this->configuration->getId(),
			$this->configuration->getTable(),
			$this->configuration->getFile(),
			$item[$this->configuration->getFile()],
			$this->configuration->getLocale(),
			$item[$this->configuration->getLocale()],
			$this->configuration->getKey(),
			$item[$this->configuration->getKey()])->fetchField();
	}

	/**
	 * @param array  $data
	 * @param bool   $justInsertNew
	 */
	protected function insert2Database(array $data, bool $justInsertNew = true): void
	{
		foreach ($data as $item) {
			$translateId = $this->getTranslateId($item);
			if ($translateId === null) {
				$this->connection->query('INSERT INTO ?name', $this->configuration->getTable(), $item);
			} else if ($justInsertNew === false) {
				$item[$this->configuration->getUpdated()] = 'NOW()';
				$this->connection->query('UPDATE ?name SET', $this->configuration->getTable(), $item, 'WHERE ?name = ?', $this->configuration->getId(), $translateId);
			}
		}
	}
}