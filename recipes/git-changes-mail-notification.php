<?php

namespace Deployer;

set('use_git_changes_mail_notification', true);
set('mail_from', false);   // REQUIRED! String
set('mail_to', false);     // REQUIRED! String or Array!
set('mail_subject', 'Deployment success');
set('git_log_with_merges', false);
set('git_log_pretty', 'oneline');
set('git_log_attachment_pretty', 'oneline');

task('deploy:git-changes-mail-notification', function () {
    // Will list only dirs in releases.
    $releases = run('cd {{deploy_path}}/releases && ls -t -d */')->toArray();

    // Prepare list.
    $releases = array_map(function ($release) {
        return basename(rtrim($release, '/'));
    }, $releases);

    if (count($releases) > 1) {
        if (checkSendEmailConfiguration()) {
            set('current_release_path', sprintf('{{deploy_path}}/releases/%s', $releases[0]));
            set('previous_release_path', sprintf('{{deploy_path}}/releases/%s', $releases[1]));
            $lastCommitHash = run('cd {{previous_release_path}} && git rev-parse --verify HEAD')->toString();
            // if the git clone ran with --depth=1 parameter, the log is contain only 1 commit.
            $lastCommitHashExistsOnTheServer = run(sprintf('cd {{current_release_path}} && if git cat-file -e %s; then echo "true"; fi', $lastCommitHash))->toBool();
            $currentCommitHash = run('cd {{current_release_path}} && git rev-parse --verify HEAD')->toString();

            $tos = get('mail_to');
            if (!is_array($tos)) {
                $tos = [$tos];
            }

            $commandForBody = sprintf(
                'git log %s..%s %s --pretty=%s',
                $lastCommitHash,
                $currentCommitHash,
                get('git_log_with_merges', false) ? '' : '--no-merges',
                get('git_log_pretty', 'oneline')
            );
            $commandForAttachment = sprintf(
                'git log %s..%s %s --pretty=%s',
                $lastCommitHash,
                $currentCommitHash,
                get('git_log_with_merges', false) ? '' : '--no-merges',
                get('git_log_attachment_pretty', 'oneline')
            );

            $logForBody = $lastCommitHashExistsOnTheServer
                ? run($commandForBody)->toString()
                : runLocally($commandForBody)->toString();
            $logForAttachment = $lastCommitHashExistsOnTheServer
                ? run($commandForAttachment)->toString()
                : runLocally($commandForAttachment)->toString();

            run(sprintf(
                'cd {{current_release_path}} && {{bin/php}} {{bin_dir}}/console webtown:deployment:send-mail {{console_options}} --from={{mail_from}} --subject="{{mail_subject}}" --base64-log="%s" --base64-log-attachment="%s" %s',
                base64_encode($logForBody),
                base64_encode($logForAttachment),
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
    } else {
        writeln('<comment>There is only <info>one</info> release! No changes.</comment>');
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
