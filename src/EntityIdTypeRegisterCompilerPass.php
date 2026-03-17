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
use Tito10047\TypeSafeIdBundle\IdGenerator\UniversalTypeIdGenerator;
use Tito10047\TypeSafeIdBundle\Util\PathUtil;

class EntityIdTypeRegisterCompilerPass implements CompilerPassInterface {

	private const CONTAINER_TYPES_PARAMETER = 'doctrine.dbal.connection_factory.types';

	public function __construct(
		private string $projectDir,
	) {
	}

	public function process(ContainerBuilder $container): void
	{
		// throw new \Exception('CompilerPass is running');
		if (!$container->hasParameter('type_safe_id.entity_path')) {
			return;
		}

		/** @var array<string, array{class: class-string}> $typeDefinition */
		$typeDefinition = $container->getParameter(self::CONTAINER_TYPES_PARAMETER);

		$entityNamespace = $container->getParameter('type_safe_id.entity_path');
		$typeIdNamespace = $container->getParameter('type_safe_id.type_id_path');

		$types = $this->generateTypes($container, PathUtil::namespaceToPath($typeIdNamespace));

		// Build entity -> ID class mapping for universal generator
		$entityToIdClassMap = [];

		/** @var array{namespace: string, name: string, id_class: string} $type */
		foreach ($types as $type) {
			$name      = $type['name'];
			$namespace = $type['namespace'];
			$idClass   = $type['id_class'];

			if (array_key_exists($name, $typeDefinition)) {
				continue;
			}

			$typeDefinition[$name] = ['class' => $namespace];

			// Build mapping: Entity class -> ID class
			if ($idClass) {
				// Convert App\Domain\ValueObject\ProductId to App\Domain\Entity\Product
				$entityClass = str_replace($typeIdNamespace, $entityNamespace, $idClass);
				$entityClass = preg_replace('/Id$/', '', $entityClass);

				$entityToIdClassMap[$entityClass] = $idClass;
			}
		}

		// Register single universal ID generator service with the mapping
		if (!empty($entityToIdClassMap)) {
			$generatorDefinition = new Definition(UniversalTypeIdGenerator::class, [$entityToIdClassMap]);
			$generatorDefinition->setPublic(false);
			$generatorDefinition->addTag('doctrine.id_generator');
			$container->setDefinition('doctrine.id_generator.universal', $generatorDefinition);
		}

		$container->setParameter(self::CONTAINER_TYPES_PARAMETER, $typeDefinition);
	}

	/** @return iterable<int, array{namespace: class-string, name: string, id_class: string}> */
	private function generateTypes(ContainerBuilder $container, string $typeIdPath): iterable
	{
		$searchPath = $typeIdPath;
		if (!str_starts_with($searchPath, '/') && !str_contains($searchPath, ':')) {
			$searchPath = sprintf('%s/%s', $this->projectDir, $typeIdPath);
		}

		if (!is_dir($searchPath)) {
			return;
		}

		$classNames = ConstructFinder::locatedIn($searchPath)->findClassNames();

		foreach ($classNames as $className) {
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