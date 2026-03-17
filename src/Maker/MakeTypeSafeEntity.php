<?php

namespace Tito10047\TypeSafeIdBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Maker\Common\EntityIdTypeEnum;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\UuidV7;
use Symfony\Bridge\Doctrine\Types\AbstractUidType;
use Tito10047\TypeSafeIdBundle\AbstractIntIdType;
use Tito10047\TypeSafeIdBundle\Util\PathUtil;

class MakeTypeSafeEntity extends AbstractMaker
{
    public function __construct(
        private readonly string $entityPath,
        private readonly string $typeIdPath,
        private readonly string $repositoryPath,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:entity:typesafe';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new Doctrine entity class with type-safe ID';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the entity class (e.g. <alternate>Foo</alternate>)')
            ->addOption('with-ulid', null, InputOption::VALUE_NONE, 'Use ULID as ID')
            ->addOption('with-uuid', null, InputOption::VALUE_NONE, 'Use UUID as ID')
            ->setHelp(file_get_contents(__DIR__ . '/../../docs/make_entity_typesafe.txt') ?: 'Creates a new Doctrine entity class with type-safe ID')
        ;
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        // No extra dependencies needed beyond MakerBundle and Doctrine
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (null === $input->getArgument('name')) {
            $argument = $command->getDefinition()->getArgument('name');
            $input->setArgument('name', $io->ask($argument->getDescription()));
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $className = $input->getArgument('name');
        
        $idType = EntityIdTypeEnum::INT;
        if ($input->getOption('with-ulid')) {
            $idType = EntityIdTypeEnum::ULID;
        } elseif ($input->getOption('with-uuid')) {
            $idType = EntityIdTypeEnum::UUID;
        }

        $entityNamespace = PathUtil::pathToNamespace($this->entityPath);
        $typeIdNamespace = PathUtil::pathToNamespace($this->typeIdPath);
        $repositoryNamespace = PathUtil::pathToNamespace($this->repositoryPath);

        $entityClassDetails = $generator->createClassNameDetails($className, $entityNamespace);
        $idClassDetails = $generator->createClassNameDetails($className . 'Id', $typeIdNamespace);
        $idTypeClassDetails = $generator->createClassNameDetails($className . 'IdType', $typeIdNamespace);
        $repositoryClassDetails = $generator->createClassNameDetails($className, $repositoryNamespace, 'Repository');

        // 1. Generate ID class
        $useStatements = new UseStatementGenerator([]);
        if ($idType === EntityIdTypeEnum::UUID) {
            $useStatements->addUseStatement(UuidV7::class);
        } elseif ($idType === EntityIdTypeEnum::ULID) {
            $useStatements->addUseStatement(Ulid::class);
        }
        
        $generator->generateClass(
            $idClassDetails->getFullName(),
            __DIR__ . '/../../templates/extension/maker/TypeId.tpl.php',
            [
                'id_type' => $idType,
                'use_statements' => $useStatements,
            ]
        );

        // 2. Generate ID Type class
        $useStatements = new UseStatementGenerator([]);
        $useStatements->addUseStatement($idClassDetails->getFullName());
        if ($idType === EntityIdTypeEnum::UUID || $idType === EntityIdTypeEnum::ULID) {
            $useStatements->addUseStatement(AbstractUidType::class);
        } else {
            $useStatements->addUseStatement(AbstractIntIdType::class);
        }
        
        $generator->generateClass(
            $idTypeClassDetails->getFullName(),
            __DIR__ . '/../../templates/extension/maker/TypeIdType.tpl.php',
            [
                'id_type' => $idType,
                'id_class' => $idClassDetails->getShortName(),
                'use_statements' => $useStatements,
            ]
        );

        // 3. Generate Repository
        $useStatements = new UseStatementGenerator([]);
        $useStatements->addUseStatement(\Doctrine\ORM\EntityRepository::class); // Fallback or use ServiceEntityRepository
        $useStatements->addUseStatement(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::class);
        $useStatements->addUseStatement(\Doctrine\Persistence\ManagerRegistry::class);
        $useStatements->addUseStatement($entityClassDetails->getFullName());
        $useStatements->addUseStatement($idClassDetails->getFullName());
        $useStatements->addUseStatement(\Doctrine\ORM\QueryBuilder::class);

        $generator->generateClass(
            $repositoryClassDetails->getFullName(),
            __DIR__ . '/../../templates/extension/maker/Repository.tpl.php',
            [
                'entity_full_class_name' => $entityClassDetails->getFullName(),
                'entity_class_name' => $entityClassDetails->getShortName(),
                'id_class' => $idClassDetails->getShortName(),
                'id_type' => $idType,
                'use_statements' => $useStatements,
                'include_example_comments' => false,
                'with_password_upgrade' => false,
                'entity_alias' => strtolower($entityClassDetails->getShortName()[0]),
            ]
        );

        // 4. Generate Entity
        $useStatements = new UseStatementGenerator([]);
        $useStatements->addUseStatement([[\Doctrine\ORM\Mapping::class => 'ORM']]);
        $useStatements->addUseStatement($idClassDetails->getFullName());
        $useStatements->addUseStatement($idTypeClassDetails->getFullName());

        $generator->generateClass(
            $entityClassDetails->getFullName(),
            __DIR__ . '/../../templates/extension/maker/Entity.tpl.php',
            [
                'repository_class_name' => $repositoryClassDetails->getShortName(),
                'id_type' => $idType,
                'id_generator_service' => 'doctrine.id_generator.universal',
                'use_statements' => $useStatements,
                'should_escape_table_name' => false,
                'api_resource' => false,
                'broadcast' => false,
                'table_name' => null, // Will be handled by Doctrine naming strategy
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        
        $io->text([
            'Next: You can now add fields to your entity using the official make:entity command:',
            sprintf(' <fg=yellow>php bin/console make:entity %s</>', $className),
            '',
        ]);
    }

}
