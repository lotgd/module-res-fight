<?php
declare(strict_types=1);

namespace LotGD\Module\Res\Fight\Tests;

use LotGD\Core\Events\EventContext;
use LotGD\Core\Events\EventContextData;
use LotGD\Core\Game;
use LotGD\Core\Models\Character;
use Symfony\Component\Yaml\Yaml;

use LotGD\Module\Res\Fight\Module;

class NewDayResetTest extends ModuleTestCase
{
    const Library = 'lotgd/module-res-fight';

    public function getDataSet(): array
    {
        return Yaml::parseFile(implode(DIRECTORY_SEPARATOR, [__DIR__, 'datasets', 'module.yml']));
    }

    public function testHandleUnknownEvent()
    {
        // Always good to test a non-existing event just to make sure nothing happens :).
        $context = new EventContext(
            "e/lotgd/tests/unknown-event",
            "none",
            EventContextData::create([])
        );

        Module::handleEvent($this->g, $context);
    }

    public function testNewDayEventForAliveCharacter()
    {
        /** @var Game $game */
        $game = $this->g;
        /** @var Character $character */
        $character = $this->getEntityManager()->getRepository(Character::class)->findById("10000000-0000-0000-0000-000000000001")[0];
        $game->setCharacter($character);
        $v = $game->getViewpoint();

        // Assert new day happened
        $this->assertSame("It is a new day!", $v->getTitle());

        // Assert that our new day inserts work
        $descriptions = explode("\n\n", $v->getDescription());
        $this->assertContains("You feel energized! Today, you can fight for 20 rounds.", $descriptions);
        $this->assertSame($character->getMaxHealth(), $character->getHealth());
        $this->assertSame(20, $character->getTurns());
    }

    public function testNewDayEventFoDeadCharacter()
    {
        /** @var Game $game */
        $game = $this->g;
        /** @var Character $character */
        $character = $this->getEntityManager()->getRepository(Character::class)->findById("10000000-0000-0000-0000-000000000004")[0];
        $game->setCharacter($character);
        $this->assertSame(0, $character->getHealth());

        $v = $game->getViewpoint();

        // Assert new day happened
        $this->assertSame("It is a new day!", $v->getTitle());

        // Assert that our new day inserts work
        $descriptions = explode("\n\n", $v->getDescription());
        $this->assertContains("You are back from the dead. Since you died yesterday, you can only fight for 15 rounds today.", $descriptions);
        $this->assertSame($character->getMaxHealth(), $character->getHealth());
        $this->assertSame(15, $character->getTurns());
    }
}
