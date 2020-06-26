<?php
/******************************************************************************
 * Author: Petr Suchy (xsuchy09) <suchy@wamos.cz> <https://www.wamos.cz>
 * Project: nette-neon2db
 * Date: 25.06.20
 * Time: 14:00
 * Copyright: (c) Petr Suchy (xsuchy09) <suchy@wamos.cz> <http://www.wamos.cz>
 *****************************************************************************/

declare(strict_types=1);

namespace xsuchy09\Neon2Db;

/**
 * Class Configuration
 * @package xsuchy09\Neon2Db
 */
class Configuration
{
	/**
	 * @var string
	 */
	private string $table = 'translation';

	/**
	 * @var string
	 */
	private string $id = 'translation_id';

	/**
	 * @var string
	 */
	private string $file = 'file';

	/**
	 * @var string
	 */
	private string $key = 'key';

	/**
	 * @var string
	 */
	private string $locale = 'locale';

	/**
	 * @var string
	 */
	private string $message = 'message';

	/**
	 * @var string
	 */
	private string $created = 'created';

	/**
	 * @var string
	 */
	private string $updated = 'updated';

	/**
	 * @return string
	 */
	public function getTable(): string
	{
		return $this->table;
	}

	/**
	 * @param string $table
	 *
	 * @return Configuration
	 */
	public function setTable(string $table): Configuration
	{
		$this->table = $table;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getId(): string
	{
		return $this->id;
	}

	/**
	 * @param string $id
	 *
	 * @return Configuration
	 */
	public function setId(string $id): Configuration
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFile(): string
	{
		return $this->file;
	}

	/**
	 * @param string $file
	 *
	 * @return Configuration
	 */
	public function setFile(string $file): Configuration
	{
		$this->file = $file;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getKey(): string
	{
		return $this->key;
	}

	/**
	 * @param string $key
	 *
	 * @return Configuration
	 */
	public function setKey(string $key): Configuration
	{
		$this->key = $key;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLocale(): string
	{
		return $this->locale;
	}

	/**
	 * @param string $locale
	 *
	 * @return Configuration
	 */
	public function setLocale(string $locale): Configuration
	{
		$this->locale = $locale;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getMessage(): string
	{
		return $this->message;
	}

	/**
	 * @param string $message
	 *
	 * @return Configuration
	 */
	public function setMessage(string $message): Configuration
	{
		$this->message = $message;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCreated(): string
	{
		return $this->created;
	}

	/**
	 * @param string $created
	 *
	 * @return Configuration
	 */
	public function setCreated(string $created): Configuration
	{
		$this->created = $created;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getUpdated(): string
	{
		return $this->updated;
	}

	/**
	 * @param string $updated
	 *
	 * @return Configuration
	 */
	public function setUpdated(string $updated): Configuration
	{
		$this->updated = $updated;
		return $this;
	}
}