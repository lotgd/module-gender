<?php
declare(strict_types=1);

namespace LotGD\Module\Gender\SceneTemplates;

use LotGD\Core\SceneTemplates\SceneTemplateInterface;
use LotGD\Module\Gender\Module;

class GenderSetScene implements SceneTemplateInterface
{
    public static function getNavigationEvent(): string
    {
        return Module::SceneGenderSelect;
    }
}
