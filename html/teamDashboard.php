<?php
    include('/var/www/dbConnection.php');
    include('/var/www/html/apiFunctions.php');
	include('/var/www/html/lineGraphQueries.php');
	include('/var/www/html/heatMapQueries.php');
    
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        
		{//process team input
        //raw data clense this
        $raw_teamName = $_POST['teamName'];
        
        //clean data
        $raw_teamName = str_replace(".", "%", $raw_teamName);
		$raw_teamName = str_replace(" ", "%", $raw_teamName);
        $raw_teamName = trim($raw_teamName);
        $teamName = filter_var($raw_teamName, FILTER_SANITIZE_STRING);
        $teamName = "%".$teamName."%";

        //The query to grab the team name
        $queryTeam = "Select teamName, idteams, location, division from NHLapiDB.teams "
		."WHERE CONCAT(location, ' ', teamName) LIKE '".$teamName."';";
        $run_queryTeam = mysqli_query($connection, $queryTeam);
        $resultTeam = mysqli_fetch_assoc($run_queryTeam);
		
		$query = "SELECT COUNT(home) AS GP, "
		."COUNT(IF(home=".$resultTeam['idteams']." AND homeWIN OR home!=".$resultTeam['idteams']." AND NOT homeWIN, 1, NULL)) AS W, "
		."COUNT(IF(NOT(home=".$resultTeam['idteams']." AND homeWIN OR home!=".$resultTeam['idteams']
		." AND NOT homeWIN) AND NOT (overtime OR shootout), 1, NULL)) AS L, COUNT(IF(NOT(home=".$resultTeam['idteams']
		." AND homeWIN OR home!=".$resultTeam['idteams']." AND NOT homeWIN) AND (overtime OR shootout), 1, NULL)) AS OT "
		."FROM (SELECT home, homeWIN, overtime, shootout FROM NHLapiDB.games WHERE home = ".$resultTeam['idteams']
		." OR away = ".$resultTeam['idteams'].") AS RECORD;";
		
		$runQuery = mysqli_query($connection, $query);
		$record = mysqli_fetch_assoc($runQuery);
		}
		
		{//Get Graph Data for Team
		$runGraph = lineGraphTeamQuery($connection, $resultTeam['idteams']);
		
		$date = array();
		$goalsFor = array();
		$goalsAgainst = array();
		$shotsFor = array();
		$shotsAgainst = array();
		$ppFor = array();
		$ppAgainst = array();
		$gTotal = 0;
		$gaTotal = 0;
		$sTotal = 0;
		$saTotal = 0;
		$ppTotal = 0;
		$ppaTotal = 0;
		$gpTotal = 0;
		
       while ($lineData = mysqli_fetch_assoc($runGraph)) {
            array_push($date, $lineData['date']);
			array_push($goalsFor, $lineData['GoalsFor']);
			$gTotal = $gTotal + (int)$lineData['GoalsFor'];
            array_push($goalsAgainst, $lineData['GoalsAgainst']);
			$gaTotal = $gaTotal + (int)$lineData['GoalsAgainst'];
            array_push($shotsFor, $lineData['ShotsFor']);
			$sTotal = $sTotal + (int)$lineData['ShotsFor'];
            array_push($shotsAgainst, $lineData['ShotsAgainst']);
			$saTotal = $saTotal + (int)$lineData['ShotsAgainst'];
            array_push($ppFor, $lineData['Powerplays']);
			$ppTotal = $ppTotal + (int)$lineData['Powerplays'];
            array_push($ppAgainst, $lineData['PenaltyKills']);
			$ppaTotal = $ppaTotal + (int)$lineData['PenaltyKills'];
			$gpTotal ++;
		}
		
		$radarData = radarGraphTeamAvgs($connection);
		}
		
		{//call heatmap function
		list($heatX1, $heatY1, $heatX2, $heatY2, $heatX3, $heatY3, $heatX4, $heatY4) = heatMapTeamGSFA($connection, $resultTeam['idteams']);
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
    <link rel="stylesheet" href = "teamDashStyle.css">

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
                       <input id = "playerField" type = "text" name = "teamName" placeholder ="Enter Team" style = "z-index: 10;"><input id = "searchBtn" type = "submit" value = "Search">
                   </form>
               </div>
            </div>
            <div class = "content">
               <div class = "dashboardTitle">
                    <h2> Team Dashboard</h2>
                </div>
                
                <div class = "teamInfo">
                   <div class = "row">
                        <div class = "teamCard">
                            <div class = "teamCardName">
                           
                             <h3> <?php echo (($resultTeam) ? $resultTeam['location'] : "")." "
							 .(($resultTeam) ? $resultTeam['teamName'] : "");?> </h3>
                            </div>
    <!--                        Note: ٠ -->
                      

                            <div class = "teamCardDetails">
								<p style = "text-align: center">
                                <?php echo (($resultTeam['division'])?$resultTeam['division']:'Division'); ?></p> 
    <!--                           This will be the Record-->
                                <p style = "text-align: center">
                                <?php echo (($record['W'])?$record['W']:'-')."  •  "
                                            .(($record['L'])?$record['L']:'-')."  •  "
                                            .(($record['OT'])?$record['OT']:'-'); ?></p> 
                           

                            </div>
                        </div>
                        <div class = "graphCard">
                             <div class = "teamCardName">
								Goals <input type = "radio" name = "graphType" value = "goals">
								Shots<input type = "radio" name = "graphType" value = "shots">
								PowerPlay <input type = "radio" name = "graphType" value = "powerplays">
                             
        
                            </div>
                             
							<canvas id = "myChart"> </canvas> 

                      
                   

                        </div>

                        <div class = "graphCard">
                            <div class = "teamCardName">
							
							</div>
							
							<canvas id = "spiderChart"> </canvas>
                        </div>
                    </div>    
					<br>
					<div class="heatmapCard">
                        <div class = "teamCardName">
                            <h3> Goals Heat Map </h3>
                        </div>
						<div id='heatMap' class="heatmap" name="playerGoals">
		                    <canvas width="480" height="204" style="position:absolute; left: 0; top: 0"></canvas>
						</div>
					</div> 
                       
                    <div class = "graphCard">

                    </div>
                        
                </div>
				
				
            </div>
        </div>
    </div>
   
    
        
    <script>//charts
 
		let date = <?php echo '["' . implode('", "', $date) . '"]' ?>;
		let goalsFor  = <?php echo '["' . implode('", "', $goalsFor) . '"]' ?>;
		let goalsAgainst = <?php echo '["' . implode('", "', $goalsAgainst) . '"]' ?>;
		let shotsFor = <?php echo '["' . implode('", "', $shotsFor) . '"]' ?>;
		let shotsAgainst = <?php echo '["' . implode('", "', $shotsAgainst) . '"]' ?>;
		let ppFor = <?php echo '["' . implode('", "', $ppFor) . '"]' ?>;
		let ppAgainst = <?php echo '["' . implode('", "', $ppAgainst) . '"]' ?>;

        let myChart = document.getElementById('myChart').getContext('2d');
     
        let massChart = new Chart(myChart, {
            type: 'line', //bar, horizontal bar, pie, line, donut, radar, polarArea
            data: {
				labels: date,
				datasets:[
				{
					label: 'Goals For',
					data: goalsFor,
					backgroundColor:'rgba(255, 0, 0, 0.4)'},
				{
					label: 'Goals Against',
					data:goalsAgainst,
					backgroundColor: 'rgba(0,0,255,0.4'}
				]
            },
            options: {
                title: {
                    display: true,
                    text: 'Goals Per Game'
                }
            }
        });
		
		
		let gpTotal = <?php echo $gpTotal;?>;
		let gTotal = <?php echo $gTotal;?> ;
		let gaTotal = <?php echo $gaTotal;?>;
		let sTotal = <?php echo $sTotal;?>;
		let saTotal = <?php echo $saTotal;?>;
		let ppTotal = <?php echo $ppTotal;?>;
		let ppaTotal = <?php echo $ppaTotal;?>;
		
		let avgGoals = <?php echo $radarData['avgGoals'];?>;
		let avgShots = <?php echo $radarData['avgShots'];?>;
		let avgPPs = <?php echo $radarData['avgPPs'];?>;
		
		let spiderChartID = document.getElementById('spiderChart').getContext('2d');
     
        let spiderChart = new Chart(spiderChartID, {
            type: 'radar', //bar, horizontal bar, pie, line, donut, radar, polarArea
            data: {
				labels: ['Goals', 'PowerPlays', 'PowerPlays Given', 'Goals Against', 'Shots Against', 'Shots'],
				datasets:[
				{				
					data: [(gTotal/gpTotal)/avgGoals, (ppTotal/gpTotal)/avgPPs, (ppaTotal/gpTotal)/avgPPs, 
							(gaTotal/gpTotal)/avgGoals, (saTotal/gpTotal)/avgShots,	(sTotal/gpTotal)/avgShots],
					backgroundColor:'rgba(255, 0, 0, 0.5)'
				},{
					data: [.5, .5, .5, .5, .5,	.5],
					backgroundColor:'rgba(100, 100, 100, 0.25)'
				}]
            },
            options: {
				scale: {
					ticks: {
						display: false,
						suggestedMin: 0.35,
						suggestedMax: 0.65
					}
				},
				legend: {
					display: false,
				},
                title: {
                    display: false,
                    text: 'Radar'
                }
            }
        });
		
		
		
    </script>
	
	<script>//heatmap
	var heatmapInstance4 = h337.create({
		container: document.getElementById('heatMap'),
		radius: 14,
		maxOpacity: 0.6,
		minOpacity: 0.01,
		gradient: {
			'.9': '#444444',
			'.6': '#777777',
			'.3': '#AAAAAA',
			'.01': 'white'
		}
	});

	var xPoints4 = <?php echo '["' . implode('", "', $heatX4) . '"]' ?>;
	var yPoints4 = <?php echo '["' . implode('", "', $heatY4) . '"]' ?>;

	dataPoints4 = [];
	for(var i=0; i<xPoints4.length; i++){
		dataPoints4.push({x: (parseFloat(xPoints4[i])+100)*2.4, y: (parseFloat(yPoints4[i])+42.5)*2.4, value: 1});
	}

	var testData4 = {
		min: 0,
        max: 10,
       data: dataPoints4
	};
	heatmapInstance4.setData(testData4);
	
	
	var heatmapInstance3 = h337.create({
		container: document.getElementById('heatMap'),
		radius: 14,
		maxOpacity: 0.85,
		minOpacity: 0.01,
		gradient: {
			'.9': '#0000AA',
			'.6': '#0000FF',
			'.3': '#AAAAFF',
			'.01': 'white'
		}
	});

	var xPoints3 = <?php echo '["' . implode('", "', $heatX3) . '"]' ?>;
	var yPoints3 = <?php echo '["' . implode('", "', $heatY3) . '"]' ?>;

	console.log(xPoints4);
	console.log(xPoints3);

	dataPoints3 = [];
	for(var i=0; i<xPoints3.length; i++){
		dataPoints3.push({x: (parseFloat(xPoints3[i])+100)*2.4, y: (parseFloat(yPoints3[i])+42.5)*2.4, value: 1});
	}
	
	console.log(dataPoints4);
	console.log(dataPoints3);
	
	var testData3 = {
		min: 0,
        max: 3,
		data: dataPoints3
	};
	heatmapInstance3.setData(testData3);


	var heatmapInstance2 = h337.create({
		container: document.getElementById('heatMap'),
		radius: 14,
		maxOpacity: 0.6,
		minOpacity: 0.01,
		gradient: {
			'.9': '#00AA00',
			'.6': '#00FF00',
			'.3': '#AAFFAA',
			'.01': 'white'
		}
	});

	var xPoints2 = <?php echo '["' . implode('", "', $heatX2) . '"]' ?>;
	var yPoints2 = <?php echo '["' . implode('", "', $heatY2) . '"]' ?>;

	dataPoints2 = [];
	for(var i=0; i<xPoints2.length; i++){
		dataPoints2.push({x: (parseFloat(xPoints2[i])+100)*2.4, y: (parseFloat(yPoints2[i])+42.5)*2.4, value: 1});
	}

	var testData2 = {
		min: 0,
        max: 10,
       data: dataPoints2
	};
	heatmapInstance2.setData(testData2);
	
	
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

	dataPoints = [];
	for(var i=0; i<xPoints.length; i++){
		dataPoints.push({x: (parseFloat(xPoints[i])+100)*2.4, y: (parseFloat(yPoints[i])+42.5)*2.4, value: 1});
	}

	var testData = {
		min: 0,
        max: 3,
		data: dataPoints
	};
	heatmapInstance.setData(testData);

	
	</script>
</body>



</html>