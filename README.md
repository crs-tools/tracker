Tracker
=======

Requirements
------------

- >= PHP 5.6.0
- ext/curl
- ext/intl
- ext/mbstring
- ext/xsl
- pecl/apcu
- pecl/xdiff

- PostgreSQL >= 9.2 database

Note: libxdiff0 and pecl/xdiff are available as debian packages from 
  https://github.com/a-tze/libxdiff 
  https://github.com/a-tze/php5-xdiff

Install
-------

Try `composer install` to satisfy all requirements. Then

```bash
php -q Install/install.php
```

sets the database config in `Config/Config.php` and tries to setup tables
and initial data.
