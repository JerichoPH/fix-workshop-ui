String.prototype.format = function () {
    if (arguments.length === 0) return this;
    var param = arguments[0];
    var s = this;
    if (typeof (param) == 'object') {
        for (var key in param)
            s = s.replace(new RegExp("\\{" + key + "\\}", "g"), param[key]);
        return s;
    } else {
        for (var i = 0; i < arguments.length; i++)
            s = s.replace(new RegExp("\\{" + i + "\\}", "g"), arguments[i]);
        return s;
    }
};

String.prototype.tooLong = function (maxLength, end = '…') {
    return this.length > maxLength ? this.substr(0, maxLength) + end : this;
};

Array.prototype.chunk = function chunk(size) {
    if (this.length <= 0 || size <= 0) {
        return this;
    }

    let chunks = [];

    for (let i = 0; i < this.length; i = i + size) {
        chunks.push(this.slice(i, i + size));
    }

    return chunks;
};