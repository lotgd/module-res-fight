<?php
declare(strict_types=1);

namespace LotGD\Module\Res\Fight\Tests;

use LotGD\Core\Models\BasicEnemy;
use LotGD\Core\Models\Character;
use LotGD\Core\Tools\Model\AutoScaleFighter;
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
            public function setProperty(string $name, $value) { $this->_properties[$name] = $value; }
            public function getProperty(string $name, $default = null)
            {
                if (isset($this->_properties[$name])) {
                    return $this->_properties[$name];
                } else {
                    return $default;
                }
            }
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
                $experienceEarned = ResFightModule::characterEarnExperience($character, $monster);

                $this->assertGreaterThan(0, $character->getProperty(ResFightModule::CharacterPropertyCurrentExperience),
                    sprintf("Assertion greater than failed with character level %s and monster level %s", $i, $j)
                );

                $this->assertSame($experienceEarned, $character->getProperty(ResFightModule::CharacterPropertyCurrentExperience));

                unset($character, $monster);
            }
        }
    }

    public function testCharacterLevelUp()
    {
        for ($i = 1; $i < 15; $i++) {
            $character = $this->getCharacterMock($i);
            ResFightModule::characterLevelUp($character);
            $this->assertSame($i+1, $character->getLevel());
            $this->assertGreaterThan(0, $character->getProperty(ResFightModule::CharacterPropertyNeededExperience, 0));
        }

        // Level 15 characters are not allowed to level up.
        $character = $this->getCharacterMock(15);
        ResFightModule::characterLevelUp($character);
        $this->assertSame(15, $character->getLevel());
    }

    public function testGetNeededExperienceByLevel()
    {
        for ($i = 1; $i <= 15; $i++) {
            $this->assertGreaterThan(0, ResFightModule::getNeededExperienceByLevel($i));
        }
    }

    public function testCharacterHasNeededExperience()
    {
        for ($i = 1; $i <=  15; $i++) {
            $neededExperience = ResFightModule::getNeededExperienceByLevel($i);
            $character = $this->getCharacterMock($i);

            // Not enough
            $character->setProperty(ResFightModule::CharacterPropertyCurrentExperience, 0);
            $this->assertFalse(ResFightModule::characterHasNeededExperience($character));

            // Just not enough
            $character->setProperty(ResFightModule::CharacterPropertyCurrentExperience, $neededExperience-1);
            $this->assertFalse(ResFightModule::characterHasNeededExperience($character));

            // Barely enough
            $character->setProperty(ResFightModule::CharacterPropertyCurrentExperience, $neededExperience);
            $this->assertTrue(ResFightModule::characterHasNeededExperience($character));

            // More than enough
            $character->setProperty(ResFightModule::CharacterPropertyCurrentExperience, $neededExperience*2);
            $this->assertTrue(ResFightModule::characterHasNeededExperience($character));
        }
    }
}