const assert = require('node:assert/strict');
const crypto = require('node:crypto');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');

const scriptSource = fs.readFileSync(path.join(__dirname, '../../google-apps-script/Code.gs'), 'utf8');
const secret = 'test-only-shared-secret';

function makeRelay(sendEmail = () => {}) {
  const properties = new Map([['LITTLE_STEPS_SHARED_SECRET', secret], ['LITTLE_STEPS_SENDER_NAME', 'Little Steps']]);
  let sent = 0;
  const store = {
    getProperty: key => properties.get(key) || null,
    setProperty: (key, value) => properties.set(key, value),
    deleteProperty: key => properties.delete(key),
    getProperties: () => Object.fromEntries(properties),
  };
  const context = {
    Date,
    JSON,
    Object,
    String,
    Error,
    LockService: { getScriptLock: () => ({ tryLock: () => true, releaseLock: () => {} }) },
    PropertiesService: { getScriptProperties: () => store },
    Utilities: {
      Charset: { UTF_8: 'utf8' },
      DigestAlgorithm: { SHA_256: 'sha256' },
      computeHmacSha256Signature: (value, key) => [...crypto.createHmac('sha256', key).update(value, 'utf8').digest()],
      computeDigest: (_algorithm, value) => [...crypto.createHash('sha256').update(value, 'utf8').digest()],
      newBlob: value => ({ getBytes: () => [...Buffer.from(value, 'utf8')] }),
    },
    MailApp: { sendEmail: message => { sent++; sendEmail(message); } },
    ContentService: {
      MimeType: { JSON: 'application/json' },
      createTextOutput: content => ({ content, setMimeType() { return this; } }),
    },
  };
  vm.createContext(context);
  vm.runInContext(scriptSource, context);
  return { context, get sent() { return sent; } };
}

function post(context, payload, signature = null) {
  const encoded = JSON.stringify(payload);
  const digest = signature || crypto.createHmac('sha256', secret).update(encoded, 'utf8').digest('hex');
  const output = context.doPost({ postData: { contents: JSON.stringify({ payload: encoded, signature: digest }) } });
  return JSON.parse(output.content);
}

function samplePayload(eventKey = 'appointment:42:payment_succeeded:user:7') {
  return { event_key: eventKey, to: 'patient@example.test', sender_name: 'Little Steps', subject: 'Payment confirmed', html: '<p>Appointment update</p>', text: 'Appointment update' };
}

test('rejects invalid signatures without sending mail', () => {
  const relay = makeRelay();
  const result = post(relay.context, samplePayload(), '0'.repeat(64));
  assert.equal(result.ok, false);
  assert.equal(result.error, 'invalid_signature');
  assert.equal(relay.sent, 0);
});

test('sends a valid message once and suppresses duplicate event keys', () => {
  const relay = makeRelay(message => assert.equal(message.name, 'Little Steps'));
  const payload = samplePayload();
  assert.equal(post(relay.context, payload).sent, true);
  assert.equal(post(relay.context, payload).duplicate, true);
  assert.equal(relay.sent, 1);
});

test('clears a failed send reservation so the outbox may retry', () => {
  let fail = true;
  const relay = makeRelay(() => { if (fail) throw new Error('temporary Gmail failure'); });
  const payload = samplePayload('appointment:42:payment_failed:user:7');
  assert.equal(post(relay.context, payload).retry, true);
  fail = false;
  assert.equal(post(relay.context, payload).sent, true);
  assert.equal(relay.sent, 2);
});
