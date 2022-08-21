var jMilHubProxy;
$(function () {
    if (typeof $.hubConnection === "undefined") { return; }
    let jConnection = $.hubConnection("http://172.25.25.34/signalr", { useDefaultPath: false });
    jMilHubProxy = jConnection.createHubProxy("MilHub");
    jMilHubProxy.on("NewRfidData", function (cIndexList) {
        if (typeof appendRfidTags === "function") { appendRfidTags(cIndexList); }
    });
    jMilHubProxy.on("NewZData", function (cIndexList) {
        if (typeof appendZValues === "function") { appendZValues(cIndexList); }
    });
    jMilHubProxy.on("NewDischargeData", function (cIndexList) {
        if (typeof appendDischargeValues === "function") { appendDischargeValues(cIndexList); }
    });
    jMilHubProxy.on("NewVibrationData", function (cIndexList) {
        if (typeof appendVibrationValues === "function") { appendVibrationValues(cIndexList); }
    });

    jConnection.start().done(function () {
    });
});
