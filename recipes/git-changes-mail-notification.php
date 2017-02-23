<?php

namespace Deployer;

set('use_git_changes_mail_notification', true);
set('mail_from', false);   // REQUIRED! String
set('mail_to', false);     // REQUIRED! String or Array!
set('mail_subject', 'Deployment success');

task('deploy:git-changes-mail-notification', function () {
    $releases = get('releases_list');

    if (isset($releases[1])) {
        if (checkSendEmailConfiguration()) {
            $lastCommitHash = run("cd {{deploy_path}}/releases/{$releases[1]} && git rev-parse --verify HEAD")->toString();
            $tos = get('mail_to');

            if (!is_array($tos)) {
                $tos = [$tos];
            }

            run(sprintf(
                'cd {{deploy_path}}/releases/%s && {{bin/php}} {{release_path}}/{{bin_dir}}/console webtown:deployment:send-mail --env={{env}} --with-merges --from={{mail_from}} --subject="{{mail_subject}}" --branch="HEAD" --last="%s" %s',
                $releases[1],
                $lastCommitHash,
                '--to="' . implode('" --to="', $tos) . '"'
            ));
        } elseif (get('use_git_changes_mail_notification') === false) {
            writeln('The e-mail notification from GIT changes is <comment>disabled</comment>');
        } else {
            writeln(sprintf(
                'The e-mail sending is disabled! Some configurations are missing or invalid! You have to set the <info>%s</info> and the <info>%s</info> parameter!',
                'mail_from',
                'mail_to'
            ));
        }
    }
})->desc('Sends email(s) after deployment');
after('deploy', 'deploy:git-changes-mail-notification');

function checkSendEmailConfiguration()
{
    $from = get('mail_from', false);
    $tos = get('mail_to', false);

    return get('use_git_changes_mail_notification')
        && $from
        && !filter_var($from, FILTER_VALIDATE_EMAIL) === false
        && $tos
    ;
}
