<?php

namespace Tests\Unit\MilestoneVersion;

use PHPUnit\Framework\TestCase;
use Scripts\MilestoneVersion\ReleaseModuleVersions;
use Scripts\MilestoneVersion\SemVer;

class ReleaseModuleVersionsTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/release-module-versions-'.bin2hex(random_bytes(4));
        mkdir($this->root.'/modules/learning', recursive: true);
        file_put_contents($this->root.'/modules/learning/module.php', <<<'PHP'
<?php

return [
    'slug' => 'learning',
    'version' => '0.2.1',
];
PHP);
    }

    protected function tearDown(): void
    {
        unlink($this->root.'/modules/learning/module.php');
        rmdir($this->root.'/modules/learning');
        rmdir($this->root.'/modules');
        rmdir($this->root);
    }

    public function test_collect_lists_core_then_modules_in_slug_order(): void
    {
        $versions = ReleaseModuleVersions::collect($this->root, SemVer::parse('v1.4.0'));

        $this->assertSame([
            'core' => 'v1.4.0',
            'learning' => '0.2.1',
        ], $versions);
    }
}
