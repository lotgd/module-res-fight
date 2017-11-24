<?php
declare(strict_types=1);

namespace LotGD\Module\Res\Fight;

use LotGD\Core\Battle;
use LotGD\Core\Events\EventContextData;
use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Models\Scene;

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