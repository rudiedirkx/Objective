ObjectStore = function(url, store) {
	function req(data) {
		var p = new Promise(function(resolve, reject) {
			var datas = '';
			for (var k in data) datas += '&' + k + '=' + encodeURIComponent(data[k]);

			var xhr = new XMLHttpRequest;
			xhr.open('post', url, true);
			xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
			xhr.onload = function() {
				if (this.status != 200) return reject({error: 'http'});

				var json = this.responseText.substr(this.getResponseHeader('X-anti-hijack'));
				try {
					var rsp = JSON.parse(json);
					if (rsp.error) {
						return reject(rsp);
					}

					resolve(rsp);
				}
				catch (ex) {
					return reject({error: 'json'});
				}
			};
			xhr.onerror = function() {
				reject({error: 'http'});
			};
			xhr.send('store=' + encodeURIComponent(store) + datas);
		});
		return p;
	};
	this.get = function(name) {
		return req({
			get: name
		});
	};
	this.put = function(name, value) {
		return req({
			put: name,
			value: JSON.stringify(value)
		});
	};
	this.delete = function(name) {
		return req({
			"delete": name,
		});
	};
	this.push = function(name, value, unique) {
		return req({
			push: name,
			value: JSON.stringify(value),
			unique: unique ? 1 : 0
		});
	};
	this.pull = function(name, value, unique) {
		return req({
			pull: name,
			value: JSON.stringify(value),
			unique: unique ? 1 : 0
		});
	};
};
