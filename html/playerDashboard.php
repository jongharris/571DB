<?php
    include('/var/www/dbConnection.php');
	include('/var/www/html/heatMapQueries.php');
    include('/var/www/html/apiFunctions.php');
	include('/var/www/html/lineGraphQueries.php');

    
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        
        {//process player input
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
		}
		{//call heatmap function
		if($resultPlayer)
			list($heatX1, $heatY1, $heatX2, $heatY2) = heatMapPlayerQueries($connection, $resultPlayer);
		}
		{//call chart functions
		if($resultPlayer){
			list($date, $goals, $assists) = lineGraphPlayerQuery($connection, $resultPlayer['idplayers']);
			$radarData = radarGraphSkater($connection, $resultPlayer['idplayers']);
		}
		}
    }
        

?>


<!DOCTYPE html
<html lang="en">
<script src='http://www.patrick-wied.at/static/heatmapjs/assets/js/heatmap.min.js'></script>
<script src = "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>

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
                       <input id = "playerField" type = "text" name = "playerName" placeholder ="Enter Player" style = "z-index: 100;"><input id = "searchBtn" type = "submit" value = "Search">
                   </form>
               </div>
            </div>
            <div class = "content">
               <div class = "dashboardTitle">
                    <h2> Player Dashboard</h2>
                </div>
                
                <div class = "playerInfo">
                    <div class = "row">
                        <div class = "playerCard">
                            <div class = "playerCardName">
                          
                       <h3> <?php echo (($resultPlayer) ? $resultPlayer['fName'] : "")." "
								.(($resultPlayer) ? $resultPlayer['lName'] : "")." | "
								.(($resultPlayer) ? $resultPlayer['primeNumber'] : "");?> </h3>
							</div>
   <!--                        Note: ٠ -->
                      
	
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
                             <div class = "graphCard">
                                 <div class = "playerCardName">
								 </div>
                             
								<canvas id = "myChart"> </canvas>
							</div>

							<div class = "graphCard">
								Goals <input type = "radio" name = "graphType" value = "goals">
								Shots<input type = "radio" name = "graphType" value = "shots">
								PowerPlay <input type = "radio" name = "graphType" value = "powerplays">
							</div>
						
					</div> 
					
					<br>
					
					<div class = "row">
                       <!-- <div class="heatmapCard">
                            <div class = "playerCardName">
                                <h3> Goals Heat Map </h3>
                            </div>
                            <div id='heatMap' class="heatmap" name="playerGoals">
                                <canvas width="799" height="340" style="position:absolute; left: 0; top: 0"></canvas>
                            </div> -->
                        </div>
                    </div>   
                     
                </div>
				
				
            </div>
        </div>
    </div>
</body>


<script> 

	let date = <?php echo '["' . implode('", "', $date) . '"]' ?>;
	let goals = <?php echo '["' . implode('", "', $goals) . '"]' ?>;
	let assists = <?php echo '["' . implode('", "', $assists) . '"]' ?>;
	console.log(date);
	console.log(goals);
	console.log(assists);
	
	let myChart = document.getElementById('myChart').getContext('2d');
     
        let massChart = new Chart(myChart, {
            type: 'line', //bar, horizontal bar, pie, line, donut, radar, polarArea
            data: {
				labels: date,
				datasets:[
				{
					label: 'Goals',
					data: goals,
					backgroundColor:'rgba(255, 0, 0, 0.4)'},
				{
					label: 'Assists',
					data:assists,
					backgroundColor: 'rgba(0,0,255,0.4'}
				]
            },
            options: {
                title: {
                    display: true,
                    text: 'Goals and Assists'
                }
            }
        });



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

	var xPoints = <?php echo '["' . implode('", "', $heatX1) . '"]' ?>;
	var yPoints = <?php echo '["' . implode('", "', $heatY1) . '"]' ?>;

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

	var xPoints2 = <?php echo '["' . implode('", "', $heatX2) . '"]' ?>;
	var yPoints2 = <?php echo '["' . implode('", "', $heatY2) . '"]' ?>;

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