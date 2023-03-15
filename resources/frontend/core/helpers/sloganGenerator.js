const slogans = ['Cattr - a free open source time tracker', 'Manage your time with ease'];

const getRandomInt = max => {
    return Math.floor(Math.random() * Math.floor(max));
};

export default () => {
    return slogans[getRandomInt(slogans.length)];
};
