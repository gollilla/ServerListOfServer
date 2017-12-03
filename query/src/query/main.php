<?php

namespace query;

use pocketmine\math\Vector3;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\InteractPacket;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\utils\Config;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\tile\Sign;

use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use pocketmine\item\Item;


class main extends PluginBase implements Listener{


        public $wid = [];


	public function onEnable(){

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		if(!file_exists($this->getDataFolder())){
			mkdir($this->getDataFolder(), 0744, true);
		}

		$this->data = [];

		$this->con = new Config($this->getDataFolder() ."config.yml", Config::YAML, 
			        [
                               "title"=>"§b----- CONSOLE ----\n",
                                "text"=>["x"=>0, "y"=>4, "z"=>0],
			        "button"=>["right"=>["x"=>0, "y"=>0, "z"=>0], "center"=>["x"=>0, "y"=>0, "z"=>0], "left"=>["x"=>0, "y"=>0, "z"=>0]],
                                "id"=>1,
                                "pare"=>["right"=>["x"=>0, "y"=>0, "z"=>0], "center"=>["x"=>0, "y"=>0, "z"=>0], "left"=>["x"=>0, "y"=>0, "z"=>0]]
			        ]);
		$this->sdata = new Config($this->getDataFolder() ."serverdata.yml", Config::YAML, []);
                $this->pdata = new Config($this->getDataFolder() ."players.yml", Config::YAML, []);
                
    }

        
    public function onJoin(\pocketmine\event\player\PlayerJoinEvent $ev){

    	    $player = $ev->getPlayer();
    	    $name = $player->getName();
    	    $this->id[$name] = Entity::$entityCount++;
            $this->now[$name] = 1;
            $data = $this->getData(1);
            $text = "§b ------ §a".$data["name"]." §b--------\n§e鯖主 : §6".$data["owner"]."\n\n§f".$data["info"];
            $this->sendText($player, $text);
            $this->setButtonText();
            $player->getInventory()->clearAll();
    }


    public function setData($name, $info, $owner, $ip, $port){

        $id = $this->con->get("id");
        $data = ["owner"=>$owner, "info"=>$info, "ip"=>$ip, "port"=>$port, "id"=>$id];
        $this->sdata->set($name, $data);
        $this->sdata->save();
        $this->con->set("id", $id+1);
        $this->con->save();
    }



    public function getServerData(){

        $text = $this->con->get("url")."?type=new";
        $array = json_decode($text);
        if(!$this->sdata->exists($array["name"])){
            $data = ["owner"=>$array["owner"], "info"=>$array["info"], "ip"=>$array["ip"], "port"=>$array["port"], "id"=>$this->con->get($id)];
            $this->con->set("id", $this->con->get("id")+1);
            $this->con->save();
            $this->sdata->set($array["name"], $data);
            $this->sdata->save(); 
        }
    }


    public function getData($id){

        foreach ($this->sdata->getAll(true) as $name) {

            if($this->sdata->get($name)["id"] == $id){

                $all = $this->sdata->get($name);

                return ["owner"=>$all["owner"], "name"=>$name, "info"=>$all["info"], "ip"=>$all["ip"], "port"=>$all["port"]];
            }
            # code...
        }
    }



    public function idExists($id){
        foreach ($this->sdata->getAll() as $name) {

            if($id == $name["id"]){
                return true;
            }            
        }

        return false;
    }
  



