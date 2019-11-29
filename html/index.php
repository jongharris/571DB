<?php
	ini_set('max_execution_time', 0);
    include('/var/www/dbConnection.php');
	include('/var/www/html/apiFunctions.php');

	if (isset($_POST['AddAllActiveTeams'])) {
		addAllTeams($connection);
    }elseif (isset($_POST['AddAllActivePlayers'])) {
		addAllActivePlayers($connection);
    }elseif (isset($_POST['AddGameData'])) {
		removeGameData($connection, $_POST["gameNumber"]);
		addGameData($connection, $_POST["gameNumber"]);
	}elseif (isset($_POST['AddGamesSince'])){
		addGamesSince($connection, $game);
	}
  
?>



<!DOCTYPE html>
<html lang="en">
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
<!--    <button id="retrievePlayers" onclick= "requestAllActivePlayers()">Request all Active Players</button>-->
<!--    <button id="retrieveTeams" onclick= "requestAllTeams()">Request all Teams</button>-->

<!--    <script src = "./requestTeams.js"></script>-->
<!--    <script src = "./requestPlayers.js"></script>-->

</body>
</html>