<?php
declare(strict_types=1);

namespace LotGD\Module\Res\Fight\Models;


use LotGD\Core\Events\CharacterEventData;
use LotGD\Core\Game;
use LotGD\Core\Models\Character;

use LotGD\Module\Res\Fight\Module as ResFightModule;
use LotGD\Core\Doctrine\Annotations\Extension;
use LotGD\Core\Doctrine\Annotations\ExtensionMethod;

/**
 * API extension helpers for the character model
 * @package LotGD\Module\Res\Fight\Models
 * @Extension(of="LotGD\Core\Models\Character")
 */
class CharacterResFightExtension
{
    const BaseExperienceArray = [
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

    /**
     * Levels up a given character.
     *
     * This method levels up characters with a level < 15. It increases their max health by 10 points and heals them.
     * It also adjusts the needed experience and throws an event.
     * @param Character $character
     * @ExtensionMethod(as="levelUp")
     */
    public static function levelUpCharacter(Character $character): void
    {
        if ($character->getLevel() < 15) {
            // Increment level by 1
            $character->setLevel($character->getLevel() + 1);

            // Increase health by +10 and heal.
            $character->setMaxHealth($character->getMaxHealth() + 10);
            $character->setHealth($character->getMaxHealth());

            // Adjust needed experience
            $character->setProperty(
                ResFightModule::CharacterPropertyRequiredExperience,
                    self::calculateNeededExperienceForCharacter($character)
            );

            // Call event
            $character->getGame()->getEventManager()->publish(
                ResFightModule::EventCharacterLevelUp,
                CharacterEventData::create(["character" => $character, "value" => null])
            );
        }
    }

    /**
     * Returns the amount of experience needed for a character
     * @param Character $character
     * @return int
     * @ExtensionMethod(as="calculateNeededExperience")
     */
    public static function calculateNeededExperienceForCharacter(Character $character): int
    {
        // @ToDo: Add hook for additional scaling.
        $level = $character->getLevel();

        if ($level > count(self::BaseExperienceArray)) {
            $level = count(self::BaseExperienceArray);
        } elseif ($level < min(array_keys(self::BaseExperienceArray))) {
            $level = min(array_keys(self::BaseExperienceArray));
        }

        return self::BaseExperienceArray[$level] ?: 0;
    }

    /**
     * Increments the current experience of a character by a given amount.
     * @param Character $character
     * @param int $experience
     * @ExtensionMethod(as="rewardExperience")
     */
    public static function rewardExperienceToCharacter(Character $character, int $experience): void
    {
        $character->setProperty(
            ResFightModule::CharacterPropertyCurrentExperience,
            $character->getProperty(ResFightModule::CharacterPropertyCurrentExperience, 0) + $experience
        );
    }

    /**
     * Adjusts the experience of a character by a given factor.
     * @param Character $character
     * @param float $factor
     * @ExtensionMethod(as="multiplyExperience")
     */
    public static function modifyRelativeExperienceFromCharacter(Character $character, float $factor): void
    {
        $character->setProperty(
            ResFightModule::CharacterPropertyCurrentExperience,
            (int)floor($character->getProperty(ResFightModule::CharacterPropertyCurrentExperience, 0) * $factor)
        );
    }

    /**
     * Returns true if the character has enough experience for a level up.
     * @param Character $c
     * @return bool
     * @ExtensionMethod(as="hasRequiredExperience")
     */
    public static function characterHasRequiredExperience(Character $c): bool
    {
        $currentExp = self::getCurrentExperienceForCharacter($c);
        $requiredExp = self::getRequiredExperienceForCharacter($c);

        if ($currentExp >= $requiredExp) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the required experience for a given character.
     * @param Character $c
     * @return int
     * @ExtensionMethod(as="getRequiredExperience")
     */
    public static function getRequiredExperienceForCharacter(Character $c): int
    {
        return $c->getProperty(ResFightModule::CharacterPropertyRequiredExperience, self::calculateNeededExperienceForCharacter($c));
    }

    /**
     * Sets the required for a given character.
     * @param Character $c
     * @param int $experience
     * @ExtensionMethod(as="setRequiredExperience")
     */
    public static function setRequiredExperienceForCharacter(Character $c, int $experience): void
    {
        $c->setProperty(ResFightModule::CharacterPropertyRequiredExperience, $experience);
    }

    /**
     * Returns the current experience for a given character.
     * @param Character $c
     * @return int
     * @ExtensionMethod(as="getCurrentExperience")
     */
    public static function getCurrentExperienceForCharacter(Character $c): int
    {
        return $c->getProperty(ResFightModule::CharacterPropertyCurrentExperience, 0);
    }

    /**
     * Sets the current experience for a given character.
     * @param Character $c
     * @param int $experience
     * @ExtensionMethod(as="setCurrentExperience")
     */
    public static function setCurrentExperienceForCharacter(Character $c, int $experience): void
    {
        $c->setProperty(ResFightModule::CharacterPropertyCurrentExperience, $experience);
    }

    /**
     * Increments the turns a character has by a given amount.
     * @param Character $c
     * @param int $increment
     * @ExtensionMethod(as="incrementTurns")
     */
    public static function incrementTurnsForCharacter(Character $c, int $increment): void
    {
        $c->setProperty(
            ResFightModule::CharacterPropertyTurns,
            $c->getProperty(ResFightModule::CharacterPropertyTurns, 30) + $increment
        );
    }

    /**
     * Returns the number of turns a character has left.
     * @param Character $c
     * @return int
     * @ExtensionMethod(as="getTurns")
     */
    public static function getTurnsLeftForCharacter(Character $c): int
    {
        return $c->getProperty(ResFightModule::CharacterPropertyTurns, 30);
    }

    /**
     * @param Character $c
     * @param int $turns
     * @ExtensionMethod(as="setTurns")
     */
    public static function setTurnsLeftForCharacter(Character $c, int $turns): void
    {
        $c->setProperty(ResFightModule::CharacterPropertyTurns, $turns);
    }
}