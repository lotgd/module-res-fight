<?php

namespace LotGD\Module\Res\Fight\Tests;

use DateTime;
use Symfony\Component\Yaml\Yaml;
use LotGD\Core\Game;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\FighterInterface;
use LotGD\Core\Models\Monster;
use LotGD\Core\Models\Scene;
use LotGD\Core\Tools\Model\AutoScaleFighter;
use LotGD\Module\NewDay\Module as NewDayModule;

use LotGD\Module\Res\Fight\Module as FightModule;
use LotGD\Module\Res\Fight\Fight;
use LotGD\Module\Res\Fight\Tests\helpers\EventRegistry;

class EnemyFighter implements FighterInterface {
    use AutoScaleFighter;
    private $name;
    private $level;
    private $health;

    public function __construct(string $name, int $level)
    {
        $this->name = $name;
        $this->level = $level;
        $this->health = $this->getMaxHealth();
    }

    public function getDisplayName(): string {return $this->name;}
    public function getName(): string {return $this->name;}
    public function getLevel(): int {return $this->level;}
    public function getHealth(): int {return $this->health;}
    public function setHealth(int $amount): void {$this->health = $amount;}
    public function heal(int $heal): void {$this->health += $heal;}
    public function damage(int $damage): void {$this->health -= $damage;}
    public function isAlive(): bool {return $this->health > 0;}
}

class FightHelperTest extends ModuleTestCase
{
    const Library = 'lotgd/module-res-fight';

    public function getDataSet(): array
    {
        return Yaml::parseFile(implode(DIRECTORY_SEPARATOR, [__DIR__, 'datasets', 'fight-helpers.yml']));
    }

    protected function preloadGameConditions($charid)
    {
        /** @var Game $game */
        $game = $this->g;

        /** @var Character $character */
        $character = $this->getEntityManager()->getRepository(Character::class)->find($charid);
        $character->setProperty(NewDayModule::CharacterPropertyLastNewDay, new DateTime());
        $game->setCharacter($character);

        $v = $game->getViewpoint();

        // Make sure we are in the village.
        $this->assertSame("Village", $v->getTitle());
    }

    public function testIfFightSequenceWorksProperly()
    {
        $this->preloadGameConditions("10000000-0000-0000-0000-000000000006");
        $enemy = new EnemyFighter("Paper Warrior", 1);
        $this->getEntityManager()->detach($enemy);

        $villageScene = $this->getEntityManager()->getRepository(Scene::class)->find("20000000-0000-0000-0000-000000000001");
        $fight = Fight::start($this->g, $enemy, $villageScene, "test-battle");

        // Without anything done by showFightActions, this array should have default and hidden group.
        $actionGroups = $this->g->getViewpoint()->getActionGroups();
        $c = 0;
        foreach ($actionGroups as $actionGroup) {
            $id = $actionGroup->getId();

            if ($id == "lotgd/core/hidden") $c++;
            if ($id == "lotgd/core/default") $c++;
        }
        $this->assertSame(2, $c);

        $fight->showFightActions();

        // showFightActions should add two action groups and remove the existing ones.
        $actionGroups = $this->g->getViewpoint()->getActionGroups();
        $c = 0;
        foreach ($actionGroups as $actionGroup) {
            $id = $actionGroup->getId();

            if ($id == "lotgd/core/hidden") $c++;
            if ($id == "lotgd/core/default") $c++;
        }
        $this->assertSame(0, $c);
        $this->assertSame(2, count($actionGroups));

        // Suspend the fight ("save it")
        $fight->suspend();

        // Take the attack action
        $action = $this->searchAction($this->g->getViewpoint(), ["getTitle", "Attack"], "lotgd/module-res-fight/fight");
        $this->g->takeAction($action->getId());

        $v = $this->g->getViewpoint();

        $this->assertSame("A fight!", $v->getTitle());
        $this->assertNotSame("You are fighting.", $v->getDescription());

        $fight->clear();
    }

    public function testIfFightHooksAreCalled()
    {
        $this->preloadGameConditions("10000000-0000-0000-0000-000000000008");
        $enemy = new EnemyFighter("Paper Warrior", 1);
        $this->getEntityManager()->detach($enemy);
        $villageScene = $this->getEntityManager()->getRepository(Scene::class)->find("20000000-0000-0000-0000-000000000001");
        $villageSceneId = $villageScene->getId();
        $fight = Fight::start($this->g, $enemy, $villageScene, "test-battle");
        $fight->showFightActions();

        // Check if EventRegistrator registered the fightAction Hook
        $this->assertSame(1, EventRegistry::$registration[FightModule::HookSelectAction]);

        // Register an a reaction for the afterBattle hook before it actually happens.
        $works = False;
        EventRegistry::reactOn(FightModule::HookBattleOver, function ($game, $context) use (&$works, $villageSceneId) {
            $sceneId = $context->getDataField("referrerSceneId");
            $battleIdentifier = $context->getDataField("battleIdentifier");

            if ($sceneId == $villageSceneId and $battleIdentifier == "test-battle") {
                $works = true;
            }
        });

        $fight->suspend();
        $action = $this->searchAction($this->g->getViewpoint(), ["getTitle", "Attack"], "lotgd/module-res-fight/fight");
        $this->g->takeAction($action->getId());

        // Validate if the AfterBattle hook works properly
        $this->assertSame(1, EventRegistry::$registration[FightModule::HookBattleOver]);
        $this->assertSame(1, EventRegistry::$registration[FightModule::HookActionChosen]);
        $this->assertTrue($works);

        EventRegistry::reset();
    }
}