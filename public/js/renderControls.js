function renderControl(mCallBack) {
    let aRenderControlPromises = [];
    $("div[data-rendertype]").each(function (i, eDiv) {
        let jDiv = $(eDiv);
        let cRendertype = jDiv.attr("data-rendertype");
        let oData = {};
        $.each(eDiv.attributes, function () {
            if (this.name.startsWith("data-property-")) {
                oData[this.name.substring(14)] = this.value;
            }
        });
        aRenderControlPromises.push(json("post", "/renderControls/" + cRendertype, oData, function (oData) {
            jDiv.append(oData.cHtml);
            if (oData.cScript != "") { eval(oData.cScript); }
        }));
        ;
    });
    $.when.apply($, aRenderControlPromises).then(function () {
        if (typeof mCallBack === "function") mCallBack();
    });
}