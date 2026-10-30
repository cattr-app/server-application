const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');
const moment = require('moment-timezone');

function loadDuration() {
    const file = fs.readFileSync(
        path.join(__dirname, '../../resources/frontend/core/modules/TimeIntervals/duration.js'),
        'utf8',
    );
    const script = file.replace(/^export /gm, '');
    const sandbox = {};
    vm.runInNewContext(
        script +
            '\nthis.api = { DEFAULT_DURATION_MINUTES, DURATION_PRESETS, MAX_DURATION_MINUTES, parseDuration,' +
            ' validateDuration, addDuration, durationBetween, formatDuration, createDurationHandlers, toIntervalPayload };',
        sandbox,
    );
    return sandbox.api;
}

const duration = loadDuration();

function createForm(values) {
    const writes = [];
    const form = { ...values };
    const setValue = (key, value) => {
        writes.push(key);
        form[key] = value;
    };
    return { form, writes, handlers: duration.createDurationHandlers(form, setValue) };
}

const elapsedSeconds = (start, end) => (Date.parse(end) - Date.parse(start)) / 1000;

test('presets are 15, 30, 60 and 120 minutes and the default stays 10 minutes', () => {
    assert.deepEqual([...duration.DURATION_PRESETS], [15, 30, 60, 120]);
    assert.equal(duration.DEFAULT_DURATION_MINUTES, 10);
});

test('selecting a preset or custom duration updates only the end time', () => {
    const start = '2026-10-30T09:00:00.000Z';
    for (const [minutes, end] of [
        [15, '2026-10-30T09:15:00.000Z'],
        [30, '2026-10-30T09:30:00.000Z'],
        [60, '2026-10-30T10:00:00.000Z'],
        [120, '2026-10-30T11:00:00.000Z'],
        ['1', '2026-10-30T09:01:00.000Z'],
        ['95', '2026-10-30T10:35:00.000Z'],
    ]) {
        const { form, writes, handlers } = createForm({ start_at: start, duration: 10 });
        form.duration = minutes;
        handlers.onDurationChange(minutes);
        assert.equal(form.start_at, start);
        assert.equal(form.end_at, end);
        assert.deepEqual(writes, ['end_at']);
    }
});

test('changing the start keeps the selected duration', () => {
    const { form, handlers } = createForm({ start_at: '2026-10-30T09:00:00.000Z', duration: 95 });
    handlers.onStartChange('2026-10-30T12:00:00.000Z');
    assert.equal(form.end_at, '2026-10-30T13:35:00.000Z');
    assert.equal(form.duration, 95);
});

test('default start change behaves as before (+10 minutes), even before the default is set', () => {
    const { form, handlers } = createForm({});
    handlers.onStartChange('2026-10-30T09:00:00.000Z');
    assert.equal(form.end_at, '2026-10-30T09:10:00.000Z');
});

test('manual end change synchronizes the duration without writing the end back', () => {
    const { form, writes, handlers } = createForm({
        start_at: '2026-10-30T09:00:00.000Z',
        end_at: '2026-10-30T09:10:00.000Z',
        duration: 10,
    });
    form.end_at = '2026-10-30T10:45:00.000Z';
    handlers.onEndChange(form.end_at, { source: 'user' });
    assert.equal(form.duration, 105);
    assert.deepEqual(writes, ['duration']);
    assert.equal(duration.formatDuration(form.duration), '105');
});

test('end before start makes the duration invalid instead of wrapping it', () => {
    const { form, handlers } = createForm({ start_at: '2026-10-30T09:00:00.000Z', duration: 10 });
    handlers.onEndChange('2026-10-30T08:00:00.000Z', { source: 'user' });
    assert.equal(form.duration, -60);
    assert.equal(duration.validateDuration(form.duration), 'not_positive');
});

test('invalid durations are rejected and never move the end time', () => {
    for (const [value, error] of [
        [0, 'not_positive'],
        ['0', 'not_positive'],
        [-5, 'not_positive'],
        [0.000001, 'not_positive'],
        ['0.01', 'not_positive'],
        ['-5', 'not_positive'],
        ['abc', 'invalid'],
        ['', 'invalid'],
        ['1e3', 'invalid'],
        ['0x10', 'invalid'],
        [NaN, 'invalid'],
        [Infinity, 'invalid'],
        [null, 'invalid'],
        [541, 'too_long'],
        ['100000', 'too_long'],
    ]) {
        assert.equal(duration.validateDuration(value), error, `value ${String(value)}`);
        const { form, writes, handlers } = createForm({ start_at: '2026-10-30T09:00:00.000Z', duration: value });
        handlers.onDurationChange(value);
        handlers.onStartChange('2026-10-30T10:00:00.000Z');
        assert.equal(form.end_at, undefined);
        assert.deepEqual(writes, []);
    }
    assert.equal(duration.validateDuration(540), null);
    assert.equal(duration.validateDuration(1 / 60), null);
    assert.ok(Date.parse(duration.addDuration('2026-10-30T09:00:00.000Z', 1 / 60)) > Date.parse('2026-10-30T09:00:00.000Z'));
    assert.equal(duration.validateDuration(' 95 '), null);
});

