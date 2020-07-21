function onFormChange(target) {
    renderFieldTable(target.value);
    renderDistributionChart(target.value);

}
function filterData(data, formName) {
    if (formName === 'all') {
        return data;
    }
    var newData = [];
    while (data.length!== 0) {
        var currData = data.shift();
        if (formName == currData.label) {
            newData.push(currData);
            return newData;
        }
    }
    return newData;
}