    public function onTouch(\pocketmine\event\player\PlayerInteractEvent $ev){


    	    $player = $ev->getPlayer();
    	    $name = $player->getName();
    	    $block = $ev->getBlock();
    	    if($button = $this->isButton($block)){

    	    	switch($button){

    	    		case "r":

    	    		        if($next = $this->getNearId("up", $this->now[$name])){
                                     $this->now[$name] = $next;
                                     $data = $this->getData($next);
                                     $text = "§b ------ §a".$data["name"]." §b--------\n§e鯖主 : §6".$data["owner"]."\n\n§f".$data["info"];
                                     $this->sendText($player, $text);
                                     if(isset($this->entry[$name])){
                                             $player->getInventory()->clearAll();
                                             unset($this->entry[$name]);
                                             $player->sendMessage("§a>§6選択を解除しました");
                                     }
                                 }else{
                                     $player->sendMessage("§cPage Not Found");
                                 }
    	    		break;

    	    		case "l":
                            
                            if($next = $this->getNearId("down", $this->now[$name])){
                                     $this->now[$name] = $next;
                                     $data = $this->getData($next);
                                     $text = "§b ------ §a".$data["name"]." §b--------\n§e鯖主 : §6".$data["owner"]."\n\n§f".$data["info"];
                                     $this->sendText($player, $text);
                                     if(isset($this->entry[$name])){
                                             $player->getInventory()->clearAll();
                                             unset($this->entry[$name]);
                                             $player->sendMessage("§a>§6選択を解除しました");
                                     }
                            }else{
                                $player->sendMessage("§cPage Not Found");
                            }
    	    		        
                    break;


                    case "c":
                         if($this->now[$name] !== 1){

                            $in = $player->getInventory();
                            $data = $this->getData($this->now[$name]);
                            $this->entry[$name] = ["ip"=>$data["ip"], "port"=>$data["port"]];
                            $items = [Item::get(35, 1, 1)->setCustomName("§aサーバーに入る"),
                                     Item::get(35, 3, 1)->setCustomName("§eキャンセル")
                                     ];
                            for($i=0;$i<2;$i++){
                                $in->setItem($i, $items[$i]);
                                $in->setHotbarSlotIndex($i, $i);
                                $in->sendContents($player);
                            }
                         }
                            


    	    		break;
    	    	}
            
    	   }else if(isset($this->pare[$name])){
  
             $base = $this->con->get("pare");

             switch($this->pare[$name]){
    		case "r":
                         $data = array_replace($base, ["right"=>["x"=>$block->x, "y"=>$block->y, "z"=>$block->z]]);
                         $this->con->set("pare", $data);
                         $this->con->save();
                         unset($this->pare[$name]);
                         $player->sendMessage("§b右ボタンtextを設定しました");
                break;

                case "c":
                         $data = array_replace($base, ["center"=>["x"=>$block->x, "y"=>$block->y, "z"=>$block->z]]);
                         $this->con->set("pare", $data);
                         $this->con->save();
                         unset($this->pare[$name]);
                         $player->sendMessage("§b決定ボタンtextを設定しました");
                break;

                case "l":
                         $data = array_replace($base, ["left"=>["x"=>$block->x, "y"=>$block->y, "z"=>$block->z]]);
                         $this->con->set("pare", $data);
                         $this->con->save();
                         unset($this->pare[$name]);
                         $player->sendMessage("§b左ボタンtextを設定しました");
                break;
    	     }
           }
    }



    public function onRe(\pocketmine\event\server\DataPacketReceiveEvent $ev){

        $pk = $ev->getPacket();
        $player = $ev->getPlayer();
        $name = $player->getName();
        
        if($pk instanceof \pocketmine\network\protocol\UseItemPacket){

            $in = $player->getInventory();
            $item = $in->getItemInHand();
            $id = $item->getId();
            $meta = $item->getDamage();

            if($id == 35){


                switch($meta){


                     case 1:

                          $ip =  gethostbyname($this->entry[$name]["ip"]);
                          
                          $ppk = new \pocketmine\network\protocol\TransferPacket;
                          $ppk->address = $ip;
                          $ppk->port = $this->entry[$name]["port"];

                          unset($this->entry[$name]);
                          $player->dataPacket($ppk);
                     break;

                     case 3: 

                             $in->clearAll();
                             unset($this->entry[$name]);
                             $player->sendMessage("§eキャンセルしました");

                     break;


                }


            }

            
        }
        
        
    }