test('a duration crossing midnight produces one interval with correct instants', () => {
    const start = moment.tz('2026-10-30 23:30', 'Europe/Moscow').toISOString();
    const { form, handlers } = createForm({ start_at: start, duration: 10 });
    handlers.onDurationChange(60);
    assert.equal(moment(form.end_at).tz('Europe/Moscow').format('YYYY-MM-DD HH:mm'), '2026-10-31 00:30');
    assert.equal(elapsedSeconds(form.start_at, form.end_at), 3600);
});

test('DST spring-forward and fall-back keep the elapsed seconds', () => {
    const zone = 'America/New_York';
    const spring = moment.tz('2026-03-08 01:30', zone).toISOString();
    const springEnd = duration.addDuration(spring, 60);
    assert.equal(elapsedSeconds(spring, springEnd), 3600);
    assert.equal(moment(springEnd).tz(zone).format('HH:mm'), '03:30');

    const fall = moment.tz('2026-11-01 00:30', zone).toISOString();
    const fallEnd = duration.addDuration(fall, 120);
    assert.equal(elapsedSeconds(fall, fallEnd), 7200);
    assert.equal(moment(fallEnd).tz(zone).format('HH:mm'), '01:30');

    assert.equal(duration.durationBetween(fall, fallEnd), 120);
});

test('timezone switch keeps the custom duration', () => {
    const { form, handlers } = createForm({ start_at: '2026-10-30T09:00:00.000Z', duration: 10 });
    form.duration = '95';
    handlers.onDurationChange('95');

    // Both inputs re-emit the same instants for the new timezone; the end input may lag behind.
    const start = moment.tz(form.start_at, 'Asia/Tokyo').toISOString();
    form.start_at = start;
    handlers.onStartChange(start, { source: 'timezone' });
    form.end_at = '2026-10-30T09:10:00.000Z';
    handlers.onEndChange(form.end_at, { source: 'timezone' });

    assert.equal(form.duration, '95');
    assert.equal(form.end_at, '2026-10-30T10:35:00.000Z');
});

test('end input initialization does not overwrite the selected duration', () => {
    const { form, writes, handlers } = createForm({ start_at: '2026-10-30T09:00:00.000Z', duration: 30 });
    handlers.onEndChange('2026-10-30T09:10:00.000Z', { source: 'init' });
    assert.equal(form.duration, 30);
    assert.equal(form.end_at, '2026-10-30T09:30:00.000Z');
    assert.deepEqual(writes, ['end_at']);
});

test('handlers never trigger each other (no update loop)', () => {
    const writes = [];
    const form = { start_at: '2026-10-30T09:00:00.000Z', duration: 10 };
    let handlers;
    let depth = 0;
    const setValue = (key, value) => {
        depth += 1;
        assert.ok(depth < 2, 'setValue re-entered');
        writes.push(key);
        form[key] = value;
        depth -= 1;
    };
    handlers = duration.createDurationHandlers(form, setValue);
    handlers.onDurationChange(30);
    handlers.onStartChange('2026-10-30T10:00:00.000Z');
    handlers.onEndChange('2026-10-30T11:00:00.000Z', { source: 'user' });
    assert.deepEqual(writes, ['end_at', 'end_at', 'duration']);
});

test('submit payload keeps the existing time-intervals/create contract', () => {
    const { form, handlers } = createForm({
        is_manual: true,
        user_id: 1,
        task_id: 7,
        timezone: 'Europe/Moscow',
        start_at: '2026-10-30T09:00:00.000Z',
        duration: 10,
    });
    handlers.onDurationChange(30);
    form.duration = 30;
    assert.deepEqual(
        { ...duration.toIntervalPayload(form) },
        {
            is_manual: true,
            user_id: 1,
            task_id: 7,
            timezone: 'Europe/Moscow',
            start_at: '2026-10-30T09:00:00.000Z',
            end_at: '2026-10-30T09:30:00.000Z',
        },
    );
});

test('entry without shortcuts still submits start + 10 minutes', () => {
    const { form, handlers } = createForm({ is_manual: true, duration: 10 });
    form.start_at = '2026-10-30T09:00:00.000Z';
    handlers.onStartChange(form.start_at, { source: 'init' });
    handlers.onEndChange('2026-10-30T09:10:00.000Z', { source: 'init' });
    const payload = duration.toIntervalPayload(form);
    assert.equal(payload.end_at, '2026-10-30T09:10:00.000Z');
    assert.equal('duration' in payload, false);
});
