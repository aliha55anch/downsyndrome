const PROPERTY_SECRET = 'LITTLE_STEPS_SHARED_SECRET';
const PROPERTY_SENDER_NAME = 'LITTLE_STEPS_SENDER_NAME';
const PROPERTY_EVENT_PREFIX = 'appointment_email_';
const MAX_HTML_BYTES = 180 * 1024;
const IDEMPOTENCY_RETENTION_MS = 30 * 24 * 60 * 60 * 1000;
const IN_PROGRESS_RETENTION_MS = 15 * 60 * 1000;

function doGet() {
  return jsonOutput({ ok: true, service: 'Little Steps appointment email relay' });
}

function doPost(event) {
  const lock = LockService.getScriptLock();
  if (!lock.tryLock(10000)) return jsonOutput({ ok: false, retry: true, error: 'busy' });

  try {
    const secret = PropertiesService.getScriptProperties().getProperty(PROPERTY_SECRET);
    if (!secret) return jsonOutput({ ok: false, retry: false, error: 'secret_not_configured' });
    const raw = event && event.postData ? String(event.postData.contents || '') : '';
    if (!raw || raw.length > 260 * 1024) return jsonOutput({ ok: false, retry: false, error: 'invalid_request_size' });

    const envelope = JSON.parse(raw);
    if (typeof envelope.payload !== 'string' || typeof envelope.signature !== 'string') {
      return jsonOutput({ ok: false, retry: false, error: 'invalid_envelope' });
    }
    const expected = hmacHex(envelope.payload, secret);
    if (!constantTimeEquals(expected, envelope.signature.toLowerCase())) {
      return jsonOutput({ ok: false, retry: false, error: 'invalid_signature' });
    }

    const message = JSON.parse(envelope.payload);
    validateMessage(message);
    const properties = PropertiesService.getScriptProperties();
    const now = Date.now();
    cleanupIdempotencyRecords(properties, now);
    const recordKey = PROPERTY_EVENT_PREFIX + sha256Hex(message.event_key);
    const prior = properties.getProperty(recordKey);
    if (prior) {
      const saved = JSON.parse(prior);
      if (saved.state === 'sent') return jsonOutput({ ok: true, duplicate: true });
      if (saved.state === 'sending' && now - saved.time < IN_PROGRESS_RETENTION_MS) {
        return jsonOutput({ ok: false, retry: true, error: 'message_in_progress' });
      }
    }

    properties.setProperty(recordKey, JSON.stringify({ state: 'sending', time: now }));
    try {
      const senderName = properties.getProperty(PROPERTY_SENDER_NAME) || message.sender_name || 'Little Steps';
      const mailOptions = { htmlBody: message.html, name: senderName };
      if (message.reply_to) mailOptions.replyTo = message.reply_to;
      const mail = Object.assign({
        to: message.to,
        subject: message.subject,
        body: message.text || 'Please view this appointment update in Little Steps.',
      }, mailOptions);
      MailApp.sendEmail(mail);
      properties.setProperty(recordKey, JSON.stringify({ state: 'sent', time: Date.now() }));
      return jsonOutput({ ok: true, sent: true });
    } catch (sendError) {
      properties.deleteProperty(recordKey);
      return jsonOutput({ ok: false, retry: true, error: safeError(sendError) });
    }
  } catch (error) {
    return jsonOutput({ ok: false, retry: false, error: safeError(error) });
  } finally {
    lock.releaseLock();
  }
}

function validateMessage(message) {
  if (!message || typeof message !== 'object') throw new Error('invalid_message');
  if (typeof message.event_key !== 'string' || message.event_key.length < 1 || message.event_key.length > 240) throw new Error('invalid_event_key');
  if (typeof message.to !== 'string' || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(message.to)) throw new Error('invalid_recipient');
  if (typeof message.subject !== 'string' || !message.subject.trim() || message.subject.length > 200) throw new Error('invalid_subject');
  if (typeof message.html !== 'string' || Utilities.newBlob(message.html).getBytes().length > MAX_HTML_BYTES) throw new Error('invalid_html_size');
  if (message.text !== undefined && typeof message.text !== 'string') throw new Error('invalid_text');
}

function cleanupIdempotencyRecords(properties, now) {
  const all = properties.getProperties();
  Object.keys(all).forEach(function (key) {
    if (!key.startsWith(PROPERTY_EVENT_PREFIX)) return;
    try {
      const record = JSON.parse(all[key]);
      const age = now - Number(record.time || 0);
      const retention = record.state === 'sending' ? IN_PROGRESS_RETENTION_MS : IDEMPOTENCY_RETENTION_MS;
      if (age > retention) properties.deleteProperty(key);
    } catch (_) {
      properties.deleteProperty(key);
    }
  });
}

function hmacHex(value, secret) {
  return Utilities.computeHmacSha256Signature(value, secret, Utilities.Charset.UTF_8)
    .map(function (byte) { return ('0' + (byte & 255).toString(16)).slice(-2); })
    .join('');
}

function sha256Hex(value) {
  return Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256, value, Utilities.Charset.UTF_8)
    .map(function (byte) { return ('0' + (byte & 255).toString(16)).slice(-2); })
    .join('');
}

function constantTimeEquals(left, right) {
  if (left.length !== right.length) return false;
  let difference = 0;
  for (let i = 0; i < left.length; i++) difference |= left.charCodeAt(i) ^ right.charCodeAt(i);
  return difference === 0;
}

function safeError(error) {
  return String(error && error.message ? error.message : 'email_relay_error').slice(0, 240);
}

function jsonOutput(value) {
  return ContentService.createTextOutput(JSON.stringify(value)).setMimeType(ContentService.MimeType.JSON);
}
