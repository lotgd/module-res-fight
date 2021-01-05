<?php
declare(strict_types=1);

namespace LotGD\Module\Res\Fight;

use LotGD\Core\Action;
use LotGD\Core\ActionGroup;
use LotGD\Core\Battle;
use LotGD\Core\Game;
use LotGD\Core\Models\FighterInterface;
use LotGD\Core\Models\Scene;
use LotGD\Module\Res\Fight\Module as FightModule;
use LotGD\Module\Res\Fight\Events\EventFightActionsData;
use LotGD\Module\Res\Fight\Events\EventFightActionChosenData;
use LotGD\Module\Res\Fight\SceneTemplates\BattleScene;

class Fight
{
    const ActionGroupFight = FightModule::ModuleIdentifier . "/fight";
    const ActionGroupFlee = FightModule::ModuleIdentifier . "/flee";

    const ActionGroupDetails = [
        self::ActionGroupFight => []
    ];

    const ActionParameterField = FightModule::ModuleIdentifier . "/inFight";
    const ActionParameterAttack = FightModule::ModuleIdentifier . "/attack";
    const ActionParameterFlee = FightModule::ModuleIdentifier . "/flee";

    /** @var Game */
    private $game;
    /** @var Battle */
    private $battle;
    /** @var array */
    private $data;

    private function __construct()
    {
    }

    /**
     * Creates a fight instance and initializes a battle.
     * @param Game $g
     * @param FighterInterface $enemy
     * @param Scene $refererScene The Scene that starts the fight
     * @param string $battle_identifier A identifier (eg, scene template) to identify which fight we are referring to.
     * @return Fight
     */
    public static function start(Game $g, FighterInterface $enemy, Scene $refererScene, string $battle_identifier): self
    {
        $fight = new Fight();
        $fight->game = $g;
        $fight->battle = new Battle($g, $g->getCharacter(), $enemy);
        $fight->data = [
            "sceneId" => $refererScene->getId(),
            "identifier" => $battle_identifier
        ];

        return $fight;
    }

    /**
     * Returns the battle instance
     * @return Battle
     */
    public function getBattle(): Battle
    {
        return $this->battle;
    }

    /**
     * Returns the id of the scene that started the fight.
     * @return int
     */
    public function getReferrerSceneId(): string
    {
        return $this->data["sceneId"];
    }

    /**
     * Returns the identifier assigned to this fight.
     * @return string
     */
    public function getBattleIdentifier(): string
    {
        return $this->data["identifier"];
    }

    /**
     * Used to display actions during a fight.
     *
     * This method replaces the current viewpoint's actions with the ones providing the means to attack. It also offers
     * a hook for modules to extend the fight actions.
     * @return null
     */
    public function showFightActions()
    {
        if ($this->battle->isOver()) {
            return null;
        }

        $scene = $this->game->getEntityManager()
            ->getRepository(Scene::class)
            ->findOneBy(["template" => BattleScene::class]);
        $sceneId = $scene->getId();

        $parameterField = self::ActionParameterField;
        $createActionCallback = function($title, $parameterValue) use ($sceneId, $parameterField) {
            return new Action($sceneId, $title, [$parameterField => $parameterValue]);
        };

        /** @var array<ActionGroup> */
        $groups = [
            new ActionGroup(self::ActionGroupFight, "Fight", 0),
            new ActionGroup(self::ActionGroupFlee, "Flee", 100)
        ];

        $groups[0]->setActions([
            $createActionCallback("Attack", self::ActionParameterAttack),
        ]);

        /*$groups[1]->setActions([
            new Action($sceneId, "Try to Escape", [self::ActionParameterField => self::ActionParameterFlee])
        ]);*/

        // Event to modify action groups. Must put battle and scene into event context. Character is in global context.
        $hookData = $this->game->getEventManager()->publish(
            FightModule::HookSelectAction,
            New EventFightActionsData([
                "groups" => $groups,
                "battle" => $this->battle,
                "referrerSceneId" => $this->getReferrerSceneId(),
                "battleIdentifier" => $this->getBattleIdentifier(),
                "createActionCallback" => $createActionCallback
            ])
        );

        // Set groups to the modified groups. Maybe we can put in some logs later.
        $groups_changed = $hookData->get("groups");
        $groups = $groups_changed;

        // Set groups to viewpoint. This will overwrite anything else.
        $this->game->getViewpoint()->setActionGroups($groups);
    }

    /**
     * Suspends a fight by saving it the character's property storage.
     */
    public function suspend()
    {
        $this->game->getCharacter()
            ->setProperty(FightModule::CharacterPropertyBattleState, [
                "data" => $this->data,
                "battle" => $this->battle->serialize()
            ]);
    }

    /**
     * Clears after a fight the character property
     */
    public function clear()
    {
        $this->game->getCharacter()
            ->setProperty(FightModule::CharacterPropertyBattleState, null);
    }

    /**
     * Restores a fight from a character's property storage.
     * @param Game $game
     * @return Fight
     */
    public static function restore(Game $game): self
    {
        $data = $game->getCharacter()
            ->getProperty(FightModule::CharacterPropertyBattleState);

        $fight = new Fight();
        $fight->game = $game;
        $fight->battle = Battle::unserialize($game, $game->getCharacter(), $data["battle"]);
        $fight->data = $data["data"];

        return $fight;
    }

    /**
     * Processes the selected
     * @param array $parameters
     */
    public function process(array $parameters)
    {
        $v = $this->game->getViewpoint();
        $v->clearDescription();

        // Event to modify action groups. Must put battle and scene into event context. Character is in global context.
        $hookData = $this->game->getEventManager()->publish(
            FightModule::HookActionChosen,
            new EventFightActionChosenData([
                "viewpoint" => $v,
                "actionParameter" => $parameters[self::ActionParameterField],
                "battle" => $this->battle,
                "referrerSceneId" => $this->getReferrerSceneId(),
                "battleIdentifier" => $this->getBattleIdentifier(),
                "blockNormalFightProcessing" => false,
            ])
        );

        if (isset($parameters[self::ActionParameterField]) and $hookData->get("blockNormalFightProcessing") !== true) {
            switch ($parameters[self::ActionParameterField]) {
                default:
                case self::ActionParameterAttack:
                    $this->battle->fightNRounds(1);
                    break;
            }
        }

        $v->addDescriptionParagraph(sprintf("You are fighting against %s (level %s) who has %s hitpoints left.",
            $this->battle->getMonster()->getDisplayName(),
            $this->battle->getMonster()->getLevel(),
            $this->battle->getMonster()->getHealth()
        ));

        $events = $this->battle->getEvents();
        foreach ($events as $event) {
            $v->addDescriptionParagraph($event->decorate($this->game));
        }

        $v->addDescriptionParagraph(sprintf("%s (level %s) now has %s hitpoints left.",
            $this->battle->getMonster()->getDisplayName(),
            $this->battle->getMonster()->getLevel(),
            $this->battle->getMonster()->getHealth()
        ));
    }

    /**
     * Returns true if the battle is over
     * @return bool
     */
    public function isOver()
    {
        return $this->battle->isOver();
    }
}