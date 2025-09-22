<?php

namespace Tito10047\TypeSafeIdBundle;

use Symfony\Bridge\Doctrine\Types\AbstractUidType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use League\ConstructFinder\ConstructFinder;

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

		$types = $this->generateTypes();

		/** @var array{namespace: string, name: string} $type */
		foreach ($types as $type) {
			$name      = $type['name'];
			$namespace = $type['namespace'];

			if (array_key_exists($name, $typeDefinition)) {
				continue;
			}

			$typeDefinition[$name] = ['class' => $namespace];
		}

		$container->setParameter(self::CONTAINER_TYPES_PARAMETER, $typeDefinition);
	}

	/** @return Generator<int, array{namespace: class-string, name: string}> */
	private function generateTypes(): iterable
	{
		$srcFolder = sprintf(self::SRC_FOLDER_MASK, $this->projectDir);

		$classNames = ConstructFinder::locatedIn($srcFolder)->findClassNames();

		foreach ($classNames as $className) {
			if (preg_match(self::PROJECT_TYPES_PATTERN, $className) === 0) {
				continue;
			}

			$reflection = new \ReflectionClass($className);

			if (! $reflection->isSubclassOf(AbstractUidType::class) ) {
				continue;
			}



			yield [
				'namespace' => $reflection->getName(),
				'name'      => $reflection->getName(),
			];
		}
	}
}