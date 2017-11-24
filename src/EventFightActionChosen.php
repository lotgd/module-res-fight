<?php
declare(strict_types=1);

namespace LotGD\Module\Res\Fight;


use LotGD\Core\Battle;
use LotGD\Core\Events\EventContextData;
use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Models\Viewpoint;

class EventFightActionChosen extends EventContextData
{
    public function __construct(array $data)
    {
        if (!isset($data["viewpoint"])) {
            throw new ArgumentException("Array field 'viewpoint' is required.");
        } elseif ($data["viewpoint"] instanceof Viewpoint === false) {
            throw new ArgumentException("Array field 'viewpoint' must be an instance of LotGD\\Core\\Model\\Viewpoint.");
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

        if (!isset($data["actionParameter"])) {
            throw new ArgumentException("Array field 'actionParameter' is required.");
        } elseif (is_string($data["actionParameter"]) === false) {
            throw new ArgumentException("Array field 'actionParameter' must be a string.");
        }

        if (!isset($data["blockNormalFightProcessing"])) {
            throw new ArgumentException("Array field 'blockNormalFightProcessing' is required.");
        } elseif (is_bool($data["blockNormalFightProcessing"]) === false) {
            throw new ArgumentException("Array field 'blockNormalFightProcessing' must be a boolean.");
        }

        parent::__construct($data);
    }
}