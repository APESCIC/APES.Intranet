<?php

namespace App\Core\Changelog;

use App\Core\Version\AppVersion;
use DateTimeImmutable;
use JsonException;

/**
 * Checks changelog/releases/vX.Y.Z.json files before they reach the database.
 * Runs in CI (changelog:validate) and again in every deploy sync.
 */
class ChangelogEntryValidator
{
    public const REQUIRED = ['version', 'released_at', 'title', 'summary', 'audiences', 'changes', 'published'];

    public const OPTIONAL = ['internal_notes'];

    /**
     * Text that must never appear in a release note: secrets and keys, and
     * personal or infrastructure details (docs/release-notes.md).
     *
     * @var array<string, string>
     */
    public const SENSITIVE_PATTERNS = [
        'a private key' => '/-----BEGIN [A-Z ]*PRIVATE KEY-----/',
        'a password, secret or token value' => '/\b(password|passwd|secret|token|api[_-]?key|client[_-]?secret)\b\s*[:=]\s*\S+/i',
        'an env-style setting' => '/\b[A-Z][A-Z0-9]*_[A-Z0-9_]+=\S+/',
        'an encoded key' => '/\bbase64:[A-Za-z0-9+\/=]{16,}/',
        'a GitHub, Stripe or AWS key' => '/\b(gh[pousr]_[A-Za-z0-9]{20,}|sk_(live|test)_[A-Za-z0-9]{10,}|AKIA[0-9A-Z]{16})\b/',
        'a long hex string' => '/\b[0-9a-f]{32,}\b/i',
        'an email address' => '/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/',
        'a phone number' => '/(\+44\s?|\b0)\d{2,4}[\s-]?\d{3,4}[\s-]?\d{3,4}\b/',
        'an IP address' => '/\b\d{1,3}(\.\d{1,3}){3}\b/',
        'a hostname' => '/\b[a-z0-9-]+(\.[a-z0-9-]+)*\.(test|local|internal|lan|uk|com|org|net|io|dev|app)\b/i',
        'an SQL statement' => '/\b(SELECT|INSERT|UPDATE|DELETE|DROP|ALTER)\b.*\b(FROM|INTO|SET|WHERE|TABLE)\b/',
    ];

    /**
     * @return list<string> Problems with one file, empty when it is valid.
     */
    public function validateFile(string $path): array
    {
        $name = basename($path);
        $raw = is_file($path) ? file_get_contents($path) : false;
        if ($raw === false) {
            return ["{$name}: cannot be read"];
        }

        try {
            $payload = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            return ["{$name}: invalid JSON ({$exception->getMessage()})"];
        }

        if (! is_array($payload) || array_is_list($payload)) {
            return ["{$name}: must be a JSON object"];
        }

        return array_map(fn (string $error) => "{$name}: {$error}", $this->validatePayload($payload, $name));
    }

