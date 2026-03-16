<?php

use Symfony\Bundle\MakerBundle\Maker\Common\EntityIdTypeEnum;

?>
<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements; ?>

#[ORM\Entity(repositoryClass: <?= $repository_class_name ?>::class)]
<?php if ($should_escape_table_name): ?>#[ORM\Table(name: '`<?= $table_name ?>`')]
<?php endif ?>
<?php if ($api_resource): ?>
#[ApiResource]
<?php endif ?>
<?php if ($broadcast): ?>
#[Broadcast]
<?php endif ?>
class <?= $class_name."\n" ?>
{
    #[ORM\Id]
<?php if (EntityIdTypeEnum::UUID === $id_type || EntityIdTypeEnum::ULID === $id_type): ?>
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: '<?= $id_generator_service ?>')]
    #[ORM\Column(type: <?= $class_name ?>IdType::class, unique: true)]
    private ?<?= $class_name ?>Id $id = null;
<?php else: ?>
    #[ORM\GeneratedValue]
    #[ORM\Column(type: <?= $class_name ?>IdType::class, unique: true)]
    private ?<?= $class_name ?>Id $id = null;

    public function setId(<?= $class_name ?>Id $id): void
    {
        $this->id = $id;
    }
<?php endif; ?>

    public function getId(): ?<?= $class_name ?>Id
    {
        return $this->id;
    }
}
