<?php

namespace App\Core\Changelog\Console;

use App\Core\Changelog\ChangelogEntryValidator;
use App\Core\Changelog\ReleaseChangelogSync;
use App\Core\Version\AppVersion;
use Illuminate\Console\Command;

class ValidateChangelog extends Command
{
    protected $signature = 'changelog:validate
        {--require-published= : Also fail unless this version (such as v0.3.11) has a published entry}';

    protected $description = 'Check the release notes in changelog/releases';

    public function handle(ChangelogEntryValidator $validator, ReleaseChangelogSync $sync): int
    {
        $required = $this->option('require-published');
        $required = is_string($required) && $required !== '' ? $required : null;
        if ($required !== null && ! AppVersion::isValid($required)) {
            $this->error("--require-published must look like v1.2.3, not '{$required}'.");

            return self::FAILURE;
        }

        $directory = $sync->directory();
        $errors = $validator->validateDirectory($directory, $required);

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $count = count($validator->entryPaths($directory));
        $this->info("{$count} release notes are valid.".($required ? " {$required} is published." : ''));

        return self::SUCCESS;
    }
}
