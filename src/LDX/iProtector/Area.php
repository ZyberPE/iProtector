<?php

declare(strict_types=1);

namespace LDX\iProtector;

use pocketmine\math\Vector3;

class Area{

	private string $name;
	private array $flags;

	private Vector3 $pos1;
	private Vector3 $pos2;

	private string $world;

	private array $whitelist;

	private Main $plugin;

	public function __construct(string $name,array $flags,Vector3 $pos1,Vector3 $pos2,string $world,array $whitelist,Main $plugin){

		$this->name=$name;
		$this->flags=$flags;

		$this->pos1=$pos1;
		$this->pos2=$pos2;

		$this->world=$world;

		$this->whitelist=$whitelist;

		$this->plugin=$plugin;

		$this->save();
	}

	public function getName():string{
		return $this->name;
	}

	public function getFirstPosition():Vector3{
		return $this->pos1;
	}

	public function getSecondPosition():Vector3{
		return $this->pos2;
	}

	public function getFlags():array{
		return $this->flags;
	}

	public function getFlag(string $flag):bool{
		return $this->flags[$flag] ?? false;
	}

	public function setFlag(string $flag,bool $value):void{
		$this->flags[$flag]=$value;
		$this->plugin->saveAreas();
	}

	public function getWorldName():string{
		return $this->world;
	}

	public function getWhitelist():array{
		return $this->whitelist;
	}

	public function setWhitelisted(string $name,bool $value):void{

		if($value){
			if(!in_array($name,$this->whitelist,true)){
				$this->whitelist[]=$name;
			}
		}else{
			$key=array_search($name,$this->whitelist,true);
			if($key!==false){
				unset($this->whitelist[$key]);
			}
		}

		$this->plugin->saveAreas();
	}

	public function isWhitelisted(string $name):bool{
		return in_array($name,$this->whitelist,true);
	}

	public function contains(Vector3 $pos,string $world):bool{

		return
		$this->world === $world &&

		min($this->pos1->getX(),$this->pos2->getX()) <= $pos->getX() &&
		max($this->pos1->getX(),$this->pos2->getX()) >= $pos->getX() &&

		min($this->pos1->getY(),$this->pos2->getY()) <= $pos->getY() &&
		max($this->pos1->getY(),$this->pos2->getY()) >= $pos->getY() &&

		min($this->pos1->getZ(),$this->pos2->getZ()) <= $pos->getZ() &&
		max($this->pos1->getZ(),$this->pos2->getZ()) >= $pos->getZ();
	}

	public function delete():void{
		unset($this->plugin->areas[$this->name]);
		$this->plugin->saveAreas();
	}

	public function save():void{
		$this->plugin->areas[$this->name]=$this;
	}
}