    /**
     * @param  array<mixed>  $payload
     * @return list<string>
     */
    public function validatePayload(array $payload, ?string $fileName = null): array
    {
        $errors = [];

        foreach (self::REQUIRED as $key) {
            if (! array_key_exists($key, $payload)) {
                $errors[] = "missing \"{$key}\"";
            }
        }
        foreach (array_keys($payload) as $key) {
            if (! in_array($key, [...self::REQUIRED, ...self::OPTIONAL], true)) {
                $errors[] = "unknown field \"{$key}\"";
            }
        }
        if ($errors !== []) {
            return $errors;
        }

        $version = $payload['version'];
        if (! is_string($version) || ! AppVersion::isValid($version)) {
            $errors[] = 'version must look like v1.2.3';
        } elseif ($fileName !== null && $fileName !== "{$version}.json") {
            $errors[] = "file name must be {$version}.json";
        }

        $date = is_string($payload['released_at']) ? DateTimeImmutable::createFromFormat('!Y-m-d', $payload['released_at']) : false;
        if ($date === false || $date->format('Y-m-d') !== $payload['released_at']) {
            $errors[] = 'released_at must be a real date, YYYY-MM-DD';
        }

        $errors = [...$errors, ...$this->text($payload['title'], 'title', 120)];
        $errors = [...$errors, ...$this->text($payload['summary'], 'summary', 500)];

        $audiences = $payload['audiences'];
        if (! is_array($audiences) || ! array_is_list($audiences) || $audiences === []
            || array_diff($audiences, Release::AUDIENCES) !== [] || count(array_unique($audiences)) !== count($audiences)) {
            $errors[] = 'audiences must list one or more of '.implode(', ', Release::AUDIENCES).', each once';
        }

        $changes = $payload['changes'];
        if (! is_array($changes) || $changes === [] || array_is_list($changes)) {
            $errors[] = 'changes must be an object keyed by change type';
        } else {
            foreach ($changes as $type => $items) {
                if (! in_array($type, Release::CHANGE_TYPES, true)) {
                    $errors[] = "unknown change type \"{$type}\"; use ".implode(', ', Release::CHANGE_TYPES);

                    continue;
                }
                $errors = [...$errors, ...$this->textList($items, "changes.{$type}")];
            }
        }

        if (array_key_exists('internal_notes', $payload)) {
            $errors = [...$errors, ...$this->textList($payload['internal_notes'], 'internal_notes')];
        }

        if (! is_bool($payload['published'])) {
            $errors[] = 'published must be true or false';
        }

        foreach ($this->strings($payload) as $string) {
            foreach (self::SENSITIVE_PATTERNS as $what => $pattern) {
                if (preg_match($pattern, $string) === 1) {
                    $errors[] = "looks like it contains {$what}; release notes must not";
                }
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * Validates every entry in a folder, including the rules that span
     * files: unique versions, and (optionally) a published entry for a tag.
     *
     * @return list<string>
     */
    public function validateDirectory(string $directory, ?string $requirePublished = null): array
    {
        $errors = [];
        $versions = [];

        foreach ($this->entryPaths($directory) as $path) {
            $fileErrors = $this->validateFile($path);
            $errors = [...$errors, ...$fileErrors];
            if ($fileErrors !== []) {
                continue;
            }

            $payload = $this->decode($path);
            $version = strtolower($payload['version']);
            if (isset($versions[$version])) {
                $errors[] = basename($path).": version {$payload['version']} is already used by {$versions[$version]['file']}";
            }
            $versions[$version] = ['file' => basename($path), 'published' => $payload['published']];
        }

        if ($requirePublished !== null) {
            $entry = $versions[strtolower($requirePublished)] ?? null;
            if ($entry === null) {
                $errors[] = "{$requirePublished} has no release note: add changelog/releases/{$requirePublished}.json";
            } elseif ($entry['published'] !== true) {
                $errors[] = "{$requirePublished}.json is not published: set \"published\": true before tagging";
            }
        }

        return $errors;
    }

    /**
     * @return list<string> Entry files, oldest version first. Files starting with "_" are templates.
     */
    public function entryPaths(string $directory): array
    {
        $paths = array_values(array_filter(
            glob(rtrim($directory, '/\\').'/*.json') ?: [],
            fn (string $path) => ! str_starts_with(basename($path), '_'),
        ));
        usort($paths, fn (string $a, string $b) => version_compare(ltrim(basename($a, '.json'), 'v'), ltrim(basename($b, '.json'), 'v')));

        return $paths;
    }

    /**
     * @return array<string, mixed>
     */
    public function decode(string $path): array
    {
        return json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<string>
     */
    private function text(mixed $value, string $field, int $max): array
    {
        if (! is_string($value) || trim($value) === '') {
            return ["{$field} must be non-empty text"];
        }
        if (mb_strlen($value) > $max) {
            return ["{$field} must be {$max} characters or fewer"];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function textList(mixed $value, string $field): array
    {
        if (! is_array($value) || ! array_is_list($value) || $value === []) {
            return ["{$field} must be a non-empty list of text"];
        }
        foreach ($value as $item) {
            $errors = $this->text($item, $field, 500);
            if ($errors !== []) {
                return ["{$field} must be a non-empty list of text, each 500 characters or fewer"];
            }
        }

        return [];
    }

    /**
     * @param  array<mixed>  $value
     * @return list<string>
     */
    private function strings(array $value): array
    {
        $strings = [];
        array_walk_recursive($value, function (mixed $item, mixed $key) use (&$strings) {
            if (is_string($item)) {
                $strings[] = $item;
            }
            if (is_string($key)) {
                $strings[] = $key;
            }
        });

        return $strings;
    }
}
