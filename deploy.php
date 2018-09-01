<?php

namespace Deployer;

require 'recipe/common.php';

// Project name
set('application', 'discord-webhook-lapi');

// Project repository
set('repository', 'git@github.com:LapiTV/discord-webhook.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

set('shared_files', ['.env', 'db.json']);

// Hosts
host('ns378030')
    ->set('deploy_path', '/home/projects/{{application}}');

// Tasks
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:clear_paths',
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy your project');
