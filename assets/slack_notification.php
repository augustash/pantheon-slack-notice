<?php

if ($_ENV['PANTHEON_ENVIRONMENT'] !== 'live') {
  exit;
}

// Important constants :)
$pantheon_yellow = '#EFD01B';

// Default values for parameters - this will assume the channel you define the webhook for.
// The full Slack Message API allows you to specify other channels and enhance the messagge further
// if you like: https://api.slack.com/docs/messages/builder
$defaults = array(
  'slack_username' => 'Pantheon/Slack Deploy Bot',
  'always_show_text' => true,
);

// Load our hidden credentials.
// See the README.md for instructions on storing secrets.
$secrets = _get_secrets(array('slack_url', 'slack_channel'), $defaults);

// Build an array of fields to be rendered with Slack Attachments as a table
// attachment-style formatting:
// https://api.slack.com/docs/attachments
$fields = array(
  array(
    'title' => 'Site',
    'value' => $_ENV['PANTHEON_SITE_NAME'],
    'short' => 'true'
  ),
  array( // Render Environment name with link to site, <http://{ENV}-{SITENAME}.pantheon.io|{ENV}>
    'title' => 'Environment',
    'value' => '<http://' . $_ENV['PANTHEON_ENVIRONMENT'] . '-' . $_ENV['PANTHEON_SITE_NAME'] . '.pantheonsite.io|' . $_ENV['PANTHEON_ENVIRONMENT'] . '>',
    'short' => 'true'
  ),
  array( // Render Name with link to Email from Commit message
    'title' => 'By',
    'value' => $_POST['user_email'],
    'short' => 'true'
  ),
  array( // Render workflow phase that the message was sent
    'title' => 'Workflow',
    'value' => ucfirst($_POST['stage']) . ' ' . str_replace('_', ' ',  $_POST['wf_type']),
    'short' => 'true'
  ),
  array(
    'title' => 'View Dashboard',
    'value' => '<https://dashboard.pantheon.io/sites/' . $_ENV['PANTHEON_SITE'] . '#' . $_ENV['PANTHEON_ENVIRONMENT'] . '/deploys|View Dashboard>',
    'short' => 'true'
  ),
);

