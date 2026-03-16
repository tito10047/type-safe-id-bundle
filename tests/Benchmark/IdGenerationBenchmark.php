<?php

namespace Tito10047\TypeSafeIdBundle\Tests\Benchmark;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\TypeSafeIdBundle\Tests\Benchmark\Kernel\ClassicKernel;
use Tito10047\TypeSafeIdBundle\Tests\Benchmark\Kernel\TypeSafeKernel;

class IdGenerationBenchmark extends TestCase
{
    private Filesystem $fs;
    private string $projectDir;
    private array $results = [];
    private ?\Composer\Autoload\ClassLoader $loader = null;

    public function setUp(): void
    {
        $this->projectDir = realpath(sys_get_temp_dir()) . '/benchmark_' . uniqid();
        $this->fs = new Filesystem();
        $this->fs->mkdir($this->projectDir . '/src/Entity');
        $this->fs->mkdir($this->projectDir . '/src/EntityId');
        $this->fs->mkdir($this->projectDir . '/var');

        file_put_contents($this->projectDir . '/composer.json', json_encode([
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'src/',
                ],
            ],
        ]));

        // Create a real ClassLoader and register it
        $this->loader = new \Composer\Autoload\ClassLoader();
        $this->loader->addPsr4('App\\', $this->projectDir . '/src/');
        $this->loader->register(true);
    }

    public function tearDown(): void
    {
        if ($this->loader) {
            $this->loader->unregister();
        }

        if (file_exists($this->projectDir)) {
            $this->fs->remove($this->projectDir);
        }
    }

    public function testBenchmarkClassicVsTypeSafeId(): void
    {
        $iterations = 10;

        echo "\n\n=== TypeSafeId Bundle Performance Benchmark ===\n";
        echo "Iterations: $iterations\n";
        echo "Entities per test: 1000\n";
        echo "Cache: Cleared after each iteration\n\n";

        // === CLASSIC BENCHMARK ===
        echo "=== Classic Doctrine Benchmark ===\n";

        $this->createClassicEntities();

        for ($i = 1; $i <= $iterations; $i++) {
            echo "  Classic iteration $i/$iterations... ";

            $kernelData = $this->setupClassicKernel();
            $this->results['classic'][$i] = $this->benchmarkPersist($kernelData['em'], 'classic');

            // Add compilation metrics
            $this->results['classic'][$i]['container_compilation'] = $kernelData['compilation_time'];
            $this->results['classic'][$i]['container_memory'] = $kernelData['compilation_memory'];

            $kernelData['kernel']->shutdown();

            // Clear cache
            $cacheDir = $this->projectDir . '/var/cache';
            if (is_dir($cacheDir)) {
                $this->fs->remove($cacheDir);
            }

            gc_collect_cycles();
            echo "done\n";
        }

        // Clean up
        $this->tearDown();
        $this->setUp();

        // === TYPESAFE BENCHMARK ===
        echo "\n=== TypeSafeId Benchmark ===\n";

        $this->createTypeSafeEntities();

        for ($i = 1; $i <= $iterations; $i++) {
            echo "  TypeSafe iteration $i/$iterations... ";

            $kernelData = $this->setupTypeSafeKernel();
            $this->results['typesafe'][$i] = $this->benchmarkPersist($kernelData['em'], 'typesafe');

            // Add compilation metrics
            $this->results['typesafe'][$i]['container_compilation'] = $kernelData['compilation_time'];
            $this->results['typesafe'][$i]['container_memory'] = $kernelData['compilation_memory'];

            $kernelData['kernel']->shutdown();

            // Clear cache
            $cacheDir = $this->projectDir . '/var/cache';
            if (is_dir($cacheDir)) {
                $this->fs->remove($cacheDir);
            }

            gc_collect_cycles();
            echo "done\n";
        }

        // Calculate averages (now includes compilation metrics)
        $classicAvg = $this->calculateAverages('classic');
        $typeSafeAvg = $this->calculateAverages('typesafe');

        // Generate markdown report
        $this->generateMarkdownReport($classicAvg, $typeSafeAvg);

        // Output summary
        echo "\n=== Summary ===\n";
        echo sprintf("Classic approach - Container compilation: %.2fms\n", $classicAvg['container_compilation']);
        echo sprintf("TypeSafe approach - Container compilation: %.2fms\n", $typeSafeAvg['container_compilation']);
        echo sprintf("Classic approach - 1000 UUID persists: %.2fms (avg)\n", $classicAvg['uuid_persist']);
        echo sprintf("TypeSafe approach - 1000 UUID persists: %.2fms (avg)\n", $typeSafeAvg['uuid_persist']);
        echo sprintf("Classic approach - 1000 ULID persists: %.2fms (avg)\n", $classicAvg['ulid_persist']);
        echo sprintf("TypeSafe approach - 1000 ULID persists: %.2fms (avg)\n", $typeSafeAvg['ulid_persist']);

        $this->assertTrue(true, "Benchmark completed");
    }

    private function setupClassicKernel(): array
    {
        // Measure container compilation time
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $kernel = new ClassicKernel('prod', false, $this->projectDir);
        $kernel->boot();

        $compilationTime = (microtime(true) - $startTime) * 1000;
        $compilationMemory = (memory_get_usage() - $startMemory) / 1024 / 1024;

        // Create schema
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'doctrine:schema:update',
            '--force' => true,
        ]);
        $output = new BufferedOutput();
        $application->run($input, $output);

        $em = $kernel->getContainer()->get('doctrine')->getManager();

        return [
            'kernel' => $kernel,
            'em' => $em,
            'compilation_time' => $compilationTime,
            'compilation_memory' => $compilationMemory,
        ];
    }

    private function setupTypeSafeKernel(): array
    {
        // Measure container compilation time
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $kernel = new TypeSafeKernel('prod', false, $this->projectDir);
        $kernel->boot();

        $compilationTime = (microtime(true) - $startTime) * 1000;
        $compilationMemory = (memory_get_usage() - $startMemory) / 1024 / 1024;

        // Create schema
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'doctrine:schema:update',
            '--force' => true,
        ]);
        $output = new BufferedOutput();
        $application->run($input, $output);

        $em = $kernel->getContainer()->get('doctrine')->getManager();

        return [
            'kernel' => $kernel,
            'em' => $em,
            'compilation_time' => $compilationTime,
            'compilation_memory' => $compilationMemory,
        ];
    }

    private function benchmarkPersist($em, string $type): array
    {
        $results = [];

        // Benchmark UUID persist
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            if ($type === 'classic') {
                $entity = new \App\Entity\ClassicUuidEntity();
            } else {
                $entity = new \App\Entity\TypeSafeUuidEntity();
            }
            $em->persist($entity);
        }
        $em->flush();
        $results['uuid_persist'] = (microtime(true) - $startTime) * 1000;

        $em->clear();

        // Benchmark ULID persist
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            if ($type === 'classic') {
                $entity = new \App\Entity\ClassicUlidEntity();
            } else {
                $entity = new \App\Entity\TypeSafeUlidEntity();
            }
            $em->persist($entity);
        }
        $em->flush();
        $results['ulid_persist'] = (microtime(true) - $startTime) * 1000;

        $em->clear();

        return $results;
    }

    private function createClassicEntities(): void
    {
        // Create ClassicUuidEntity
        $classicUuidContent = <<<'PHP'
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class ClassicUuidEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
PHP;
        file_put_contents($this->projectDir . '/src/Entity/ClassicUuidEntity.php', $classicUuidContent);

        // Create ClassicUlidEntity
        $classicUlidContent = <<<'PHP'
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
class ClassicUlidEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid')]
    private Ulid $id;

    public function __construct()
    {
        $this->id = new Ulid();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }
}
PHP;
        file_put_contents($this->projectDir . '/src/Entity/ClassicUlidEntity.php', $classicUlidContent);

        // Classes will be autoloaded when needed
    }

    private function createTypeSafeEntities(): void
    {
        // Create TypeSafeUuidEntity ID
        $uuidIdContent = <<<'PHP'
<?php

namespace App\EntityId;

use Symfony\Component\Uid\UuidV7;
use Tito10047\TypeSafeIdBundle\GeneratableId;

final class TypeSafeUuidEntityId extends UuidV7 implements GeneratableId
{
    public static function new(): self
    {
        return new self();
    }
}
PHP;
        file_put_contents($this->projectDir . '/src/EntityId/TypeSafeUuidEntityId.php', $uuidIdContent);

        // Create TypeSafeUuidEntity ID Type
        $uuidIdTypeContent = <<<'PHP'
<?php

namespace App\EntityId;

use Symfony\Bridge\Doctrine\Types\AbstractUidType;

final class TypeSafeUuidEntityIdType extends AbstractUidType
{
    public function getName(): string
    {
        return self::class;
    }

    protected function getUidClass(): string
    {
        return TypeSafeUuidEntityId::class;
    }
}
PHP;
        file_put_contents($this->projectDir . '/src/EntityId/TypeSafeUuidEntityIdType.php', $uuidIdTypeContent);

        // Create TypeSafeUuidEntity
        $uuidEntityContent = <<<'PHP'
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\EntityId\TypeSafeUuidEntityId;
use App\EntityId\TypeSafeUuidEntityIdType;

#[ORM\Entity]
class TypeSafeUuidEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.id_generator.universal')]
    #[ORM\Column(type: TypeSafeUuidEntityIdType::class)]
    private ?TypeSafeUuidEntityId $id = null;

    public function getId(): ?TypeSafeUuidEntityId
    {
        return $this->id;
    }
}
PHP;
        file_put_contents($this->projectDir . '/src/Entity/TypeSafeUuidEntity.php', $uuidEntityContent);

        // Create TypeSafeUlidEntity ID
        $ulidIdContent = <<<'PHP'
