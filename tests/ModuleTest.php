<?php
declare(strict_types=1);

use LotGD\Core\Configuration;
use LotGD\Core\GameBuilder;
use LotGD\Core\Game;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\Module as ModuleModel;
use LotGD\Core\Tests\ModelTestCase as ModelTestCase;
use Symfony\Component\Yaml\Yaml;

use LotGD\Module\Gender\Module;

class ModuleTest extends ModelTestCase
{
    const Library = 'lotgd/module-gender';

    protected $dataset = "module";

    public function getDataSet(): array
    {
        return Yaml::parseFile(implode(DIRECTORY_SEPARATOR, [__DIR__, 'datasets', 'module.yml']));
    }

    public function setUp(): void
    {
        parent::setUp();

        // Register and unregister before/after each test, since
        // handleEvent() calls may expect the module be registered (for example,
        // if they read properties from the model).
        $this->moduleModel = new ModuleModel(self::Library);
        $this->moduleModel->save($this->getEntityManager());
        Module::onRegister($this->g, $this->moduleModel);

        $this->g->getEntityManager()->flush();
        $this->g->getEntityManager()->clear();
    }

    public function tearDown(): void
    {
        $this->g->getEntityManager()->flush();
        $this->g->getEntityManager()->clear();

        Module::onUnregister($this->g, $this->moduleModel);
        $m = $this->getEntityManager()->getRepository(ModuleModel::class)->find(self::Library);
        if ($m) {
            $m->delete($this->getEntityManager());
        }

        parent::tearDown();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testUnregister()
    {
        Module::onUnregister($this->g, $this->moduleModel);
        $m = $this->getEntityManager()->getRepository(ModuleModel::class)->find(self::Library);
        $m->delete($this->getEntityManager());

        // Assert that databases are the same before and after.
        // TODO for module author: update list of tables below to include the
        // tables you modify during registration/unregistration.
        $tableList = [
            'characters', 'scenes', 'modules', 'scene_connections', "module_properties"
        ];

        $this->assertDataWasKeptIntact($tableList);

        // Since tearDown() contains an onUnregister() call, this also tests
        // double-unregistering, which should be properly supported by modules.
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testHandleUnknownEvent()
    {
        // Always good to test a non-existing event just to make sure nothing happens :).
        $context = new \LotGD\Core\Events\EventContext(
            "e/lotgd/tests/unknown-event",
            "none",
            \LotGD\Core\Events\EventContextData::create([])
        );

        Module::handleEvent($this->g, $context);
    }

    public function testModuleFlow()
    {
        /** @var Game $game */
        $game = $this->g;
        $character = $this->getEntityManager()->getRepository(Character::class)->find("10000000-0000-0000-0000-000000000001");
        $game->setCharacter($character);
        $v = $game->getViewpoint();

        // Assert new day happened
        $this->assertSame("Which gender do you have?", $v->getTitle());

        $groups = $v->getActionGroups();
        $this->assertCount(3, $v->getActionGroups());
        $this->assertCount(2, $v->getActionGroups()[2]->getActions());

        $actionId = $v->getActionGroups()[2]->getActions()[0]->getId();
        $game->takeAction($actionId);

        $this->assertSame("It is a new day!", $v->getTitle());
        $this->assertSame(
            Module::GenderFemale,
                $game->getCharacter()->getProperty(Module::CharacterPropertyGender, null)
        );
    }
}
