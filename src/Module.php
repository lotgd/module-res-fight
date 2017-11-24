<?php
declare(strict_types=1);

namespace LotGD\Module\Res\Fight;

use Composer\Script\Event;
use Doctrine\Common\Util\Debug;
use LotGD\Core\Game;
use LotGD\Core\Events\EventContext;
use LotGD\Core\Models\Scene;
use LotGD\Core\Module as ModuleInterface;
use LotGD\Core\Models\Module as ModuleModel;
use LotGD\Module\NewDay\Module as NewDayModule;

class Module implements ModuleInterface {
    const ModuleIdentifier = "lotgd/module-res-fight";

    const CharacterPropertyBattleState = self::ModuleIdentifier . "/battleState";
    const CharacterPropertyTurns = self::ModuleIdentifier . "/turns";

    const HookAfterBattle = "h/" . self::ModuleIdentifier . "/battleOver";
    const HookFightActions = "h/" . self::ModuleIdentifier . "/fightActions";

    const ModulePropertyBattleSceneId = self::ModuleIdentifier . "/battleSceneId";

    const SceneBattle = self::ModuleIdentifier . "/battle";

    public static function handleEvent(Game $g, EventContext $context): EventContext
    {
        $event = $context->getEvent();

        if ($event === NewDayModule::HookAfterNewDay) {
            $context = self::handleAfterNewDay($g, $context);
        } elseif ($event == "h/lotgd/core/navigate-to/" . self::SceneBattle) {
            $context = self::handleBattleScene($g, $context);
        }

        return $context;
    }

    /**
     * Handles new day "refreshments"
     * @param Game $g
     * @param EventContext $context
     * @return EventContext
     */
    private static function handleAfterNewDay(Game $g, EventContext $context): EventContext
    {
        $turns = 20;
        $viewpoint = $context->getDataField("viewpoint");

        if ($g->getCharacter()->isAlive() == false) {
            $turns-=5;
            $viewpoint->addDescriptionParagraph(
                sprintf("You are back from the dead. Since you died yesterday, you can only fight for %s rounds today.", $turns)
            );
        } else {
            $viewpoint->addDescriptionParagraph(
                sprintf("You feel energized! Today, you can fight for %s rounds.", $turns)
            );
        }

        $g->getCharacter()->setProperty(self::CharacterPropertyTurns, $turns);
        $g->getCharacter()->setHealth($g->getCharacter()->getMaxHealth());

        return $context;
    }

    /**
     * Handles the battle scene
     * @param Game $g
     * @param EventContext $context
     * @return EventContext
     */
    private static function handleBattleScene(Game $g, EventContext $context): EventContext
    {
        /** @var Viewpoint $v */
        $v = $context->getDataField("viewpoint");
        /** @var Character $c */
        $c = $g->getCharacter();
        /** @var array $parameters */
        $parameters = $context->getDataField("parameters");

        // Restore fight from suspension
        $fight = Fight::restore($g);

        // Process battle
        $fight->process($parameters);

        // Check if fight is over to publish "is over" event
        if ($fight->isOver()) {
            $hookData = $g->getEventManager()->publish(
                self::HookAfterBattle,
                New EventBattleOverData([
                    "battle" => $fight->getBattle(),
                    "viewpoint" => $v,
                    "referrerSceneId" => $fight->getReferrerSceneId(),
                    "battleIdentifier" => $fight->getBattleIdentifier()
                ])
            );

            $fight->clear();
        } else {
            // Fight is not over - lets show normal fight actions and suspend the fight.
            $fight->showFightActions();
            $fight->suspend();
        }

        return $context;
    }
    
    public static function onRegister(Game $g, ModuleModel $module)
    {
        $battleScene = Scene::create([
            "template" => self::SceneBattle,
            "title" => "A fight!",
            "description" => "You are fighting."
        ]);

        $g->getEntityManager()->persist($battleScene);
        $g->getEntityManager()->flush();

        $module->setProperty(self::ModulePropertyBattleSceneId, $battleScene->getId());
    }

    public static function onUnregister(Game $g, ModuleModel $module)
    {
        $sceneId = $module->getProperty(self::ModulePropertyBattleSceneId);

        if ($sceneId !== null) {
            $g->getEntityManager()->getRepository(Scene::class)->find($sceneId)->delete($g->getEntityManager());
        }

        $module->setProperty(self::ModulePropertyBattleSceneId, null);
    }
}
