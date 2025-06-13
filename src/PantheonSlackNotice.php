<?php

namespace Augustash;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Pantheon Slack Notice console class.
 */
class PantheonSlackNotice {

  /**
   * Run on post-install-cmd.
   *
   * @param \Composer\Script\Event $event
   *   The event.
   */
  public static function postPackageInstall(Event $event) {
    $fileSystem = new Filesystem();
    $io = $event->getIO();

    // Secrets setup.
    try {
      $secretsPath = './web/sites/default/files/private/secrets.json';
      if (!$fileSystem->exists($secretsPath)) {
        $fileSystem->mkdir(dirname($secretsPath));
        $fileSystem->dumpFile($secretsPath, '{}');
      }
      // Prompt the user to add their Slack URL and channel.
      $slackUrl = $io->ask('<info>Enter your Slack webhook URL:</info>', '', function ($answer) {
        if (empty($answer)) {
          throw new \InvalidArgumentException('Slack webhook URL cannot be empty.');
        }
        return $answer;
      });
      $secrets = json_decode($fileSystem->exists($secretsPath) ? file_get_contents($secretsPath) : '{}', TRUE);
      $secrets['slack_url'] = $slackUrl;
      $fileSystem->dumpFile($secretsPath, json_encode($secrets, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
      $slackChannel = $io->ask('<info>Enter your Slack channel:</info>', '', function ($answer) {
        if (empty($answer)) {
          throw new \InvalidArgumentException('Slack channel can not be empty.');
        }
        return $answer;
      });
      if (!empty($slackChannel)) {
        $secrets['slack_channel'] = $slackChannel;
        $fileSystem->dumpFile($secretsPath, json_encode($secrets, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
      }
    }
    catch (\Error $e) {
      $io->error('<error>' . $e->getMessage() . '</error>');
    }

    // pantheon.yml.
    try {
      $ymlPath = './pantheon.yml';
      $workflowNotice = $fileSystem->exists($ymlPath) ? file_get_contents($ymlPath) : '';
      if (strpos($workflowNotice, 'workflows:') === FALSE) {
        $workflowNotice .= "\n" . file_get_contents(__DIR__ . '/../assets/pantheon.yml.append');
        $fileSystem->dumpFile($ymlPath, $workflowNotice);
      }
    }
    catch (\Error $e) {
      $io->error('<error>' . $e->getMessage() . '</error>');
    }

    // Slack notification script.
    try {
      $scriptPath = './web/private/scripts/slack_notification.php';
      $fileSystem->mkdir(dirname($scriptPath));
      // Always overwrite the file, even if it exists.
      $fileSystem->dumpFile($scriptPath, file_get_contents(__DIR__ . '/../assets/slack_notification.php'));
    }
    catch (\Error $e) {
      $io->error('<error>' . $e->getMessage() . '</error>');
    }
  }

}
