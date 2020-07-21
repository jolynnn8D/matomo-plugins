function renderFieldTable(formName) {
// Themes begin
    am4core.useTheme(am4themes_animated);
// Themes end

    var chartContainer = document.getElementById("chartdiv");
    var averageTimeContainer = document.getElementById("average_time");
    var noDataMessageContainer = document.getElementById("messagecontainer");
    var raw_data = JSON.parse(chartContainer.getAttribute('data-data'));
    var data = filterData(raw_data, formName);
    var myChartData = [];

    if (data.length != 0) {
        noDataMessageContainer.innerHTML = "";
        if (formName === 'individual-field') {
            averageTimeContainer.innerHTML = '';
        } else {
            averageTimeContainer.innerHTML = "<h3>Average time spent on form: " + getAverageTime(data).toFixed(2) + " min</h3>";
        }
        var partitions = createPartitionsFromData(data);
        myChartData = processData(data, partitions);

    } else {
        noDataMessageContainer.innerHTML = "<h2>There is no data to display!</h2>";
        averageTimeContainer.innerHTML = "";
    }

    function processData(data, partitions) {
        var chartData = [];
        while (data.length != 0) {
            var form = data.shift();
            chartData = processForm(form, partitions, chartData);
            // chartData.push(form.fields);
        }

        return chartData;
    }

    function processForm(form, partitions, chartData) {
        var formName = form.label;
        var formFields = form.fields;
        for (var key in formFields) {
            processField(formFields[key]);
        }

        function processField(field) {
            var fieldName = field.label;
            var users = parseInt(field.users);
            var userGroup = retrieveUserString(users, partitions);
            var avgTime = field.average_time;
            var avgClicks = field.average_clicks;
            chartData = addField(fieldName, userGroup, users, avgTime, avgClicks, formName, chartData);
        }

        return chartData;
    }

    function addField(fieldName, userGroup, users, avgTime, avgClicks, formName, chartData) {
        chartData.push({
            "field": fieldName,
            "users": userGroup,
            "exactUsers": users,
            "time": avgTime,
            "clicks": avgClicks,
            "form": formName,
        });
        return chartData;
    }

    function retrieveUserString(users, partitions) {
        var returnString = "Others";
        partitions.forEach(isWithinRange);
        function isWithinRange(partition) {
            if (users >= partition.lower && users <= partition.upper) {
                returnString = partition.label;
            }
        }
        return returnString;
    }

    function createPartitions(min, max, step) {
        var lowerLimit = min;
        var upperLimit = lowerLimit + step - 1;
        var partitions = [];
        while (upperLimit < max) {
            var rangeString = lowerLimit + " to " + upperLimit + " users";
            partitions.push({
                lower: lowerLimit,
                upper: upperLimit,
                label: rangeString,
            })
            lowerLimit = upperLimit + 1;
            upperLimit = lowerLimit + step - 1;
        }
        partitions.push({
            lower: max,
            upper: Number.MAX_SAFE_INTEGER,
            label: max + " users+"
        })
        return partitions;
    }

    function createPartitionsFromData(data, noOfPartitions) {
        var maxUsers = 0;
        for (var formName in data) {
            var form = data[formName];
            var fields = form.fields;
            for (var key in fields) {
                var field = fields[key];
                var users = field.users;
                if (users > maxUsers) {
                    maxUsers = users;
                }
            }
        }
        noOfPartitions = noOfPartitions || defaultPartitions(maxUsers);
        const step = Math.round(maxUsers/noOfPartitions);
        const max = step * noOfPartitions;
        return createPartitions(0, max, step);
    }

    function defaultPartitions(maxUsers) {
        if (maxUsers <= 5) {
            return 2;
        } else if (maxUsers <= 11) {
            return 3;
        } else if (maxUsers <= 19) {
            return 4;
        } else {
            return 5;
        }
    }

    function getAverageTime(data) {
        var totalAverage = 0;
        var forms = 0;
        data.forEach(getTime);
        function getTime(form) {
            if (form.label !== 'individual-field') {
                var time = form.average_time;
                forms += 1;
                totalAverage = (totalAverage*(forms-1) + time) / forms;
            }
        }
        return totalAverage/60;
    }
    var chart = am4core.create("chartdiv", am4charts.XYChart);
    chart.maskBullets = false;

    var xAxis = chart.xAxes.push(new am4charts.CategoryAxis());
    var yAxis = chart.yAxes.push(new am4charts.CategoryAxis());

    xAxis.dataFields.category = "users";
    yAxis.dataFields.category = "field";

    xAxis.renderer.grid.template.disabled = true;
    xAxis.renderer.minGridDistance = 40;

    yAxis.renderer.grid.template.disabled = true;
    yAxis.renderer.inversed = true;
    yAxis.renderer.minGridDistance = 30;

    var series = chart.series.push(new am4charts.ColumnSeries());
    series.dataFields.categoryX = "users";
    series.dataFields.categoryY = "field";
    series.dataFields.value = "time";
    series.sequencedInterpolation = true;
    series.defaultState.transitionDuration = 3000;

    var bgColor = new am4core.InterfaceColorSet().getFor("background");

    var columnTemplate = series.columns.template;
    columnTemplate.strokeWidth = 1;
    columnTemplate.strokeOpacity = 0.2;
    columnTemplate.stroke = bgColor;
    columnTemplate.tooltipText = "[bold]Form: {form}[/]\n ---- \n " +
        "Field: {field}\n Average Time: {time}s\n " +
        "Average Clicks: {clicks}\n Users: {exactUsers}";
    columnTemplate.width = am4core.percent(100);
    columnTemplate.height = am4core.percent(100);

    series.heatRules.push({
        target: columnTemplate,
        property: "fill",
        min: am4core.color("#e6eff5"),
        max: am4core.color("#4873c4")
    });

// heat legend
    var heatLegend = chart.bottomAxesContainer.createChild(am4charts.HeatLegend);
    heatLegend.width = am4core.percent(100);
    heatLegend.series = series;
    heatLegend.valueAxis.renderer.labels.template.fontSize = 9;
    heatLegend.valueAxis.renderer.minGridDistance = 30;

// heat legend behavior
    series.columns.template.events.on("over", function(event) {
        handleHover(event.target);
    })

    series.columns.template.events.on("hit", function(event) {
        handleHover(event.target);
    })

    function handleHover(column) {
        if (!isNaN(column.dataItem.value)) {
            heatLegend.valueAxis.showTooltipAt(column.dataItem.value)
        }
        else {
            heatLegend.valueAxis.hideTooltip();
        }
    }

    series.columns.template.events.on("out", function(event) {
        heatLegend.valueAxis.hideTooltip();
    });

    chart.events.on("beforedatavalidated", function(ev) {
        chart.data.sort(function(a, b) {
            if (a.exactUsers < b.exactUsers) {
                if (a.users === b.users) {
                    return a.time - b.time;
                } else {
                    return -1;
                }
            } else if (a.exactUsers === b.exactUsers) {
                return a.time - b.time;
            } else {
                if (a.users === b.users) {
                    return a.time - b.time;
                } else {
                    return 1;
                }
            }
            // return a.exactUsers - b.exactUsers;
        });
    });
    chart.data = myChartData;
} // end am4core.ready()