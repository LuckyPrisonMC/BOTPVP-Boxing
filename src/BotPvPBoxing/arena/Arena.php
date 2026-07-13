<?php

declare(strict_types=1);

namespace BotPvPBoxing\arena;

use BotPvPBoxing\entity\BoxingBot;
use BotPvPBoxing\Main;
use BotPvPBoxing\utils\Messages;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\animation\HurtAnimation;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\sound\XpLevelUpSound;
use pocketmine\world\World;

class Arena {

    /** Hits required to win the match. */
    public const WIN_TARGET = 100;

    /** Length of the pre-fight countdown, in seconds. */
    public const COUNTDOWN_SECONDS = 3;

    /** Horizontal walking speed of the bot while chasing the player, in blocks/tick. */
    private const BOT_WALK_SPEED = 0.11;

    private string $name;
    private string $worldName;

    private ?Vector3 $botSpawn = null;
    private float $botSpawnYaw = 0.0;

    private ?Vector3 $playerSpawn = null;
    private float $playerSpawnYaw = 0.0;

    /** Ticks between each bot hit - lower is faster/harder. Configurable per arena. */
    private int $hitIntervalTicks = 20;

    /** Distance (in blocks) at which the bot is able to land a hit. Configurable per arena. */
    private float $hitRange = 2.5;

    private ?Skin $botSkin = null;
    private ?string $botSkinOwnerName = null;

    private ArenaState $state = ArenaState::WAITING;

    private ?Player $currentPlayer = null;
    private ?BoxingBot $bot = null;

    private int $playerHits = 0;
    private int $botHits = 0;

    private int $countdownTicksLeft = 0;
    private int $attackCooldown = 0;

    public function __construct(string $name, string $worldName) {
        $this->name = $name;
        $this->worldName = $worldName;
    }

    public function getName() : string {
        return $this->name;
    }

    public function getWorldName() : string {
        return $this->worldName;
    }

    public function getWorld() : ?World {
        $manager = Server::getInstance()->getWorldManager();
        $world = $manager->getWorldByName($this->worldName);
        if($world === null){
            $manager->loadWorld($this->worldName);
            $world = $manager->getWorldByName($this->worldName);
        }
        return $world;
    }

    public function setBotSpawn(Vector3 $pos, float $yaw) : void {
        $this->botSpawn = $pos->asVector3();
        $this->botSpawnYaw = $yaw;
    }

    public function setPlayerSpawn(Vector3 $pos, float $yaw) : void {
        $this->playerSpawn = $pos->asVector3();
        $this->playerSpawnYaw = $yaw;
    }

    public function getBotSpawn() : ?Vector3 {
        return $this->botSpawn;
    }

    public function getPlayerSpawn() : ?Vector3 {
        return $this->playerSpawn;
    }

    public function isFullyConfigured() : bool {
        return $this->botSpawn !== null && $this->playerSpawn !== null;
    }

    public function isFree() : bool {
        return $this->state === ArenaState::WAITING && $this->currentPlayer === null;
    }

    public function getState() : ArenaState {
        return $this->state;
    }

    public function getCurrentPlayer() : ?Player {
        return $this->currentPlayer;
    }

    public function getHitIntervalTicks() : int {
        return $this->hitIntervalTicks;
    }

    public function setHitIntervalTicks(int $ticks) : void {
        $this->hitIntervalTicks = max(1, $ticks);
    }

    public function getHitRange() : float {
        return $this->hitRange;
    }

    public function setHitRange(float $range) : void {
        $this->hitRange = max(0.5, $range);
    }

    public function setBotSkin(Skin $skin, string $ownerName) : void {
        $this->botSkin = $skin;
        $this->botSkinOwnerName = $ownerName;
    }

    public function getBotSkinOwnerName() : ?string {
        return $this->botSkinOwnerName;
    }

    private function resolveSkin() : Skin {
        return $this->botSkin ?? BoxingBot::makeDefaultSkin();
    }

    /**
     * Attempts to put a player into this arena. Returns true on success.
     */
    public function join(Player $player) : bool {
        if(!$this->isFree() || !$this->isFullyConfigured()){
            return false;
        }
        $world = $this->getWorld();
        if($world === null || $this->botSpawn === null || $this->playerSpawn === null){
            return false;
        }

        $this->currentPlayer = $player;
        $this->playerHits = 0;
        $this->botHits = 0;
        $this->attackCooldown = $this->hitIntervalTicks;
        $this->countdownTicksLeft = self::COUNTDOWN_SECONDS * 20;
        $this->state = ArenaState::COUNTDOWN;

        $player->teleport(new Position($this->playerSpawn->x, $this->playerSpawn->y, $this->playerSpawn->z, $world), $this->playerSpawnYaw, 0.0);
        $player->getHungerManager()->setFood(20.0);
        $player->setHealth($player->getMaxHealth());

        $this->spawnBot($world);

        return true;
    }

