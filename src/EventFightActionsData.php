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

        if (!isset($data["scene"])) {
            throw new ArgumentException("Array field 'scene' is required.");
        } elseif ($data["scene"] instanceof Scene === false) {
            throw new ArgumentException("Array field 'scene' must be an instance of LotGD\\Core\\Models\\Scene.");
        }

        parent::__construct($data);
    }
}