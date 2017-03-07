<?php

namespace Deployer;

use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/*
 * The inserted new tasks:
 *
 * task('deploy', [
 *     'deploy:prepare',                   'deploy:prepare',
 *     'deploy:lock',                      'deploy:lock',
 *     'deploy:release',                   'deploy:release',
 *     'deploy:update_code',               'deploy:update_code',
 *     'deploy:clear_paths',               'deploy:clear_paths',
 *     'deploy:create_cache_dir',          'deploy:create_cache_dir',
 *     'deploy:shared',                    'deploy:shared',
 *     'deploy:assets',                    'deploy:assets',
 *
 *         'deploy:init-parameters-yml',
 *             'database:mysql:backup',    // Only enable load fixture
 *             'database:mysql:raw-drop',  // Only enable load fixture
 *         'database:mysql:raw-create',
 *
 *     'deploy:vendors',                   'deploy:vendors',
 *     'deploy:assets:install',            'deploy:assets:install',
 *     'deploy:assetic:dump',              'deploy:assetic:dump',
 *     'deploy:cache:warmup',              'deploy:cache:warmup',
 *
 *         'database:migrate',
 *             'database:load-fixtures',   // Only enable load fixture
 *
 *     'deploy:writable',                  'deploy:writable',
 *     'deploy:symlink',                   'deploy:symlink',
 *     'deploy:unlock',                    'deploy:unlock',
 *     'cleanup',                          'cleanup',
 * ]);
 */

// Ask questions?
set('interaction', true);
set('doctrine_migration_path', 'app/DoctrineMigrations');
set('use_database_migration_strategy', false);
set('enable_mysql_database_create', false);
set('enable_mysql_database_drop', false);
set('parameters_file', 'app/config/parameters.yml');

// Database
set('sql_backup_file', function() {
    return sprintf('{{deploy_path}}/current/backup_%s.sql', date('YdmHis'));
});

// Only use in maintenance.php recipe! Override the original
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

task('database:mysql:backup', function() {
    if (fileExists('{{release_path}}/{{parameters_file}}')
        && fileExists('`dirname {{ sql_backup_file}}`', FILE_CHECK_IS_DIR)
    ) {
        $parameters = getParameters();
        $backupFilePath = get('sql_backup_file');

        run(sprintf(
            'mysqldump --default-character-set=utf8 --opt' .
            ' --host=%s' .
            ' --user=%s' .
            ' --password="%s"' .
            ' -B %s > %s',
            $parameters['database_host'],
            $parameters['database_user'],
            $parameters['database_password'],
            $parameters['database_name'],
            $backupFilePath
        ));
        writeln(sprintf('MySQL backup file: <info>%s</info>', $backupFilePath));
    } else {
        if (!fileExists('{{release_path}}/{{parameters_file}}')) {
            writeln('No database backup: The <info>parameters.yml</info> doesn\'t exist! (<info>{{release_path}}/{{parameters_file}}</info>)');
        } else {
            writeln('No database backup: The <info>directory of {{sql_backup_file}}</info> doesn\'t exist yet.');
        }
    }
})->desc('MySQL database backup.');

/**
 * Drop database if exists
 */
task('database:mysql:raw-drop', function () {
    if (get('enable_mysql_database_create') && get('enable_mysql_database_drop')) {
        $parameters = getParameters();

        run(sprintf(
            'mysql' .
            ' --host=%s' .
            ' --user=%s' .
            ' --password="%s"'.
            ' -e "DROP DATABASE IF EXISTS \`%s\`"',
            $parameters['database_host'],
            $parameters['database_user'],
            $parameters['database_password'],
            $parameters['database_name']
        ));
    } else {
        writeln(sprintf(
            '<info>"Drop database in mysql"</info> is <comment>disabled</comment>. You can enable it with the <info>%s</info> parameter!',
            'enable_mysql_database_create'
        ));
    }
})->desc('Drop database in mysql');

/**
 * Create database if not exists
 */
task('database:mysql:raw-create', function () {
    if (get('enable_mysql_database_create')) {
        $parameters = getParameters();

        run(sprintf(
            'mysql' .
            ' --host=%s' .
            ' --user=%s' .
            ' --password="%s"'.
            ' -e "CREATE DATABASE IF NOT EXISTS \`%s\`"',
            $parameters['database_host'],
            $parameters['database_user'],
            $parameters['database_password'],
            $parameters['database_name']
        ));
    } else {
        writeln(sprintf(
            '<info>"Creating database in mysql"</info> is <comment>disabled</comment>. You can enable it with the <info>%s</info> parameter!',
            'enable_mysql_database_create'
        ));
    }
})->desc('Creating database in mysql');

// If you want to use drop, register it!
before('database:mysql:raw-drop', 'database:mysql:backup');

// Build database if not exists
before('deploy:vendors', 'database:mysql:raw-create');
before('database:mysql:backup', 'deploy:init-parameters-yml');

// Migration
before('deploy:writable', 'database:migrate');

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

task('upload:app_dev', function() {
    run('cd {{deploy_path}}/current && git archive --remote={{repository}} HEAD web/app_dev.php | tar -x');
    writeln('Now you can use the <info>app_dev.php</info>!');
    writeln('<comment>After debug run the <info>deploy:clear_paths</info> task!</comment>');
})->desc('Upload the deleted app_dev.php. For fast debugging.');

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

function getParameters()
{
    if (!get('symfony_parameters', false)) {
        try {
            $ymlParser = new Parser();
            $config = $ymlParser->parse(run("cat {{release_path}}/{{parameters_file}}")->toString());
            set('symfony_parameters', $config['parameters']);
        } catch (ParseException $e) {
            writeln(sprintf('Parse error [<info>%s</info>]: <comment>%s</comment>', get('parameters_file'), $e->getMessage()));
        }
    }

    return get('symfony_parameters');
}

/**
 * Override the original
 */
set('composer_options', function () {
    $base = '{{composer_action}} --verbose --prefer-dist --no-progress --no-interaction --optimize-autoloader';
    return get('env') == 'prod' ? $base . ' --no-dev' : $base;
});
