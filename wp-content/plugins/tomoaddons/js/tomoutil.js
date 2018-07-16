function b64EncodeUnicode(str) {
    return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, function(match, p1) {
        return String.fromCharCode('0x' + p1);
    }));
}

function b64DecodeUnicode(str) {
    return decodeURIComponent(atob(str).split('').map(function(c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));
}

function insertParam(pairs) {
	var queryParameters = {}, queryString = location.search.substring(1), re = /([^&=]+)=([^&]*)/g, m;

	while (m = re.exec(queryString)) {
		queryParameters[decodeURIComponent(m[1])] = decodeURIComponent(m[2]);
	}
	
	Object.keys(pairs).forEach(function(key) {
		key = encodeURI(key);
		if(key == 'ft'){
			queryParameters[key] = b64EncodeUnicode(pairs[key]);
		} else {
			queryParameters[key] = encodeURI(pairs[key]);
		}
	});

	location.search = jQuery.param(queryParameters); // Causes page to reload
}