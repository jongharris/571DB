<?php
	function lineGraphPlayerQuery($connection, $playerID) {
		$query = "SELECT date(date) as date, count(IF(scorer=".$playerID.", 1, NULL)) as goals, count(IF(scorer=".$playerID.", NULL, 1)) as assists"
				."FROM NHLapiDB.games"
				."as gametable"
				."JOIN (SELECT game, scorer, assist1, assist2 FROM NHLapiDB.goals as go WHERE scorer=".$playerID." OR assist1 = ".$playerID." OR assist2 = ".$playerID.")"
				."AS goaltable ON gametable.idgames = goaltable.game"
				."Group by date;"

		return mysqli_query($connection, $query);
	}


?>