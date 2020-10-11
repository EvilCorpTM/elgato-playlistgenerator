<?php
include("config.php");

$channels=json_decode(file_get_contents("channels.json"), true);
//print_r($channels);
echo "#EXTM3U\n";
foreach($channels as $channel) {
	echo "#EXTINF:-1,".$channel["name"]."\n";
	//echo $channel["name"]."\n";
	echo "http://$IP/?src=1";
	echo "&msys=dvbs"; if ($channel["transponder"]["type"] == "DVB-S2") echo "2";

	echo "&mtype=".strtolower($channel["transponder"]["modulation"]);
	echo "&freq=".$channel["transponder"]["freq"].".00";
	echo "&sr=".$channel["transponder"]["rate"];
	echo "&pol=".strtolower($channel["transponder"]["pol"]);
	echo "&fec=".preg_replace( '/[^0-9]/', '', $channel["transponder"]["fec"]);
	$pids=array(0,16,17,18,20);
	$pids=array_merge($pids, $channel["pids"]);
	$pids=array_unique($pids);
	asort($pids);
	echo "&pids=".implode(',', $pids); //0,16,17,18,20,1760,1720,1760,5071";
	echo "\n";
}
?>
