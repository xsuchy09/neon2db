<?php
/**
 * @dataProvider database.ini
 */

/******************************************************************************
 * Author: Petr Suchy (xsuchy09) <suchy@wamos.cz> <https://www.wamos.cz>
 * Project: nette-neon2db
 * Date: 25.06.20
 * Time: 15:13
 * Copyright: (c) Petr Suchy (xsuchy09) <suchy@wamos.cz> <http://www.wamos.cz>
 *****************************************************************************/

declare(strict_types=1);

namespace xsuchy09\Neon2Db\Tests;

use Exception;
use Nette\Database\Connection;
use Nette\Database\IRow;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use Tester\Assert;
use Tester\Environment;
use xsuchy09\Neon2Db\Configuration;
use xsuchy09\Neon2Db\Neon2Database;

require_once __DIR__ . '/bootstrap.php';

/**
 * Class Neon2DatabaseTest
 * @package xsuchy09\Neon2Db\Tests
 */
class Neon2DatabaseTest extends \Tester\TestCase
{
	const NEON_FILE = __DIR__ . '/data/admin.cs_CZ.neon';

	/**
	 * @var Configuration
	 */
	protected Configuration $configuration;

	/**
	 * @var Connection
	 */
	protected Connection $connection;

	/**
	 * @var Neon2Database
	 */
	protected Neon2Database $neon2Database;

	/**
	 * @throws Exception
	 */
	protected function setUp(): void
	{
		parent::setUp();

		Environment::lock('database', __DIR__ . '/tmp');

		$args = Environment::loadData();

		$this->configuration = new Configuration();
		$this->connection = new Connection($args['dsn'], $args['user'], $args['password']);

		$this->connection->query('CREATE TABLE IF NOT EXISTS ?name (?name serial not null, ?name text not null, ?name text not null, ?name text not null, ?name text not null, ?name timestamp without time zone DEFAULT now() NOT NULL, ?name timestamp without time zone)',
			$this->configuration->getTable(),
			$this->configuration->getId(),
			$this->configuration->getLocale(),
			$this->configuration->getFile(),
			$this->configuration->getKey(),
			$this->configuration->getMessage(),
			$this->configuration->getCreated(),
			$this->configuration->getUpdated());

		$this->connection->query('ALTER TABLE ?name DROP CONSTRAINT IF EXISTS ?name', $this->configuration->getTable(), $this->configuration->getId() . '_pk');
		$this->connection->query('ALTER TABLE ONLY ?name ADD CONSTRAINT ?name PRIMARY KEY (?name)', $this->configuration->getTable(), $this->configuration->getId() . '_pk', $this->configuration->getId());

		$this->connection->query('TRUNCATE TABLE ?name RESTART IDENTITY CASCADE', $this->configuration->getTable());

		$this->neon2Database = new Neon2Database($this->connection, $this->configuration);
	}

	public function testGetDataFromNeon(): void
	{
		$data = Neon2Database::getDataFromNeon(self::NEON_FILE);

		Assert::type('array', $data);
		Assert::same(9, count($data));
		Assert::contains('Uživatelský modul', $data);
		Assert::same('Uživatelský modul', $data['user.title']);
	}

	public function testGetDataFromNeonItems(): void
	{
		$neonItems = [
			'user' => [
				'help' => 'Help',
				'detail' => [
					'title' => 'User detail'
				]
			]
		];
		$data = Neon2Database::getDataFromNeonItems($neonItems);

		Assert::type('array', $data);
		Assert::same(2, count($data));
		Assert::contains('User detail', $data);
		Assert::same('User detail', $data['user.detail.title']);
	}

	public function testInsertFromNeon(): void
	{
		$this->neon2Database->insertFromNeon(self::NEON_FILE, true);
		$result = $this->connection->query('SELECT * FROM ?name', $this->configuration->getTable());
		Assert::same(9, $result->getRowCount());

		$item = $this->getItem('admin', 'cs_CZ', 'user.form.input.name.label');
		Assert::same('Název', $item->offsetGet($this->configuration->getMessage()));

		Assert::null($item->offsetGet($this->configuration->getUpdated()));

		$this->neon2Database->insertFromNeon(self::NEON_FILE, false); // so update existing records too
		$item = $this->getItem('admin', 'cs_CZ', 'user.form.input.name.label');
		Assert::same('Název', $item->offsetGet($this->configuration->getMessage()));
		Assert::notNull($item->offsetGet($this->configuration->getUpdated()));
	}

	public function testUpdateFromNeon(): void
	{
		$this->neon2Database->updateFromNeon(self::NEON_FILE, true);
		$result = $this->connection->query('SELECT * FROM ?name', $this->configuration->getTable());
		Assert::same(9, $result->getRowCount());

		$item = $this->getItem('admin', 'cs_CZ', 'user.form.input.name.label');
		Assert::same('Název', $item->offsetGet($this->configuration->getMessage()));

		Assert::null($item->offsetGet($this->configuration->getUpdated()));

		$this->neon2Database->updateFromNeon(self::NEON_FILE, false); // so update existing records too
		$item = $this->getItem('admin', 'cs_CZ', 'user.form.input.name.label');
		Assert::same('Název', $item->offsetGet($this->configuration->getMessage()));
		Assert::notNull($item->offsetGet($this->configuration->getUpdated()));
	}

