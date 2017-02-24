<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputOption;

set('load_fixtures', false);
option('load-fixtures', null, InputOption::VALUE_NONE, 'Load fixtures.');
task('database:load-fixtures', function() {
    if (input()->getOption('load-fixtures') || get('load_fixtures')) {
        set('enable_mysql_database_drop', true);
        run('{{env_vars}} {{bin/php}} {{bin/console}} doctrine:fixtures:load {{console_options}}');
    }
})->desc('Load the fixtures');

before('database:mysql:raw-create', 'database:mysql:raw-drop');
after('database:migrate', 'database:load-fixtures');
