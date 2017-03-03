# Install

Add composer:

```
    "webtown/deployer-recipes": "~1.0.0"
```

# Bundle Commands

## Init

You or your organization can register custom `deploy.php` template file. The template can create other files also! The command find all template service, which has `webtown_deployer.template`
tag.

1. Create your own `DeployerTemplatesBundle`.
2. Create a container directory in the Resources directory: `Resources/Template/mySymfonyDeployerTemplateStructure`
3. Build your additional file structure:
```
Resources
  '- Template
       '- mySymfonyDeployerTemplateStructure
            |- app
            |    '- config
            |         '- Deployer
            |              |- server1_key.pub
            |              '- servers.yml
            |
            '- deploy.php
```
4. Create `MySymfonyDeployerTemplate` template class to the `/Template` directory:
```php
<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2017.02.17.
 * Time: 16:37
 */

namespace Tests\Webtown\DeployerRecipesBundle\Template;

use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Filesystem\Filesystem;
use Webtown\DeployerRecipesBundle\Template\AbstractDirectoryTwigTemplate;

class MySymfonyDeployerTemplate extends AbstractDirectoryTwigTemplate
{
    // Vars for templates
    protected $type;
    protected $info;
    protected $username;
    protected $host;

    public function getDirectory()
    {
        return implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            '..',
            'Resources',
            'Template',
            'mySymfonyDeployerTemplateStructure',
        ]);
    }
    
    public function build(InputInterface $input, OutputInterface $output, Command $command)
    {
        // Ask informations
        $helper = $command->getHelper('question');
        
        $typeQuestion = new Question('Please set type', false);
        $this->type = $helper->ask($input, $output, $typeQuestion);
        
        $infoQuestion = new Question('Please set info', false);
        $this->info = $helper->ask($input, $output, $infoQuestion);
        
        $usernameQuestion = new ChoiceQuestion(
            'Please select username',
            ['root', 'admin', 'barfoo'],
            0
        );
        $this->username = $helper->ask($input, $output, $usernameQuestion);
        
        $hostQuestion = new Question('Please set host', 'company.com');
        $this->host = $helper->ask($input, $output, $hostQuestion);
    
        parent::build($input, $output, $command);
    }
    
    public function getTemplateParameters()
    {
        return [
            'name'      => 'Test',
            'type'      => $this->type,
            'info'      => $this->info,
            'username'  => $this->username,
            'host'      => $this->host,
        ];
    }

    public function getName()
    {
        return 'My Symfony Deployer Template';
    }
}
```

# Recipes

## Symfony extra

Add new tasks:
- `database:raw-create`: Try to create database if not exists (from command line, without doctrine!) You can enable it with the `enable_mysql_database_create`
- `deploy:init-parameters-yml`: Ask the parameters.
- `database:migrate:rollback`: Migration rollback. You have to set the `use_database_migration_strategy` true!
- `deploy:force-cache-clean`: Force cache clean. Private!

```php
<?php

namespace Deployer;

// include base
require 'vendor/deployer/deployer/recipe/symfony3.php';
// include extension
require 'vendor/webtown/deployer-recipes/recipes/symfony.php';

const SERVER_CONFIGURATION_FILE_PATH = 'app/config/deploy/servers.yml';
if (!file_exists(SERVER_CONFIGURATION_FILE_PATH)) {
    throw new \Exception(sprintf('Nem lett még létrehozva a szerver konfigurációs fájlod a `%s` helyen!', SERVER_CONFIGURATION_FILE_PATH));
}
serverList(SERVER_CONFIGURATION_FILE_PATH);

set('repository', 'git@github.com:webtown-php/project.git');
set('enable_mysql_database_create', true);
```

## Kunstmaan

Kunstmaan specific settings. Include the `vendor/webtown/deployer-recipes/recipes/kunstmaan.php` file after `symfony.php` or `symfony3.php` recipe:

```php
<?php

namespace Deployer;

// include base
require 'vendor/deployer/deployer/recipe/symfony3.php';
require 'vendor/webtown/deployer-recipes/recipes/common.php';
// include extension (it contains WT symfony.php)
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

## Load fixtures

> This will delete the whole database and rebuild it!

You can enable on each server with the `load_fixtures` parameter! Default value is `false`.

```php
<?php

namespace Deployer;

// include base
require 'vendor/deployer/deployer/recipe/symfony3.php';
// include extension
require 'vendor/webtown/deployer-recipes/recipes/common.php';
require 'vendor/webtown/deployer-recipes/recipes/symfony.php';
require 'vendor/webtown/deployer-recipes/recipes/load-fixtures.php';

const SERVER_CONFIGURATION_FILE_PATH = 'app/config/deploy/servers.yml';
if (!file_exists(SERVER_CONFIGURATION_FILE_PATH)) {
    throw new \Exception(sprintf('Nem lett még létrehozva a szerver konfigurációs fájlod a `%s` helyen!', SERVER_CONFIGURATION_FILE_PATH));
}
serverList(SERVER_CONFIGURATION_FILE_PATH);

set('repository', 'git@github.com:webtown-php/project.git');
```


## Maintenance

Add maintenance function, you can lock or unlock the site. You need a maintenance template html. The default path is defined in `maintenance_template` parameter. Default value is `maintenance.html.tpl` or `app/Resources/views/maintenance.html` file in Symfony projects.

1. Create an `maintenance.html.tpl` or `app/Resources/views/maintenance.html`. This file will see users during the maintenance.
2. Configure the webserver. If `maintenance.html` exists in the document root, force show this file with 503 status!
    - Apache eg:
        ```
        ErrorDocument 503 /maintenance.html
        RewriteEngine On
        RewriteCond %{REQUEST_URI} !\.(css|js|gif|jpg|png)$
        RewriteCond %{DOCUMENT_ROOT}/maintenance.html -f
        RewriteCond %{SCRIPT_FILENAME} !maintenance.html
        RewriteRule ^.*$  -  [redirect=503,last]
        ```
    - Nginx eg:
        ```
        if (-f \$document_root/maintenance.html) {
          return 503;
        }
        error_page 503 @maintenance;
        location @maintenance {
          rewrite  ^(.*)$  /maintenance.html last;
          break;
        }
        ```
3. Register the tasks (use `before` and `after`)

deploy.php file:

```php
<?php

namespace Deployer;

// include base
require 'vendor/deployer/deployer/recipe/symfony3.php';
// include extension
require 'vendor/webtown/deployer-recipes/recipes/common.php';
require 'vendor/webtown/deployer-recipes/recipes/maintenance.php'; // <-- first!!!!
require 'vendor/webtown/deployer-recipes/recipes/symfony.php';     // <-- second!!!

// ...

// !!!! Register tasks
before('database:migrate', 'maintenance:lock');
// You don't have to unlock after deploy, because there is a new, clean `web` path without maintenance.html!
after('rollback', 'maintenance:unlock');
```

Console commands:
```
$ bin/dep rollback prod --keep-maintenance-file
// ...

$ bin/dep maintenance:lock prod 
✔ Executing task maintenance:lock
$ bin/dep maintenance:unlock prod
✔ Executing task maintenance:unlock
```
