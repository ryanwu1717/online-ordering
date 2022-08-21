/* eObject = { 1. eObject 的 ID ( 但要自己帶 '#' 字首), 2. eObject DOM 物件, 3. eObject 的 Jquery } */
function switchClass(eObject, cClassA, cClassB) {
	let jObject = eObject instanceof jQuery ? eObject : $(eObject);
	if (jObject.hasClass(cClassA)) {
		jObject.removeClass(cClassA); jObject.addClass(cClassB);
	} else {
		jObject.removeClass(cClassB); jObject.addClass(cClassA);
	}
}
