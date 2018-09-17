CRS Ticket Tracker
==================

The Ticket Tracker is a web platform tracking process of video recording and
ingest sources and video encoding progress. It guides users through manual processes like editing and checking and provides an API for [scripts](https://github.com/crs-tools/crs-scripts) doing post processing and encoding.


Requirements
------------

- \>= PHP 7.1.0
  - ext/curl
  - ext/intl
  - ext/mbstring
  - ext/openssl
  - ext/xsl
  - ext/xmlrpc
  - pecl/apcu
  - pecl/xdiff

- PostgreSQL >= 9.2 database
  - ltree feature, often found in separate "-contrib" packages

Note: [libxdiff0](https://github.com/a-tze/libxdiff ) and [pecl/xdiff](https://github.com/a-tze/php7-xdiff) are available as debian packages.


Install
-------
After checkout run

```bash
git submodule init
git submodule update
```

Then you may try `composer install` to satisfy all requirements. Then running

```bash
./scripts/install
```

sets the database config in `src/Config/Config.php` and tries to setup tables
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


License
-------

Copyright 2018 Jannes Jeising  
Copyright 2018 Peter Große

Licensed under the Apache License, Version 2.0.  
Excluding graphics and name, please see LICENSE file for details.
