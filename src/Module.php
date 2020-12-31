<?php
declare(strict_types=1);

namespace LotGD\Module\Gender;

use LotGD\Core\Action;
use LotGD\Core\ActionGroup;
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
use LotGD\Module\NewDay\SceneTemplates\ContinueScene;

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
            $context = self::handleSceneChoose($g, $context);
        } elseif ($event === "h/lotgd/core/navigate-to/" . self::SceneGenderSelect) {
            $context = self::handleSceneSelect($g, $context);
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

    private static function handleSceneChoose(Game $g, EventContext $context): EventContext
    {
        /** @var Viewpoint $v */
        $v = $context->getDataField("viewpoint");
        $destinationId = $g->getEntityManager()
            ->getRepository(Scene::class)
            ->findOneBy(["template" => GenderSetScene::class])
            ->getId();

        $actionF = new Action($destinationId, "♀ Female ", ["gender" => self::GenderFemale]);
        $actionM = new Action($destinationId, "♂ Male", ["gender" => self::GenderMale]);

        $group = new ActionGroup(self::Module, "Choose", 0);
        $group->setActions([$actionF, $actionM]);

        // Need to have better api here
        $groups = $v->getActionGroups();
        $groups[] = $group;
        $v->setActionGroups($groups);

        return $context;
    }

    private static function handleSceneSelect(Game $g, EventContext $context): EventContext
    {
        $gender = $context->getDataField("parameters")["gender"];
        $continue = true;

        switch($gender) {
            case self::GenderFemale:
            case self::GenderMale:
                $g->getCharacter()->setProperty(self::CharacterPropertyGender, $gender);
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

    private static function getScenes()
    {
        $choose = Scene::create([
            "template" => new SceneTemplate(GenderChooseScene::class, self::Module),
            "title" => "Which gender do you have?",
            "description" => "You are looking at your flickering shadow in a cold, empty room. The shadow has no face, "
                ."but still, it talks. «I wonder... What's your gender?», it asks you, leaving you questioning yourself."
        ]);

        $set = Scene::create([
            "template" => new SceneTemplate(GenderSetScene::class, self::Module),
            "title" => "You have chosen your gender.",
            "description" => "Your shadow makes an agreeing gesture - or was it you? You don't know, you don't care. "
                ."And you certainly should not see this text."
        ]);

        $choose->getTemplate()->setUserAssignable(false);
        $set->getTemplate()->setUserAssignable(false);

        return [$choose, $set];
    }
    
    public static function onRegister(Game $g, ModuleModel $module)
    {
        $em = $g->getEntityManager();

        $newScenes = self::getScenes();

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
