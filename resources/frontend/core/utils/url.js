export function serialize(data, prefix) {
    let str = [],
        p;
    for (p in data) {
        if (Object.prototype.hasOwnProperty.call(data, p)) {
            let k = prefix ? prefix + '[' + p + ']' : p,
                v = data[p];
            str.push(
                v !== null && typeof v === 'object'
                    ? serialize(v, k)
                    : encodeURIComponent(k) + '=' + encodeURIComponent(v),
            );
        }
    }
    return str.join('&');
}