// Customize the message based on the workflow type.  Note that slack_notification.php
// must appear in your pantheon.yml for each workflow type you wish to send notifications on.
switch ($_POST['wf_type']) {
  case 'deploy':
    // Find out what tag we are on and get the annotation.
    $deploy_tag = `git describe --tags`;
    $deploy_message = $_POST['deploy_message'];

    // Prepare the slack payload as per:
    // https://api.slack.com/incoming-webhooks
    $text = 'Deploy to the ' . $_ENV['PANTHEON_ENVIRONMENT'];
    $text .= ' environment of ' . $_ENV['PANTHEON_SITE_NAME'] . ' by ' . $_POST['user_email'] . ' complete!';
    $text .= ' <https://dashboard.pantheon.io/sites/' . $_ENV['PANTHEON_SITE'] . '#' . $_ENV['PANTHEON_ENVIRONMENT'] . '/deploys|View Dashboard>';
    // Build an array of fields to be rendered with Slack Attachments as a table
    // attachment-style formatting:
    // https://api.slack.com/docs/attachments
    $fields[] = array(
    'title' => 'Details',
    'value' => $text,
    'short' => 'false'
    );
    $fields[] = array(
    'title' => 'Deploy Note',
    'value' => $deploy_message,
    'short' => 'false'
    );
    break;

  case 'sync_code':
    // Get the committer, hash, and message for the most recent commit.
    $committer = `git log -1 --pretty=%cn`;
    $email = `git log -1 --pretty=%ce`;
    $message = `git log -1 --pretty=%B`;
    $hash = `git log -1 --pretty=%h`;

    // Prepare the slack payload as per:
    // https://api.slack.com/incoming-webhooks
    $text = 'Code was synced to the ' . '<https://' . $_ENV['PANTHEON_ENVIRONMENT'] . '-' . $_ENV['PANTHEON_SITE_NAME'] . '.pantheonsite.io|' . $_ENV['PANTHEON_ENVIRONMENT'] . '>' . ' environment of ' . '<https://dashboard.pantheon.io/sites/' . $_ENV['PANTHEON_SITE'] . '#' . $_ENV['PANTHEON_ENVIRONMENT'] . '/deploys|' . $_ENV['PANTHEON_SITE_NAME'] .'>' . ' by ' . $_POST['user_email'] . "!\n";
    $text .= 'Most recent commit: ' . rtrim($hash) . ' by ' . rtrim($committer)
    . "\n" . 'Commit Message: ' . $message;
    // Build an array of fields to be rendered with Slack Attachments as a table
    // attachment-style formatting:
    // https://api.slack.com/docs/attachments
    $fields[] = array(
    'title' => 'Commit',
    'value' => rtrim($hash),
    'short' => 'true'
    );
    $fields[] = array(
    'title' => 'Commit Message',
    'value' => $message,
    'short' => 'false'
    );
    break;

  case 'clear_cache':
    // Prepare the slack payload as per:
    // https://api.slack.com/incoming-webhooks
    $text = 'Cache was cleard for the ' . '<https://' . $_ENV['PANTHEON_ENVIRONMENT'] . '-' . $_ENV['PANTHEON_SITE_NAME'] . '.pantheonsite.io|' . $_ENV['PANTHEON_ENVIRONMENT'] . '>';
    $text .= ' environment of ' . $_ENV['PANTHEON_SITE_NAME'] . ' by ' . $_POST['user_email'] . ' complete!';
    $text .= ' <https://dashboard.pantheon.io/sites/' . $_ENV['PANTHEON_SITE'] . '#' . $_ENV['PANTHEON_ENVIRONMENT'] . '/deploys|View Dashboard>';

    $fields[] = array(
    'title' => 'Cleared caches',
    'value' => 'Cleared caches on the ' . $_ENV['PANTHEON_ENVIRONMENT'] . ' environment of ' . $_ENV['PANTHEON_SITE_NAME'] . "!\n",
    'short' => 'false'
    );
    break;

  default:
    $text = $_POST['qs_description'];
    break;
}

$attachment = array(
  'fallback' => $text,
  'pretext' => ($_POST['wf_type'] == 'clear_cache') ? 'Caches cleared :construction:' : 'Deploying :rocket:',
  'color' => $pantheon_yellow, // Can either be one of 'good', 'warning', 'danger', or any hex color code
);

// Live environment notifications only.
_slack_notification($secrets['slack_url'], $secrets['slack_channel'], $secrets['slack_username'], $text, $attachment, $secrets['always_show_text']);

/**
 * Get secrets from secrets file.
 *
 * @param array $requiredKeys List of keys in secrets file that must exist.
 */
function _get_secrets($requiredKeys, $defaults) {
  $secretsFile = $_SERVER['HOME'] . '/files/private/secrets.json';
  if (!file_exists($secretsFile)) {
    die('No secrets file found. Aborting!');
  }
  $secretsContents = file_get_contents($secretsFile);
  $secrets = json_decode($secretsContents, 1);
  if ($secrets == false) {
    die('Could not parse json in secrets file. Aborting!');
  }
  $secrets += $defaults;
  $missing = array_diff($requiredKeys, array_keys($secrets));
  if (!empty($missing)) {
    die('Missing required keys in json secrets file: ' . implode(',', $missing) . '. Aborting!');
  }
  return $secrets;
}

/**
 * Send a notification to slack
 */
function _slack_notification($slack_url, $channel, $username, $text, $attachment, $alwaysShowText = false) {
  $attachment['fallback'] = $text;
  $post = array(
    'username' => $username,
    'channel' => $channel,
    'icon_emoji' => ':lightning_cloud:',
    'attachments' => array($attachment)
  );
  if ($alwaysShowText) {
    $post['text'] = $text;
  }
  $payload = json_encode($post);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $slack_url);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  curl_exec($ch);
  curl_close($ch);
}
