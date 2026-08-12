<?php

namespace App\Console\Commands\Ci;

use App\Support\Ci\CiPayload;
use App\Support\Ci\ConventionalCommit;
use App\Support\Ci\PullRequestPolicy;
use App\Support\Ci\Review;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Checks an open pull request against the merge policy.
 *
 * The workflow hands this command the event payload and the review list it read
 * from the API; the command itself performs no network access and executes no
 * contributed code, which is what lets it run against untrusted forks.
 */
#[Signature('ci:pull-request-policy {--event= : Path to the GitHub event payload JSON} {--reviews= : Path to the pull request reviews JSON}')]
#[Description('Validate a pull request title and its required approvals.')]
final class CheckPullRequestPolicy extends Command
{
    public function handle(): int
    {
        $event = CiPayload::readFile((string) $this->option('event'));
        $pullRequest = CiPayload::map($event, 'pull_request');

        $reviews = [];
        $reviewsPath = (string) $this->option('reviews');

        if ($reviewsPath !== '') {
            $reviews = Review::fromArrays(array_values(array_filter(
                CiPayload::readFile($reviewsPath),
                is_array(...),
            )));
        }

        $title = CiPayload::string($pullRequest, 'title');
        $headSha = CiPayload::string(CiPayload::map($pullRequest, 'head'), 'sha');
        $fork = PullRequestPolicy::isFromFork($pullRequest);

        $violations = PullRequestPolicy::violations($title, $fork, $reviews, $headSha);

        foreach ($violations as $violation) {
            $this->line("::error::{$violation}");
        }

        if ($violations !== []) {
            $this->line(ConventionalCommit::USAGE);

            return self::FAILURE;
        }

        $origin = $fork ? 'a fork' : 'this repository';
        $this->line("The pull request satisfies the merge policy for a branch in {$origin}.");

        return self::SUCCESS;
    }
}
