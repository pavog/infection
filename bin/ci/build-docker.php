<?php

declare(strict_types=1);

$platform = match (getenv('PLATFORM')) {
    'ubuntu-24.04-arm' => 'arm64',
    'ubuntu-latest' => 'amd64',
};

$commit = getenv('GITHUB_SHA');
$user = getenv('ACTOR');
$is_tag = str_starts_with(getenv('REF'), 'refs/tags/');
$ref = str_replace(['refs/heads/', 'refs/tags/'], '', getenv('REF'));

echo "Waiting for commit $commit on $ref...".PHP_EOL;

function r(string $cmd): void
{
    echo "> $cmd\n";
    passthru($cmd, $exit);
    if ($exit) {
        exit($exit);
    }
}

$composer_branch = $is_tag ? $ref : "$ref-dev";
if ($composer_branch === 'master-dev') {
    $composer_branch = 'dev-master';
}
$dev = $is_tag ? '' : '~dev';

if ($is_tag) {
    $cur = 0;
    while (true) {
        $json = json_decode(file_get_contents("https://repo.packagist.org/p2/infection/infection$dev.json?v=$cur"), true)["packages"]["infection/infection"];
        foreach ($json as $v) {
            if ($v['version'] === $composer_branch) {
                if ($v['source']['reference'] === $commit) {
                    break 2;
                }
                break;
            }
        }
        sleep(1);
        $cur++;
    }
}

$ref = escapeshellarg($ref);
$composer_branch = escapeshellarg($composer_branch);
$platform = escapeshellarg($platform);

r("docker buildx build --push . -t ghcr.io/$user/infection:$ref-$platform --build-arg INFECTION_REV=$composer_branch --build-arg PHP_VERSION=8.1 -f devTools/Dockerfile");
