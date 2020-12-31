<?php
declare(strict_types=1);

namespace LotGD\Module\Gender\SceneTemplates;

use LotGD\Core\Models\Scene;
use LotGD\Core\Models\SceneTemplate;
use LotGD\Core\SceneTemplates\SceneTemplateInterface;
use LotGD\Module\Gender\Module;

class GenderChooseScene implements SceneTemplateInterface
{
    public static function getNavigationEvent(): string
    {
        return Module::SceneGenderChoose;
    }

    public static function getScaffold()
    {
        $choose = Scene::create([
            "template" => new SceneTemplate(GenderChooseScene::class, Module::Module),
            "title" => "Which gender do you have?",
            "description" => "You are looking at your flickering shadow in a cold, empty room. The shadow has no face, "
                ."but still, it talks. «I wonder... What's your gender?», it asks you, leaving you questioning yourself."
        ]);

        $choose->getTemplate()->setUserAssignable(false);

        return $choose;
    }
}
