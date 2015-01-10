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
use pocketmine\utils\TextFormat;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
class Main extends PluginBase  implements Listener {
	
	
    public function onEnable(){
		@mkdir($this->getDataFolder("players"));
		@mkdir($this->getDataFolder());
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->config = new Config($this->getDataFolder() . "config.yml", CONFIG::YAML, array(
			"friend-system" => true,
			"players-with-same-perms-are-friendly" => true,
			"max-friends" => 5,
		));
		if(!$this->getServer()->getPluginManager()->getPlugin("PurePerms") == true) {
			$this->getLogger()->info( TextFormat::RED . "PurePerms Not Loaded With PvPFriend!" );
		}else{
			$this->pure = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
			$this->getLogger()->info( TextFormat::RED . "PurePerms Loaded With PvPFriend!" );
		}
		$this->getLogger()->info( TextFormat::RED . "PvPFriendly Loaded!" );
    }
	public function onEntityDamageByEntity(EntityDamageEvent $pf){
        $player = $pf->getEntity();
		if($this->config->get("friend-system")) {
        if($pf instanceof EntityDamageByEntityEvent){
            $sender = $pf->getDamager();
            if($player instanceof Player && $sender instanceof Player){
			if($this->players->exists($player, [$sender,])){
					$playerget = $pf->getPlayer()->getName();
					$sender->sendMessage("[PvPFriend] $playerget is your friend!");
                    $pf->setCancelled(true);
                }elseif(!$this->players->exists($player, [$sender,])){
                    $pf->setCancelled(false);
                }
			}
		}
	}else{
		if($this->config->get("players-with-same-perms-are-friendly")) {
		if($pf instanceof EntityDamageByEntityEvent){
            $sender = $pf->getDamager()->getPlayer()->getName();
			$player = $pf->getEntity()->getPlayer()->getName();
            if($player instanceof Player && $sender instanceof Player){
				$groupName = $this->pure->getUser($player)->getGroup($levelName)->getName();
				$groupName2 = $this->plugin->getUser($sender)->getGroup($levelName)->getName();
				if($groupName = $groupName2) {
					$player = $pf->getPlayer()->getName();
					$sender->sendMessage("[PvPFriend] You may not attack $player!");
                    $pf->setCancelled(true);
                }else{
                    $pf->setCancelled(false);
                }
			}
		}	
	}
	}
	}
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		$player = $sender->getName();
			if(strtolower($command->getName('pf'))) {
				if(empty($args)) {
					$sender->sendMessage("[PvPFriend] Usage:\n/pf addfriend <player-name>\n/pf delfriend <player-name>");
					return true;
				}
				if(count($args == 2)) {
					if($args[0] == "addfriend") {
						if(empty($args[1])) {
							$sender->sendMessage("[PvPFriend] Usage:\n/pf addfriend <player-name>");
							return true;
						}
						$asked = $this->getServer()->getPlayerExact($args[1]);
						if(!$asked instanceof Player) {
							$sender->sendMessage("[PvPFriend] Player not online!");
							return true;
						}
						$player = strtolower($args[1]);
						$issuer = strtolower($sender->getName());
						$this->getSenderFriends = $this->players = new Config($this->getDataFolder("players") . $issuer, CONFIG::YAML);
						$this->getPlayerFriends = $this->players = new Config($this->getDataFolder("players") . $player, CONFIG::YAML);
						if($this->getSenderFriends->get($player)) {
							$sender->sendMessage("[PvPFriend] Player is already your friend!");
							return true;
						}
						$key = $this->config->get("max-friends");
						if(count($this->getSenderFriends->getAll()) == $key) {
							$sender->sendMessage("[PvPFriend] You have reached the max friends!");
							return true;
						}
						$this->getSenderFriends->set($player, [$issuer,]);
						$this->players->set($issuer, [$player,]);
						$this->players->save();
						$sender->sendMessage("[PvPFriend] You added '$args[1]'.");
						$player->sendMessage("[PvPFriend] '$sender' has added you as a friend.\n To remove this player run /pf unfriend <player>\To ban this user from adding you,run /pf ban <player>");
						return true;
					}/*elseif($args[0] == "unfriend") {
						if(empty($args[1])) {
							$sender->sendMessage("[PvPFriendly] Usage:\n/cl remove <player-name>");
							return true;
						}
						$player = (strtolower($args[1]));
						if(!$this->playersToLog->exists($player)) {
							$sender->sendMessage("[PvPFriendly] Player not being logged!");
							return true;
						}
						$this->playersToLog->remove($player);
						$this->playersToLog->save();
						$sender->sendMessage("[PvPFriendly] Player '$args[1]' has been removed from being logged.");
						return true;
					}else{
						$sender->sendMessage("[PvPFriendly] Usage:\n/cl add <player-name>\n/cl remove <player-name>");
						return true;
					}
				}else{
					$sender->sendMessage("[PvPFriendly] Usage:\n/cl add <player-name>\n/cl remove <player-name>");
					return true;
				}
			}else{
				return true;
			}*/
	}
}
}

	/*public function onPlayerCommand(PlayerCommandPreprocessEvent $pf){
		$player = $pf->getPlayer()->getName();
		if($this->players->get((strtolower($player)))) {
			$this->player = new Config($this->getDataFolder() . (strtolower($player)) .".yml", CONFIG::YAML);
		    if(substr($message = $pf->getMessage(), 0, 1) === "/") {
				$this->player;
				$this->player->set($message, null); 
				$this->player->save();
				return true;
			}else{
				return true;
			}
		}else{
			return true;
		}
	}*/
}
