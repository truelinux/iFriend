<?php
/**
 * author: LilCrispy2o9/Angelo Vidrio
 */
namespace PvPFriend;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\command\CommandExecutor;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\IPlayer;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginManager;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
class Main extends PluginBase  implements Listener {
	

    public function onEnable(){
		@mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder() . "Players/");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
		new Config($this->getDataFolder() . "config.yml", CONFIG::YAML, array(
			"players-in-same-group-are-friendly" => true,
			"friend-system" => true,
		));
		if(!$this->getServer()->getPluginManager()->getPlugin("PurePerms")) {
			$this->getLogger()->info( TextFormat::RED . "PurePerms Not Loaded With PvPFriend!" );
			$this->getServer()->getPluginManager()->disablePlugin($this);
		}else{
			$this->pure = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
			$this->getLogger()->info( TextFormat::GREEN . "PurePerms Loaded With PvPFriend!" );
		}
    }
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		if($sender instanceof Player) {
			$player = strtolower($sender->getName());
			$playercase = $sender->getPlayer()->getName();
				if(strtolower($command->getName()) == "friend") {
					if(empty($args)) {
						$sender->sendMessage("[PvPFriend] Usage:\n/friend <player-name>");
						return true;
					}
					if(strtolower($args[0]) !== "accept" && strtolower($args[0]) !== "decline") {
						$friend = strtolower($args[0]);
						$friendexact =  $this->getServer()->getPlayerExact($args[0]);
						if(!$friendexact instanceof Player) {
								$sender->sendMessage("[PvPFriend] Player not online!");
								return true;
						}
						if($this->getUser($player, $friend)) {
							$sender->sendMessage("[PvPFriend] '$friend' is already your friend!");
							return true;
						}
						if($this->getUserTEMP($player, $friend)) {
							$sender->sendMessage("[PvPFriend] You already sent an request to '$friend'");
							return true;
						}
						if($this->getConfig()->get("friend-system") && !file_exists($this->getDataFolder() . "Players/" . $player . ".yml")) {
							$this->pcreate = new Config($this->getDataFolder() . "Players/" . $player . ".yml", CONFIG::YAML);
							$place = "TempFriends";
							$this->pcreate->setNested($place . "." . $friend,[
            true,
        ]);
							$this->pcreate->save();
							$sender->sendMessage("[PvPFriend] '$friend' was asked to be\n your friend.");
							$friendexact->sendMessage("[PvPFriend] '$playercase' wants to be your friend!");
							return true;
						}
						if($this->getConfig()->get("friend-system") && file_exists($this->getDataFolder() . "Players/" . $player . ".yml")) {
							$this->pcreate = new Config($this->getDataFolder() . "Players/" . $player . ".yml", CONFIG::YAML);
							$place = "TempFriends";
							$this->pcreate->setNested($place . "." . $friend,[
            true,
        ]);
							$this->pcreate->save();
							$sender->sendMessage("[PvPFriend] '$friend' was asked to be your friend.");
							$friendexact->sendMessage("[PvPFriend] '$playercase' wants to be your friend!");
							return true;
						}
					}elseif(strtolower($args[0]) == "accept") {
						if(empty($args[1])) {
							$sender->sendMessage("[PvPFriend] Usage:\n/friend [decline/accept] <player-name>");
							return true;
						}
						$friendexact =  $this->getServer()->getPlayerExact($args[1]);
						$getsender = strtolower($args[1]);
						if($args[0] == "accept") {
							if(!$friendexact instanceof Player) {
								$sender->sendMessage("[PvPFriend] Player not online!");
								return true;
							}
							if(!$this->getUserTEMP($getsender, $player)) {
								$sender->sendMessage("[PvPFriend] Player has not sent you\n a request!");
								return true;
							}
							$playerget = strtolower($sender->getName());
							$this->removeUserTEMP($getsender, strtolower($sender->getName()));
							$this->setUser($getsender, $player);
							$this->setUser($player, $getsender);
							$sender->sendMessage("[PvPFriend] Request Accepted!");
							$friendexact->sendMessage("[PvPFriend] Your request to '$player'\nwas accepted!");
							return true;
						}
					}else{
						if($args[0] == "decline") {
							if(empty($args[1])) {
								$sender->sendMessage("[PvPFriend] Usage:\n/friend [decline/accept] <player-name>");
								return true;
							}
							$friendexact =  $this->getServer()->getPlayerExact($args[1]);
							$getsender = strtolower($args[1]);
							if(!$friendexact instanceof Player) {
								$sender->sendMessage("[PvPFriend] Player not online!");
								return true;
							}
							if(!$this->getUserTEMP($getsender, $player)) {
								$sender->sendMessage("[PvPFriend] Player has not sent you\n a request!");
								return true;
							}
							$this->removeUserTEMP($getsender, $player);
							$sender->sendMessage("[PvPFriend] Request Declined!");
							$friendexact->sendMessage("[PvPFriend] Your request to '$player'\n was declined!");
							return true;
						}
					}
				}
				if(strtolower($command->getName()) == "unfriend") {
					if(empty($args)) {
					$sender->sendMessage("[PvPFriend] Usage:\n/unfriend <player-name>");
					return true;
					}
					$friend = strtolower($args[0]);
					$friendexact =  $this->getServer()->getPlayerExact($args[0]);
					if(!$this->getUser($player, $friend)) {
						$sender->sendMessage("[PvPFriend] '$friend' is not your friend!");
						return true;
					}
					if($this->getUser($player, $friend)) {
						$this->removeUser($player, $friend);
						$this->removeUser($friend, $player);
						$sender->sendMessage("[PvPFriend] '$friend' is no longer your friend!");
					}
				}
		}
	}
	public function removeUser($player, $playerINF) {
		$this->pget = new Config($this->getDataFolder() . "Players/" . $player . ".yml", CONFIG::YAML);
		$this->pget->remove($playerINF);
		$this->pget->save();
		return true;
		}
	public function setUser($player, $playerINF) {
		$this->pget = new Config($this->getDataFolder() . "Players/" . $player . ".yml", CONFIG::YAML);
		$this->pget->set($playerINF);
		$this->pget->save();
		return true;
		}
	public function removeUserTEMP($player, $playerINF) {
		$this->pget = new Config($this->getDataFolder() . "Players/" . $player . ".yml", CONFIG::YAML);
		$this->pget->remove("TempFriends");
		$this->pget->save();
		return true;
	}
	public function removeLeaveTEMP($player) {
		$this->pget = new Config($this->getDataFolder() . "Players/" . $player . ".yml", CONFIG::YAML);
		$place = "TempFriends";
		$this->pget->remove($place);
		$this->pget->save();
		return true;
	}
	public function getUserTEMP($player, $playerINF) {
		$this->pget = new Config($this->getDataFolder() . "Players/" . $player . ".yml", CONFIG::YAML);
		$place = "TempFriends";
		$v = $this->pget->getNested($place . "." . $playerINF);
		if($v[0] == true) {
			return true;
		}else{
			return false;
		}
	}
	public function getUser($player, $playerINF) {
		$this->pget = new Config($this->getDataFolder() . "Players/" . $player . ".yml", CONFIG::YAML);
		$place = "Friends";
		$v = $this->pget->get($playerINF);
		if($v) {
			return true;
		}else{
			return false;
		}
	}
	public function getTEMP($player, $playerINF) {
		$this->pget = new Config($this->getDataFolder() . "Players/" . $player . ".yml", CONFIG::YAML);
		
		if($this->pget->get("$playerINF")) {
			return true;
		}else{
			return false;
		}
	}
	public function hasFriends($player) {
		if($this->getDataFolder() . "Players/" . $player . ".yml") {
			return true;
		}else{
			return false;
		}
	}
	public function onEntityDamageByEntityEvent(EntityDamageEvent $pf){
		$reciever = $pf->getEntity()->getPlayer();
		if($pf instanceof EntityDamageByEntityEvent) {
			$sender = $pf->getDamager()->getPlayer();
		}else{
			return true;
		}
		$levelName = null;
		$groupName = $this->pure->getUser($reciever)->getGroup($levelName)->getName();
		$groupName2 = $this->pure->getUser($sender)->getGroup($levelName)->getName();
		if($groupName == $groupName2 && $this->getConfig()->get("players-in-same-group-are-friendly")) {
			$pf->setCancelled(true);
		}
		$friend1 = strtolower($pf->getEntity()->getPlayer()->getName());
		$friend2 = strtolower($pf->getDamager()->getPlayer()->getName());
		if($this->getUser($friend1, $friend2)) {
			$pf->setCancelled(true);
		}
	}
	public function onPlayerQuitEvent(PlayerQuitEvent $pf){
		$player = strtolower($pf->getPlayer()->getName());
		if($this->hasFriends($player)) {
			$this->removeLeaveTEMP($player);
		}else{
			return true;
		}
	}
}



