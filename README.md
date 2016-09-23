# Install

Add composer:

```
    "webtown/deployer-recipes": "dev-master"
```

# Kunstmaan

Include the `vendor/webtown/deployer-recipes/recipes/kunstmaan.php` file after `symfony.php` recipe:

```php
<?php

require 'vendor/deployer/deployer/recipe/symfony.php';
require 'vendor/webtown/deployer-recipes/recipes/kunstmaan.php';

const SERVER_CONFIGURATION_FILE_PATH = 'app/config/deploy/servers.yml';
if (!file_exists(SERVER_CONFIGURATION_FILE_PATH)) {
    throw new \Exception(sprintf('Nem lett még létrehozva a szerver konfigurációs fájlod a `%s` helyen!', SERVER_CONFIGURATION_FILE_PATH));
}
serverList(SERVER_CONFIGURATION_FILE_PATH);

set('repository', 'git@github.com:webtown-php/project.git');
```

Make the config file:

```yml
prod:
    stage:          prod
    host:           host.com
    user:           user
    # if you are using key
    forward_agent:  true
    # if you are using different php version
    'bin/php':      /usr/local/php/php5.6/bin/php
    'bin/composer': /usr/local/php/php5.6/bin/php /usr/local/bin/composer
    deploy_path:    /var/www/project
```

Then:

```
$ bin/dep deploy prod [-vvv]
```

Rollback:

```
$ bin/dep rollback prod [-vvv]
```

If you have created an `app/Resources/views/maintenance.html` file, you can lock or unlock the site, and rollback without unlock (keep the previous lock file):

```
$ bin/dep rollback prod --keep-maintenance-file
// ...

$ bin/dep maintenance:lock prod 
✔ Executing task maintenance:lock
$ bin/dep maintenance:unlock prod
✔ Executing task maintenance:unlock
```