	public function testSaveToNeon(): void
	{
		$this->neon2Database->insertFromNeon(self::NEON_FILE);
		$this->neon2Database->saveToNeon(__DIR__ . '/data/export/', 'admin', 'cs_CZ');
		Assert::equal(
			Neon::decode(FileSystem::read(self::NEON_FILE)),
			Neon::decode(FileSystem::read(__DIR__ . '/data/export/' . pathinfo(self::NEON_FILE, PATHINFO_BASENAME)))
		);
	}

	public function testSaveToNeonAll(): void
	{
		$this->neon2Database->insertFromDir(pathinfo(self::NEON_FILE, PATHINFO_DIRNAME));
		$this->neon2Database->saveToNeon(__DIR__ . '/data/export/');
		$dirPath = pathinfo(self::NEON_FILE, PATHINFO_DIRNAME);
		$dir = new \DirectoryIterator($dirPath);
		foreach ($dir as $file) {
			if ($file->isDot() === false && $file->isFile() === true && $file->getExtension() === 'neon') {
				Assert::equal(
					Neon::decode(FileSystem::read($file->getRealPath())),
					Neon::decode(FileSystem::read(__DIR__ . '/data/export/' . $file->getBasename()))
				);
			}
		}
	}
	
	public function testInsertFromDir(): void
	{
		$this->neon2Database->insertFromDir(pathinfo(self::NEON_FILE, PATHINFO_DIRNAME));
		$result = $this->connection->query('SELECT * FROM ?name', $this->configuration->getTable());
		Assert::same(18, $result->getRowCount());
		
		$item = $this->getItem('admin', 'cs_CZ', 'user.form.input.name.label');
		Assert::same('Název', $item->offsetGet($this->configuration->getMessage()));
		$item = $this->getItem('admin', 'en_US', 'user.form.input.name.label');
		Assert::same('Name', $item->offsetGet($this->configuration->getMessage()));

		Assert::null($item->offsetGet($this->configuration->getUpdated()));

		$this->neon2Database->insertFromDir(pathinfo(self::NEON_FILE, PATHINFO_DIRNAME), false); // so update existing records too
		$item = $this->getItem('admin', 'cs_CZ', 'user.form.input.name.label');
		Assert::same('Název', $item->offsetGet($this->configuration->getMessage()));
		Assert::notNull($item->offsetGet($this->configuration->getUpdated()));
		$item = $this->getItem('admin', 'en_US', 'user.form.input.name.label');
		Assert::same('Name', $item->offsetGet($this->configuration->getMessage()));
		Assert::notNull($item->offsetGet($this->configuration->getUpdated()));
	}

	public function testUpdateFromDir(): void
	{
		$this->neon2Database->updateFromDir(pathinfo(self::NEON_FILE, PATHINFO_DIRNAME), true);
		$result = $this->connection->query('SELECT * FROM ?name', $this->configuration->getTable());
		Assert::same(18, $result->getRowCount());

		$item = $this->getItem('admin', 'cs_CZ', 'user.form.input.name.label');
		Assert::same('Název', $item->offsetGet($this->configuration->getMessage()));
		$item = $this->getItem('admin', 'en_US', 'user.form.input.name.label');
		Assert::same('Name', $item->offsetGet($this->configuration->getMessage()));

		Assert::null($item->offsetGet($this->configuration->getUpdated()));

		$this->neon2Database->updateFromDir(pathinfo(self::NEON_FILE, PATHINFO_DIRNAME), false); // so update existing records too
		$item = $this->getItem('admin', 'cs_CZ', 'user.form.input.name.label');
		Assert::same('Název', $item->offsetGet($this->configuration->getMessage()));
		Assert::notNull($item->offsetGet($this->configuration->getUpdated()));
		$item = $this->getItem('admin', 'en_US', 'user.form.input.name.label');
		Assert::same('Name', $item->offsetGet($this->configuration->getMessage()));
		Assert::notNull($item->offsetGet($this->configuration->getUpdated()));
	}

	/**
	 * @param string $file
	 * @param string $locale
	 * @param string $key
	 *
	 * @return IRow|null
	 */
	protected function getItem(string $file, string $locale, string $key): ?IRow
	{
		return $this->connection->query('SELECT ?name, ?name FROM ?name WHERE ?name = ? AND ?name = ? AND ?name = ?',
			$this->configuration->getMessage(),
			$this->configuration->getUpdated(),
			$this->configuration->getTable(),
			$this->configuration->getFile(), 
			$file,
			$this->configuration->getLocale(),
			$locale,
			$this->configuration->getKey(),
			$key)->fetch();
	}
}

(new Neon2DatabaseTest)->run();
