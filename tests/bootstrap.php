<?php

require __DIR__ . '/../vendor/autoload.php';

// Fix for MakerBundle EntityIdTypeEnum if it's not loaded correctly in some environments
if (!class_exists(\Symfony\Bundle\MakerBundle\Maker\Common\EntityIdTypeEnum::class)) {
    require_once __DIR__ . '/../vendor/symfony/maker-bundle/src/Maker/Common/EntityIdTypeEnum.php';
}
