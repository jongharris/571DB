<?php
	ini_set('max_execution_time', 0);
    include('/var/www/dbConnection.php');
	include('/var/www/html/apiFunctions.php');

	$xpoints = array();
	$ypoints = array();

	if (isset($_POST['AddAllActiveTeams'])) {
		addAllTeams($connection);
    }elseif (isset($_POST['AddAllActivePlayers'])) {
		addAllActivePlayers($connection);
    }elseif (isset($_POST['AddGameData'])) {
		removeGameData($connection, $_POST["gameNumber"]);
		addGameData($connection, $_POST["gameNumber"]);
	}elseif (isset($_POST['AddGamesSince'])){
		addGamesSince($connection, $game);
	}elseif (isset($_POST['GetGoals'])){
		//$query = "SELECT xposition AS x, yposition AS y, period as p FROM NHLapiDB.goals WHERE scorer = ".$_POST["playerNumber"]." AND shootout=false;";
		$query = "SELECT xposition AS x, yposition AS y, period as p FROM NHLapiDB.goals WHERE shootout=false AND shotType='Tip-In';";
		$runQuery = mysqli_query($connection, $query);
		if(!$runQuery)
			echo "unsuccessful"."<br>".$query."<br>";
		else{
			while ($result = mysqli_fetch_assoc($runQuery)){
				array_push($xpoints, $result['x']);
				array_push($ypoints, $result['y']);
				echo $result['x'].", ".$result['y']."<br>";
			}
		}
	}
  
?>



<!DOCTYPE html>
<html lang="en">
<script src='http://www.patrick-wied.at/static/heatmapjs/assets/js/heatmap.min.js'></script>
<head>
    <meta charset="UTF-8">
    <title>Pull From API</title>
</head>
<body>
   
   <form method = "post" action = "<?php echo $_SERVER['PHP_SELF'];?>">
       <button class = "button" type = "submit" name = "AddAllActiveTeams" value = "AddAllActiveTeams">
	   Request Teams </button>
   </form><br>
   <form method = "post" action = "<?php echo $_SERVER['PHP_SELF'];?>">
       <button class = "button" type = "submit" name = "AddAllActivePlayers" value = "AddAllActivePlayers">
	   Add All Active Players </button>
   </form><br>
   <form method = "post" action = "<?php echo $_SERVER['PHP_SELF'];?>">
		Game Number <input type="text" name="gameNumber"><br>
       <button class = "button" type = "submit" name = "AddGameData" value = "AddGameData">
		Get Game </button>
   </form><br>
   <form method = "post" action = "<?php echo $_SERVER['PHP_SELF'];?>">
		Game Number <input type="text" name="gameNumber"><br>
       <button class = "button" type = "submit" name = "AddGamesSince" value = "AddGamesSince">
		Get Games Since </button>
   </form><br>
   <form method = "post" action = "<?php echo $_SERVER['PHP_SELF'];?>">
		Player Number <input type="text" name="playerNumber"><br>
       <button class = "button" type = "submit" name = "GetGoals" value = "GetGoals">
		Get Player Goals </button>
   </form><br>
   <p id="jsOut"></p>
   <div id='heatMap' style="height: 742px">
    <canvas width="400" height="170" style="position:absolute; left: 0; top: 0"></canvas>
  </div>
</body>


<script> 
	var heatmapInstance = h337.create({
		container: document.getElementById('heatMap')
	});

	//var xPoints = [<?php echo '"'.implode('","',  $xpoints).'"' ?>];
	var xPoints = <?php echo '["' . implode('", "', $xpoints) . '"]' ?>;
	//var yPoints = [<?php echo '"'.implode('","',  $ypoints).'"' ?>];
	var yPoints = <?php echo '["' . implode('", "', $ypoints) . '"]' ?>;

	dataPoints = [];
	for(var i=0; i<xPoints.length; i++){
		dataPoints.push({x: (parseFloat(xPoints[i])+100)*2, y: (parseFloat(yPoints[i])+42.5)*2, value: 1});
	}
	console.log(dataPoints);

	var testData = {
		min: 0,
        max: 30,
       data: dataPoints
	};
	heatmapInstance.setData(testData);  
</script>

</html>