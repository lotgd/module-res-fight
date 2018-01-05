<?php
declare(strict_types=1);

namespace LotGD\Module\Res\Fight\Events;

use LotGD\Core\Battle;
use LotGD\Core\Events\EventContextData;
use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Models\Viewpoint;

/**
 * Class EventBattleOverData
 *
 * Contains data if the fight event is over.
 * @package LotGD\Module\Res\Fight
 */
class EventBattleOverData extends EventContextData
{
    public function __construct(array $data)
    {
        if (!isset($data["battle"])) {
            throw new ArgumentException("Array field 'battle' is required.");
        } elseif ($data["battle"] instanceof Battle === false) {
            throw new ArgumentException("Array field 'battle' must be an instance of LotGD\\Core\\Battle.");
        }

        if (!isset($data["viewpoint"])) {
            throw new ArgumentException("Array field 'viewpoint' is required.");
        } elseif ($data["viewpoint"] instanceof Viewpoint === false) {
            throw new ArgumentException("Array field 'viewpoint' must be an instance of LotGD\\Core\\Models\\Viewpoint.");
        }

        if (!isset($data["referrerSceneId"])) {
            throw new ArgumentException("Array field 'referrerSceneId' is required.");
        } elseif (is_int($data["referrerSceneId"]) === false) {
            throw new ArgumentException("Array field 'referrerSceneId' must be an integer (and a valid scene id).");
        }

        if (!isset($data["battleIdentifier"])) {
            throw new ArgumentException("Array field 'battleIdentifier' is required.");
        } elseif (is_string($data["battleIdentifier"]) === false) {
            throw new ArgumentException("Array field 'battleIdentifier' must be a string.");
        }

        parent::__construct($data);
    }
}