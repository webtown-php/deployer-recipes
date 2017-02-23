<?php

namespace Deployer;

use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/*
 * Register new tasks:
 *  - deploy:init-parameters-yml
 */

// Ask questions?
set('interaction', true);
set('doctrine_migration_path', 'app/DoctrineMigrations');
set('use_database_migration_strategy', false);
set('enable_mysql_database_create', false);
set('parameters_file', 'app/config/parameters.yml');

// Only use in maintenance.php recipe!
set('maintenance_template', 'app/Resources/views/maintenance.html');

/**
 * Init parameters.yml
 *
 * Rákérdez a paraméterkre
 */
task('deploy:init-parameters-yml', function() {
    if (get('interaction')) {
        $composerConfigString = run('cat {{release_path}}/composer.json');
        if ($composerConfigString->toString()) {
            $composerConfig = json_decode($composerConfigString, true);
            $distFiles = isset($composerConfig['extra']['incenteev-parameters'])
                ? $composerConfig['extra']['incenteev-parameters']
                : []
            ;

            /** @var string|array $distFile */
            foreach ($distFiles as $distFile) {
                // get filename
                $distFile = is_array($distFile) ? $distFile['file'] : $distFile;
                set('config_yml_path', '{{release_path}}/' . $distFile);

                $result = run('if [[ -f {{config_yml_path}} && ! -s {{config_yml_path}} ]] ; then echo "1" ; else echo "0" ; fi');
                if ($result->toString() == '1') {
                    writeln(sprintf('Set the `%s` config file parameters', $distFile));
                    $ymlParser = new Parser();
                    $parameters = $ymlParser->parse((string) run('cat {{config_yml_path}}.dist'));
                    $newParameters = [];
                    foreach ($parameters['parameters'] as $key => $default) {
                        $value = ask($key, $default);
                        $newParameters[$key] = $value;
                    }
                    $ymlDumper = new Dumper();
                    $content = $ymlDumper->dump(['parameters' => $newParameters], 2);
                    run("cat << EOYAML > {{config_yml_path}}\n$content\nEOYAML");
                }
            }
        }
    }
})->desc('Initialize `parameters.yml`');

/**
 * Create database if not exists
 */
task('database:mysql:raw-create', function () {
    if (get('enable_mysql_database_create')) {
        try {
            $ymlParser = new Parser();
            $config = $ymlParser->parse(run("cat {{release_path}}/{{parameters_file}}")->toString());
            $parameters = $config['parameters'];

            run(sprintf('mysql -u %s -p%s -e "create database if not exists %s"',
                $parameters['database_user'],
                $parameters['database_password'],
                $parameters['database_name']));
        } catch (ParseException $e) {
            writeln(sprintf('Parse error [<info>%s</info>]: <comment>%s</comment>', get('parameters_file'), $e->getMessage()));
        }
    } else {
        writeln(sprintf(
            '<info>"Creating database in mysql"</info> is <comment>disabled</comment>. You can enable it with the <info>%s</info> parameter!',
            'enable_mysql_database_create'
        ));
    }
})->desc('Creating database in mysql');

before('deploy:vendors', 'database:mysql:raw-create');
before('database:mysql:raw-create', 'deploy:init-parameters-yml');

/**
 * Database migration rollback
 */
task('database:migrate:rollback', function () {
    if (get('use_database_migration_strategy')) {
        $releases = get('releases_list');

        if (isset($releases[1])) {
            $commandPattern = 'cd {{deploy_path}}/releases/%s && find {{doctrine_migration_path}} -maxdepth 1 -mindepth 1 -type f -name \'Version*.php\'';
            $currentMigrations = run(sprintf($commandPattern, $releases[0]))->toArray();
            $prevMigrations = run(sprintf($commandPattern, $releases[1]))->toArray();

            $downDiffMigrations = array_diff($currentMigrations, $prevMigrations);
            arsort($downDiffMigrations);

            switch (count($downDiffMigrations)) {
                case 0:
                    writeln('There isn\'t deprecated database migration.');
                    break;
                case 1:
                    writeln('There is <comment>1</comment> deprecated database migration!');
                    break;
                default:
                    writeln(
                        sprintf(
                            'There are <comment>%d</comment> deprecated database migrations!',
                            count($downDiffMigrations)
                        )
                    );
            }

            foreach ($downDiffMigrations as $migrationFile) {
                if (preg_match('|Version(\d+)\.php|', $migrationFile, $matches)) {
                    if (isVerbose()) {
                        writeln(sprintf('Start down the <comment>%s</comment> migration file.', $matches[0]));
                    }
                    run(
                        sprintf(
                            '{{env_vars}} {{bin/php}} %s/%s/console doctrine:migrations:execute %s --down {{console_options}}',
                            "{{deploy_path}}/releases/{$releases[0]}",
                            trim(get('bin_dir'), '/'),
                            $matches[1]
                        )
                    );
                } else {
                    throw new \Exception(sprintf('Invalid migration file name: `%s`', $migrationFile));
                }
            }

            if (count($downDiffMigrations) > 0) {
                writeln(sprintf('Undo <comment>%d</comment> migrations file: done', count($downDiffMigrations)));
            }

            run(
                sprintf(
                    '{{env_vars}} {{bin/php}} %s/%s/console doctrine:migrations:migrate {{console_options}}',
                    "{{deploy_path}}/releases/{$releases[1]}",
                    trim(get('bin_dir'), '/')
                )
            );
        }
    }
})->desc('Rollback the database (only if set `use_database_migration_strategy` true)');

// Run before rollback
before('rollback', 'database:migrate:rollback');

/**
 * Sometimes we need a force cache cleaner
 */
task('deploy:force-cache-clean', function() {
    run ('rm -rf {{release_path}}/app/cache/{{env}}');
})->setPrivate();

function extendArrayConfig($name, $newValues)
{
    $new = array_unique(array_merge(get($name, []), $newValues));
    set($name, $new);
}
