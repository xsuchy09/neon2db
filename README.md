# nette-neon2db
Library to save data from neon file to the database and back to the neon file. It can be helpful for managing translates in the database for [Kdyby/Translation](https://github.com/Kdyby/Translation).

## Install
```composer
composer require xsuchy09/neon2db
```

## Usage
```php
$connection = new \Nette\Database\Connection('dsn', 'user', 'password');
$configuration = new \xsuchy09\Neon2Db\DI\Configuration();
$configuration->setTable('public.translation');

$neon2Database = new \xsuchy09\Neon2Db\Neon2Database($connection, $configuration);
// neon files should be named as name of the file dot locale dot neon - examples:
// admin.cs_CZ.neon
// base.cs_CZ.neon
// ...

// these files you have probably as default in git
$neon2Database->insertFromDir('path to the dir with neon files to import into the database');
// these files should use your app ... they should be regenerate after any update in database - use this method to re/generate them
$neon2Database->saveToNeon('path to the dir where files from database data should be exported');
```

That's all :)

Any comments and pull requests are welcome.