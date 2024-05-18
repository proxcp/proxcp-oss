var Api = require('./proxmox-api.js');

var Proxmox = function(host, username, realm, password) {
	this.proxmox = new Api(host, username, realm, password);
};
Proxmox.prototype.get = function(target, data, next) {
	this.proxmox.get(target, data, function(err, data) {
		if(err) {
			return next(err);
		}else{
			return next(undefined, data);
		}
	});
};
Proxmox.prototype.delete = function(target, data, next) {
	this.proxmox.delete(target, data, function(err, data) {
		if(err) {
			return next(err);
		}else{
			return next(undefined, data);
		}
	});
};
Proxmox.prototype.post = function(target, data, next) {
	this.proxmox.post(target, data, function(err, data) {
		if(err) {
			return next(err);
		}else{
			return next(undefined, data);
		}
	});
};
Proxmox.prototype.put = function(target, data, next) {
	this.proxmox.put(target, data, function(err, data) {
		if(err) {
			return next(err);
		}else{
			return next(undefined, data);
		}
	});
};

module.exports = Proxmox;