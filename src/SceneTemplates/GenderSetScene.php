<?php
declare(strict_types=1);

namespace LotGD\Module\Gender\SceneTemplates;

use LotGD\Core\Models\Scene;
use LotGD\Core\Models\SceneTemplate;
use LotGD\Core\SceneTemplates\SceneTemplateInterface;
use LotGD\Module\Gender\Module;

class GenderSetScene implements SceneTemplateInterface
{
    public static function getNavigationEvent(): string
    {
        return Module::SceneGenderSelect;
    }

    public static function getScaffold(): Scene
    {
        $set = Scene::create([
            "template" => new SceneTemplate(GenderSetScene::class, Module::Module),
            "title" => "You have chosen your gender.",
            "description" => "Your shadow makes an agreeing gesture - or was it you? You don't know, you don't care. "
                ."And you certainly should not see this text."
        ]);


        $set->getTemplate()->setUserAssignable(false);

        return $set;
    }
}
