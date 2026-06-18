import mqtt from 'mqtt';

let _client = null;
const _handlers = {};

export function getMqttClient() {
    return _client;
}

export function initMqtt({ userId, wsUrl, username, password }) {
    if (_client) return _client;

    const url = (location.protocol === 'https:' ? 'wss://' : 'ws://') + location.host + wsUrl;

    _client = mqtt.connect(url, {
        username,
        password,
        clientId: 'tm_browser_' + userId + '_' + Math.random().toString(16).slice(2, 8),
        reconnectPeriod: 1000,
        connectTimeout: 10000,
        keepalive: 60,
    });

    _client.on('connect', () => {
        _client.subscribe([
            `tm/user/${userId}/notifications`,
            `tm/user/${userId}/messages/+`,
            `tm/presence/+`,
        ]);
    });

    _client.on('message', (topic, message) => {
        let payload;
        try { payload = JSON.parse(message.toString()); } catch { return; }

        const matchingKeys = Object.keys(_handlers).filter(pattern => topicMatches(pattern, topic));
        matchingKeys.forEach(key => _handlers[key].forEach(fn => fn(payload, topic)));
    });

    _client.on('error', () => {});

    return _client;
}

export function onTopic(pattern, handler) {
    if (!_handlers[pattern]) _handlers[pattern] = [];
    _handlers[pattern].push(handler);
}

function topicMatches(pattern, topic) {
    const patParts = pattern.split('/');
    const topParts = topic.split('/');
    if (patParts.length !== topParts.length && !pattern.endsWith('#')) return false;
    return patParts.every((p, i) => p === '#' || p === '+' || p === topParts[i]);
}
