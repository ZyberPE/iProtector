<?php

declare(strict_types=1);

namespace LDX\iProtector;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\player\Player;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerBucketEvent;

use pocketmine\event\entity\EntityDamageEvent;

use pocketmine\math\Vector3;
use pocketmine\world\Position;

use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener{

	public array $areas = [];

	private array $selectingFirst = [];
	private array $selectingSecond = [];

	private array $firstPosition = [];
	private array $secondPosition = [];

	public function onEnable() : void{

		$this->getServer()->getPluginManager()->registerEvents($this,$this);

		@mkdir($this->getDataFolder());

		if(!file_exists($this->getDataFolder()."areas.json")){
			file_put_contents($this->getDataFolder()."areas.json","[]");
		}

		$data = json_decode(file_get_contents($this->getDataFolder()."areas.json"),true);

		foreach($data as $datum){

			new Area(
				$datum["name"],
				$datum["flags"],
				new Vector3(...$datum["pos1"]),
				new Vector3(...$datum["pos2"]),
				$datum["level"],
				$datum["whitelist"],
				$this
			);
		}
	}

	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool{

		if(!$sender instanceof Player){
			$sender->sendMessage("Run this in-game.");
			return true;
		}

		if(!isset($args[0])){
			return false;
		}

		$player = strtolower($sender->getName());

		switch(strtolower($args[0])){

			case "pos1":

				$this->selectingFirst[$player] = true;

				$sender->sendMessage(TextFormat::GREEN."Break or place block for pos1");
			break;

			case "pos2":

				$this->selectingSecond[$player] = true;

				$sender->sendMessage(TextFormat::GREEN."Break or place block for pos2");
			break;

			case "create":

				if(!isset($args[1])){
					$sender->sendMessage("Usage: /area create <name>");
					return true;
				}

				if(!isset($this->firstPosition[$player],$this->secondPosition[$player])){
					$sender->sendMessage("Select both positions first.");
					return true;
				}

				$name = strtolower($args[1]);

				if(isset($this->areas[$name])){
					$sender->sendMessage("Area already exists.");
					return true;
				}

				new Area(
					$name,
					["edit"=>true,"god"=>false,"touch"=>true],
					$this->firstPosition[$player],
					$this->secondPosition[$player],
					$sender->getWorld()->getFolderName(),
					[$player],
					$this
				);

				$this->saveAreas();

				unset($this->firstPosition[$player],$this->secondPosition[$player]);

				$sender->sendMessage(TextFormat::AQUA."Area created.");
			break;

			case "list":

				foreach($this->areas as $area){
					$sender->sendMessage("- ".$area->getName());
				}

			break;

			case "delete":

				if(!isset($args[1])){
					return true;
				}

				$name = strtolower($args[1]);

				if(isset($this->areas[$name])){
					$this->areas[$name]->delete();
					$sender->sendMessage("Area deleted.");
				}

			break;
		}

		return true;
	}

	public function saveAreas() : void{

		$data = [];

		foreach($this->areas as $area){

			$data[] = [
				"name"=>$area->getName(),
				"flags"=>$area->getFlags(),
				"pos1"=>[
					$area->getFirstPosition()->getX(),
					$area->getFirstPosition()->getY(),
					$area->getFirstPosition()->getZ()
				],
				"pos2"=>[
					$area->getSecondPosition()->getX(),
					$area->getSecondPosition()->getY(),
					$area->getSecondPosition()->getZ()
				],
				"level"=>$area->getWorldName(),
				"whitelist"=>$area->getWhitelist()
			];
		}

		file_put_contents($this->getDataFolder()."areas.json",json_encode($data,JSON_PRETTY_PRINT));
	}

	public function canEdit(Player $player, Position $pos) : bool{

		if($player->hasPermission("iprotector.access")){
			return true;
		}

		foreach($this->areas as $area){

			if($area->contains($pos,$pos->getWorld()->getFolderName())){

				if($area->isWhitelisted(strtolower($player->getName()))){
					return true;
				}

				if(!$area->getFlag("edit")){
					return true;
				}

				return false;
			}
		}

		return true;
	}

	public function onPlace(BlockPlaceEvent $event) : void{

		$player = $event->getPlayer();
		$name = strtolower($player->getName());

		foreach($event->getTransaction()->getBlocks() as [$x,$y,$z,$block]){

			$pos = new Vector3($x,$y,$z);

			if(isset($this->selectingFirst[$name])){
				unset($this->selectingFirst[$name]);

				$this->firstPosition[$name] = $pos;

				$player->sendMessage("Pos1 set");

				$event->cancel();
				return;
			}

			if(isset($this->selectingSecond[$name])){
				unset($this->selectingSecond[$name]);

				$this->secondPosition[$name] = $pos;

				$player->sendMessage("Pos2 set");

				$event->cancel();
				return;
			}

			if(!$this->canEdit($player,new Position($x,$y,$z,$player->getWorld()))){
				$event->cancel();
			}
		}
	}

	public function onBreak(BlockBreakEvent $event) : void{

		if(!$this->canEdit($event->getPlayer(),$event->getBlock()->getPosition())){
			$event->cancel();
		}
	}

	public function onBucket(PlayerBucketEvent $event) : void{

		if(!$this->canEdit($event->getPlayer(),$event->getBlockClicked()->getPosition())){
			$event->cancel();
		}
	}

	public function onDamage(EntityDamageEvent $event) : void{

		$entity = $event->getEntity();

		if(!$entity instanceof Player){
			return;
		}

		foreach($this->areas as $area){

			if($area->contains($entity->getPosition(),$entity->getWorld()->getFolderName())){

				if($area->getFlag("god")){
					$event->cancel();
				}
			}
		}
	}
}
