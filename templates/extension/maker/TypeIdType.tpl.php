<?php

use Symfony\Bundle\MakerBundle\Maker\Common\EntityIdTypeEnum;

?>
<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements; ?>

final class <?= $class_name ?> extends AbstractUidType
{
    public function getName(): string
    {
        return self::class;
    }

    protected function getUidClass(): string
    {
        return <?= $id_class ?>::class;
    }
}