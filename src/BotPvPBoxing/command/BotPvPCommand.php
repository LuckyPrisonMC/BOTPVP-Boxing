<?php

declare(strict_types=1);

namespace BotPvPBoxing\command;

use BotPvPBoxing\Main;
use BotPvPBoxing\utils\Messages;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;

class BotPvPCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("botpvp", "Main command for the Bot PvP Boxing minigame", "/botpvp <join|leave|create|...>", ["boxing", "bpvp"]);
        $this->setPermission("botpvp.command");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
        if(!$this->testPermission($sender)){
            return false;
        }

        $sub = strtolower(array_shift($args) ?? "help");

        switch($sub){
            case "join":
                return $this->handleJoin($sender, $args);
            case "leave":
                return $this->handleLeave($sender);
            case "create":
                return $this->handleCreate($sender, $args);
            case "remove":
            case "delete":
                return $this->handleRemove($sender, $args);
            case "list":
                return $this->handleList($sender);
            case "info":
                return $this->handleInfo($sender, $args);
            case "setbotspawn":
                return $this->handleSetBotSpawn($sender, $args);
            case "setplayerspawn":
                return $this->handleSetPlayerSpawn($sender, $args);
            case "setspeed":
                return $this->handleSetSpeed($sender, $args);
            case "setrange":
                return $this->handleSetRange($sender, $args);
            case "setskin":
                return $this->handleSetSkin($sender, $args);
            case "setlobby":
                return $this->handleSetLobby($sender);
            case "help":
            default:
                return $this->sendHelp($sender);
        }
    }

    private function sendHelp(CommandSender $sender) : bool {
        $lines = [
            TF::BOLD . TF::GOLD . "--- Bot PvP Boxing Help ---",
            TF::YELLOW . "/botpvp join [arena]" . TF::GRAY . " - Join a boxing match against a bot.",
            TF::YELLOW . "/botpvp leave" . TF::GRAY . " - Leave your current match.",
            TF::YELLOW . "/botpvp list" . TF::GRAY . " - List every arena.",
            TF::YELLOW . "/botpvp info <arena>" . TF::GRAY . " - View an arena's settings.",
        ];
        if($sender->hasPermission("botpvp.admin")){
            $lines[] = TF::RED . "/botpvp create <arena>" . TF::GRAY . " - Create a new arena in your current world.";
            $lines[] = TF::RED . "/botpvp remove <arena>" . TF::GRAY . " - Delete an arena.";
            $lines[] = TF::RED . "/botpvp setbotspawn <arena>" . TF::GRAY . " - Set the bot's spawn to your position.";
            $lines[] = TF::RED . "/botpvp setplayerspawn <arena>" . TF::GRAY . " - Set the player's spawn to your position.";
            $lines[] = TF::RED . "/botpvp setspeed <arena> <ticks>" . TF::GRAY . " - Ticks between each bot hit (20 = 1s).";
            $lines[] = TF::RED . "/botpvp setrange <arena> <blocks>" . TF::GRAY . " - Distance the bot can hit from.";
            $lines[] = TF::RED . "/botpvp setskin <arena> <player>" . TF::GRAY . " - Give the bot a real player's skin.";
            $lines[] = TF::RED . "/botpvp setlobby" . TF::GRAY . " - Set where players return to after a match.";
        }
        $sender->sendMessage(implode("\n", $lines));
        return true;
    }

    private function handleJoin(CommandSender $sender, array $args) : bool {
        if(!($sender instanceof Player)){
            $sender->sendMessage(Messages::playersOnly());
            return true;
        }

        $manager = $this->plugin->getArenaManager();

        if($manager->getArenaOf($sender) !== null){
            $sender->sendMessage(Messages::alreadyInArena());
            return true;
        }

        $arenaName = $args[0] ?? null;
        if($arenaName !== null){
            $arena = $manager->getArena($arenaName);
            if($arena === null){
                $sender->sendMessage(Messages::arenaNotFound($arenaName));
                return true;
            }
            if(!$arena->isFullyConfigured()){
                $sender->sendMessage(Messages::arenaIncomplete());
                return true;
            }
            if(!$arena->isFree()){
                $sender->sendMessage(Messages::noFreeArena());
                return true;
            }
        }else{
            $arena = $manager->findFreeArena();
            if($arena === null){
                $sender->sendMessage(Messages::noFreeArena());
                return true;
            }
        }

        if($arena->join($sender)){
            $sender->sendMessage(Messages::joinedArena($arena->getName()));
        }else{
            $sender->sendMessage(Messages::arenaIncomplete());
        }
        return true;
    }

    private function handleLeave(CommandSender $sender) : bool {
        if(!($sender instanceof Player)){
            $sender->sendMessage(Messages::playersOnly());
            return true;
        }
        $arena = $this->plugin->getArenaManager()->getArenaOf($sender);
        if($arena === null){
            $sender->sendMessage(Messages::notInArena());
            return true;
        }
        $arena->reset($this->plugin, true);
        $sender->sendMessage(Messages::leftArena());
        return true;
    }

    private function handleCreate(CommandSender $sender, array $args) : bool {
        if(!$sender->hasPermission("botpvp.admin")){
            $sender->sendMessage(Messages::noPermission());
            return true;
        }
        if(!($sender instanceof Player)){
            $sender->sendMessage(Messages::playersOnly());
            return true;
        }
        $name = $args[0] ?? null;
        if($name === null){
            $sender->sendMessage(Messages::usage("/botpvp create <arena>"));
            return true;
        }
        if($this->plugin->getArenaManager()->getArena($name) !== null){
            $sender->sendMessage(Messages::arenaAlreadyExists($name));
            return true;
        }
        $this->plugin->getArenaManager()->createArena($name, $sender->getWorld()->getFolderName());
        $sender->sendMessage(Messages::arenaCreated($name));
        return true;
    }

    private function handleRemove(CommandSender $sender, array $args) : bool {
        if(!$sender->hasPermission("botpvp.admin")){
            $sender->sendMessage(Messages::noPermission());
            return true;
        }
        $name = $args[0] ?? null;
        if($name === null){
            $sender->sendMessage(Messages::usage("/botpvp remove <arena>"));
            return true;
        }
        if(!$this->plugin->getArenaManager()->removeArena($name)){
            $sender->sendMessage(Messages::arenaNotFound($name));
            return true;
        }
        $sender->sendMessage(Messages::arenaRemoved($name));
        return true;
    }

    private function handleList(CommandSender $sender) : bool {
        $names = array_map(fn($a) => $a->getName(), $this->plugin->getArenaManager()->getAll());
        $sender->sendMessage(Messages::arenaList(array_values($names)));
        return true;
    }

    private function handleInfo(CommandSender $sender, array $args) : bool {
        $name = $args[0] ?? null;
        if($name === null){
            $sender->sendMessage(Messages::usage("/botpvp info <arena>"));
            return true;
        }
        $arena = $this->plugin->getArenaManager()->getArena($name);
        if($arena === null){
            $sender->sendMessage(Messages::arenaNotFound($name));
            return true;
        }
        $status = $arena->isFullyConfigured() ? ($arena->isFree() ? TF::GREEN . "Free" : TF::RED . "In use") : TF::YELLOW . "Incomplete";
        $sender->sendMessage(implode("\n", [
            Messages::prefix() . TF::GOLD . "Arena: " . TF::WHITE . $arena->getName(),
            TF::GRAY . " World: " . TF::WHITE . $arena->getWorldName(),
            TF::GRAY . " Status: " . $status,
            TF::GRAY . " Hit speed: " . TF::WHITE . $arena->getHitIntervalTicks() . " ticks between hits",
            TF::GRAY . " Hit range: " . TF::WHITE . $arena->getHitRange() . " blocks",
            TF::GRAY . " Bot skin: " . TF::WHITE . ($arena->getBotSkinOwnerName() ?? "Default"),
        ]));
        return true;
    }

    private function handleSetBotSpawn(CommandSender $sender, array $args) : bool {
        if(!$sender->hasPermission("botpvp.admin")){
            $sender->sendMessage(Messages::noPermission());
            return true;
        }
        if(!($sender instanceof Player)){
            $sender->sendMessage(Messages::playersOnly());
            return true;
        }
        $name = $args[0] ?? null;
        if($name === null){
            $sender->sendMessage(Messages::usage("/botpvp setbotspawn <arena>"));
            return true;
        }
        $arena = $this->plugin->getArenaManager()->getArena($name);
        if($arena === null){
            $sender->sendMessage(Messages::arenaNotFound($name));
            return true;
        }
        $pos = $sender->getPosition();
        $arena->setBotSpawn($pos->asVector3(), $sender->getLocation()->getYaw());
        $this->plugin->getArenaManager()->save();
        $sender->sendMessage(Messages::botSpawnSet($name));
        return true;
    }

    private function handleSetPlayerSpawn(CommandSender $sender, array $args) : bool {
        if(!$sender->hasPermission("botpvp.admin")){
            $sender->sendMessage(Messages::noPermission());
            return true;
        }
        if(!($sender instanceof Player)){
            $sender->sendMessage(Messages::playersOnly());
            return true;
        }
        $name = $args[0] ?? null;
        if($name === null){
            $sender->sendMessage(Messages::usage("/botpvp setplayerspawn <arena>"));
            return true;
        }
        $arena = $this->plugin->getArenaManager()->getArena($name);
        if($arena === null){
            $sender->sendMessage(Messages::arenaNotFound($name));
            return true;
        }
        $pos = $sender->getPosition();
        $arena->setPlayerSpawn($pos->asVector3(), $sender->getLocation()->getYaw());
        $this->plugin->getArenaManager()->save();
        $sender->sendMessage(Messages::playerSpawnSet($name));
        return true;
    }

    private function handleSetSpeed(CommandSender $sender, array $args) : bool {
        if(!$sender->hasPermission("botpvp.admin")){
            $sender->sendMessage(Messages::noPermission());
            return true;
        }
        $name = $args[0] ?? null;
        $ticksStr = $args[1] ?? null;
        if($name === null || $ticksStr === null || !is_numeric($ticksStr)){
            $sender->sendMessage(Messages::usage("/botpvp setspeed <arena> <ticksBetweenHits>"));
            return true;
        }
        $arena = $this->plugin->getArenaManager()->getArena($name);
        if($arena === null){
            $sender->sendMessage(Messages::arenaNotFound($name));
            return true;
        }
        $arena->setHitIntervalTicks((int) $ticksStr);
        $this->plugin->getArenaManager()->save();
        $sender->sendMessage(Messages::speedSet($name, $arena->getHitIntervalTicks()));
        return true;
    }

    private function handleSetRange(CommandSender $sender, array $args) : bool {
        if(!$sender->hasPermission("botpvp.admin")){
            $sender->sendMessage(Messages::noPermission());
            return true;
        }
        $name = $args[0] ?? null;
        $rangeStr = $args[1] ?? null;
        if($name === null || $rangeStr === null || !is_numeric($rangeStr)){
            $sender->sendMessage(Messages::usage("/botpvp setrange <arena> <blocks>"));
            return true;
        }
        $arena = $this->plugin->getArenaManager()->getArena($name);
        if($arena === null){
            $sender->sendMessage(Messages::arenaNotFound($name));
            return true;
        }
        $arena->setHitRange((float) $rangeStr);
        $this->plugin->getArenaManager()->save();
        $sender->sendMessage(Messages::rangeSet($name, $arena->getHitRange()));
        return true;
    }

    private function handleSetSkin(CommandSender $sender, array $args) : bool {
        if(!$sender->hasPermission("botpvp.admin")){
            $sender->sendMessage(Messages::noPermission());
            return true;
        }
        $name = $args[0] ?? null;
        $targetName = $args[1] ?? null;
        if($name === null || $targetName === null){
            $sender->sendMessage(Messages::usage("/botpvp setskin <arena> <onlinePlayer>"));
            return true;
        }
        $arena = $this->plugin->getArenaManager()->getArena($name);
        if($arena === null){
            $sender->sendMessage(Messages::arenaNotFound($name));
            return true;
        }
        $target = Server::getInstance()->getPlayerByPrefix($targetName);
        if($target === null){
            $sender->sendMessage(Messages::prefix() . TF::RED . "Player '" . $targetName . "' is not online.");
            return true;
        }
        $arena->setBotSkin($target->getSkin(), $target->getName());
        $this->plugin->getArenaManager()->save();
        $sender->sendMessage(Messages::skinSet($name, $target->getName()));
        return true;
    }

    private function handleSetLobby(CommandSender $sender) : bool {
        if(!$sender->hasPermission("botpvp.admin")){
            $sender->sendMessage(Messages::noPermission());
            return true;
        }
        if(!($sender instanceof Player)){
            $sender->sendMessage(Messages::playersOnly());
            return true;
        }
        $location = $sender->getLocation();
        $this->plugin->setLobbySpawn($sender->getPosition(), $location->getYaw(), $location->getPitch());
        $sender->sendMessage(Messages::lobbySet());
        return true;
    }
}
