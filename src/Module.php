<?php
declare(strict_types=1);

namespace LotGD\Module\Res\Fight;

use LotGD\Core\Game;
use LotGD\Core\Events\EventContext;
use LotGD\Core\Models\BasicEnemy;
use LotGD\Core\Models\Character;
use LotGD\Core\Models\Scene;
use LotGD\Core\Module as ModuleInterface;
use LotGD\Core\Models\Module as ModuleModel;
use LotGD\Module\NewDay\Module as NewDayModule;

class Module implements ModuleInterface {
    const ModuleIdentifier = "lotgd/module-res-fight";

    const CharacterPropertyBattleState = self::ModuleIdentifier . "/battleState";
    const CharacterPropertyTurns = self::ModuleIdentifier . "/turns";
    const CharacterPropertyCurrentExperience = self::ModuleIdentifier . "/experience";
    const CharacterPropertyNeededExperience = self::ModuleIdentifier . "/experienceForLevelUp";

    const HookBattleOver = "h/" . self::ModuleIdentifier . "/battleOver";
    const HookSelectAction = "h/" . self::ModuleIdentifier . "/fightSelectAction";
    const HookActionChosen = "h/" . self::ModuleIdentifier . "/fightActionChosen";

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
                self::HookBattleOver,
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

    /**
     * Adds experience to a character based on the enemy and the level difference between the character and the enemy.
     * @param Character $character
     * @param BasicEnemy $enemy
     * @return int Experience gained.
     */
    public static function characterEarnExperience(Character $character, BasicEnemy $enemy): int
    {
        $experienceTable = [
            1 => 14,
            2 => 24,
            3 => 34,
            4 => 45,
            5 => 55,
            6 => 66,
            7 => 77,
            8 => 89,
            9 => 101,
            10 => 114,
            11 => 127,
            12 => 141,
            13 => 135,
            14 => 172,
            15 => 189,
            16 => 207,
            17 => 223,
            18 => 249,
        ];
        $levelDifference = $enemy->getLevel() - $character->getLevel();

        if (isset($experienceTable[$enemy->getLevel()])) {
            $experience = $experienceTable[$enemy->getLevel()];
        } else {
            $experience = 0;
        }

        if ($levelDifference < 0) {
            $modifier = -0.25*$levelDifference;
        } else {
            $modifier = 0.1*$levelDifference;
        }

        $experience = (int)round($experience*$modifier);
        if ($experience <= 0) {
            $experience = 1;
        }

        $character->setProperty(
            self::CharacterPropertyCurrentExperience,
            $character->getProperty(self::CharacterPropertyCurrentExperience, 0) + $experience
        );

        return $experience;
    }

    /**
     * Levels up the given character and sets the new experience he needs for the next level.
     *
     * This method does not check if the experience requirement is fulfilled.
     * @param Character $c
     */
    public static function characterLevelUp(Character $c): void
    {
        if ($c->getLevel() < 15) {
            $c->setLevel($c->getLevel() + 1);
            $c->setProperty(self::CharacterPropertyNeededExperience, self::getNeededExperienceByLevel($c->getLevel()));
        }
    }

    /**
     * Helper method to return the needed base experience by a given level.
     * @param int $level
     * @return int
     */
    public static function getNeededExperienceByLevel(int $level): int
    {
        // @ToDo: Add hook for additional scaling.
        $experienceArray = [
            1 => 100,
            2 => 400,
            3 => 1002,
            4 => 1912,
            5 => 3140,
            6 => 4707,
            7 => 6641,
            8 => 8985,
            9 => 11795,
            10 => 15143,
            11 => 19121,
            12 => 23840,
            13 => 29437,
            14 => 36071,
            15 => 43930
        ];

        if ($level > count($experienceArray)) {
            $level = count($experienceArray);
        } elseif ($level < min(array_keys($experienceArray))) {
            $level = min(array_keys($experienceArray));
        }

        return $experienceArray[$level] ?: 0;
    }

    /**
     * Returns true if the character has enough experience for a level up.
     * @param Character $c
     * @return bool
     */
    public static function characterHasNeededExperience(Character $c): bool
    {
        $currentExp = $c->getProperty(self::CharacterPropertyCurrentExperience);
        $neededExp = $c->getProperty(self::CharacterPropertyNeededExperience, self::getNeededExperienceByLevel($c->getLevel()));
        if ($currentExp >= $neededExp) {
            return true;
        } else {
            return false;
        }
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
