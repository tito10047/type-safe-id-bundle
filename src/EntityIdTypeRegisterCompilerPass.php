<?php

namespace Tito10047\TypeSafeIdBundle;

use Symfony\Bridge\Doctrine\Types\AbstractUidType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use League\ConstructFinder\ConstructFinder;
use Tito10047\TypeSafeIdBundle\IdGenerator\TypeIdGenerator;

class EntityIdTypeRegisterCompilerPass implements CompilerPassInterface {

	private const CONTAINER_TYPES_PARAMETER = 'doctrine.dbal.connection_factory.types';
	private const PROJECT_TYPES_PATTERN     = '/EntityId(\\\\(.*))?/i';
	private const SRC_FOLDER_MASK           = '%s/src';

	public function __construct(
		private string $projectDir,
	) {
	}

	public function process(ContainerBuilder $container): void
	{
		/** @var array<string, array{class: class-string}> $typeDefinition */
		$typeDefinition = $container->getParameter(self::CONTAINER_TYPES_PARAMETER);

		$types = $this->generateTypes($container);

		/** @var array{namespace: string, name: string, id_class: string} $type */
		foreach ($types as $type) {
			$name      = $type['name'];
			$namespace = $type['namespace'];
			$idClass   = $type['id_class'];

			if (array_key_exists($name, $typeDefinition)) {
				continue;
			}

			$typeDefinition[$name] = ['class' => $namespace];

			// Register a custom ID generator for this entity ID type
			if ($idClass) {
				$generatorServiceId = 'doctrine.id_generator.' . str_replace('\\', '_', $idClass);
				$generatorDefinition = new Definition(TypeIdGenerator::class, [$idClass]);
				$generatorDefinition->setPublic(false);
				$generatorDefinition->addTag('doctrine.id_generator');
				$container->setDefinition($generatorServiceId, $generatorDefinition);
			}
		}

		$container->setParameter(self::CONTAINER_TYPES_PARAMETER, $typeDefinition);
	}

	/** @return Generator<int, array{namespace: class-string, name: string, id_class: string}> */
	private function generateTypes(ContainerBuilder $container): iterable
	{
		$srcFolder = sprintf(self::SRC_FOLDER_MASK, $this->projectDir);

		$classNames = ConstructFinder::locatedIn($srcFolder)->findClassNames();

		foreach ($classNames as $className) {
			if (preg_match(self::PROJECT_TYPES_PATTERN, $className) === 0) {
				continue;
			}

			$reflection = new \ReflectionClass($className);

			if (! $reflection->isSubclassOf(AbstractUidType::class) && !$reflection->isSubclassOf(AbstractIntIdType::class) ) {
				continue;
			}

			// Find the corresponding entity ID class
			$idClass = null;
			if (str_ends_with($className, 'Type')) {
				$idClass = substr($className, 0, -4); // Remove 'Type' suffix
			}

			yield [
				'namespace' => $reflection->getName(),
				'name'      => $reflection->getName(),
				'id_class'  => $idClass,
			];
		}
	}
}