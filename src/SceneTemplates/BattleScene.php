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
        return Module::SceneGenderChoose;
    }

    public static function getScaffold(): Scene
    {
        $battleScene = Scene::create([
            "template" => new SceneTemplate(self::class, "lotgd/module-res-fight"),
            "title" => "A fight!",
            "description" => "You are fighting."
        ]);

        $battleScene->getTemplate()->setUserAssignable(false);

        return $battleScene;
    }
}