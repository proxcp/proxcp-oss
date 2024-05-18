var request = require('request');

var api = function(host, username, auth, password) {
	this.host = 'https://'+host+':8006';
	this.username = username+'@'+auth;
	this.password = password;
	this.loggedIn = false;
};

api.prototype.delete = function(target, data, next) {
	if (!next) {
		next = data;
		data = {};
	}

	if (!this.loggedIn) return this.login(function(err) {
		if (err) return next(err);

		this.delete(target, data, next);
	}.bind(this));

	// do request
	request({
		url: this.host+'/api2/json'+target,
		method: 'DELETE',
		strictSSL: false,
		form: data,
		headers: {
			'Cookie': 'PVEAuthCookie='+this.token.ticket,
			'CSRFPreventionToken': this.token.CSRFPreventionToken
		}
	}, function(err, response, body) {
		if (err) return next(err);

		var data = JSON.parse(body);
		if (data.errors) return next(JSON.stringify(data.errors));
		next(undefined, data.data);
	});
};

api.prototype.get = function(target, data, next) {
	if (!next) {
		next = data;
		data = {};
	}

	if (!this.loggedIn) return this.login(function(err) {
		if (err) return next(err);

		this.get(target, data, next);
	}.bind(this));

	// do request
	request({
		url: this.host+'/api2/json'+target,
		method: 'GET',
		strictSSL: false,
		qs: data,
		headers: {
			'Cookie': 'PVEAuthCookie='+this.token.ticket
		}
	}, function(err, response, body) {
		if (err) return next(err);

		try {
			var data = JSON.parse(body).data;
			next(undefined, data);
		} catch (err) {
			next([err, body]);
		}
	});
};

api.prototype.post = function(target, data, next) {
	if (!next) {
		next = data;
		data = {};
	}

	if (!this.loggedIn) return this.login(function(err) {
		if (err) return next(err);

		this.post(target, data, next);
	}.bind(this));

	// do request
	request({
		url: this.host+'/api2/json'+target,
		method: 'POST',
		strictSSL: false,
		form: data,
		headers: {
			'Cookie': 'PVEAuthCookie='+this.token.ticket,
			'CSRFPreventionToken': this.token.CSRFPreventionToken
		}
	}, function(err, response, body) {
		if (err) return next(err);

		var data = JSON.parse(body);
		if (data.errors) return next(JSON.stringify(data.errors));
		next(undefined, data.data);
	});
};

api.prototype.put = function(target, data, next) {
	if (!next) {
		next = data;
		data = {};
	}

	if (!this.loggedIn) return this.login(function(err) {
		if (err) return next(err);

		this.put(target, data, next);
	}.bind(this));

	// do request
	request({
		url: this.host+'/api2/json'+target,
		method: 'PUT',
		strictSSL: false,
		form: data,
		headers: {
			'Cookie': 'PVEAuthCookie='+this.token.ticket,
			'CSRFPreventionToken': this.token.CSRFPreventionToken
		}
	}, function(err, response, body) {
		if (err) return next(err);

		var data = JSON.parse(body);
		if (data.errors) return next(JSON.stringify(data.errors));
		next(undefined, data.data);
	});
};

api.prototype.login = function(next) {
	this.loggedIn = true;

	request({
		url: this.host+'/api2/json/access/ticket',
		method: 'POST',
		form: {
			username: this.username,
			password: this.password
		},
		strictSSL: false
	}, function(err, response, body) {
		if (err) return next(err);

		var data = JSON.parse(body).data;
		this.token = {ticket: data.ticket, CSRFPreventionToken: data.CSRFPreventionToken};

		next();
	}.bind(this));
};

module.exports = api;
