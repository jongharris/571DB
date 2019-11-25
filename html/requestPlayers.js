function requestAllActivePlayers() {
    const TEAMSPLAYERS_URL = "http://statsapi.web.nhl.com/api/v1/teams/?expand=team.roster";

    const promise1 = fetch(TEAMSPLAYERS_URL);
    //make html for this
    promise1
        .then(function(response) {
        const processingPromise = response.json();
        return processingPromise;
    })
        .then(function(processedResponse) {
        console.log(processedResponse);
        for (var i = 0; i < 31; i++) {
        
            //Access teams[i].id, teams[i].name, teams[i].division.name, teams[i].conference.name, teams[i].locationName
            //Store data from teams into DB using PHP       
            console.log(teamName = processedResponse.teams[i]);
        }
    })
}