# Pull request

<!--
Issue PR: from an issue branch into the version branch, titled "[vX.Y.Z] <summary> (#<issue>)", ending "Refs #<issue>".
Version PR: from the version branch into main, titled "vX.Y.Z: <milestone name>", listing every issue shipped, ending "Refs #<epic>".
composer milestone:next prints both titles. Plain words; link logs instead of pasting them.
-->

## What changed

## Why

## How it was checked

- `php artisan test`:
- Checked in the Laragon preview (<http://apes-intranet.test>, demo data):

## What you need to do

Nothing.

## Explained

## Checklist

- [ ] No secrets or real personal data
- [ ] Checks are green
- [ ] Version PR only: every issue in the milestone is closed (`composer milestone:status -- vX.Y.Z`), the release note is published, and the ship-gate answer is on the Epic

Refs #
