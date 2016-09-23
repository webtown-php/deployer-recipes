<?php
namespace Deployer;

set('shared_files', ['app/config/parameters.yml', 'app/config/nodejs_parameters.yml']);
set('shared_dirs', ['app/logs', 'web/uploads']);
set('writable_dirs', ['app/cache', 'app/logs', 'web/uploads']);
set('writable_use_sudo', false);

/**
 * Erőszakosan töröljük a cache-t. Vmiért erre szükség van. Ha nem volt használva, akkor néha az assetic:dump elhasalt
 * hibaüzenettel.
 */
task('deploy:force-cache-clean', function() {
    run ('rm -rf {{release_path}}/app/cache/prod');
})->setPrivate();
before('deploy:assetic:dump', 'deploy:force-cache-clean');
after('deploy:assetic:dump', 'deploy:force-cache-clean');

task('deploy:gulp-build', function () {
    run('cd {{release_path}} && bundle install --path vendor/bundle');
    run('cd {{release_path}} && npm install');
    run('cd {{release_path}} && bower install');
    run('cd {{release_path}} && gulp build');
    run('rm -rf /tmp/gulp-ruby-sass/');
})->desc('Install environment (Bundler, NPM, Bower)');
after('deploy:assetic:dump', 'deploy:gulp-build');

// @todo (Chris) kuma:search:populate +
