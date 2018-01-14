# module-res-fight
[![Build Status](https://travis-ci.org/lotgd/module-res-fight.svg?branch=master)](https://travis-ci.org/lotgd/module-res-fight)

This module provides helpers for other modules to:
1. Initiate a fight
2. Let character earn experience
3. Let characters level up.
4. Let characters regenerate their number of turns upon a new day.

Note that this module does not provide any fights on it's own.

## API
### Events
- `h/lotgd/module-res-fight/battleOver` (`Module::HookBattleOver`)\
This event happens if a fight has been finished. If offers the battle instance, the current viewpoint, the 
id of the scene starting the battle and a battle identifier. The latter can be used to distinguish between 
different battle initiations (master fight vs forest, for example).

- `h/lotgd/module-res-fight/fightSelectAction` (`Module::HookSelectAction`)\
This event can be used to modify the actions during a battle. For example, the fight navigation can be 
extended to support the use of skills or health potions.

- `h/lotgd/module-res-fight/fightActionChosen` (`Module::HookActionChosen`)\
This event must be used together with ..fightSelectAction. While the first can be used to extend the 
scope of actions, this must be used afterwards to create the effect of the action.

- `h/lotgd/module-res-fight/characterLevelUp` (`Module::EventCharacterLevelUp`))\
This event is published when a character is leveled up with the character bound levelUp method.

### Character Model Extension Methods
- `levelUp()`\
Levels up the character and publishes the `Module::EventCharacterLevelUp` event.

- `calculateNeededExperience(): int`\
Calculates how much experience the user needs for the next level. This method is used internally for
pure calculations. Please refer to the getRequiredExperience method for public access.

- `rewardExperience(int $experience)`\
Can be used to easily reward a Character experience a set amount of experience.

- `multiplyExperience(float $factor)`\
Resets the characters current experience by applying a factor. Can be used, for example, to let a character
loose 10% of his experience.

- `hasRequiredExperience(): bool`\
Returns true if the current experience >= required experience.

- `getRequiredExperience(): int`\
Returns the currently required experience.

- `setRequiredExperience(int $requiredExperience)`\
Returns the currently required experience.

- `getCurrentExperience(): int`\
Returns the currently required experience.

- `setCurrentExperience(int $currentExperience)`\
Returns the currently required experience.

- `incrementTurns(int $numberOfTurns)`\
Adds the given number of turns to the character.

- `getTurns(): int`\
Returns the current number of turns left.

- `setTurns(int $numberOfTurns)`\
Sets the current number of turns left.

### Character Properties
- `lotgd/module-res-fight/battleState` (`Module::CharacterPropertyBattleState`)
Internal use only.

- `lotgd/module-res-fight/turns` (`Module::CharacterPropertyTurns`)
Number of turns left; Gets regenerated upon a new day.

- `lotgd/module-res-fight/experience` (`Module::CharacterPropertyCurrentExperience`)
Amount of experience the character currently has.

- `lotgd/module-res-fight/experienceForLevelUp` (`Module::CharacterPropertyRequiredExperience`)
Amount of experience the character currently needs to challenge his master.