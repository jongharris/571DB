<?php
    include('/var/www/dbConnection.php');
    include('/var/www/html/apiFunctions.php');
    
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        
        //raw data clense this
        $raw_playerName = $_POST['playerName'];
        
        //clean data
        $raw_playerName = str_replace(".", "%", $raw_playerName);
		$raw_playerName = str_replace(" ", "%", $raw_playerName);
        $raw_playerName = trim($raw_playerName);
        $playerName = filter_var($raw_playerName, FILTER_SANITIZE_STRING);
        $playerName = "%".$playerName."%";
        
        //query to grab the player searched
        $queryPlayer = "SELECT *, DATEDIFF('".date("Y/m/d")."', birthdate) AS age FROM NHLapiDB.players WHERE CONCAT(fName, ' ', lName) LIKE '".$playerName."';";
        $run_queryPlayer = mysqli_query($connection, $queryPlayer);
        $resultPlayer = mysqli_fetch_assoc($run_queryPlayer);

        //The query to grab the team name
        $queryTeam = "Select teamName, location from NHLapiDB.teams WHERE idteams = ".$resultPlayer['currentTeam'].";";
        $run_queryTeam = mysqli_query($connection, $queryTeam);
        $resultTeam = mysqli_fetch_assoc($run_queryTeam);
		
		//***Heat Map Queries***//
		$xpoints = array();
		$ypoints = array();
		$xpoints2 = array();
		$ypoints2 = array();
		//Faceoffs
		//$queryHeat = "SELECT xposition AS x, yposition AS y FROM NHLapiDB.faceoffs;";
		//Goals
		$queryHeat = "SELECT xposition AS x, yposition AS y FROM NHLapiDB.goals WHERE scorer = "
			.$resultPlayer['idplayers']." AND shootout=false;";
			
		//Shots
		$queryHeat = "SELECT xposition AS x, yposition AS y FROM NHLapiDB.goals WHERE scorer = "
			.$resultPlayer['idplayers']." AND shootout=false;";
		$queryHeat2 = "SELECT xposition AS x, yposition AS y FROM NHLapiDB.shots WHERE shooter = "
			.$resultPlayer['idplayers']." AND shootout=false;";
		$runqueryHeat2 = mysqli_query($connection, $queryHeat2);
		if(!$runqueryHeat2)
			echo "error";
		else
			while ($result = mysqli_fetch_assoc($runqueryHeat2)){
				array_push($xpoints2, $result['x']);
				array_push($ypoints2, $result['y']);
			}
		
		//Shot Attempts

		//$queryHeat = "SELECT xposition AS x, yposition AS y FROM NHLapiDB.goals WHERE scorer = "
		//	.$resultPlayer['idplayers']." AND shootout=false;";
		
		$runqueryHeat = mysqli_query($connection, $queryHeat);
		if(!$runqueryHeat)
			echo "error";
		else
			while ($result = mysqli_fetch_assoc($runqueryHeat)){
				array_push($xpoints, $result['x']);
				array_push($ypoints, $result['y']);
			}        
    }
        

?>


<!DOCTYPE html
<html lang="en">
<script src='http://www.patrick-wied.at/static/heatmapjs/assets/js/heatmap.min.js'></script>
<head>
    <meta charset="UTF-8">
    <title>Document</title>
    <link rel="stylesheet" href = "playerDashStyle.css">

