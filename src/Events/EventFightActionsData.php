<?php
declare(strict_types=1);

namespace LotGD\Module\Res\Fight\Events;

use LotGD\Core\Battle;
use LotGD\Core\Events\EventContextData;
use LotGD\Core\Exceptions\ArgumentException;

/**
 * Class EventFightActionsData
 *
 * EventContextData for HookSelectAction
 * @package LotGD\Module\Res\Fight
 */
class EventFightActionsData extends EventContextData
{
    public function __construct(array $data)
    {
        if (!isset($data["groups"])) {
            throw new ArgumentException("Array field 'groups' is required.");
        }

        if (!isset($data["battle"])) {
            throw new ArgumentException("Array field 'battle' is required.");
        } elseif ($data["battle"] instanceof Battle === false) {
            throw new ArgumentException("Array field 'battle' must be an instance of LotGD\\Core\\Battle.");
        }

        if (!isset($data["referrerSceneId"])) {
            throw new ArgumentException("Array field 'referrerSceneId' is required.");
        }

        if (!isset($data["battleIdentifier"])) {
            throw new ArgumentException("Array field 'battleIdentifier' is required.");
        } elseif (is_string($data["battleIdentifier"]) === false) {
            throw new ArgumentException("Array field 'battleIdentifier' must be a string.");
        }

        if (!isset($data["createActionCallback"])) {
            throw new ArgumentException("Array field 'createActionCallback' is required.");
        } elseif (is_callable($data["createActionCallback"]) === false) {
            throw new ArgumentException("Array field 'createActionCallback' must be a string.");
        }

        parent::__construct($data);
    }
}