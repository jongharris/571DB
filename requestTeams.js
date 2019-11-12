const TEAMS_URL = "https://statsapi.web.nhl.com/api/v1/teams";

const promise = fetch(TEAMS_URL);
//make html for this
promise
    .then(function(response) {
    const processingPromise = response.json();
    return processingPromise;
})
    .then(function(processedResponse) {
    for (var i = 0; i < 31; i++) {
    
    //Access teams[i].id, teams[i].name, teams[i].division.name, teams[i].conference.name, teams[i].locationName
    //Store data from teams into DB using PHP
    teamName = processedResponse.teams[i];
    }
})
