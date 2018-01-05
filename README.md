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
### Helper Methods
- `Module::characterEarnExperience(Character $character, BasicEnemy $enemy): int` \
Can be used to easily reward a Character experience based his and the enemies level.

- `characterLevelUp(Character $c): void` \
Can be used to level the character up in a standardized fashion.

- `getNeededExperienceByLevel(int $level): int` \
Returns the amount of experience required for a specific level. For any character, the character
property should be used instead.

- `characterHasNeededExperience(Character $c): bool` \
Returns true of the user has at least as much experience as he must have to increase his level.

### Character Properties
- `lotgd/module-res-fight/battleState` (`Module::CharacterPropertyBattleState`)
Internal use only.

- `lotgd/module-res-fight/turns` (`Module::CharacterPropertyTurns`)
Number of turns left; Gets regenerated upon a new day.

- `lotgd/module-res-fight/experience` (`Module::CharacterPropertyCurrentExperience`)
Amount of experience the character currently has.

- `lotgd/module-res-fight/experienceForLevelUp` (`Module::CharacterPropertyRequiredExperience`)
Amount of experience the character currently needs to challenge his master.