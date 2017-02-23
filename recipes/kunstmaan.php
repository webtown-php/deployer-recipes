<?php
namespace Deployer;

require_once 'symfony.php';

set('shared_files', ['app/config/parameters.yml', 'app/config/nodejs_parameters.yml']);
extendArrayConfig('shared_dirs', ['web/uploads']);
extendArrayConfig('writable_dirs', ['web/uploads']);
set('writable_use_sudo', false);

// Commented example
//before('deploy:assetic:dump', 'deploy:force-cache-clean');

task('deploy:gulp-build', function () {
    run('cd {{release_path}} && bundle install --path vendor/bundle');
    run('cd {{release_path}} && npm install');
    run('cd {{release_path}} && bower install');
    run('cd {{release_path}} && gulp build');
    // clean after gulp
    run('rm -rf /tmp/gulp-ruby-sass/');
})->desc('Install environment (Bundler, NPM, Bower)');
after('deploy:assetic:dump', 'deploy:gulp-build');

// @todo (Chris) kuma:search:populate +
