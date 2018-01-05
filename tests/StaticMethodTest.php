<?php
declare(strict_types=1);

namespace LotGD\Module\Res\Fight\Tests;

use LotGD\Core\Models\BasicEnemy;
use LotGD\Core\Models\Character;
use LotGD\Core\Tools\Model\AutoScaleFighter;
use LotGD\Module\Res\Fight\Models\CharacterResFightExtension;
use LotGD\Module\Res\Fight\Module as ResFightModule;

class StaticMethodTest extends ModuleTestCase
{
    const Library = 'lotgd/module-res-fight';

    protected function getDataSet(): \PHPUnit_Extensions_Database_DataSet_YamlDataSet
    {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(implode(DIRECTORY_SEPARATOR, [__DIR__, 'datasets', 'module.yml']));
    }

    protected function getCharacterMock(int $level): Character
    {
        $character = new class($level) extends Character {
            public $_level = 0;
            public $_properties = [];
            public function __construct($level) {$this->_level = $level;}
            public function getLevel(): int {return $this->_level;}
            public function setLevel(int $level): void {$this->_level = $level;}
            public function setProperty(string $name, $value): void { $this->_properties[$name] = $value; }
            public function getProperty(string $name, $default = null)
            {
                if (isset($this->_properties[$name])) {
                    return $this->_properties[$name];
                } else {
                    return $default;
                }
            }
            public function getMaxHealth(): int
            {
                if (!isset($this->_maxHealth)) {
                    $this->_maxHealth = $this->_level * 10;
                }
                return $this->_maxHealth;
            }
            public function setMaxHealth(int $maxHealth){$this->_maxHealth = $maxHealth;}
            public function getHealth(): int
            {
                if (!isset($this->_health)) {
                    $this->_health = $this->_maxHealth;
                }
                return $this->_health;
            }
            public function setHealth(int $health) {$this->_health = $health;}
        };

        return $character;
    }

    protected function getMonsterMock(int $level): BasicEnemy
    {
        $enemy = new class($level) extends BasicEnemy {
            use AutoScaleFighter;
            public $_level = 0;
            public function __construct($level) {$this->_level = $level;}
            public function getLevel(): int {return $this->_level;}
            public function setLevel(int $level): void {$this->_level = $level;}
        };

        return $enemy;
    }

    public function testCharacterEarnsExperience()
    {
        for ($i = 1; $i < 16; $i++) {
            for ($j = 1; $j < 18; $j++) {
                $character = $this->getCharacterMock($i);
                $monster = $this->getMonsterMock($j);

                $character->setProperty(ResFightModule::CharacterPropertyCurrentExperience, 0);
                CharacterResFightExtension::rewardExperienceToCharacter($character, 100);

                $this->assertGreaterThan(0, $character->getProperty(ResFightModule::CharacterPropertyCurrentExperience),
                    sprintf("Assertion greater than failed with character level %s and monster level %s", $i, $j)
                );

                $this->assertSame(100, $character->getProperty(ResFightModule::CharacterPropertyCurrentExperience));

                unset($character, $monster);
            }
        }
    }

    public function testCharacterLevelUp()
    {
        for ($i = 1; $i < 15; $i++) {
            $character = $this->getCharacterMock($i);
            $maxHealthBeforeLevelUp = $character->getMaxHealth();
            $healthBeforeLevelUp = $character->getHealth();
            CharacterResFightExtension::levelUpCharacter($character, $this->g);
            $this->assertSame($i+1, $character->getLevel());
            $this->assertGreaterThan(0, $character->getProperty(ResFightModule::CharacterPropertyRequiredExperience, 0));
            $this->assertGreaterThan($maxHealthBeforeLevelUp, $character->getMaxHealth());
            $this->assertGreaterThan($healthBeforeLevelUp, $character->getHealth());
            $this->assertSame($character->getHealth(), $character->getMaxHealth());
        }

        // Level 15 characters are not allowed to level up.
        $character = $this->getCharacterMock(15);
        CharacterResFightExtension::levelUpCharacter($character, $this->g);
        $this->assertSame(15, $character->getLevel());
    }

    public function testGetNeededExperienceByLevel()
    {
        for ($i = 1; $i <= 15; $i++) {
            $character = $this->getCharacterMock($i);
            $this->assertGreaterThan(0, CharacterResFightExtension::calculateNeededExperienceForCharacter($character, $this->g));
        }
    }

    public function testCharacterHasNeededExperience()
    {
        for ($i = 1; $i <=  15; $i++) {
            $character = $this->getCharacterMock($i);
            $neededExperience = CharacterResFightExtension::calculateNeededExperienceForCharacter($character, $this->g);
            $character = $this->getCharacterMock($i);

            // Not enough
            $character->setProperty(ResFightModule::CharacterPropertyCurrentExperience, 0);
            $this->assertFalse(CharacterResFightExtension::characterHasRequiredExperience($character, $this->g));

            // Just not enough
            $character->setProperty(ResFightModule::CharacterPropertyCurrentExperience, $neededExperience-1);
            $this->assertFalse(CharacterResFightExtension::characterHasRequiredExperience($character, $this->g));

            // Barely enough
            $character->setProperty(ResFightModule::CharacterPropertyCurrentExperience, $neededExperience);
            $this->assertTrue(CharacterResFightExtension::characterHasRequiredExperience($character, $this->g));

            // More than enough
            $character->setProperty(ResFightModule::CharacterPropertyCurrentExperience, $neededExperience*2);
            $this->assertTrue(CharacterResFightExtension::characterHasRequiredExperience($character, $this->g));
        }
    }
}