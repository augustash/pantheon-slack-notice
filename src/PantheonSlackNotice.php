<?php

namespace Augustash;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;


/**
 * Pantheon Slack Notice console class.
 */
class PantheonSlackNotice {

  /**
   * Path to config file.
   *
   * @var string
   */
  private static $configPath = __DIR__ . '/../../../../.ddev/config.yaml';

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
      // Check if terminus is installed.
      $terminusPath = exec('which terminus');

      if (empty($terminusPath)) {
        $io->error('<error>Terminus is not installed. Please install Terminus to use Pantheon Slack Notice. https://github.com/pantheon-systems/terminus</error>');
        exit;
      }

      // Check for Terminus Secrets Plugin.
      $secretsPlugin = shell_exec('terminus self:plugin:list --field=name | grep terminus-secrets-plugin');
      if (!$secretsPlugin) {
        shell_exec('terminus self:plugin:install terminus-secrets-plugin');
      }

      // Prompt the user to add their Slack URL and channel.
      $slackUrl = $io->ask('<info>Enter your Slack webhook URL: </info>', '', function ($answer) {
        if (empty($answer)) {
          throw new \InvalidArgumentException('Slack webhook URL cannot be empty.');
        }
        return $answer;
      });
      $slackChannel = $io->ask('<info>Enter your Slack channel: </info>', '', function ($answer) {
        if (empty($answer)) {
          throw new \InvalidArgumentException('Slack channel can not be empty.');
        }
        return $answer;
      });

      $site = str_replace('project=', '', Yaml::parseFile(static::$configPath)['web_environment'][0]);
      $io->write('<info>Writing secrets to ' . $site . ' file.</info>');

      $secrets = [
        'slack_url' => $slackUrl,
        'slack_channel' => $slackChannel,
      ];

      foreach ($secrets as $key => $value) {
        shell_exec('terminus secrets:set ' . $site . ' ' . $key . ' ' . $value);
      }

      $output = shell_exec('terminus secrets:list ' . escapeshellarg($site));
      $io->write(PHP_EOL);
      $io->write('<info>✅ Secrets file successfully generated for ' . $site . ':</info>' . PHP_EOL);
      $io->write('<comment>' . trim($output) . '</comment>' . PHP_EOL);
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
