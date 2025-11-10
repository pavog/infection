<?php

declare(strict_types=1);

function r(string $cmd): void
{
    echo "> $cmd\n";
    passthru($cmd, $exit);
    if ($exit) {
        exit($exit);
    }
}

$user = getenv('ACTOR');
$is_tag = str_starts_with(getenv('REF'), 'refs/tags/');
$ref = str_replace(['refs/heads/', 'refs/tags/'], '', getenv('REF'));

$ref = escapeshellarg($ref);

r("docker pull ghcr.io/$user/infection:$ref-arm64 --platform arm64");
r("docker pull ghcr.io/$user/infection:$ref-amd64 --platform amd64");

r("docker buildx imagetools create -t ghcr.io/$user/infection:$ref ghcr.io/$user/infection:$ref-arm64 ghcr.io/$user/infection:$ref-amd64");

if ($is_tag && !str_contains($ref, 'beta')) {
    r("docker buildx imagetools create -t ghcr.io/$user/infection:latest ghcr.io/$user/infection:$ref-arm64 ghcr.io/$user/infection:$ref-amd64");
}