</head>
<body>
    <div class = "flexContainer">
        <div class = "sideNav">
            <div class = "title"> 
            
            </div>
        </div>
        <div class = "informationNav">
            <div class = "topNav">
               <div class = "userForm">
                   <form method = "post" action="<?php echo $_SERVER['PHP_SELF'];?>">
                       <input id = "playerField" type = "text" name = "playerName" placeholder ="Enter Player"><input id = "searchBtn" type = "submit" value = "Search">
                   </form>
               </div>
            </div>
            <div class = "content">
               <div class = "dashboardTitle">
                    <h2> Player Dashboard</h2>
                </div>
                
                <div class = "playerInfo">
                    <div class = "playerCard">
                        <div class = "playerCardName">
                        <?php
                       if ($resultPlayer) {
                        ?>
                         <h3> <?php echo $resultPlayer['fName']." ".$resultPlayer['lName']." | ".$resultPlayer['primeNumber'];?> </h3>
                        </div>
<!--                        Note: ٠ -->
                    <?php
                       }
                    ?>

                        <div class = "playerCardDetails">
                            <p style = "text-align: center">
							<?php echo (($resultPlayer['primePosition'])?$resultPlayer['primePosition']:'-')."  •  "
										.(($resultPlayer['shootsLeft'])?'Left':'Right')."  •  "
										.(($resultTeam)? $resultTeam['location']." ".$resultTeam['teamName']:'Not Active'); ?></p> 
                            <p style = "text-align: center">
							<?php echo (($resultPlayer['height'])?$resultPlayer['height']:"-'--")."\"  •  "
										.(($resultPlayer['weight'])?$resultPlayer['weight']:'---')."lbs  •  Age: "
										.(($resultPlayer['age'])? floor($resultPlayer['age']/365.25) : '--')."  •  "
										.(($resultPlayer['nationality'])? $resultPlayer['nationality']:'---'); ?></p>
                           
                        </div>
                    </div>
					<br>
					<div class="heatmapCard">
                        <div class = "playerCardName">
                            <h3> Goals Heat Map </h3>
                        </div>
						<div id='heatMap' class="heatmap" name="playerGoals">
		                    <canvas width="799" height="340" style="position:absolute; left: 0; top: 0"></canvas>
						</div>
					</div>
                        
                </div>
				
				
            </div>
        </div>
    </div>
    </div>
</body>


<script> 
	var heatmapInstance = h337.create({
		container: document.getElementById('heatMap'),
		radius: 14,
		maxOpacity: 0.85,
		minOpacity: 0.01,
		gradient: {
			'.9': '#AA0000',
			'.6': '#FF0000',
			'.3': '#FFAAAA',
			'.01': 'white'
		}
	});

	var xPoints = <?php echo '["' . implode('", "', $xpoints) . '"]' ?>;
	var yPoints = <?php echo '["' . implode('", "', $ypoints) . '"]' ?>;

	console.log(xPoints);
	console.log(yPoints);

	dataPoints = [];
	for(var i=0; i<xPoints.length; i++){
		dataPoints.push({x: (parseFloat(xPoints[i])+100)*2.4, y: (parseFloat(yPoints[i])+42.5)*2.4, value: 1});
	}
	console.log(dataPoints);

	var testData = {
		min: 0,
        max: 1,
		data: dataPoints
	};
	heatmapInstance.setData(testData);


	var heatmapInstance2 = h337.create({
		container: document.getElementById('heatMap'),
		radius: 14,
		maxOpacity: 0.5,
		minOpacity: 0.01,
		gradient: {
			'.9': '#0000AA',
			'.6': '#0000FF',
			'.3': '#AAAAFF',
			'.01': 'white'
		}
	});

	var xPoints2 = <?php echo '["' . implode('", "', $xpoints2) . '"]' ?>;
	var yPoints2 = <?php echo '["' . implode('", "', $ypoints2) . '"]' ?>;

	console.log(xPoints2);
	console.log(yPoints2);

	dataPoints2 = [];
	for(var i=0; i<xPoints2.length; i++){
		dataPoints2.push({x: (parseFloat(xPoints2[i])+100)*2.4, y: (parseFloat(yPoints2[i])+42.5)*2.4, value: 1});
	}
	console.log(dataPoints2);

	var testData2 = {
		min: 0,
        max: 3,
       data: dataPoints2
	};
	heatmapInstance2.setData(testData2);
	
</script>
</html>