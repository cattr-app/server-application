export function getInitials(str) {
    const names = str.split(' ');
    let initials = names[0].substring(0, 1).toUpperCase();

    if (names.length > 1) {
        initials += names[names.length - 1].substring(0, 1).toUpperCase();
    }

    return initials;
}

export function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}
