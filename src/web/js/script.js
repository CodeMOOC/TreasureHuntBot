/**
 * Created by saver on 30/03/2017.
 */
$("a[href='#map-next-tab']").on('shown.bs.tab', function(){
    var mapNextCenter = mapNext.getCenter();
    google.maps.event.trigger(mapNext, 'resize');
    mapNext.setCenter(mapNextCenter);
});

var markers =  [];
var markersNextLoc =  [];
var map;
var mapNext;
var teamChart;

function getRandColor(brightness){
    // Six levels of brightness from 0 to 5, 0 being the darkest
    var rgb = [Math.random() * 256, Math.random() * 256, Math.random() * 256];
    var mix = [brightness*51, brightness*51, brightness*51]; //51 => 255/5
    return [rgb[0] + mix[0], rgb[1] + mix[1], rgb[2] + mix[2]].map(function(x){ return Math.round(x/2.0)})
}

function getRGBA(color, opacity){
    return "rgba(" + color.join(",") + "," + opacity + ")";
}

function addMarkers(locations, markers, map) {
    for(var i = 0; i < locations.length; i++){
        //var image = 'web/images/teams-ico.png'; //may be the groups' icon
        markers[i] = new MarkerWithLabel({
            position: {lat: locations[i].lat, lng: locations[i].lng},
            map: map,
            icon: getAvatarUrl(locations[i].team, "70x70"),
            //animation: google.maps.Animation.DROP,
            labelContent: locations[i].team,
            labelAnchor: new google.maps.Point(150, 0),
            labelClass: "labels"
        });
    }
}

function cleanMarkers(markers){
    for(var i = 0; i < markers.length; i++){
        markers[i].setMap(null);
    }
    markers = [];
}

function getWinner(data){
    var winner = "";
    var max_pos = 0;

    for(var i = 0; i < data.length; i++){
        if(max_pos < data[i].pos){
            winner = data[i].team;
            max_pos = data[i].pos;
        }
    }

    return winner;
}

function getLabels(data){
    var labels = [];

    for(var i = 0; i < data.length; i++){
        labels[i] = data[i].team;
    }

    return labels;
}

function getChartDataset(data){
    var dataset = [];

    for(var i = 0; i < data.length; i++){
        dataset[i] = data[i].pos;
    }

    return dataset;
}

function getAvatarUrl(team, size) {

    size = size || "100x100";

    return "https://robohash.org/" + encodeURIComponent(team) + ".png?size=" + size;
}

function getNextLocData(data) {
    var nldata = [];
    for(var i = 0; i < data.length; i++) {
        nldata.push({"team":data[i].team,"lat":data[i].next_lat,"lng":data[i].next_lng});
    }

    return nldata;
}

function updateWinner(winner, team_count){
    document.getElementById("team-count").textContent = team_count;
    document.getElementById("winner-team").textContent = winner;
    document.getElementById("winner-avatar").src = getAvatarUrl(winner, "150x150");
}

function updateRank(data){
    $("#rank .list-group").html("");

    for(var i=0; i < data.length; i++){

        $("#rank .list-group").append('<div class="list-group-item clearfix">'+
            '<div class="col-xs-1">'+
            '    <img class="move-up" src="'+getAvatarUrl(data[i].team, '70x70')+'"/>'+
            '    </div>'+
            '    <div class="col-xs-11">'+
            '    <h3 class="list-group-item-heading">'+data[i].team+' <small>(#'+data[i].team_id+')</small></h3>'+
            '<p class="">' + getStatusMessage(data[i]) + '</p>'+
            '</div>'+
            '</div>');
    }
}

function updateChart(avatars, chartDataset){

    var colors = adaptColors(avatars.length, teamChart.data.datasets[0].backgroundColor, teamChart.data.datasets[0].borderColor);

    teamChart.data.datasets[0].backgroundColor = colors.backgroundColor;
    teamChart.data.datasets[0].borderColor = colors.borderColor;

    teamChart.data.labels = avatars;
    teamChart.data.datasets[0].data = chartDataset;
    teamChart.update();
}

function updateMap(data) {
    cleanMarkers(markers);
    addMarkers(data, markers, map);
}

function updateMapNextLoc(data) {
    cleanMarkers(markersNextLoc);
    addMarkers(data, markersNextLoc, mapNext);
}

function updateUI(data){

    //prepare data
    var winner = getWinner(data);
    var labels = getLabels(data);
    var avatars = labels.map(getAvatarUrl);
    var chartDataset = getChartDataset(data);

    updateWinner(winner, data.length);
    updateRank(data);
    updateChart(labels, chartDataset);
    updateMap(data);
    var nextLocsData = getNextLocData(data);
    updateMapNextLoc(nextLocsData);
}


function setupMap(locations){
    var mapCenter = {lat: 43.726276, lng: 12.635762};

    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 16,
        center: mapCenter
    });

    addMarkers(locations, markers, map);
}

function setupMapNextLoc(locations){
    var mapCenter = {lat: 43.726276, lng: 12.635762};

    mapNext = new google.maps.Map(document.getElementById('map-next'), {
        zoom: 16,
        center: mapCenter
    });

    addMarkers(locations, markersNextLoc, mapNext);
}

