const slogans = [
    'Cattr - a free self-hosted time tracker',
    'Track time. Own your data.',
    'Time tracking built for your infrastructure',
    'Manage your time with ease',
];

const getRandomInt = max => {
    return Math.floor(Math.random() * Math.floor(max));
};

export default () => {
    return slogans[getRandomInt(slogans.length)];
};
