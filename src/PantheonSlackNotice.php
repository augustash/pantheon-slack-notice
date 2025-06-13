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
      // Check if terminus is installed.
      $terminusPath = exec('which terminus');

      if (empty($terminusPath)) {
        $io->error('<error>Terminus is not installed. Please install Terminus to use Pantheon Slack Notice. https://github.com/pantheon-systems/terminus</error>');
      }
      else {
        // Get plugin list as JSON.
        $pluginJson = shell_exec('terminus self:plugin:list --format=json');
        $plugins = json_decode($pluginJson, TRUE);

        $pluginFound = FALSE;

        if (json_last_error() !== JSON_ERROR_NONE) {
          $io->error('<error>Failed to parse Terminus plugin list. Check Terminus installation or permissions.</error>');
        }
        elseif (is_array($plugins)) {
          foreach ($plugins as $plugin) {
            if (!empty($plugin['name']) && $plugin['name'] === 'terminus-secrets-plugin') {
              $pluginFound = TRUE;
              break;
            }
          }

          if (!$pluginFound) {
            $io->error('<error>Terminus Secrets plugin is not installed. Please install it with: `terminus self:plugin:install terminus-secrets-plugin`</error>');
          }
          else {
            // Prompt the user to add their Slack URL and channel.
            $slackUrl = $io->ask('<info>Enter your Slack webhook URL:</info>', '', function ($answer) {
              if (empty($answer)) {
                throw new \InvalidArgumentException('Slack webhook URL cannot be empty.');
              }
              return $answer;
            });
            $slackChannel = $io->ask('<info>Enter your Slack channel:</info>', '', function ($answer) {
              if (empty($answer)) {
                throw new \InvalidArgumentException('Slack channel can not be empty.');
              }
              return $answer;
            });
            $site = $io->ask('<info>Enter your Pantheon site.env:</info>', '', function ($answer) {
              if (empty($answer)) {
                throw new \InvalidArgumentException('Site can not be empty.');
              }
              return $answer;
            });
            $io->write('<info>Secrets plugin is installed. Write to ' . $site . ' to generate a secrets file.</info>');

            $secrets = [
              'slack_url' => $slackUrl,
              'slack_channel' => $slackChannel,
            ];

            foreach ($secrets as $key => $value) {
              $cmd = sprintf(
                'terminus secrets:set ' . $site . ' %s %s',
                escapeshellarg($key),
                escapeshellarg($value)
              );
              shell_exec($cmd);
            }

            $output = shell_exec('terminus secrets:list ' . escapeshellarg($site));
            $io->write(PHP_EOL);
            $io->write('<info>✅ Secrets file successfully generated for ' . $site . ':</info>' . PHP_EOL);
            $io->write('<comment>' . trim($output) . '</comment>' . PHP_EOL);
          }
        }
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
