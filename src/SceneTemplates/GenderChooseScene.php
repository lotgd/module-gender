<?php
declare(strict_types=1);

namespace LotGD\Module\Gender\SceneTemplates;

use LotGD\Core\Action;
use LotGD\Core\ActionGroup;
use LotGD\Core\Events\EventContext;
use LotGD\Core\Game;
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
        $choose = new Scene(
            title: "Which gender do you have?",
            description: <<<TXT
                You are looking at your flickering shadow in a cold, empty room. The shadow has no face, 
                but still, it talks. «I wonder... What's your gender?», it asks you, leaving you questioning yourself.
            TXT,
            template: new SceneTemplate(GenderChooseScene::class, Module::Module),
        );

        $choose->getTemplate()->setUserAssignable(false);

        return $choose;
    }

    public static function handleEvent(Game $g, EventContext $context): EventContext
    {
        /** @var Viewpoint $v */
        $v = $context->getDataField("viewpoint");
        $destinationId = $g->getEntityManager()
            ->getRepository(Scene::class)
            ->findOneBy(["template" => GenderSetScene::class])
            ->getId();

        $actionF = new Action($destinationId, "♀ Female ", ["gender" => Module::GenderFemale]);
        $actionM = new Action($destinationId, "♂ Male", ["gender" => Module::GenderMale]);

        $group = new ActionGroup(Module::Module, "Choose", 0);
        $group->setActions([$actionF, $actionM]);

        // Need to have better api here
        $groups = $v->getActionGroups();
        $groups[] = $group;
        $v->setActionGroups($groups);

        return $context;
    }
}
