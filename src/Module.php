<?php
declare(strict_types=1);

namespace LotGD\Module\Gender;

use LotGD\Core\Game;
use LotGD\Core\Events\EventContext;
use LotGD\Core\Module as ModuleInterface;
use LotGD\Core\Models\Module as ModuleModel;
use LotGD\Core\Models\Scene;
use LotGD\Core\Models\SceneTemplate;
use LotGD\Core\Models\Viewpoint;
use LotGD\Module\Gender\SceneTemplates\GenderChooseScene;
use LotGD\Module\Gender\SceneTemplates\GenderSetScene;
use LotGD\Module\NewDay\Module as NewDayModule;

const MODULE = "lotgd/module-gender";

class Module implements ModuleInterface {
    const Module = MODULE;
    const ModulePropertySceneId = MODULE . "/sceneIds";
    const CharacterPropertyGender = MODULE . "/gender";
    const SceneGenderChoose = MODULE . "/choose";
    const SceneGenderSelect = MODULE . "/select";

    const GenderFemale = "Female";
    const GenderMale = "Male";

    public static function handleEvent(Game $g, EventContext $context): EventContext
    {
        $event = $context->getEvent();

        if ($event === NewDayModule::HookBeforeNewDay) {
            $context = self::handleHookBeforeNewDay($g, $context);
        } elseif ($event === "h/lotgd/core/navigate-to/" . self::SceneGenderChoose) {
            $context = GenderChooseScene::handleEvent($g, $context);
        } elseif ($event === "h/lotgd/core/navigate-to/" . self::SceneGenderSelect) {
            $context = GenderSetScene::handleEvent($g, $context);
        }

        return $context;
    }

    public static function handleHookBeforeNewDay(Game $g, EventContext $context): EventContext
    {
        if ($g->getCharacter()->getProperty(self::CharacterPropertyGender, null) === null) {
            $context->setDataField(
                "redirect",
                $g->getEntityManager()
                    ->getRepository(Scene::class)
                    ->findOneBy(["template" => GenderChooseScene::class])
            );
        }

        return $context;
    }
    
    public static function onRegister(Game $g, ModuleModel $module)
    {
        $em = $g->getEntityManager();

        $newScenes = [
            GenderChooseScene::getScaffold(),
            GenderSetScene::getScaffold(),
        ];

        foreach ($newScenes as $scene) {
            $em->persist($scene);
            $em->persist($scene->getTemplate());
        }

        // no flush

        $g->getLogger()->notice(sprintf(
            "%s: Adds scenes (choose: %s, set: %s)",
            self::Module,
            $newScenes[0]->getId(),
            $newScenes[1]->getId()
        ));
    }

    public static function onUnregister(Game $g, ModuleModel $module)
    {
        $em = $g->getEntityManager();

        // Get all scenes that use our SceneTemplates. As they are not user-assignable and don't make sense without the
        // module itself, we will freely delete all of them.
        $registeredScenes = $em->getRepository(Scene::class)->findBy([
            "template" => [
                GenderChooseScene::class,
                GenderSetScene::class,
            ]
        ]);

        foreach ($registeredScenes as $scene) {
            $template = $scene->getTemplate();

            // We must remove the template and the scene.
            $em->remove($template);
            $em->remove($scene);
        }
    }
}
