var mysql = require('mysql');

var connect = mysql.createConnection({
    host: "localhost",
    user: "kett"
});

connect.connect(function(err) {
    if (err) throw err;
    console.log("Connected!");
});