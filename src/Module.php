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
use LotGD\Core\Models\Viewpoint;
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
                    ->findOneBy(["template" => self::SceneGenderChoose])
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
            ->findOneBy(["template" => self::SceneGenderSelect])
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
                ->findOneBy(["template" => NewDayModule::SceneContinue]);
        } else {
            // Redirect to GenderChoose, since the gender looks invalid...
            // You should not end up here though, but you might!
            $scene = $g->getEntityManager()
                ->getRepository(Scene::class)
                ->findOneBy(["template" => self::SceneGenderChoose]);
        }

        $context->setDataField("redirect", $scene);

        return $context;
    }

    private static function getScenes()
    {
        $choose = Scene::create([
            "template" => self::SceneGenderChoose,
            "title" => "Which gender do you have?",
            "description" => "You are looking at your flickering shadow in a cold, empty room. The shadow has no face, "
                ."but still, it talks. «I wander... What's your gender?», it asks you, leaving you questioning yourself."
        ]);

        $select = Scene::create([
            "template" => self::SceneGenderSelect,
            "title" => "You have chosen your gender.",
            "description" => "Your shadow makes an agreeing gesture - or was it you? You don't know, you don't care. "
                ."And you certainly should not see this text."
        ]);

        return [$choose, $select];
    }
    
    public static function onRegister(Game $g, ModuleModel $module)
    {
        // Register new day scene and "restoration" scene.
        $sceneIds = $module->getProperty(self::ModulePropertySceneId);

        if ($sceneIds === null) {
            [$choose, $select] = self::getScenes();

            $g->getEntityManager()->persist($choose);
            $g->getEntityManager()->persist($select);
            $g->getEntityManager()->flush();

            $module->setProperty(self::ModulePropertySceneId, [
                self::SceneGenderChoose => $choose->getId(),
                self::SceneGenderSelect => $select->getId()
            ]);

            // logging
            $g->getLogger()->addNotice(sprintf(
                "%s: Adds scenes (newday: %s, restoration: %s)",
                self::Module,
                $choose->getId(),
                $select->getId()
            ));
        }
    }

    public static function onUnregister(Game $g, ModuleModel $module)
    {
        // Unregister them again.
        $sceneIds = $module->getProperty(self::ModulePropertySceneId);

        if ($sceneIds !== null) {
            // delete village
            $g->getEntityManager()->getRepository(Scene::class)->find($sceneIds[self::SceneGenderChoose])->delete($g->getEntityManager());
            $g->getEntityManager()->getRepository(Scene::class)->find($sceneIds[self::SceneGenderSelect])->delete($g->getEntityManager());

            // set property to null
            $module->setProperty(self::ModulePropertySceneId, null);
        }
    }
}
