function isString(obj) {
    return typeof obj === "string"
}
function padLeft(str, len) {	//����ɹs
    str = '' + str;
    return str.length >= len ? str : new Array(len - str.length + 1).join("0") + str;
}