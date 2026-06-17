import './bootstrap';
import './calendar';
import { initMqtt, onTopic } from './mqtt-client';

// Expose MQTT helpers globally for Alpine.js components in Blade templates
window._mqtt = { initMqtt, onTopic };

