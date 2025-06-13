# Pantheon Slack Deploy Notice
This module provides a way to setup a Slack notification through a webhook into a set channel upon a successful deploy to the LIVE Pantheon environment.

## Module Setup:

### Single Line:
```bash
composer config scripts.slack-notice "Augustash\\PantheonSlackNotice::postPackageInstall" && composer require augustash/pantheon-slack-notice && composer slack-notice
```

### Manual:
Add the following to root composer.json:

Root level:
```
"scripts": {
    "slack-notice": "Augustash\\PantheonSlackNotice::postPackageInstall"
}
```

Run:
```
composer require augustash/pantheon-slack-notice && composer slack-notice
```

## Note:
- You will need Pantheon's CLI tool [Terminus](https://github.com/pantheon-systems/terminus) as well as the [Terminus Secrets Plugin](https://github.com/pantheon-systems/terminus-secrets-plugin) to generate the secrets file automatically.
- You will be asked to provide your Pantheon site name and env.
- You will need to know your slack webhook URL and slack channel ID to pass to the prompt to generate a secrets file.