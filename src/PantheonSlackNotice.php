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

    // Try {
    //   $color = NULL;
    //   foreach ($event->getArguments() as $arg) {
    //     if (substr($arg, 0, 8) === '--color=') {
    //       $color = substr($arg, 8);
    //       break;
    //     }
    //   }
    //   if ($color === NULL) {
    //     $color = $io->ask('<info>Primary HEX color? (Default: #2780e3)</info>:' . "\n > #", '#2780e3');
    //   }
    //   $color = '#' . preg_replace('/[^a-f0-9]/', '', $color);.
    // $settingsPath = './.vscode/settings.json';
    //   $settings = [];
    //   if ($fileSystem->exists($settingsPath)) {
    //     $jsonString = file_get_contents($settingsPath);
    //     $settings = json_decode($jsonString, TRUE);
    //   }
    //   $settings['workbench.colorCustomizations']['titleBar.activeForeground'] = '#f1f1f1';
    //   $settings['workbench.colorCustomizations']['titleBar.inactiveForeground'] = '#f1f1f1cc';
    //   $settings['workbench.colorCustomizations']['titleBar.activeBackground'] = $color;
    //   $settings['workbench.colorCustomizations']['titleBar.inactiveBackground'] = $color . 'cc';
    //   $fileSystem->dumpFile($settingsPath, json_encode($settings, JSON_PRETTY_PRINT));
    // }
    // catch (\Error $e) {
    //   $io->error('<error>' . $e->getMessage() . '</error>');
    // }
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
  }

}