function generateBarColors(count){

    var data = {backgroundColor:[], borderColor:[]};

    for(var i = 0; i < count; i++){
        var c = getRandColor(5);
        data.backgroundColor[i] = getRGBA(c,0.2) + "";
        data.borderColor[i] = getRGBA(c,1.0) + "";
    }

    return data;
}

function getStatusMessage(data) {
    var message = "";

    switch (data.status){
        case 30:
            message = 'is reaching a new stop';
            break;
        case 32:
            message = 'is taking a selfie';
            break;
        case 34:
            message = 'is struggling with a quiz';
            break;
        case 40:
            message = 'is reaching the last stop';
            break;
        case 99:
            message = 'has completed the treasure hunt!';
            break;
    }
    return message;
}

function adaptColors(count, backgroundColors, borderColors){
    if(count <= backgroundColors.length){
        return {backgroundColor: backgroundColors, borderColor: borderColors};
    }

    var diff_count = count -  backgroundColors.length;
    var new_c = generateBarColors(diff_count);

    return {backgroundColor: backgroundColors.concat(new_c.backgroundColor),
        borderColor: borderColors.concat(new_c.borderColor)};
}

function updateChartSize(labels, ctx){
    ctx.canvas.height = 50 * labels.length;
    return ctx;
}

function setupChart(labels, chartDataset){

    var colors = generateBarColors(labels.length);
    var ctx = document.getElementById("teamChart").getContext("2d");
    updateChartSize(labels, ctx);

    teamChart = new Chart(ctx, {
        type: 'horizontalBar',
        data: {
            labels: labels,
            datasets: [{
                label: 'last known stop',
                data: chartDataset,
                backgroundColor: colors.backgroundColor,
                borderColor: colors.borderColor,
                borderWidth: 1
            }]
        },
        options: {
            legend: {
                display: false
            },
            scales: {
                xAxes: [{
                    ticks: {
                        suggestedMin: 0
                    }
                }],
                yAxes: [{
                    barPercentage: 0.7,
                    categoryPercentage:1.0
                }]
            }
        }
    });
}

function zeroFill( number, width )
{
    width -= number.toString().length;
    if ( width > 0 )
    {
        return new Array( width + (/\./.test( number ) ? 2 : 1) ).join( '0' ) + number;
    }
    return number + ""; // always return a string
}

function updateTimeot(timeout) {
    var now = new Date();
    var diffMs = (timeout - now); // milliseconds between now & Christmas
    var diffDays = Math.floor(diffMs / 86400000); // days
    var diffHrs = Math.floor((diffMs % 86400000) / 3600000); // hours
    var diffMins = Math.floor(((diffMs % 86400000) % 3600000) / 60000); // minutes
    var diffSecs = Math.floor((((diffMs % 86400000) % 3600000) % 60000) / 1000); // minutes

    //console.log("mancano: " + "days: " + diffDays + "  " + diffHrs + ":" + diffMins + ":" + diffSecs);

    if(diffHrs < 0 || diffMins < 0 || diffSecs < 0){
        $('#countdown').html("FINISHED!");
    } else {
        $('#hours').html(zeroFill(diffHrs, 2));
        $('#minutes').html(zeroFill(diffMins, 2));
        $('#seconds').html(zeroFill(diffSecs, 2));
    }
}

function setupTimeout(data){
    if(data.is_timeout_th){

        var timeout = new Date(Date.parse(data.timeout_value));
        //var timeout = new Date(2017, 2, 30, 16, 1-1);

        var timeout_date = new Date(Date.UTC(timeout.getFullYear(),
            timeout.getMonth(),
            timeout.getDate(),
            timeout.getHours(),
            timeout.getMinutes(),
            timeout.getSeconds()));

        updateTimeot(timeout_date);

        $("#timeout").removeClass('hidden');

        setInterval(function () {
            updateTimeot(timeout_date);

        }, 1000);
    }
}

function getData(callback){
    try {
        $.get("teams_status.php", function (data, status) {
            //alert("Data: " + data + "\nStatus: " + status);
            if (data && status == 'success') {
                callback(data);
            } else {
                console.error(status);
                console.error(data);
            }
        });
        /*$.post("teamStatus.php", function(data, status) {
         alert("Data: " + data + "\nStatus: " + status);
         if (data) {
         callback(data);
         }
         });*/
    }catch(ex){
        console.log(ex);
    }
}

function getTimeout(callback) {
    $.get("timeout_status.php", function (data, status){
        if(data && status == 'success'){
            callback(data);
        }else{
            console.error(status);
            console.error(data);
        }
    });
}

function setup() {

    getTimeout(function (data){
        setupTimeout(data);
    });

    getData(function (data) {

        //prepare data
        var winner = getWinner(data);
        var labels = getLabels(data);
        var chartDataset = getChartDataset(data);

        updateWinner(winner, data.length);
        updateRank(data);
        setupChart(labels, chartDataset);
        setupMap(data);
        var nextLocsData = getNextLocData(data);
        setupMapNextLoc(nextLocsData);

        setInterval(function () {
            getData(updateUI);

        }, 10000);
    });

}