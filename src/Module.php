<?php
declare(strict_types=1);

namespace LotGD\Module\Res\Fight;

use Doctrine\Common\Util\Debug;
use LotGD\Core\Game;
use LotGD\Core\Events\EventContext;
use LotGD\Core\Module as ModuleInterface;
use LotGD\Core\Models\Module as ModuleModel;
use LotGD\Module\NewDay\Module as NewDayModule;

class Module implements ModuleInterface {
    const ModuleIdentifier = "lotgd/module-res-fight";

    const CharacterPropertyBattleState = self::ModuleIdentifier . "/battleState";
    const CharacterPropertyTurns = self::ModuleIdentifier . "/turns";

    public static function handleEvent(Game $g, EventContext $context): EventContext
    {
        $event = $context->getEvent();

        if ($event === NewDayModule::HookAfterNewDay) {
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
        }

        return $context;
    }
    
    public static function onRegister(Game $g, ModuleModel $module) { }
    public static function onUnregister(Game $g, ModuleModel $module) { }
}
