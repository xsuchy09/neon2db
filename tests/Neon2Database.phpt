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

use Nette\Database\Connection;
use Nette\Database\IRow;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use Tester\Assert;
use Tester\Environment;
use xsuchy09\Neon2Db\DI\Configuration;

require_once __DIR__ . '/bootstrap.php';

/**
 * Class Neon2Database
 * @package xsuchy09\Neon2Db\Tests
 */
class Neon2Database extends \Tester\TestCase
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
	 * @var \xsuchy09\Neon2Db\Neon2Database
	 */
	protected \xsuchy09\Neon2Db\Neon2Database $neon2Database;

	/**
	 * @throws \Exception
	 */
	protected function setUp(): void
	{
		parent::setUp();

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

		$this->neon2Database = new \xsuchy09\Neon2Db\Neon2Database($this->connection, $this->configuration);
	}

	public function testGetDataFromFile(): void
	{
		$data = \xsuchy09\Neon2Db\Neon2Database::getDataFromFile(self::NEON_FILE);

		Assert::type('array', $data);
		Assert::same(8, count($data));
		Assert::contains('User module', $data);
		Assert::same('User module', $data['user.title']);
	}

	public function getDataFromNeonItems(): void
	{
		$neonItems = [
			'user' => [
				'help' => 'Help',
				'detail' => [
					'title' => 'User detail'
				]
			]
		];
		$data = \xsuchy09\Neon2Db\Neon2Database::getDataFromNeonItems($neonItems);

		Assert::type('array', $data);
		Assert::same(2, count($data));
		Assert::contains('User detail', $data);
		Assert::same('User detail', $data['user.detail.title']);
	}

	public function testUpdateFromFile(): void
	{
		$this->neon2Database->updateFromFile(self::NEON_FILE);
		$result = $this->connection->query('SELECT * FROM ?name', $this->configuration->getTable());
		Assert::same(8, $result->getRowCount());

		$item = $this->getItem('user.form.input.title.label');
		Assert::same('Title', $item->offsetGet($this->configuration->getMessage()));

		Assert::null($item->offsetGet($this->configuration->getUpdated()));

		$this->neon2Database->updateFromFile(self::NEON_FILE, false); // so update existing records too
		$item = $this->getItem('user.form.input.title.label');
		Assert::same('Title', $item->offsetGet($this->configuration->getMessage()));
		Assert::notNull($item->offsetGet($this->configuration->getUpdated()));
	}

	/**
	 * @param string $key
	 *
	 * @return IRow|null
	 */
	protected function getItem(string $key): ?IRow
	{
		return $this->connection->query('SELECT ?name, ?name FROM ?name WHERE ?name = ? AND ?name = ? AND ?name = ?',
			$this->configuration->getMessage(),
			$this->configuration->getUpdated(),
			$this->configuration->getTable(),
			$this->configuration->getFile(),
			'admin',
			$this->configuration->getLocale(),
			'cs_CZ',
			$this->configuration->getKey(),
			$key)->fetch();
	}
}

(new Neon2Database)->run();