    public function setButtonText(){


        $button = $this->con->get("pare");
    	$br = new Vector3($button["right"]["x"], $button["right"]["y"], $button["right"]["z"]);
    	$bl = new Vector3($button["left"]["x"], $button["left"]["y"], $button["left"]["z"]);
    	$bc = new Vector3($button["center"]["x"], $button["center"]["y"], $button["center"]["z"]);

        $array = [$br, $bl, $bc];

        for($i=0;$i<3;$i++){

            if($i==0){
                $text = "§b>>>";
                $eid = 114514;
            }else if($i==1){
                $text = "§b<<<";
                $eid = 114515;
            }else{
                $text = "§6決定";
                $eid = 114516;
            }
            $pk = new AddEntityPacket; 
            $pk->eid = $eid;
            $pk->type = 15;
            $pk->x = $array[$i]->x+0.5;
            $pk->y = $array[$i]->y-1.5;
            $pk->z = $array[$i]->z+0.5;
            $pk->speedX = 0;
            $pk->speedY = 0;
            $pk->speedZ = 0;
            $pk->yaw = 0;
            $pk->pitch = 0;
            $pk->item = 0;
            $pk->meta = 0;
            $flags = 0;
            $flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
            $flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
            $flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
            $flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
            $pk->metadata = [
                Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
                Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $text."\n"],
            ];


            Server::broadcastPacket($this->getServer()->getOnlinePlayers(), $pk);
        }


    }


    public function getNearId($type, $id){

        switch($type){

            case "up":

                   for($i=$id+1;$i<$this->con->get("id");$i++){

                           if($this->idExists($i)){

                                   return $i;
                                   break;

                           }
                           
                   }

                   return false;
            break;

            case "down":

                   for($i=$id-1;$i>0;$i--){

                            if($this->idExists($i)){

                                return $i;
                                break;
                            }
                   }

                   return false;
            break;
        }
    }


    public function onPlace(\pocketmine\event\block\BlockPlaceEvent $ev){

        $player = $ev->getPlayer();
        $name = $player->getName();
        $base = $this->con->get("button");
        $block = $ev->getBlock();
        if(!$player->isOp()) $ev->setCancelled();

    	if(isset($this->data[$name])){

    		switch($this->data[$name]){
    			case "r":
                         $data = array_replace($base, ["right"=>["x"=>$block->x, "y"=>$block->y, "z"=>$block->z]]);
                         $this->con->set("button", $data);
                         $this->con->save();
                         unset($this->data[$name]);
                         $player->sendMessage("§b右ボタンを設定しました");
                break;

                case "c":
                         $data = array_replace($base, ["center"=>["x"=>$block->x, "y"=>$block->y, "z"=>$block->z]]);
                         $this->con->set("button", $data);
                         $this->con->save();
                         unset($this->data[$name]);
                         $player->sendMessage("§b決定ボタンを設定しました");
                break;

                case "l":
                         $data = array_replace($base, ["left"=>["x"=>$block->x, "y"=>$block->y, "z"=>$block->z]]);
                         $this->con->set("button", $data);
                         $this->con->save();
                         unset($this->data[$name]);
                         $player->sendMessage("§b左ボタンを設定しました");
                break;
    		}
    	}
    }



    public function isButton($block){


    	$button = $this->con->get("button");
    	$posr = new Vector3($button["right"]["x"], $button["right"]["y"], $button["right"]["z"]);
    	$posl = new Vector3($button["left"]["x"], $button["left"]["y"], $button["left"]["z"]);
    	$posc = new Vector3($button["center"]["x"], $button["center"]["y"], $button["center"]["z"]);

    	$posb = new Vector3($block->x, $block->y, $block->z);

    	if($posr == $posb){
    		return "r";
    	}elseif($posl == $posb){
    		return "l";
    	}elseif($posc == $posb){
    		return "c";
    	}else{
    		return false;
    	}
    }


    public function onCommand(CommandSender $sender, Command $cmd, $label,array $args){


    	$name = $sender->getName();
        if($sender->isOp()){

            switch($cmd->getName()){


            	    case "set":

            	            if(isset($args[0])){

            	            	switch($args[0]){

            	            		case "text":

            	            		        $data = ["x"=>$sender->x, "y"=>$sender->y, "z"=>$sender->z];
            	            		        $this->con->set("text", $data);
            	            		        $this->con->save();
            	            		        $sender->sendMessage("文章の位置を設定しました");
            	            		break;

            	            		case "button":

            	            		        if(isset($args[1])){
            	            		        	switch($args[1]){

            	            		        		case "r":
            	            		        		case "right":
            	            		        		          $this->data[$name] = "r";
            	            		        		break;

            	            		        		case "l":
            	            		        		case "left":
                                                              $this->data[$name] = "l";
                                                    break;

                                                    case "c":
                                                    case "center":
                                                              $this->data[$name] = "c";
                                                    break;

            	            		        	}
            	            		        	$sender->sendMessage("ボタンを置いてください");
            	            		        }
            	            		break;


                                        case "pare":


                                               if(isset($args[1])){
            	            		        	switch($args[1]){

            	            		        		case "r":
            	            		        		case "right":
            	            		        		          $this->pare[$name] = "r";
            	            		        		break;

            	            		        		case "l":
            	            		        		case "left":
                                                              $this->pare[$name] = "l";
                                                    break;

                                                    case "c":
                                                    case "center":
                                                              $this->pare[$name] = "c";
                                                    break;

            	            		        	}
            	            		        	$sender->sendMessage("タッチしてください。。");
            	            		        }

                                   
            	            	}
            	            }
            	     break;


                     case "server":

                             if(isset($args[0])){

                                switch($args[0]){

                                    case "add":

                                          if(isset($args[5])){

                                                $sname = $args[1];
                                                $ip = $args[2];
                                                $port = $args[3];
                                                $owner = $args[4];
                                                $info = $args[5];
                                                $info = str_replace("[br]", "\n", $info);
                                                if($this->sdata->exists($name)){

                                                    $sender->sendMessage("§b同じ名前のサーバーが存在します。変更してください");

                                                }else{
                                                    $data = ["owner"=>$owner, "ip"=>$ip, "port"=>$port, "info"=>$info, "id"=>$this->con->get("id")];
                                                    $this->con->set("id", $this->con->get("id")+1);
                                                    $this->con->save();
                                                    $this->sdata->set($sname, $data);
                                                    $this->sdata->save();
                                                    if($this->pdata->exists($name)){
                                                        $data = $this->pdata->get($name);
                                                        $data = array_push($data, $sname);
                                                        $this->pdata->set($name, $data);
                                                        $this->pdata->save();
                                                    }else{
                                                        $this->pdata->set($name, [$sname]);
                                                        $this->pdata->save();
                                                    }
                                                    $sender->sendMessage("§aデーターを保存しました");

                                                }    
   
                                           }else{

                                                $sender->sendMessage("§e/server add [name] [ip] [port] [owner] [info]");
                                           }
                                     break;
                    break;


                                }
                             }

            }

        }
    }


    public function sendText(Player $player, $text){

            $pk = new AddEntityPacket; 
            $name = $player->getName();
            $pk->eid = $this->id[$name];
			$pk->type = 15;
			$pk->x = $this->con->get("text")["x"];
			$pk->y = $this->con->get("text")["y"];
			$pk->z = $this->con->get("text")["z"];
			$pk->speedX = 0;
			$pk->speedY = 0;
			$pk->speedZ = 0;
			$pk->yaw = 0;
			$pk->pitch = 0;
			$pk->item = 0;
			$pk->meta = 0;
			$flags = 0;
			$flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
			$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
			$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
			$flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
			$pk->metadata = [
				Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
				Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, "\n".$text],
			];


            $player->dataPacket($pk);

              

    }


   

}
                                  