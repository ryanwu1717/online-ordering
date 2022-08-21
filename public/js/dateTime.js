function isDate(obj) {
    return typeof obj === "date";
}
//1. 不帶參數 -> now    2. 字串   3. 日期
function dateTime(dDateTime) {
    if (dDateTime === undefined) {
        dDateTime = new Date();
    } else if (isString(dDateTime)) {
        dDateTime = new Date(dDateTime);
    } else if (!isDate(dDateTime)) {
        dDateTime = new Date();
    }

    let iDay = dDateTime.getDate();
    let cDay = padLeft(iDay, 2);
    let iMonth = dDateTime.getMonth() + 1;
    let cMonth = padLeft(iMonth, 2);
    let iYear = dDateTime.getFullYear();

    let cDate = iYear + "-" + cMonth + "-" + cDay;

    let iHours = dDateTime.getHours();
    let iMinutes = dDateTime.getMinutes();
    let iSeconds = dDateTime.getSeconds();
    let iMilliseconds = dDateTime.getMilliseconds();
    let oTime = todayMilliseconds(iHours, iMinutes, iSeconds, iMilliseconds);

    let cDateTime = cDate + ' ' + oTime.cTime;
    let cDateTimeWithMillisecond = cDate + " " + oTime.cTimeWithMillisecond;

    let oDate = {
        iYear: iYear, iMonth: iMonth, iDay: iDay,
        cMonth: cMonth, cDay: cDay,
        cDate: cDate, cDateTime: cDateTime, cDateTimeWithMillisecond: cDateTimeWithMillisecond
    };
    return $.extend(oDate, oTime);
}

//function todaySeconds(iTodaySeconds) {
function todaySeconds(iHours, iMinutes, iSeconds) {
    if (iMinutes === undefined) {
        iHours = iHours * 1000;
	}
    return todayMilliseconds(iHours, iMinutes, iSeconds, 0);
}

//function todayMilliseconds(iTodayMilliseconds)
function todayMilliseconds(iHours, iMinutes, iSeconds, iMilliseconds) {
    let iTodaySeconds = 0;
    let iTodayMilliseconds = 0;
    if (iMinutes === undefined) {
        iTodayMilliseconds = iHours;
        let iTemp = iTodayMilliseconds;
        iHours = 0; while (iTemp >= 3600000) { iTemp -= 3600000; iHours++; }
        iMinutes = 0; while (iTemp >= 60000) { iTemp -= 60000; iMinutes++; }
        iSeconds = 0; while (iTemp >= 1000) { iTemp -= 1000; iSeconds++; }
        iMilliseconds = iTemp;
        iTodaySeconds = (iTodayMilliseconds - iMilliseconds) / 1000;
    } else {
        iTodaySeconds = iHours * 3600 + iMinutes * 60 + iSeconds;
        iTodayMilliseconds = iTodaySeconds * 1000 + iMilliseconds;
	}

    let cHours = padLeft(iHours, 2);
    let cMinutes = padLeft(iMinutes, 2);
    let cSeconds = padLeft(iSeconds, 2);
    let cMilliseconds = padLeft(iMilliseconds, 3);

    let cTime = cHours + ":" + cMinutes + ":" + cSeconds;
    let cTimeWithMillisecond = cTime + "." + cMilliseconds;

    return {
        iHours: iHours, iMinutes: iMinutes, iSeconds: iSeconds, iMilliseconds: iMilliseconds,
        cHours: cHours, cMinutes: cMinutes, cSeconds: cSeconds, cMilliseconds: cMilliseconds,
        cTime: cTime, cTimeWithMillisecond: cTimeWithMillisecond,
        iTodaySeconds: iTodaySeconds, iTodayMilliseconds: iTodayMilliseconds
    };
}

var jDatePickerSelected;
function setDatePicker(cID, oOptions) {
    var jDatePicker = $('#' + cID);
    var eSetting = {
        beforeShow: function (input) { jDatePickerSelected = jDatePicker; },
        onSelect: function (dateText, inst) {
            if (typeof (onAscxDateSelect) == 'function') { onAscxDateSelect(jDatePickerSelected); }
            jDatePickerSelected = undefined;
        },
        onClose: function (dateText, inst) {
            if (typeof (jDatePickerSelected) != 'undefined') {
                jDatePickerSelected.removeClass('is-valid').removeClass('is-invalid');
                if (dateText != '') {
                    try {
                        $.datepicker.parseDate('yy-mm-dd', dateText);
                        jDatePickerSelected.addClass('is-valid');
                    } catch (e) {
                        jDatePickerSelected.addClass('is-invalid');
                    };
                }
            }
        },
        dateFormat: 'yy-mm-dd', changeYear: true, changeMonth: true,
        dayNamesMin: ['日', '一', '二', '三', '四', '五', '六'],
        monthNames: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
        monthNamesShort: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月']
    }
    eSetting = $.extend(eSetting, oOptions);
    jDatePicker.datepicker(eSetting);
}