<?php
declare(strict_types=1);

namespace LotGD\Module\Gender\SceneTemplates;

use LotGD\Core\Events\EventContext;
use LotGD\Core\Game;
use LotGD\Core\Models\Scene;
use LotGD\Core\Models\SceneTemplate;
use LotGD\Core\SceneTemplates\SceneTemplateInterface;
use LotGD\Module\Gender\Module;
use LotGD\Module\NewDay\SceneTemplates\ContinueScene;

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

    public static function handleEvent(Game $g, EventContext $context): EventContext
    {
        $gender = $context->getDataField("parameters")["gender"];
        $continue = true;

        switch($gender) {
            case Module::GenderFemale:
            case Module::GenderMale:
                $g->getCharacter()->setProperty(Module::CharacterPropertyGender, $gender);
                break;

            default:
                // You should not end up here, but still let us cover it
                $continue = false;
        }

        if ($continue) {
            // Redirect to SceneContinue to continue the new day
            $scene = $g->getEntityManager()
                ->getRepository(Scene::class)
                ->findOneBy(["template" => ContinueScene::class]);
        } else {
            // Redirect to GenderChoose, since the gender looks invalid...
            // You should not end up here though, but you might!
            $scene = $g->getEntityManager()
                ->getRepository(Scene::class)
                ->findOneBy(["template" => GenderChooseScene::class]);
        }

        $context->setDataField("redirect", $scene);

        return $context;
    }
}
