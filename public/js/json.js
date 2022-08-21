function json(cRequestType, cUrl, oData, mCallBack) {
    if (cRequestType.toLowerCase() == "post") {
        oData = typeof oData === "string" ? oData : JSON.stringify(oData);
    }
    return $.ajax({
        type: cRequestType, url: cUrl, contentType: "application/json", dataType: 'json',
        data: oData,
        error: function (xhr, textStatus, errorThrown) {
            alert(xhr.responseText);
            return;
        },
        success: function (oReturn) {
            console.log("success"); console.log(oReturn);
            if (oReturn.status === "failed") { alert(oReturn.message); return; }
            if (typeof mCallBack === "function") mCallBack(oReturn.data);
        }
    });
}