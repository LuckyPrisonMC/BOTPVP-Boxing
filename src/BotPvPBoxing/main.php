<?php

declare(strict_types=1);

namespace BotPvPBoxing;

use BotPvPBoxing\arena\ArenaManager;
use BotPvPBoxing\command\BotPvPCommand;
use BotPvPBoxing\listener\PlayerListener;
use BotPvPBoxing\task\ArenaTickTask;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\Position;

class Main extends PluginBase {

    private ArenaManager $arenaManager;
    private Config $lobbyConfig;

    protected function onEnable() : void {
        @mkdir($this->getDataFolder());

        $this->arenaManager = new ArenaManager($this);
        $this->lobbyConfig = new Config($this->getDataFolder() . "lobby.yml", Config::YAML, []);

        $this->getServer()->getCommandMap()->register("botpvp", new BotPvPCommand($this));
        $this->getServer()->getPluginManager()->registerEvents(new PlayerListener($this), $this);

        // Drives every arena's countdown, bot AI and win checks - once per tick.
        $this->getScheduler()->scheduleRepeatingTask(new ArenaTickTask($this), 1);
    }

    protected function onDisable() : void {
        if(isset($this->arenaManager)){
            $this->arenaManager->save();
        }
    }

    public function getArenaManager() : ArenaManager {
        return $this->arenaManager;
    }

    public function setLobbySpawn(Position $pos, float $yaw, float $pitch) : void {
        $this->lobbyConfig->setAll([
            "world" => $pos->getWorld()->getFolderName(),
            "x" => $pos->x,
            "y" => $pos->y,
            "z" => $pos->z,
            "yaw" => $yaw,
            "pitch" => $pitch,
        ]);
        $this->lobbyConfig->save();
    }

    public function getLobbySpawn() : ?Position {
        $data = $this->lobbyConfig->getAll();
        if(!isset($data["world"], $data["x"], $data["y"], $data["z"])){
            return null;
        }
        $world = Server::getInstance()->getWorldManager()->getWorldByName((string) $data["world"]);
        if($world === null){
            Server::getInstance()->getWorldManager()->loadWorld((string) $data["world"]);
            $world = Server::getInstance()->getWorldManager()->getWorldByName((string) $data["world"]);
        }
        if($world === null){
            return null;
        }
        return new Position((float) $data["x"], (float) $data["y"], (float) $data["z"], $world);
    }

    /**
     * Teleports a player back to the configured lobby spawn, falling back to
     * their world's default spawn if no lobby has been set yet.
     */
    public function teleportToLobby(Player $player) : void {
        $lobby = $this->getLobbySpawn();
        if($lobby !== null){
            $data = $this->lobbyConfig->getAll();
            $player->teleport($lobby, (float) ($data["yaw"] ?? 0.0), (float) ($data["pitch"] ?? 0.0));
            return;
        }
        $world = $player->getWorld();
        $player->teleport(Position::fromObject($world->getSpawnLocation(), $world));
    }
}