<?php

namespace App\EntityId;

use Symfony\Component\Uid\Ulid;
use Tito10047\TypeSafeIdBundle\GeneratableId;

final class TypeSafeUlidEntityId extends Ulid implements GeneratableId
{
    public static function new(): self
    {
        return new self();
    }
}
PHP;
        file_put_contents($this->projectDir . '/src/EntityId/TypeSafeUlidEntityId.php', $ulidIdContent);

        // Create TypeSafeUlidEntity ID Type
        $ulidIdTypeContent = <<<'PHP'
<?php

namespace App\EntityId;

use Symfony\Bridge\Doctrine\Types\AbstractUidType;

final class TypeSafeUlidEntityIdType extends AbstractUidType
{
    public function getName(): string
    {
        return self::class;
    }

    protected function getUidClass(): string
    {
        return TypeSafeUlidEntityId::class;
    }
}
PHP;
        file_put_contents($this->projectDir . '/src/EntityId/TypeSafeUlidEntityIdType.php', $ulidIdTypeContent);

        // Create TypeSafeUlidEntity
        $ulidEntityContent = <<<'PHP'
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\EntityId\TypeSafeUlidEntityId;
use App\EntityId\TypeSafeUlidEntityIdType;

#[ORM\Entity]
class TypeSafeUlidEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.id_generator.universal')]
    #[ORM\Column(type: TypeSafeUlidEntityIdType::class)]
    private ?TypeSafeUlidEntityId $id = null;

    public function getId(): ?TypeSafeUlidEntityId
    {
        return $this->id;
    }
}
PHP;
        file_put_contents($this->projectDir . '/src/Entity/TypeSafeUlidEntity.php', $ulidEntityContent);

        // Classes will be autoloaded when needed
    }

    private function calculateAverages(string $type): array
    {
        $metrics = ['uuid_persist', 'ulid_persist', 'container_compilation', 'container_memory'];
        $averages = [];

        foreach ($metrics as $metric) {
            $values = array_column($this->results[$type], $metric);
            $values = array_filter($values); // Remove empty values
            if (count($values) > 0) {
                $averages[$metric] = array_sum($values) / count($values);
                $averages[$metric . '_std'] = $this->calculateStdDev($values);
            } else {
                $averages[$metric] = 0;
                $averages[$metric . '_std'] = 0;
            }
        }

        return $averages;
    }

    private function calculateStdDev(array $values): float
    {
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / count($values);
        return sqrt($variance);
    }

    private function generateMarkdownReport(array $classicAvg, array $typeSafeAvg): void
    {
        $reportPath = dirname(__DIR__, 2) . '/benchmark.md';

        $iterationCount = count($this->results['classic']);

        $content = "# TypeSafeId Bundle - Performance Benchmark\n\n";
        $content .= "**Date:** " . date('Y-m-d H:i:s') . "\n";
        $content .= "**PHP Version:** " . PHP_VERSION . "\n";
        $content .= "**Environment:** " . PHP_OS . "\n\n";

        $content .= "## Test Setup\n\n";
        $content .= "- **Iterations:** $iterationCount\n";
        $content .= "- **Entities per test:** 1000\n";
        $content .= "- **Database:** SQLite (in-memory)\n\n";

        $content .= "## Results\n\n";
        $content .= "### Container Compilation Time\n\n";
        $content .= "| Approach | Time (ms) | Std Dev | Memory (MB) |\n";
        $content .= "|----------|-----------|---------|-------------|\n";
        $content .= sprintf("| Classic (Doctrine) | %.2f | ±%.2f | %.2f |\n",
            $classicAvg['container_compilation'],
            $classicAvg['container_compilation_std'],
            $classicAvg['container_memory']
        );
        $content .= sprintf("| TypeSafeId Bundle | %.2f | ±%.2f | %.2f |\n",
            $typeSafeAvg['container_compilation'],
            $typeSafeAvg['container_compilation_std'],
            $typeSafeAvg['container_memory']
        );

        // Safely calculate differences, avoiding division by zero
        $compDiff = 0;
        if ($classicAvg['container_compilation'] > 0) {
            $compDiff = (($typeSafeAvg['container_compilation'] / $classicAvg['container_compilation']) - 1) * 100;
        }

        $memDiff = 0;
        if ($classicAvg['container_memory'] > 0) {
            $memDiff = (($typeSafeAvg['container_memory'] / $classicAvg['container_memory']) - 1) * 100;
        }

        $content .= sprintf("\n**Difference:** TypeSafeId is %.1f%% %s (compilation), %.1f%% %s (memory)\n\n",
            abs($compDiff),
            $compDiff > 0 ? 'slower' : 'faster',
            abs($memDiff),
            $memDiff > 0 ? 'more memory' : 'less memory'
        );

        $content .= "### UUID Entity Persistence (1000 entities)\n\n";
        $content .= "| Approach | Time (ms) | Std Dev | Entities/sec |\n";
        $content .= "|----------|-----------|---------|-------------|\n";
        $content .= sprintf("| Classic (Doctrine) | %.2f | ±%.2f | %.0f |\n",
            $classicAvg['uuid_persist'],
            $classicAvg['uuid_persist_std'],
            1000 / ($classicAvg['uuid_persist'] / 1000)
        );
        $content .= sprintf("| TypeSafeId Bundle | %.2f | ±%.2f | %.0f |\n",
            $typeSafeAvg['uuid_persist'],
            $typeSafeAvg['uuid_persist_std'],
            1000 / ($typeSafeAvg['uuid_persist'] / 1000)
        );

        $uuidDiff = (($typeSafeAvg['uuid_persist'] / $classicAvg['uuid_persist']) - 1) * 100;
        $content .= sprintf("\n**Difference:** TypeSafeId is %.1f%% %s\n\n",
            abs($uuidDiff),
            $uuidDiff > 0 ? 'slower' : 'faster'
        );

        $content .= "### ULID Entity Persistence (1000 entities)\n\n";
        $content .= "| Approach | Time (ms) | Std Dev | Entities/sec |\n";
        $content .= "|----------|-----------|---------|-------------|\n";
        $content .= sprintf("| Classic (Doctrine) | %.2f | ±%.2f | %.0f |\n",
            $classicAvg['ulid_persist'],
            $classicAvg['ulid_persist_std'],
            1000 / ($classicAvg['ulid_persist'] / 1000)
        );
        $content .= sprintf("| TypeSafeId Bundle | %.2f | ±%.2f | %.0f |\n",
            $typeSafeAvg['ulid_persist'],
            $typeSafeAvg['ulid_persist_std'],
            1000 / ($typeSafeAvg['ulid_persist'] / 1000)
        );

        $ulidDiff = (($typeSafeAvg['ulid_persist'] / $classicAvg['ulid_persist']) - 1) * 100;
        $content .= sprintf("\n**Difference:** TypeSafeId is %.1f%% %s\n\n",
            abs($ulidDiff),
            $ulidDiff > 0 ? 'slower' : 'faster'
        );

        $content .= "## Analysis\n\n";

        $content .= "### Container Compilation\n";
        if ($compDiff > 10) {
            $content .= "⚠️ TypeSafeId has **significant overhead** during container compilation due to registering one service per EntityId type.\n";
            $content .= "For applications with many entity types (50+), this could impact deployment time.\n\n";
        } elseif ($compDiff > 5) {
            $content .= "⚡ TypeSafeId has **moderate overhead** during container compilation.\n";
            $content .= "This is acceptable for most applications and only affects deployment, not runtime.\n\n";
        } else {
            $content .= "✅ TypeSafeId has **minimal overhead** during container compilation.\n\n";
        }

        $content .= "### Runtime Performance\n";
        $avgRuntimeDiff = ($uuidDiff + $ulidDiff) / 2;
        if (abs($avgRuntimeDiff) < 5) {
            $content .= "✅ Runtime performance is **equivalent** between both approaches.\n";
            $content .= "The custom ID generator with static `::new()` call performs just as well as manual construction.\n\n";
        } elseif ($avgRuntimeDiff < 0) {
            $content .= "🚀 TypeSafeId is **faster** at runtime!\n";
            $content .= "This is likely due to optimized service resolution and lack of reflection.\n\n";
        } else {
            $content .= "⚠️ TypeSafeId is slightly **slower** at runtime.\n";
            $content .= "The overhead is minimal and unlikely to be noticeable in real applications.\n\n";
        }

        $content .= "## Recommendations\n\n";

        if ($compDiff > 20) {
            $content .= "### Consider Optimization\n\n";
            $content .= "If your application has many entity types (50+), consider these optimizations:\n\n";
            $content .= "1. **Shared Generator**: Use one generator service that resolves the ID class dynamically\n";
            $content .= "2. **Lazy Services**: Mark generators as lazy to defer instantiation\n";
            $content .= "3. **Service Subscribers**: Use service subscribers to reduce container size\n\n";
        }

        $content .= "### When to Use TypeSafeId Bundle\n\n";
        $content .= "✅ **Recommended for:**\n";
        $content .= "- Applications valuing type safety and IDE autocompletion\n";
        $content .= "- Domain-driven design with strong value objects\n";
        $content .= "- Projects with < 50 entity types\n";
        $content .= "- Teams that benefit from explicit type hints\n\n";

        $content .= "⚠️ **Consider alternatives if:**\n";
        $content .= "- You have 100+ entity types (container size concern)\n";
        $content .= "- Deployment time is critical\n";
        $content .= "- You need maximum runtime performance at all costs\n\n";

        $content .= "## Conclusion\n\n";
        $content .= sprintf(
            "TypeSafeId Bundle adds **%.1f%% overhead** to container compilation but provides **%.1f%% %s** runtime performance. ",
            abs($compDiff),
            abs($avgRuntimeDiff),
            $avgRuntimeDiff > 0 ? 'slower' : 'faster'
        );
        $content .= "The trade-off is worthwhile for most applications that value type safety and developer experience.\n";

        file_put_contents($reportPath, $content);
        echo "\nBenchmark report generated: $reportPath\n";
    }
}
