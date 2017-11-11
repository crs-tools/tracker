CRS Ticket Tracker
==================

The Ticket Tracker is a web plattform tracking process of video recording and
ingest sources and video encoding progress. It guides users through manuell processes like cutting and checking and provides an API for [scripts](https://github.com/crs-tools/crs-scripts) doing postprocessing and encoding.

Requirements
------------

- \>= PHP 5.6.0
  - ext/curl
  - ext/intl
  - ext/mbstring
  - ext/xsl
  - pecl/apcu
  - pecl/xdiff

- PostgreSQL >= 9.2 database

Note: [libxdiff0](https://github.com/a-tze/libxdiff ) and [pecl/xdiff](https://github.com/a-tze/php5-xdiff) are available as debian packages.

Install
-------

Try `composer install` to satisfy all requirements. Then

```bash
php -q Install/install.php
```

sets the database config in `Config/Config.php` and tries to setup tables
and initial data.


Contribute
----------

We welcome any contributions and pull requests.
Please open an issue before implementing big features or working on large
reworks as there may be overlaps with existing development.
We may not accept all requests if we don't see fit or certain quality standards
are not met.

Contributors may have to a agree to a Contributors License Agreement allowing
relicensing, soon.

