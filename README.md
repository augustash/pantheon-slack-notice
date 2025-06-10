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
- You will need to manually move the secrets file in `web/sites/default/files/private/secrets.json` through your Pantheon environments.
- You will need to know your slack webhook URL and slack channel ID to pass to the prompt to generate a secrets file.