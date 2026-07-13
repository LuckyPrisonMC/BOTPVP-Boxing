# Bot PvP Boxing

A PocketMine-MP plugin (API `5.44.3`) that lets players box against an AI
bot — first to land **100 hits** wins, styled after the Zeqa Network "Bot
PvP" minigame.

## Installation

1. Copy the `BotPvPBoxing` folder into your server's `plugins/` directory
   (folder-based plugins are loaded the same as `.phar` files).
2. Restart or reload the server.

## Setting up an arena (admin, `botpvp.admin`)

1. `/botpvp create <name>` — creates an arena in your current world.
2. Stand where the bot should stand and run `/botpvp setbotspawn <name>`.
3. Stand where the player should stand and run `/botpvp setplayerspawn <name>`.
4. (Optional) `/botpvp setspeed <name> <ticks>` — ticks between each bot hit
   (20 ticks = 1 second). Lower = faster/harder bot.
5. (Optional) `/botpvp setrange <name> <blocks>` — how close the bot needs to
   be to land a hit.
6. (Optional) `/botpvp setskin <name> <onlinePlayer>` — gives the bot that
   player's current skin instead of the plain default one.
7. Once, anywhere on the server: `/botpvp setlobby` — sets where players are
   teleported back to once a match ends.

An arena only becomes joinable once both spawn points are set.

## Player commands

- `/botpvp join [arena]` — joins a free arena (or a specific one by name).
  A 3-second countdown runs before the bot starts fighting.
- `/botpvp leave` — leaves the current match early.
- `/botpvp list` / `/botpvp info <arena>` — view arenas and their settings.

## How a match works

- Each arena only ever hosts **one player at a time** — if the arena you
  request is occupied, `/botpvp join` automatically places you in a
  different free arena instead.
- After the 3-second countdown, the bot walks up to the player and swings
  within its configured range/speed. Every landed hit (either side) shows up
  instantly on the action bar: `Your Hits: X / 100   Bot Hits: Y / 100`.
- Hits never cause real damage or death — this is a pure hit-count contest.
- The first side to reach 100 hits wins immediately; a title announces the
  result and the player is teleported straight back to the lobby spawn.

## Permissions

| Permission       | Default | Grants                                   |
|-------------------|---------|-------------------------------------------|
| `botpvp.command`  | true    | `/botpvp join`, `/botpvp leave`, `/botpvp list`, `/botpvp info` |
| `botpvp.admin`    | op      | Arena setup commands                     |

## Notes

- Written against the PocketMine-MP `5.44.3` API. If you run a different
  5.x build, most of the plugin should still work, but re-test after
  updating since entity/skin APIs occasionally shift between releases.
- The bot is implemented as a non-persistent `Human` entity — it is never
  written to the world save and is respawned fresh each time a player joins.
