<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class DeploymentScriptTest extends TestCase
{
    private string $directory;
    private string $target;
    private string $release;
    private string $state;
    private array $environment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/rdsa-deploy-'.bin2hex(random_bytes(6));
        $this->target = $this->directory.'/site';
        $this->release = $this->directory.'/release';
        $this->state = $this->directory.'/state';
        foreach (['site/storage/framework', 'site/bootstrap/cache', 'site/public', 'site/vendor', 'release/public/build', 'release/vendor', 'release/deployment', 'bin'] as $path) mkdir($this->directory.'/'.$path, 0700, true);
        file_put_contents($this->target.'/artisan', 'old application');
        file_put_contents($this->target.'/.env', 'PRIVATE_PRODUCTION_KEY=unchanged');
        file_put_contents($this->target.'/storage/participants.txt', 'real reservations to preserve');
        file_put_contents($this->target.'/bootstrap/cache/preserved.txt', 'cache directory preserved');
        file_put_contents($this->target.'/bootstrap/cache/old-provider.php', '<?php /* obsolete provider cache */');
        file_put_contents($this->target.'/obsolete.php', 'old file');
        file_put_contents($this->release.'/artisan', 'new application');
        file_put_contents($this->release.'/new.php', 'new file');
        file_put_contents($this->release.'/.env', 'UNSAFE_VALUE_MUST_NOT_REPLACE_PRODUCTION');
        file_put_contents($this->release.'/public/build/manifest.json', '{}');
        file_put_contents($this->release.'/vendor/autoload.php', '<?php');
        file_put_contents($this->release.'/deployment/backup-database.php', '<?php');
        $php = <<<'BASH'
#!/usr/bin/env bash
set -eu
if [[ $1 == *backup-database.php ]]; then
    if [[ ${FAIL_AT:-} == backup ]]; then exit 1; fi
    printf 'private SQL backup' > "$3"
    exit 0
fi
if [[ $1 == -r ]]; then
    if [[ $3 == */manifest.json ]]; then printf 'assets/entry.css\n'; exit 0; fi
    grep -q '"status":"ok"' "$3"
    exit
fi
command=${2:-}
if [[ $command == config:clear && -f bootstrap/cache/old-provider.php ]] && grep -q 'new application' artisan; then exit 75; fi
if [[ ${FAIL_AT:-} == "$command" && ! -f storage/failure-seen ]]; then
    touch storage/failure-seen
    exit 1
fi
if [[ $command == down ]]; then touch storage/framework/down; fi
if [[ $command == up ]]; then rm -f storage/framework/down; fi
BASH;
        file_put_contents($this->directory.'/bin/php', $php);
        chmod($this->directory.'/bin/php', 0700);
        file_put_contents($this->directory.'/bin/curl', "#!/usr/bin/env bash\nif [[ \${FAIL_AT:-} == health ]]; then exit 22; fi\nif [[ \${FAIL_AT:-} == assets && \$* == *entry.css* ]]; then exit 22; fi\nprintf '{\"status\":\"ok\"}'\n");
        chmod($this->directory.'/bin/curl', 0700);
        $this->environment = ['PATH' => $this->directory.'/bin:'.getenv('PATH'), 'RDSA_DEPLOY_ROOT'=>$this->target,
            'RDSA_DEPLOY_STATE'=>$this->state, 'RDSA_DEPLOY_URL'=>'https://rdsa.invalid'];
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        rmdir($this->directory);
        parent::tearDown();
    }

    private function deploy(string $failure = '', int $run = 10): Process
    {
        $process = new Process(['bash', dirname(__DIR__, 2).'/deployment/deploy-release.sh', $this->release, str_repeat('a', 40), (string)$run], null, [...$this->environment, 'FAIL_AT'=>$failure]);
        $process->setTimeout(30); $process->run();
        return $process;
    }

    public function test_deployment_preserves_configuration_and_data_and_retains_private_backup(): void
    {
        $result = $this->deploy();
        $this->assertTrue($result->isSuccessful(), $result->getErrorOutput());
        $this->assertSame('new application', file_get_contents($this->target.'/artisan'));
        $this->assertSame('PRIVATE_PRODUCTION_KEY=unchanged', file_get_contents($this->target.'/.env'));
        $this->assertSame('real reservations to preserve', file_get_contents($this->target.'/storage/participants.txt'));
        $this->assertFileDoesNotExist($this->target.'/obsolete.php');
        $this->assertFileDoesNotExist($this->target.'/storage/framework/down');
        $this->assertFileExists($this->state.'/previous/database.sql');
        $this->assertSame('PRIVATE_PRODUCTION_KEY=unchanged', file_get_contents($this->state.'/previous/environment.env'));
        $this->assertSame(str_repeat('a', 40), json_decode(file_get_contents($this->state.'/last-success.json'), true)['sha']);
    }

    public function test_backup_failure_never_updates_code_and_reopens_existing_site(): void
    {
        $result = $this->deploy('backup');
        $this->assertFalse($result->isSuccessful());
        $this->assertSame('old application', file_get_contents($this->target.'/artisan'));
        $this->assertFileDoesNotExist($this->target.'/storage/framework/down');
        $this->assertFileDoesNotExist($this->state.'/last-success.json');
    }

    public function test_failure_before_migration_restores_old_code_and_keeps_live_data(): void
    {
        $result = $this->deploy('config:clear');
        $this->assertFalse($result->isSuccessful());
        $this->assertSame('old application', file_get_contents($this->target.'/artisan'));
        $this->assertFileDoesNotExist($this->target.'/new.php');
        $this->assertFileExists($this->target.'/obsolete.php');
        $this->assertFileDoesNotExist($this->target.'/storage/framework/down');
        $this->assertSame('real reservations to preserve', file_get_contents($this->target.'/storage/participants.txt'));
    }

    public function test_migration_failure_restores_code_but_keeps_maintenance_and_database_backup(): void
    {
        $result = $this->deploy('migrate');
        $this->assertFalse($result->isSuccessful());
        $this->assertSame('old application', file_get_contents($this->target.'/artisan'));
        $this->assertFileExists($this->target.'/storage/framework/down');
        $this->assertFileExists($this->state.'/failed/database.sql');
        $this->assertFileDoesNotExist($this->state.'/last-success.json');
    }

    public function test_failed_post_deployment_health_check_never_marks_release_successful(): void
    {
        $result = $this->deploy('health');
        $this->assertFalse($result->isSuccessful());
        $this->assertSame('old application', file_get_contents($this->target.'/artisan'));
        $this->assertFileExists($this->target.'/storage/framework/down');
        $this->assertFileDoesNotExist($this->state.'/last-success.json');
    }

    public function test_missing_public_asset_blocks_release_success(): void
    {
        $result = $this->deploy('assets');
        $this->assertFalse($result->isSuccessful());
        $this->assertFileExists($this->target.'/storage/framework/down');
        $this->assertFileDoesNotExist($this->state.'/last-success.json');
    }

    public function test_manual_maintenance_is_preserved_and_never_implicitly_reopened(): void
    {
        touch($this->target.'/storage/framework/down');
        $result = $this->deploy();
        $this->assertFalse($result->isSuccessful());
        $this->assertFileExists($this->target.'/storage/framework/down');
        $this->assertSame('old application', file_get_contents($this->target.'/artisan'));
    }

    public function test_older_workflow_cannot_overwrite_a_newer_deployment(): void
    {
        mkdir($this->state, 0700, true); file_put_contents($this->state.'/last-run', '20');
        $result = $this->deploy('', 19);
        $this->assertTrue($result->isSuccessful());
        $this->assertSame('old application', file_get_contents($this->target.'/artisan'));
        $this->assertFileDoesNotExist($this->target.'/storage/framework/down');
    }
}