    private function spawnBot(World $world) : void {
        $this->removeBot();
        if($this->botSpawn === null){
            return;
        }
        $location = new Location($this->botSpawn->x, $this->botSpawn->y, $this->botSpawn->z, $world, $this->botSpawnYaw, 0.0);
        $bot = new BoxingBot($location, $this->resolveSkin());
        $bot->setNameTag(Messages::prefix() . "Boxing Bot");
        $bot->setNameTagAlwaysVisible(true);
        $bot->arena = $this;
        $bot->spawnToAll();
        $this->bot = $bot;
    }

    private function removeBot() : void {
        if($this->bot !== null && !$this->bot->isClosed()){
            $this->bot->flagForDespawn();
        }
        $this->bot = null;
    }

    public function getBot() : ?BoxingBot {
        return $this->bot;
    }

    /**
     * Removes the player from the arena, optionally sending them back to the
     * configured lobby spawn. Safe to call even if no player is present.
     */
    public function reset(Main $plugin, bool $teleportAway = true) : void {
        $player = $this->currentPlayer;
        $this->removeBot();
        $this->currentPlayer = null;
        $this->playerHits = 0;
        $this->botHits = 0;
        $this->countdownTicksLeft = 0;
        $this->attackCooldown = 0;
        $this->state = ArenaState::WAITING;

        if($player !== null && $player->isOnline() && $teleportAway){
            $plugin->teleportToLobby($player);
        }
    }

    /**
     * Called every tick by ArenaTickTask. Drives the countdown, the bot AI
     * and the win condition.
     */
    public function tick(Main $plugin) : void {
        switch($this->state){
            case ArenaState::WAITING:
            case ArenaState::ENDING:
                return;
            case ArenaState::COUNTDOWN:
                $this->tickCountdown($plugin);
                return;
            case ArenaState::FIGHTING:
                $this->tickFight($plugin);
                return;
        }
    }

    private function tickCountdown(Main $plugin) : void {
        $player = $this->currentPlayer;
        if($player === null || !$player->isOnline()){
            $this->reset($plugin, false);
            return;
        }

        if($this->countdownTicksLeft % 20 === 0){
            $secondsLeft = intdiv($this->countdownTicksLeft, 20);
            if($secondsLeft > 0){
                $player->sendTitle(Messages::countdownTitle($secondsLeft), "", 0, 25, 5);
            }else{
                $player->sendTitle(Messages::fightTitle(), "", 0, 20, 10);
                $this->state = ArenaState::FIGHTING;
                $this->attackCooldown = $this->hitIntervalTicks;
            }
        }
        $this->countdownTicksLeft--;
    }

    private function tickFight(Main $plugin) : void {
        $player = $this->currentPlayer;
        $bot = $this->bot;
        $world = $this->getWorld();

        if($player === null || !$player->isOnline() || $bot === null || $bot->isClosed() || $world === null){
            $this->reset($plugin, false);
            return;
        }

        $botPos = $bot->getPosition();
        $playerPos = $player->getPosition();

        $dx = $playerPos->x - $botPos->x;
        $dz = $playerPos->z - $botPos->z;
        $distance = sqrt($dx * $dx + $dz * $dz);

        // Face the player.
        if($distance > 0.05){
            $yaw = atan2(-$dx, $dz) * (180 / M_PI);
            $bot->setRotation($yaw, 0.0);
        }

        // Chase the player if outside attacking range, otherwise hold position.
        if($distance > max(1.2, $this->hitRange * 0.75)){
            $nx = $dx / $distance;
            $nz = $dz / $distance;
            $motion = $bot->getMotion();
            $bot->setMotion(new Vector3($nx * self::BOT_WALK_SPEED, $motion->y, $nz * self::BOT_WALK_SPEED));
        }else{
            $motion = $bot->getMotion();
            $bot->setMotion(new Vector3(0.0, $motion->y, 0.0));
        }

        // Handle the bot's attack timer.
        if($this->attackCooldown > 0){
            $this->attackCooldown--;
        }elseif($distance <= $this->hitRange){
            $this->performBotAttack($player, $bot);
        }

        // Update the on-screen hit counter.
        $player->sendActionBarMessage(Messages::actionBar($this->playerHits, $this->botHits, self::WIN_TARGET));

        if($this->playerHits >= self::WIN_TARGET){
            $this->endMatch($plugin, true);
        }elseif($this->botHits >= self::WIN_TARGET){
            $this->endMatch($plugin, false);
        }
    }

