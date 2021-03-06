function renderDistributionChart(formName) {
    var chartContainer = document.getElementById("distributionchart");
    var messageContainer = document.getElementById("distributionmessage");

    messageContainer.innerHTML = "<h3> Distribution of Users by Time Spent on Form</h3>";
    var raw_data = JSON.parse(chartContainer.getAttribute('data-data'));
    var data = filterData(raw_data, formName);
    if (formName === "all" || formName === "" || data.length === 0) {
        messageContainer.innerHTML = "<h3>Chart distribution is only available for individual forms.</h3>";
        chartContainer.innerHTML = "";
        return;
    }
    var myChartData = processDistributionData(data);
    function processDistributionData(data) {
        var chartData = [];
        while (data.length !== 0) {
            var formData = data.shift();
            var formName = formData.label;
            var formSessions = formData.sessions;
            formSessions.forEach(processSession);
            function processSession(session) {
                chartData = addField(session.userId, session.timeSpent/60, session.nb_focusin,
                    session.longest_field, session.longest_field_time, chartData);
            }
        }
        return chartData;
    }

    function processUser(userId, user, chartData) {
        var time = user.timeSpent / 60;
        var focusIns = user.nb_focusin;
        var longestField = user.longest_field;
        var longestFieldTime = user.longest_field_time;
        chartData = addField(userId, time, focusIns, longestField, longestFieldTime, chartData);
        return chartData;
    }

    function addField(userId, time, focusIns, longestField, longestFieldTime, chartData) {
        chartData.push({
            "time": time.toFixed(2),
            "focusIns": focusIns,
            "longestField": longestField,
            "longestFieldTime": longestFieldTime,
            "userId": userId,
        });
        return chartData;
    }


    // amCharts beging
    am4core.useTheme(am4themes_animated);
// Themes end

// Create chart instance
    var chart = am4core.create("distributionchart", am4charts.XYChart);

    chart.data = myChartData;

// Create times
    var valueAxisX = chart.xAxes.push(new am4charts.ValueAxis());
    valueAxisX.title.text = 'Time Spent by User (min)';
    valueAxisX.renderer.minGridDistance = 40;

// Create value focus-ins
    var valueAxisY = chart.yAxes.push(new am4charts.ValueAxis());
    valueAxisY.title.text = 'Number of Focus-ins on Form';

// Create series
    var lineSeries = chart.series.push(new am4charts.LineSeries());
    lineSeries.dataFields.valueY = "focusIns";
    lineSeries.dataFields.valueX = "time";
    lineSeries.strokeOpacity = 0;

// Add a bullet
    var bullet = lineSeries.bullets.push(new am4charts.Bullet());

// Add a triangle to act as am arrow
    var arrow = bullet.createChild(am4core.Circle);
    arrow.horizontalCenter = "middle";
    arrow.verticalCenter = "middle";
    arrow.strokeWidth = 0;
    arrow.strokeOpacity = 0.8;
    arrow.fill = am4core.color('#8CA8D9');
    arrow.direction = "top";
    arrow.width = 15;
    arrow.height = 15;
    arrow.tooltipText = "Time spent: {time}min\n Focus-ins: {focusIns}\n " +
        "Longest Field Time: [bold]{longestFieldTime}s[/] on [bold]{longestField}[/]\n" +
        "User Id: {userId}";


//scrollbars
    chart.scrollbarX = new am4core.Scrollbar();
    chart.scrollbarY = new am4core.Scrollbar();
}