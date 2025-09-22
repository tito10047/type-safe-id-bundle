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
    #[ORM\Column(type: <?= $class_name ?>IdType::class, unique: true)]
    private <?= $class_name ?>Id $id;

	public function __construct()
    {
        $this->id = new <?= $class_name ?>Id();
    }

    public function getId(): <?= $class_name ?>Id
    {
        return $this->id;
    }
}
