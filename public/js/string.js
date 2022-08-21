function isString(obj) {
    return typeof obj === "string"
}
function padLeft(str, len) {	//¥ªÃä¸É¹s
    str = '' + str;
    return str.length >= len ? str : new Array(len - str.length + 1).join("0") + str;
}