<?php

namespace App\Jobs\Media;

/**
 * Publishes a processed chat image as a new message.
 *
 * Unlike avatar and branding, nothing exists yet to replace: the message — and,
 * for a pair's first message, the conversation itself — is created here, once
 * the image is known to be good. That ordering is deliberate. A failed upload
 * must not leave an empty conversation behind, which is exactly what would
 * happen if the conversation were opened when the request arrived.
 *
 * The message is broadcast and notified by `SendMessage`, so an image message
 * reaches the recipient through the same path a text message does.
 */
class ProcessChatImageUpload extends ProcessQueuedImageUpload {}
