<?php
declare(strict_types=1);

namespace LotGD\Module\Res\Fight\SceneTemplates;

use LotGD\Core\Models\Scene;
use LotGD\Core\Models\SceneTemplate;
use LotGD\Core\SceneTemplates\SceneTemplateInterface;
use LotGD\Module\Res\Fight\Module;

class BattleScene implements SceneTemplateInterface
{
    public static function getNavigationEvent(): string
    {
        return Module::SceneBattle;
    }

    public static function getScaffold(): Scene
    {
        $battleScene = new Scene(
            title: "A fight!",
            description: "You are fighting.",
            template: new SceneTemplate(self::class, "lotgd/module-res-fight"),
        );

        $battleScene->getTemplate()->setUserAssignable(false);

        return $battleScene;
    }
}