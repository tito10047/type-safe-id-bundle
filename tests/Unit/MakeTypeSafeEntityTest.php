<?php

namespace Tito10047\TypeSafeIdBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Command\Command;
use Tito10047\TypeSafeIdBundle\Maker\MakeTypeSafeEntity;

class MakeTypeSafeEntityTest extends TestCase
{
    private MakeTypeSafeEntity $maker;

    protected function setUp(): void
    {
        $this->maker = new MakeTypeSafeEntity('src/Entity', 'src/TypeId', 'src/Repository');
    }

    public function testGetCommandName(): void
    {
        $this->assertSame('make:entity:typesafe', MakeTypeSafeEntity::getCommandName());
    }

    public function testGetCommandDescription(): void
    {
        $this->assertSame('Creates a new Doctrine entity class with type-safe ID', MakeTypeSafeEntity::getCommandDescription());
    }

    public function testConfigureCommand(): void
    {
        $command = new Command('make:entity:typesafe');
        $inputConfig = new InputConfiguration();

        $this->maker->configureCommand($command, $inputConfig);

        $this->assertTrue($command->getDefinition()->hasArgument('name'));
        $this->assertTrue($command->getDefinition()->hasOption('with-ulid'));
        $this->assertTrue($command->getDefinition()->hasOption('with-uuid'));
    }
}