    private function performBotAttack(Player $player, BoxingBot $bot) : void {
        $this->attackCooldown = $this->hitIntervalTicks;
        $this->botHits++;

        $bot->broadcastAnimation(new ArmSwingAnimation($bot));

        $dx = $player->getPosition()->x - $bot->getPosition()->x;
        $dz = $player->getPosition()->z - $bot->getPosition()->z;
        $player->knockBack($dx, $dz, 0.35);
        $player->broadcastAnimation(new HurtAnimation($player));
    }

    /**
     * Called by the damage listener whenever the player lands a legitimate
     * hit on the bot.
     */
    public function registerPlayerHit(BoxingBot $bot) : void {
        $this->playerHits++;
        $bot->broadcastAnimation(new HurtAnimation($bot));
    }

    public function endMatch(Main $plugin, bool $playerWon) : void {
        $player = $this->currentPlayer;
        $this->state = ArenaState::ENDING;

        if($player !== null && $player->isOnline()){
            if($playerWon){
                $player->sendTitle(Messages::victoryTitle(), Messages::victorySubtitle($this->playerHits, $this->botHits), 5, 40, 10);
                $player->broadcastSound(new XpLevelUpSound(1));
            }else{
                $player->sendTitle(Messages::defeatTitle(), Messages::defeatSubtitle($this->playerHits, $this->botHits), 5, 40, 10);
                $player->broadcastAnimation(new HurtAnimation($player));
            }
        }

        // "Immediately" teleport the player back to the spawn/lobby as requested.
        $this->reset($plugin, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray() : array {
        return [
            "world" => $this->worldName,
            "botSpawn" => $this->botSpawn === null ? null : ["x" => $this->botSpawn->x, "y" => $this->botSpawn->y, "z" => $this->botSpawn->z, "yaw" => $this->botSpawnYaw],
            "playerSpawn" => $this->playerSpawn === null ? null : ["x" => $this->playerSpawn->x, "y" => $this->playerSpawn->y, "z" => $this->playerSpawn->z, "yaw" => $this->playerSpawnYaw],
            "hitIntervalTicks" => $this->hitIntervalTicks,
            "hitRange" => $this->hitRange,
            "botSkin" => $this->botSkin === null ? null : [
                "skinId" => $this->botSkin->getSkinId(),
                "skinData" => base64_encode($this->botSkin->getSkinData()),
                "capeData" => base64_encode($this->botSkin->getCapeData()),
                "geometryName" => $this->botSkin->getGeometryName(),
                "geometryData" => base64_encode($this->botSkin->getGeometryData()),
                "ownerName" => $this->botSkinOwnerName,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(string $name, array $data) : self {
        $arena = new self($name, (string) ($data["world"] ?? "world"));

        if(isset($data["botSpawn"]) && is_array($data["botSpawn"])){
            $b = $data["botSpawn"];
            $arena->setBotSpawn(new Vector3((float) $b["x"], (float) $b["y"], (float) $b["z"]), (float) ($b["yaw"] ?? 0.0));
        }
        if(isset($data["playerSpawn"]) && is_array($data["playerSpawn"])){
            $p = $data["playerSpawn"];
            $arena->setPlayerSpawn(new Vector3((float) $p["x"], (float) $p["y"], (float) $p["z"]), (float) ($p["yaw"] ?? 0.0));
        }
        $arena->hitIntervalTicks = (int) ($data["hitIntervalTicks"] ?? 20);
        $arena->hitRange = (float) ($data["hitRange"] ?? 2.5);

        if(isset($data["botSkin"]) && is_array($data["botSkin"])){
            $s = $data["botSkin"];
            try{
                $skin = new Skin(
                    (string) $s["skinId"],
                    base64_decode((string) $s["skinData"], true) ?: "",
                    base64_decode((string) ($s["capeData"] ?? ""), true) ?: "",
                    (string) ($s["geometryName"] ?? ""),
                    base64_decode((string) ($s["geometryData"] ?? ""), true) ?: ""
                );
                $arena->setBotSkin($skin, (string) ($s["ownerName"] ?? "Unknown"));
            }catch(\Throwable $e){
                // Corrupt/invalid stored skin - fall back to the default skin silently.
            }
        }

        return $arena;
    }
}
